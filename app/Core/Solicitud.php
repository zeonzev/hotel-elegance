<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Representa la petición HTTP actual y calcula la ruta y la URL base de forma
 * dinámica, para que el sistema funcione en cualquier subcarpeta de htdocs,
 * con o sin mod_rewrite.
 */
final class Solicitud
{
    private static ?string $base = null;
    private static ?bool $reescritura = null;

    public function __construct(
        private string $metodo,
        private string $ruta,
        private array $consulta,
        private array $cuerpo,
        private array $servidor
    ) {
    }

    public static function capturar(): self
    {
        $metodo = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($metodo === 'POST' && isset($_POST['_metodo'])) {
            $metodo = strtoupper((string) $_POST['_metodo']);
        }
        $cuerpo = $_POST;
        $tipo = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($tipo, 'application/json')) {
            $json = json_decode((string) file_get_contents('php://input'), true);
            $cuerpo = is_array($json) ? $json : [];
        }
        return new self($metodo, self::rutaActual(), $_GET, $cuerpo, $_SERVER);
    }

    /** Ruta de la aplicación sin la carpeta base: /panel/reservas */
    public static function rutaActual(): string
    {
        $uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
        $base = self::base();
        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }
        foreach (['/public/index.php', '/index.php', '/public'] as $prefijo) {
            if ($uri === $prefijo || str_starts_with($uri, $prefijo . '/')) {
                $uri = substr($uri, strlen($prefijo));
                break;
            }
        }
        $uri = '/' . trim($uri, '/');
        return $uri;
    }

    /** Carpeta del proyecto vista desde el navegador, p. ej. "/hotel-elegance". */
    public static function base(): string
    {
        if (self::$base === null) {
            $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
            foreach (['/public/index.php', '/index.php'] as $sufijo) {
                if (str_ends_with($script, $sufijo)) {
                    $script = substr($script, 0, -strlen($sufijo));
                    break;
                }
            }
            self::$base = rtrim($script, '/');
        }
        return self::$base;
    }

    /** true si Apache reescribe las URL (.htaccess con mod_rewrite activo). */
    public static function conReescritura(): bool
    {
        if (self::$reescritura === null) {
            self::$reescritura = false;
            foreach ($_SERVER as $clave => $valor) {
                if (str_ends_with((string) $clave, 'HE_REESCRITURA') && $valor) {
                    self::$reescritura = true;
                    break;
                }
            }
        }
        return self::$reescritura;
    }

    public static function reiniciarCache(): void
    {
        self::$base = null;
        self::$reescritura = null;
    }

    public function metodo(): string
    {
        return $this->metodo === 'HEAD' ? 'GET' : $this->metodo;
    }

    public function ruta(): string
    {
        return $this->ruta;
    }

    public function entrada(string $clave, mixed $porDefecto = null): mixed
    {
        $valor = $this->cuerpo[$clave] ?? $this->consulta[$clave] ?? $porDefecto;
        return is_string($valor) ? trim($valor) : $valor;
    }

    public function consulta(string $clave, mixed $porDefecto = null): mixed
    {
        $valor = $this->consulta[$clave] ?? $porDefecto;
        return is_string($valor) ? trim($valor) : $valor;
    }

    /** Todos los datos enviados (cuerpo + query string), con espacios recortados. */
    public function todo(): array
    {
        $datos = array_merge($this->consulta, $this->cuerpo);
        array_walk_recursive($datos, function (&$v) {
            if (is_string($v)) {
                $v = trim($v);
            }
        });
        return $datos;
    }

    public function solo(array $claves): array
    {
        $todo = $this->todo();
        $resultado = [];
        foreach ($claves as $clave) {
            $resultado[$clave] = $todo[$clave] ?? null;
        }
        return $resultado;
    }

    public function filtros(): array
    {
        return $this->consulta;
    }

    public function esAjax(): bool
    {
        return ($this->servidor['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
            || str_contains($this->servidor['HTTP_ACCEPT'] ?? '', 'application/json');
    }

    public function encabezado(string $nombre): ?string
    {
        $clave = 'HTTP_' . strtoupper(str_replace('-', '_', $nombre));
        return $this->servidor[$clave] ?? null;
    }

    public function ip(): string
    {
        return (string) ($this->servidor['REMOTE_ADDR'] ?? '127.0.0.1');
    }

    public function referente(): ?string
    {
        return $this->servidor['HTTP_REFERER'] ?? null;
    }
}
