<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

/**
 * Front controller: inicia la sesión, carga las rutas y despacha la petición.
 */
final class Aplicacion
{
    public static function ejecutar(): void
    {
        ManejadorErrores::registrar();
        $solicitud = Solicitud::capturar();
        try {
            Sesion::iniciar();
            $respuesta = self::enrutador()->despachar($solicitud);
        } catch (Throwable $e) {
            try {
                $respuesta = ManejadorErrores::respuesta($e, $solicitud);
            } catch (Throwable $fatal) {
                ManejadorErrores::registrarEnLog($fatal);
                $respuesta = Respuesta::html('<h1>Error interno</h1><p>Revise storage/logs/errores.log</p>', 500);
            }
        }
        $respuesta->enviar();
    }

    public static function enrutador(): Enrutador
    {
        $enrutador = new Enrutador();
        (require RAIZ . '/config/rutas.php')($enrutador);
        return $enrutador;
    }
}
