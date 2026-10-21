<?php

declare(strict_types=1);

namespace App\Servicios;

use App\Core\BaseDatos;
use DateTimeImmutable;

/**
 * Indicadores del dashboard ejecutivo.
 *   ADR    (tarifa diaria promedio)      = ingreso por habitaciones / noches vendidas
 *   RevPAR (ingreso por hab. disponible) = ingreso por habitaciones / noches disponibles
 */
final class DashboardServicio
{
    public static function datos(): array
    {
        $hoy = date('Y-m-d');
        $capacidad = DisponibilidadServicio::totalHabitaciones();

        $estados = ['DISPONIBLE' => 0, 'OCUPADA' => 0, 'LIMPIEZA' => 0, 'MANTENIMIENTO' => 0];
        foreach (BaseDatos::filas('SELECT estado, COUNT(*) AS n FROM habitacion GROUP BY estado') as $f) {
            $estados[$f['estado']] = (int) $f['n'];
        }
        $noche = DisponibilidadServicio::ocupacionPorNoche($hoy, $hoy, $capacidad)[$hoy];

        $llegadas = BaseDatos::fila(
            "SELECT COUNT(*) AS total, COALESCE(SUM(estado = 'EN_CURSO'), 0) AS hechas
               FROM reserva WHERE fecha_ingreso = CURDATE() AND estado IN ('PENDIENTE', 'CONFIRMADA', 'EN_CURSO')"
        );
        $salidas = BaseDatos::fila(
            "SELECT COUNT(*) AS total, COALESCE(SUM(estado = 'FINALIZADA'), 0) AS hechas
               FROM reserva WHERE fecha_salida = CURDATE() AND estado IN ('EN_CURSO', 'FINALIZADA')"
        );
        $alojados = BaseDatos::fila("SELECT COUNT(*) AS reservas, COALESCE(SUM(cantidad_personas), 0) AS personas FROM reserva WHERE estado = 'EN_CURSO'");

        // Ingresos cobrados: mes actual vs. mismo periodo del mes anterior
        $inicioMes = date('Y-m-01');
        $ingresosMes = (float) BaseDatos::valor('SELECT COALESCE(SUM(monto), 0) FROM pago WHERE fecha >= :d AND fecha < CURDATE() + INTERVAL 1 DAY', ['d' => $inicioMes]);
        $inicioAnterior = (new DateTimeImmutable('first day of last month'))->format('Y-m-d');
        $finAnterior = (new DateTimeImmutable('first day of last month'))->modify('+' . ((int) date('j') - 1) . ' days')->format('Y-m-d');
        $ingresosAnterior = (float) BaseDatos::valor('SELECT COALESCE(SUM(monto), 0) FROM pago WHERE fecha >= :d AND fecha < :h + INTERVAL 1 DAY', ['d' => $inicioAnterior, 'h' => $finAnterior]);

        $mes = self::rendimiento($inicioMes, $hoy, $capacidad);
        $mesAnterior = self::rendimiento($inicioAnterior, $finAnterior, $capacidad);

        // Tendencia: 30 días atrás y 14 hacia adelante
        $desde = (new DateTimeImmutable())->modify('-29 days')->format('Y-m-d');
        $hasta = (new DateTimeImmutable())->modify('+14 days')->format('Y-m-d');
        $tendencia = DisponibilidadServicio::ocupacionPorNoche($desde, $hasta, $capacidad);

        $canales = BaseDatos::filas(
            "SELECT c.nombre, COUNT(r.id_reserva) AS total
               FROM canal_reserva c
               LEFT JOIN reserva r ON r.id_canal = c.id_canal AND r.fecha_registro >= CURDATE() - INTERVAL 90 DAY
              GROUP BY c.id_canal, c.nombre ORDER BY total DESC"
        );

        $ingresosMensuales = [];
        $inicio = (new DateTimeImmutable('first day of this month'))->modify('-11 months');
        for ($i = 0; $i < 12; $i++) {
            $ingresosMensuales[$inicio->modify("+$i months")->format('Y-m')] = 0.0;
        }
        foreach (BaseDatos::filas(
            "SELECT DATE_FORMAT(fecha, '%Y-%m') AS mes, SUM(monto) AS total FROM pago WHERE fecha >= :d GROUP BY DATE_FORMAT(fecha, '%Y-%m')",
            ['d' => $inicio->format('Y-m-d')]
        ) as $f) {
            if (isset($ingresosMensuales[$f['mes']])) {
                $ingresosMensuales[$f['mes']] = (float) $f['total'];
            }
        }

        $listaLlegadas = BaseDatos::filas(
            "SELECT r.id_reserva, r.codigo, r.estado, r.cantidad_personas, r.fecha_salida, r.es_sobreventa,
                    CONCAT(h.nombres, ' ', h.apellidos) AS huesped, h.nacionalidad, h.ciudad_procedencia,
                    (SELECT GROUP_CONCAT(COALESCE(x.numero, 'Por asignar') SEPARATOR ', ') FROM detalle_reserva d LEFT JOIN habitacion x ON x.id_habitacion = d.id_habitacion WHERE d.id_reserva = r.id_reserva) AS habitaciones
               FROM reserva r JOIN huesped h ON h.id_huesped = r.id_huesped
              WHERE r.fecha_ingreso = CURDATE() AND r.estado IN ('PENDIENTE', 'CONFIRMADA', 'EN_CURSO')
              ORDER BY r.estado = 'EN_CURSO', h.apellidos LIMIT 8"
        );
        $listaSalidas = BaseDatos::filas(
            "SELECT r.id_reserva, r.codigo, r.estado, r.monto_total, r.cargos_extra,
                    CONCAT(h.nombres, ' ', h.apellidos) AS huesped,
                    (SELECT COALESCE(SUM(p.monto), 0) FROM pago p WHERE p.id_reserva = r.id_reserva) AS pagado,
                    (SELECT GROUP_CONCAT(x.numero SEPARATOR ', ') FROM detalle_reserva d JOIN habitacion x ON x.id_habitacion = d.id_habitacion WHERE d.id_reserva = r.id_reserva) AS habitaciones
               FROM reserva r JOIN huesped h ON h.id_huesped = r.id_huesped
              WHERE r.fecha_salida = CURDATE() AND r.estado IN ('EN_CURSO', 'FINALIZADA')
              ORDER BY r.estado = 'FINALIZADA', h.apellidos LIMIT 8"
        );

        $alertas = SobreventaServicio::alertasProximas(14);
        $config = SobreventaServicio::configuracion();
        $actividad = BaseDatos::filas(
            'SELECT b.*, u.nombre_usuario FROM bitacora b LEFT JOIN usuario u ON u.id_usuario = b.id_usuario ORDER BY b.fecha DESC, b.id_bitacora DESC LIMIT 7'
        );
        $proximas = (int) BaseDatos::valor("SELECT COUNT(*) FROM reserva WHERE estado IN ('PENDIENTE', 'CONFIRMADA') AND fecha_ingreso > CURDATE()");

        return compact(
            'capacidad', 'estados', 'noche', 'llegadas', 'salidas', 'alojados', 'ingresosMes', 'ingresosAnterior',
            'mes', 'mesAnterior', 'tendencia', 'canales', 'ingresosMensuales', 'listaLlegadas', 'listaSalidas',
            'alertas', 'config', 'actividad', 'proximas'
        );
    }

