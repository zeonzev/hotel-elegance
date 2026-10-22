<?php
/** @var \App\Core\Vista $this */
$r = $d['resumen'];
$nac = $d['origen']['nacional'];
$ext = $d['origen']['extranjero'];
if ($imprimir) { echo '<div class="documento" style="max-width:1100px">'; }
if (!$imprimir) {
    echo $this->parcial('parciales/encabezado', [
        'titulo' => 'Procedencia de los huéspedes',
        'descripcion' => 'De qué países y ciudades llegan los huéspedes, según las estadías (en curso o finalizadas) con ingreso entre el ' . fecha($desde, 'larga') . ' y el ' . fecha($hasta, 'larga') . '.',
        'migas' => [['Reportes', '/panel/reportes'], ['Procedencia', null]],
    ]);
}
echo $this->parcial('reportes/filtros', ['ruta' => '/panel/reportes/procedencia', 'desde' => $desde, 'hasta' => $hasta, 'imprimir' => $imprimir]);
$paisesTop = array_slice($d['paises'], 0, 10, true);
$ciudadesTop = array_slice($d['ciudades'], 0, 10, true);
?>
<?php if ($imprimir): ?><h1 class="serif mb-1">Procedencia de los huéspedes</h1><p class="texto-suave">Periodo: <?= e(fecha($desde, 'larga')) ?> al <?= e(fecha($hasta, 'larga')) ?></p><?php endif; ?>
<div class="row g-4-5 mb-4">
    <?php foreach ([
        ['Estadías del periodo', numero($r['estadias']), numero($r['huespedes']) . ' huéspedes distintos', 'bi-people', 'chip-noche'],
        ['Huéspedes nacionales', numero($nac['porcentaje'], 1) . ' %', numero($nac['estadias']) . ' estadías · ' . numero($nac['estancia'], 1) . ' noches promedio', 'bi-geo-alt', 'chip-verde'],
        ['Huéspedes extranjeros', numero($ext['porcentaje'], 1) . ' %', numero($ext['estadias']) . ' estadías · ' . numero($ext['estancia'], 1) . ' noches promedio', 'bi-airplane', 'chip-azul'],
        ['Principal origen extranjero', $r['pais_extranjero'] ?? '—', $r['paises'] . ' países en total', 'bi-globe-americas', 'chip-oro'],
    ] as [$t, $v, $s, $i, $c]): ?>
        <div class="col-sm-6 col-xl-3"><div class="tarjeta kpi"><div class="kpi-cabecera"><span class="kpi-titulo"><?= e($t) ?></span><span class="icono-chip <?= $c ?>"><i class="bi <?= $i ?>"></i></span></div><div class="kpi-valor" style="font-size:1.6rem"><?= e($v) ?></div><div class="kpi-pie"><?= e($s) ?></div></div></div>
    <?php endforeach; ?>
</div>
<?php if (!$r['estadias']): ?>
    <div class="tarjeta"><?= $this->parcial('parciales/vacio', ['icono' => 'bi-globe', 'titulo' => 'Periodo sin estadías', 'texto' => 'No hay estadías en curso ni finalizadas en el periodo seleccionado.']) ?></div>
<?php else: ?>
<div class="row g-4-5 mb-4">
    <div class="col-xl-4">
        <div class="tarjeta h-100">
            <div class="tarjeta-encabezado"><div><h2>Nacionales y extranjeros</h2><div class="subtitulo">Porcentaje de estadías</div></div></div>
            <div class="tarjeta-cuerpo">
                <div class="grafico grafico-sm"><canvas id="g-origen"></canvas></div>
                <table class="tabla tabla-compacta mt-3">
                    <thead><tr><th>Origen</th><th class="text-end">Estadías</th><th class="text-end">Ingreso</th></tr></thead>
                    <tbody>
                        <tr><td>Bolivia</td><td class="text-end tabular"><?= numero($nac['estadias']) ?></td><td class="text-end tabular"><?= e(bs($nac['ingreso'], 0)) ?></td></tr>
                        <tr><td>Extranjero</td><td class="text-end tabular"><?= numero($ext['estadias']) ?></td><td class="text-end tabular"><?= e(bs($ext['ingreso'], 0)) ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-8">
        <div class="tarjeta h-100">
            <div class="tarjeta-encabezado"><div><h2>Estadías por país</h2><div class="subtitulo">Los <?= count($paisesTop) ?> países con más estadías</div></div></div>
            <div class="tarjeta-cuerpo"><div class="grafico grafico-md"><canvas id="g-paises"></canvas></div></div>
        </div>
    </div>
