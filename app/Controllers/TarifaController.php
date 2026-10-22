<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseDatos;
use App\Core\ExcepcionHttp;
use App\Core\ExcepcionValidacion;
use App\Core\Respuesta;
use App\Servicios\DemandaServicio;
use App\Servicios\TarifaServicio;

/**
 * Sprint 6 · Estimación de demanda y tarifas:
 * CU-20 Configurar tarifas por temporada y CU-21 Sugerir tarifa referencial.
 */
final class TarifaController extends Controlador
{
    public function index(): Respuesta
    {
        $tipo = (string) $this->solicitud->consulta('tipo', '');
        $vista = $this->solicitud->consulta('vista') === 'todas' ? 'todas' : 'vigentes';
        $donde = $vista === 'vigentes' ? 't.fecha_fin >= CURDATE()' : '1 = 1';
        $p = [];
        if (in_array($tipo, ['ALTA', 'BAJA', 'ESPECIAL'], true)) {
            $donde .= ' AND t.tipo = :tipo';
            $p['tipo'] = $tipo;
        }
        $temporadas = BaseDatos::filas("SELECT t.* FROM temporada t WHERE $donde ORDER BY t.fecha_inicio", $p);
        $categorias = BaseDatos::filas('SELECT * FROM categoria_habitacion ORDER BY tarifa_base');
        $precios = [];
        foreach (BaseDatos::filas('SELECT * FROM tarifa_temporada') as $t) {
            $precios[(int) $t['id_temporada']][(int) $t['id_categoria']] = (float) $t['precio_noche'];
        }
        $hoy = [];
        foreach ($categorias as $c) {
            $hoy[(int) $c['id_categoria']] = TarifaServicio::precioNoche((int) $c['id_categoria'], date('Y-m-d'));
        }
        return $this->vista('tarifas/index', [
            'titulo' => 'Tarifas y demanda', 'temporadas' => $temporadas, 'categorias' => $categorias, 'precios' => $precios,
            'hoy' => $hoy, 'tipo' => $tipo, 'vistaFiltro' => $vista, 'mapa' => DemandaServicio::mapa(42),
            'mensual' => DemandaServicio::ocupacionMensual(12),
        ]);
    }

    public function crear(): Respuesta
    {
        return $this->vista('tarifas/formulario', ['titulo' => 'Nueva temporada', 'temporada' => null, 'precios' => [], 'categorias' => $this->categorias()]);
    }

    public function guardar(): Respuesta
    {
        [$datos, $precios] = $this->datos();
        BaseDatos::transaccion(function () use ($datos, $precios) {
            $id = BaseDatos::insertar('temporada', $datos);
            $this->guardarPrecios($id, $precios);
        });
        TarifaServicio::reiniciarCache();
        $this->bitacora('Tarifas', 'Configurar temporada', "Temporada {$datos['nombre']} ({$datos['tipo']}) del " . fecha($datos['fecha_inicio']) . ' al ' . fecha($datos['fecha_fin']));
        return $this->redirigir('/panel/tarifas', "Temporada {$datos['nombre']} creada. Las nuevas reservas usarán estas tarifas.");
    }

    public function editar(string $id): Respuesta
    {
        $t = BaseDatos::fila('SELECT * FROM temporada WHERE id_temporada = ?', [(int) $id]) ?? throw new ExcepcionHttp(404);
        $precios = [];
        foreach (BaseDatos::filas('SELECT * FROM tarifa_temporada WHERE id_temporada = ?', [(int) $id]) as $p) {
            $precios[(int) $p['id_categoria']] = (float) $p['precio_noche'];
        }
        return $this->vista('tarifas/formulario', ['titulo' => 'Editar temporada', 'temporada' => $t, 'precios' => $precios, 'categorias' => $this->categorias()]);
    }

    public function actualizar(string $id): Respuesta
    {
        BaseDatos::fila('SELECT 1 FROM temporada WHERE id_temporada = ?', [(int) $id]) ?? throw new ExcepcionHttp(404);
        [$datos, $precios] = $this->datos();
        BaseDatos::transaccion(function () use ($id, $datos, $precios) {
            BaseDatos::actualizar('temporada', $datos, 'id_temporada = :id', ['id' => (int) $id]);
            $this->guardarPrecios((int) $id, $precios);
        });
        TarifaServicio::reiniciarCache();
        $this->bitacora('Tarifas', 'Configurar temporada', "Temporada {$datos['nombre']} actualizada");
        return $this->redirigir('/panel/tarifas', 'Temporada actualizada.');
    }

    public function eliminar(string $id): Respuesta
    {
        $t = BaseDatos::fila('SELECT * FROM temporada WHERE id_temporada = ?', [(int) $id]) ?? throw new ExcepcionHttp(404);
        BaseDatos::consulta('DELETE FROM temporada WHERE id_temporada = ?', [(int) $id]);
        $this->bitacora('Tarifas', 'Eliminar temporada', $t['nombre']);
        return $this->redirigir('/panel/tarifas', "Temporada {$t['nombre']} eliminada. Las reservas ya registradas conservan su monto.");
    }

