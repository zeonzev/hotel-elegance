<?php
/**
 * Barra de periodo y exportación común a los tres reportes.
 * $ruta, $desde, $hasta, $imprimir
 */
$rangos = \App\Servicios\ReporteServicio::rangos();
$params = ['desde' => $desde, 'hasta' => $hasta];
?>
<?php if ($imprimir): ?>
    <?= $this->parcial('parciales/membrete') ?>
<?php else: ?>
    <div class="tarjeta mb-4 no-imprimir">
        <form class="filtros border-0" method="get">
            <div class="segmentos">
                <?php foreach ($rangos as $clave => [$texto, $d, $h]): ?>
                    <a class="<?= $desde === $d && $hasta === $h ? 'active' : '' ?>" href="<?= url($ruta, ['rango' => $clave]) ?>"><?= e($texto) ?></a>
                <?php endforeach; ?>
            </div>
            <div class="d-flex align-items-center gap-2">
                <input type="date" class="form-control w-auto<?= clase_error('desde') ?>" name="desde" value="<?= e($desde) ?>" aria-label="Desde">
                <span class="texto-tenue">→</span>
                <input type="date" class="form-control w-auto<?= clase_error('hasta') ?>" name="hasta" value="<?= e($hasta) ?>" aria-label="Hasta">
                <button class="btn btn-claro" type="submit"><i class="bi bi-funnel"></i> Aplicar</button>
            </div>
            <div class="ms-auto d-flex gap-2">
                <a class="btn btn-claro" href="<?= url($ruta, $params + ['formato' => 'csv']) ?>"><i class="bi bi-filetype-csv"></i> CSV</a>
                <a class="btn btn-claro" href="<?= url($ruta, $params + ['formato' => 'xlsx']) ?>"><i class="bi bi-file-earmark-excel"></i> Excel</a>
                <a class="btn btn-primary" href="<?= url($ruta, $params + ['formato' => 'imprimir']) ?>" target="_blank" rel="noopener"><i class="bi bi-printer"></i> Imprimir / PDF</a>
            </div>
        </form>
    </div>
<?php endif; ?>
