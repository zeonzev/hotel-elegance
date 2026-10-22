<?php
/** @var \App\Core\Vista $this */
$v = fn (string $c, $d = '') => anterior($c, $temporada[$c] ?? $d);
echo $this->parcial('parciales/encabezado', [
    'titulo' => $titulo,
    'cu' => 'CU-20',
    'descripcion' => 'Defina el periodo y el precio por noche de cada categoría. Si ya existe una tarifa para la categoría en esta temporada, se actualiza.',
    'migas' => [['Tarifas', '/panel/tarifas'], [$temporada ? 'Editar' : 'Nueva temporada', null]],
]);
$sugerencias = ['ALTA' => 1.15, 'BAJA' => 0.9, 'ESPECIAL' => 1.3];
?>
<form method="post" action="<?= $temporada ? url('/panel/tarifas/' . $temporada['id_temporada']) : url('/panel/tarifas') ?>" class="validar" novalidate>
    <?= csrf_campo() ?>
    <div class="row g-4-5">
        <div class="col-xl-8">
            <div class="tarjeta">
                <div class="tarjeta-cuerpo">
                    <div class="seccion-form"><h3>Temporada</h3><p>Nombre, tipo y periodo de vigencia.</p>
                        <div class="row g-3">
                            <div class="col-md-8"><label class="form-label" for="nombre">Nombre *</label><input class="form-control<?= clase_error('nombre') ?>" id="nombre" name="nombre" value="<?= e($v('nombre')) ?>" required maxlength="80" placeholder="Ej.: Gran Poder 2027"><?= mensaje_error('nombre') ?></div>
                            <div class="col-md-4"><label class="form-label" for="color">Color</label><input type="color" class="form-control form-control-color w-100" id="color" name="color" value="<?= e($v('color', '#C6A15B')) ?>"></div>
                            <div class="col-12">
                                <label class="form-label">Tipo *</label>
                                <div class="row g-2">
                                    <?php foreach (['ALTA' => ['bi-graph-up-arrow', 'Temporada alta', 'Vacaciones e invierno'], 'BAJA' => ['bi-graph-down-arrow', 'Temporada baja', 'Época de lluvias'], 'ESPECIAL' => ['bi-stars', 'Fecha especial', 'Carnaval, Alasitas, Gran Poder…']] as $tipo => [$ico, $t, $s]): ?>
                                        <div class="col-md-4"><label class="opcion-tarjeta"><input type="radio" name="tipo" value="<?= $tipo ?>" data-factor="<?= $sugerencias[$tipo] ?>"<?= marcado($v('tipo', 'ESPECIAL') === $tipo) ?> required><span class="contenido d-flex gap-2 align-items-center"><i class="bi <?= $ico ?> fs-4 texto-oro"></i><span><strong class="d-block"><?= $t ?></strong><span class="fs-xs texto-tenue"><?= $s ?></span></span></span></label></div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="col-md-6"><label class="form-label" for="fecha_inicio">Fecha de inicio *</label><input type="date" class="form-control<?= clase_error('fecha_inicio') ?>" id="fecha_inicio" name="fecha_inicio" value="<?= e($v('fecha_inicio')) ?>" required><?= mensaje_error('fecha_inicio') ?></div>
                            <div class="col-md-6"><label class="form-label" for="fecha_fin">Fecha de fin *</label><input type="date" class="form-control<?= clase_error('fecha_fin') ?>" id="fecha_fin" name="fecha_fin" value="<?= e($v('fecha_fin')) ?>" required><?= mensaje_error('fecha_fin') ?></div>
                            <div class="col-12"><label class="form-label" for="descripcion">Descripción</label><input class="form-control" id="descripcion" name="descripcion" value="<?= e($v('descripcion')) ?>" maxlength="255"></div>
                        </div>
                    </div>
                    <div class="seccion-form"><h3>Precio por noche</h3><p>En bolivianos. <button type="button" class="btn btn-sm btn-fantasma p-0 align-baseline texto-oro" id="aplicar-factor">Proponer según el tipo</button></p>
                        <div class="row g-3">
                            <?php foreach ($categorias as $c): $campo = 'precio_' . $c['id_categoria']; ?>
                                <div class="col-sm-6 col-lg-3">
                                    <label class="form-label" for="<?= $campo ?>"><?= e($c['nombre']) ?> *</label>
                                    <div class="input-group"><span class="input-group-text">Bs</span><input type="number" step="0.01" min="1" class="form-control<?= clase_error($campo) ?>" id="<?= $campo ?>" name="<?= $campo ?>" data-base="<?= (float) $c['tarifa_base'] ?>" value="<?= e(anterior($campo, $precios[(int) $c['id_categoria']] ?? '')) ?>" required></div>
                                    <div class="form-text">Base: <?= e(bs($c['tarifa_base'], 0)) ?></div><?= mensaje_error($campo) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="estado" name="estado" value="1"<?= marcado($temporada ? $temporada['estado'] : 1) ?>><label class="form-check-label" for="estado">Temporada activa</label></div>
                </div>
                <div class="tarjeta-pie d-flex justify-content-end gap-2"><a class="btn btn-claro" href="<?= url('/panel/tarifas') ?>">Cancelar</a><button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> Guardar temporada</button></div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="aviso aviso-oro"><i class="bi bi-info-circle"></i><div><strong>¿Cómo se elige la tarifa de una noche?</strong><span class="fs-sm">Si varias temporadas cubren la misma fecha, gana la de mayor prioridad: <b>especial</b> &gt; <b>alta</b> &gt; <b>baja</b>. Sin temporada, se usa la tarifa base de la categoría. El monto de la reserva se calcula noche por noche.</span></div></div>
        </div>
    </div>
</form>
<?php $this->seccion('scripts') ?>
<script>
document.getElementById('aplicar-factor').addEventListener('click', function () {
    var tipo = document.querySelector('input[name=tipo]:checked');
    var f = tipo ? parseFloat(tipo.dataset.factor) : 1;
    document.querySelectorAll('[data-base]').forEach(function (c) { c.value = Math.round(parseFloat(c.dataset.base) * f / 10) * 10; });
});
</script>
<?php $this->fin() ?>
