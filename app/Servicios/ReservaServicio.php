<?php

declare(strict_types=1);

namespace App\Servicios;

use App\Core\Autenticacion;
use App\Core\BaseDatos;
use App\Core\ExcepcionValidacion;
use PDOException;

/**
 * Gestión de reservas (Sprint 4): CU-11 a CU-16.
 *
 * Garantía contra la doble reserva:
 *   1. Dentro de una transacción se bloquean las filas de las habitaciones
 *      elegidas (SELECT ... FOR UPDATE): dos recepcionistas que intenten la
 *      misma habitación al mismo tiempo quedan en fila.
 *   2. Se vuelve a verificar que no exista otra reserva activa cuyas fechas
 *      se crucen (ingreso < salida_otra AND salida > ingreso_otra).
 *   3. Como última defensa, los disparadores trg_*_antitraslape de MySQL
 *      rechazan cualquier traslape aunque se inserte por fuera del sistema.
 */
final class ReservaServicio
{
    public const MODIFICABLES = ['PENDIENTE', 'CONFIRMADA'];

    // ------------------------------------------------------------------
    // CU-11 Registrar reserva
    // ------------------------------------------------------------------
    public static function registrar(array $d): int
    {
        $ingreso = (string) ($d['fecha_ingreso'] ?? '');
        $salida = (string) ($d['fecha_salida'] ?? '');
        DisponibilidadServicio::validarRango($ingreso, $salida);
        if ($ingreso < date('Y-m-d')) {
            throw ExcepcionValidacion::campo('fecha_ingreso', 'La fecha de ingreso no puede ser anterior a hoy.');
        }
        $idHuesped = (int) ($d['id_huesped'] ?? 0);
        if (!$idHuesped || !BaseDatos::valor('SELECT 1 FROM huesped WHERE id_huesped = ?', [$idHuesped])) {
            throw ExcepcionValidacion::campo('id_huesped', 'Seleccione o registre al huésped titular de la reserva.');
        }
        $idCanal = (int) ($d['id_canal'] ?? 0);
        if (!BaseDatos::valor('SELECT 1 FROM canal_reserva WHERE id_canal = ?', [$idCanal])) {
            throw ExcepcionValidacion::campo('id_canal', 'Seleccione el canal por el que llegó la reserva.');
        }
        $personas = max(1, (int) ($d['cantidad_personas'] ?? 1));
        $habitaciones = array_values(array_unique(array_filter(array_map('intval', (array) ($d['habitaciones'] ?? [])))));
        $categoriaSobreventa = (int) ($d['sobreventa_categoria'] ?? 0);
        if (!$habitaciones && !$categoriaSobreventa) {
            throw ExcepcionValidacion::campo('habitaciones', 'Seleccione al menos una habitación disponible.');
        }
        $estado = in_array($d['estado'] ?? '', ['PENDIENTE', 'CONFIRMADA'], true) ? $d['estado'] : 'CONFIRMADA';

        try {
            return BaseDatos::transaccion(function () use ($d, $ingreso, $salida, $idHuesped, $idCanal, $personas, $habitaciones, $categoriaSobreventa, $estado) {
                $lineas = self::prepararLineas($habitaciones, $ingreso, $salida, null);

                $evaluacion = null;
                if ($categoriaSobreventa) {
                    $evaluacion = self::validarSobreventa($categoriaSobreventa, $ingreso, $salida);
                    $cot = TarifaServicio::cotizar($categoriaSobreventa, $ingreso, $salida);
                    $lineas[] = ['id_habitacion' => null, 'id_categoria' => $categoriaSobreventa, 'cotizacion' => $cot, 'capacidad' => (int) BaseDatos::valor('SELECT capacidad FROM categoria_habitacion WHERE id_categoria = ?', [$categoriaSobreventa])];
                }
                $capacidadTotal = array_sum(array_column($lineas, 'capacidad'));
                if ($personas > $capacidadTotal) {
                    throw ExcepcionValidacion::campo('cantidad_personas', "Las habitaciones elegidas admiten hasta $capacidadTotal persona(s). Agregue otra habitación.");
                }

                $idReserva = BaseDatos::insertar('reserva', [
                    'codigo'            => 'TMP-' . bin2hex(random_bytes(6)),
                    'id_huesped'        => $idHuesped,
                    'id_canal'          => $idCanal,
                    'id_usuario'        => Autenticacion::id(),
                    'fecha_ingreso'     => $ingreso,
                    'fecha_salida'      => $salida,
                    'cantidad_personas' => $personas,
                    'estado'            => $estado,
                    'monto_total'       => 0,
                    'es_sobreventa'     => $categoriaSobreventa ? 1 : 0,
                    'observaciones'     => self::texto($d['observaciones'] ?? null),
                ]);
                $codigo = self::codigo($idReserva);
                $total = self::insertarLineas($idReserva, $lineas);
                BaseDatos::actualizar('reserva', ['codigo' => $codigo, 'monto_total' => $total], 'id_reserva = :id', ['id' => $idReserva]);

                if (!empty($d['anticipo']) && (float) $d['anticipo'] > 0) {
                    self::insertarPago($idReserva, (float) $d['anticipo'], (int) ($d['id_metodo_pago'] ?? 1), 'ANTICIPO', $d['referencia'] ?? null, $total);
                }
                // CU-19: la decisión del recepcionista frente a la alerta queda registrada
                if ($evaluacion === null && !empty($d['alerta_vista'])) {
                    $evaluacion = SobreventaServicio::alertaParaRango($ingreso, $salida);
                }
                if ($evaluacion !== null) {
                    SobreventaServicio::guardarEvaluacion($evaluacion, 'RESERVA', $idReserva, 'CONTINUAR');
                }
                if (!empty($d['id_solicitud'])) {
                    BaseDatos::actualizar('solicitud_reserva', ['estado' => 'ATENDIDA', 'id_reserva' => $idReserva], 'id_solicitud = :id', ['id' => (int) $d['id_solicitud']]);
                }
                BitacoraServicio::registrar('Reservas', 'Registrar reserva', "Reserva $codigo del " . fecha($ingreso) . ' al ' . fecha($salida) . ($categoriaSobreventa ? ' (sobreventa)' : ''));
                return $idReserva;
            });
        } catch (PDOException $e) {
            throw self::traducirError($e);
        }
    }

