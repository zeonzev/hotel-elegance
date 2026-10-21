<?php

declare(strict_types=1);

namespace App\Repositorios;

use App\Core\BaseDatos;
use App\Core\Paginador;

/**
 * Acceso a datos de la entidad Empleado (paquete Sesiones).
 */
final class EmpleadoRepositorio
{
    public static function listar(array $f, int $pagina): array
    {
        $donde = ['1 = 1'];
        $p = [];
        if (!empty($f['q'])) {
            $donde[] = "(CONCAT(e.nombres, ' ', e.apellidos) LIKE :q OR e.ci LIKE :q2 OR e.correo LIKE :q3)";
            $p += ['q' => "%{$f['q']}%", 'q2' => "%{$f['q']}%", 'q3' => "%{$f['q']}%"];
        }
        if (!empty($f['cargo'])) {
            $donde[] = 'e.id_cargo = :cargo';
            $p['cargo'] = (int) $f['cargo'];
        }
        if (!empty($f['estado'])) {
            $donde[] = 'e.estado = :estado';
            $p['estado'] = $f['estado'];
        }
        $where = implode(' AND ', $donde);
        $total = (int) BaseDatos::valor("SELECT COUNT(*) FROM empleado e WHERE $where", $p);
        $paginador = new Paginador($total, $pagina, 12);
        $filas = BaseDatos::filas(
            "SELECT e.*, c.nombre AS cargo, u.id_usuario, u.nombre_usuario, u.estado AS estado_cuenta, r.nombre AS rol
               FROM empleado e
               JOIN cargo c ON c.id_cargo = e.id_cargo
               LEFT JOIN usuario u ON u.id_empleado = e.id_empleado
               LEFT JOIN rol r ON r.id_rol = u.id_rol
              WHERE $where
              ORDER BY e.estado, e.apellidos, e.nombres
              LIMIT {$paginador->porPagina} OFFSET {$paginador->desplazamiento}",
            $p
        );
        return [$filas, $paginador];
    }

    public static function buscar(int $id): ?array
    {
        return BaseDatos::fila('SELECT e.*, c.nombre AS cargo FROM empleado e JOIN cargo c ON c.id_cargo = e.id_cargo WHERE e.id_empleado = ?', [$id]);
    }

    public static function existeCi(string $ci, int $excluir = 0): bool
    {
        return (bool) BaseDatos::valor('SELECT 1 FROM empleado WHERE ci = ? AND id_empleado <> ?', [$ci, $excluir]);
    }

    public static function existeCorreo(string $correo, int $excluir = 0): bool
    {
        return (bool) BaseDatos::valor('SELECT 1 FROM empleado WHERE correo = ? AND id_empleado <> ?', [$correo, $excluir]);
    }

    public static function cargos(): array
    {
        return BaseDatos::filas('SELECT * FROM cargo WHERE estado = 1 ORDER BY nombre');
    }

    public static function sinCuenta(): array
    {
        return BaseDatos::filas(
            "SELECT e.id_empleado, e.nombres, e.apellidos, c.nombre AS cargo FROM empleado e
               JOIN cargo c ON c.id_cargo = e.id_cargo
              WHERE e.estado = 'ACTIVO' AND NOT EXISTS (SELECT 1 FROM usuario u WHERE u.id_empleado = e.id_empleado)
              ORDER BY e.apellidos"
        );
    }

    public static function conteos(): array
    {
        return BaseDatos::fila(
            "SELECT COUNT(*) AS total, COALESCE(SUM(estado = 'ACTIVO'), 0) AS activos,
                    (SELECT COUNT(*) FROM usuario) AS cuentas
               FROM empleado"
        ) ?? [];
    }
}
