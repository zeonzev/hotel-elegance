<?php /** @var \App\Core\Paginador $paginador */ ?>
<?php if ($paginador->total > 0): ?>
<div class="paginacion">
    <span>Mostrando <strong class="tabular"><?= numero($paginador->desde()) ?>–<?= numero($paginador->hasta()) ?></strong> de <strong class="tabular"><?= numero($paginador->total) ?></strong> <?= e($etiqueta ?? 'registros') ?></span>
    <?php if ($paginador->paginas > 1): ?>
        <nav aria-label="Paginación">
            <a href="<?= e($paginador->enlace($paginador->pagina - 1)) ?>" class="<?= $paginador->pagina <= 1 ? 'deshabilitado' : '' ?>" aria-label="Anterior"><i class="bi bi-chevron-left"></i></a>
            <?php foreach ($paginador->ventana() as $p): ?>
                <?php if ($p === '…'): ?>
                    <span class="pag">…</span>
                <?php else: ?>
                    <a href="<?= e($paginador->enlace($p)) ?>" class="<?= $p === $paginador->pagina ? 'actual' : '' ?>"<?= $p === $paginador->pagina ? ' aria-current="page"' : '' ?>><?= $p ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
            <a href="<?= e($paginador->enlace($paginador->pagina + 1)) ?>" class="<?= $paginador->pagina >= $paginador->paginas ? 'deshabilitado' : '' ?>" aria-label="Siguiente"><i class="bi bi-chevron-right"></i></a>
        </nav>
    <?php endif; ?>
</div>
<?php endif; ?>