    /** CU-21 Sugerir tarifa referencial. */
    public function sugerencia(): Respuesta
    {
        $categorias = $this->categorias();
        $resultado = null;
        $desde = (string) $this->solicitud->consulta('desde', '');
        $hasta = (string) $this->solicitud->consulta('hasta', '');
        $idCategoria = $this->entero('categoria') ?: (int) ($categorias[2]['id_categoria'] ?? $categorias[0]['id_categoria'] ?? 0);
        if ($desde !== '') {
            $hasta = $hasta ?: $desde;
            if (!$this->fechaValida($desde) || !$this->fechaValida($hasta)) {
                throw ExcepcionValidacion::campo('desde', 'Ingrese fechas válidas.');
            }
            $resultado = DemandaServicio::sugerir($desde, $hasta, $idCategoria, true);
        }
        $recientes = BaseDatos::filas(
            'SELECT s.*, c.nombre AS categoria, t.nombre AS temporada FROM sugerencia_tarifa s
               JOIN categoria_habitacion c ON c.id_categoria = s.id_categoria LEFT JOIN temporada t ON t.id_temporada = s.id_temporada
              ORDER BY s.fecha DESC LIMIT 8'
        );
        $especiales = BaseDatos::filas("SELECT * FROM temporada WHERE tipo = 'ESPECIAL' AND fecha_fin >= CURDATE() ORDER BY fecha_inicio LIMIT 6");
        return $this->vista('tarifas/sugerencia', [
            'titulo' => 'Tarifa referencial', 'categorias' => $categorias, 'resultado' => $resultado, 'recientes' => $recientes,
            'desde' => $desde ?: date('Y-m-d', strtotime('+10 days')), 'hasta' => $hasta ?: date('Y-m-d', strtotime('+12 days')),
            'idCategoria' => $idCategoria, 'parametros' => DemandaServicio::parametros(), 'especiales' => $especiales,
        ]);
    }

    public function aplicarSugerencia(): Respuesta
    {
        $d = $this->validar(['id_sugerencia' => 'requerido|entero', 'nombre' => 'requerido|max:80', 'precio' => 'opcional|numero|mayor:0']);
        DemandaServicio::aplicar((int) $d['id_sugerencia'], (string) $d['nombre'], $d['precio'] ? (float) $d['precio'] : null);
        $this->bitacora('Tarifas', 'Aplicar tarifa sugerida', "Temporada especial «{$d['nombre']}» creada desde la sugerencia #{$d['id_sugerencia']}");
        return $this->redirigir('/panel/tarifas', "Tarifa aplicada: se creó la temporada especial «{$d['nombre']}».");
    }

    private function categorias(): array
    {
        return BaseDatos::filas('SELECT * FROM categoria_habitacion WHERE estado = 1 ORDER BY tarifa_base');
    }

    /** CU-20 paso 3: valida fechas y precios (flujo 3a: fin anterior al inicio). */
    private function datos(): array
    {
        $d = $this->validar([
            'nombre' => 'requerido|max:80', 'tipo' => 'requerido|en:ALTA,BAJA,ESPECIAL',
            'fecha_inicio' => 'requerido|fecha', 'fecha_fin' => 'requerido|fecha|despues_o_igual:fecha_inicio',
            'descripcion' => 'opcional|max:255', 'color' => 'opcional|color',
        ]);
        $precios = [];
        $errores = [];
        foreach ($this->categorias() as $c) {
            $valor = $this->solicitud->entrada('precio_' . $c['id_categoria']);
            if (!is_numeric($valor) || (float) $valor <= 0) {
                $errores['precio_' . $c['id_categoria']] = "Ingrese un precio mayor a 0 para {$c['nombre']}.";
            } else {
                $precios[(int) $c['id_categoria']] = round((float) $valor, 2);
            }
        }
        if ($errores) {
            throw new ExcepcionValidacion($errores);
        }
        return [[
            'nombre' => $d['nombre'], 'tipo' => $d['tipo'], 'fecha_inicio' => $d['fecha_inicio'], 'fecha_fin' => $d['fecha_fin'],
            'descripcion' => $d['descripcion'], 'color' => $d['color'] ?: '#C6A15B', 'estado' => $this->solicitud->entrada('estado') ? 1 : 0,
        ], $precios];
    }

    /** CU-20 flujo 3b: si ya existe la tarifa de esa categoría y temporada, se actualiza. */
    private function guardarPrecios(int $idTemporada, array $precios): void
    {
        foreach ($precios as $idCategoria => $precio) {
            BaseDatos::consulta(
                'INSERT INTO tarifa_temporada (id_temporada, id_categoria, precio_noche) VALUES (:t, :c, :p)
                 ON DUPLICATE KEY UPDATE precio_noche = VALUES(precio_noche)',
                ['t' => $idTemporada, 'c' => $idCategoria, 'p' => $precio]
            );
        }
    }

    private function fechaValida(string $f): bool
    {
        $d = \DateTimeImmutable::createFromFormat('Y-m-d', $f);
        return $d && $d->format('Y-m-d') === $f;
    }
}
