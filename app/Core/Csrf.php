<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Protección contra Cross-Site Request Forgery con un token por sesión.
 */
final class Csrf
{
    public static function token(): string
    {
        $token = Sesion::obtener('_csrf');
        if (!is_string($token) || strlen($token) !== 64) {
            $token = bin2hex(random_bytes(32));
            Sesion::establecer('_csrf', $token);
        }
        return $token;
    }

    public static function campo(): string
    {
        return '<input type="hidden" name="_token" value="' . self::token() . '">';
    }

    public static function valido(?string $token): bool
    {
        $esperado = Sesion::obtener('_csrf');
        return is_string($token) && is_string($esperado) && hash_equals($esperado, $token);
    }
}
