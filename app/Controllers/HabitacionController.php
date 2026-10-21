<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseDatos;
use App\Core\ExcepcionHttp;
use App\Core\ExcepcionValidacion;
use App\Core\Respuesta;
use App\Servicios\HabitacionServicio;

/**
 * Sprint 3 · CU-08 Gestionar habitaciones y CU-10 Actualizar estado de habitación.
 */
final class HabitacionController extends Controlador
{
    public function index(): Respuesta
    {
        $filtros = $this->solicitud->solo(['estado', 'categoria', 'piso', 'vista']);
        $donde = ['1 = 1'];
        $p = [];
        if ($filtros['estado']) {
            $donde[] = 'h.estado = :estado';
            $p['estado'] = $filtros['estado'];
        }
        if ($filtros['categoria']) {
            $donde[] = 'h.id_categoria = :cat';
            $p['cat'] = (int) $filtros['categoria'];
        }
        if ($filtros['piso']) {
            $donde[] = 'h.piso = :piso';
            $p['piso'] = (int) $filtros['piso'];
        }
        $habitaciones = BaseDatos::filas(
            "SELECT h.*, c.nombre AS categoria, c.capacidad, c.tarifa_base,
                    (SELECT CONCAT(hu.nombres, ' ', hu.apellidos, '|', r.fecha_salida, '|', r.id_reserva)
                       FROM reserva r JOIN detalle_reserva d ON d.id_reserva = r.id_reserva JOIN huesped hu ON hu.id_huesped = r.id_huesped
                      WHERE d.id_habitacion = h.id_habitacion AND r.estado = 'EN_CURSO' LIMIT 1) AS ocupante,
                    (SELECT MIN(r.fecha_ingreso) FROM reserva r JOIN detalle_reserva d ON d.id_reserva = r.id_reserva
                      WHERE d.id_habitacion = h.id_habitacion AND r.estado IN ('PENDIENTE', 'CONFIRMADA') AND r.fecha_ingreso >= CURDATE()) AS proxima_llegada
               FROM habitacion h JOIN categoria_habitacion c ON c.id_categoria = h.id_categoria
              WHERE " . implode(' AND ', $donde) . ' ORDER BY h.piso DESC, h.numero',
            $p
        );
        $pisos = [];
        foreach ($habitaciones as $h) {
            $pisos[(int) $h['piso']][] = $h;
        }
        $conteo = ['DISPONIBLE' => 0, 'OCUPADA' => 0, 'LIMPIEZA' => 0, 'MANTENIMIENTO' => 0];
        foreach (BaseDatos::filas('SELECT estado, COUNT(*) n FROM habitacion GROUP BY estado') as $f) {
            $conteo[$f['estado']] = (int) $f['n'];
        }
        return $this->vista('habitaciones/index', [
            'titulo' => 'Habitaciones', 'habitaciones' => $habitaciones, 'pisos' => $pisos, 'filtros' => $filtros, 'conteo' => $conteo,
            'categorias' => BaseDatos::filas('SELECT * FROM categoria_habitacion ORDER BY tarifa_base'),
            'listaPisos' => BaseDatos::consulta('SELECT DISTINCT piso FROM habitacion ORDER BY piso')->fetchAll(\PDO::FETCH_COLUMN),
        ]);
    }

    public function crear(): Respuesta
    {
        return $this->vista('habitaciones/formulario', ['titulo' => 'Nueva habitación', 'habitacion' => null, 'categorias' => $this->categorias()]);
    }

    public function guardar(): Respuesta
    {
        $d = $this->datosValidados(0);
        $id = BaseDatos::insertar('habitacion', $d + ['estado' => 'DISPONIBLE']);
        $this->bitacora('Habitaciones', 'Registrar habitación', "Habitación {$d['numero']} (piso {$d['piso']})");
        return $this->redirigir('/panel/habitaciones/' . $id, "Habitación {$d['numero']} registrada.");
    }

    public function ver(string $id): Respuesta
    {
        $habitacion = BaseDatos::fila(
            'SELECT h.*, c.nombre AS categoria, c.capacidad, c.tarifa_base, c.camas, c.superficie_m2, c.imagen
               FROM habitacion h JOIN categoria_habitacion c ON c.id_categoria = h.id_categoria WHERE h.id_habitacion = ?',
            [(int) $id]
        ) ?? throw new ExcepcionHttp(404, 'La habitación no existe.');
        $historial = BaseDatos::filas(
            'SELECT x.*, u.nombre_usuario FROM historial_estado_habitacion x LEFT JOIN usuario u ON u.id_usuario = x.id_usuario
              WHERE x.id_habitacion = ? ORDER BY x.fecha DESC, x.id_historial DESC LIMIT 15',
            [(int) $id]
        );
        $reservas = BaseDatos::filas(
            "SELECT r.id_reserva, r.codigo, r.fecha_ingreso, r.fecha_salida, r.estado, CONCAT(h.nombres, ' ', h.apellidos) AS huesped
               FROM reserva r JOIN detalle_reserva d ON d.id_reserva = r.id_reserva JOIN huesped h ON h.id_huesped = r.id_huesped
              WHERE d.id_habitacion = ? AND r.estado IN ('PENDIENTE', 'CONFIRMADA', 'EN_CURSO') ORDER BY r.fecha_ingreso LIMIT 8",
            [(int) $id]
        );
        $estadisticas = BaseDatos::fila(
            "SELECT COUNT(*) AS estadias, COALESCE(SUM(d.noches), 0) AS noches, COALESCE(SUM(d.subtotal), 0) AS ingresos
               FROM detalle_reserva d JOIN reserva r ON r.id_reserva = d.id_reserva
              WHERE d.id_habitacion = ? AND r.estado = 'FINALIZADA' AND r.fecha_salida >= CURDATE() - INTERVAL 12 MONTH",
            [(int) $id]
        );
        return $this->vista('habitaciones/ver', [
            'titulo' => 'Habitación ' . $habitacion['numero'], 'h' => $habitacion, 'historial' => $historial, 'reservas' => $reservas,
            'estadisticas' => $estadisticas, 'alojado' => HabitacionServicio::estadiaAbierta((int) $id),
        ]);
    }