    /** Bloquea las habitaciones y verifica su disponibilidad dentro de la transacción. */
    private static function prepararLineas(array $habitaciones, string $ingreso, string $salida, ?int $excluir): array
    {
        if (!$habitaciones) {
            return [];
        }
        $marcas = implode(',', array_fill(0, count($habitaciones), '?'));
        $filas = BaseDatos::filas(
            "SELECT h.id_habitacion, h.numero, h.estado, h.id_categoria, c.capacidad
               FROM habitacion h JOIN categoria_habitacion c ON c.id_categoria = h.id_categoria
              WHERE h.id_habitacion IN ($marcas) ORDER BY h.id_habitacion FOR UPDATE",
            $habitaciones
        );
        if (count($filas) !== count($habitaciones)) {
            throw ExcepcionValidacion::campo('habitaciones', 'Alguna de las habitaciones seleccionadas no existe.');
        }
        $lineas = [];
        foreach ($filas as $h) {
            if ($h['estado'] === 'MANTENIMIENTO') {
                throw ExcepcionValidacion::campo('habitaciones', "La habitación {$h['numero']} está en mantenimiento.");
            }
            if (!DisponibilidadServicio::estaDisponible((int) $h['id_habitacion'], $ingreso, $salida, $excluir)) {
                // CU-11 flujo 7a
                throw ExcepcionValidacion::campo('habitaciones', "La habitación {$h['numero']} acaba de ser reservada para esas fechas. Elija otra.");
            }
            $lineas[] = [
                'id_habitacion' => (int) $h['id_habitacion'],
                'id_categoria'  => (int) $h['id_categoria'],
                'capacidad'     => (int) $h['capacidad'],
                'cotizacion'    => TarifaServicio::cotizar((int) $h['id_categoria'], $ingreso, $salida),
            ];
        }
        return $lineas;
    }

    private static function insertarLineas(int $idReserva, array $lineas): float
    {
        $total = 0.0;
        foreach ($lineas as $l) {
            $cot = $l['cotizacion'];
            BaseDatos::insertar('detalle_reserva', [
                'id_reserva'    => $idReserva,
                'id_categoria'  => $l['id_categoria'],
                'id_habitacion' => $l['id_habitacion'],
                'precio_noche'  => $cot['promedio'],
                'noches'        => $cot['cantidad'],
                'subtotal'      => $cot['total'],
            ]);
            $total += $cot['total'];
        }
        return round($total, 2);
    }

