<?php

declare(strict_types=1);

namespace App\Servicios;

use App\Core\BaseDatos;
use App\Core\ExcepcionValidacion;
use DateTimeImmutable;

/**
 * Consulta de disponibilidad (CU-12) y ocupación por noche.
 * Una habitación está disponible para [ingreso, salida) si no está en
 * MANTENIMIENTO y no tiene reservas activas cuyas fechas se crucen:
 *     reserva.fecha_ingreso < salida  AND  reserva.fecha_salida > ingreso
 */
final class DisponibilidadServicio
{
    public const ESTADOS_ACTIVOS = ['PENDIENTE', 'CONFIRMADA', 'EN_CURSO'];
    /** Estados que ocupan una noche en las estadísticas de ocupación. */
    public const ESTADOS_OCUPAN = ['PENDIENTE', 'CONFIRMADA', 'EN_CURSO', 'FINALIZADA'];

    public static function validarRango(?string $ingreso, ?string $salida): void
    {
        if (!$ingreso || !$salida) {
            throw new ExcepcionValidacion(['fecha_ingreso' => 'Indique las fechas de ingreso y salida.']);
        }
        if ($salida <= $ingreso) {
            // CU-12 flujo 1a
            throw new ExcepcionValidacion(['fecha_salida' => 'La fecha de salida debe ser posterior a la fecha de ingreso.']);
        }
        if (noches($ingreso, $salida) > 60) {
            throw new ExcepcionValidacion(['fecha_salida' => 'La estadía no puede superar 60 noches.']);
        }
    }

    /**
     * Habitaciones libres en el rango, con su categoría y la cotización de la temporada.
     */
    public static function habitacionesDisponibles(string $ingreso, string $salida, ?int $idCategoria = null, ?int $excluirReserva = null, int $personas = 0): array
    {
        self::validarRango($ingreso, $salida);
        $parametros = ['ingreso' => $ingreso, 'salida' => $salida, 'excluir' => $excluirReserva ?? 0];
        $filtro = '';
        if ($idCategoria) {
            $filtro .= ' AND h.id_categoria = :categoria';
            $parametros['categoria'] = $idCategoria;
        }
        if ($personas > 0) {
            $filtro .= ' AND c.capacidad >= :personas';
            $parametros['personas'] = $personas;
        }
        $filas = BaseDatos::filas(
            "SELECT h.id_habitacion, h.numero, h.piso, h.estado, h.descripcion, c.id_categoria, c.nombre AS categoria,
                    c.capacidad, c.tarifa_base, c.camas
               FROM habitacion h
               JOIN categoria_habitacion c ON c.id_categoria = h.id_categoria
              WHERE h.estado <> 'MANTENIMIENTO' $filtro
                AND NOT EXISTS (
                    SELECT 1 FROM detalle_reserva d
                      JOIN reserva r ON r.id_reserva = d.id_reserva
                     WHERE d.id_habitacion = h.id_habitacion
                       AND r.id_reserva <> :excluir
                       AND r.estado IN ('PENDIENTE', 'CONFIRMADA', 'EN_CURSO')
                       AND r.fecha_ingreso < :salida
                       AND r.fecha_salida > :ingreso)
              ORDER BY c.tarifa_base, h.numero",
            $parametros
        );
        $cotizaciones = [];
        foreach ($filas as &$f) {
            $idC = (int) $f['id_categoria'];
            $cotizaciones[$idC] ??= TarifaServicio::cotizar($idC, $ingreso, $salida);
            $f['cotizacion'] = $cotizaciones[$idC];
        }
        return $filas;
    }

    /** ¿La habitación está libre en el rango? (se usa dentro de transacciones) */
    public static function estaDisponible(int $idHabitacion, string $ingreso, string $salida, ?int $excluirReserva = null): bool
    {
        $cruces = (int) BaseDatos::valor(
            "SELECT COUNT(*) FROM detalle_reserva d
               JOIN reserva r ON r.id_reserva = d.id_reserva
              WHERE d.id_habitacion = :hab AND r.id_reserva <> :excluir
                AND r.estado IN ('PENDIENTE', 'CONFIRMADA', 'EN_CURSO')
                AND r.fecha_ingreso < :salida AND r.fecha_salida > :ingreso",
            ['hab' => $idHabitacion, 'excluir' => $excluirReserva ?? 0, 'ingreso' => $ingreso, 'salida' => $salida]
        );
        return $cruces === 0;
    }

    /** Habitaciones que pueden venderse (todas excepto las que están en mantenimiento). */
    public static function capacidad(): int
    {
        return (int) BaseDatos::valor("SELECT COUNT(*) FROM habitacion WHERE estado <> 'MANTENIMIENTO'");
    }

    public static function totalHabitaciones(): int
    {
        return (int) BaseDatos::valor('SELECT COUNT(*) FROM habitacion');
    }

