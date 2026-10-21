<?php
/** @var \App\Core\Vista $this */
$total = array_sum(array_map('count', $resultados));
echo $this->parcial('parciales/encabezado', [
    'titulo' => 'Resultados de búsqueda',
    'descripcion' => $q !== '' ? "$total coincidencia(s) para «{$q}»." : 'Escriba al menos 2 caracteres en el buscador superior.',
    'migas' => [['Búsqueda', null]],
]);
$grupos = [['huespedes', 'Huéspedes', 'bi-person', 'chip-oro'], ['reservas', 'Reservas', 'bi-journal-bookmark', 'chip-azul'], ['habitaciones', 'Habitaciones', 'bi-door-closed', 'chip-verde']];
?>
<?php if ($total === 0): ?>
    <div class="tarjeta"><?= $this->parcial('parciales/vacio', ['icono' => 'bi-search', 'titulo' => 'Sin coincidencias', 'texto' => 'Pruebe con el número de documento, el código de reserva (HE-…) o el número de habitación.']) ?></div>
<?php else: ?>
    <div class="row g-4-5">
        <?php foreach ($grupos as [$clave, $titulo, $icono, $chip]): if (!$resultados[$clave]) continue; ?>
            <div class="col-xl-4">
                <div class="tarjeta h-100">
                    <div class="tarjeta-encabezado"><h2><?= $titulo ?></h2><span class="insignia insignia-gris"><?= count($resultados[$clave]) ?></span></div>
                    <?php foreach ($resultados[$clave] as $r): ?>
                        <a class="item-lista" href="<?= e($r['url']) ?>"><span class="icono-chip <?= $chip ?>" style="width:34px;height:34px"><i class="bi <?= $icono ?>"></i></span><span class="min-w-0"><span class="d-block fw-medio truncar"><?= e($r['titulo']) ?></span><span class="d-block fs-xs texto-tenue truncar"><?= e($r['detalle']) ?></span></span></a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