    /**
     * Reserva en sobreventa (sin habitación asignada): sólo si ya no hay
     * habitaciones libres de la categoría y el modelo recomienda ACEPTAR
     * (o un administrador lo autoriza, CU-19 flujo 4a).
     */
    private static function validarSobreventa(int $idCategoria, string $ingreso, string $salida): array
    {
        $libres = DisponibilidadServicio::habitacionesDisponibles($ingreso, $salida, $idCategoria);
        if ($libres) {
            throw ExcepcionValidacion::campo('habitaciones', 'Aún hay habitaciones libres de esa categoría: asigne una en lugar de sobrevender.');
        }
        $precio = TarifaServicio::cotizar($idCategoria, $ingreso, $salida)['promedio'];
        $evaluacion = SobreventaServicio::alertaParaRango($ingreso, $salida, $precio)
            ?? SobreventaServicio::evaluar($ingreso, $precio);
        if ($evaluacion['decision'] === 'RECHAZAR' && !Autenticacion::puede('sobreventa.autorizar')) {
            throw ExcepcionValidacion::campo('habitaciones', 'El modelo de decisión recomienda RECHAZAR esta reserva adicional. Sólo un administrador puede autorizarla.');
        }
        return $evaluacion;
    }

    // ------------------------------------------------------------------
    // CU-13 Modificar reserva
    // ------------------------------------------------------------------
    public static function modificar(int $idReserva, array $d): void
    {
        $reserva = self::obtener($idReserva);
        if (!in_array($reserva['estado'], self::MODIFICABLES, true)) {
            // flujo 1a
            throw ExcepcionValidacion::campo('estado', 'Sólo se pueden modificar reservas pendientes o confirmadas.');
        }
        $ingreso = (string) $d['fecha_ingreso'];
        $salida = (string) $d['fecha_salida'];
        DisponibilidadServicio::validarRango($ingreso, $salida);
        if ($ingreso < date('Y-m-d') && $ingreso !== $reserva['fecha_ingreso']) {
            throw ExcepcionValidacion::campo('fecha_ingreso', 'La nueva fecha de ingreso no puede ser anterior a hoy.');
        }
        $habitaciones = array_values(array_unique(array_filter(array_map('intval', (array) ($d['habitaciones'] ?? [])))));
        $lineasSinHabitacion = BaseDatos::filas('SELECT id_categoria FROM detalle_reserva WHERE id_reserva = ? AND id_habitacion IS NULL', [$idReserva]);
        if (!$habitaciones && !$lineasSinHabitacion) {
            throw ExcepcionValidacion::campo('habitaciones', 'Seleccione al menos una habitación.');
        }
        try {
            BaseDatos::transaccion(function () use ($idReserva, $reserva, $d, $ingreso, $salida, $habitaciones, $lineasSinHabitacion) {
                BaseDatos::fila('SELECT id_reserva FROM reserva WHERE id_reserva = ? FOR UPDATE', [$idReserva]);
                $lineas = self::prepararLineas($habitaciones, $ingreso, $salida, $idReserva);
                foreach ($habitaciones ? [] : $lineasSinHabitacion as $s) {
                    $lineas[] = ['id_habitacion' => null, 'id_categoria' => (int) $s['id_categoria'], 'capacidad' => 4, 'cotizacion' => TarifaServicio::cotizar((int) $s['id_categoria'], $ingreso, $salida)];
                }
                $personas = max(1, (int) ($d['cantidad_personas'] ?? $reserva['cantidad_personas']));
                $capacidad = array_sum(array_column($lineas, 'capacidad'));
                if ($personas > $capacidad) {
                    throw ExcepcionValidacion::campo('cantidad_personas', "Las habitaciones elegidas admiten hasta $capacidad persona(s).");
                }
                // Se reemplaza el detalle completo: evita estados intermedios que choquen con el disparador
                BaseDatos::consulta('DELETE FROM detalle_reserva WHERE id_reserva = ?', [$idReserva]);
                BaseDatos::actualizar('reserva', [
                    'fecha_ingreso'      => $ingreso,
                    'fecha_salida'       => $salida,
                    'cantidad_personas'  => $personas,
                    'id_canal'           => (int) ($d['id_canal'] ?? $reserva['id_canal']),
                    'observaciones'      => self::texto($d['observaciones'] ?? null),
                    'es_sobreventa'      => $habitaciones ? 0 : (int) $reserva['es_sobreventa'],
                    'fecha_modificacion' => date('Y-m-d H:i:s'),
                ], 'id_reserva = :id', ['id' => $idReserva]);
                $total = self::insertarLineas($idReserva, $lineas);
                BaseDatos::actualizar('reserva', ['monto_total' => $total], 'id_reserva = :id', ['id' => $idReserva]);
                BitacoraServicio::registrar('Reservas', 'Modificar reserva', "Reserva {$reserva['codigo']}: " . fecha($ingreso) . ' – ' . fecha($salida));
            });
        } catch (PDOException $e) {
            throw self::traducirError($e);
        }
    }

