<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Paginación simple basada en LIMIT/OFFSET que conserva los filtros de la URL.
 */
final class Paginador
{
    public readonly int $pagina;
    public readonly int $paginas;
    public readonly int $desplazamiento;

    public function __construct(public readonly int $total, int $pagina, public readonly int $porPagina = 15)
    {
        $this->paginas = max(1, (int) ceil($total / max(1, $porPagina)));
        $this->pagina = min(max(1, $pagina), $this->paginas);
        $this->desplazamiento = ($this->pagina - 1) * $porPagina;
    }

    public function desde(): int
    {
        return $this->total === 0 ? 0 : $this->desplazamiento + 1;
    }

    public function hasta(): int
    {
        return min($this->total, $this->desplazamiento + $this->porPagina);
    }

    public function enlace(int $pagina): string
    {
        $parametros = $_GET;
        $parametros['pagina'] = $pagina;
        return url(Solicitud::rutaActual()) . '?' . http_build_query($parametros);
    }

    /** Páginas a mostrar, con «…» para saltos largos. */
    public function ventana(): array
    {
        $paginas = [];
        for ($i = 1; $i <= $this->paginas; $i++) {
            if ($i === 1 || $i === $this->paginas || abs($i - $this->pagina) <= 1) {
                $paginas[] = $i;
            } elseif (end($paginas) !== '…') {
                $paginas[] = '…';
            }
        }
        return $paginas;
    }
}
