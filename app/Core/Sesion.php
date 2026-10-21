<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Sesión segura: cookie HttpOnly + SameSite, modo estricto, regeneración del
 * identificador al iniciar sesión y mensajes flash de un solo uso.
 */
final class Sesion
{
    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE || PHP_SAPI === 'cli') {
            if (PHP_SAPI === 'cli' && !isset($_SESSION)) {
                $_SESSION = [];
            }
            return;
        }
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_name((string) Configuracion::obtener('sesion.nombre', 'HE_SESION'));
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => (Solicitud::base() ?: '') . '/',
            'secure'   => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
        self::moverFlash();
    }

    private static function moverFlash(): void
    {
        $_SESSION['_flash_actual'] = $_SESSION['_flash_siguiente'] ?? [];
        $_SESSION['_flash_siguiente'] = [];
    }

    public static function obtener(string $clave, mixed $porDefecto = null): mixed
    {
        return $_SESSION[$clave] ?? $porDefecto;
    }

    public static function establecer(string $clave, mixed $valor): void
    {
        $_SESSION[$clave] = $valor;
    }

    public static function olvidar(string $clave): void
    {
        unset($_SESSION[$clave]);
    }

    /** Guarda un valor disponible sólo en la siguiente petición. */
    public static function flash(string $clave, mixed $valor): void
    {
        $_SESSION['_flash_siguiente'][$clave] = $valor;
    }

    public static function flashActual(string $clave, mixed $porDefecto = null): mixed
    {
        return $_SESSION['_flash_actual'][$clave] ?? $porDefecto;
    }

    /** Mensajes para las notificaciones toast: exito, error, aviso, info. */
    public static function mensajes(): array
    {
        $mensajes = [];
        foreach (['exito', 'error', 'aviso', 'info'] as $tipo) {
            if ($m = self::flashActual($tipo)) {
                $mensajes[] = ['tipo' => $tipo, 'texto' => $m];
            }
        }
        return $mensajes;
    }

    public static function regenerar(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function destruir(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
            session_destroy();
        }
    }
}
