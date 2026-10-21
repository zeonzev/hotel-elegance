<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Usuario autenticado de la petición actual y control de permisos por rol.
 * Los permisos se declaran en config/permisos.php (permiso => roles).
 */
final class Autenticacion
{
    private static ?array $usuario = null;
    private static bool $cargado = false;
    private static ?array $permisos = null;

    public static function usuario(): ?array
    {
        if (!self::$cargado) {
            self::$cargado = true;
            $id = Sesion::obtener('id_usuario');
            if ($id) {
                $fila = BaseDatos::fila(
                    "SELECT u.id_usuario, u.id_empleado, u.nombre_usuario, u.estado, u.cambiar_contrasena, u.ultimo_acceso,
                            r.id_rol, r.nombre AS rol, e.nombres, e.apellidos, e.correo, e.telefono, c.nombre AS cargo
                       FROM usuario u
                       JOIN rol r ON r.id_rol = u.id_rol
                       JOIN empleado e ON e.id_empleado = u.id_empleado
                       JOIN cargo c ON c.id_cargo = e.id_cargo
                      WHERE u.id_usuario = ?",
                    [(int) $id]
                );
                if ($fila && $fila['estado'] === 'ACTIVO') {
                    self::$usuario = $fila;
                } else {
                    Sesion::olvidar('id_usuario');
                }
            }
        }
        return self::$usuario;
    }

    public static function id(): ?int
    {
        $u = self::usuario();
        return $u ? (int) $u['id_usuario'] : null;
    }

    public static function verificado(): bool
    {
        return self::usuario() !== null;
    }

    public static function rol(): ?string
    {
        return self::usuario()['rol'] ?? null;
    }

    public static function iniciar(array $usuario): void
    {
        Sesion::regenerar();
        Sesion::establecer('id_usuario', (int) $usuario['id_usuario']);
        Sesion::establecer('ultima_actividad', time());
        self::$cargado = false;
        self::$usuario = null;
    }

    public static function cerrar(): void
    {
        Sesion::destruir();
        self::$cargado = true;
        self::$usuario = null;
    }

    /** Para pruebas: fija el usuario actual sin pasar por la sesión. */
    public static function simular(?array $usuario): void
    {
        self::$cargado = true;
        self::$usuario = $usuario;
    }

    public static function permisos(): array
    {
        if (self::$permisos === null) {
            self::$permisos = require RAIZ . '/config/permisos.php';
        }
        return self::$permisos;
    }

    public static function puede(string $permiso, ?string $rol = null): bool
    {
        $rol ??= self::rol();
        if ($rol === null) {
            return false;
        }
        $roles = self::permisos()[$permiso] ?? null;
        if ($roles === null) {
            return false;
        }
        return in_array($rol, $roles, true);
    }
}
