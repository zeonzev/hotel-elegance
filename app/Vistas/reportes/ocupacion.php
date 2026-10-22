<?php
/** @var \App\Core\Vista $this */
$r = $d['resumen'];
if ($imprimir) { echo '<div class="documento" style="max-width:1100px">'; }
if (!$imprimir) {
    echo $this->parcial('parciales/encabezado', [
        'titulo' => 'Reporte de ocupación', 'cu' => 'CU-22',
        'descripcion' => 'Noches ocupadas ÷ noches disponibles, por día y por categoría, del ' . fecha($desde, 'larga') . ' al ' . fecha($hasta, 'larga') . '.',
        'migas' => [['Reportes', '/panel/reportes'], ['Ocupación', null]],
    ]);
}
echo $this->parcial('reportes/filtros', ['ruta' => '/panel/reportes/ocupacion', 'desde' => $desde, 'hasta' => $hasta, 'imprimir' => $imprimir]);
?>
<?php if ($imprimir): ?><h1 class="serif mb-1">Reporte de ocupación</h1><p class="texto-suave">Periodo: <?= e(fecha($desde, 'larga')) ?> al <?= e(fecha($hasta, 'larga')) ?> · <?= (int) $r['habitaciones'] ?> habitaciones · generado el <?= e(fecha(date('Y-m-d H:i'), 'hora')) ?></p><?php endif; ?>
<div class="row g-4-5 mb-4">
    <?php foreach ([['Ocupación promedio', numero($r['porcentaje'], 1) . ' %', $r['dias'] . ' días · ' . $r['habitaciones'] . ' habitaciones', 'bi-buildings', 'chip-noche'], ['Noches vendidas', numero($r['ocupadas']), 'de ' . numero($r['disponibles']) . ' disponibles', 'bi-moon-stars', 'chip-oro'], ['Día de mayor ocupación', numero($r['maximo'], 0) . ' %', $r['dia_maximo'] ? fecha($r['dia_maximo'], 'larga') : '—', 'bi-arrow-up-circle', 'chip-rojo'], ['ADR · RevPAR', bs($r['adr'], 0) . ' · ' . bs($r['revpar'], 0), 'tarifa promedio · ingreso por hab. disponible', 'bi-graph-up', 'chip-verde']] as [$t, $v, $s, $i, $c]): ?>
        <div class="col-sm-6 col-xl-3"><div class="tarjeta kpi"><div class="kpi-cabecera"><span class="kpi-titulo"><?= e($t) ?></span><span class="icono-chip <?= $c ?>"><i class="bi <?= $i ?>"></i></span></div><div class="kpi-valor" style="font-size:1.55rem"><?= e($v) ?></div><div class="kpi-pie"><?= e($s) ?></div></div></div>
    <?php endforeach; ?>
</div>
<?php if ($r['ocupadas'] === 0): ?>
    <div class="tarjeta"><?= $this->parcial('parciales/vacio', ['icono' => 'bi-calendar-x', 'titulo' => 'Periodo sin datos', 'texto' => 'No hay reservas en el periodo seleccionado (CU-22, flujo 1a).']) ?></div>
<?php else: ?>
<div class="tarjeta mb-4">
    <div class="tarjeta-encabezado"><div><h2>Ocupación diaria</h2><div class="subtitulo">Porcentaje de habitaciones ocupadas cada noche</div></div></div>
    <div class="tarjeta-cuerpo"><div class="grafico grafico-lg"><canvas id="g-diario"></canvas></div></div>