    // ------------------------------------------------------------------
    // CU-14 Cancelar reserva
    // ------------------------------------------------------------------
    public static function cancelar(int $idReserva, string $tipo, string $descripcion): void
    {
        $reserva = self::obtener($idReserva);
        if (!in_array($reserva['estado'], self::MODIFICABLES, true)) {
            throw ExcepcionValidacion::campo('estado', 'Sólo se pueden cancelar reservas pendientes o confirmadas.');
        }
        if (!array_key_exists($tipo, MOTIVOS_CANCELACION)) {
            throw ExcepcionValidacion::campo('tipo_motivo', 'Seleccione el tipo de motivo.');
        }
        if (mb_strlen(trim($descripcion)) < 5) {
            // flujo 2a: sin motivo no se puede continuar
            throw ExcepcionValidacion::campo('descripcion', 'Describa el motivo de la cancelación (mínimo 5 caracteres).');
        }
        BaseDatos::transaccion(function () use ($idReserva, $tipo, $descripcion, $reserva) {
            // El disparador trg_cancelacion_reserva cambia la reserva a CANCELADA
            BaseDatos::insertar('cancelacion', [
                'id_reserva'  => $idReserva,
                'tipo_motivo' => $tipo,
                'descripcion' => mb_substr(trim($descripcion), 0, 255),
                'id_usuario'  => Autenticacion::id(),
            ]);
            BitacoraServicio::registrar('Reservas', 'Cancelar reserva', "Reserva {$reserva['codigo']} cancelada: " . MOTIVOS_CANCELACION[$tipo]);
        });
    }

    public static function confirmar(int $idReserva): void
    {
        $reserva = self::obtener($idReserva);
        if ($reserva['estado'] !== 'PENDIENTE') {
            throw ExcepcionValidacion::campo('estado', 'Sólo se pueden confirmar reservas pendientes.');
        }
        BaseDatos::actualizar('reserva', ['estado' => 'CONFIRMADA', 'fecha_modificacion' => date('Y-m-d H:i:s')], 'id_reserva = :id', ['id' => $idReserva]);
        BitacoraServicio::registrar('Reservas', 'Confirmar reserva', "Reserva {$reserva['codigo']} confirmada");
    }

