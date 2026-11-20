<?php

declare(strict_types=1);

namespace App\Servicios;

/**
 * Teoría de Decisiones aplicada a la sobreventa (Sprint 5, CU-18).
 * Clase de cálculo puro (sin base de datos) para poder probarla de forma aislada.
 *
 * Notación:
 *   n   = reservas confirmadas para la noche
 *   cap = habitaciones disponibles físicamente
 *   q   = probabilidad de que un huésped se presente (1 − tasa de no-show)
 *   I   = ingreso por aceptar la reserva adicional
 *   C   = costo de reubicar a un huésped si no hay habitación
 *   S   = número de huéspedes que se presentan ~ Binomial(n, q)
 */
final class ModeloDecision
{
    /** Distribución Binomial(n, q): P(S = k) para k = 0..n (cálculo en logaritmos, estable). */
    public static function binomial(int $n, float $q): array
    {
        $n = max(0, $n);
        $q = min(1.0, max(0.0, $q));
        if ($n === 0) {
            return [1.0];
        }
        if ($q === 0.0 || $q === 1.0) {
            $d = array_fill(0, $n + 1, 0.0);
            $d[$q === 0.0 ? 0 : $n] = 1.0;
            return $d;
        }
        $logQ = log($q);
        $log1Q = log(1 - $q);
        $logComb = 0.0;
        $dist = [];
        for ($k = 0; $k <= $n; $k++) {
            if ($k > 0) {
                $logComb += log($n - $k + 1) - log($k);
            }
            $dist[$k] = exp($logComb + $k * $logQ + ($n - $k) * $log1Q);
        }
        return $dist;
    }

    /** P(S ≤ limite) */
    public static function acumulada(array $dist, int $limite): float
    {
        $suma = 0.0;
        foreach ($dist as $k => $p) {
            if ($k <= $limite) {
                $suma += $p;
            }
        }
        return min(1.0, $suma);
    }

    /** Fórmula del informe (CU-18): VE = p·I − (1 − p)·C */
    public static function veSimple(float $p, float $ingreso, float $costo): float
    {
        return $p * $ingreso - (1 - $p) * $costo;
    }

    /**
     * Valor esperado de ACEPTAR una reserva adicional frente a RECHAZARLA (VE = 0).
     *
     *   VE(aceptar) = Σ P(se presentan k de los n) × [ingreso si hay cupo − costo si no hay cupo]
     *               = q · [ P(S ≤ cap−1)·I − P(S ≥ cap)·C ]
     *
     * Si el huésped adicional no se presenta (probabilidad 1 − q) el resultado es 0.
     */
    public static function evaluarAdicional(int $n, int $cap, float $q, float $ingreso, float $costo): array
    {
        $dist = self::binomial($n, $q);
        $pCupo = $cap <= 0 ? 0.0 : self::acumulada($dist, $cap - 1);
        $pLleno = max(0.0, 1 - $pCupo);
        $ve = $q * ($pCupo * $ingreso - $pLleno * $costo);

        // Matriz de decisión: alternativas × estados de la naturaleza
        $estados = [
            ['estado' => 'El huésped adicional no se presenta', 'probabilidad' => 1 - $q, 'aceptar' => 0.0, 'rechazar' => 0.0],
            ['estado' => 'Se presenta y hay habitación libre', 'probabilidad' => $q * $pCupo, 'aceptar' => $ingreso, 'rechazar' => 0.0],
            ['estado' => 'Se presenta y el hotel está lleno', 'probabilidad' => $q * $pLleno, 'aceptar' => -$costo, 'rechazar' => 0.0],
        ];

        $distribucion = [];
        foreach ($dist as $k => $p) {
            $distribucion[] = [
                'k' => $k,
                'probabilidad' => $p,
                'hay_cupo' => $k < $cap,
                'resultado' => $k < $cap ? $ingreso : -$costo,
            ];
        }

        return [
            've_aceptar'   => $ve,
            've_rechazar'  => 0.0,
            'p_cupo'       => $pCupo,
            'p_lleno'      => $pLleno,
            'matriz'       => $estados,
            'distribucion' => $distribucion,
            'esperados'    => $n * $q,
        ];
    }

    /** Ganancia esperada del hotel si acepta un total de N reservas para cap habitaciones. */
    public static function gananciaEsperada(int $total, int $cap, float $q, float $ingreso, float $costo): array
    {
        $dist = self::binomial($total, $q);
        $ganancia = 0.0;
        $reubicados = 0.0;
        $desborde = 0.0;
        foreach ($dist as $k => $p) {
            $exceso = max(0, $k - $cap);
            $ganancia += $p * ($ingreso * min($k, $cap) - $costo * $exceso);
            $reubicados += $p * $exceso;
            if ($exceso > 0) {
                $desborde += $p;
            }
        }
        return ['ganancia' => $ganancia, 'reubicados' => $reubicados, 'p_desborde' => $desborde, 'presentados' => $total * $q];
    }

