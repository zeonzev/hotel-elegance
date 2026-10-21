<div class="vacio">
    <div class="vacio-icono"><i class="bi <?= e($icono ?? 'bi-inbox') ?>"></i></div>
    <h3><?= e($titulo ?? 'Sin resultados') ?></h3>
    <p><?= e($texto ?? 'No hay registros que coincidan con los filtros aplicados.') ?></p>
    <?php if (!empty($accion)): ?>
        <a href="<?= e($accion['url']) ?>" class="btn btn-primary"><i class="bi <?= e($accion['icono'] ?? 'bi-plus-lg') ?>"></i> <?= e($accion['texto']) ?></a>
    <?php endif; ?>
</div>