</div>
<div class="row g-4-5">
    <div class="col-xl-5">
        <div class="tarjeta h-100">
            <div class="tarjeta-encabezado"><div><h2>Ciudades con más huéspedes</h2><div class="subtitulo">Top <?= count($ciudadesTop) ?></div></div></div>
            <div class="tarjeta-cuerpo"><div class="grafico grafico-lg"><canvas id="g-ciudades"></canvas></div></div>
        </div>
    </div>
    <div class="col-xl-7">
        <div class="tarjeta h-100">
            <div class="tarjeta-encabezado"><div><h2>Detalle por ciudad de procedencia</h2><div class="subtitulo"><?= count($d['ciudades']) ?> ciudades</div></div></div>
            <div class="tabla-contenedor" style="<?= $imprimir ? '' : 'max-height:470px' ?>">
                <table class="tabla tabla-compacta">
                    <thead><tr><th>Ciudad</th><th>País</th><th class="text-end">Estadías</th><th class="text-end">Personas</th><th class="text-end">Noches</th><th class="text-end">Ingreso</th></tr></thead>
                    <tbody><?php foreach ($d['ciudades'] as $ciudad => $c): ?>
                        <tr><td class="fw-medio"><?= e(explode(',', $ciudad)[0]) ?></td><td><?= e($c['pais']) ?></td><td class="text-end tabular"><?= numero($c['estadias']) ?></td><td class="text-end tabular"><?= numero($c['personas']) ?></td><td class="text-end tabular"><?= numero($c['noches']) ?></td><td class="text-end tabular"><?= e(bs($c['ingreso'], 0)) ?></td></tr>
                    <?php endforeach; ?></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<?php if ($imprimir) { echo '</div>'; } ?>

<?php $this->seccion('scripts') ?>
<script src="<?= vendor('chartjs/chart.umd.min.js') ?>"></script>
<script>
(function () {
    var origen = <?= json_encode([round($nac['estadias']), round($ext['estadias'])]) ?>;
    var paises = <?= json_encode(array_map(fn ($n, $p) => ['n' => $n, 'e' => $p['estadias']], array_keys($paisesTop), $paisesTop)) ?>;
    var ciudades = <?= json_encode(array_map(fn ($n, $c) => ['n' => explode(',', $n)[0], 'e' => $c['estadias'], 'b' => $c['pais'] === 'Bolivia'], array_keys($ciudadesTop), $ciudadesTop)) ?>;
    var animar = <?= $imprimir ? 'false' : 'true' ?>;
    var g1 = document.getElementById('g-origen');
    if (g1) HE.grafico(g1, function (c) {
        return { type: 'doughnut', data: { labels: ['Nacionales', 'Extranjeros'], datasets: [{ data: origen, backgroundColor: [c.verde, c.azul], borderColor: c.superficie, borderWidth: 2 }] },
            options: { animation: animar, cutout: '64%', plugins: { legend: { position: 'bottom' }, tooltip: { callbacks: { label: function (x) { var t = origen[0] + origen[1]; return x.label + ': ' + x.parsed + ' (' + Math.round(x.parsed / t * 100) + ' %)'; } } } } } };
    });
    var g2 = document.getElementById('g-paises');
    if (g2) HE.grafico(g2, function (c) {
        return { type: 'bar', data: { labels: paises.map(function (p) { return p.n; }), datasets: [{ label: 'Estadías', data: paises.map(function (p) { return p.e; }), backgroundColor: paises.map(function (p) { return p.n === 'Bolivia' ? c.verde : HE.alfa('#C6A15B', .85); }), borderRadius: 8 }] },
            options: { animation: animar, plugins: { legend: { display: false } }, scales: { y: { grid: { color: c.rejilla }, ticks: { precision: 0 } }, x: { grid: { display: false } } } } };
    });
    var g3 = document.getElementById('g-ciudades');
    if (g3) HE.grafico(g3, function (c) {
        return { type: 'bar', data: { labels: ciudades.map(function (x) { return x.n; }), datasets: [{ label: 'Estadías', data: ciudades.map(function (x) { return x.e; }), backgroundColor: ciudades.map(function (x) { return x.b ? c.verde : c.azul; }), borderRadius: 6 }] },
            options: { animation: animar, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { grid: { color: c.rejilla }, ticks: { precision: 0 } }, y: { grid: { display: false } } } } };
    });
})();
</script>
<?php $this->fin() ?>
