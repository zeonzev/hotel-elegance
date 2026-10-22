<?php

declare(strict_types=1);

namespace App\Servicios;

use App\Core\BaseDatos;
use DateTimeImmutable;

/**
 * Sprint 7 · Reportes: CU-22 ocupación, CU-23 ingresos y CU-24 cancelaciones.
 */
final class ReporteServicio
{
    /** CU-22: noches ocupadas / noches disponibles por día y por categoría. */
    public static function ocupacion(string $desde, string $hasta): array
    {
        $total = DisponibilidadServicio::totalHabitaciones();
        $diario = DisponibilidadServicio::ocupacionPorNoche($desde, $hasta, $total);
        $dias = count($diario);
        $categorias = [];
        foreach (BaseDatos::filas('SELECT c.id_categoria, c.nombre, COUNT(h.id_habitacion) AS habitaciones FROM categoria_habitacion c LEFT JOIN habitacion h ON h.id_categoria = c.id_categoria GROUP BY c.id_categoria, c.nombre ORDER BY c.tarifa_base') as $c) {
            $serie = DisponibilidadServicio::ocupacionPorNoche($desde, $hasta, max(1, (int) $c['habitaciones']), (int) $c['id_categoria']);
            $ocupadas = array_sum(array_column($serie, 'ocupadas'));
            $disponibles = (int) $c['habitaciones'] * $dias;
            $categorias[] = ['nombre' => $c['nombre'], 'habitaciones' => (int) $c['habitaciones'], 'ocupadas' => $ocupadas, 'disponibles' => $disponibles, 'porcentaje' => $disponibles ? $ocupadas / $disponibles * 100 : 0];
        }
        $semana = array_fill(1, 7, ['suma' => 0.0, 'n' => 0]);
        foreach ($diario as $f => $o) {
            $d = (int) date('N', strtotime($f));
            $semana[$d]['suma'] += $o['porcentaje'];
            $semana[$d]['n']++;
        }
        $ocupadas = array_sum(array_column($diario, 'ocupadas'));
        $disponibles = $total * $dias;
        $rend = DashboardServicio::rendimiento($desde, $hasta, $total);
        $pcts = array_column($diario, 'porcentaje');
        return [
            'diario' => $diario, 'categorias' => $categorias,
            'semana' => array_map(fn ($s) => $s['n'] ? $s['suma'] / $s['n'] : 0, $semana),
            'resumen' => [
                'dias' => $dias, 'habitaciones' => $total, 'ocupadas' => $ocupadas, 'disponibles' => $disponibles,
                'porcentaje' => $disponibles ? $ocupadas / $disponibles * 100 : 0,
                'maximo' => $pcts ? max($pcts) : 0, 'minimo' => $pcts ? min($pcts) : 0,
                'dia_maximo' => $pcts ? array_keys($diario)[array_search(max($pcts), $pcts, true)] : null,
                'adr' => $rend['adr'], 'revpar' => $rend['revpar'],
            ],
        ];
    }

    /** CU-23: ingresos estimados (vw_ingresos_mensuales) y cobrados (pagos). */
    public static function ingresos(string $desde, string $hasta): array
    {
        $estimados = BaseDatos::filas(
            "SELECT DATE_FORMAT(r.fecha_ingreso, '%Y-%m') AS mes, c.nombre AS categoria, COUNT(DISTINCT r.id_reserva) AS reservas,
                    SUM(d.noches) AS noches, SUM(d.subtotal) AS ingreso
               FROM reserva r JOIN detalle_reserva d ON d.id_reserva = r.id_reserva JOIN categoria_habitacion c ON c.id_categoria = d.id_categoria
              WHERE r.estado NOT IN ('CANCELADA', 'NO_SHOW') AND r.fecha_ingreso BETWEEN :d AND :h
              GROUP BY DATE_FORMAT(r.fecha_ingreso, '%Y-%m'), c.id_categoria, c.nombre ORDER BY mes, c.tarifa_base",
            ['d' => $desde, 'h' => $hasta]
        );
        $meses = [];
        $categorias = [];
        foreach ($estimados as $e) {
            $meses[$e['mes']] ??= [];
            $meses[$e['mes']][$e['categoria']] = (float) $e['ingreso'];
            $categorias[$e['categoria']] ??= ['reservas' => 0, 'noches' => 0, 'ingreso' => 0.0];
            $categorias[$e['categoria']]['reservas'] += (int) $e['reservas'];
            $categorias[$e['categoria']]['noches'] += (int) $e['noches'];
            $categorias[$e['categoria']]['ingreso'] += (float) $e['ingreso'];
        }
        $cobradosMes = BaseDatos::filas(
            "SELECT DATE_FORMAT(fecha, '%Y-%m') AS mes, SUM(monto) AS total FROM pago WHERE fecha >= :d AND fecha < :h + INTERVAL 1 DAY GROUP BY DATE_FORMAT(fecha, '%Y-%m') ORDER BY mes",
            ['d' => $desde, 'h' => $hasta]
        );
        $metodos = BaseDatos::filas(
            'SELECT m.nombre, COUNT(*) AS pagos, SUM(p.monto) AS total FROM pago p JOIN metodo_pago m ON m.id_metodo_pago = p.id_metodo_pago
              WHERE p.fecha >= :d AND p.fecha < :h + INTERVAL 1 DAY GROUP BY m.id_metodo_pago, m.nombre ORDER BY total DESC',
            ['d' => $desde, 'h' => $hasta]
        );
        $conceptos = BaseDatos::filas(
            'SELECT concepto, SUM(monto) AS total FROM pago WHERE fecha >= :d AND fecha < :h + INTERVAL 1 DAY GROUP BY concepto ORDER BY total DESC',
            ['d' => $desde, 'h' => $hasta]
        );
        $canales = BaseDatos::filas(
            "SELECT c.nombre, COUNT(*) AS reservas, SUM(r.monto_total) AS total FROM reserva r JOIN canal_reserva c ON c.id_canal = r.id_canal
              WHERE r.estado NOT IN ('CANCELADA', 'NO_SHOW') AND r.fecha_ingreso BETWEEN :d AND :h GROUP BY c.id_canal, c.nombre ORDER BY total DESC",
            ['d' => $desde, 'h' => $hasta]
        );
        $totalEstimado = array_sum(array_column($categorias, 'ingreso'));
        $totalCobrado = array_sum(array_map(fn ($c) => (float) $c['total'], $cobradosMes));
        $reservas = array_sum(array_column($categorias, 'reservas'));
        return [
            'meses' => $meses, 'categorias' => $categorias, 'cobradosMes' => $cobradosMes, 'metodos' => $metodos,
            'conceptos' => $conceptos, 'canales' => $canales,
            'resumen' => [
                'estimado' => $totalEstimado, 'cobrado' => $totalCobrado, 'reservas' => $reservas,
                'promedio' => $reservas ? $totalEstimado / $reservas : 0,
                'noches' => array_sum(array_column($categorias, 'noches')),
            ],
        ];
    }

