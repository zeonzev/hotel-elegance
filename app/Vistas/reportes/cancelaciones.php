<?php
/** @var \App\Core\Vista $this */
$r = $d['resumen'];
if ($imprimir) { echo '<div class="documento" style="max-width:1100px">'; }
if (!$imprimir) {
    echo $this->parcial('parciales/encabezado', [
        'titulo' => 'Reporte de cancelaciones', 'cu' => 'CU-24',
        'descripcion' => 'Reservas canceladas en el periodo agrupadas por tipo de motivo (vista vw_reporte_cancelaciones), con su canal y anticipación.',
        'migas' => [['Reportes', '/panel/reportes'], ['Cancelaciones', null]],
    ]);
}
echo $this->parcial('reportes/filtros', ['ruta' => '/panel/reportes/cancelaciones', 'desde' => $desde, 'hasta' => $hasta, 'imprimir' => $imprimir]);
$motivos = array_filter($d['motivos'], fn ($m) => $m['cantidad'] > 0);
?>
<?php if ($imprimir): ?><h1 class="serif mb-1">Reporte de cancelaciones</h1><p class="texto-suave">Periodo: <?= e(fecha($desde, 'larga')) ?> al <?= e(fecha($hasta, 'larga')) ?></p><?php endif; ?>
<div class="row g-4-5 mb-4">
    <?php foreach ([['Cancelaciones', numero($r['cantidad']), 'registradas en el periodo', 'bi-x-octagon', 'chip-rojo'], ['Tasa de cancelación', numero($r['tasa'], 1) . ' %', 'de ' . numero($r['reservas']) . ' reservas con ingreso en el periodo', 'bi-percent', 'chip-ambar'], ['No-show', numero($r['no_show']), numero($r['tasa_no_show'], 1) . ' % de las reservas', 'bi-person-x', 'chip-violeta'], ['Monto no realizado', bs($r['monto'], 0), 'anticipación media ' . numero($r['anticipacion'], 0) . ' días', 'bi-cash-coin', 'chip-gris']] as [$t, $v, $s, $i, $c]): ?>
        <div class="col-sm-6 col-xl-3"><div class="tarjeta kpi"><div class="kpi-cabecera"><span class="kpi-titulo"><?= e($t) ?></span><span class="icono-chip <?= $c ?>"><i class="bi <?= $i ?>"></i></span></div><div class="kpi-valor" style="font-size:1.6rem"><?= e($v) ?></div><div class="kpi-pie"><?= e($s) ?></div></div></div>
    <?php endforeach; ?>
</div>
<?php if (!$d['lista']): ?>
    <div class="tarjeta"><?= $this->parcial('parciales/vacio', ['icono' => 'bi-emoji-smile', 'titulo' => 'No hubo cancelaciones', 'texto' => 'No se registraron cancelaciones en el periodo seleccionado (CU-24, flujo 1a).']) ?></div>
<?php else: ?>
<div class="row g-4-5 mb-4">
    <div class="col-xl-7">
        <div class="tarjeta h-100">
            <div class="tarjeta-encabezado"><div><h2>Por tipo de motivo</h2></div></div>
            <div class="tarjeta-cuerpo"><div class="grafico grafico-md"><canvas id="g-motivos"></canvas></div></div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="tarjeta h-100">
            <div class="tarjeta-encabezado"><div><h2>Resumen por motivo y canal</h2></div></div>
            <table class="tabla tabla-compacta">
                <thead><tr><th>Motivo</th><th class="text-end">Cantidad</th><th class="text-end">%</th><th class="text-end">Monto</th></tr></thead>
                <tbody><?php foreach ($motivos as $m): ?><tr><td><?= e($m['texto']) ?></td><td class="text-end tabular"><?= $m['cantidad'] ?></td><td class="text-end tabular"><?= numero($m['cantidad'] / $r['cantidad'] * 100, 1) ?> %</td><td class="text-end tabular"><?= e(bs($m['monto'], 0)) ?></td></tr><?php endforeach; ?></tbody>
            </table>
            <div class="tarjeta-cuerpo d-flex flex-wrap gap-2">
                <?php foreach ($d['canales'] as $canal => $n): ?><span class="insignia insignia-gris"><?= e($canal) ?> · <?= $n ?></span><?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<div class="tarjeta">
    <div class="tarjeta-encabezado"><div><h2>Detalle de reservas canceladas</h2><div class="subtitulo"><?= count($d['lista']) ?> registros</div></div></div>
    <div class="tabla-contenedor" style="<?= $imprimir ? '' : 'max-height:520px' ?>">
        <table class="tabla tabla-compacta">
            <thead><tr><th>Cancelada</th><th>Reserva</th><th>Huésped</th><th>Estadía prevista</th><th>Motivo</th><th class="text-end">Anticipación</th><th class="text-end">Monto</th></tr></thead>
            <tbody><?php foreach ($d['lista'] as $c): ?>
                <tr>
                    <td class="tabular text-nowrap"><?= e(fecha($c['fecha_cancelacion'])) ?><div class="celda-secundaria">@<?= e($c['usuario'] ?? '—') ?></div></td>
                    <td><?php if ($imprimir): ?><?= e($c['codigo']) ?><?php else: ?><a class="etiqueta-codigo" href="<?= url('/panel/reservas/' . $c['id_reserva']) ?>"><?= e($c['codigo']) ?></a><?php endif; ?><div class="celda-secundaria"><?= e($c['canal']) ?></div></td>
                    <td><?= e($c['huesped']) ?><div class="celda-secundaria"><?= e($c['nacionalidad']) ?></div></td>
                    <td class="text-nowrap"><?= e(fecha($c['fecha_ingreso'])) ?> → <?= e(fecha($c['fecha_salida'])) ?></td>
                    <td><span class="insignia insignia-rojo"><?= e(MOTIVOS_CANCELACION[$c['tipo_motivo']] ?? $c['tipo_motivo']) ?></span><div class="celda-secundaria"><?= e($c['descripcion']) ?></div></td>
                    <td class="text-end tabular"><?= max(0, (int) $c['dias_anticipacion']) ?> días</td>
                    <td class="text-end tabular"><?= e(bs($c['monto_total'], 0)) ?></td>
                </tr>
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
    var motivos = <?= json_encode(array_values(array_map(fn ($m) => ['t' => $m['texto'], 'n' => $m['cantidad']], $motivos))) ?>;
    var g = document.getElementById('g-motivos');
    if (g) HE.grafico(g, function (c) {
        return { type: 'bar', data: { labels: motivos.map(function (m) { return m.t; }), datasets: [{ data: motivos.map(function (m) { return m.n; }), backgroundColor: motivos.map(function (m, i) { return i === 0 ? c.rojo : HE.alfa('#C6A15B', .8); }), borderRadius: 8 }] },
            options: { animation: <?= $imprimir ? 'false' : 'true' ?>, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { grid: { color: c.rejilla }, ticks: { precision: 0 } }, y: { grid: { display: false } } } } };
    });
})();
</script>
<?php $this->fin() ?>
