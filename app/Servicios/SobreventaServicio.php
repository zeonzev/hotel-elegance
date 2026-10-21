<?php

declare(strict_types=1);

namespace App\Servicios;

use App\Core\Autenticacion;
use App\Core\BaseDatos;
use App\Core\ExcepcionValidacion;
use DateTimeImmutable;

/**
 * Política de sobreventa (Sprint 5):
 *  - CU-17 Calcular tasa de cancelación y no-show
 *  - CU-18 Calcular valor esperado de sobreventa
 *  - CU-19 Alertar sobreventa
 */
final class SobreventaServicio
{
    /** Configuración vigente (umbral, costo de reubicación, máximo de reservas extra...). */
    public static function configuracion(): array
    {
        $c = BaseDatos::fila('SELECT * FROM configuracion_sobreventa WHERE vigente = 1 ORDER BY id_configuracion DESC LIMIT 1');
        return $c ?: [
            'umbral_ocupacion' => 90, 'costo_reubicacion' => 650, 'max_reservas_extra' => 3,
            'meses_historial' => 12, 'incluir_cancelaciones' => 0,
        ];
    }

    public static function guardarConfiguracion(array $datos): void
    {
        BaseDatos::transaccion(function () use ($datos) {
            BaseDatos::consulta('UPDATE configuracion_sobreventa SET vigente = 0 WHERE vigente = 1');
            BaseDatos::insertar('configuracion_sobreventa', [
                'umbral_ocupacion'      => (float) $datos['umbral_ocupacion'],
                'costo_reubicacion'     => (float) $datos['costo_reubicacion'],
                'max_reservas_extra'    => (int) $datos['max_reservas_extra'],
                'meses_historial'       => (int) $datos['meses_historial'],
                'incluir_cancelaciones' => !empty($datos['incluir_cancelaciones']) ? 1 : 0,
                'vigente'               => 1,
                'id_usuario'            => Autenticacion::id(),
            ]);
        });
    }

    /**
     * CU-17: tasa_cancelacion = cancelaciones / reservas ; tasa_no_show = no_show / reservas
     * del mes (según la fecha de ingreso). Se guarda en tasa_historica.
     */
    public static function calcularTasa(int $anio, int $mes): ?array
    {
        $inicio = sprintf('%04d-%02d-01', $anio, $mes);
        $fin = (new DateTimeImmutable($inicio))->modify('first day of next month')->format('Y-m-d');
        $fila = BaseDatos::fila(
            "SELECT COUNT(*) AS total, COALESCE(SUM(estado = 'CANCELADA'), 0) AS cancelaciones, COALESCE(SUM(estado = 'NO_SHOW'), 0) AS no_show
               FROM reserva WHERE fecha_ingreso >= :inicio AND fecha_ingreso < :fin",
            ['inicio' => $inicio, 'fin' => $fin]
        );
        $total = (int) $fila['total'];
        if ($total === 0) {
            return null; // flujo 2a: sin reservas no se calcula
        }
        $datos = [
            'anio' => $anio, 'mes' => $mes, 'total_reservas' => $total,
            'cancelaciones' => (int) $fila['cancelaciones'], 'no_show' => (int) $fila['no_show'],
            'tasa_cancelacion' => round((int) $fila['cancelaciones'] / $total, 4),
            'tasa_no_show' => round((int) $fila['no_show'] / $total, 4),
        ];
        BaseDatos::consulta(
            'INSERT INTO tasa_historica (anio, mes, total_reservas, cancelaciones, no_show, tasa_cancelacion, tasa_no_show, fecha_calculo)
             VALUES (:anio, :mes, :total_reservas, :cancelaciones, :no_show, :tasa_cancelacion, :tasa_no_show, NOW())
             ON DUPLICATE KEY UPDATE total_reservas = VALUES(total_reservas), cancelaciones = VALUES(cancelaciones),
                 no_show = VALUES(no_show), tasa_cancelacion = VALUES(tasa_cancelacion), tasa_no_show = VALUES(tasa_no_show), fecha_calculo = NOW()',
            $datos
        );
        return $datos;
    }

    /** Recalcula los últimos N meses cerrados. Devuelve cuántos meses se calcularon. */
    public static function recalcularTasas(int $meses = 12): int
    {
        $calculados = 0;
        $mes = new DateTimeImmutable('first day of last month');
        for ($i = 0; $i < $meses; $i++) {
            if (self::calcularTasa((int) $mes->format('Y'), (int) $mes->format('n')) !== null) {
                $calculados++;
            }
            $mes = $mes->modify('-1 month');
        }
        return $calculados;
    }

    public static function tasas(int $meses = 12): array
    {
        return array_reverse(BaseDatos::filas(
            'SELECT * FROM tasa_historica ORDER BY anio DESC, mes DESC LIMIT ' . max(1, $meses)
        ));
    }

