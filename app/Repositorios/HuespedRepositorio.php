<?php

declare(strict_types=1);

namespace App\Repositorios;

use App\Core\BaseDatos;
use App\Core\ExcepcionValidacion;
use App\Core\Paginador;
use App\Core\Validador;

/**
 * Entidad Huesped (paquete Gestión de Huéspedes).
 */
final class HuespedRepositorio
{
    public const REGLAS = [
        'id_tipo_documento'  => 'requerido|entero',
        'nro_documento'      => 'requerido|documento',
        'nombres'            => 'requerido|nombre|max:80',
        'apellidos'          => 'requerido|nombre|max:80',
        'nacionalidad'       => 'requerido|max:60',
        'ciudad_procedencia' => 'requerido|max:80',
        'telefono'           => 'opcional|telefono',
        'correo'             => 'opcional|correo|max:120',
        'fecha_nacimiento'   => 'opcional|fecha',
        'observaciones'      => 'opcional|max:1000',
    ];

    public static function listar(array $f, int $pagina): array
    {
        $donde = ['1 = 1'];
        $p = [];
        if (!empty($f['q'])) {
            $donde[] = "(CONCAT(h.nombres, ' ', h.apellidos) LIKE :q OR h.nro_documento LIKE :q2 OR h.correo LIKE :q3 OR h.telefono LIKE :q4)";
            $p += ['q' => "%{$f['q']}%", 'q2' => "%{$f['q']}%", 'q3' => "%{$f['q']}%", 'q4' => "%{$f['q']}%"];
        }
        if (!empty($f['nacionalidad'])) {
            $donde[] = 'h.nacionalidad = :nac';
            $p['nac'] = $f['nacionalidad'];
        }
        if (($f['origen'] ?? '') === 'nacional') {
            $donde[] = "h.nacionalidad = 'Boliviana'";
        } elseif (($f['origen'] ?? '') === 'extranjero') {
            $donde[] = "h.nacionalidad <> 'Boliviana'";
        }
        $orden = match ($f['orden'] ?? '') {
            'estadias' => 'estadias DESC, h.apellidos',
            'reciente' => 'ultima_visita IS NULL, ultima_visita DESC',
            'gasto' => 'gasto DESC',
            default => 'h.apellidos, h.nombres',
        };
        $where = implode(' AND ', $donde);
        $total = (int) BaseDatos::valor("SELECT COUNT(*) FROM huesped h WHERE $where", $p);
        $paginador = new Paginador($total, $pagina, 15);
        $filas = BaseDatos::filas(
            "SELECT h.*, td.sigla,
                    (SELECT COUNT(*) FROM reserva r WHERE r.id_huesped = h.id_huesped AND r.estado = 'FINALIZADA') AS estadias,
                    (SELECT MAX(r.fecha_ingreso) FROM reserva r WHERE r.id_huesped = h.id_huesped AND r.estado IN ('FINALIZADA', 'EN_CURSO')) AS ultima_visita,
                    (SELECT COALESCE(SUM(r.monto_total + r.cargos_extra), 0) FROM reserva r WHERE r.id_huesped = h.id_huesped AND r.estado = 'FINALIZADA') AS gasto,
                    (SELECT COUNT(*) FROM reserva r WHERE r.id_huesped = h.id_huesped AND r.estado = 'EN_CURSO') AS alojado
               FROM huesped h JOIN tipo_documento td ON td.id_tipo_documento = h.id_tipo_documento
              WHERE $where ORDER BY $orden
              LIMIT {$paginador->porPagina} OFFSET {$paginador->desplazamiento}",
            $p
        );
        return [$filas, $paginador];
    }

    public static function buscar(int $id): ?array
    {
        return BaseDatos::fila(
            'SELECT h.*, td.sigla, td.nombre AS tipo_documento FROM huesped h JOIN tipo_documento td ON td.id_tipo_documento = h.id_tipo_documento WHERE h.id_huesped = ?',
            [$id]
        );
    }

    public static function porDocumento(int $tipo, string $nro): ?array
    {
        return BaseDatos::fila('SELECT * FROM huesped WHERE id_tipo_documento = ? AND nro_documento = ?', [$tipo, strtoupper(trim($nro))]);
    }

    /** Búsqueda rápida por documento o nombre (CU-06 paso 1, asistente de reserva). */
    public static function buscarTexto(string $q, int $limite = 8): array
    {
        $like = '%' . trim($q) . '%';
        return BaseDatos::filas(
            "SELECT h.id_huesped, h.nombres, h.apellidos, h.nro_documento, h.nacionalidad, h.ciudad_procedencia, h.telefono, h.correo, td.sigla,
                    (SELECT COUNT(*) FROM reserva r WHERE r.id_huesped = h.id_huesped AND r.estado = 'FINALIZADA') AS estadias
               FROM huesped h JOIN tipo_documento td ON td.id_tipo_documento = h.id_tipo_documento
              WHERE h.nro_documento LIKE :a OR CONCAT(h.nombres, ' ', h.apellidos) LIKE :b
              ORDER BY h.nro_documento = :exacto DESC, h.apellidos LIMIT $limite",
            ['a' => $like, 'b' => $like, 'exacto' => trim($q)]
        );
    }

    /** CU-07: historial de reservas y estadías desde la vista vw_historial_huesped. */
    public static function historial(int $id): array
    {
        return BaseDatos::filas('SELECT * FROM vw_historial_huesped WHERE id_huesped = ? ORDER BY fecha_ingreso DESC', [$id]);
    }

    public static function tiposDocumento(): array
    {
        return BaseDatos::filas('SELECT * FROM tipo_documento ORDER BY id_tipo_documento');
    }

    public static function nacionalidades(): array
    {
        return BaseDatos::consulta('SELECT nacionalidad FROM huesped GROUP BY nacionalidad ORDER BY COUNT(*) DESC')->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Valida y normaliza los datos. CU-05 paso 3: el documento no puede estar registrado.
     * Si ya existe, el error incluye el id del huésped existente (flujo 3a: usarlo).
     */
    public static function validar(array $entrada, int $excluir = 0): array
    {
        $d = Validador::validar($entrada, self::REGLAS);
        $d['nro_documento'] = strtoupper((string) $d['nro_documento']);
        $d['id_tipo_documento'] = (int) $d['id_tipo_documento'];
        $d['correo'] = $d['correo'] ? mb_strtolower((string) $d['correo']) : null;
        if (!BaseDatos::valor('SELECT 1 FROM tipo_documento WHERE id_tipo_documento = ?', [$d['id_tipo_documento']])) {
            throw ExcepcionValidacion::campo('id_tipo_documento', 'Seleccione el tipo de documento.');
        }
        if ($d['fecha_nacimiento'] && $d['fecha_nacimiento'] > date('Y-m-d', strtotime('-16 years'))) {
            throw ExcepcionValidacion::campo('fecha_nacimiento', 'El titular de la reserva debe ser mayor de 16 años.');
        }
        $existente = self::porDocumento($d['id_tipo_documento'], $d['nro_documento']);
        if ($existente && (int) $existente['id_huesped'] !== $excluir) {
            $e = new ExcepcionValidacion(['nro_documento' => "Ya existe un huésped con ese documento: {$existente['nombres']} {$existente['apellidos']}."]);
            $e->existente = $existente;
            throw $e;
        }
        return $d;
    }

    public static function crear(array $d): int
    {
        return BaseDatos::insertar('huesped', $d);
    }

    public static function actualizar(int $id, array $d): void
    {
        BaseDatos::actualizar('huesped', $d, 'id_huesped = :id', ['id' => $id]);
    }
}
