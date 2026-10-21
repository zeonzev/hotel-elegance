<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Autenticacion;
use App\Core\ExcepcionHttp;
use App\Core\Respuesta;
use App\Core\Solicitud;

/** Control de acceso por rol en el servidor: «permiso:reservas.gestionar». */
final class Permiso implements Middleware
{
    public function manejar(Solicitud $solicitud, callable $siguiente, ?string $argumento = null): Respuesta
    {
        foreach (explode(',', (string) $argumento) as $permiso) {
            if (Autenticacion::puede(trim($permiso))) {
                return $siguiente($solicitud);
            }
        }
        throw new ExcepcionHttp(403, 'Su rol (' . (Autenticacion::rol() ?? 'sin rol') . ') no tiene acceso a esta sección.');
    }
}
