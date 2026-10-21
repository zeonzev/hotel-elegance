<?php

declare(strict_types=1);

namespace App\Servicios;

use App\Core\Autenticacion;
use App\Core\BaseDatos;
use App\Core\ExcepcionValidacion;
use DateTimeImmutable;

/**
 * Estimación de demanda y tarifa referencial (Sprint 6, CU-21).
 *
 *   ocupación histórica  Ō = promedio de ocupación de las mismas fechas (±3 días)
 *                           del año anterior para la categoría
 *   factor               f = 1 + α · (Ō − meta), acotado entre [f_min, f_max]
 *   tarifa sugerida        = tarifa base × f   (redondeada a Bs 10)
 *
 * Si no hay suficientes datos (menos de 3 noches de muestra) se sugiere la
 * tarifa base (flujo 2a).
 */
final class DemandaServicio
{
    public static function parametros(): array
    {
        return [
            'meta'   => (float) ajuste('tarifa_meta_ocupacion', 70) / 100,
            'alfa'   => (float) ajuste('tarifa_sensibilidad', 0.8),
            'minimo' => (float) ajuste('tarifa_factor_min', 0.85),
            'maximo' => (float) ajuste('tarifa_factor_max', 1.40),
            'ventana' => 3,
        ];
    }

    /** Factor de ajuste puro (probado en tests/): f = 1 + α(Ō − meta), acotado. */
    public static function factor(float $ocupacion, float $meta, float $alfa, float $minimo, float $maximo): float
    {
        return round(min($maximo, max($minimo, 1 + $alfa * ($ocupacion - $meta))), 3);
    }

    public static function redondear(float $monto): float
    {
        return round($monto / 10) * 10;
    }

    /** Ocupación promedio de fechas similares (mismo periodo del año anterior ± ventana). */
    public static function ocupacionHistorica(string $desde, string $hasta, int $idCategoria): array
    {
        $p = self::parametros();
        $inicio = (new DateTimeImmutable($desde))->modify('-1 year')->modify("-{$p['ventana']} days");
        $fin = (new DateTimeImmutable($hasta))->modify('-1 year')->modify("+{$p['ventana']} days");
        // Sólo se consideran noches ya transcurridas
        $limite = new DateTimeImmutable('yesterday');
        if ($fin > $limite) {
            $fin = $limite;
        }
        $habitaciones = (int) BaseDatos::valor('SELECT COUNT(*) FROM habitacion WHERE id_categoria = ?', [$idCategoria]);
        if ($inicio > $fin || $habitaciones === 0) {
            return ['promedio' => null, 'dias' => 0, 'serie' => [], 'desde' => $inicio->format('Y-m-d'), 'hasta' => $fin->format('Y-m-d')];
        }
        $serie = DisponibilidadServicio::ocupacionPorNoche($inicio->format('Y-m-d'), $fin->format('Y-m-d'), $habitaciones, $idCategoria);
        // Sin ninguna reserva registrada en el periodo no hay historial válido
        $conDatos = (int) BaseDatos::valor(
            'SELECT COUNT(*) FROM reserva WHERE fecha_ingreso <= :h AND fecha_salida > :d',
            ['h' => $fin->format('Y-m-d'), 'd' => $inicio->format('Y-m-d')]
        );
        if ($conDatos === 0) {
            return ['promedio' => null, 'dias' => 0, 'serie' => [], 'desde' => $inicio->format('Y-m-d'), 'hasta' => $fin->format('Y-m-d')];
        }
        $valores = array_map(fn ($o) => min(1, $o['ocupadas'] / max(1, $habitaciones)), $serie);
        return [
            'promedio' => count($valores) ? array_sum($valores) / count($valores) : null,
            'dias'     => count($valores),
            'serie'    => $serie,
            'desde'    => $inicio->format('Y-m-d'),
            'hasta'    => $fin->format('Y-m-d'),
        ];
    }

    /** CU-21: calcula la tarifa sugerida para una categoría y un rango de fechas. */
    public static function sugerir(string $desde, string $hasta, int $idCategoria, bool $guardar = true): array
    {
        if ($hasta < $desde) {
            throw new ExcepcionValidacion(['fecha_hasta' => 'La fecha final no puede ser anterior a la inicial.']);
        }
        $categoria = BaseDatos::fila('SELECT * FROM categoria_habitacion WHERE id_categoria = ?', [$idCategoria]);
        if (!$categoria) {
            throw new ExcepcionValidacion(['id_categoria' => 'Seleccione una categoría válida.']);
        }
        $p = self::parametros();
        $historico = self::ocupacionHistorica($desde, $hasta, $idCategoria);
        $base = (float) $categoria['tarifa_base'];
        $vigente = TarifaServicio::cotizar($idCategoria, $desde, (new DateTimeImmutable($hasta))->modify('+1 day')->format('Y-m-d'));
        $suficiente = $historico['promedio'] !== null && $historico['dias'] >= 3;
        $ocupacion = $suficiente ? $historico['promedio'] : $p['meta'];
        $factor = $suficiente ? self::factor($ocupacion, $p['meta'], $p['alfa'], $p['minimo'], $p['maximo']) : 1.0;
        $sugerida = self::redondear($base * $factor);

        // Ocupación ya reservada para las fechas objetivo (demanda "en libros")
        $habitaciones = (int) BaseDatos::valor('SELECT COUNT(*) FROM habitacion WHERE id_categoria = ?', [$idCategoria]);
        $reservada = DisponibilidadServicio::ocupacionPorNoche($desde, $hasta, max(1, $habitaciones), $idCategoria);
        $pctReservada = count($reservada) ? array_sum(array_column($reservada, 'porcentaje')) / count($reservada) : 0;

        $resultado = [
            'desde' => $desde, 'hasta' => $hasta, 'categoria' => $categoria,
            'historico' => $historico, 'suficiente' => $suficiente, 'ocupacion' => $ocupacion,
            'factor' => $factor, 'base' => $base, 'sugerida' => $sugerida,
            'vigente_promedio' => $vigente['promedio'], 'temporadas' => $vigente['temporadas'],
            'reservada' => $pctReservada, 'parametros' => $p, 'id_sugerencia' => null,
        ];
        if ($guardar) {
            $resultado['id_sugerencia'] = BaseDatos::insertar('sugerencia_tarifa', [
                'fecha_desde' => $desde, 'fecha_hasta' => $hasta, 'id_categoria' => $idCategoria,
                'ocupacion_historica' => round($ocupacion * 100, 2), 'dias_muestra' => $historico['dias'],
                'factor' => $factor, 'tarifa_base' => $base, 'tarifa_sugerida' => $sugerida,
                'aplicada' => 0, 'id_usuario' => Autenticacion::id(),
            ]);
        }
        return $resultado;
    }

