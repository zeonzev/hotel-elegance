<?php

declare(strict_types=1);

namespace App\Servicios;

use App\Core\BaseDatos;
use DateInterval;
use DatePeriod;
use DateTimeImmutable;

/**
 * Tarifa vigente por noche (CU-11 paso 5, CU-20). Regla de prioridad:
 * fecha especial > temporada alta > temporada baja > tarifa base de la categoría.
 */
final class TarifaServicio
{
    private const PRIORIDAD = ['ESPECIAL' => 3, 'ALTA' => 2, 'BAJA' => 1];

    private static ?array $temporadas = null;
    private static ?array $categorias = null;

    public static function reiniciarCache(): void
    {
        self::$temporadas = null;
        self::$categorias = null;
    }

    private static function cargar(): void
    {
        if (self::$temporadas !== null) {
            return;
        }
        self::$categorias = [];
        foreach (BaseDatos::filas('SELECT id_categoria, nombre, tarifa_base, capacidad FROM categoria_habitacion') as $c) {
            self::$categorias[(int) $c['id_categoria']] = $c;
        }
        self::$temporadas = [];
        $filas = BaseDatos::filas(
            'SELECT t.id_temporada, t.nombre, t.tipo, t.fecha_inicio, t.fecha_fin, t.color, tt.id_categoria, tt.precio_noche
               FROM temporada t
               JOIN tarifa_temporada tt ON tt.id_temporada = t.id_temporada
              WHERE t.estado = 1'
        );
        foreach ($filas as $f) {
            $id = (int) $f['id_temporada'];
            self::$temporadas[$id] ??= [
                'id' => $id, 'nombre' => $f['nombre'], 'tipo' => $f['tipo'], 'color' => $f['color'],
                'inicio' => $f['fecha_inicio'], 'fin' => $f['fecha_fin'], 'precios' => [],
            ];
            self::$temporadas[$id]['precios'][(int) $f['id_categoria']] = (float) $f['precio_noche'];
        }
    }

    /** Temporada de mayor prioridad que cubre la fecha (o null). */
    public static function temporadaEn(string $fecha, ?int $idCategoria = null): ?array
    {
        self::cargar();
        $mejor = null;
        foreach (self::$temporadas as $t) {
            if ($fecha < $t['inicio'] || $fecha > $t['fin']) {
                continue;
            }
            if ($idCategoria !== null && !isset($t['precios'][$idCategoria])) {
                continue;
            }
            if ($mejor === null || self::PRIORIDAD[$t['tipo']] > self::PRIORIDAD[$mejor['tipo']]) {
                $mejor = $t;
            }
        }
        return $mejor;
    }

    /** Precio de una noche para una categoría en una fecha. */
    public static function precioNoche(int $idCategoria, string $fecha): array
    {
        self::cargar();
        $base = (float) (self::$categorias[$idCategoria]['tarifa_base'] ?? 0);
        $temporada = self::temporadaEn($fecha, $idCategoria);
        return [
            'fecha'     => $fecha,
            'precio'    => $temporada ? $temporada['precios'][$idCategoria] : $base,
            'base'      => $base,
            'temporada' => $temporada['nombre'] ?? null,
            'tipo'      => $temporada['tipo'] ?? 'BASE',
            'color'     => $temporada['color'] ?? null,
        ];
    }

    /** Cotización noche por noche de una estadía (fecha de salida excluida). */
    public static function cotizar(int $idCategoria, string $ingreso, string $salida): array
    {
        $noches = [];
        $total = 0.0;
        foreach (self::fechas($ingreso, $salida) as $fecha) {
            $n = self::precioNoche($idCategoria, $fecha);
            $noches[] = $n;
            $total += $n['precio'];
        }
        $cantidad = count($noches);
        return [
            'noches'   => $noches,
            'cantidad' => $cantidad,
            'total'    => round($total, 2),
            'promedio' => $cantidad ? round($total / $cantidad, 2) : 0.0,
            'temporadas' => array_values(array_unique(array_filter(array_column($noches, 'temporada')))),
        ];
    }

    /** Fechas de cada noche entre ingreso (incluido) y salida (excluida). */
    public static function fechas(string $ingreso, string $salida): array
    {
        $inicio = new DateTimeImmutable($ingreso);
        $fin = new DateTimeImmutable($salida);
        if ($fin <= $inicio) {
            return [];
        }
        $fechas = [];
        foreach (new DatePeriod($inicio, new DateInterval('P1D'), $fin) as $dia) {
            $fechas[] = $dia->format('Y-m-d');
        }
        return $fechas;
    }

    public static function temporadas(): array
    {
        self::cargar();
        return self::$temporadas;
    }
}