    // ------------------------------------------------------------------
    // CU-15 Registrar check-in
    // ------------------------------------------------------------------
    public static function checkin(int $idReserva, array $asignaciones = [], ?string $observaciones = null): void
    {
        $reserva = self::obtener($idReserva);
        if (!in_array($reserva['estado'], self::MODIFICABLES, true)) {
            throw ExcepcionValidacion::campo('estado', 'La reserva debe estar pendiente o confirmada para registrar el ingreso.');
        }
        $hoy = date('Y-m-d');
        if ($reserva['fecha_ingreso'] > $hoy) {
            throw ExcepcionValidacion::campo('estado', 'El check-in sólo puede registrarse a partir de la fecha de ingreso (' . fecha($reserva['fecha_ingreso']) . ').');
        }
        if ($reserva['fecha_salida'] <= $hoy) {
            throw ExcepcionValidacion::campo('estado', 'La fecha de salida de esta reserva ya pasó. Corresponde marcarla como no-show.');
        }
        try {
            BaseDatos::transaccion(function () use ($idReserva, $reserva, $asignaciones, $observaciones, $hoy) {
                BaseDatos::fila('SELECT id_reserva FROM reserva WHERE id_reserva = ? FOR UPDATE', [$idReserva]);
                // Asignación de habitación a líneas en sobreventa
                foreach (BaseDatos::filas('SELECT id_detalle, id_categoria FROM detalle_reserva WHERE id_reserva = ? AND id_habitacion IS NULL', [$idReserva]) as $linea) {
                    $idHab = (int) ($asignaciones[$linea['id_detalle']] ?? 0);
                    if (!$idHab) {
                        throw ExcepcionValidacion::campo('asignacion', 'Asigne una habitación libre al huésped de la reserva en sobreventa.');
                    }
                    self::asignarLinea((int) $linea['id_detalle'], $idHab, $hoy, $reserva['fecha_salida'], $idReserva);
                }
                $habitaciones = BaseDatos::filas(
                    'SELECT h.id_habitacion, h.numero, h.estado FROM detalle_reserva d JOIN habitacion h ON h.id_habitacion = d.id_habitacion WHERE d.id_reserva = ? FOR UPDATE',
                    [$idReserva]
                );
                foreach ($habitaciones as $h) {
                    if (in_array($h['estado'], ['OCUPADA', 'MANTENIMIENTO'], true)) {
                        throw ExcepcionValidacion::campo('estado', "La habitación {$h['numero']} figura como " . strtolower($h['estado']) . '. Revise su estado antes del ingreso.');
                    }
                }
                BaseDatos::insertar('estadia', [
                    'id_reserva'         => $idReserva,
                    'fecha_checkin'      => date('Y-m-d H:i:s'),
                    'id_usuario_checkin' => Autenticacion::id(),
                    'observaciones'      => self::texto($observaciones),
                ]);
                BaseDatos::actualizar('reserva', ['estado' => 'EN_CURSO', 'fecha_modificacion' => date('Y-m-d H:i:s')], 'id_reserva = :id', ['id' => $idReserva]);
                foreach ($habitaciones as $h) {
                    HabitacionServicio::cambiarEstado((int) $h['id_habitacion'], 'OCUPADA', "Check-in de la reserva {$reserva['codigo']}", true);
                }
                BitacoraServicio::registrar('Reservas', 'Check-in', "Ingreso de {$reserva['huesped']} · reserva {$reserva['codigo']}");
            });
        } catch (PDOException $e) {
            throw self::traducirError($e);
        }
    }

