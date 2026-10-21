<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Autenticacion;
use App\Core\Respuesta;
use App\Core\Solicitud;

/** Las pantallas de acceso sólo se muestran a quien no inició sesión. */
final class Invitado implements Middleware
{
    public function manejar(Solicitud $solicitud, callable $siguiente, ?string $argumento = null): Respuesta
    {
        if (Autenticacion::verificado()) {
            return Respuesta::redirigir('/panel');
        }
        return $siguiente($solicitud);
    }
}