    /** Aplica la sugerencia creando una temporada especial con ese precio. */
    public static function aplicar(int $idSugerencia, string $nombre, ?float $precio = null): int
    {
        return BaseDatos::transaccion(function () use ($idSugerencia, $nombre, $precio) {
            $s = BaseDatos::fila('SELECT * FROM sugerencia_tarifa WHERE id_sugerencia = ?', [$idSugerencia]);
            if (!$s) {
                throw new ExcepcionValidacion(['id_sugerencia' => 'La sugerencia no existe.']);
            }
            if ((int) $s['aplicada'] === 1) {
                throw new ExcepcionValidacion(['id_sugerencia' => 'Esta sugerencia ya fue aplicada.']);
            }
            $precioFinal = $precio && $precio > 0 ? $precio : (float) $s['tarifa_sugerida'];
            $idTemporada = BaseDatos::insertar('temporada', [
                'nombre' => mb_substr($nombre, 0, 80), 'tipo' => 'ESPECIAL',
                'fecha_inicio' => $s['fecha_desde'], 'fecha_fin' => $s['fecha_hasta'],
                'descripcion' => 'Creada desde la tarifa referencial sugerida (CU-21).', 'color' => '#9C4F8E',
            ]);
            // Las demás categorías conservan su tarifa vigente en ese rango
            foreach (BaseDatos::filas('SELECT id_categoria FROM categoria_habitacion') as $c) {
                $idC = (int) $c['id_categoria'];
                $valor = $idC === (int) $s['id_categoria'] ? $precioFinal : TarifaServicio::precioNoche($idC, $s['fecha_desde'])['precio'];
                BaseDatos::insertar('tarifa_temporada', ['id_temporada' => $idTemporada, 'id_categoria' => $idC, 'precio_noche' => $valor]);
            }
            BaseDatos::actualizar('sugerencia_tarifa', ['aplicada' => 1, 'id_temporada' => $idTemporada, 'tarifa_sugerida' => $precioFinal], 'id_sugerencia = :id', ['id' => $idSugerencia]);
            TarifaServicio::reiniciarCache();
            return $idTemporada;
        });
    }

    /**
     * Mapa de demanda de los próximos días: ocupación ya reservada vs. ocupación
     * histórica de la misma fecha del año anterior, y la temporada vigente.
     */
    public static function mapa(int $dias = 42): array
    {
        $desde = date('Y-m-d');
        $hasta = (new DateTimeImmutable())->modify('+' . ($dias - 1) . ' days')->format('Y-m-d');
        $capacidad = DisponibilidadServicio::totalHabitaciones();
        $reservada = DisponibilidadServicio::ocupacionPorNoche($desde, $hasta, $capacidad);
        $anioPasado = DisponibilidadServicio::ocupacionPorNoche(
            (new DateTimeImmutable($desde))->modify('-1 year')->format('Y-m-d'),
            (new DateTimeImmutable($hasta))->modify('-1 year')->format('Y-m-d'),
            $capacidad
        );
        $historicos = array_values($anioPasado);
        $mapa = [];
        $i = 0;
        foreach ($reservada as $fecha => $o) {
            $t = TarifaServicio::temporadaEn($fecha);
            $mapa[$fecha] = [
                'reservada' => $o['porcentaje'],
                'historica' => $historicos[$i]['porcentaje'] ?? null,
                'temporada' => $t['nombre'] ?? null,
                'tipo'      => $t['tipo'] ?? null,
            ];
            $i++;
        }
        return $mapa;
    }

    /** Ocupación mensual de los últimos 12 meses (para el gráfico de demanda). */
    public static function ocupacionMensual(int $meses = 12): array
    {
        $capacidad = DisponibilidadServicio::totalHabitaciones();
        $inicio = (new DateTimeImmutable('first day of this month'))->modify('-' . ($meses - 1) . ' months');
        $fin = new DateTimeImmutable('last day of this month');
        $serie = DisponibilidadServicio::ocupacionPorNoche($inicio->format('Y-m-d'), $fin->format('Y-m-d'), $capacidad);
        $resultado = [];
        foreach ($serie as $fecha => $o) {
            $clave = substr($fecha, 0, 7);
            $resultado[$clave] ??= ['ocupadas' => 0, 'disponibles' => 0];
            $resultado[$clave]['ocupadas'] += $o['ocupadas'];
            $resultado[$clave]['disponibles'] += $capacidad;
        }
        foreach ($resultado as &$r) {
            $r['porcentaje'] = $r['disponibles'] ? round($r['ocupadas'] / $r['disponibles'] * 100, 1) : 0;
        }
        return $resultado;
    }
}
