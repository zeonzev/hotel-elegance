<?php
/** @var \App\Core\Vista $this */
$r = $d['resumen'];
$categorias = array_keys($d['categorias']);
if ($imprimir) { echo '<div class="documento" style="max-width:1100px">'; }
if (!$imprimir) {
    echo $this->parcial('parciales/encabezado', [
        'titulo' => 'Reporte de ingresos', 'cu' => 'CU-23',
        'descripcion' => 'Ingresos estimados a partir de las reservas registradas (vista vw_ingresos_mensuales: excluye canceladas y no-show) y cobros efectivos del periodo.',
        'migas' => [['Reportes', '/panel/reportes'], ['Ingresos', null]],
    ]);
}
echo $this->parcial('reportes/filtros', ['ruta' => '/panel/reportes/ingresos', 'desde' => $desde, 'hasta' => $hasta, 'imprimir' => $imprimir]);
?>
<?php if ($imprimir): ?><h1 class="serif mb-1">Reporte de ingresos</h1><p class="texto-suave">Periodo: <?= e(fecha($desde, 'larga')) ?> al <?= e(fecha($hasta, 'larga')) ?> · montos en bolivianos</p><?php endif; ?>
<div class="row g-4-5 mb-4">
    <?php foreach ([['Ingreso estimado', bs($r['estimado'], 0), 'reservas con ingreso en el periodo', 'bi-cash-stack', 'chip-verde'], ['Cobrado en el periodo', bs($r['cobrado'], 0), 'pagos registrados', 'bi-wallet2', 'chip-oro'], ['Reservas', numero($r['reservas']), numero($r['noches']) . ' noches', 'bi-journal-bookmark', 'chip-noche'], ['Ingreso por reserva', bs($r['promedio'], 0), 'promedio', 'bi-calculator', 'chip-azul']] as [$t, $v, $s, $i, $c]): ?>
        <div class="col-sm-6 col-xl-3"><div class="tarjeta kpi"><div class="kpi-cabecera"><span class="kpi-titulo"><?= e($t) ?></span><span class="icono-chip <?= $c ?>"><i class="bi <?= $i ?>"></i></span></div><div class="kpi-valor" style="font-size:1.6rem"><?= e($v) ?></div><div class="kpi-pie"><?= e($s) ?></div></div></div>
    <?php endforeach; ?>
</div>
<?php if (!$d['meses']): ?>
    <div class="tarjeta"><?= $this->parcial('parciales/vacio', ['icono' => 'bi-cash', 'titulo' => 'Periodo sin datos', 'texto' => 'No hay reservas con ingreso en el periodo (CU-23, flujo 1a).']) ?></div>
<?php else: ?>
<div class="tarjeta mb-4">
    <div class="tarjeta-encabezado"><div><h2>Ingreso estimado por mes y categoría</h2></div></div>
    <div class="tarjeta-cuerpo"><div class="grafico grafico-lg"><canvas id="g-meses"></canvas></div></div>
    <div class="tabla-contenedor">
        <table class="tabla tabla-compacta">
            <thead><tr><th>Mes</th><?php foreach ($categorias as $c): ?><th class="text-end"><?= e($c) ?></th><?php endforeach; ?><th class="text-end">Total</th></tr></thead>
            <tbody><?php foreach ($d['meses'] as $mes => $valores): [$a, $m] = explode('-', $mes); ?>
                <tr><td><?= e(ucfirst(MESES[(int) $m]) . ' ' . $a) ?></td><?php foreach ($categorias as $c): ?><td class="text-end tabular"><?= e(bs($valores[$c] ?? 0, 0)) ?></td><?php endforeach; ?><td class="text-end tabular fw-semibold"><?= e(bs(array_sum($valores), 0)) ?></td></tr>
            <?php endforeach; ?></tbody>
            <tfoot><tr><td>Total</td><?php foreach ($categorias as $c): ?><td class="text-end"><?= e(bs($d['categorias'][$c]['ingreso'], 0)) ?></td><?php endforeach; ?><td class="text-end"><?= e(bs($r['estimado'], 0)) ?></td></tr></tfoot>
        </table>
    </div>
