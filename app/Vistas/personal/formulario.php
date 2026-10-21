<?php
/** @var \App\Core\Vista $this */
$v = fn (string $c, $d = '') => anterior($c, $empleado[$c] ?? $d);
$accion = $empleado ? url('/panel/personal/' . $empleado['id_empleado']) : url('/panel/personal');
echo $this->parcial('parciales/encabezado', [
    'titulo' => $titulo,
    'cu' => 'CU-01',
    'descripcion' => 'Los campos marcados con * son obligatorios. El CI y el correo no pueden repetirse.',
    'migas' => [['Personal', '/panel/personal'], [$empleado ? 'Editar' : 'Nuevo', null]],
]);
?>
<form method="post" action="<?= $accion ?>" class="validar" novalidate>
    <?= csrf_campo() ?>
    <div class="row g-4-5">
        <div class="col-xl-8">
            <div class="tarjeta">
                <div class="tarjeta-cuerpo">
                    <div class="seccion-form">
                        <h3>Datos personales</h3>
                        <p>Información de identificación del empleado.</p>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="ci">CI *</label>
                                <input class="form-control<?= clase_error('ci') ?>" id="ci" name="ci" value="<?= e($v('ci')) ?>" required maxlength="20" pattern="[A-Za-z0-9\-]{4,20}" placeholder="6034871">
                                <?= mensaje_error('ci') ?><div class="invalid-feedback">Ingrese un CI válido.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="nombres">Nombres *</label>
                                <input class="form-control<?= clase_error('nombres') ?>" id="nombres" name="nombres" value="<?= e($v('nombres')) ?>" required maxlength="80">
                                <?= mensaje_error('nombres') ?><div class="invalid-feedback">Ingrese los nombres.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="apellidos">Apellidos *</label>
                                <input class="form-control<?= clase_error('apellidos') ?>" id="apellidos" name="apellidos" value="<?= e($v('apellidos')) ?>" required maxlength="80">
                                <?= mensaje_error('apellidos') ?><div class="invalid-feedback">Ingrese los apellidos.</div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label" for="direccion">Dirección <span class="opcional">(opcional)</span></label>
                                <input class="form-control<?= clase_error('direccion') ?>" id="direccion" name="direccion" value="<?= e($v('direccion')) ?>" maxlength="200" placeholder="Calle, número y zona">
                                <?= mensaje_error('direccion') ?>
                            </div>
                        </div>
                    </div>
                    <div class="seccion-form">
                        <h3>Contacto</h3>
                        <p>Medios para comunicarse con el empleado.</p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="telefono">Teléfono <span class="opcional">(opcional)</span></label>
                                <div class="con-icono"><i class="bi bi-telephone"></i><input class="form-control<?= clase_error('telefono') ?>" id="telefono" name="telefono" value="<?= e($v('telefono')) ?>" maxlength="25" placeholder="+591 7xxxxxxx"></div>
                                <?= mensaje_error('telefono') ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="correo">Correo <span class="opcional">(opcional)</span></label>
                                <div class="con-icono"><i class="bi bi-envelope"></i><input type="email" class="form-control<?= clase_error('correo') ?>" id="correo" name="correo" value="<?= e($v('correo')) ?>" maxlength="120" placeholder="nombre@hotelelegance.bo"></div>
                                <?= mensaje_error('correo') ?><div class="invalid-feedback">Ingrese un correo válido.</div>
                            </div>
                        </div>
                    </div>
                    <div class="seccion-form">
                        <h3>Datos laborales</h3>
                        <p>Cargo dentro del organigrama del hotel.</p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="id_cargo">Cargo *</label>
                                <select class="form-select<?= clase_error('id_cargo') ?>" id="id_cargo" name="id_cargo" required>
                                    <option value="">Seleccione…</option>
                                    <?php foreach ($cargos as $c): ?><option value="<?= $c['id_cargo'] ?>"<?= seleccionado($v('id_cargo'), $c['id_cargo']) ?>><?= e($c['nombre']) ?></option><?php endforeach; ?>
                                </select>
                                <?= mensaje_error('id_cargo') ?><div class="invalid-feedback">Seleccione el cargo.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="fecha_contratacion">Fecha de contratación *</label>
                                <input type="date" class="form-control<?= clase_error('fecha_contratacion') ?>" id="fecha_contratacion" name="fecha_contratacion" value="<?= e($v('fecha_contratacion', hoy())) ?>" max="<?= hoy() ?>" required>
                                <?= mensaje_error('fecha_contratacion') ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tarjeta-pie d-flex justify-content-end gap-2">
                    <a href="<?= url('/panel/personal') ?>" class="btn btn-claro">Cancelar</a>
                    <button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> <?= $empleado ? 'Guardar cambios' : 'Registrar empleado' ?></button>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="tarjeta tarjeta-noche">
                <div class="tarjeta-cuerpo">
                    <span class="icono-chip chip-oro mb-3"><i class="bi bi-diagram-3"></i></span>
                    <h3 class="h4">Flujo del registro</h3>
                    <ol class="fs-sm texto-suave ps-3 mb-0" style="line-height:1.9">
                        <li>Se registra al empleado con estado <strong class="text-white">ACTIVO</strong>.</li>
                        <li>Desde la lista de personal se le crea su cuenta (CU-02).</li>
                        <li>Al crearla se le asigna un rol: Administrador, Recepcionista o Gerente (CU-03).</li>
                        <li>En su primer ingreso deberá cambiar la contraseña por defecto (CU-04).</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</form>