    /**
     * Ocupación por noche: [fecha => ['ocupadas' => n, 'capacidad' => c, 'porcentaje' => %]].
     * Cuenta reservas activas y finalizadas (incluye las de sobreventa sin habitación).
     */
    public static function ocupacionPorNoche(string $desde, string $hasta, ?int $capacidad = null, ?int $idCategoria = null): array
    {
        $capacidad ??= self::capacidad();
        $resultado = [];
        foreach (TarifaServicio::fechas($desde, (new DateTimeImmutable($hasta))->modify('+1 day')->format('Y-m-d')) as $f) {
            $resultado[$f] = ['ocupadas' => 0, 'capacidad' => $capacidad, 'porcentaje' => 0.0];
        }
        $filtro = $idCategoria ? ' AND d.id_categoria = :cat' : '';
        $p = ['desde' => $desde, 'hasta' => $hasta];
        if ($idCategoria) {
            $p['cat'] = $idCategoria;
        }
        $filas = BaseDatos::filas(
            "SELECT r.fecha_ingreso, r.fecha_salida
               FROM reserva r JOIN detalle_reserva d ON d.id_reserva = r.id_reserva
              WHERE r.estado IN ('PENDIENTE', 'CONFIRMADA', 'EN_CURSO', 'FINALIZADA')
                AND r.fecha_ingreso <= :hasta AND r.fecha_salida > :desde $filtro",
            $p
        );
        foreach ($filas as $f) {
            $inicio = max($f['fecha_ingreso'], $desde);
            foreach (TarifaServicio::fechas($inicio, $f['fecha_salida']) as $noche) {
                if ($noche > $hasta) {
                    break;
                }
                if (isset($resultado[$noche])) {
                    $resultado[$noche]['ocupadas']++;
                }
            }
        }
        foreach ($resultado as &$r) {
            $r['porcentaje'] = $r['capacidad'] > 0 ? round($r['ocupadas'] / $r['capacidad'] * 100, 1) : 0.0;
        }
        return $resultado;
    }

    /**
     * Datos del calendario de ocupación (rack): habitaciones × días con las
     * reservas que se cruzan con la ventana.
     */
    public static function rack(string $desde, int $dias, ?int $idCategoria = null, ?int $piso = null): array
    {
        $hasta = (new DateTimeImmutable($desde))->modify("+$dias days")->format('Y-m-d');
        $filtro = '';
        $p = [];
        if ($idCategoria) {
            $filtro .= ' AND h.id_categoria = :cat';
            $p['cat'] = $idCategoria;
        }
        if ($piso) {
            $filtro .= ' AND h.piso = :piso';
            $p['piso'] = $piso;
        }
        $habitaciones = BaseDatos::filas(
            "SELECT h.id_habitacion, h.numero, h.piso, h.estado, c.nombre AS categoria, c.id_categoria
               FROM habitacion h JOIN categoria_habitacion c ON c.id_categoria = h.id_categoria
              WHERE 1 = 1 $filtro ORDER BY h.piso, h.numero",
            $p
        );
        $reservas = BaseDatos::filas(
            "SELECT r.id_reserva, r.codigo, r.estado, r.fecha_ingreso, r.fecha_salida, r.es_sobreventa,
                    d.id_habitacion, d.id_categoria, CONCAT(hu.nombres, ' ', hu.apellidos) AS huesped
               FROM reserva r
               JOIN detalle_reserva d ON d.id_reserva = r.id_reserva
               JOIN huesped hu ON hu.id_huesped = r.id_huesped
              WHERE r.estado IN ('PENDIENTE', 'CONFIRMADA', 'EN_CURSO', 'FINALIZADA')
                AND r.fecha_ingreso < :hasta AND r.fecha_salida > :desde
              ORDER BY r.fecha_ingreso",
            ['desde' => $desde, 'hasta' => $hasta]
        );
        $porHabitacion = [];
        $sinAsignar = [];
        foreach ($reservas as $r) {
            if ($r['id_habitacion'] === null) {
                $sinAsignar[] = $r;
            } else {
                $porHabitacion[(int) $r['id_habitacion']][] = $r;
            }
        }
        $fechas = TarifaServicio::fechas($desde, $hasta);
        $ocupacion = self::ocupacionPorNoche($desde, end($fechas) ?: $desde);
        return compact('habitaciones', 'porHabitacion', 'sinAsignar', 'fechas', 'ocupacion', 'desde', 'hasta');
    }

    /** Resumen de disponibilidad por categoría para el sitio público. */
    public static function resumenPorCategoria(string $ingreso, string $salida, int $personas = 1): array
    {
        $libres = self::habitacionesDisponibles($ingreso, $salida, null, null, $personas);
        $resumen = [];
        foreach ($libres as $h) {
            $id = (int) $h['id_categoria'];
            $resumen[$id] ??= [
                'id_categoria' => $id, 'categoria' => $h['categoria'], 'capacidad' => (int) $h['capacidad'],
                'libres' => 0, 'total' => $h['cotizacion']['total'], 'promedio' => $h['cotizacion']['promedio'],
                'noches' => $h['cotizacion']['cantidad'],
            ];
            $resumen[$id]['libres']++;
        }
        return array_values($resumen);
    }
}
