<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseDatos;
use App\Core\ExcepcionHttp;
use App\Core\Respuesta;
use App\Repositorios\HuespedRepositorio;
use App\Repositorios\ReservaRepositorio;
use App\Servicios\ExportadorServicio;
use App\Servicios\ReservaServicio;

/**
 * Sprint 4 · Gestión de reservas: CU-11 Registrar, CU-13 Modificar, CU-14 Cancelar.
 * (CU-12 en DisponibilidadController; CU-15 y CU-16 en RecepcionController.)
 */
final class ReservaController extends Controlador
{
    public function index(): Respuesta
    {
        $filtros = $this->solicitud->solo(['q', 'estado', 'canal', 'desde', 'hasta', 'sobreventa', 'periodo']);
        if ($this->solicitud->consulta('formato') === 'csv') {
            $filas = array_map(fn ($r) => array_values($r), ReservaRepositorio::exportar($filtros));
            return ExportadorServicio::csv('reservas', ['Código', 'Huésped', 'Documento', 'Canal', 'Ingreso', 'Salida', 'Noches', 'Personas', 'Estado', 'Monto (Bs)', 'Registrada'], $filas);
        }
        [$reservas, $paginador] = ReservaRepositorio::listar($filtros, $this->entero('pagina', 1));
        return $this->vista('reservas/index', [
            'titulo' => 'Reservas', 'reservas' => $reservas, 'paginador' => $paginador, 'filtros' => $filtros,
            'conteo' => ReservaRepositorio::conteoPorEstado($filtros), 'canales' => ReservaRepositorio::canales(),
        ]);
    }

    public function crear(): Respuesta
    {
        $huesped = null;
        if ($id = $this->entero('huesped') ?: (int) anterior('id_huesped', 0)) {
            $huesped = HuespedRepositorio::buscar($id);
        }
        $solicitud = null;
        if ($idSol = $this->entero('solicitud')) {
            $solicitud = BaseDatos::fila("SELECT * FROM solicitud_reserva WHERE id_solicitud = ? AND estado = 'NUEVA'", [$idSol]);
            if ($solicitud && !$huesped) {
                $huesped = HuespedRepositorio::porDocumento((int) $solicitud['id_tipo_documento'], $solicitud['nro_documento']);
            }
        }
        return $this->vista('reservas/crear', [
            'titulo' => 'Nueva reserva', 'huesped' => $huesped, 'solicitud' => $solicitud,
            'canales' => ReservaRepositorio::canales(), 'metodos' => ReservaRepositorio::metodosPago(),
            'categorias' => BaseDatos::filas('SELECT id_categoria, nombre, capacidad FROM categoria_habitacion WHERE estado = 1 ORDER BY tarifa_base'),
            'tipos' => HuespedRepositorio::tiposDocumento(),
            'anticipo' => (float) ajuste('anticipo_porcentaje', 30),
        ]);
    }

    public function guardar(): Respuesta
    {
        $id = ReservaServicio::registrar($this->solicitud->todo());
        $r = ReservaServicio::obtener($id);
        return $this->redirigir('/panel/reservas/' . $id, "Reserva {$r['codigo']} registrada para {$r['huesped']}." . ((int) $r['es_sobreventa'] ? ' Quedó en sobreventa: asigne habitación al check-in.' : ''));
    }

    public function ver(string $id): Respuesta
    {
        $reserva = ReservaServicio::obtener((int) $id);
        $detalle = ReservaServicio::detalle((int) $id);
        $pagos = ReservaServicio::pagos((int) $id);
        $pagado = array_sum(array_map(fn ($p) => (float) $p['monto'], $pagos));
        $estadia = BaseDatos::fila(
            'SELECT e.*, ui.nombre_usuario AS usuario_checkin, uo.nombre_usuario AS usuario_checkout FROM estadia e
               LEFT JOIN usuario ui ON ui.id_usuario = e.id_usuario_checkin LEFT JOIN usuario uo ON uo.id_usuario = e.id_usuario_checkout
              WHERE e.id_reserva = ?',
            [(int) $id]
        );
        $cancelacion = BaseDatos::fila('SELECT c.*, u.nombre_usuario FROM cancelacion c LEFT JOIN usuario u ON u.id_usuario = c.id_usuario WHERE c.id_reserva = ?', [(int) $id]);
        $recibo = BaseDatos::fila('SELECT * FROM recibo WHERE id_reserva = ? ORDER BY id_recibo DESC LIMIT 1', [(int) $id]);
        $evaluacion = BaseDatos::fila('SELECT * FROM evaluacion_sobreventa WHERE id_reserva = ? ORDER BY id_evaluacion DESC LIMIT 1', [(int) $id]);
        $libres = [];
        foreach ($detalle as $d) {
            if ($d['id_habitacion'] === null && in_array($reserva['estado'], ReservaServicio::MODIFICABLES, true)) {
                $libres = \App\Servicios\DisponibilidadServicio::habitacionesDisponibles(max($reserva['fecha_ingreso'], date('Y-m-d')), $reserva['fecha_salida'], null, (int) $id);
                break;
            }
        }
        return $this->vista('reservas/ver', [
            'titulo' => 'Reserva ' . $reserva['codigo'], 'r' => $reserva, 'detalle' => $detalle, 'pagos' => $pagos,
            'pagado' => $pagado, 'saldo' => round((float) $reserva['monto_total'] + (float) $reserva['cargos_extra'] - $pagado, 2),
            'estadia' => $estadia, 'cancelacion' => $cancelacion, 'recibo' => $recibo, 'evaluacion' => $evaluacion,
            'metodos' => ReservaRepositorio::metodosPago(), 'libres' => $libres,
        ]);
    }

