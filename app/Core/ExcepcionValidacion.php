<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Errores de validación por campo. El manejador de errores redirige al
 * formulario anterior conservando lo escrito, o responde 422 en peticiones AJAX.
 */
class ExcepcionValidacion extends RuntimeException
{
    /** Registro ya existente relacionado con el error (p. ej. huésped con el mismo documento). */
    public ?array $existente = null;

    public function __construct(private array $errores, string $mensaje = 'Revise los datos marcados en el formulario.')
    {
        parent::__construct($mensaje, 422);
    }

    public function errores(): array
    {
        return $this->errores;
    }

    /** Atajo para lanzar un error de negocio asociado a un campo. */
    public static function campo(string $campo, string $mensaje): self
    {
        return new self([$campo => $mensaje], $mensaje);
    }
}