    // ------------------------------------------------------------------
    // CU-16 Registrar check-out (cobro, recibo y liberación automática)
    // ------------------------------------------------------------------
    public static function checkout(int $idReserva, array $d): int
    {
        $reserva = self::obtener($idReserva);
        if ($reserva['estado'] !== 'EN_CURSO') {
            throw ExcepcionValidacion::campo('estado', 'La reserva no tiene una estadía en curso.');
        }
        $cargos = max(0, round((float) ($d['cargos_extra'] ?? 0), 2));
        $pagado = self::pagado($idReserva);
        $saldoHospedaje = max(0, round((float) $reserva['monto_total'] - $pagado, 2));
        $saldo = round($saldoHospedaje + $cargos, 2);
        $idMetodo = (int) ($d['id_metodo_pago'] ?? 0);
        if ($saldo > 0 && !BaseDatos::valor('SELECT 1 FROM metodo_pago WHERE id_metodo_pago = ?', [$idMetodo])) {
            throw ExcepcionValidacion::campo('id_metodo_pago', 'Seleccione el método con el que se cobra el saldo.');
        }
        $requiereRef = $idMetodo ? (int) BaseDatos::valor('SELECT requiere_referencia FROM metodo_pago WHERE id_metodo_pago = ?', [$idMetodo]) : 0;
        if ($saldo > 0 && $requiereRef && empty($d['referencia'])) {
            throw ExcepcionValidacion::campo('referencia', 'Ingrese el número de transacción o referencia del pago.');
        }

        return BaseDatos::transaccion(function () use ($idReserva, $reserva, $d, $cargos, $saldoHospedaje, $idMetodo, $pagado) {
            $hoy = date('Y-m-d');
            $observacion = self::texto($d['observaciones'] ?? null);
            if ($hoy < $reserva['fecha_salida']) {
                // flujo 2a: salida anticipada
                $observacion = trim(($observacion ? $observacion . ' · ' : '') . 'Salida anticipada: prevista para el ' . fecha($reserva['fecha_salida']) . ', realizada el ' . fecha($hoy) . '.');
            }
            $concepto = trim((string) ($d['concepto_extra'] ?? ''));
            if ($saldoHospedaje > 0) {
                self::insertarPago($idReserva, $saldoHospedaje, $idMetodo, 'HOSPEDAJE', $d['referencia'] ?? null);
            }
            if ($cargos > 0) {
                self::insertarPago($idReserva, $cargos, $idMetodo, 'CONSUMO', $d['referencia'] ?? null);
            }
            $observacionesReserva = trim(($reserva['observaciones'] ? $reserva['observaciones'] . "\n" : '') . ($cargos > 0 ? 'Cargos adicionales: ' . ($concepto ?: 'consumos') . ' (' . bs($cargos) . ').' : ''));
            BaseDatos::actualizar('reserva', [
                'estado'             => 'FINALIZADA',
                'cargos_extra'       => round((float) $reserva['cargos_extra'] + $cargos, 2),
                'observaciones'      => $observacionesReserva ?: null,
                'fecha_modificacion' => date('Y-m-d H:i:s'),
            ], 'id_reserva = :id', ['id' => $idReserva]);
            BaseDatos::consulta(
                'UPDATE estadia SET fecha_checkout = NOW(), id_usuario_checkout = :u, observaciones = CONCAT_WS(" · ", observaciones, :o) WHERE id_reserva = :r',
                ['u' => Autenticacion::id(), 'o' => $observacion, 'r' => $idReserva]
            );
            $destino = !empty($d['enviar_limpieza']) ? 'LIMPIEZA' : 'DISPONIBLE';
            foreach (BaseDatos::filas('SELECT id_habitacion FROM detalle_reserva WHERE id_reserva = ? AND id_habitacion IS NOT NULL', [$idReserva]) as $h) {
                HabitacionServicio::cambiarEstado((int) $h['id_habitacion'], $destino, "Check-out de la reserva {$reserva['codigo']}", true);
            }
            $numero = 'REC-' . str_pad((string) ((int) BaseDatos::valor('SELECT COALESCE(MAX(id_recibo), 0) FROM recibo') + 1), 6, '0', STR_PAD_LEFT);
            $idRecibo = BaseDatos::insertar('recibo', [
                'numero'     => $numero,
                'id_reserva' => $idReserva,
                'total'      => round((float) $reserva['monto_total'] + (float) $reserva['cargos_extra'] + $cargos, 2),
                'id_usuario' => Autenticacion::id(),
            ]);
            BitacoraServicio::registrar('Reservas', 'Check-out', "Salida de {$reserva['huesped']} · reserva {$reserva['codigo']} · recibo $numero");
            return $idRecibo;
        });
    }

    // ------------------------------------------------------------------
    // Pagos, asignación de habitación y no-show
    // ------------------------------------------------------------------
    public static function registrarPago(int $idReserva, float $monto, int $idMetodo, string $concepto, ?string $referencia): void
    {
        $reserva = self::obtener($idReserva);
        if (in_array($reserva['estado'], ['CANCELADA', 'FINALIZADA'], true)) {
            throw ExcepcionValidacion::campo('monto', 'No se registran pagos en reservas canceladas o finalizadas.');
        }
        $saldo = round((float) $reserva['monto_total'] + (float) $reserva['cargos_extra'] - self::pagado($idReserva), 2);
        if ($monto <= 0) {
            throw ExcepcionValidacion::campo('monto', 'El monto debe ser mayor a cero.');
        }
        if ($monto > $saldo + 0.001) {
            throw ExcepcionValidacion::campo('monto', 'El monto supera el saldo pendiente (' . bs($saldo) . ').');
        }
        if (!in_array($concepto, ['ANTICIPO', 'HOSPEDAJE', 'CONSUMO', 'PENALIDAD'], true)) {
            $concepto = 'ANTICIPO';
        }
        $requiereRef = (int) BaseDatos::valor('SELECT requiere_referencia FROM metodo_pago WHERE id_metodo_pago = ?', [$idMetodo]);
        if ($requiereRef && !$referencia) {
            throw ExcepcionValidacion::campo('referencia', 'Este método de pago requiere el número de referencia.');
        }
        self::insertarPago($idReserva, $monto, $idMetodo, $concepto, $referencia);
        BitacoraServicio::registrar('Reservas', 'Registrar pago', "Pago de " . bs($monto) . " en la reserva {$reserva['codigo']}");
    }

