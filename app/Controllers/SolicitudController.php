<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseDatos;
use App\Core\Respuesta;

/**
 * Solicitudes de reserva y mensajes que llegan desde el sitio público.
 * Al atender una solicitud se abre el asistente de reserva (CU-11) con los datos precargados.
 */
final class SolicitudController extends Controlador
{
    public function index(): Respuesta
    {
        $pestana = $this->solicitud->consulta('pestana') === 'mensajes' ? 'mensajes' : 'solicitudes';
        $estado = (string) $this->solicitud->consulta('estado', 'NUEVA');
        $solicitudes = BaseDatos::filas(
            "SELECT s.*, c.nombre AS categoria, td.sigla, r.codigo
               FROM solicitud_reserva s
               JOIN categoria_habitacion c ON c.id_categoria = s.id_categoria
               JOIN tipo_documento td ON td.id_tipo_documento = s.id_tipo_documento
               LEFT JOIN reserva r ON r.id_reserva = s.id_reserva
              WHERE (:estado = '' OR s.estado = :estado2)
              ORDER BY s.fecha DESC LIMIT 100",
            ['estado' => $estado, 'estado2' => $estado]
        );
        $conteo = [];
        foreach (BaseDatos::filas('SELECT estado, COUNT(*) n FROM solicitud_reserva GROUP BY estado') as $f) {
            $conteo[$f['estado']] = (int) $f['n'];
        }
        return $this->vista('solicitudes/index', [
            'titulo' => 'Solicitudes web', 'pestana' => $pestana, 'estado' => $estado, 'solicitudes' => $solicitudes, 'conteo' => $conteo,
            'mensajes' => BaseDatos::filas('SELECT * FROM mensaje_contacto ORDER BY leido, fecha DESC LIMIT 100'),
            'noLeidos' => (int) BaseDatos::valor('SELECT COUNT(*) FROM mensaje_contacto WHERE leido = 0'),
        ]);
    }

    public function descartar(string $id): Respuesta
    {
        BaseDatos::actualizar('solicitud_reserva', ['estado' => 'DESCARTADA'], "id_solicitud = :id AND estado = 'NUEVA'", ['id' => (int) $id]);
        $this->bitacora('Reservas', 'Descartar solicitud web', 'Solicitud #' . $id);
        return $this->volver('Solicitud descartada.', 'info');
    }

    public function marcarLeido(string $id): Respuesta
    {
        BaseDatos::actualizar('mensaje_contacto', ['leido' => 1], 'id_mensaje = :id', ['id' => (int) $id]);
        return $this->volver('Mensaje marcado como leído.', 'info');
    }
}
