<?php

declare(strict_types=1);

namespace App\Servicios;

/**
 * Cadenas de Markov aplicadas al ciclo de vida de una reserva (aporte de IO II).
 * Clase de cálculo puro (sin base de datos), igual que ModeloDecision, para poder
 * probarla de forma aislada.
 *
 * Estados (un paso = un día):
 *   C = Confirmada      (transitorio)
 *   H = En estadía      (transitorio)
 *   X = Cancelada       (absorbente)
 *   N = No presentada   (absorbente)
 *   F = Finalizada      (absorbente)
 *
 * Fórmulas usadas:
 *   p_ij = n_ij / Σ_k n_ik                 (probabilidad de transición)
 *   V_n  = V_0 · P^n                       (probabilidades de n pasos)
 *   P = | Q  R |  →  N = (I − Q)^-1        (matriz fundamental)
 *       | 0  I |      B = N · R            (probabilidades de absorción)
 *                     t = N · 1            (días esperados hasta la absorción)
 */
final class CadenaMarkov
{
    public const ESTADOS = [
        'C' => 'Confirmada',
        'H' => 'En estadía',
        'X' => 'Cancelada',
        'N' => 'No presentada',
        'F' => 'Finalizada',
    ];
    public const TRANSITORIOS = ['C', 'H'];
    public const ABSORBENTES = ['X', 'N', 'F'];

    /** Matriz de conteos vacía: n_ij = 0 para todos los pares de estados. */
    public static function conteosVacios(): array
    {
        $fila = array_fill_keys(array_keys(self::ESTADOS), 0);
        return array_fill_keys(array_keys(self::ESTADOS), $fila);
    }

    /**
     * Construye la matriz P a partir de los conteos n_ij.
     * Los estados absorbentes tienen p_ii = 1. Si un estado transitorio no tiene
     * transiciones observadas se deja que permanezca en sí mismo (fila válida).
     */
    public static function matrizDesdeConteos(array $conteos): array
    {
        $p = [];
        foreach (self::ESTADOS as $i => $_) {
            $total = array_sum($conteos[$i] ?? []);
            foreach (self::ESTADOS as $j => $__) {
                if (in_array($i, self::ABSORBENTES, true) || $total === 0) {
                    $p[$i][$j] = $i === $j ? 1.0 : 0.0;
                } else {
                    $p[$i][$j] = ($conteos[$i][$j] ?? 0) / $total;
                }
            }
        }
        return $p;
    }

    /** Verifica que cada fila de la matriz sume 1 (requisito de una matriz estocástica). */
    public static function filasSumanUno(array $p, float $tolerancia = 1e-9): bool
    {
        foreach ($p as $fila) {
            if (abs(array_sum($fila) - 1.0) > $tolerancia) {
                return false;
            }
        }
        return true;
    }

    /** Producto de matrices A·B (con claves de estado o numéricas). */
    public static function multiplicar(array $a, array $b): array
    {
        $resultado = [];
        $columnas = array_keys(reset($b));
        foreach ($a as $i => $filaA) {
            foreach ($columnas as $j) {
                $suma = 0.0;
                foreach ($filaA as $k => $valor) {
                    $suma += $valor * $b[$k][$j];
                }
                $resultado[$i][$j] = $suma;
            }
        }
        return $resultado;
    }

    /** P^n (P elevada a n) multiplicando n veces. P^0 es la identidad. */
    public static function potencia(array $p, int $n): array
    {
        $resultado = self::identidad(array_keys($p));
        for ($paso = 0; $paso < max(0, $n); $paso++) {
            $resultado = self::multiplicar($resultado, $p);
        }
        return $resultado;
    }

    /** Vector inicial V_0: 1 en el estado de partida y 0 en los demás. */
    public static function vectorInicial(string $estado): array
    {
        $v = array_fill_keys(array_keys(self::ESTADOS), 0.0);
        $v[$estado] = 1.0;
        return $v;
    }

    /** V_{k+1} = V_k · P  →  devuelve [V_0, V_1, ..., V_n]. */
    public static function evolucion(array $v0, array $p, int $n): array
    {
        $vectores = [$v0];
        $v = $v0;
        for ($paso = 1; $paso <= $n; $paso++) {
            $siguiente = [];
            foreach (array_keys($p) as $j) {
                $suma = 0.0;
                foreach ($v as $i => $valor) {
                    $suma += $valor * $p[$i][$j];
                }
                $siguiente[$j] = $suma;
            }
            $vectores[] = $v = $siguiente;
        }
        return $vectores;
    }