    public function editar(string $id): Respuesta
    {
        $reserva = ReservaServicio::obtener((int) $id);
        if (!in_array($reserva['estado'], ReservaServicio::MODIFICABLES, true)) {
            return $this->redirigir('/panel/reservas/' . $id, 'Sólo se pueden modificar reservas pendientes o confirmadas (CU-13, flujo 1a).', 'aviso');
        }
        return $this->vista('reservas/editar', [
            'titulo' => 'Modificar reserva ' . $reserva['codigo'], 'r' => $reserva, 'detalle' => ReservaServicio::detalle((int) $id),
            'canales' => ReservaRepositorio::canales(),
        ]);
    }

    public function actualizar(string $id): Respuesta
    {
        ReservaServicio::modificar((int) $id, $this->solicitud->todo());
        return $this->redirigir('/panel/reservas/' . $id, 'Reserva modificada y monto recalculado.');
    }

    public function cancelar(string $id): Respuesta
    {
        ReservaServicio::cancelar((int) $id, (string) $this->solicitud->entrada('tipo_motivo', ''), (string) $this->solicitud->entrada('descripcion', ''));
        return $this->redirigir('/panel/reservas/' . $id, 'Reserva cancelada: las habitaciones quedaron libres para esas fechas.');
    }

    public function confirmar(string $id): Respuesta
    {
        ReservaServicio::confirmar((int) $id);
        return $this->volver('Reserva confirmada.');
    }

    public function noShow(string $id): Respuesta
    {
        ReservaServicio::marcarNoShow((int) $id);
        return $this->volver('La reserva quedó como NO-SHOW y cuenta para la tasa histórica (CU-17).', 'aviso');
    }

    public function registrarPago(string $id): Respuesta
    {
        $d = $this->validar(['monto' => 'requerido|numero|mayor:0', 'id_metodo_pago' => 'requerido|entero', 'concepto' => 'requerido', 'referencia' => 'opcional|max:80'], ['id_metodo_pago' => 'método de pago']);
        ReservaServicio::registrarPago((int) $id, (float) $d['monto'], (int) $d['id_metodo_pago'], (string) $d['concepto'], $d['referencia']);
        return $this->volver('Pago de ' . bs($d['monto']) . ' registrado.');
    }

    public function asignarHabitacion(string $id): Respuesta
    {
        ReservaServicio::asignarHabitacion((int) $id, $this->entero('id_detalle'), $this->entero('id_habitacion'));
        return $this->volver('Habitación asignada a la reserva en sobreventa.');
    }

    public function recibo(string $id): Respuesta
    {
        $reserva = ReservaServicio::obtener((int) $id);
        $recibo = BaseDatos::fila('SELECT r.*, u.nombre_usuario FROM recibo r LEFT JOIN usuario u ON u.id_usuario = r.id_usuario WHERE r.id_reserva = ? ORDER BY r.id_recibo DESC LIMIT 1', [(int) $id]);
        $pagos = ReservaServicio::pagos((int) $id);
        return $this->vista('reservas/recibo', [
            'titulo' => $recibo ? 'Recibo ' . $recibo['numero'] : 'Comprobante de reserva', 'r' => $reserva, 'recibo' => $recibo,
            'detalle' => ReservaServicio::detalle((int) $id), 'pagos' => $pagos,
            'pagado' => array_sum(array_map(fn ($p) => (float) $p['monto'], $pagos)),
        ], 'impresion');
    }
}
