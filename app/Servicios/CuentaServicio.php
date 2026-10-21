<?php

declare(strict_types=1);

namespace App\Servicios;

use App\Core\Autenticacion;
use App\Core\BaseDatos;
use App\Core\Configuracion;
use App\Core\ExcepcionValidacion;
use App\Core\Validador;

/**
 * Sprint 1 · Sesiones: CU-02 Crear cuenta de usuario, CU-03 Asignar rol,
 * CU-04 Iniciar sesión (con bloqueo tras 5 intentos) y cambio de contraseña.
 */
final class CuentaServicio
{
    /** CU-04 Iniciar sesión. Devuelve el usuario o lanza un error con mensaje genérico. */
    public static function iniciarSesion(string $nombreUsuario, string $clave, string $ip): array
    {
        $generico = 'Usuario o contraseña incorrectos.';
        $usuario = BaseDatos::fila(
            'SELECT u.*, r.nombre AS rol FROM usuario u JOIN rol r ON r.id_rol = u.id_rol WHERE u.nombre_usuario = ?',
            [mb_strtolower(trim($nombreUsuario))]
        );
        if (!$usuario) {
            BitacoraServicio::registrar('Sesiones', 'Intento fallido', 'Usuario inexistente: ' . mb_substr($nombreUsuario, 0, 40), $ip, null);
            password_verify($clave, '$2y$10$abcdefghijklmnopqrstuuJ0b2C5n8c5mZ0s8Q4n3m2l1k0j9i8h7'); // tiempo constante
            throw ExcepcionValidacion::campo('usuario', $generico);
        }
        if ($usuario['estado'] === 'BLOQUEADO') {
            BitacoraServicio::registrar('Sesiones', 'Acceso a cuenta bloqueada', 'Intento de ingreso a una cuenta bloqueada', $ip, (int) $usuario['id_usuario']);
            throw ExcepcionValidacion::campo('usuario', 'Su cuenta está bloqueada por intentos fallidos. Solicite al administrador que la desbloquee.');
        }
        if (!password_verify($clave, $usuario['contrasena_hash'])) {
            $intentos = (int) $usuario['intentos_fallidos'] + 1;
            $maximo = (int) Configuracion::obtener('sesion.intentos_maximos', 5);
            $bloquear = $intentos >= $maximo;
            BaseDatos::actualizar('usuario', [
                'intentos_fallidos' => $intentos,
                'estado' => $bloquear ? 'BLOQUEADO' : $usuario['estado'],
            ], 'id_usuario = :id', ['id' => $usuario['id_usuario']]);
            BitacoraServicio::registrar('Sesiones', $bloquear ? 'Cuenta bloqueada' : 'Intento fallido', "Contraseña incorrecta ($intentos de $maximo)", $ip, (int) $usuario['id_usuario']);
            if ($bloquear) {
                throw ExcepcionValidacion::campo('usuario', "Su cuenta fue bloqueada tras $maximo intentos fallidos. Contacte al administrador.");
            }
            $restantes = $maximo - $intentos;
            throw ExcepcionValidacion::campo('usuario', $generico . ($restantes <= 2 ? " Le quedan $restantes intento(s) antes del bloqueo." : ''));
        }
        if ($usuario['estado'] === 'INACTIVO') {
            throw ExcepcionValidacion::campo('usuario', 'Su cuenta está deshabilitada. Consulte con el administrador.');
        }
        $datos = ['intentos_fallidos' => 0, 'ultimo_acceso' => date('Y-m-d H:i:s')];
        if (password_needs_rehash($usuario['contrasena_hash'], PASSWORD_DEFAULT)) {
            $datos['contrasena_hash'] = password_hash($clave, PASSWORD_DEFAULT);
        }
        BaseDatos::actualizar('usuario', $datos, 'id_usuario = :id', ['id' => $usuario['id_usuario']]);
        BitacoraServicio::registrar('Sesiones', 'Inicio de sesión', 'Ingreso al sistema como ' . $usuario['rol'], $ip, (int) $usuario['id_usuario']);
        return $usuario;
    }

