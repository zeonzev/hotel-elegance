<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Lector mínimo de archivos .env (CLAVE=valor). Si no existe .env se usan
 * los valores por defecto de config/config.php, pensados para XAMPP.
 */
final class Entorno
{
    private static array $valores = [];
    private static bool $cargado = false;

    public static function cargar(string $archivo): void
    {
        self::$cargado = true;
        if (!is_file($archivo)) {
            return;
        }
        foreach (file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
            $linea = trim($linea);
            if ($linea === '' || $linea[0] === '#' || !str_contains($linea, '=')) {
                continue;
            }
            [$clave, $valor] = array_map('trim', explode('=', $linea, 2));
            if (strlen($valor) >= 2 && ($valor[0] === '"' || $valor[0] === "'") && $valor[-1] === $valor[0]) {
                $valor = substr($valor, 1, -1);
            }
            self::$valores[$clave] = $valor;
        }
    }

    public static function obtener(string $clave, mixed $porDefecto = null): mixed
    {
        if (!self::$cargado) {
            self::cargar(RAIZ . '/.env');
        }
        $valor = self::$valores[$clave] ?? getenv($clave);
        if ($valor === false || $valor === null) {
            return $porDefecto;
        }
        return match (strtolower((string) $valor)) {
            'true' => true,
            'false' => false,
            'null' => null,
            default => $valor,
        };
    }
}