</div>
<div class="row g-4-5">
    <div class="col-xl-4">
        <div class="tarjeta h-100"><div class="tarjeta-encabezado"><div><h2>Por canal de reserva</h2></div></div>
            <?php foreach ($d['canales'] as $c): $p = $r['estimado'] ? (float) $c['total'] / $r['estimado'] * 100 : 0; ?>
                <div class="item-lista d-block"><div class="d-flex justify-content-between fs-sm"><span class="fw-medio"><?= e($c['nombre']) ?> <span class="texto-tenue">· <?= (int) $c['reservas'] ?></span></span><span class="tabular"><?= e(bs($c['total'], 0)) ?></span></div><div class="barra-progreso mt-2"><span style="width:<?= $p ?>%"></span></div></div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="tarjeta h-100"><div class="tarjeta-encabezado"><div><h2>Cobros por método de pago</h2></div></div>
            <div class="tarjeta-cuerpo"><div class="grafico grafico-sm"><canvas id="g-metodos"></canvas></div></div>
            <?php foreach ($d['metodos'] as $m): ?><div class="item-lista py-2 fs-sm"><span class="flex-grow-1"><?= e($m['nombre']) ?> <span class="texto-tenue">· <?= (int) $m['pagos'] ?> pagos</span></span><span class="tabular fw-semibold"><?= e(bs($m['total'], 0)) ?></span></div><?php endforeach; ?>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="tarjeta h-100"><div class="tarjeta-encabezado"><div><h2>Cobros por concepto</h2></div></div>
            <?php foreach ($d['conceptos'] as $c): ?><div class="item-lista py-2 fs-sm"><span class="flex-grow-1"><?= e(ucfirst(strtolower($c['concepto']))) ?></span><span class="tabular fw-semibold"><?= e(bs($c['total'], 0)) ?></span></div><?php endforeach; ?>
            <div class="tarjeta-cuerpo fs-xs texto-tenue">El ingreso estimado se asigna al mes de ingreso de la reserva; lo cobrado, a la fecha del pago.</div>
        </div>
    </div>
</div>
<?php endif; ?>
<?php if ($imprimir) { echo '</div>'; } ?>
<?php $this->seccion('scripts') ?>
<script src="<?= vendor('chartjs/chart.umd.min.js') ?>"></script>
<script>
(function () {
    var meses = <?= json_encode($d['meses']) ?>;
    var categorias = <?= json_encode($categorias) ?>;
    var metodos = <?= json_encode($d['metodos']) ?>;
    var nombres = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    var animar = <?= $imprimir ? 'false' : 'true' ?>;
    var g = document.getElementById('g-meses');
    if (g) HE.grafico(g, function (c) {
        var claves = Object.keys(meses);
        return { type: 'bar', data: { labels: claves.map(function (k) { var p = k.split('-'); return nombres[+p[1] - 1] + ' ' + p[0].slice(2); }),
            datasets: categorias.map(function (cat, i) { return { label: cat, data: claves.map(function (k) { return meses[k][cat] || 0; }), backgroundColor: c.serie[i % c.serie.length], borderRadius: 4, stack: 's' }; }) },
            options: { animation: animar, plugins: { legend: { position: 'bottom' }, tooltip: { callbacks: { label: function (x) { return x.dataset.label + ': ' + HE.bs(x.parsed.y, 0); } } } },
                scales: { x: { stacked: true, grid: { display: false } }, y: { stacked: true, ticks: { callback: function (v) { return 'Bs ' + Math.round(v / 1000) + 'k'; } }, grid: { color: c.rejilla } } } } };
    });
    var g2 = document.getElementById('g-metodos');
    if (g2) HE.grafico(g2, function (c) {
        return { type: 'doughnut', data: { labels: metodos.map(function (m) { return m.nombre; }), datasets: [{ data: metodos.map(function (m) { return +m.total; }), backgroundColor: c.serie, borderColor: c.superficie, borderWidth: 2 }] },
            options: { animation: animar, cutout: '62%', plugins: { legend: { position: 'right' } } } };
    });
})();
</script>
<?php $this->fin() ?>