    /** CU-24: cancelaciones agrupadas por tipo de motivo (vw_reporte_cancelaciones). */
    public static function cancelaciones(string $desde, string $hasta): array
    {
        $lista = BaseDatos::filas(
            'SELECT * FROM vw_reporte_cancelaciones WHERE fecha_cancelacion >= :d AND fecha_cancelacion < :h + INTERVAL 1 DAY ORDER BY fecha_cancelacion DESC',
            ['d' => $desde, 'h' => $hasta]
        );
        $motivos = [];
        foreach (MOTIVOS_CANCELACION as $clave => $texto) {
            $motivos[$clave] = ['texto' => $texto, 'cantidad' => 0, 'monto' => 0.0];
        }
        $canales = [];
        $anticipacion = 0;
        foreach ($lista as $c) {
            $motivos[$c['tipo_motivo']]['cantidad']++;
            $motivos[$c['tipo_motivo']]['monto'] += (float) $c['monto_total'];
            $canales[$c['canal']] = ($canales[$c['canal']] ?? 0) + 1;
            $anticipacion += max(0, (int) $c['dias_anticipacion']);
        }
        uasort($motivos, fn ($a, $b) => $b['cantidad'] <=> $a['cantidad']);
        arsort($canales);
        $totales = BaseDatos::fila(
            "SELECT COUNT(*) AS reservas, COALESCE(SUM(estado = 'CANCELADA'), 0) AS canceladas, COALESCE(SUM(estado = 'NO_SHOW'), 0) AS no_show
               FROM reserva WHERE fecha_ingreso BETWEEN :d AND :h",
            ['d' => $desde, 'h' => $hasta]
        );
        $n = count($lista);
        return [
            'lista' => $lista, 'motivos' => $motivos, 'canales' => $canales,
            'resumen' => [
                'cantidad' => $n, 'monto' => array_sum(array_column($lista, 'monto_total')),
                'anticipacion' => $n ? $anticipacion / $n : 0,
                'tasa' => (int) $totales['reservas'] ? (int) $totales['canceladas'] / (int) $totales['reservas'] * 100 : 0,
                'no_show' => (int) $totales['no_show'],
                'tasa_no_show' => (int) $totales['reservas'] ? (int) $totales['no_show'] / (int) $totales['reservas'] * 100 : 0,
                'reservas' => (int) $totales['reservas'],
            ],
        ];
    }