    private static function insertarPago(int $idReserva, float $monto, int $idMetodo, string $concepto, ?string $referencia, ?float $tope = null): void
    {
        if ($tope !== null && $monto > $tope) {
            throw ExcepcionValidacion::campo('anticipo', 'El anticipo no puede superar el monto total (' . bs($tope) . ').');
        }
        if (!BaseDatos::valor('SELECT 1 FROM metodo_pago WHERE id_metodo_pago = ?', [$idMetodo])) {
            throw ExcepcionValidacion::campo('id_metodo_pago', 'Seleccione un método de pago válido.');
        }
        BaseDatos::insertar('pago', [
            'id_reserva'     => $idReserva,
            'id_metodo_pago' => $idMetodo,
            'concepto'       => $concepto,
            'monto'          => round($monto, 2),
            'referencia'     => $referencia ? mb_substr(trim($referencia), 0, 80) : null,
            'id_usuario'     => Autenticacion::id(),
        ]);
    }

    public static function asignarHabitacion(int $idReserva, int $idDetalle, int $idHabitacion): void
    {
        $reserva = self::obtener($idReserva);
        if (!in_array($reserva['estado'], self::MODIFICABLES, true)) {
            throw ExcepcionValidacion::campo('asignacion', 'Sólo se asignan habitaciones a reservas pendientes o confirmadas.');
        }
        try {
            BaseDatos::transaccion(function () use ($idReserva, $idDetalle, $idHabitacion, $reserva) {
                self::asignarLinea($idDetalle, $idHabitacion, $reserva['fecha_ingreso'], $reserva['fecha_salida'], $idReserva);
                $pendientes = (int) BaseDatos::valor('SELECT COUNT(*) FROM detalle_reserva WHERE id_reserva = ? AND id_habitacion IS NULL', [$idReserva]);
                if ($pendientes === 0) {
                    BaseDatos::actualizar('reserva', ['fecha_modificacion' => date('Y-m-d H:i:s')], 'id_reserva = :id', ['id' => $idReserva]);
                }
                BitacoraServicio::registrar('Reservas', 'Asignar habitación', "Reserva {$reserva['codigo']} en sobreventa recibió habitación");
            });
        } catch (PDOException $e) {
            throw self::traducirError($e);
        }
    }

    private static function asignarLinea(int $idDetalle, int $idHabitacion, string $desde, string $hasta, int $idReserva): void
    {
        $h = BaseDatos::fila('SELECT id_habitacion, numero, estado FROM habitacion WHERE id_habitacion = ? FOR UPDATE', [$idHabitacion]);
        if (!$h || $h['estado'] === 'MANTENIMIENTO') {
            throw ExcepcionValidacion::campo('asignacion', 'La habitación elegida no está disponible.');
        }
        $linea = BaseDatos::fila('SELECT * FROM detalle_reserva WHERE id_detalle = ? AND id_reserva = ?', [$idDetalle, $idReserva]);
        if (!$linea || $linea['id_habitacion'] !== null) {
            throw ExcepcionValidacion::campo('asignacion', 'La línea de la reserva ya tiene habitación.');
        }
        if (!DisponibilidadServicio::estaDisponible($idHabitacion, $desde, $hasta, $idReserva)) {
            throw ExcepcionValidacion::campo('asignacion', "La habitación {$h['numero']} no está libre en esas fechas.");
        }
        BaseDatos::actualizar('detalle_reserva', ['id_habitacion' => $idHabitacion], 'id_detalle = :id', ['id' => $idDetalle]);
    }

