<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseDatos;
use App\Core\ExcepcionHttp;
use App\Core\ExcepcionValidacion;
use App\Core\Respuesta;
use App\Repositorios\EmpleadoRepositorio;

/**
 * Sprint 1 · CU-01 Registrar personal.
 */
final class PersonalController extends Controlador
{
    public function index(): Respuesta
    {
        $filtros = $this->solicitud->solo(['q', 'cargo', 'estado']);
        [$empleados, $paginador] = EmpleadoRepositorio::listar($filtros, $this->entero('pagina', 1));
        return $this->vista('personal/index', [
            'titulo' => 'Personal', 'empleados' => $empleados, 'paginador' => $paginador, 'filtros' => $filtros,
            'cargos' => EmpleadoRepositorio::cargos(), 'conteos' => EmpleadoRepositorio::conteos(),
            'roles' => BaseDatos::filas('SELECT * FROM rol WHERE estado = 1 ORDER BY id_rol'),
        ]);
    }

    public function crear(): Respuesta
    {
        return $this->vista('personal/formulario', ['titulo' => 'Nuevo empleado', 'empleado' => null, 'cargos' => EmpleadoRepositorio::cargos()]);
    }

    public function guardar(): Respuesta
    {
        $datos = $this->datosValidados(0);
        $id = BaseDatos::insertar('empleado', $datos + ['estado' => 'ACTIVO']);
        $this->bitacora('Sesiones', 'Registrar personal', "Empleado {$datos['nombres']} {$datos['apellidos']} (CI {$datos['ci']})");
        \App\Core\Sesion::flash('nuevo_empleado', $id);
        return $this->redirigir('/panel/personal', 'Empleado registrado. Ya puede crearle una cuenta de usuario.');
    }

    public function editar(string $id): Respuesta
    {
        $empleado = EmpleadoRepositorio::buscar((int) $id) ?? throw new ExcepcionHttp(404);
        return $this->vista('personal/formulario', ['titulo' => 'Editar empleado', 'empleado' => $empleado, 'cargos' => EmpleadoRepositorio::cargos()]);
    }

    public function actualizar(string $id): Respuesta
    {
        $empleado = EmpleadoRepositorio::buscar((int) $id) ?? throw new ExcepcionHttp(404);
        $datos = $this->datosValidados((int) $id);
        BaseDatos::actualizar('empleado', $datos, 'id_empleado = :id', ['id' => (int) $id]);
        $this->bitacora('Sesiones', 'Modificar personal', "Empleado {$empleado['nombres']} {$empleado['apellidos']}");
        return $this->redirigir('/panel/personal', 'Datos del empleado actualizados.');
    }

    public function cambiarEstado(string $id): Respuesta
    {
        $empleado = EmpleadoRepositorio::buscar((int) $id) ?? throw new ExcepcionHttp(404);
        $nuevo = $empleado['estado'] === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO';
        if ($nuevo === 'INACTIVO' && (int) BaseDatos::valor('SELECT id_usuario FROM usuario WHERE id_empleado = ?', [(int) $id]) === \App\Core\Autenticacion::id()) {
            throw ExcepcionValidacion::campo('estado', 'No puede dar de baja su propio registro.');
        }
        BaseDatos::transaccion(function () use ($id, $nuevo) {
            BaseDatos::actualizar('empleado', ['estado' => $nuevo], 'id_empleado = :id', ['id' => (int) $id]);
            if ($nuevo === 'INACTIVO') {
                BaseDatos::consulta("UPDATE usuario SET estado = 'INACTIVO' WHERE id_empleado = ?", [(int) $id]);
            }
        });
        $this->bitacora('Sesiones', $nuevo === 'ACTIVO' ? 'Reactivar empleado' : 'Dar de baja empleado', "{$empleado['nombres']} {$empleado['apellidos']}");
        return $this->volver($nuevo === 'ACTIVO' ? 'Empleado reactivado.' : 'Empleado dado de baja; su cuenta quedó deshabilitada.');
    }

    /** CU-01 pasos 3-4: campos obligatorios y CI / correo no repetidos (flujos 4a y 4b). */
    private function datosValidados(int $excluir): array
    {
        $d = $this->validar([
            'ci'                 => 'requerido|documento',
            'nombres'            => 'requerido|nombre|max:80',
            'apellidos'          => 'requerido|nombre|max:80',
            'id_cargo'           => 'requerido|entero',
            'telefono'           => 'opcional|telefono',
            'correo'             => 'opcional|correo|max:120',
            'direccion'          => 'opcional|max:200',
            'fecha_contratacion' => 'requerido|fecha',
        ]);
        $errores = [];
        if (EmpleadoRepositorio::existeCi((string) $d['ci'], $excluir)) {
            $errores['ci'] = 'Ya existe un empleado registrado con ese CI.';
        }
        if ($d['correo'] && EmpleadoRepositorio::existeCorreo((string) $d['correo'], $excluir)) {
            $errores['correo'] = 'Ese correo ya pertenece a otro empleado.';
        }
        if ($d['fecha_contratacion'] > date('Y-m-d')) {
            $errores['fecha_contratacion'] = 'La fecha de contratación no puede ser futura.';
        }
        if (!BaseDatos::valor('SELECT 1 FROM cargo WHERE id_cargo = ?', [(int) $d['id_cargo']])) {
            $errores['id_cargo'] = 'Seleccione un cargo válido.';
        }
        if ($errores) {
            throw new ExcepcionValidacion($errores);
        }
        $d['correo'] = $d['correo'] ? mb_strtolower((string) $d['correo']) : null;
        $d['id_cargo'] = (int) $d['id_cargo'];
        return $d;
    }
}
