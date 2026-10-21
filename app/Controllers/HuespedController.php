<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\ExcepcionHttp;
use App\Core\ExcepcionValidacion;
use App\Core\Respuesta;
use App\Core\Sesion;
use App\Repositorios\HuespedRepositorio;

/**
 * Sprint 2 · Gestión de huéspedes: CU-05 Registrar huésped, CU-06 Modificar
 * datos de huésped y CU-07 Consultar historial de huéspedes.
 */
final class HuespedController extends Controlador
{
    public function index(): Respuesta
    {
        $filtros = $this->solicitud->solo(['q', 'nacionalidad', 'origen', 'orden']);
        [$huespedes, $paginador] = HuespedRepositorio::listar($filtros, $this->entero('pagina', 1));
        $resumen = \App\Core\BaseDatos::fila(
            "SELECT COUNT(*) AS total, COALESCE(SUM(nacionalidad = 'Boliviana'), 0) AS nacionales,
                    (SELECT COUNT(DISTINCT id_huesped) FROM reserva WHERE estado = 'EN_CURSO') AS alojados,
                    (SELECT COUNT(*) FROM (SELECT id_huesped FROM reserva WHERE estado = 'FINALIZADA' GROUP BY id_huesped HAVING COUNT(*) > 1) x) AS recurrentes
               FROM huesped"
        );
        return $this->vista('huespedes/index', [
            'titulo' => 'Huéspedes', 'huespedes' => $huespedes, 'paginador' => $paginador, 'filtros' => $filtros,
            'nacionalidades' => HuespedRepositorio::nacionalidades(), 'resumen' => $resumen,
        ]);
    }

    public function crear(): Respuesta
    {
        return $this->vista('huespedes/formulario', [
            'titulo' => 'Nuevo huésped', 'huesped' => null, 'tipos' => HuespedRepositorio::tiposDocumento(),
            'documento' => $this->solicitud->consulta('documento'),
        ]);
    }

    public function guardar(): Respuesta
    {
        try {
            $d = HuespedRepositorio::validar($this->solicitud->todo());
        } catch (ExcepcionValidacion $e) {
            if ($e->existente) {
                // CU-05 flujo 3a: se muestran los datos del huésped existente para usarlo
                Sesion::flash('aviso', $e->getMessage());
                return $this->redirigir('/panel/huespedes/' . $e->existente['id_huesped']);
            }
            throw $e;
        }
        $id = HuespedRepositorio::crear($d);
        $this->bitacora('Huéspedes', 'Registrar huésped', "{$d['nombres']} {$d['apellidos']} · doc. {$d['nro_documento']}");
        $destino = $this->solicitud->entrada('volver_reserva') ? '/panel/reservas/nueva?huesped=' . $id : '/panel/huespedes/' . $id;
        return $this->redirigir($destino, 'Huésped registrado correctamente.');
    }

    public function ver(string $id): Respuesta
    {
        $huesped = HuespedRepositorio::buscar((int) $id) ?? throw new ExcepcionHttp(404, 'El huésped no existe.');
        $historial = HuespedRepositorio::historial((int) $id);
        $finalizadas = array_filter($historial, fn ($h) => $h['estado'] === 'FINALIZADA');
        $resumen = [
            'estadias' => count($finalizadas),
            'noches' => array_sum(array_column($finalizadas, 'noches')),
            'gasto' => array_sum(array_map(fn ($h) => (float) $h['monto_total'] + (float) $h['cargos_extra'], $finalizadas)),
            'cancelaciones' => count(array_filter($historial, fn ($h) => in_array($h['estado'], ['CANCELADA', 'NO_SHOW'], true))),
            'proxima' => current(array_filter(array_reverse($historial), fn ($h) => in_array($h['estado'], ['PENDIENTE', 'CONFIRMADA', 'EN_CURSO'], true))) ?: null,
        ];
        return $this->vista('huespedes/ver', ['titulo' => $huesped['nombres'] . ' ' . $huesped['apellidos'], 'huesped' => $huesped, 'historial' => $historial, 'resumen' => $resumen]);
    }

    public function editar(string $id): Respuesta
    {
        $huesped = HuespedRepositorio::buscar((int) $id) ?? throw new ExcepcionHttp(404);
        return $this->vista('huespedes/formulario', ['titulo' => 'Editar huésped', 'huesped' => $huesped, 'tipos' => HuespedRepositorio::tiposDocumento(), 'documento' => null]);
    }

    public function actualizar(string $id): Respuesta
    {
        $huesped = HuespedRepositorio::buscar((int) $id) ?? throw new ExcepcionHttp(404);
        $d = HuespedRepositorio::validar($this->solicitud->todo(), (int) $id);
        HuespedRepositorio::actualizar((int) $id, $d);
        $this->bitacora('Huéspedes', 'Modificar huésped', "{$huesped['nombres']} {$huesped['apellidos']}");
        return $this->redirigir('/panel/huespedes/' . $id, 'Datos del huésped actualizados.');
    }

    /** API para el asistente de reservas: búsqueda por documento o nombre. */
    public function buscar(): Respuesta
    {
        $q = (string) $this->solicitud->consulta('q', '');
        return $this->json(['ok' => true, 'huespedes' => mb_strlen($q) >= 2 ? HuespedRepositorio::buscarTexto($q) : []]);
    }

    /** API: registro rápido desde el asistente de reserva (CU-05 «extend» CU-11). */
    public function guardarRapido(): Respuesta
    {
        try {
            $d = HuespedRepositorio::validar($this->solicitud->todo());
        } catch (ExcepcionValidacion $e) {
            if ($e->existente) {
                return $this->json(['ok' => true, 'existente' => true, 'mensaje' => 'El documento ya estaba registrado: se usará ese huésped.', 'huesped' => $e->existente]);
            }
            throw $e;
        }
        $id = HuespedRepositorio::crear($d);
        $this->bitacora('Huéspedes', 'Registrar huésped', "{$d['nombres']} {$d['apellidos']} (desde una reserva)");
        return $this->json(['ok' => true, 'mensaje' => 'Huésped registrado.', 'huesped' => HuespedRepositorio::buscar($id)]);
    }
}
