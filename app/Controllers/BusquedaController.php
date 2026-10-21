<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Autenticacion;
use App\Core\BaseDatos;
use App\Core\Respuesta;

/**
 * Búsqueda global (barra superior y Ctrl + K): huéspedes, reservas y habitaciones,
 * respetando los permisos del rol.
 */
final class BusquedaController extends Controlador
{
    public function index(): Respuesta
    {
        $q = (string) $this->solicitud->consulta('q', '');
        return $this->vista('busqueda/index', ['titulo' => 'Búsqueda', 'q' => $q, 'resultados' => $this->buscar($q, 25)]);
    }

    public function api(): Respuesta
    {
        return $this->json($this->buscar((string) $this->solicitud->consulta('q', ''), 5));
    }

    private function buscar(string $q, int $limite): array
    {
        $r = ['huespedes' => [], 'reservas' => [], 'habitaciones' => []];
        $q = trim($q);
        if (mb_strlen($q) < 2) {
            return $r;
        }
        $like = '%' . $q . '%';
        if (Autenticacion::puede('huespedes.ver')) {
            foreach (BaseDatos::filas(
                "SELECT id_huesped, nombres, apellidos, nro_documento, ciudad_procedencia FROM huesped
                  WHERE CONCAT(nombres, ' ', apellidos) LIKE :a OR nro_documento LIKE :b OR correo LIKE :c OR telefono LIKE :d
                  ORDER BY apellidos LIMIT $limite",
                ['a' => $like, 'b' => $like, 'c' => $like, 'd' => $like]
            ) as $h) {
                $r['huespedes'][] = ['titulo' => $h['nombres'] . ' ' . $h['apellidos'], 'detalle' => 'Doc. ' . $h['nro_documento'] . ' · ' . $h['ciudad_procedencia'], 'url' => url('/panel/huespedes/' . $h['id_huesped'])];
            }
        }
        if (Autenticacion::puede('reservas.ver')) {
            foreach (BaseDatos::filas(
                "SELECT r.id_reserva, r.codigo, r.estado, r.fecha_ingreso, r.fecha_salida, CONCAT(h.nombres, ' ', h.apellidos) AS huesped
                   FROM reserva r JOIN huesped h ON h.id_huesped = r.id_huesped
                  WHERE r.codigo LIKE :a OR CONCAT(h.nombres, ' ', h.apellidos) LIKE :b
                  ORDER BY r.fecha_ingreso DESC LIMIT $limite",
                ['a' => $like, 'b' => $like]
            ) as $x) {
                $r['reservas'][] = ['titulo' => $x['codigo'] . ' · ' . $x['huesped'], 'detalle' => fecha($x['fecha_ingreso']) . ' → ' . fecha($x['fecha_salida']) . ' · ' . estado_info($x['estado'])[0], 'url' => url('/panel/reservas/' . $x['id_reserva']), 'estado' => $x['estado']];
            }
        }
        if (Autenticacion::puede('habitaciones.ver')) {
            foreach (BaseDatos::filas(
                'SELECT h.id_habitacion, h.numero, h.piso, h.estado, c.nombre AS categoria FROM habitacion h JOIN categoria_habitacion c ON c.id_categoria = h.id_categoria
                  WHERE h.numero LIKE :a OR c.nombre LIKE :b ORDER BY h.numero LIMIT ' . $limite,
                ['a' => $like, 'b' => $like]
            ) as $h) {
                $r['habitaciones'][] = ['titulo' => 'Habitación ' . $h['numero'] . ' · ' . $h['categoria'], 'detalle' => 'Piso ' . $h['piso'] . ' · ' . estado_info($h['estado'])[0], 'url' => url('/panel/habitaciones/' . $h['id_habitacion']), 'estado' => $h['estado']];
            }
        }
        return $r;
    }
}