    /** CU-02 paso 2: nombre de usuario a partir de los nombres (flujo 2a: agrega un número si existe). */
    public static function proponerNombreUsuario(string $nombres, string $apellidos): string
    {
        $limpiar = static function (string $t): string {
            $t = strtr(mb_strtolower($t), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n', 'ü' => 'u']);
            return preg_replace('/[^a-z]/', '', $t) ?? '';
        };
        $base = mb_substr($limpiar(explode(' ', trim($nombres))[0] ?? ''), 0, 1) . $limpiar(explode(' ', trim($apellidos))[0] ?? '');
        $base = $base !== '' ? mb_substr($base, 0, 30) : 'usuario';
        $candidato = $base;
        $n = 1;
        while (BaseDatos::valor('SELECT 1 FROM usuario WHERE nombre_usuario = ?', [$candidato])) {
            $n++;
            $candidato = $base . $n;
        }
        return $candidato;
    }

    /** Contraseña por defecto que cumple la política (mayúscula, minúscula, número y símbolo). */
    public static function generarClave(): string
    {
        return 'Hotel' . random_int(1000, 9999) . '*';
    }

    /** CU-02 Crear cuenta de usuario «include» CU-03 Asignar rol. */
    public static function crearCuenta(int $idEmpleado, int $idRol): array
    {
        $empleado = BaseDatos::fila('SELECT * FROM empleado WHERE id_empleado = ?', [$idEmpleado]);
        if (!$empleado) {
            throw ExcepcionValidacion::campo('id_empleado', 'Seleccione un empleado registrado.');
        }
        if ($empleado['estado'] !== 'ACTIVO') {
            throw ExcepcionValidacion::campo('id_empleado', 'El empleado está inactivo.');
        }
        if (BaseDatos::valor('SELECT 1 FROM usuario WHERE id_empleado = ?', [$idEmpleado])) {
            throw ExcepcionValidacion::campo('id_empleado', 'El empleado ya tiene una cuenta de usuario.');
        }
        self::validarRol($idRol);
        $nombreUsuario = self::proponerNombreUsuario($empleado['nombres'], $empleado['apellidos']);
        $clave = self::generarClave();
        $id = BaseDatos::insertar('usuario', [
            'id_empleado'        => $idEmpleado,
            'id_rol'             => $idRol,
            'nombre_usuario'     => $nombreUsuario,
            'contrasena_hash'    => password_hash($clave, PASSWORD_DEFAULT),
            'estado'             => 'ACTIVO',
            'cambiar_contrasena' => 1,
        ]);
        $rol = BaseDatos::valor('SELECT nombre FROM rol WHERE id_rol = ?', [$idRol]);
        BitacoraServicio::registrar('Sesiones', 'Crear cuenta', "Cuenta @$nombreUsuario creada para {$empleado['nombres']} {$empleado['apellidos']} con rol $rol");
        return ['id_usuario' => $id, 'nombre_usuario' => $nombreUsuario, 'clave' => $clave, 'empleado' => $empleado['nombres'] . ' ' . $empleado['apellidos'], 'rol' => $rol];
    }

    /** CU-03 Asignar rol. Flujo 3a: un administrador no puede quitarse su propio rol. */
    public static function asignarRol(int $idUsuario, int $idRol): void
    {
        self::validarRol($idRol);
        $usuario = BaseDatos::fila('SELECT u.*, r.nombre AS rol FROM usuario u JOIN rol r ON r.id_rol = u.id_rol WHERE u.id_usuario = ?', [$idUsuario]);
        if (!$usuario) {
            throw ExcepcionValidacion::campo('id_rol', 'La cuenta no existe.');
        }
        $nuevoRol = BaseDatos::valor('SELECT nombre FROM rol WHERE id_rol = ?', [$idRol]);
        if ($idUsuario === Autenticacion::id() && $usuario['rol'] === 'Administrador' && $nuevoRol !== 'Administrador') {
            throw ExcepcionValidacion::campo('id_rol', 'No puede quitarse su propio rol de Administrador.');
        }
        if ((int) $usuario['id_rol'] === $idRol) {
            return;
        }
        BaseDatos::actualizar('usuario', ['id_rol' => $idRol], 'id_usuario = :id', ['id' => $idUsuario]);
        BitacoraServicio::registrar('Sesiones', 'Asignar rol', "@{$usuario['nombre_usuario']}: {$usuario['rol']} → $nuevoRol");
    }

    private static function validarRol(int $idRol): void
    {
        if (!BaseDatos::valor('SELECT 1 FROM rol WHERE id_rol = ? AND estado = 1', [$idRol])) {
            throw ExcepcionValidacion::campo('id_rol', 'Seleccione un rol activo.');
        }
    }

    public static function desbloquear(int $idUsuario): void
    {
        BaseDatos::actualizar('usuario', ['estado' => 'ACTIVO', 'intentos_fallidos' => 0], 'id_usuario = :id', ['id' => $idUsuario]);
        BitacoraServicio::registrar('Sesiones', 'Desbloquear cuenta', 'Cuenta #' . $idUsuario . ' desbloqueada');
    }

    public static function restablecer(int $idUsuario): array
    {
        $usuario = BaseDatos::fila('SELECT nombre_usuario FROM usuario WHERE id_usuario = ?', [$idUsuario]);
        if (!$usuario) {
            throw ExcepcionValidacion::campo('usuario', 'La cuenta no existe.');
        }
        $clave = self::generarClave();
        BaseDatos::actualizar('usuario', [
            'contrasena_hash' => password_hash($clave, PASSWORD_DEFAULT),
            'cambiar_contrasena' => 1, 'intentos_fallidos' => 0, 'estado' => 'ACTIVO',
        ], 'id_usuario = :id', ['id' => $idUsuario]);
        BitacoraServicio::registrar('Sesiones', 'Restablecer contraseña', "Contraseña de @{$usuario['nombre_usuario']} restablecida");
        return ['nombre_usuario' => $usuario['nombre_usuario'], 'clave' => $clave];
    }

    public static function cambiarEstado(int $idUsuario, string $estado): void
    {
        if ($idUsuario === Autenticacion::id()) {
            throw ExcepcionValidacion::campo('estado', 'No puede deshabilitar su propia cuenta.');
        }
        if (!in_array($estado, ['ACTIVO', 'INACTIVO'], true)) {
            throw ExcepcionValidacion::campo('estado', 'Estado no válido.');
        }
        BaseDatos::actualizar('usuario', ['estado' => $estado, 'intentos_fallidos' => 0], 'id_usuario = :id', ['id' => $idUsuario]);
        BitacoraServicio::registrar('Sesiones', $estado === 'ACTIVO' ? 'Habilitar cuenta' : 'Deshabilitar cuenta', 'Cuenta #' . $idUsuario);
    }

    /** Cambio de contraseña (primer ingreso o desde el perfil). */
    public static function cambiarContrasena(int $idUsuario, string $actual, string $nueva, string $confirmacion): void
    {
        $hash = (string) BaseDatos::valor('SELECT contrasena_hash FROM usuario WHERE id_usuario = ?', [$idUsuario]);
        if (!password_verify($actual, $hash)) {
            throw ExcepcionValidacion::campo('contrasena_actual', 'La contraseña actual no es correcta.');
        }
        if ($error = Validador::contrasenaSegura($nueva)) {
            throw ExcepcionValidacion::campo('contrasena', $error);
        }
        if ($nueva !== $confirmacion) {
            throw ExcepcionValidacion::campo('contrasena_confirmacion', 'La confirmación no coincide con la nueva contraseña.');
        }
        if (password_verify($nueva, $hash)) {
            throw ExcepcionValidacion::campo('contrasena', 'La nueva contraseña debe ser distinta de la actual.');
        }
        BaseDatos::actualizar('usuario', ['contrasena_hash' => password_hash($nueva, PASSWORD_DEFAULT), 'cambiar_contrasena' => 0], 'id_usuario = :id', ['id' => $idUsuario]);
        BitacoraServicio::registrar('Sesiones', 'Cambiar contraseña', 'El usuario actualizó su contraseña', null, $idUsuario);
    }
}
