<?php

declare(strict_types=1);

namespace App\Servicios;

use App\Core\Autenticacion;
use App\Core\BaseDatos;
use App\Core\Paginador;
use Throwable;

/**
 * Auditoría de acciones importantes (quién, qué, cuándo y desde dónde).
 */
final class BitacoraServicio
{
    public static function registrar(string $modulo, string $accion, string $descripcion = '', ?string $ip = null, ?int $idUsuario = null): void
    {
        try {
            BaseDatos::insertar('bitacora', [
                'id_usuario'  => $idUsuario ?? Autenticacion::id(),
                'modulo'      => mb_substr($modulo, 0, 40),
                'accion'      => mb_substr($accion, 0, 60),
                'descripcion' => mb_substr($descripcion, 0, 255),
                'ip'          => $ip ?? ($_SERVER['REMOTE_ADDR'] ?? 'CLI'),
            ]);
        } catch (Throwable) {
            // La auditoría nunca debe interrumpir la operación principal.
        }
    }

    public static function listar(array $filtros, int $pagina): array
    {
        $donde = ['1 = 1'];
        $p = [];
        if (!empty($filtros['modulo'])) {
            $donde[] = 'b.modulo = :modulo';
            $p['modulo'] = $filtros['modulo'];
        }
        if (!empty($filtros['usuario'])) {
            $donde[] = 'b.id_usuario = :usuario';
            $p['usuario'] = (int) $filtros['usuario'];
        }
        if (!empty($filtros['q'])) {
            $donde[] = '(b.accion LIKE :q OR b.descripcion LIKE :q2)';
            $p['q'] = '%' . $filtros['q'] . '%';
            $p['q2'] = '%' . $filtros['q'] . '%';
        }
        if (!empty($filtros['desde'])) {
            $donde[] = 'b.fecha >= :desde';
            $p['desde'] = $filtros['desde'] . ' 00:00:00';
        }
        if (!empty($filtros['hasta'])) {
            $donde[] = 'b.fecha <= :hasta';
            $p['hasta'] = $filtros['hasta'] . ' 23:59:59';
        }
        $where = implode(' AND ', $donde);
        $total = (int) BaseDatos::valor("SELECT COUNT(*) FROM bitacora b WHERE $where", $p);
        $paginador = new Paginador($total, $pagina, 25);
        $filas = BaseDatos::filas(
            "SELECT b.*, u.nombre_usuario, CONCAT(e.nombres, ' ', e.apellidos) AS empleado, r.nombre AS rol
               FROM bitacora b
               LEFT JOIN usuario u ON u.id_usuario = b.id_usuario
               LEFT JOIN empleado e ON e.id_empleado = u.id_empleado
               LEFT JOIN rol r ON r.id_rol = u.id_rol
              WHERE $where
              ORDER BY b.fecha DESC, b.id_bitacora DESC
              LIMIT {$paginador->porPagina} OFFSET {$paginador->desplazamiento}",
            $p
        );
        return [$filas, $paginador];
    }

    public static function modulos(): array
    {
        return BaseDatos::consulta('SELECT DISTINCT modulo FROM bitacora ORDER BY modulo')->fetchAll(\PDO::FETCH_COLUMN);
    }
}