    /**
     * Tabla de niveles de sobreventa x = 0..max (reservas por encima de la capacidad)
     * y nivel óptimo: el que maximiza la ganancia esperada.
     */
    public static function niveles(int $cap, float $q, float $ingreso, float $costo, int $maximo = 8): array
    {
        $filas = [];
        $anterior = null;
        $optimo = 0;
        $mejor = -INF;
        for ($x = 0; $x <= $maximo; $x++) {
            $g = self::gananciaEsperada($cap + $x, $cap, $q, $ingreso, $costo);
            $filas[] = [
                'extra'      => $x,
                'total'      => $cap + $x,
                'ganancia'   => $g['ganancia'],
                'marginal'   => $anterior === null ? null : $g['ganancia'] - $anterior,
                'p_desborde' => $g['p_desborde'],
                'reubicados' => $g['reubicados'],
            ];
            if ($g['ganancia'] > $mejor + 1e-9) {
                $mejor = $g['ganancia'];
                $optimo = $x;
            }
            $anterior = $g['ganancia'];
        }
        return ['filas' => $filas, 'optimo' => $optimo];
    }

    /**
     * Probabilidad de cada estado de la naturaleza del Hito 3:
     * E_j = "faltan j huéspedes" con el hotel lleno (cap reservas, cada una falta con prob. p).
     * El último estado agrupa "K o más" para que las probabilidades sumen 1.
     */
    public static function probabilidadesNoShow(int $cap, float $pAusencia, int $maxExtra): array
    {
        $dist = self::binomial($cap, $pAusencia);
        $probs = [];
        for ($j = 0; $j < $maxExtra; $j++) {
            $probs[$j] = $dist[$j] ?? 0.0;
        }
        $probs[$maxExtra] = max(0.0, 1 - array_sum($probs));
        return $probs;
    }

    /**
     * Matriz de pagos del Hito 3: alternativa A_k = aceptar k reservas extra,
     * estado E_j = faltan j huéspedes.
     *   Pago(k, j) = I · mín(k, j) − C · máx(0, k − j)
     */
    public static function matrizPagos(int $maxExtra, float $ingreso, float $costo): array
    {
        $m = [];
        for ($k = 0; $k <= $maxExtra; $k++) {
            for ($j = 0; $j <= $maxExtra; $j++) {
                $m[$k][$j] = $ingreso * min($k, $j) - $costo * max(0, $k - $j);
            }
        }
        return $m;
    }

    /**
     * Criterios de decisión sobre una matriz de pagos:
     *  - bajo riesgo: valor esperado (VE) y valor esperado de la información perfecta (VEIP)
     *  - bajo incertidumbre: Maximax, Maximin, Laplace, Hurwicz (α) y Savage (mínimo arrepentimiento)
     * Ante un empate se elige la alternativa más conservadora (menos reservas extra).
     */
    public static function criterios(array $pagos, array $probs, float $alfa = 0.6): array
    {
        $alfa = min(1.0, max(0.0, $alfa));
        $estados = array_keys($probs);
        // Mejor pago de cada estado (columna): sirve para Savage y para el VEIP
        $mejorEstado = [];
        foreach ($estados as $j) {
            $mejorEstado[$j] = max(array_column($pagos, $j));
        }

        $filas = [];
        foreach ($pagos as $k => $fila) {
            $ve = 0.0;
            foreach ($estados as $j) {
                $ve += $probs[$j] * $fila[$j];
            }
            $arrepentimientos = array_map(fn ($j) => $mejorEstado[$j] - $fila[$j], $estados);
            $filas[$k] = [
                'pagos'          => $fila,
                've'             => $ve,
                'maximax'        => max($fila),
                'maximin'        => min($fila),
                'laplace'        => array_sum($fila) / count($fila),
                'hurwicz'        => $alfa * max($fila) + (1 - $alfa) * min($fila),
                'arrepentimiento' => array_combine($estados, $arrepentimientos),
                'savage'         => max($arrepentimientos),
            ];
        }

        $elegir = function (string $criterio, bool $maximizar = true) use ($filas): int {
            $mejor = null;
            foreach ($filas as $k => $f) {
                if ($mejor === null || ($maximizar ? $f[$criterio] > $filas[$mejor][$criterio] + 1e-9 : $f[$criterio] < $filas[$mejor][$criterio] - 1e-9)) {
                    $mejor = $k;
                }
            }
            return $mejor;
        };

        $optimoVe = $elegir('ve');
        $veConInformacion = 0.0;
        foreach ($estados as $j) {
            $veConInformacion += $probs[$j] * $mejorEstado[$j];
        }

        return [
            'filas'      => $filas,
            'mejor_estado' => $mejorEstado,
            'alfa'       => $alfa,
            'elegida'    => [
                've'      => $optimoVe,
                'maximax' => $elegir('maximax'),
                'maximin' => $elegir('maximin'),
                'laplace' => $elegir('laplace'),
                'hurwicz' => $elegir('hurwicz'),
                'savage'  => $elegir('savage', false),
            ],
            've_optimo'  => $filas[$optimoVe]['ve'],
            've_ci'      => $veConInformacion,
            'veip'       => $veConInformacion - $filas[$optimoVe]['ve'],
        ];
    }

    /** Regla del Hito 3: conviene aceptar una reserva extra si p ≥ C / (I + C). */
    public static function umbralAceptar(float $ingreso, float $costo): float
    {
        return $ingreso + $costo > 0 ? $costo / ($ingreso + $costo) : 1.0;
    }
}
