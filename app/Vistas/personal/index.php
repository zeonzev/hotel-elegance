<?php
/** @var \App\Core\Vista $this */
$nuevo = (int) \App\Core\Sesion::flashActual('nuevo_empleado', 0);
echo $this->parcial('parciales/encabezado', [
    'titulo' => 'Personal del hotel',
    'cu' => 'CU-01 · CU-02',
    'descripcion' => 'Registro de empleados con sus datos personales y laborales. Desde aquí se crea la cuenta de acceso de cada uno.',
    'migas' => [['Administración', null], ['Personal', null]],
    'acciones' => '<a class="btn btn-claro" href="' . url('/panel/usuarios') . '"><i class="bi bi-shield-lock"></i> Usuarios y roles</a><a class="btn btn-primary" href="' . url('/panel/personal/nuevo') . '"><i class="bi bi-person-plus"></i> Nuevo empleado</a>',
]);
?>
<div class="row g-4-5 mb-4">
    <?php foreach ([['Empleados registrados', $conteos['total'] ?? 0, 'bi-people', 'chip-noche'], ['Activos', $conteos['activos'] ?? 0, 'bi-person-check', 'chip-verde'], ['Con cuenta de acceso', $conteos['cuentas'] ?? 0, 'bi-key', 'chip-oro']] as [$t, $v, $i, $c]): ?>
        <div class="col-sm-4"><div class="tarjeta kpi d-flex align-items-center gap-3"><span class="icono-chip <?= $c ?>"><i class="bi <?= $i ?>"></i></span><div><div class="kpi-titulo"><?= $t ?></div><div class="fs-4 fw-semibold tabular"><?= (int) $v ?></div></div></div></div>
    <?php endforeach; ?>
</div>