    /**
     * Clasificación de estados: absorbente si p_ii = 1; los demás son transitorios
     * cuando desde ellos se puede llegar a un absorbente.
     */
    public static function clasificar(array $p): array
    {
        $clase = [];
        foreach ($p as $i => $fila) {
            $clase[$i] = abs($fila[$i] - 1.0) < 1e-12 ? 'Absorbente (recurrente)' : 'Transitorio';
        }
        return $clase;
    }

    /**
     * Análisis de absorción con la forma canónica de P.
     *   Q = transiciones entre transitorios, R = de transitorios a absorbentes
     *   N = (I − Q)^-1,  B = N·R,  t = N·1
     */
    public static function absorcion(array $p): array
    {
        $t = self::TRANSITORIOS;
        $a = self::ABSORBENTES;
        $q = [];
        $r = [];
        foreach ($t as $i) {
            foreach ($t as $j) {
                $q[$i][$j] = $p[$i][$j];
            }
            foreach ($a as $j) {
                $r[$i][$j] = $p[$i][$j];
            }
        }
        // I − Q
        $iMenosQ = [];
        foreach ($t as $i) {
            foreach ($t as $j) {
                $iMenosQ[$i][$j] = ($i === $j ? 1.0 : 0.0) - $q[$i][$j];
            }
        }
        $n = self::inversa($iMenosQ);
        $b = $n === null ? null : self::multiplicar($n, $r);
        $tiempos = null;
        if ($n !== null) {
            foreach ($n as $i => $fila) {
                $tiempos[$i] = array_sum($fila);
            }
        }
        return ['Q' => $q, 'R' => $r, 'I_Q' => $iMenosQ, 'N' => $n, 'B' => $b, 't' => $tiempos];
    }

    /**
     * Inversa de una matriz cuadrada por Gauss-Jordan (sirve para cualquier tamaño).
     * Devuelve null si la matriz no tiene inversa (determinante 0).
     */
    public static function inversa(array $m): ?array
    {
        $claves = array_keys($m);
        $tam = count($claves);
        // Matriz aumentada [M | I] con índices numéricos
        $aum = [];
        foreach ($claves as $f => $i) {
            foreach ($claves as $c => $j) {
                $aum[$f][$c] = (float) $m[$i][$j];
            }
            for ($c = 0; $c < $tam; $c++) {
                $aum[$f][$tam + $c] = $f === $c ? 1.0 : 0.0;
            }
        }
        for ($col = 0; $col < $tam; $col++) {
            // Pivote: la fila con el mayor valor absoluto en la columna
            $pivote = $col;
            for ($f = $col + 1; $f < $tam; $f++) {
                if (abs($aum[$f][$col]) > abs($aum[$pivote][$col])) {
                    $pivote = $f;
                }
            }
            if (abs($aum[$pivote][$col]) < 1e-12) {
                return null;
            }
            [$aum[$col], $aum[$pivote]] = [$aum[$pivote], $aum[$col]];
            // Se divide la fila del pivote para que el pivote quede en 1
            $divisor = $aum[$col][$col];
            for ($c = 0; $c < 2 * $tam; $c++) {
                $aum[$col][$c] /= $divisor;
            }
            // Se hacen ceros en el resto de la columna
            for ($f = 0; $f < $tam; $f++) {
                if ($f !== $col && $aum[$f][$col] != 0.0) {
                    $factor = $aum[$f][$col];
                    for ($c = 0; $c < 2 * $tam; $c++) {
                        $aum[$f][$c] -= $factor * $aum[$col][$c];
                    }
                }
            }
        }
        $inv = [];
        foreach ($claves as $f => $i) {
            foreach ($claves as $c => $j) {
                $inv[$i][$j] = $aum[$f][$tam + $c];
            }
        }
        return $inv;
    }

    /** Matriz identidad con las claves indicadas. */
    public static function identidad(array $claves): array
    {
        $m = [];
        foreach ($claves as $i) {
            foreach ($claves as $j) {
                $m[$i][$j] = $i === $j ? 1.0 : 0.0;
            }
        }
        return $m;
    }
}
