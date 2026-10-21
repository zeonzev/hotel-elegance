<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Autenticacion;
use App\Core\Configuracion;
use App\Core\Respuesta;
use App\Core\Sesion;
use App\Core\Solicitud;

/**
 * Exige sesión iniciada (CU-04), cierra sesiones inactivas y obliga a cambiar
 * la contraseña por defecto en el primer ingreso (CU-04, flujo 3a).
 */
final class Autenticado implements Middleware
{
    public function manejar(Solicitud $solicitud, callable $siguiente, ?string $argumento = null): Respuesta
    {
        $limite = (int) Configuracion::obtener('sesion.inactividad_minutos', 120) * 60;
        $ultima = (int) Sesion::obtener('ultima_actividad', time());
        if (Sesion::obtener('id_usuario') && time() - $ultima > $limite) {
            Autenticacion::cerrar();
            \App\Core\Sesion::iniciar();
            Sesion::flash('aviso', 'Su sesión se cerró por inactividad. Ingrese nuevamente.');
            return $this->rechazar($solicitud);
        }

        $usuario = Autenticacion::usuario();
        if ($usuario === null) {
            Sesion::flash('info', 'Inicie sesión para continuar.');
            if ($solicitud->metodo() === 'GET' && !$solicitud->esAjax()) {
                Sesion::establecer('url_deseada', $solicitud->ruta() . (empty($_GET) ? '' : '?' . http_build_query($_GET)));
            }
            return $this->rechazar($solicitud);
        }
        Sesion::establecer('ultima_actividad', time());

        if ((int) $usuario['cambiar_contrasena'] === 1 && !in_array($solicitud->ruta(), ['/cambiar-contrasena', '/logout'], true)) {
            return Respuesta::redirigir('/cambiar-contrasena');
        }
        return $siguiente($solicitud);
    }

    private function rechazar(Solicitud $solicitud): Respuesta
    {
        if ($solicitud->esAjax()) {
            return Respuesta::json(['ok' => false, 'mensaje' => 'Sesión no iniciada.'], 401);
        }
        return Respuesta::redirigir('/login');
    }
}