    /** Tasas ponderadas del periodo configurado (probabilidades del modelo). */
    public static function tasaVigente(?array $config = null): array
    {
        $config ??= self::configuracion();
        $filas = self::tasas((int) $config['meses_historial']);
        $total = array_sum(array_column($filas, 'total_reservas'));
        $noShow = array_sum(array_column($filas, 'no_show'));
        $cancel = array_sum(array_column($filas, 'cancelaciones'));
        $pNoShow = $total > 0 ? $noShow / $total : 0.06;
        $pCancel = $total > 0 ? $cancel / $total : 0.10;
        $pAusencia = $pNoShow + (!empty($config['incluir_cancelaciones']) ? $pCancel : 0);
        return [
            'p_no_show' => $pNoShow,
            'p_cancelacion' => $pCancel,
            'p_ausencia' => min(0.95, $pAusencia),
            'q' => max(0.05, 1 - $pAusencia),
            'meses' => count($filas),
            'reservas' => $total,
        ];
    }

    /** Ingreso de referencia por noche para una fecha: tarifa promedio ponderada por habitaciones. */
    public static function ingresoReferencia(string $fecha): float
    {
        $filas = BaseDatos::filas('SELECT id_categoria, COUNT(*) AS n FROM habitacion GROUP BY id_categoria');
        $suma = 0.0;
        $cantidad = 0;
        foreach ($filas as $f) {
            $suma += TarifaServicio::precioNoche((int) $f['id_categoria'], $fecha)['precio'] * (int) $f['n'];
            $cantidad += (int) $f['n'];
        }
        return $cantidad ? round($suma / $cantidad, 2) : 0.0;
    }

    /** Reservas que ocupan una noche (incluye las de sobreventa sin habitación). */
    public static function reservasEnNoche(string $fecha): int
    {
        return (int) BaseDatos::valor(
            "SELECT COUNT(*) FROM reserva r JOIN detalle_reserva d ON d.id_reserva = r.id_reserva
              WHERE r.estado IN ('PENDIENTE', 'CONFIRMADA', 'EN_CURSO', 'FINALIZADA')
                AND r.fecha_ingreso <= :f1 AND r.fecha_salida > :f2",
            ['f1' => $fecha, 'f2' => $fecha]
        );
    }

    /**
     * CU-18: evaluación completa para una noche.
     * Compara VE(aceptar) con VE(rechazar) = 0 y aplica el límite de reservas extra (flujo 5a).
     */
    public static function evaluar(string $fecha, ?float $ingreso = null, ?array $config = null, ?int $reservasAdicionales = 0): array
    {
        $config ??= self::configuracion();
        $tasas = self::tasaVigente($config);
        $capacidad = DisponibilidadServicio::capacidad();
        $reservas = self::reservasEnNoche($fecha) + (int) $reservasAdicionales;
        $ingreso ??= self::ingresoReferencia($fecha);
        $costo = (float) $config['costo_reubicacion'];
        $q = $tasas['q'];

        $adicional = ModeloDecision::evaluarAdicional($reservas, $capacidad, $q, $ingreso, $costo);
        // Modelo del informe: p = probabilidad de que se libere al menos una habitación
        $pLibera = $adicional['p_cupo'];
        $veSimple = ModeloDecision::veSimple($pLibera, $ingreso, $costo);
        $niveles = ModeloDecision::niveles($capacidad, $q, $ingreso, $costo, max(6, (int) $config['max_reservas_extra'] + 3));

        $extrasActuales = max(0, $reservas - $capacidad);
        $maxExtra = (int) $config['max_reservas_extra'];
        $ocupacion = $capacidad > 0 ? round($reservas / $capacidad * 100, 1) : 100.0;
        $limiteAlcanzado = $extrasActuales >= $maxExtra;
        $decision = ($adicional['ve_aceptar'] > 0 && !$limiteAlcanzado) ? 'ACEPTAR' : 'RECHAZAR';
        $motivo = match (true) {
            $reservas < $capacidad && $decision === 'ACEPTAR' => 'Todavía hay habitaciones libres: aceptar no implica riesgo de reubicación.',
            $limiteAlcanzado && $adicional['ve_aceptar'] > 0 => "El VE es positivo, pero ya se alcanzó el máximo de $maxExtra reservas en sobreventa (flujo 5a).",
            $decision === 'ACEPTAR' => 'El ingreso esperado supera al costo esperado de reubicación.',
            default => 'El costo esperado de reubicar a un huésped supera al ingreso esperado.',
        };

        return [
            'fecha'            => $fecha,
            'capacidad'        => $capacidad,
            'reservas'         => $reservas,
            'ocupacion'        => $ocupacion,
            'umbral'           => (float) $config['umbral_ocupacion'],
            'supera_umbral'    => $ocupacion >= (float) $config['umbral_ocupacion'],
            'p_no_show'        => $tasas['p_no_show'],
            'p_cancelacion'    => $tasas['p_cancelacion'],
            'p_ausencia'       => $tasas['p_ausencia'],
            'q'                => $q,
            'ingreso'          => $ingreso,
            'costo'            => $costo,
            'p_libera'         => $pLibera,
            've_simple'        => $veSimple,
            've_aceptar'       => $adicional['ve_aceptar'],
            've_rechazar'      => 0.0,
            'matriz'           => $adicional['matriz'],
            'distribucion'     => $adicional['distribucion'],
            'esperados'        => $adicional['esperados'],
            'niveles'          => $niveles['filas'],
            'nivel_optimo'     => $niveles['optimo'],
            'extras_actuales'  => $extrasActuales,
            'max_extra'        => $maxExtra,
            'decision'         => $decision,
            'motivo'           => $motivo,
            'meses_historial'  => $tasas['meses'],
            'incluir_cancelaciones' => (bool) $config['incluir_cancelaciones'],
        ];
    }

