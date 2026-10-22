<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Autenticacion;
use App\Core\Configuracion;
use App\Core\Respuesta;
use App\Core\Sesion;
use App\Servicios\InstaladorServicio;
use Throwable;

/**
 * Instalador web opcional (/instalar). Si la base ya está instalada, sólo un
 * administrador con sesión iniciada puede reinstalarla.
 */
final class InstaladorController extends Controlador
{
    public function index(): Respuesta
    {
        $conexion = $this->conexion();
        $estado = InstaladorServicio::estado(...array_values($conexion));
        return $this->vista('instalador/index', [
            'titulo' => 'Instalador', 'conexion' => $conexion, 'estado' => $estado,
            'bloqueado' => $this->bloqueado($estado), 'resultado' => Sesion::flashActual('instalacion'),
            'habilitado' => (bool) Configuracion::obtener('instalador.habilitado', true),
        ], 'auth');
    }

    public function instalar(): Respuesta
    {
        if (!Configuracion::obtener('instalador.habilitado', true)) {
            return $this->redirigir('/instalar', 'El instalador está deshabilitado en .env (INSTALADOR_HABILITADO=false).', 'error');
        }
        $c = [
            'host' => (string) $this->solicitud->entrada('host', '127.0.0.1'),
            'puerto' => (int) $this->solicitud->entrada('puerto', 3306),
            'usuario' => (string) $this->solicitud->entrada('usuario', 'root'),
            'clave' => (string) ($this->solicitud->todo()['clave'] ?? ''),
            'nombre' => (string) $this->solicitud->entrada('nombre', 'hotel_elegance'),
        ];
        $estado = InstaladorServicio::estado(...array_values($c));
        if ($this->bloqueado($estado)) {
            return $this->redirigir('/instalar', 'La base ya está instalada. Inicie sesión como administrador para reinstalarla.', 'aviso');
        }
        if ($estado['existe'] && $estado['tablas'] > 0 && $this->solicitud->entrada('confirmar') !== 'REINSTALAR') {
            return $this->redirigir('/instalar', 'Para reemplazar la base existente escriba REINSTALAR en la confirmación.', 'aviso');
        }
        try {
            $inicio = microtime(true);
            $n = InstaladorServicio::instalar(...array_values($c));
            $env = true;
            $porDefecto = ['host' => '127.0.0.1', 'puerto' => 3306, 'usuario' => 'root', 'clave' => '', 'nombre' => 'hotel_elegance'];
            if ($c !== $porDefecto || is_file(RAIZ . '/.env')) {
                $env = InstaladorServicio::guardarEnv(...array_values($c));
            }
            Autenticacion::cerrar();
            Sesion::iniciar();
            Sesion::flash('instalacion', ['sentencias' => $n, 'segundos' => round(microtime(true) - $inicio, 1), 'env' => $env]);
            return $this->redirigir('/instalar', 'Instalación completada. Ya puede iniciar sesión.');
        } catch (Throwable $e) {
            return $this->redirigir('/instalar', 'No se pudo instalar: ' . $e->getMessage(), 'error');
        }
    }

    private function conexion(): array
    {
        return [
            'host' => (string) Configuracion::obtener('bd.host'), 'puerto' => (int) Configuracion::obtener('bd.puerto'),
            'usuario' => (string) Configuracion::obtener('bd.usuario'), 'clave' => (string) Configuracion::obtener('bd.clave'),
            'nombre' => (string) Configuracion::obtener('bd.nombre'),
        ];
    }

    /** Con datos ya instalados, sólo un administrador puede reinstalar. */
    private function bloqueado(array $estado): bool
    {
        if (!$estado['existe'] || $estado['usuarios'] === 0) {
            return false;
        }
        try {
            return Autenticacion::rol() !== 'Administrador';
        } catch (Throwable) {
            return true;
        }
    }
}
