<?php
/** @var \App\Core\Vista $this */
$v = fn (string $c, $d = '') => anterior($c, $habitacion[$c] ?? $d);
echo $this->parcial('parciales/encabezado', [
    'titulo' => $titulo,
    'cu' => 'CU-08',
    'descripcion' => 'Número, piso y categoría. La capacidad y la tarifa base se heredan de la categoría.',
    'migas' => [['Habitaciones', '/panel/habitaciones'], [$habitacion ? 'Editar' : 'Nueva', null]],
]);
?>
<form method="post" action="<?= $habitacion ? url('/panel/habitaciones/' . $habitacion['id_habitacion']) : url('/panel/habitaciones') ?>" class="validar" novalidate>
    <?= csrf_campo() ?>
    <div class="row g-4-5">
        <div class="col-xl-7">
            <div class="tarjeta">
                <div class="tarjeta-cuerpo">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label" for="numero">Número *</label><input class="form-control<?= clase_error('numero') ?>" id="numero" name="numero" value="<?= e($v('numero')) ?>" required maxlength="10" pattern="[0-9A-Za-z\-]{1,10}" placeholder="305"><?= mensaje_error('numero') ?><div class="invalid-feedback">Número no válido.</div></div>
                        <div class="col-md-6"><label class="form-label" for="piso">Piso *</label><input type="number" class="form-control<?= clase_error('piso') ?>" id="piso" name="piso" value="<?= e($v('piso', 1)) ?>" min="0" max="50" required><?= mensaje_error('piso') ?></div>
                        <div class="col-12">
                            <label class="form-label">Categoría *</label>
                            <div class="row g-2">
                                <?php foreach ($categorias as $c): ?>
                                    <div class="col-sm-6"><label class="opcion-tarjeta"><input type="radio" name="id_categoria" value="<?= $c['id_categoria'] ?>"<?= marcado((string) $v('id_categoria') === (string) $c['id_categoria']) ?> required>
                                        <span class="contenido d-flex gap-3 align-items-center"><img src="<?= asset('img/habitaciones/' . $c['imagen']) ?>" alt="" width="64" height="48" style="object-fit:cover;border-radius:8px"><span><strong class="d-block"><?= e($c['nombre']) ?></strong><span class="fs-xs texto-tenue"><?= (int) $c['capacidad'] ?> pers. · <?= e(bs($c['tarifa_base'], 0)) ?>/noche</span></span></span>
                                    </label></div>
                                <?php endforeach; ?>
                            </div>
                            <?= mensaje_error('id_categoria') ?>
                        </div>
                        <div class="col-12"><label class="form-label" for="descripcion">Descripción <span class="opcional">(vista, ubicación…)</span></label><input class="form-control" id="descripcion" name="descripcion" value="<?= e($v('descripcion')) ?>" maxlength="255"></div>
                    </div>
                </div>
                <div class="tarjeta-pie d-flex justify-content-end gap-2"><a class="btn btn-claro" href="<?= url('/panel/habitaciones') ?>">Cancelar</a><button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> Guardar</button></div>
            </div>
        </div>
    </div>
</form>
