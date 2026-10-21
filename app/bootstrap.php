<?php
/**
 * Arranque de la aplicación: autocarga PSR-4 propia, zona horaria y helpers.
 * No requiere Composer: el espacio de nombres App\ se resuelve contra app/.
 */

declare(strict_types=1);

if (!defined('RAIZ')) {
    define('RAIZ', dirname(__DIR__));
}

if (PHP_VERSION_ID < 80000) {
    http_response_code(500);
    exit('Hotel Elegance requiere PHP 8.0 o superior. Versión actual: ' . PHP_VERSION);
}

spl_autoload_register(static function (string $clase): void {
    $prefijo = 'App\\';
    if (strncmp($clase, $prefijo, strlen($prefijo)) !== 0) {
        return;
    }
    $relativa = substr($clase, strlen($prefijo));
    $archivo = RAIZ . '/app/' . str_replace('\\', '/', $relativa) . '.php';
    if (is_file($archivo)) {
        require $archivo;
    }
});

date_default_timezone_set('America/La_Paz');
mb_internal_encoding('UTF-8');

require RAIZ . '/app/helpers.php';
