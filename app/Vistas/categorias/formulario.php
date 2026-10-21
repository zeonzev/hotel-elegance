<?php
/** @var \App\Core\Vista $this */
$v = fn (string $c, $d = '') => anterior($c, $categoria[$c] ?? $d);
echo $this->parcial('parciales/encabezado', [
    'titulo' => $titulo,
    'cu' => 'CU-09',
    'descripcion' => 'La capacidad y la tarifa base deben ser mayores a cero.',
    'migas' => [['Categorías', '/panel/categorias'], [$categoria ? 'Editar' : 'Nueva', null]],
]);
?>
<form method="post" action="<?= $categoria ? url('/panel/categorias/' . $categoria['id_categoria']) : url('/panel/categorias') ?>" class="validar" novalidate>
    <?= csrf_campo() ?>
    <div class="row g-4-5">
        <div class="col-xl-8">
            <div class="tarjeta">
                <div class="tarjeta-cuerpo row g-3">
                    <div class="col-md-6"><label class="form-label" for="nombre">Nombre *</label><input class="form-control<?= clase_error('nombre') ?>" id="nombre" name="nombre" value="<?= e($v('nombre')) ?>" required maxlength="60"><?= mensaje_error('nombre') ?></div>
                    <div class="col-md-3"><label class="form-label" for="capacidad">Capacidad *</label><input type="number" class="form-control<?= clase_error('capacidad') ?>" id="capacidad" name="capacidad" value="<?= e($v('capacidad', 2)) ?>" min="1" max="10" required><?= mensaje_error('capacidad') ?></div>
                    <div class="col-md-3"><label class="form-label" for="tarifa_base">Tarifa base (Bs) *</label><input type="number" step="0.01" class="form-control<?= clase_error('tarifa_base') ?>" id="tarifa_base" name="tarifa_base" value="<?= e($v('tarifa_base')) ?>" min="1" required><?= mensaje_error('tarifa_base') ?></div>
                    <div class="col-md-6"><label class="form-label" for="camas">Camas *</label><input class="form-control<?= clase_error('camas') ?>" id="camas" name="camas" value="<?= e($v('camas')) ?>" required maxlength="80" placeholder="1 cama Queen"></div>
                    <div class="col-md-6"><label class="form-label" for="superficie_m2">Superficie (m²)</label><input type="number" class="form-control" id="superficie_m2" name="superficie_m2" value="<?= e($v('superficie_m2')) ?>" min="1"></div>
                    <div class="col-12"><label class="form-label" for="descripcion">Descripción *</label><textarea class="form-control<?= clase_error('descripcion') ?>" id="descripcion" name="descripcion" rows="3" required maxlength="600"><?= e($v('descripcion')) ?></textarea><?= mensaje_error('descripcion') ?></div>
                    <div class="col-12"><label class="form-label" for="comodidades">Comodidades <span class="opcional">(una por línea)</span></label><textarea class="form-control" id="comodidades" name="comodidades" rows="4"><?= e(str_replace('|', "\n", (string) $v('comodidades'))) ?></textarea></div>
                    <div class="col-12">
                        <label class="form-label">Imagen</label>
                        <div class="row g-2">
                            <?php foreach ($imagenes as $img): ?>
                                <div class="col-4 col-md-3 col-lg-2"><label class="opcion-tarjeta"><input type="radio" name="imagen" value="<?= e($img) ?>"<?= marcado($v('imagen', 'detalle-1.jpg') === $img) ?>><span class="contenido p-1"><img src="<?= asset('img/habitaciones/' . $img) ?>" alt="" class="w-100 rounded-2" style="height:64px;object-fit:cover"></span></label></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="estado" name="estado" value="1"<?= marcado($categoria ? $categoria['estado'] : 1) ?>><label class="form-check-label" for="estado">Categoría activa (visible en el sitio y en nuevas habitaciones)</label></div></div>
                </div>
                <div class="tarjeta-pie d-flex justify-content-end gap-2"><a class="btn btn-claro" href="<?= url('/panel/categorias') ?>">Cancelar</a><button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> Guardar</button></div>
            </div>
        </div>
    </div>
</form>