<div class="tarjeta">
    <form class="filtros" method="get">
        <div class="con-icono buscar-tabla"><i class="bi bi-search"></i><input class="form-control" type="search" name="q" value="<?= e($filtros['q']) ?>" placeholder="Nombre, CI o correo"></div>
        <select class="form-select w-auto" name="cargo" data-autoenviar>
            <option value="">Todos los cargos</option>
            <?php foreach ($cargos as $c): ?><option value="<?= $c['id_cargo'] ?>"<?= seleccionado($filtros['cargo'], $c['id_cargo']) ?>><?= e($c['nombre']) ?></option><?php endforeach; ?>
        </select>
        <select class="form-select w-auto" name="estado" data-autoenviar>
            <option value="">Todos los estados</option>
            <option value="ACTIVO"<?= seleccionado($filtros['estado'], 'ACTIVO') ?>>Activos</option>
            <option value="INACTIVO"<?= seleccionado($filtros['estado'], 'INACTIVO') ?>>Inactivos</option>
        </select>
        <button class="btn btn-claro" type="submit"><i class="bi bi-funnel"></i> Filtrar</button>
        <?php if (array_filter($filtros)): ?><a class="btn btn-fantasma" href="<?= url('/panel/personal') ?>">Limpiar</a><?php endif; ?>
    </form>
    <?php if ($empleados): ?>
        <div class="tabla-contenedor">
            <table class="tabla">
                <thead><tr><th>Empleado</th><th>CI</th><th>Cargo</th><th>Contacto</th><th>Contratación</th><th>Cuenta</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                <tbody>
                <?php foreach ($empleados as $e): ?>
                    <tr<?= $nuevo === (int) $e['id_empleado'] ? ' style="background: var(--he-oro-suave)"' : '' ?>>
                        <td><div class="persona"><span class="avatar avatar-sm<?= $e['estado'] === 'ACTIVO' ? '' : ' avatar-noche' ?>"><?= e(iniciales($e['nombres'] . ' ' . $e['apellidos'])) ?></span><div><div class="celda-principal"><?= e($e['nombres'] . ' ' . $e['apellidos']) ?></div><div class="celda-secundaria"><?= e($e['direccion'] ?: 'Sin dirección') ?></div></div></div></td>
                        <td class="tabular"><?= e($e['ci']) ?></td>
                        <td><?= e($e['cargo']) ?></td>
                        <td><div><?= e($e['telefono'] ?: '—') ?></div><div class="celda-secundaria"><?= e($e['correo'] ?: '') ?></div></td>
                        <td><?= e(fecha($e['fecha_contratacion'])) ?></td>
                        <td>
                            <?php if ($e['id_usuario']): ?>
                                <div class="fw-medio">@<?= e($e['nombre_usuario']) ?></div><div class="celda-secundaria"><?= e($e['rol']) ?> · <?= e(strtolower($e['estado_cuenta'])) ?></div>
                            <?php elseif ($e['estado'] === 'ACTIVO'): ?>
                                <button class="btn btn-sm btn-oro" type="button" data-bs-toggle="modal" data-bs-target="#modal-cuenta" data-empleado="<?= (int) $e['id_empleado'] ?>" data-nombre="<?= e($e['nombres'] . ' ' . $e['apellidos']) ?>"><i class="bi bi-key"></i> Crear cuenta</button>
                            <?php else: ?><span class="texto-tenue">—</span><?php endif; ?>
                        </td>
                        <td><?= insignia($e['estado']) ?></td>
                        <td class="acciones">
                            <a class="btn btn-claro btn-accion" href="<?= url('/panel/personal/' . $e['id_empleado'] . '/editar') ?>" data-bs-toggle="tooltip" title="Editar"><i class="bi bi-pencil"></i></a>
                            <form class="d-inline" method="post" action="<?= url('/panel/personal/' . $e['id_empleado'] . '/estado') ?>" data-confirmar="<?= $e['estado'] === 'ACTIVO' ? '¿Dar de baja a este empleado?' : '¿Reactivar a este empleado?' ?>" data-confirmar-texto="<?= $e['estado'] === 'ACTIVO' ? 'Su cuenta de acceso quedará deshabilitada.' : 'Volverá a figurar como personal activo.' ?>" data-confirmar-boton="<?= $e['estado'] === 'ACTIVO' ? 'Dar de baja' : 'Reactivar' ?>"<?= $e['estado'] === 'ACTIVO' ? '' : ' data-confirmar-tipo="normal" data-confirmar-icono="bi-person-check"' ?>>
                                <?= csrf_campo() ?>
                                <button class="btn btn-accion <?= $e['estado'] === 'ACTIVO' ? 'btn-peligro-suave' : 'btn-claro' ?>" type="submit" data-bs-toggle="tooltip" title="<?= $e['estado'] === 'ACTIVO' ? 'Dar de baja' : 'Reactivar' ?>"><i class="bi <?= $e['estado'] === 'ACTIVO' ? 'bi-person-dash' : 'bi-person-check' ?>"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= $this->parcial('parciales/paginacion', ['paginador' => $paginador, 'etiqueta' => 'empleados']) ?>
    <?php else: ?>
        <?= $this->parcial('parciales/vacio', ['icono' => 'bi-person-vcard', 'titulo' => 'Sin empleados', 'texto' => 'No se encontraron empleados con esos filtros.', 'accion' => ['url' => url('/panel/personal/nuevo'), 'texto' => 'Registrar empleado']]) ?>
    <?php endif; ?>
</div>

<div class="modal fade" id="modal-cuenta" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" action="<?= url('/panel/usuarios') ?>">
            <?= csrf_campo() ?>
            <input type="hidden" name="id_empleado" data-campo-empleado>
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-key texto-oro"></i> Crear cuenta de usuario</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
            <div class="modal-body">
                <p class="texto-suave fs-sm">El sistema propondrá un nombre de usuario a partir de los nombres de <strong data-nombre-empleado></strong> y generará una contraseña por defecto cifrada con <code>password_hash()</code>, que deberá cambiar en su primer ingreso (CU-02).</p>
                <label class="form-label">Rol de la cuenta (CU-03)</label>
                <div class="row g-2">
                    <?php foreach ($roles as $i => $r): ?>
                        <div class="col-12"><label class="opcion-tarjeta"><input type="radio" name="id_rol" value="<?= $r['id_rol'] ?>"<?= $r['nombre'] === 'Recepcionista' ? ' checked' : '' ?> required><span class="contenido"><strong><?= e($r['nombre']) ?></strong><span class="d-block texto-tenue fs-xs"><?= e($r['descripcion']) ?></span></span></label></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-claro" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> Crear cuenta</button></div>
        </form>
    </div>
</div>
<?php $this->seccion('scripts') ?>
<script>
document.getElementById('modal-cuenta').addEventListener('show.bs.modal', function (e) {
    var b = e.relatedTarget;
    this.querySelector('[data-campo-empleado]').value = b.dataset.empleado;
    this.querySelector('[data-nombre-empleado]').textContent = b.dataset.nombre;
});
</script>
<?php $this->fin() ?>
