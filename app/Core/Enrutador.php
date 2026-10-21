<?php

declare(strict_types=1);

namespace App\Core;

use App\Middleware\Autenticado;
use App\Middleware\Invitado;
use App\Middleware\Permiso;
use App\Middleware\VerificarCsrf;

/**
 * Enrutador con parámetros ({id}), grupos con prefijo y middlewares.
 *
 *   $r->get('/panel/reservas/{id}', [ReservaController::class, 'ver'], ['permiso:reservas.ver']);
 */
final class Enrutador
{
    private array $rutas = [];
    private array $grupo = ['prefijo' => '', 'middleware' => []];

    private const ALIAS = [
        'auth'     => Autenticado::class,
        'invitado' => Invitado::class,
        'permiso'  => Permiso::class,
    ];

    public function get(string $patron, array|callable $accion, array $middleware = []): void
    {
        $this->agregar('GET', $patron, $accion, $middleware);
    }

    public function post(string $patron, array|callable $accion, array $middleware = []): void
    {
        $this->agregar('POST', $patron, $accion, $middleware);
    }

    public function grupo(array $opciones, callable $definir): void
    {
        $anterior = $this->grupo;
        $this->grupo = [
            'prefijo'    => $anterior['prefijo'] . ($opciones['prefijo'] ?? ''),
            'middleware' => array_merge($anterior['middleware'], $opciones['middleware'] ?? []),
        ];
        $definir($this);
        $this->grupo = $anterior;
    }

    private function agregar(string $metodo, string $patron, array|callable $accion, array $middleware): void
    {
        $ruta = '/' . trim($this->grupo['prefijo'] . $patron, '/');
        $regex = '#^' . preg_replace('#\{([a-z_]+)\}#', '(?P<$1>[^/]+)', $ruta) . '$#u';
        $this->rutas[] = [
            'metodo'     => $metodo,
            'patron'     => $ruta,
            'regex'      => $regex,
            'accion'     => $accion,
            'middleware' => array_merge($this->grupo['middleware'], $middleware),
        ];
    }

    public function rutas(): array
    {
        return $this->rutas;
    }

    public function despachar(Solicitud $solicitud): Respuesta
    {
        $ruta = $solicitud->ruta();
        $metodoPermitido = false;
        foreach ($this->rutas as $definicion) {
            if (!preg_match($definicion['regex'], $ruta, $coincidencias)) {
                continue;
            }
            if ($definicion['metodo'] !== $solicitud->metodo()) {
                $metodoPermitido = true;
                continue;
            }
            $parametros = array_filter($coincidencias, 'is_string', ARRAY_FILTER_USE_KEY);
            return $this->ejecutar($definicion, $solicitud, $parametros);
        }
        throw new ExcepcionHttp($metodoPermitido ? 405 : 404);
    }

    private function ejecutar(array $definicion, Solicitud $solicitud, array $parametros): Respuesta
    {
        $cadena = [];
        if ($solicitud->metodo() === 'POST') {
            $cadena[] = [new VerificarCsrf(), null];
        }
        foreach ($definicion['middleware'] as $alias) {
            [$nombre, $argumento] = array_pad(explode(':', $alias, 2), 2, null);
            $clase = self::ALIAS[$nombre] ?? $nombre;
            $cadena[] = [new $clase(), $argumento];
        }

        $nucleo = function (Solicitud $s) use ($definicion, $parametros): Respuesta {
            $accion = $definicion['accion'];
            if (is_array($accion)) {
                [$clase, $metodo] = $accion;
                if (!class_exists($clase)) {
                    throw new ExcepcionHttp(501, $s->ruta());
                }
                $resultado = (new $clase($s))->$metodo(...array_values($parametros));
            } else {
                $resultado = $accion($s, ...array_values($parametros));
            }
            return self::normalizar($resultado);
        };

        $siguiente = array_reduce(
            array_reverse($cadena),
            fn (callable $siguiente, array $m) => fn (Solicitud $s) => $m[0]->manejar($s, $siguiente, $m[1]),
            $nucleo
        );
        return $siguiente($solicitud);
    }

    public static function normalizar(mixed $resultado): Respuesta
    {
        return match (true) {
            $resultado instanceof Respuesta => $resultado,
            is_array($resultado)            => Respuesta::json($resultado),
            default                         => Respuesta::html((string) $resultado),
        };
    }
}
