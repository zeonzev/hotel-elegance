<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseDatos;
use App\Core\ExcepcionHttp;
use App\Core\ExcepcionValidacion;
use App\Core\Respuesta;
use App\Servicios\TarifaServicio;

/**
 * Sprint 3 · CU-09 Gestionar categorías de habitación.
 */
final class CategoriaController extends Controlador
{
    public function index(): Respuesta
    {
        $categorias = BaseDatos::filas(
            "SELECT c.*, (SELECT COUNT(*) FROM habitacion h WHERE h.id_categoria = c.id_categoria) AS habitaciones,
                    (SELECT COUNT(*) FROM habitacion h WHERE h.id_categoria = c.id_categoria AND h.estado = 'DISPONIBLE') AS disponibles
               FROM categoria_habitacion c ORDER BY c.tarifa_base"
        );
        foreach ($categorias as &$c) {
            $c['tarifa_hoy'] = TarifaServicio::precioNoche((int) $c['id_categoria'], date('Y-m-d'));
        }
        return $this->vista('categorias/index', ['titulo' => 'Categorías', 'categorias' => $categorias]);
    }

    public function crear(): Respuesta
    {
        return $this->vista('categorias/formulario', ['titulo' => 'Nueva categoría', 'categoria' => null, 'imagenes' => $this->imagenes()]);
    }

    public function guardar(): Respuesta
    {
        $d = $this->datos(0);
        BaseDatos::insertar('categoria_habitacion', $d);
        $this->bitacora('Habitaciones', 'Crear categoría', $d['nombre']);
        return $this->redirigir('/panel/categorias', "Categoría {$d['nombre']} creada.");
    }

    public function editar(string $id): Respuesta
    {
        $c = BaseDatos::fila('SELECT * FROM categoria_habitacion WHERE id_categoria = ?', [(int) $id]) ?? throw new ExcepcionHttp(404);
        return $this->vista('categorias/formulario', ['titulo' => 'Editar categoría', 'categoria' => $c, 'imagenes' => $this->imagenes()]);
    }

    public function actualizar(string $id): Respuesta
    {
        BaseDatos::fila('SELECT 1 FROM categoria_habitacion WHERE id_categoria = ?', [(int) $id]) ?? throw new ExcepcionHttp(404);
        $d = $this->datos((int) $id);
        BaseDatos::actualizar('categoria_habitacion', $d, 'id_categoria = :id', ['id' => (int) $id]);
        TarifaServicio::reiniciarCache();
        $this->bitacora('Habitaciones', 'Modificar categoría', $d['nombre']);
        return $this->redirigir('/panel/categorias', 'Categoría actualizada.');
    }

    /** CU-09 flujo 3a: no se elimina una categoría con habitaciones asignadas. */
    public function eliminar(string $id): Respuesta
    {
        $c = BaseDatos::fila('SELECT * FROM categoria_habitacion WHERE id_categoria = ?', [(int) $id]) ?? throw new ExcepcionHttp(404);
        $habitaciones = (int) BaseDatos::valor('SELECT COUNT(*) FROM habitacion WHERE id_categoria = ?', [(int) $id]);
        if ($habitaciones > 0) {
            return $this->volver("La categoría {$c['nombre']} tiene $habitaciones habitación(es) asignada(s) y no puede eliminarse.", 'error');
        }
        try {
            BaseDatos::consulta('DELETE FROM categoria_habitacion WHERE id_categoria = ?', [(int) $id]);
        } catch (\PDOException $e) {
            return $this->volver("La categoría {$c['nombre']} tiene reservas históricas asociadas; desactívela en lugar de eliminarla.", 'error');
        }
        $this->bitacora('Habitaciones', 'Eliminar categoría', $c['nombre']);
        return $this->redirigir('/panel/categorias', "Categoría {$c['nombre']} eliminada.");
    }

    private function imagenes(): array
    {
        return array_map('basename', glob(RAIZ . '/public/assets/img/habitaciones/*.jpg') ?: []);
    }

    private function datos(int $excluir): array
    {
        $d = $this->validar([
            'nombre' => 'requerido|max:60',
            'descripcion' => 'requerido|max:600',
            'capacidad' => 'requerido|entero|mayor:0|maximo:10',
            'tarifa_base' => 'requerido|numero|mayor:0',
            'camas' => 'requerido|max:80',
            'superficie_m2' => 'opcional|entero|mayor:0',
            'comodidades' => 'opcional|max:600',
            'imagen' => 'opcional|max:120',
        ], ['tarifa_base' => 'tarifa base', 'superficie_m2' => 'superficie']);
        if (BaseDatos::valor('SELECT 1 FROM categoria_habitacion WHERE nombre = ? AND id_categoria <> ?', [$d['nombre'], $excluir])) {
            throw ExcepcionValidacion::campo('nombre', 'Ya existe una categoría con ese nombre.');
        }
        if ($d['imagen'] && !in_array($d['imagen'], $this->imagenes(), true)) {
            $d['imagen'] = null;
        }
        $comodidades = array_filter(array_map('trim', preg_split('/[\r\n|]+/', (string) $d['comodidades'])));
        return [
            'nombre' => $d['nombre'], 'descripcion' => $d['descripcion'], 'capacidad' => (int) $d['capacidad'],
            'tarifa_base' => round((float) $d['tarifa_base'], 2), 'camas' => $d['camas'],
            'superficie_m2' => $d['superficie_m2'] ? (int) $d['superficie_m2'] : null,
            'comodidades' => implode('|', $comodidades) ?: null, 'imagen' => $d['imagen'] ?? 'detalle-1.jpg',
            'estado' => $this->solicitud->entrada('estado') ? 1 : 0,
        ];
    }
}
