<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Acceso a la configuración con notación de puntos: Configuracion::obtener('bd.host').
 */
final class Configuracion
{
    private static ?array $datos = null;

    public static function obtener(string $clave, mixed $porDefecto = null): mixed
    {
        if (self::$datos === null) {
            self::$datos = require RAIZ . '/config/config.php';
        }
        $valor = self::$datos;
        foreach (explode('.', $clave) as $parte) {
            if (!is_array($valor) || !array_key_exists($parte, $valor)) {
                return $porDefecto;
            }
            $valor = $valor[$parte];
        }
        return $valor;
    }

    /** Permite sobrescribir valores en tiempo de ejecución (pruebas, instalador). */
    public static function establecer(string $clave, mixed $valor): void
    {
        self::obtener($clave);
        $ref = &self::$datos;
        foreach (explode('.', $clave) as $parte) {
            $ref = &$ref[$parte];
        }
        $ref = $valor;
    }
}