    /** Ingreso por habitaciones, noches vendidas, ADR, RevPAR y ocupación de un periodo. */
    public static function rendimiento(string $desde, string $hasta, int $capacidad): array
    {
        $filas = BaseDatos::filas(
            "SELECT r.fecha_ingreso, r.fecha_salida, d.subtotal, d.noches
               FROM reserva r JOIN detalle_reserva d ON d.id_reserva = r.id_reserva
              WHERE r.estado IN ('EN_CURSO', 'FINALIZADA') AND r.fecha_ingreso <= :h AND r.fecha_salida > :d",
            ['d' => $desde, 'h' => $hasta]
        );
        $ingreso = 0.0;
        $vendidas = 0;
        foreach ($filas as $f) {
            $porNoche = (float) $f['subtotal'] / max(1, (int) $f['noches']);
            foreach (TarifaServicio::fechas(max($f['fecha_ingreso'], $desde), $f['fecha_salida']) as $n) {
                if ($n > $hasta) {
                    break;
                }
                $ingreso += $porNoche;
                $vendidas++;
            }
        }
        $dias = noches($desde, $hasta) + 1;
        $disponibles = $capacidad * $dias;
        return [
            'ingreso'    => $ingreso,
            'vendidas'   => $vendidas,
            'disponibles' => $disponibles,
            'adr'        => $vendidas ? $ingreso / $vendidas : 0,
            'revpar'     => $disponibles ? $ingreso / $disponibles : 0,
            'ocupacion'  => $disponibles ? $vendidas / $disponibles * 100 : 0,
        ];
    }

    public static function variacion(float $actual, float $anterior): ?float
    {
        return $anterior > 0 ? ($actual - $anterior) / $anterior * 100 : null;
    }
}
