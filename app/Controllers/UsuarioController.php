<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseDatos;
use App\Core\Respuesta;
use App\Core\Sesion;
use App\Repositorios\EmpleadoRepositorio;
use App\Servicios\CuentaServicio;

/**
 * Sprint 1 · CU-02 Crear cuenta de usuario y CU-03 Asignar rol.
 */
final class UsuarioController extends Controlador
{
    public function index(): Respuesta
    {
        $usuarios = BaseDatos::filas(
            "SELECT u.*, r.nombre AS rol, e.nombres, e.apellidos, e.correo, c.nombre AS cargo
               FROM usuario u
               JOIN rol r ON r.id_rol = u.id_rol
               JOIN empleado e ON e.id_empleado = u.id_empleado
               JOIN cargo c ON c.id_cargo = e.id_cargo
              ORDER BY FIELD(u.estado, 'BLOQUEADO', 'ACTIVO', 'INACTIVO'), r.id_rol, e.apellidos"
        );
        $roles = BaseDatos::filas(
            'SELECT r.*, (SELECT COUNT(*) FROM usuario u WHERE u.id_rol = r.id_rol) AS cuentas FROM rol r WHERE r.estado = 1 ORDER BY r.id_rol'
        );
        return $this->vista('usuarios/index', [
            'titulo' => 'Usuarios y roles', 'usuarios' => $usuarios, 'roles' => $roles,
            'sinCuenta' => EmpleadoRepositorio::sinCuenta(), 'credenciales' => Sesion::flashActual('credenciales'),
            'permisos' => \App\Core\Autenticacion::permisos(),
        ]);
    }

    public function crearCuenta(): Respuesta
    {
        $d = $this->validar(['id_empleado' => 'requerido|entero', 'id_rol' => 'requerido|entero'], ['id_empleado' => 'empleado']);
        $cuenta = CuentaServicio::crearCuenta((int) $d['id_empleado'], (int) $d['id_rol']);
        Sesion::flash('credenciales', $cuenta + ['accion' => 'creada']);
        return $this->redirigir('/panel/usuarios', "Cuenta @{$cuenta['nombre_usuario']} creada con rol {$cuenta['rol']}.");
    }

    public function asignarRol(string $id): Respuesta
    {
        $d = $this->validar(['id_rol' => 'requerido|entero']);
        CuentaServicio::asignarRol((int) $id, (int) $d['id_rol']);
        return $this->volver('Rol actualizado. Los permisos se aplican desde la siguiente acción del usuario.');
    }

    public function desbloquear(string $id): Respuesta
    {
        CuentaServicio::desbloquear((int) $id);
        return $this->volver('Cuenta desbloqueada: el contador de intentos volvió a cero.');
    }

    public function restablecer(string $id): Respuesta
    {
        $r = CuentaServicio::restablecer((int) $id);
        Sesion::flash('credenciales', $r + ['accion' => 'restablecida']);
        return $this->volver("Contraseña de @{$r['nombre_usuario']} restablecida.");
    }

    public function cambiarEstado(string $id): Respuesta
    {
        $actual = (string) BaseDatos::valor('SELECT estado FROM usuario WHERE id_usuario = ?', [(int) $id]);
        CuentaServicio::cambiarEstado((int) $id, $actual === 'INACTIVO' ? 'ACTIVO' : 'INACTIVO');
        return $this->volver($actual === 'INACTIVO' ? 'Cuenta habilitada.' : 'Cuenta deshabilitada.');
    }
}