    public function editar(string $id): Respuesta
    {
        $habitacion = BaseDatos::fila('SELECT * FROM habitacion WHERE id_habitacion = ?', [(int) $id]) ?? throw new ExcepcionHttp(404);
        return $this->vista('habitaciones/formulario', ['titulo' => 'Editar habitación ' . $habitacion['numero'], 'habitacion' => $habitacion, 'categorias' => $this->categorias()]);
    }

    public function actualizar(string $id): Respuesta
    {
        BaseDatos::fila('SELECT 1 FROM habitacion WHERE id_habitacion = ?', [(int) $id]) ?? throw new ExcepcionHttp(404);
        $d = $this->datosValidados((int) $id);
        BaseDatos::actualizar('habitacion', $d, 'id_habitacion = :id', ['id' => (int) $id]);
        $this->bitacora('Habitaciones', 'Modificar habitación', "Habitación {$d['numero']}");
        return $this->redirigir('/panel/habitaciones/' . $id, 'Habitación actualizada.');
    }

    /** CU-08 flujo 4a: con reservas asociadas la base de datos lo impide (ON DELETE RESTRICT). */
    public function eliminar(string $id): Respuesta
    {
        $h = BaseDatos::fila('SELECT * FROM habitacion WHERE id_habitacion = ?', [(int) $id]) ?? throw new ExcepcionHttp(404);
        try {
            BaseDatos::consulta('DELETE FROM habitacion WHERE id_habitacion = ?', [(int) $id]);
        } catch (\PDOException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1451) {
                return $this->volver("La habitación {$h['numero']} tiene reservas asociadas y no puede eliminarse. Póngala en MANTENIMIENTO para retirarla de la venta.", 'error');
            }
            throw $e;
        }
        $this->bitacora('Habitaciones', 'Eliminar habitación', "Habitación {$h['numero']}");
        return $this->redirigir('/panel/habitaciones', "Habitación {$h['numero']} eliminada.");
    }

    /** CU-10 Actualizar estado de habitación. */
    public function cambiarEstado(string $id): Respuesta
    {
        $d = $this->validar(['estado' => 'requerido|en:DISPONIBLE,OCUPADA,LIMPIEZA,MANTENIMIENTO', 'motivo' => 'opcional|max:255']);
        $numero = BaseDatos::valor('SELECT numero FROM habitacion WHERE id_habitacion = ?', [(int) $id]) ?? throw new ExcepcionHttp(404);
        $cambio = BaseDatos::transaccion(fn () => HabitacionServicio::cambiarEstado((int) $id, (string) $d['estado'], $d['motivo']));
        if (!$cambio) {
            return $this->volver("La habitación $numero ya estaba en ese estado: no se registraron cambios.", 'info');
        }
        $this->bitacora('Habitaciones', 'Cambio de estado', "Habitación $numero → {$d['estado']}" . ($d['motivo'] ? " ({$d['motivo']})" : ''));
        $aviso = '';
        if ($d['estado'] === 'MANTENIMIENTO' && ($futuras = HabitacionServicio::reservasFuturas((int) $id)) > 0) {
            $aviso = " Atención: tiene $futuras reserva(s) próxima(s) que conviene reasignar.";
        }
        if ($this->solicitud->esAjax()) {
            return $this->json(['ok' => true, 'mensaje' => "Habitación $numero ahora está " . strtolower(estado_info($d['estado'])[0]) . '.' . $aviso]);
        }
        return $this->volver("Habitación $numero: estado actualizado a " . estado_info($d['estado'])[0] . '.' . $aviso, $aviso ? 'aviso' : 'exito');
    }

    private function categorias(): array
    {
        return BaseDatos::filas('SELECT * FROM categoria_habitacion WHERE estado = 1 ORDER BY tarifa_base');
    }

    private function datosValidados(int $excluir): array
    {
        $d = $this->validar([
            'numero' => 'requerido|regex:/^[0-9A-Za-z\-]{1,10}$/',
            'piso' => 'requerido|entero|minimo:0|maximo:50',
            'id_categoria' => 'requerido|entero',
            'descripcion' => 'opcional|max:255',
        ], ['numero' => 'número', 'id_categoria' => 'categoría']);
        // CU-08 paso 4: el número no se repite
        if (BaseDatos::valor('SELECT COUNT(*) FROM habitacion WHERE numero = ? AND id_habitacion <> ?', [$d['numero'], $excluir])) {
            throw ExcepcionValidacion::campo('numero', "Ya existe la habitación {$d['numero']}.");
        }
        if (!BaseDatos::valor('SELECT 1 FROM categoria_habitacion WHERE id_categoria = ?', [(int) $d['id_categoria']])) {
            throw ExcepcionValidacion::campo('id_categoria', 'Seleccione una categoría registrada.');
        }
        return ['numero' => strtoupper((string) $d['numero']), 'piso' => (int) $d['piso'], 'id_categoria' => (int) $d['id_categoria'], 'descripcion' => $d['descripcion']];
    }
}
