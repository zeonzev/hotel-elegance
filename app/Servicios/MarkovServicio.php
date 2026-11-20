<?php

declare(strict_types=1);

namespace App\Servicios;

use App\Core\BaseDatos;
use App\Core\ExcepcionValidacion;
use DateTimeImmutable;

/**
 * Genera la Cadena de Markov del ciclo de una reserva a partir del historial
 * guardado en la base de datos (Segundo Aporte de IO II).
 *
 * Pasos (igual que la lógica funcional del aporte):
 *   1. obtener las reservas del periodo (y de la categoría, si se filtra)
 *   2. convertir sus eventos en un estado por día (C, H, X, N, F)
 *   3. contar las transiciones n_ij entre un día y el siguiente
 *   4. calcular p_ij = n_ij / Σ n_ik y armar la matriz P
 *   5. validar que cada fila sume 1
 *   6. calcular P^n, V_n = V_0·P^n y las probabilidades de absorción
 *
 * No se agregan tablas: los eventos salen de reserva, estadia y cancelacion.
 */
final class MarkovServicio
{
    /** Matriz simulada del Segundo Aporte: se usa sólo si no hay historial suficiente. */
    public const MATRIZ_DEMOSTRATIVA = [
        'C' => ['C' => 0.10, 'H' => 0.75, 'X' => 0.10, 'N' => 0.05, 'F' => 0.00],
        'H' => ['C' => 0.00, 'H' => 0.20, 'X' => 0.00, 'N' => 0.00, 'F' => 0.80],
        'X' => ['C' => 0.00, 'H' => 0.00, 'X' => 1.00, 'N' => 0.00, 'F' => 0.00],
        'N' => ['C' => 0.00, 'H' => 0.00, 'X' => 0.00, 'N' => 1.00, 'F' => 0.00],
        'F' => ['C' => 0.00, 'H' => 0.00, 'X' => 0.00, 'N' => 0.00, 'F' => 1.00],
    ];

    /** Mínimo de transiciones desde Confirmada para confiar en los datos. */
    public const MINIMO_TRANSICIONES = 30;

    /**
     * Estado de una reserva al cierre del día $dia (fechas en formato Y-m-d).
     * Devuelve null si la reserva todavía no existía ese día.
     */
    public static function estadoEnDia(array $r, string $dia): ?string
    {
        if ($dia < $r['registro']) {
            return null;
        }
        if ($r['cancelacion'] !== null && $r['cancelacion'] <= $dia) {
            return 'X';
        }
        if ($r['estado'] === 'NO_SHOW' && $r['fecha_ingreso'] <= $dia) {
            return 'N';
        }
        if ($r['checkout'] !== null && $r['checkout'] <= $dia) {
            return 'F';
        }
        if ($r['checkin'] !== null && $r['checkin'] <= $dia) {
            return 'H';
        }
        return 'C';
    }

    /**
     * Último día cuyo estado se conoce con seguridad. Si la reserva sigue abierta
     * (todavía no llegó o no salió) no se inventa lo que pasará después.
     */
    public static function ultimoDiaConocido(array $r, string $limite): string
    {
        $abierta = $r['cancelacion'] === null && $r['estado'] !== 'NO_SHOW';
        if ($abierta && $r['checkin'] === null) {
            // Aún no se presentó: sólo se sabe que estuvo Confirmada hasta el día anterior a su llegada
            $limite = min($limite, self::sumarDias($r['fecha_ingreso'], -1));
        } elseif ($abierta && $r['checkout'] === null) {
            // Está alojado: se sabe que siguió En estadía hasta el día anterior a su salida
            $limite = min($limite, self::sumarDias($r['fecha_salida'], -1));
        }
        return $limite;
    }

    /** Cuenta las transiciones n_ij de una lista de reservas dentro de [desde, hasta]. */
    public static function contarTransiciones(array $reservas, string $desde, string $hasta): array
    {
        $conteos = CadenaMarkov::conteosVacios();
        foreach ($reservas as $r) {
            $dia = max($r['registro'], $desde);
            $fin = self::ultimoDiaConocido($r, $hasta);
            while ($dia < $fin) {
                $manana = self::sumarDias($dia, 1);
                $actual = self::estadoEnDia($r, $dia);
                if (in_array($actual, CadenaMarkov::ABSORBENTES, true)) {
                    break; // la reserva ya terminó su ciclo
                }
                $siguiente = self::estadoEnDia($r, $manana);
                if ($actual !== null && $siguiente !== null) {
                    $conteos[$actual][$siguiente]++;
                }
                $dia = $manana;
            }
        }
        return $conteos;
    }

