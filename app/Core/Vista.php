<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Motor de plantillas PHP nativo: la vista se renderiza primero y su HTML se
 * inserta en el layout. Las vistas pueden definir secciones (p. ej. «scripts»).
 *
 *   <?php $this->seccion('scripts') ?> <script>...</script> <?php $this->fin() ?>
 */
final class Vista
{
    private array $secciones = [];
    private array $pila = [];

    public function __construct(private array $datosGlobales = [])
    {
    }

    public static function renderizar(string $vista, array $datos = [], ?string $layout = 'panel'): string
    {
        $motor = new self($datos);
        $contenido = $motor->incluir($vista, $datos);
        if ($layout === null) {
            return $contenido;
        }
        return $motor->incluir('layouts/' . $layout, $datos + ['contenido' => $contenido]);
    }

    /** Renderiza un parcial dentro de otra vista: $this->parcial('parciales/paginacion', [...]) */
    public function parcial(string $vista, array $datos = []): string
    {
        return $this->incluir($vista, $datos + $this->datosGlobales);
    }

    public function seccion(string $nombre): void
    {
        $this->pila[] = $nombre;
        ob_start();
    }

    public function fin(): void
    {
        $nombre = array_pop($this->pila);
        if ($nombre === null) {
            throw new RuntimeException('fin() sin seccion() previa.');
        }
        $this->secciones[$nombre] = ($this->secciones[$nombre] ?? '') . ob_get_clean();
    }

    public function mostrar(string $nombre, string $porDefecto = ''): string
    {
        return $this->secciones[$nombre] ?? $porDefecto;
    }

    private function incluir(string $vista, array $datos): string
    {
        $archivo = RAIZ . '/app/Vistas/' . $vista . '.php';
        if (!is_file($archivo)) {
            throw new ExcepcionHttp(501, "Vista pendiente: $vista");
        }
        extract($datos, EXTR_SKIP);
        ob_start();
        try {
            require $archivo;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }
}
