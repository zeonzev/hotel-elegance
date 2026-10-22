<?php
/** @var \App\Core\Vista $this */
$acciones = '<a class="btn btn-claro" href="' . url('/panel/tarifas/sugerencia') . '"><i class="bi bi-magic"></i> Tarifa sugerida (CU-21)</a>';
if (puede('tarifas.gestionar')) {
    $acciones .= '<a class="btn btn-primary" href="' . url('/panel/tarifas/nueva') . '"><i class="bi bi-plus-lg"></i> Nueva temporada</a>';
}
echo $this->parcial('parciales/encabezado', [
    'titulo' => 'Tarifas y demanda',
    'cu' => 'CU-20 · CU-21',
    'descripcion' => 'Temporadas alta, baja y fechas especiales (Carnaval, Alasitas, Gran Poder…) con el precio por noche de cada categoría. Prioridad: especial > alta > baja > tarifa base.',
    'migas' => [['Inteligencia', null], ['Tarifas y demanda', null]],
    'acciones' => $acciones,
]);
$primerDia = array_key_first($mapa);
$offset = (int) date('N', strtotime($primerDia)) - 1;
?>
<div class="row g-4-5 mb-4">
    <?php foreach ($categorias as $c): $t = $hoy[(int) $c['id_categoria']]; ?>
        <div class="col-sm-6 col-xl-3">
            <div class="tarjeta kpi">
                <div class="kpi-cabecera"><span class="kpi-titulo"><?= e($c['nombre']) ?> · hoy</span><?= $t['tipo'] === 'BASE' ? '<span class="insignia insignia-gris">Base</span>' : insignia($t['tipo']) ?></div>
                <div class="kpi-valor"><?= e(bs($t['precio'], 0)) ?> <small>/ noche</small></div>
                <div class="kpi-pie"><?= $t['temporada'] ? e($t['temporada']) : 'Sin temporada vigente' ?> · base <?= e(bs($t['base'], 0)) ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-4-5 mb-4">
    <div class="col-xl-7">
        <div class="tarjeta h-100">
            <div class="tarjeta-encabezado">
                <div><h2>Mapa de demanda · próximas 6 semanas</h2><div class="subtitulo">Ocupación ya reservada por día · el punto dorado indica una fecha especial</div></div>
                <div class="leyenda"><span><i style="background:var(--he-verde-suave);border:1px solid var(--he-verde)"></i>&lt; 50 %</span><span><i style="background:var(--he-ambar-suave);border:1px solid var(--he-ambar)"></i>50–85 %</span><span><i style="background:var(--he-rojo-suave);border:1px solid var(--he-rojo)"></i>&gt; 85 %</span></div>
            </div>
            <div class="tarjeta-cuerpo">
                <div class="mapa-calor">
                    <?php foreach (DIAS_CORTOS as $d): ?><div class="dia-cab"><?= e($d) ?></div><?php endforeach; ?>
                    <?php for ($i = 0; $i < $offset; $i++): ?><div class="dia vacio-dia"></div><?php endfor; ?>
                    <?php foreach ($mapa as $f => $m):
                        $p = $m['reservada'];
                        [$fondo, $borde] = $p > 85 ? ['--he-rojo-suave', '--he-rojo'] : ($p >= 50 ? ['--he-ambar-suave', '--he-ambar'] : ['--he-verde-suave', '--he-verde']);
                        $titulo = fecha($f, 'larga') . ' · reservado ' . numero($p, 0) . ' %' . ($m['historica'] !== null ? ' · año anterior ' . numero($m['historica'], 0) . ' %' : '') . ($m['temporada'] ? ' · ' . $m['temporada'] : '');
                    ?>
                        <a class="dia<?= $m['tipo'] === 'ESPECIAL' ? ' especial' : '' ?>" style="background: var(<?= $fondo ?>); border-color: color-mix(in srgb, var(<?= $borde ?>) 40%, transparent)" href="<?= url('/panel/tarifas/sugerencia', ['desde' => $f, 'hasta' => $f]) ?>" data-bs-toggle="tooltip" title="<?= e($titulo) ?>">
                            <strong><?= (int) date('j', strtotime($f)) ?><?= date('j', strtotime($f)) === '1' ? ' <span class="fs-xs fw-normal texto-tenue">' . e(MESES_CORTOS[(int) date('n', strtotime($f))]) . '</span>' : '' ?></strong>
                            <span class="pct" style="color: var(<?= $borde ?>)"><?= numero($p, 0) ?> %</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="tarjeta h-100">
            <div class="tarjeta-encabezado"><div><h2>Ocupación histórica mensual</h2><div class="subtitulo">Base de la estimación de demanda (CU-21)</div></div></div>
            <div class="tarjeta-cuerpo"><div class="grafico grafico-md"><canvas id="grafico-mensual"></canvas></div></div>
        </div>
    </div>
</div>

