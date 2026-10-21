<?php
/**
 * Funciones auxiliares globales usadas por controladores y vistas.
 */

declare(strict_types=1);

use App\Core\Autenticacion;
use App\Core\Csrf;
use App\Core\Sesion;
use App\Core\Solicitud;

/** Escapa texto para HTML (protección XSS). */
function e(mixed $valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** URL absoluta a una ruta de la aplicación, con o sin mod_rewrite. */
function url(string $ruta = '/', array $parametros = []): string
{
    $ruta = '/' . ltrim($ruta, '/');
    $base = Solicitud::base();
    $prefijo = Solicitud::conReescritura() || $ruta === '/' ? $base : $base . '/index.php';
    $resultado = rtrim($prefijo . ($ruta === '/' ? '/' : $ruta), '') ?: '/';
    if ($parametros) {
        $resultado .= '?' . http_build_query($parametros);
    }
    return $resultado;
}

/** URL a un archivo estático de public/assets, con versión para evitar caché. */
function asset(string $ruta): string
{
    $ruta = ltrim($ruta, '/');
    $archivo = RAIZ . '/public/assets/' . $ruta;
    $version = is_file($archivo) ? substr((string) filemtime($archivo), -6) : '1';
    return Solicitud::base() . '/public/assets/' . $ruta . '?v=' . $version;
}

function vendor(string $ruta): string
{
    return Solicitud::base() . '/public/vendor/' . ltrim($ruta, '/');
}

function csrf_campo(): string
{
    return Csrf::campo();
}

function csrf_token(): string
{
    return Csrf::token();
}

/** Valor anterior de un campo tras un error de validación. */
function anterior(string $campo, mixed $porDefecto = ''): mixed
{
    $datos = Sesion::flashActual('_anterior', []);
    return $datos[$campo] ?? $porDefecto;
}

function error_campo(string $campo): ?string
{
    return Sesion::flashActual('_errores', [])[$campo] ?? null;
}

function clase_error(string $campo): string
{
    return error_campo($campo) ? ' is-invalid' : '';
}

function mensaje_error(string $campo): string
{
    $m = error_campo($campo);
    return $m ? '<div class="invalid-feedback d-block">' . e($m) . '</div>' : '';
}

function usuario(): ?array
{
    return Autenticacion::usuario();
}

function puede(string $permiso): bool
{
    return Autenticacion::puede($permiso);
}

/** Marca como activo un ítem del menú si la ruta actual empieza con el prefijo. */
function activo(string $prefijo, bool $exacto = false): string
{
    $ruta = Solicitud::rutaActual();
    $coincide = $exacto ? $ruta === $prefijo : ($ruta === $prefijo || str_starts_with($ruta, rtrim($prefijo, '/') . '/'));
    return $coincide ? ' active' : '';
}

/** Monto en bolivianos con formato boliviano: Bs 12.345,50 */
function bs(mixed $monto, int $decimales = 2): string
{
    return 'Bs ' . number_format((float) $monto, $decimales, ',', '.');
}

function numero(mixed $valor, int $decimales = 0): string
{
    return number_format((float) $valor, $decimales, ',', '.');
}

function porcentaje(mixed $valor, int $decimales = 1): string
{
    return number_format((float) $valor, $decimales, ',', '.') . ' %';
}

const MESES = [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
const MESES_CORTOS = [1 => 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
const DIAS = [1 => 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'];
const DIAS_CORTOS = [1 => 'lun', 'mar', 'mié', 'jue', 'vie', 'sáb', 'dom'];

/** Fecha legible en español: «21 oct 2026», «21 de octubre de 2026», «mié 21 oct». */
function fecha(mixed $valor, string $estilo = 'corta'): string
{
    if (!$valor) {
        return '—';
    }
    $f = $valor instanceof DateTimeInterface ? $valor : new DateTimeImmutable((string) $valor);
    $d = (int) $f->format('j');
    $m = (int) $f->format('n');
    $a = $f->format('Y');
    return match ($estilo) {
        'larga'    => "$d de " . MESES[$m] . " de $a",
        'dia'      => DIAS_CORTOS[(int) $f->format('N')] . " $d " . MESES_CORTOS[$m],
        'mes'      => ucfirst(MESES[$m]) . " $a",
        'hora'     => "$d " . MESES_CORTOS[$m] . " $a, " . $f->format('H:i'),
        'numerica' => $f->format('d/m/Y'),
        default    => "$d " . MESES_CORTOS[$m] . " $a",
    };
}

function hoy(): string
{
    return date('Y-m-d');
}

function noches(string $ingreso, string $salida): int
{
    return (int) (new DateTimeImmutable($ingreso))->diff(new DateTimeImmutable($salida))->days;
}

/** Iniciales para avatares: «Carla Mendoza» → «CM». */
function iniciales(string $nombre): string
{
    $partes = preg_split('/\s+/', trim($nombre)) ?: [];
    $letras = array_map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)), array_slice($partes, 0, 2));
    return implode('', $letras);
}

/** Etiquetas y colores de todos los estados del sistema. */
function estado_info(string $estado): array
{
    return match ($estado) {
        'PENDIENTE'     => ['Pendiente', 'ambar', 'bi-hourglass-split'],
        'CONFIRMADA'    => ['Confirmada', 'azul', 'bi-check2-circle'],
        'EN_CURSO'      => ['En curso', 'verde', 'bi-door-open'],
        'FINALIZADA'    => ['Finalizada', 'gris', 'bi-flag'],
        'CANCELADA'     => ['Cancelada', 'rojo', 'bi-x-circle'],
        'NO_SHOW'       => ['No-show', 'violeta', 'bi-person-x'],
        'DISPONIBLE'    => ['Disponible', 'verde', 'bi-check-circle'],
        'OCUPADA'       => ['Ocupada', 'azul', 'bi-person-fill'],
        'LIMPIEZA'      => ['Limpieza', 'ambar', 'bi-stars'],
        'MANTENIMIENTO' => ['Mantenimiento', 'rojo', 'bi-tools'],
        'ACTIVO'        => ['Activo', 'verde', 'bi-check-circle'],
        'INACTIVO'      => ['Inactivo', 'gris', 'bi-pause-circle'],
        'BLOQUEADO'     => ['Bloqueado', 'rojo', 'bi-lock'],
        'NUEVA'         => ['Nueva', 'oro', 'bi-envelope'],
        'ATENDIDA'      => ['Atendida', 'verde', 'bi-check2-all'],
        'DESCARTADA'    => ['Descartada', 'gris', 'bi-archive'],
        'ACEPTAR'       => ['Aceptar', 'verde', 'bi-hand-thumbs-up'],
        'RECHAZAR'      => ['Rechazar', 'rojo', 'bi-hand-thumbs-down'],
        'ALTA'          => ['Alta', 'verde', 'bi-graph-up-arrow'],
        'BAJA'          => ['Baja', 'azul', 'bi-graph-down-arrow'],
        'ESPECIAL'      => ['Especial', 'oro', 'bi-stars'],
        default         => [ucfirst(strtolower(str_replace('_', ' ', $estado))), 'gris', 'bi-dot'],
    };
}

function insignia(string $estado, bool $icono = true): string
{
    [$texto, $color, $ico] = estado_info($estado);
    return '<span class="insignia insignia-' . $color . '">' . ($icono ? '<i class="bi ' . $ico . '"></i>' : '<span class="punto"></span>') . e($texto) . '</span>';
}

const MOTIVOS_CANCELACION = [
    'CAMBIO_DE_PLANES' => 'Cambio de planes',
    'SALUD' => 'Salud',
    'TRABAJO' => 'Motivos laborales',
    'BLOQUEOS_O_CLIMA' => 'Bloqueos o clima',
    'PRECIO' => 'Precio',
    'OTRA_OPCION' => 'Eligió otra opción',
    'ERROR_DE_REGISTRO' => 'Error de registro',
    'OTRO' => 'Otro',
];

/** Valor de la tabla configuracion (con caché por petición). */
function ajuste(string $clave, mixed $porDefecto = null): mixed
{
    static $cache = null;
    if ($cache === null) {
        try {
            $cache = [];
            foreach (\App\Core\BaseDatos::filas('SELECT clave, valor FROM configuracion') as $fila) {
                $cache[$fila['clave']] = $fila['valor'];
            }
        } catch (Throwable) {
            $cache = [];
        }
    }
    return $cache[$clave] ?? $porDefecto;
}

function seleccionado(mixed $a, mixed $b): string
{
    return (string) $a === (string) $b ? ' selected' : '';
}

function marcado(mixed $valor): string
{
    return $valor ? ' checked' : '';
}
