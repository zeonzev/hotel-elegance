<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Csrf;
use App\Core\ExcepcionHttp;
use App\Core\Respuesta;
use App\Core\Solicitud;

/** Todas las peticiones POST deben traer el token CSRF (campo _token o encabezado X-CSRF-Token). */
final class VerificarCsrf implements Middleware
{
    public function manejar(Solicitud $solicitud, callable $siguiente, ?string $argumento = null): Respuesta
    {
        $token = $solicitud->entrada('_token') ?? $solicitud->encabezado('X-CSRF-Token');
        if (!Csrf::valido(is_string($token) ? $token : null)) {
            throw new ExcepcionHttp(419, 'El formulario expiró. Recargue la página e inténtelo de nuevo.');
        }
        return $siguiente($solicitud);
    }
}