<div class="tarjeta">
    <div class="tarjeta-encabezado">
        <div><h2>Temporadas y tarifas por categoría</h2><div class="subtitulo">Precio por noche en bolivianos</div></div>
        <form class="d-flex gap-2" method="get">
            <div class="segmentos">
                <a class="<?= $vistaFiltro === 'vigentes' ? 'active' : '' ?>" href="<?= url('/panel/tarifas', array_filter(['tipo' => $tipo])) ?>">Vigentes y próximas</a>
                <a class="<?= $vistaFiltro === 'todas' ? 'active' : '' ?>" href="<?= url('/panel/tarifas', array_filter(['vista' => 'todas', 'tipo' => $tipo])) ?>">Todas</a>
            </div>
            <input type="hidden" name="vista" value="<?= $vistaFiltro === 'todas' ? 'todas' : '' ?>">
            <select class="form-select form-select-sm w-auto" name="tipo" data-autoenviar><option value="">Todos los tipos</option><?php foreach (['ALTA' => 'Alta', 'BAJA' => 'Baja', 'ESPECIAL' => 'Especial'] as $v => $t): ?><option value="<?= $v ?>"<?= seleccionado($tipo, $v) ?>><?= $t ?></option><?php endforeach; ?></select>
        </form>
    </div>
    <?php if ($temporadas): ?>
        <div class="tabla-contenedor">
            <table class="tabla">
                <thead><tr><th>Temporada</th><th>Tipo</th><th>Vigencia</th><?php foreach ($categorias as $c): ?><th class="text-end"><?= e($c['nombre']) ?></th><?php endforeach; ?><?php if (puede('tarifas.gestionar')): ?><th></th><?php endif; ?></tr></thead>
                <tbody>
                <?php foreach ($temporadas as $t): $activa = $t['fecha_inicio'] <= hoy() && $t['fecha_fin'] >= hoy(); ?>
                    <tr<?= $t['estado'] ? '' : ' class="opacity-50"' ?>>
                        <td><div class="d-flex align-items-center gap-2"><span style="width:10px;height:34px;border-radius:4px;background:<?= e($t['color']) ?>"></span><div><div class="celda-principal"><?= e($t['nombre']) ?><?= $activa ? ' <span class="insignia insignia-verde ms-1">Vigente</span>' : '' ?></div><div class="celda-secundaria"><?= e($t['descripcion'] ?? '') ?></div></div></div></td>
                        <td><?= insignia($t['tipo']) ?></td>
                        <td class="text-nowrap"><?= e(fecha($t['fecha_inicio'])) ?> → <?= e(fecha($t['fecha_fin'])) ?><div class="celda-secundaria"><?= noches($t['fecha_inicio'], $t['fecha_fin']) + 1 ?> día(s)</div></td>
                        <?php foreach ($categorias as $c): $pr = $precios[(int) $t['id_temporada']][(int) $c['id_categoria']] ?? null; $dif = $pr ? ($pr / (float) $c['tarifa_base'] - 1) * 100 : 0; ?>
                            <td class="text-end tabular"><?= $pr ? e(bs($pr, 0)) : '—' ?><?php if ($pr): ?><div class="celda-secundaria <?= $dif > 0 ? 'text-success' : ($dif < 0 ? 'text-danger' : '') ?>"><?= ($dif > 0 ? '+' : '') . numero($dif, 0) ?> %</div><?php endif; ?></td>
                        <?php endforeach; ?>
                        <?php if (puede('tarifas.gestionar')): ?>
                            <td class="acciones">
                                <a class="btn btn-claro btn-accion" href="<?= url('/panel/tarifas/' . $t['id_temporada'] . '/editar') ?>" title="Editar"><i class="bi bi-pencil"></i></a>
                                <form class="d-inline" method="post" action="<?= url('/panel/tarifas/' . $t['id_temporada'] . '/eliminar') ?>" data-confirmar="¿Eliminar la temporada <?= e($t['nombre']) ?>?" data-confirmar-texto="Las reservas ya registradas conservan su monto." data-confirmar-boton="Eliminar"><?= csrf_campo() ?><button class="btn btn-peligro-suave btn-accion" type="submit" title="Eliminar"><i class="bi bi-trash"></i></button></form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                <tr><td><div class="celda-principal">Tarifa base</div><div class="celda-secundaria">Se aplica cuando ninguna temporada cubre la fecha</div></td><td><span class="insignia insignia-gris">Base</span></td><td>—</td><?php foreach ($categorias as $c): ?><td class="text-end tabular fw-semibold"><?= e(bs($c['tarifa_base'], 0)) ?></td><?php endforeach; ?><?php if (puede('tarifas.gestionar')): ?><td></td><?php endif; ?></tr>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <?= $this->parcial('parciales/vacio', ['icono' => 'bi-tags', 'titulo' => 'Sin temporadas', 'texto' => 'No hay temporadas con esos filtros.']) ?>
    <?php endif; ?>
</div>

<?php $this->seccion('scripts') ?>
<script src="<?= vendor('chartjs/chart.umd.min.js') ?>"></script>
<script>
(function () {
    var datos = <?= json_encode($mensual) ?>;
    var meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    var lienzo = document.getElementById('grafico-mensual');
    HE.grafico(lienzo, function (c) {
        var claves = Object.keys(datos);
        return {
            type: 'line',
            data: { labels: claves.map(function (k) { var p = k.split('-'); return meses[+p[1] - 1] + ' ' + p[0].slice(2); }),
                datasets: [{ label: 'Ocupación', data: claves.map(function (k) { return datos[k].porcentaje; }), borderColor: c.oro, backgroundColor: HE.degradado(lienzo, c.oro, .25), fill: true, tension: .35, pointRadius: 3, pointBackgroundColor: c.oro, borderWidth: 2.4 }] },
            options: { plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (x) { return x.parsed.y.toFixed(1) + ' %'; } } } },
                scales: { y: { min: 0, max: 100, ticks: { callback: function (v) { return v + ' %'; } }, grid: { color: c.rejilla } }, x: { grid: { display: false } } } }
        };
    });
})();
</script>
<?php $this->fin() ?>
