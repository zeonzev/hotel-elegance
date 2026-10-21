<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Respuesta HTTP: contenido, código y encabezados. Los controladores devuelven
 * una instancia (o un string HTML / array JSON que el enrutador convierte).
 */
final class Respuesta
{
    public function __construct(
        private string $contenido = '',
        private int $codigo = 200,
        private array $encabezados = []
    ) {
    }

    public static function html(string $contenido, int $codigo = 200): self
    {
        return new self($contenido, $codigo, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public static function json(mixed $datos, int $codigo = 200): self
    {
        return new self(
            (string) json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION),
            $codigo,
            ['Content-Type' => 'application/json; charset=UTF-8']
        );
    }

    public static function redirigir(string $destino, int $codigo = 302): self
    {
        if (!preg_match('#^https?://#', $destino)) {
            $destino = url($destino);
        }
        return new self('', $codigo, ['Location' => $destino]);
    }

    public static function descarga(string $contenido, string $archivo, string $tipo): self
    {
        return new self($contenido, 200, [
            'Content-Type'        => $tipo,
            'Content-Disposition' => 'attachment; filename="' . $archivo . '"',
            'Cache-Control'       => 'no-store',
            'Content-Length'      => (string) strlen($contenido),
        ]);
    }

    public function codigo(): int
    {
        return $this->codigo;
    }

    public function contenido(): string
    {
        return $this->contenido;
    }

    public function encabezados(): array
    {
        return $this->encabezados;
    }

    public function enviar(): void
    {
        if (!headers_sent()) {
            http_response_code($this->codigo);
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');
            header('Referrer-Policy: strict-origin-when-cross-origin');
            header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
            foreach ($this->encabezados as $nombre => $valor) {
                header($nombre . ': ' . $valor);
            }
        }
        echo $this->contenido;
    }
}
