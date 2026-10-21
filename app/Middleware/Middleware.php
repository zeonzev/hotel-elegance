<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Respuesta;
use App\Core\Solicitud;

interface Middleware
{
    public function manejar(Solicitud $solicitud, callable $siguiente, ?string $argumento = null): Respuesta;
}
