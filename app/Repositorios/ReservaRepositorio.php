<?php

declare(strict_types=1);

namespace App\Repositorios;

use App\Core\BaseDatos;
use App\Core\Paginador;

/**
 * Consultas de listado de la entidad Reserva.
 */
final class ReservaRepositorio
{
    private static function filtros(array $f): array
    {
        $donde = ['1 = 1'];
        $p = [];
        if (!empty($f['q'])) {
            $donde[] = "(r.codigo LIKE :q OR CONCAT(h.nombres, ' ', h.apellidos) LIKE :q2 OR h.nro_documento LIKE :q3)";
            $p += ['q' => "%{$f['q']}%", 'q2' => "%{$f['q']}%", 'q3' => "%{$f['q']}%"];
        }
        if (!empty($f['estado'])) {
            $donde[] = 'r.estado = :estado';
            $p['estado'] = $f['estado'];
        }
        if (!empty($f['canal'])) {
            $donde[] = 'r.id_canal = :canal';
            $p['canal'] = (int) $f['canal'];
        }
        if (!empty($f['desde'])) {
            $donde[] = 'r.fecha_ingreso >= :desde';
            $p['desde'] = $f['desde'];
        }
        if (!empty($f['hasta'])) {
            $donde[] = 'r.fecha_ingreso <= :hasta';
            $p['hasta'] = $f['hasta'];
        }
        if (!empty($f['sobreventa'])) {
            $donde[] = 'r.es_sobreventa = 1';
        }
        if (($f['periodo'] ?? '') === 'proximas') {
            $donde[] = "r.fecha_ingreso >= CURDATE() AND r.estado IN ('PENDIENTE', 'CONFIRMADA')";
        } elseif (($f['periodo'] ?? '') === 'hoy') {
            $donde[] = '(r.fecha_ingreso = CURDATE() OR r.fecha_salida = CURDATE())';
        }
        return [implode(' AND ', $donde), $p];
    }

    public static function listar(array $f, int $pagina, int $porPagina = 15): array
    {
        [$where, $p] = self::filtros($f);
        $total = (int) BaseDatos::valor("SELECT COUNT(*) FROM reserva r JOIN huesped h ON h.id_huesped = r.id_huesped WHERE $where", $p);
        $paginador = new Paginador($total, $pagina, $porPagina);
        $orden = ($f['periodo'] ?? '') === 'proximas' ? 'r.fecha_ingreso ASC' : 'r.fecha_registro DESC';
        $filas = BaseDatos::filas(
            "SELECT r.*, CONCAT(h.nombres, ' ', h.apellidos) AS huesped, h.nro_documento, h.nacionalidad, c.nombre AS canal, c.icono AS canal_icono,
                    DATEDIFF(r.fecha_salida, r.fecha_ingreso) AS noches,
                    (SELECT GROUP_CONCAT(COALESCE(x.numero, 'Por asignar') ORDER BY x.numero SEPARATOR ', ') FROM detalle_reserva d LEFT JOIN habitacion x ON x.id_habitacion = d.id_habitacion WHERE d.id_reserva = r.id_reserva) AS habitaciones,
                    (SELECT COALESCE(SUM(pg.monto), 0) FROM pago pg WHERE pg.id_reserva = r.id_reserva) AS pagado
               FROM reserva r
               JOIN huesped h ON h.id_huesped = r.id_huesped
               JOIN canal_reserva c ON c.id_canal = r.id_canal
              WHERE $where ORDER BY $orden
              LIMIT {$paginador->porPagina} OFFSET {$paginador->desplazamiento}",
            $p
        );
        return [$filas, $paginador];
    }

    public static function conteoPorEstado(array $f): array
    {
        unset($f['estado']);
        [$where, $p] = self::filtros($f);
        $conteo = ['' => 0];
        foreach (BaseDatos::filas("SELECT r.estado, COUNT(*) n FROM reserva r JOIN huesped h ON h.id_huesped = r.id_huesped WHERE $where GROUP BY r.estado", $p) as $fila) {
            $conteo[$fila['estado']] = (int) $fila['n'];
            $conteo[''] += (int) $fila['n'];
        }
        return $conteo;
    }

    public static function exportar(array $f): array
    {
        [$where, $p] = self::filtros($f);
        return BaseDatos::filas(
            "SELECT r.codigo, CONCAT(h.nombres, ' ', h.apellidos) AS huesped, h.nro_documento, c.nombre AS canal, r.fecha_ingreso, r.fecha_salida,
                    DATEDIFF(r.fecha_salida, r.fecha_ingreso) AS noches, r.cantidad_personas, r.estado, r.monto_total + 0 AS monto_total, r.fecha_registro
               FROM reserva r JOIN huesped h ON h.id_huesped = r.id_huesped JOIN canal_reserva c ON c.id_canal = r.id_canal
              WHERE $where ORDER BY r.fecha_ingreso DESC LIMIT 10000",
            $p
        );
    }

    public static function canales(): array
    {
        return BaseDatos::filas('SELECT * FROM canal_reserva WHERE estado = 1 ORDER BY id_canal');
    }

    public static function metodosPago(): array
    {
        return BaseDatos::filas('SELECT * FROM metodo_pago WHERE estado = 1 ORDER BY id_metodo_pago');
    }
}
