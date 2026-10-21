<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseDatos;
use App\Core\Respuesta;
use App\Servicios\BitacoraServicio;
use App\Servicios\ExportadorServicio;

/**
 * Bitácora / auditoría de acciones importantes.
 */
final class BitacoraController extends Controlador
{
    public function index(): Respuesta
    {
        $filtros = $this->solicitud->solo(['q', 'modulo', 'usuario', 'desde', 'hasta']);
        if ($this->solicitud->consulta('formato') === 'csv') {
            $filas = BaseDatos::filas('SELECT b.fecha, u.nombre_usuario, b.modulo, b.accion, b.descripcion, b.ip FROM bitacora b LEFT JOIN usuario u ON u.id_usuario = b.id_usuario ORDER BY b.fecha DESC LIMIT 5000');
            return ExportadorServicio::csv('bitacora', ['Fecha', 'Usuario', 'Módulo', 'Acción', 'Descripción', 'IP'], array_map('array_values', $filas));
        }
        [$registros, $paginador] = BitacoraServicio::listar($filtros, $this->entero('pagina', 1));
        return $this->vista('bitacora/index', [
            'titulo' => 'Bitácora', 'registros' => $registros, 'paginador' => $paginador, 'filtros' => $filtros,
            'modulos' => BitacoraServicio::modulos(),
            'usuarios' => BaseDatos::filas('SELECT id_usuario, nombre_usuario FROM usuario ORDER BY nombre_usuario'),
        ]);
    }
}
