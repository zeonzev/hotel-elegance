<?php
/**
 * Encabezado de página reutilizable.
 * $titulo, $descripcion, $migas = [['Texto', '/ruta'], ...], $cu = 'CU-05 · CU-06', $acciones (HTML)
 */
?>
<div class="pagina-encabezado">
    <div>
        <?php if (!empty($migas)): ?>
            <div class="migas mb-2">
                <a href="<?= url('/panel') ?>"><i class="bi bi-house" style="font-size:.8rem"></i></a>
                <?php foreach ($migas as [$texto, $ruta]): ?>
                    <i class="bi bi-chevron-right"></i>
                    <?php if ($ruta): ?><a href="<?= url($ruta) ?>"><?= e($texto) ?></a><?php else: ?><span><?= e($texto) ?></span><?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <h1><?= e($titulo) ?></h1>
            <?php if (!empty($cu)): ?><span class="cu-etiqueta mt-2" data-bs-toggle="tooltip" title="Casos de uso del informe"><i class="bi bi-diagram-2"></i><?= e($cu) ?></span><?php endif; ?>
        </div>
        <?php if (!empty($descripcion)): ?><p><?= e($descripcion) ?></p><?php endif; ?>
    </div>
    <?php if (!empty($acciones)): ?><div class="pagina-acciones"><?= $acciones ?></div><?php endif; ?>
</div>
