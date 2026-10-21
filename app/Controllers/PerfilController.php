<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Autenticacion;
use App\Core\BaseDatos;
use App\Core\ExcepcionValidacion;
use App\Core\Respuesta;
use App\Servicios\CuentaServicio;

/**
 * Perfil del usuario: datos de contacto y cambio de contraseña.
 */
final class PerfilController extends Controlador
{
    public function index(): Respuesta
    {
        $u = $this->usuario();
        $actividad = BaseDatos::filas('SELECT * FROM bitacora WHERE id_usuario = ? ORDER BY fecha DESC LIMIT 8', [$u['id_usuario']]);
        $estadisticas = BaseDatos::fila(
            "SELECT (SELECT COUNT(*) FROM reserva WHERE id_usuario = :u1) AS reservas,
                    (SELECT COUNT(*) FROM estadia WHERE id_usuario_checkin = :u2) AS checkins,
                    (SELECT COUNT(*) FROM estadia WHERE id_usuario_checkout = :u3) AS checkouts",
            ['u1' => $u['id_usuario'], 'u2' => $u['id_usuario'], 'u3' => $u['id_usuario']]
        );
        return $this->vista('perfil/index', ['titulo' => 'Mi perfil', 'u' => $u, 'actividad' => $actividad, 'estadisticas' => $estadisticas]);
    }

    public function actualizar(): Respuesta
    {
        $u = $this->usuario();
        $d = $this->validar(['telefono' => 'opcional|telefono', 'correo' => 'opcional|correo|max:120']);
        if ($d['correo'] && BaseDatos::valor('SELECT 1 FROM empleado WHERE correo = ? AND id_empleado <> ?', [$d['correo'], $u['id_empleado']])) {
            throw ExcepcionValidacion::campo('correo', 'Ese correo ya pertenece a otro empleado.');
        }
        BaseDatos::actualizar('empleado', ['telefono' => $d['telefono'], 'correo' => $d['correo'] ? mb_strtolower($d['correo']) : null], 'id_empleado = :id', ['id' => $u['id_empleado']]);
        $this->bitacora('Sesiones', 'Actualizar perfil', 'Datos de contacto actualizados');
        return $this->redirigir('/panel/perfil', 'Datos de contacto actualizados.');
    }

    public function contrasena(): Respuesta
    {
        CuentaServicio::cambiarContrasena(
            (int) Autenticacion::id(),
            (string) $this->solicitud->entrada('contrasena_actual', ''),
            (string) $this->solicitud->entrada('contrasena', ''),
            (string) $this->solicitud->entrada('contrasena_confirmacion', '')
        );
        return $this->redirigir('/panel/perfil', 'Su contraseña fue actualizada.');
    }
}