    /** Reservas del periodo con sus eventos como fechas (registro, check-in, check-out, cancelación). */
    public static function reservas(string $desde, string $hasta, ?int $idCategoria = null): array
    {
        $sql = "SELECT r.id_reserva, r.estado, DATE(r.fecha_registro) AS registro, r.fecha_ingreso, r.fecha_salida,
                       DATE(e.fecha_checkin) AS checkin, DATE(e.fecha_checkout) AS checkout,
                       CASE WHEN r.estado = 'CANCELADA' THEN DATE(COALESCE(c.fecha, r.fecha_modificacion, r.fecha_ingreso)) END AS cancelacion
                  FROM reserva r
                  LEFT JOIN estadia e ON e.id_reserva = r.id_reserva
                  LEFT JOIN cancelacion c ON c.id_reserva = r.id_reserva
                 WHERE DATE(r.fecha_registro) <= :hasta AND r.fecha_salida >= :desde";
        $parametros = ['hasta' => $hasta, 'desde' => $desde];
        if ($idCategoria) {
            $sql .= ' AND EXISTS (SELECT 1 FROM detalle_reserva d WHERE d.id_reserva = r.id_reserva AND d.id_categoria = :cat)';
            $parametros['cat'] = $idCategoria;
        }
        return BaseDatos::filas($sql, $parametros);
    }

    /**
     * Análisis completo para la pantalla.
     *
     * @param string   $inicial  estado de partida del vector V_0 (C o H)
     * @param int      $pasos    horizonte n en días
     */
    public static function analizar(string $desde, string $hasta, ?int $idCategoria = null, int $pasos = 7, string $inicial = 'C'): array
    {
        if ($hasta < $desde) {
            throw new ExcepcionValidacion(['hasta' => 'La fecha final no puede ser anterior a la inicial.']);
        }
        $hoy = date('Y-m-d');
        $limite = min($hasta, $hoy);
        $reservas = self::reservas($desde, $limite, $idCategoria);
        $conteos = self::contarTransiciones($reservas, $desde, $limite);

        $desdeC = array_sum($conteos['C']);
        $demostrativo = $desdeC < self::MINIMO_TRANSICIONES;
        $p = $demostrativo ? self::MATRIZ_DEMOSTRATIVA : CadenaMarkov::matrizDesdeConteos($conteos);

        $inicial = in_array($inicial, CadenaMarkov::TRANSITORIOS, true) ? $inicial : 'C';
        $pasos = max(1, min(60, $pasos));
        $evolucion = CadenaMarkov::evolucion(CadenaMarkov::vectorInicial($inicial), $p, max($pasos, 30));
        $absorcion = CadenaMarkov::absorcion($p);

        return [
            'desde'        => $desde,
            'hasta'        => $hasta,
            'categoria'    => $idCategoria,
            'pasos'        => $pasos,
            'inicial'      => $inicial,
            'reservas'     => count($reservas),
            'conteos'      => $conteos,
            'transiciones' => array_sum(array_map('array_sum', $conteos)),
            'demostrativo' => $demostrativo,
            'P'            => $p,
            'valida'       => CadenaMarkov::filasSumanUno($p),
            'P2'           => CadenaMarkov::potencia($p, 2),
            'Pn'           => CadenaMarkov::potencia($p, $pasos),
            'clases'       => CadenaMarkov::clasificar($p),
            'evolucion'    => $evolucion,
            'absorcion'    => $absorcion,
            'observado'    => self::resultadosObservados($reservas),
        ];
    }

    /**
     * Validación con datos reales: de las reservas que ya terminaron su ciclo,
     * qué porcentaje terminó Finalizada, Cancelada o No presentada.
     */
    public static function resultadosObservados(array $reservas): array
    {
        $cuenta = ['F' => 0, 'X' => 0, 'N' => 0];
        foreach ($reservas as $r) {
            match ($r['estado']) {
                'FINALIZADA' => $cuenta['F']++,
                'CANCELADA'  => $cuenta['X']++,
                'NO_SHOW'    => $cuenta['N']++,
                default      => null,
            };
        }
        $total = array_sum($cuenta);
        return [
            'total'      => $total,
            'cuenta'     => $cuenta,
            'proporcion' => array_map(fn ($c) => $total ? $c / $total : 0.0, $cuenta),
        ];
    }

    /**
     * Probabilidades que se envían a la Teoría de Decisiones (sobreventa):
     * probabilidad final de que una reserva Confirmada termine como no-show o cancelada.
     */
    public static function probabilidadesParaDecision(int $meses = 12): array
    {
        $hasta = date('Y-m-d');
        $desde = (new DateTimeImmutable())->modify('-' . max(1, $meses) . ' months')->format('Y-m-d');
        $a = self::analizar($desde, $hasta, null, 1, 'C');
        $b = $a['absorcion']['B'];
        return [
            'p_no_show'     => $b['C']['N'],
            'p_cancelacion' => $b['C']['X'],
            'p_finalizada'  => $b['C']['F'],
            'demostrativo'  => $a['demostrativo'],
            'transiciones'  => $a['transiciones'],
            'reservas'      => $a['reservas'],
        ];
    }

    public static function sumarDias(string $fecha, int $dias): string
    {
        return (new DateTimeImmutable($fecha))->modify(($dias >= 0 ? '+' : '') . $dias . ' days')->format('Y-m-d');
    }
}