    /** Guarda la evaluación en evaluacion_sobreventa (CU-18 paso 5). */
    public static function guardarEvaluacion(array $e, string $origen = 'MANUAL', ?int $idReserva = null, ?string $decisionUsuario = null): int
    {
        return BaseDatos::insertar('evaluacion_sobreventa', [
            'fecha_objetivo'       => $e['fecha'],
            'capacidad'            => $e['capacidad'],
            'reservas_confirmadas' => $e['reservas'],
            'ocupacion'            => min(999.99, $e['ocupacion']),
            'p_no_show'            => round($e['p_ausencia'], 4),
            'ingreso'              => round($e['ingreso'], 2),
            'costo_reubicacion'    => round($e['costo'], 2),
            've_simple'            => round($e['ve_simple'], 2),
            've_aceptar'           => round($e['ve_aceptar'], 2),
            've_rechazar'          => 0,
            'nivel_optimo'         => $e['nivel_optimo'],
            'decision'             => $e['decision'],
            'origen'               => $origen,
            'decision_usuario'     => $decisionUsuario,
            'id_reserva'           => $idReserva,
            'id_usuario'           => Autenticacion::id(),
        ]);
    }

    /**
     * CU-19: noches de un rango cuya ocupación supera el umbral.
     * Devuelve la evaluación de la noche más crítica para mostrar la alerta.
     */
    public static function alertaParaRango(string $ingreso, string $salida, ?float $ingresoNoche = null): ?array
    {
        $config = self::configuracion();
        $capacidad = DisponibilidadServicio::capacidad();
        $ultima = (new DateTimeImmutable($salida))->modify('-1 day')->format('Y-m-d');
        $ocupacion = DisponibilidadServicio::ocupacionPorNoche($ingreso, $ultima, $capacidad);
        $criticas = array_filter($ocupacion, fn ($o) => ($o['ocupadas'] + 1) / max(1, $capacidad) * 100 >= (float) $config['umbral_ocupacion']);
        if (!$criticas) {
            return null;
        }
        uasort($criticas, fn ($a, $b) => $b['ocupadas'] <=> $a['ocupadas']);
        $fecha = array_key_first($criticas);
        $evaluacion = self::evaluar($fecha, $ingresoNoche, $config);
        $evaluacion['noches_criticas'] = array_map(fn ($o) => $o['porcentaje'], $criticas);
        return $evaluacion;
    }

    /** Próximas noches en alerta (para dashboard, menú y panel de recepción). */
    public static function alertasProximas(int $dias = 14): array
    {
        $config = self::configuracion();
        $desde = date('Y-m-d');
        $hasta = (new DateTimeImmutable())->modify('+' . ($dias - 1) . ' days')->format('Y-m-d');
        $alertas = [];
        foreach (DisponibilidadServicio::ocupacionPorNoche($desde, $hasta) as $fecha => $o) {
            if ($o['porcentaje'] >= (float) $config['umbral_ocupacion']) {
                $alertas[$fecha] = $o;
            }
        }
        return $alertas;
    }

    public static function historial(int $limite = 50): array
    {
        return BaseDatos::filas(
            'SELECT e.*, u.nombre_usuario, r.codigo
               FROM evaluacion_sobreventa e
               LEFT JOIN usuario u ON u.id_usuario = e.id_usuario
               LEFT JOIN reserva r ON r.id_reserva = e.id_reserva
              ORDER BY e.fecha DESC LIMIT ' . max(1, $limite)
        );
    }

    public static function validarConfiguracion(array $d): void
    {
        $errores = [];
        if (!is_numeric($d['umbral_ocupacion'] ?? null) || $d['umbral_ocupacion'] < 50 || $d['umbral_ocupacion'] > 100) {
            $errores['umbral_ocupacion'] = 'El umbral debe estar entre 50 % y 100 %.';
        }
        if (!is_numeric($d['costo_reubicacion'] ?? null) || $d['costo_reubicacion'] <= 0) {
            $errores['costo_reubicacion'] = 'El costo de reubicación debe ser mayor a 0.';
        }
        if (!is_numeric($d['max_reservas_extra'] ?? null) || $d['max_reservas_extra'] < 0 || $d['max_reservas_extra'] > 10) {
            $errores['max_reservas_extra'] = 'Ingrese un valor entre 0 y 10.';
        }
        if (!is_numeric($d['meses_historial'] ?? null) || $d['meses_historial'] < 1 || $d['meses_historial'] > 36) {
            $errores['meses_historial'] = 'Ingrese entre 1 y 36 meses.';
        }
        if ($errores) {
            throw new ExcepcionValidacion($errores);
        }
    }
}