    /**
     * Procedencia de los huéspedes: de qué países y ciudades llegan las estadías
     * del periodo (reservas en curso o finalizadas, según su fecha de ingreso).
     */
    public static function procedencia(string $desde, string $hasta): array
    {
        $filas = BaseDatos::filas(
            "SELECT h.nacionalidad, h.ciudad_procedencia, COUNT(*) AS estadias, COUNT(DISTINCT h.id_huesped) AS huespedes,
                    SUM(r.cantidad_personas) AS personas, SUM(DATEDIFF(r.fecha_salida, r.fecha_ingreso)) AS noches,
                    SUM(r.monto_total + r.cargos_extra) AS ingreso
               FROM reserva r JOIN huesped h ON h.id_huesped = r.id_huesped
              WHERE r.estado IN ('EN_CURSO', 'FINALIZADA') AND r.fecha_ingreso BETWEEN :d AND :h
              GROUP BY h.nacionalidad, h.ciudad_procedencia",
            ['d' => $desde, 'h' => $hasta]
        );
        $huespedes = (int) BaseDatos::valor(
            "SELECT COUNT(DISTINCT r.id_huesped) FROM reserva r
              WHERE r.estado IN ('EN_CURSO', 'FINALIZADA') AND r.fecha_ingreso BETWEEN :d AND :h",
            ['d' => $desde, 'h' => $hasta]
        );

        $paises = [];
        $ciudades = [];
        $origen = ['nacional' => ['estadias' => 0, 'noches' => 0, 'ingreso' => 0.0], 'extranjero' => ['estadias' => 0, 'noches' => 0, 'ingreso' => 0.0]];
        foreach ($filas as $f) {
            $pais = self::pais((string) $f['nacionalidad']);
            $tipo = $pais === 'Bolivia' ? 'nacional' : 'extranjero';
            $paises[$pais] ??= ['estadias' => 0, 'noches' => 0, 'ingreso' => 0.0];
            $paises[$pais]['estadias'] += (int) $f['estadias'];
            $paises[$pais]['noches'] += (int) $f['noches'];
            $paises[$pais]['ingreso'] += (float) $f['ingreso'];
            $ciudad = (string) ($f['ciudad_procedencia'] ?: 'Sin dato');
            $ciudades[$ciudad] ??= ['pais' => $pais, 'estadias' => 0, 'personas' => 0, 'noches' => 0, 'ingreso' => 0.0];
            $ciudades[$ciudad]['estadias'] += (int) $f['estadias'];
            $ciudades[$ciudad]['personas'] += (int) $f['personas'];
            $ciudades[$ciudad]['noches'] += (int) $f['noches'];
            $ciudades[$ciudad]['ingreso'] += (float) $f['ingreso'];
            $origen[$tipo]['estadias'] += (int) $f['estadias'];
            $origen[$tipo]['noches'] += (int) $f['noches'];
            $origen[$tipo]['ingreso'] += (float) $f['ingreso'];
        }
        uasort($paises, fn ($a, $b) => $b['estadias'] <=> $a['estadias']);
        uasort($ciudades, fn ($a, $b) => $b['estadias'] <=> $a['estadias']);
        $total = $origen['nacional']['estadias'] + $origen['extranjero']['estadias'];
        foreach ($origen as &$o) {
            $o['porcentaje'] = $total ? $o['estadias'] / $total * 100 : 0;
            $o['estancia'] = $o['estadias'] ? $o['noches'] / $o['estadias'] : 0;
        }
        unset($o);
        $extranjeros = array_filter($paises, fn ($p, $nombre) => $nombre !== 'Bolivia', ARRAY_FILTER_USE_BOTH);
        return [
            'paises' => $paises, 'ciudades' => $ciudades, 'origen' => $origen,
            'resumen' => [
                'estadias' => $total, 'huespedes' => $huespedes, 'paises' => count($paises),
                'pais_extranjero' => array_key_first($extranjeros), 'ciudad' => array_key_first($ciudades),
            ],
        ];
    }

    /** País a partir de la nacionalidad registrada («Peruana» → «Perú»). */
    public static function pais(string $nacionalidad): string
    {
        return [
            'Boliviana' => 'Bolivia', 'Peruana' => 'Perú', 'Argentina' => 'Argentina', 'Chilena' => 'Chile',
            'Brasileña' => 'Brasil', 'Colombiana' => 'Colombia', 'Mexicana' => 'México', 'Española' => 'España',
            'Francesa' => 'Francia', 'Alemana' => 'Alemania', 'Estadounidense' => 'Estados Unidos',
            'Italiana' => 'Italia', 'Británica' => 'Reino Unido',
        ][$nacionalidad] ?? ($nacionalidad !== '' ? $nacionalidad : 'Sin dato');
    }

    /** Rangos rápidos para los filtros. */
    public static function rangos(): array
    {
        $hoy = new DateTimeImmutable('today');
        return [
            'mes' => ['Este mes', $hoy->modify('first day of this month')->format('Y-m-d'), $hoy->format('Y-m-d')],
            'anterior' => ['Mes anterior', $hoy->modify('first day of last month')->format('Y-m-d'), $hoy->modify('last day of last month')->format('Y-m-d')],
            'trimestre' => ['Últimos 3 meses', $hoy->modify('-3 months')->format('Y-m-d'), $hoy->format('Y-m-d')],
            'anio' => ['Últimos 12 meses', $hoy->modify('-12 months')->modify('+1 day')->format('Y-m-d'), $hoy->format('Y-m-d')],
            'proximos' => ['Próximos 60 días', $hoy->format('Y-m-d'), $hoy->modify('+59 days')->format('Y-m-d')],
        ];
    }
}
