<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Autenticacion;
use App\Core\BaseDatos;
use App\Core\Respuesta;
use App\Core\Sesion;
use App\Servicios\BitacoraServicio;
use App\Servicios\CuentaServicio;
use App\Servicios\ReservaServicio;

/**
 * Sprint 1 · CU-04 Iniciar sesión, cierre de sesión y cambio obligatorio de contraseña.
 */
final class AutenticacionController extends Controlador
{
    public function formulario(): Respuesta
    {
        return $this->vista('auth/login', ['titulo' => 'Iniciar sesión'], 'auth');
    }

    public function ingresar(): Respuesta
    {
        $datos = $this->validar(['usuario' => 'requerido|max:40', 'clave' => 'requerido|max:100'], ['clave' => 'contraseña']);
        $usuario = CuentaServicio::iniciarSesion((string) $datos['usuario'], (string) $datos['clave'], $this->solicitud->ip());
        Autenticacion::iniciar($usuario);
        ReservaServicio::procesarNoShowVencidos();

        if ((int) $usuario['cambiar_contrasena'] === 1) {
            return $this->redirigir('/cambiar-contrasena', 'Por seguridad, defina una contraseña personal antes de continuar.', 'aviso');
        }
        $destino = Sesion::obtener('url_deseada', '/panel');
        Sesion::olvidar('url_deseada');
        $nombre = explode(' ', (string) BaseDatos::valor('SELECT nombres FROM empleado WHERE id_empleado = ?', [(int) $usuario['id_empleado']]))[0];
        return $this->redirigir(str_starts_with($destino, '/panel') ? $destino : '/panel', "Bienvenido(a), $nombre.");
    }

    public function salir(): Respuesta
    {
        BitacoraServicio::registrar('Sesiones', 'Cierre de sesión', 'Salida del sistema', $this->solicitud->ip());
        Autenticacion::cerrar();
        Sesion::iniciar();
        return $this->redirigir('/login', 'Sesión cerrada correctamente.', 'info');
    }

    public function formularioCambio(): Respuesta
    {
        return $this->vista('auth/cambiar', ['titulo' => 'Definir contraseña', 'usuario' => $this->usuario()], 'auth');
    }

    public function cambiar(): Respuesta
    {
        CuentaServicio::cambiarContrasena(
            (int) Autenticacion::id(),
            (string) $this->solicitud->entrada('contrasena_actual', ''),
            (string) $this->solicitud->entrada('contrasena', ''),
            (string) $this->solicitud->entrada('contrasena_confirmacion', '')
        );
        return $this->redirigir('/panel', 'Contraseña actualizada. ¡Bienvenido(a) a Hotel Elegance!');
    }
}
