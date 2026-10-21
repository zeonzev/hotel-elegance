<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Autenticacion;
use App\Core\ExcepcionHttp;
use App\Core\Respuesta;
use App\Core\Sesion;
use App\Core\Solicitud;
use App\Core\Validador;
use App\Core\Vista;
use App\Servicios\BitacoraServicio;

/**
 * Controlador base con utilidades comunes a todos los módulos.
 */
abstract class Controlador
{
    public function __construct(protected Solicitud $solicitud)
    {
    }

    protected function vista(string $vista, array $datos = [], ?string $layout = 'panel'): Respuesta
    {
        return Respuesta::html(Vista::renderizar($vista, $datos, $layout));
    }

    protected function redirigir(string $ruta, ?string $mensaje = null, string $tipo = 'exito'): Respuesta
    {
        if ($mensaje !== null) {
            Sesion::flash($tipo, $mensaje);
        }
        return Respuesta::redirigir($ruta);
    }

    protected function volver(?string $mensaje = null, string $tipo = 'exito'): Respuesta
    {
        $destino = $this->solicitud->referente() ?: url('/panel');
        if ($mensaje !== null) {
            Sesion::flash($tipo, $mensaje);
        }
        return Respuesta::redirigir($destino);
    }

    protected function json(mixed $datos, int $codigo = 200): Respuesta
    {
        return Respuesta::json($datos, $codigo);
    }

    protected function validar(array $reglas, array $nombres = []): array
    {
        return Validador::validar($this->solicitud->todo(), $reglas, $nombres);
    }

    protected function autorizar(string $permiso): void
    {
        if (!Autenticacion::puede($permiso)) {
            throw new ExcepcionHttp(403, 'Su rol no tiene permiso para realizar esta acción.');
        }
    }

    protected function usuario(): ?array
    {
        return Autenticacion::usuario();
    }

    protected function bitacora(string $modulo, string $accion, string $descripcion = ''): void
    {
        BitacoraServicio::registrar($modulo, $accion, $descripcion, $this->solicitud->ip());
    }

    protected function entero(string $clave, int $porDefecto = 0): int
    {
        $valor = $this->solicitud->entrada($clave);
        return is_numeric($valor) ? (int) $valor : $porDefecto;
    }
}
