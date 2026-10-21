<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Error HTTP controlado (403, 404, 419, 422...). Se muestra con una página propia.
 */
class ExcepcionHttp extends RuntimeException
{
    public function __construct(private int $codigoHttp, string $mensaje = '')
    {
        parent::__construct($mensaje, $codigoHttp);
    }

    public function codigoHttp(): int
    {
        return $this->codigoHttp;
    }
}