</div>
<div class="row g-4-5 mb-4">
    <div class="col-xl-7">
        <div class="tarjeta h-100">
            <div class="tarjeta-encabezado"><div><h2>Por categoría</h2></div></div>
            <div class="tabla-contenedor">
                <table class="tabla">
                    <thead><tr><th>Categoría</th><th class="text-end">Habitaciones</th><th class="text-end">Noches ocupadas</th><th class="text-end">Noches disponibles</th><th style="width:35%">Ocupación</th></tr></thead>
                    <tbody><?php foreach ($d['categorias'] as $c): ?>
                        <tr><td class="fw-medio"><?= e($c['nombre']) ?></td><td class="text-end tabular"><?= $c['habitaciones'] ?></td><td class="text-end tabular"><?= numero($c['ocupadas']) ?></td><td class="text-end tabular"><?= numero($c['disponibles']) ?></td>
                            <td><div class="d-flex align-items-center gap-2"><div class="barra-progreso flex-grow-1"><span style="width:<?= min(100, $c['porcentaje']) ?>%"></span></div><strong class="tabular" style="width:56px;text-align:right"><?= numero($c['porcentaje'], 1) ?> %</strong></div></td></tr>
                    <?php endforeach; ?></tbody>
                    <tfoot><tr><td>Total</td><td class="text-end"><?= $r['habitaciones'] ?></td><td class="text-end"><?= numero($r['ocupadas']) ?></td><td class="text-end"><?= numero($r['disponibles']) ?></td><td class="text-end"><?= numero($r['porcentaje'], 1) ?> %</td></tr></tfoot>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="tarjeta h-100">
            <div class="tarjeta-encabezado"><div><h2>Por día de la semana</h2><div class="subtitulo">Ocupación promedio</div></div></div>
            <div class="tarjeta-cuerpo"><div class="grafico grafico-sm"><canvas id="g-semana"></canvas></div></div>
        </div>
    </div>
</div>
<div class="tarjeta">
    <div class="tarjeta-encabezado"><div><h2>Detalle por día</h2></div></div>
    <div class="tabla-contenedor" style="<?= $imprimir ? '' : 'max-height:420px' ?>">
        <table class="tabla tabla-compacta">
            <thead><tr><th>Fecha</th><th>Día</th><th class="text-end">Ocupadas</th><th class="text-end">Disponibles</th><th class="text-end">Ocupación</th></tr></thead>
            <tbody><?php foreach ($d['diario'] as $f => $o): ?>
                <tr><td class="tabular"><?= e(fecha($f)) ?></td><td><?= e(DIAS[(int) date('N', strtotime($f))]) ?></td><td class="text-end tabular"><?= $o['ocupadas'] ?></td><td class="text-end tabular"><?= $o['capacidad'] ?></td><td class="text-end tabular fw-semibold"><?= numero($o['porcentaje'], 1) ?> %</td></tr>
            <?php endforeach; ?></tbody>
        </table>
    </div>
</div>
<?php endif; ?>
<?php if ($imprimir) { echo '</div>'; } ?>

<?php $this->seccion('scripts') ?>
<script src="<?= vendor('chartjs/chart.umd.min.js') ?>"></script>
<script>
(function () {
    var diario = <?= json_encode(array_map(fn ($f, $o) => ['f' => $f, 'p' => $o['porcentaje']], array_keys($d['diario']), $d['diario'])) ?>;
    var semana = <?= json_encode(array_values(array_map(fn ($v) => round($v, 1), $d['semana']))) ?>;
    var animar = <?= $imprimir ? 'false' : 'true' ?>;
    var lienzo = document.getElementById('g-diario');
    if (lienzo) HE.grafico(lienzo, function (c) {
        return { type: diario.length > 45 ? 'line' : 'bar',
            data: { labels: diario.map(function (x) { var d = new Date(x.f + 'T12:00:00'); return d.getDate() + '/' + (d.getMonth() + 1); }),
                datasets: [{ label: 'Ocupación', data: diario.map(function (x) { return x.p; }), borderColor: c.noche, backgroundColor: diario.length > 45 ? HE.degradado(lienzo, c.noche, .2) : HE.alfa(c.oscuro ? '#7FB0E3' : '#1F3452', .85), fill: true, tension: .3, pointRadius: 0, borderRadius: 4 }] },
            options: { animation: animar, plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (x) { return x.parsed.y.toFixed(1) + ' %'; } } } },
                scales: { y: { min: 0, max: 100, ticks: { callback: function (v) { return v + ' %'; } }, grid: { color: c.rejilla } }, x: { grid: { display: false }, ticks: { autoSkipPadding: 14, maxRotation: 0 } } } } };
    });
    var g2 = document.getElementById('g-semana');
    if (g2) HE.grafico(g2, function (c) {
        return { type: 'bar', data: { labels: ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'], datasets: [{ data: semana, backgroundColor: semana.map(function (v, i) { return i >= 4 ? c.oro : HE.alfa(c.oscuro ? '#7FB0E3' : '#1F3452', .8); }), borderRadius: 8 }] },
            options: { animation: animar, plugins: { legend: { display: false } }, scales: { y: { min: 0, max: 100, ticks: { callback: function (v) { return v + ' %'; } }, grid: { color: c.rejilla } }, x: { grid: { display: false } } } } };
    });
})();
</script>
<?php $this->fin() ?>
