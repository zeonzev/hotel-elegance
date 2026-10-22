<?php
/** @var \App\Core\Vista $this */
echo $this->parcial('parciales/encabezado', [
    'titulo' => 'Tarifa referencial sugerida',
    'cu' => 'CU-21',
    'descripcion' => 'Estimación de demanda a partir de la ocupación histórica de fechas similares del año anterior.',
    'migas' => [['Tarifas', '/panel/tarifas'], ['Tarifa sugerida', null]],
]);
$p = $parametros;
?>
<div class="row g-4-5">
    <div class="col-xl-4">
        <div class="tarjeta mb-4">
            <div class="tarjeta-encabezado"><h2>Fecha objetivo</h2></div>
            <form method="get" class="tarjeta-cuerpo row g-3">
                <div class="col-6"><label class="form-label" for="desde">Desde</label><input type="date" class="form-control<?= clase_error('desde') ?>" id="desde" name="desde" value="<?= e($desde) ?>" required></div>
                <div class="col-6"><label class="form-label" for="hasta">Hasta</label><input type="date" class="form-control" id="hasta" name="hasta" value="<?= e($hasta) ?>"></div>
                <div class="col-12"><label class="form-label" for="categoria">Categoría</label><select class="form-select" id="categoria" name="categoria"><?php foreach ($categorias as $c): ?><option value="<?= $c['id_categoria'] ?>"<?= seleccionado($idCategoria, $c['id_categoria']) ?>><?= e($c['nombre']) ?> · base <?= e(bs($c['tarifa_base'], 0)) ?></option><?php endforeach; ?></select></div>
                <div class="col-12"><button class="btn btn-primary w-100" type="submit"><i class="bi bi-magic"></i> Calcular tarifa sugerida</button></div>
                <?php if ($especiales): ?>
                    <div class="col-12"><div class="form-label">Próximas fechas especiales</div><div class="d-flex flex-wrap gap-1">
                        <?php foreach ($especiales as $t): ?><a class="insignia insignia-oro" href="<?= url('/panel/tarifas/sugerencia', ['desde' => max($t['fecha_inicio'], hoy()), 'hasta' => $t['fecha_fin'], 'categoria' => $idCategoria]) ?>"><?= e($t['nombre']) ?></a><?php endforeach; ?>
                    </div></div>
                <?php endif; ?>
            </form>
        </div>
        <div class="tarjeta tarjeta-noche">
            <div class="tarjeta-cuerpo">
                <div class="sobretitulo" style="color:var(--he-oro-300)">Fórmula</div>
                <div class="formula my-3" style="background:rgba(255,255,255,.05);border-color:rgba(255,255,255,.1);color:#fff">f = 1 + <em>α</em> · (Ō − <em>meta</em>)</div>
                <ul class="fs-sm texto-suave ps-3 mb-0" style="line-height:1.8">
                    <li><b class="text-white">Ō</b>: ocupación promedio de las mismas fechas del año anterior (±<?= (int) $p['ventana'] ?> días) para la categoría.</li>
                    <li><b class="text-white">meta</b> = <?= numero($p['meta'] * 100) ?> % · <b class="text-white">α</b> = <?= numero($p['alfa'], 2) ?> (sensibilidad).</li>
                    <li>El factor se acota entre <?= numero($p['minimo'], 2) ?> y <?= numero($p['maximo'], 2) ?>.</li>
                    <li><b class="text-white">Tarifa sugerida = tarifa base × f</b>, redondeada a Bs 10.</li>
                    <li>Con menos de 3 noches de historial se sugiere la tarifa base (flujo 2a).</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-xl-8">
        <?php if ($resultado): $r = $resultado; ?>
            <div class="tarjeta mb-4">
                <div class="tarjeta-cuerpo">
                    <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start">
                        <div>
                            <div class="sobretitulo"><?= e($r['categoria']['nombre']) ?> · <?= e(fecha($r['desde'], 'larga')) ?><?= $r['hasta'] !== $r['desde'] ? ' al ' . e(fecha($r['hasta'], 'larga')) : '' ?></div>
                            <div class="d-flex align-items-baseline gap-3 mt-2">
                                <span class="serif fw-semibold" style="font-size:3.6rem;line-height:1"><?= e(bs($r['sugerida'], 0)) ?></span>
                                <span class="texto-suave">por noche</span>
                            </div>
                            <div class="mt-2 d-flex flex-wrap gap-2">
                                <span class="insignia <?= $r['factor'] >= 1 ? 'insignia-verde' : 'insignia-rojo' ?>"><i class="bi <?= $r['factor'] >= 1 ? 'bi-arrow-up-right' : 'bi-arrow-down-right' ?>"></i>Factor <?= numero($r['factor'], 3) ?></span>
                                <span class="insignia insignia-gris">Base <?= e(bs($r['base'], 0)) ?></span>
                                <span class="insignia insignia-gris">Vigente hoy para esas fechas <?= e(bs($r['vigente_promedio'], 0)) ?></span>
                                <?php foreach ($r['temporadas'] as $t): ?><span class="insignia insignia-oro"><?= e($t) ?></span><?php endforeach; ?>
                            </div>
                        </div>
                        <?php if (!$r['suficiente']): ?>
                            <div class="aviso aviso-oro" style="max-width:300px"><i class="bi bi-exclamation-circle"></i><div><strong>Historial insuficiente</strong><span class="fs-sm">No hay datos de esas fechas del año anterior: se sugiere la tarifa base (CU-21, flujo 2a).</span></div></div>
                        <?php endif; ?>
                    </div>
                    <hr>
                    <div class="row g-3 text-center">
                        <div class="col-6 col-md-3"><div class="kpi-titulo">Ocupación histórica (Ō)</div><div class="fs-4 fw-semibold tabular"><?= $r['suficiente'] ? numero($r['ocupacion'] * 100, 1) . ' %' : '—' ?></div><div class="fs-xs texto-tenue"><?= (int) $r['historico']['dias'] ?> noches de muestra</div></div>
                        <div class="col-6 col-md-3"><div class="kpi-titulo">Meta de ocupación</div><div class="fs-4 fw-semibold tabular"><?= numero($p['meta'] * 100) ?> %</div></div>
                        <div class="col-6 col-md-3"><div class="kpi-titulo">Ya reservado</div><div class="fs-4 fw-semibold tabular"><?= numero($r['reservada'], 1) ?> %</div><div class="fs-xs texto-tenue">demanda en libros</div></div>
                        <div class="col-6 col-md-3"><div class="kpi-titulo">Cálculo</div><div class="fs-6 fw-semibold tabular mt-1"><?= $r['suficiente'] ? '1 + ' . numero($p['alfa'], 2) . ' × (' . numero($r['ocupacion'], 3) . ' − ' . numero($p['meta'], 2) . ')' : 'f = 1' ?></div><div class="fs-xs texto-tenue"><?= numero($r['base'], 0) ?> × <?= numero($r['factor'], 3) ?> ≈ <?= numero($r['sugerida'], 0) ?></div></div>
                    </div>
                    <?php if ($r['historico']['serie']): ?>
                        <div class="grafico grafico-sm mt-4"><canvas id="grafico-historico"></canvas></div>
                        <div class="fs-xs texto-tenue mt-1">Ocupación de la categoría del <?= e(fecha($r['historico']['desde'])) ?> al <?= e(fecha($r['historico']['hasta'])) ?></div>
                    <?php endif; ?>
                </div>
                <?php if (puede('tarifas.gestionar') && $r['id_sugerencia']): ?>
                    <form class="tarjeta-pie row g-2 align-items-end" method="post" action="<?= url('/panel/tarifas/sugerencia/aplicar') ?>">
                        <?= csrf_campo() ?><input type="hidden" name="id_sugerencia" value="<?= (int) $r['id_sugerencia'] ?>">
                        <div class="col-md-6"><label class="form-label" for="nombre">Nombre de la temporada especial</label><input class="form-control" id="nombre" name="nombre" value="<?= e('Tarifa sugerida ' . $r['categoria']['nombre'] . ' ' . fecha($r['desde'])) ?>" maxlength="80" required></div>
                        <div class="col-md-3"><label class="form-label" for="precio">Precio a aplicar (Bs)</label><input type="number" step="0.01" min="1" class="form-control" id="precio" name="precio" value="<?= e((string) $r['sugerida']) ?>"></div>
                        <div class="col-md-3"><button class="btn btn-oro w-100" type="submit"><i class="bi bi-check2-circle"></i> Aplicar tarifa</button></div>
                    </form>
                <?php elseif ($r['id_sugerencia']): ?>
                    <div class="tarjeta-pie fs-sm texto-suave"><i class="bi bi-info-circle"></i> La sugerencia quedó registrada. Sólo el administrador puede aplicarla como temporada.</div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="tarjeta mb-4"><?= $this->parcial('parciales/vacio', ['icono' => 'bi-magic', 'titulo' => 'Elija una fecha objetivo', 'texto' => 'El sistema calculará la ocupación histórica de fechas similares y propondrá una tarifa referencial.']) ?></div>
        <?php endif; ?>
        <div class="tarjeta">
            <div class="tarjeta-encabezado"><div><h2>Sugerencias recientes</h2><div class="subtitulo">Tabla sugerencia_tarifa (aplicadas o no)</div></div></div>
            <?php if ($recientes): ?>
                <div class="tabla-contenedor"><table class="tabla tabla-compacta">
                    <thead><tr><th>Fechas</th><th>Categoría</th><th class="text-end">Ō</th><th class="text-end">Factor</th><th class="text-end">Base</th><th class="text-end">Sugerida</th><th>Estado</th></tr></thead>
                    <tbody><?php foreach ($recientes as $s): ?>
                        <tr><td><?= e(fecha($s['fecha_desde'])) ?><?= $s['fecha_hasta'] !== $s['fecha_desde'] ? ' → ' . e(fecha($s['fecha_hasta'])) : '' ?></td><td><?= e($s['categoria']) ?></td><td class="text-end tabular"><?= numero($s['ocupacion_historica'], 1) ?> %</td><td class="text-end tabular"><?= numero($s['factor'], 3) ?></td><td class="text-end tabular"><?= e(bs($s['tarifa_base'], 0)) ?></td><td class="text-end tabular fw-semibold"><?= e(bs($s['tarifa_sugerida'], 0)) ?></td><td><?= $s['aplicada'] ? '<span class="insignia insignia-verde">Aplicada</span>' : '<span class="insignia insignia-gris">Consulta</span>' ?></td></tr>
                    <?php endforeach; ?></tbody>
                </table></div>
            <?php else: ?><div class="tarjeta-cuerpo fs-sm texto-suave">Todavía no hay sugerencias calculadas.</div><?php endif; ?>
        </div>
    </div>
