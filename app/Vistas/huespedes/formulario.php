<?php
/** @var \App\Core\Vista $this */
$v = fn (string $c, $d = '') => anterior($c, $huesped[$c] ?? $d);
$accion = $huesped ? url('/panel/huespedes/' . $huesped['id_huesped']) : url('/panel/huespedes');
echo $this->parcial('parciales/encabezado', [
    'titulo' => $titulo,
    'cu' => $huesped ? 'CU-06' : 'CU-05',
    'descripcion' => $huesped ? 'Actualice la información del huésped. El documento no puede pertenecer a otro huésped.' : 'Registre los datos personales del huésped. El sistema impide registrar dos veces el mismo documento.',
    'migas' => [['Huéspedes', '/panel/huespedes'], [$huesped ? 'Editar' : 'Nuevo', null]],
]);
$volver = (string) ($_GET['volver_reserva'] ?? '');
?>
<form method="post" action="<?= $accion ?>" class="validar" novalidate>
    <?= csrf_campo() ?>
    <?php if ($volver): ?><input type="hidden" name="volver_reserva" value="1"><?php endif; ?>
    <div class="row g-4-5">
        <div class="col-xl-8">
            <div class="tarjeta">
                <div class="tarjeta-cuerpo">
                    <div class="seccion-form">
                        <h3>Identificación</h3>
                        <p>Documento con el que el huésped se registra en recepción.</p>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="id_tipo_documento">Tipo de documento *</label>
                                <select class="form-select<?= clase_error('id_tipo_documento') ?>" id="id_tipo_documento" name="id_tipo_documento" required>
                                    <?php foreach ($tipos as $t): ?><option value="<?= $t['id_tipo_documento'] ?>"<?= seleccionado($v('id_tipo_documento', 1), $t['id_tipo_documento']) ?>><?= e($t['nombre']) ?> (<?= e($t['sigla']) ?>)</option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="nro_documento">Número de documento *</label>
                                <input class="form-control<?= clase_error('nro_documento') ?>" id="nro_documento" name="nro_documento" value="<?= e($v('nro_documento', $documento ?? '')) ?>" required pattern="[A-Za-z0-9\-]{4,20}" maxlength="20">
                                <?= mensaje_error('nro_documento') ?><div class="invalid-feedback">Entre 4 y 20 letras o números.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="fecha_nacimiento">Fecha de nacimiento <span class="opcional">(opcional)</span></label>
                                <input type="date" class="form-control<?= clase_error('fecha_nacimiento') ?>" id="fecha_nacimiento" name="fecha_nacimiento" value="<?= e($v('fecha_nacimiento')) ?>" max="<?= date('Y-m-d', strtotime('-16 years')) ?>">
                                <?= mensaje_error('fecha_nacimiento') ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="nombres">Nombres *</label>
                                <input class="form-control<?= clase_error('nombres') ?>" id="nombres" name="nombres" value="<?= e($v('nombres')) ?>" required maxlength="80">
                                <?= mensaje_error('nombres') ?><div class="invalid-feedback">Ingrese los nombres.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="apellidos">Apellidos *</label>
                                <input class="form-control<?= clase_error('apellidos') ?>" id="apellidos" name="apellidos" value="<?= e($v('apellidos')) ?>" required maxlength="80">
                                <?= mensaje_error('apellidos') ?><div class="invalid-feedback">Ingrese los apellidos.</div>
                            </div>
                        </div>
                    </div>
                    <div class="seccion-form">
                        <h3>Procedencia</h3>
                        <p>Se usa en los reportes de origen de los huéspedes.</p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="nacionalidad">Nacionalidad *</label>
                                <input class="form-control<?= clase_error('nacionalidad') ?>" id="nacionalidad" name="nacionalidad" list="lista-nacionalidades" value="<?= e($v('nacionalidad', 'Boliviana')) ?>" required maxlength="60">
                                <datalist id="lista-nacionalidades"><?php foreach (['Boliviana', 'Peruana', 'Argentina', 'Chilena', 'Brasileña', 'Colombiana', 'Mexicana', 'Española', 'Francesa', 'Alemana', 'Estadounidense', 'Italiana', 'Británica'] as $n): ?><option value="<?= $n ?>"><?php endforeach; ?></datalist>
                                <?= mensaje_error('nacionalidad') ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="ciudad_procedencia">Ciudad de procedencia *</label>
                                <input class="form-control<?= clase_error('ciudad_procedencia') ?>" id="ciudad_procedencia" name="ciudad_procedencia" list="lista-ciudades" value="<?= e($v('ciudad_procedencia')) ?>" required maxlength="80" placeholder="Cochabamba, Santa Cruz, Lima…">
                                <datalist id="lista-ciudades"><?php foreach (['Cochabamba', 'Santa Cruz de la Sierra', 'La Paz', 'El Alto', 'Oruro', 'Potosí', 'Sucre', 'Tarija', 'Trinidad', 'Cobija', 'Uyuni', 'Lima, Perú', 'Cusco, Perú', 'Buenos Aires, Argentina', 'Santiago, Chile', 'São Paulo, Brasil', 'Madrid, España'] as $c): ?><option value="<?= $c ?>"><?php endforeach; ?></datalist>
                                <?= mensaje_error('ciudad_procedencia') ?><div class="invalid-feedback">Indique la procedencia.</div>
                            </div>
                        </div>
                    </div>
                    <div class="seccion-form">
                        <h3>Contacto</h3>
                        <p>Para confirmar la reserva y enviar el recibo.</p>
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label" for="telefono">Teléfono / WhatsApp</label><div class="con-icono"><i class="bi bi-whatsapp"></i><input class="form-control<?= clase_error('telefono') ?>" id="telefono" name="telefono" value="<?= e($v('telefono')) ?>" maxlength="25" placeholder="+591 7xxxxxxx"></div><?= mensaje_error('telefono') ?></div>
                            <div class="col-md-6"><label class="form-label" for="correo">Correo</label><div class="con-icono"><i class="bi bi-envelope"></i><input type="email" class="form-control<?= clase_error('correo') ?>" id="correo" name="correo" value="<?= e($v('correo')) ?>" maxlength="120"></div><?= mensaje_error('correo') ?><div class="invalid-feedback">Correo no válido.</div></div>
                            <div class="col-12"><label class="form-label" for="observaciones">Observaciones <span class="opcional">(preferencias, alergias, etc.)</span></label><textarea class="form-control" id="observaciones" name="observaciones" rows="3" maxlength="1000"><?= e($v('observaciones')) ?></textarea></div>
                        </div>
                    </div>
                </div>
                <div class="tarjeta-pie d-flex justify-content-end gap-2">
                    <a href="<?= $huesped ? url('/panel/huespedes/' . $huesped['id_huesped']) : url('/panel/huespedes') ?>" class="btn btn-claro">Cancelar</a>
                    <button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> <?= $huesped ? 'Guardar cambios' : 'Registrar huésped' ?></button>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="aviso aviso-azul">
                <i class="bi bi-shield-lock"></i>
                <div><strong>Protección de datos personales</strong><span class="fs-sm">Los datos del huésped se usan sólo para la gestión de su estadía, conforme al derecho a la privacidad reconocido en la Constitución Política del Estado (art. 21 y 130).</span></div>
            </div>
        </div>
    </div>
</form>