    public static function marcarNoShow(int $idReserva): void
    {
        $reserva = self::obtener($idReserva);
        if (!in_array($reserva['estado'], self::MODIFICABLES, true) || $reserva['fecha_ingreso'] > date('Y-m-d')) {
            throw ExcepcionValidacion::campo('estado', 'Sólo se marca no-show a reservas confirmadas cuya fecha de ingreso ya llegó.');
        }
        BaseDatos::actualizar('reserva', ['estado' => 'NO_SHOW', 'fecha_modificacion' => date('Y-m-d H:i:s')], 'id_reserva = :id', ['id' => $idReserva]);
        BitacoraServicio::registrar('Reservas', 'Marcar no-show', "Reserva {$reserva['codigo']}: el huésped no se presentó");
    }

    /**
     * CU-15 flujo 1a: las reservas cuyo día de ingreso terminó sin check-in pasan
     * automáticamente a NO_SHOW. Se ejecuta al cargar el dashboard y la recepción.
     */
    public static function procesarNoShowVencidos(): int
    {
        $cantidad = BaseDatos::consulta(
            "UPDATE reserva SET estado = 'NO_SHOW', fecha_modificacion = NOW()
              WHERE estado IN ('PENDIENTE', 'CONFIRMADA') AND fecha_ingreso < CURDATE()"
        )->rowCount();
        if ($cantidad > 0) {
            BitacoraServicio::registrar('Reservas', 'No-show automático', "$cantidad reserva(s) sin check-in pasaron a NO_SHOW");
        }
        return $cantidad;
    }

    // ------------------------------------------------------------------
    // Consultas
    // ------------------------------------------------------------------
    public static function obtener(int $idReserva): array
    {
        $r = BaseDatos::fila(
            "SELECT r.*, CONCAT(h.nombres, ' ', h.apellidos) AS huesped, h.nro_documento, h.telefono, h.correo,
                    h.nacionalidad, h.ciudad_procedencia, td.sigla AS tipo_documento, c.nombre AS canal, c.icono AS canal_icono,
                    u.nombre_usuario AS registrado_por
               FROM reserva r
               JOIN huesped h ON h.id_huesped = r.id_huesped
               JOIN tipo_documento td ON td.id_tipo_documento = h.id_tipo_documento
               JOIN canal_reserva c ON c.id_canal = r.id_canal
               LEFT JOIN usuario u ON u.id_usuario = r.id_usuario
              WHERE r.id_reserva = ?",
            [$idReserva]
        );
        if (!$r) {
            throw new \App\Core\ExcepcionHttp(404, 'La reserva no existe.');
        }
        return $r;
    }

    public static function detalle(int $idReserva): array
    {
        return BaseDatos::filas(
            'SELECT d.*, h.numero, h.piso, h.estado AS estado_habitacion, c.nombre AS categoria, c.capacidad
               FROM detalle_reserva d
               JOIN categoria_habitacion c ON c.id_categoria = d.id_categoria
               LEFT JOIN habitacion h ON h.id_habitacion = d.id_habitacion
              WHERE d.id_reserva = ? ORDER BY h.numero',
            [$idReserva]
        );
    }

    public static function pagos(int $idReserva): array
    {
        return BaseDatos::filas(
            'SELECT p.*, m.nombre AS metodo, u.nombre_usuario FROM pago p
               JOIN metodo_pago m ON m.id_metodo_pago = p.id_metodo_pago
               LEFT JOIN usuario u ON u.id_usuario = p.id_usuario
              WHERE p.id_reserva = ? ORDER BY p.fecha',
            [$idReserva]
        );
    }

    public static function pagado(int $idReserva): float
    {
        return (float) BaseDatos::valor('SELECT COALESCE(SUM(monto), 0) FROM pago WHERE id_reserva = ?', [$idReserva]);
    }

    public static function codigo(int $idReserva): string
    {
        return 'HE-' . str_pad((string) (1000 + $idReserva), 6, '0', STR_PAD_LEFT);
    }

    private static function texto(?string $valor): ?string
    {
        $valor = trim((string) $valor);
        return $valor === '' ? null : mb_substr($valor, 0, 1000);
    }

    /** Convierte el rechazo del disparador anti-traslape en un mensaje para el usuario. */
    private static function traducirError(PDOException $e): \Throwable
    {
        if (str_contains($e->getMessage(), 'HABITACION_NO_DISPONIBLE') || ($e->errorInfo[0] ?? '') === '45000') {
            return ExcepcionValidacion::campo('habitaciones', 'La base de datos rechazó la operación: la habitación ya está reservada en esas fechas.');
        }
        return $e;
    }
}