</div>
<?php if ($resultado && $resultado['historico']['serie']): ?>
<?php $this->seccion('scripts') ?>
<script src="<?= vendor('chartjs/chart.umd.min.js') ?>"></script>
<script>
(function () {
    var serie = <?= json_encode(array_map(fn ($f, $o) => ['f' => $f, 'p' => $o['porcentaje']], array_keys($resultado['historico']['serie']), $resultado['historico']['serie'])) ?>;
    var meta = <?= $p['meta'] * 100 ?>;
    var lienzo = document.getElementById('grafico-historico');
    HE.grafico(lienzo, function (c) {
        return {
            type: 'bar',
            data: { labels: serie.map(function (s) { var d = new Date(s.f + 'T12:00:00'); return d.getDate() + '/' + (d.getMonth() + 1); }),
                datasets: [
                    { type: 'line', label: 'Meta', data: serie.map(function () { return meta; }), borderColor: c.rojo, borderDash: [4, 4], pointRadius: 0, borderWidth: 1.2 },
                    { label: 'Ocupación', data: serie.map(function (s) { return Math.min(100, s.p); }), backgroundColor: HE.alfa('#C6A15B', .8), borderRadius: 6 }
                ] },
            options: { plugins: { legend: { display: false } }, scales: { y: { min: 0, max: 100, ticks: { callback: function (v) { return v + ' %'; } }, grid: { color: c.rejilla } }, x: { grid: { display: false } } } }
        };
    });
})();
</script>
<?php $this->fin() ?>
<?php endif; ?>
