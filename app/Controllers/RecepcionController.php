<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseDatos;
use App\Core\Respuesta;
use App\Repositorios\ReservaRepositorio;
use App\Servicios\DisponibilidadServicio;
use App\Servicios\ReservaServicio;
use App\Servicios\SobreventaServicio;

/**
 * Sprint 4 · Recepción del día: CU-15 Registrar check-in y CU-16 Registrar check-out.
 */
final class RecepcionController extends Controlador
{
    public function index(): Respuesta
    {
        ReservaServicio::procesarNoShowVencidos();
        $base = "SELECT r.*, CONCAT(h.nombres, ' ', h.apellidos) AS huesped, h.nro_documento, h.nacionalidad, h.ciudad_procedencia, h.telefono, td.sigla,
                        (SELECT GROUP_CONCAT(COALESCE(x.numero, 'Por asignar') ORDER BY x.numero SEPARATOR ', ') FROM detalle_reserva d LEFT JOIN habitacion x ON x.id_habitacion = d.id_habitacion WHERE d.id_reserva = r.id_reserva) AS habitaciones,
                        (SELECT COALESCE(SUM(p.monto), 0) FROM pago p WHERE p.id_reserva = r.id_reserva) AS pagado,
                        (SELECT COUNT(*) FROM detalle_reserva d JOIN habitacion x ON x.id_habitacion = d.id_habitacion WHERE d.id_reserva = r.id_reserva AND x.estado = 'OCUPADA') AS ocupadas
                   FROM reserva r JOIN huesped h ON h.id_huesped = r.id_huesped JOIN tipo_documento td ON td.id_tipo_documento = h.id_tipo_documento";
        return $this->vista('recepcion/index', [
            'titulo' => 'Recepción del día',
            'llegadas' => BaseDatos::filas("$base WHERE r.fecha_ingreso = CURDATE() AND r.estado IN ('PENDIENTE', 'CONFIRMADA', 'EN_CURSO') ORDER BY r.estado = 'EN_CURSO', h.apellidos"),
            'salidas' => BaseDatos::filas("$base WHERE r.fecha_salida = CURDATE() AND r.estado IN ('EN_CURSO', 'FINALIZADA') ORDER BY r.estado = 'FINALIZADA', h.apellidos"),
            'alojados' => BaseDatos::filas("$base WHERE r.estado = 'EN_CURSO' AND r.fecha_salida > CURDATE() ORDER BY r.fecha_salida, h.apellidos"),
            'atrasadas' => BaseDatos::filas("$base WHERE r.estado = 'EN_CURSO' AND r.fecha_salida < CURDATE() ORDER BY r.fecha_salida"),
            'alertas' => SobreventaServicio::alertasProximas(7),
            'limpieza' => BaseDatos::filas("SELECT id_habitacion, numero FROM habitacion WHERE estado = 'LIMPIEZA' ORDER BY numero"),
        ]);
    }

    public function formularioCheckin(string $id): Respuesta
    {
        $reserva = ReservaServicio::obtener((int) $id);
        if (!in_array($reserva['estado'], ReservaServicio::MODIFICABLES, true)) {
            return $this->redirigir('/panel/reservas/' . $id, 'La reserva no está pendiente ni confirmada; no admite check-in.', 'aviso');
        }
        $detalle = ReservaServicio::detalle((int) $id);
        $libres = [];
        if (array_filter($detalle, fn ($d) => $d['id_habitacion'] === null)) {
            $libres = DisponibilidadServicio::habitacionesDisponibles(max($reserva['fecha_ingreso'], date('Y-m-d')), $reserva['fecha_salida'], null, (int) $id);
            $libres = array_values(array_filter($libres, fn ($l) => $l['estado'] !== 'OCUPADA'));
        }
        return $this->vista('recepcion/checkin', ['titulo' => 'Check-in ' . $reserva['codigo'], 'r' => $reserva, 'detalle' => $detalle, 'libres' => $libres, 'pagado' => ReservaServicio::pagado((int) $id)]);
    }

    public function checkin(string $id): Respuesta
    {
        if (!$this->solicitud->entrada('documento_verificado')) {
            throw \App\Core\ExcepcionValidacion::campo('documento_verificado', 'Confirme que verificó el documento de identidad del huésped (CU-15 paso 2).');
        }
        ReservaServicio::checkin((int) $id, (array) $this->solicitud->entrada('asignacion', []), $this->solicitud->entrada('observaciones'));
        return $this->redirigir('/panel/reservas/' . $id, 'Check-in registrado: la habitación quedó como OCUPADA. ¡Bienvenido el huésped!');
    }

    public function formularioCheckout(string $id): Respuesta
    {
        $reserva = ReservaServicio::obtener((int) $id);
        if ($reserva['estado'] !== 'EN_CURSO') {
            return $this->redirigir('/panel/reservas/' . $id, 'La reserva no tiene una estadía en curso.', 'aviso');
        }
        return $this->vista('recepcion/checkout', [
            'titulo' => 'Check-out ' . $reserva['codigo'], 'r' => $reserva, 'detalle' => ReservaServicio::detalle((int) $id),
            'pagos' => ReservaServicio::pagos((int) $id), 'pagado' => ReservaServicio::pagado((int) $id),
            'metodos' => ReservaRepositorio::metodosPago(),
            'estadia' => BaseDatos::fila('SELECT * FROM estadia WHERE id_reserva = ?', [(int) $id]),
        ]);
    }

    public function checkout(string $id): Respuesta
    {
        $d = $this->validar(['cargos_extra' => 'opcional|numero|minimo:0', 'concepto_extra' => 'opcional|max:120', 'referencia' => 'opcional|max:80', 'observaciones' => 'opcional|max:255']);
        ReservaServicio::checkout((int) $id, $d + [
            'id_metodo_pago' => $this->entero('id_metodo_pago'),
            'enviar_limpieza' => (bool) $this->solicitud->entrada('enviar_limpieza'),
        ]);
        \App\Core\Sesion::flash('imprimir_recibo', (int) $id);
        return $this->redirigir('/panel/reservas/' . $id, 'Check-out registrado, saldo cobrado y recibo emitido. La habitación quedó liberada.');
    }
}
