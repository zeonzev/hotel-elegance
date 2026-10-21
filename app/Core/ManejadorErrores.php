<?php

declare(strict_types=1);

namespace App\Core;

use ErrorException;
use PDOException;
use Throwable;

/**
 * Convierte avisos de PHP en excepciones y presenta páginas de error propias
 * (403, 404, 405, 419, 500 y «base de datos no instalada»). Registra los
 * errores inesperados en storage/logs/errores.log.
 */
final class ManejadorErrores
{
    public static function registrar(): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', Configuracion::obtener('app.debug') ? '1' : '0');
        set_error_handler(static function (int $nivel, string $mensaje, string $archivo, int $linea): bool {
            if (!(error_reporting() & $nivel)) {
                return false;
            }
            throw new ErrorException($mensaje, 0, $nivel, $archivo, $linea);
        });
    }

    public static function respuesta(Throwable $e, Solicitud $solicitud): Respuesta
    {
        if ($e instanceof ExcepcionValidacion) {
            if ($solicitud->esAjax()) {
                return Respuesta::json(['ok' => false, 'mensaje' => $e->getMessage(), 'errores' => $e->errores()], 422);
            }
            Sesion::flash('_errores', $e->errores());
            Sesion::flash('_anterior', $solicitud->todo());
            Sesion::flash('error', $e->getMessage());
            return Respuesta::redirigir(self::volver($solicitud));
        }

        if ($e instanceof ExcepcionHttp) {
            $codigo = $e->codigoHttp();
            if ($codigo === 419 && !$solicitud->esAjax()) {
                Sesion::flash('aviso', 'La página expiró por seguridad. Vuelva a intentarlo.');
                return Respuesta::redirigir(self::volver($solicitud));
            }
            return self::pagina($codigo, $e->getMessage(), $solicitud);
        }

        if ($e instanceof PDOException && self::esFaltaDeBaseDatos($e)) {
            if ($solicitud->esAjax()) {
                return Respuesta::json(['ok' => false, 'mensaje' => 'La base de datos no está disponible.'], 503);
            }
            return Respuesta::html(Vista::renderizar('errores/bd', [
                'titulo' => 'Configuración pendiente',
                'detalle' => $e->getMessage(),
                'codigoBd' => self::codigoMysql($e),
            ], 'error'), 503);
        }

        self::registrarEnLog($e);
        return self::pagina(500, Configuracion::obtener('app.debug') ? $e->getMessage() . ' — ' . $e->getFile() . ':' . $e->getLine() : '', $solicitud);
    }

    private static function pagina(int $codigo, string $mensaje, Solicitud $solicitud): Respuesta
    {
        if ($solicitud->esAjax()) {
            return Respuesta::json(['ok' => false, 'mensaje' => $mensaje ?: self::textoCodigo($codigo)], $codigo);
        }
        if ($codigo === 501) {
            return self::enDesarrollo($solicitud);
        }
        try {
            $html = Vista::renderizar('errores/pagina', ['codigo' => $codigo, 'mensaje' => $mensaje, 'titulo' => self::textoCodigo($codigo)], 'error');
        } catch (Throwable) {
            $html = '<h1>' . $codigo . '</h1><p>' . htmlspecialchars(self::textoCodigo($codigo)) . '</p>';
        }
        return Respuesta::html($html, $codigo);
    }

    /** Avance del proyecto: las secciones de sprints aún no implementados muestran su estado. */
    private static function enDesarrollo(Solicitud $solicitud): Respuesta
    {
        $conSesion = false;
        try {
            $conSesion = Autenticacion::verificado();
        } catch (\Throwable) {
        }
        $html = Vista::renderizar('errores/desarrollo', ['titulo' => 'Módulo en desarrollo', 'ruta' => $solicitud->ruta(), 'conSesion' => $conSesion], $conSesion ? 'panel' : 'error');
        return Respuesta::html($html, 501);
    }

    public static function textoCodigo(int $codigo): string
    {
        return match ($codigo) {
            403 => 'Acceso restringido',
            404 => 'Página no encontrada',
            405 => 'Método no permitido',
            501 => 'Módulo en desarrollo',
            419 => 'La sesión expiró',
            503 => 'Servicio no disponible',
            default => 'Ocurrió un error inesperado',
        };
    }

    public static function esFaltaDeBaseDatos(PDOException $e): bool
    {
        // 1049: base inexistente · 1146: tabla inexistente · 2002: servidor apagado · 1045: credenciales
        return in_array(self::codigoMysql($e), [1049, 1146, 2002, 1045, 2006], true);
    }

    public static function codigoMysql(PDOException $e): int
    {
        if (isset($e->errorInfo[1]) && is_int($e->errorInfo[1])) {
            return $e->errorInfo[1];
        }
        if (preg_match('/\[(\d{4})\]/', $e->getMessage(), $m)) {
            return (int) $m[1];
        }
        return (int) $e->getCode();
    }

    public static function registrarEnLog(Throwable $e): void
    {
        $directorio = RAIZ . '/storage/logs';
        if (!is_dir($directorio)) {
            @mkdir($directorio, 0775, true);
        }
        $linea = sprintf("[%s] %s: %s en %s:%d\n%s\n\n", date('Y-m-d H:i:s'), get_class($e), $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString());
        @file_put_contents($directorio . '/errores.log', $linea, FILE_APPEND);
    }

    private static function volver(Solicitud $solicitud): string
    {
        $anterior = $solicitud->referente();
        $hostActual = strtok((string) ($_SERVER['HTTP_HOST'] ?? ''), ':');
        if ($anterior && parse_url($anterior, PHP_URL_HOST) === $hostActual) {
            return $anterior;
        }
        return $solicitud->ruta();
    }
}
