<?php
/** @var \App\Core\Vista $this */
$acciones = '<a class="btn btn-claro" href="' . url('/panel/sobreventa/historial') . '"><i class="bi bi-clock-history"></i> Historial</a>';
if (puede('sobreventa.calcular')) {
    $acciones .= '<form method="post" action="' . url('/panel/sobreventa/tasas') . '">' . csrf_campo() . '<button class="btn btn-claro" type="submit"><i class="bi bi-arrow-repeat"></i> Recalcular tasas (CU-17)</button></form>';
}
echo $this->parcial('parciales/encabezado', [
    'titulo' => 'Política de sobreventa',
    'cu' => 'CU-17 · CU-18 · CU-19',
    'descripcion' => 'Teoría de Decisiones aplicada a la aceptación de reservas adicionales cuando el hotel está casi lleno, a partir de la tasa histórica de no-show.',
    'migas' => [['Inteligencia', null], ['Sobreventa', null]],
    'acciones' => $acciones,
]);
$aceptar = $e['decision'] === 'ACEPTAR';
$pct = fn ($v, $d = 1) => numero($v * 100, $d) . ' %';
$n = $e['reservas'];
$cap = $e['capacidad'];
$q = $e['q'];
$p = $e['p_ausencia'];
// La distribución se muestra alrededor de la capacidad (las probabilidades lejanas son ~0)
$dist = array_values(array_filter($e['distribucion'], fn ($d) => $d['k'] >= max(0, min($n, $cap) - 10)));
?>
<div class="tarjeta mb-4">
    <form class="tarjeta-cuerpo row g-3 align-items-end" method="get">
        <div class="col-md-6 col-xl-3"><label class="form-label" for="fecha">Noche a evaluar</label><input type="date" class="form-control" id="fecha" name="fecha" value="<?= e($e['fecha']) ?>"></div>
        <div class="col-md-6 col-xl-3"><label class="form-label" for="ingreso">Ingreso de la reserva adicional (Bs)</label><input type="number" step="0.01" min="1" class="form-control" id="ingreso" name="ingreso" value="<?= e(number_format($e['ingreso'], 2, '.', '')) ?>"></div>
        <div class="col-md-6 col-xl-3"><label class="form-label" for="fuente">Probabilidades de ausencia</label>
            <select class="form-select" id="fuente" name="fuente">
                <option value="HISTORICO"<?= $e['fuente'] === 'HISTORICO' ? ' selected' : '' ?>>Tasa mensual histórica (CU-17)</option>
                <option value="MARKOV"<?= $e['fuente'] === 'MARKOV' ? ' selected' : '' ?>>Cadena de Markov (absorción)</option>
            </select></div>
        <div class="col-md-3 col-xl-1"><label class="form-label" for="alfa" title="Coeficiente de optimismo de Hurwicz">α Hurwicz</label><input type="number" step="0.05" min="0" max="1" class="form-control" id="alfa" name="alfa" value="<?= e(number_format($e['criterios']['alfa'], 2, '.', '')) ?>"></div>
        <div class="col-md-3 col-xl-2"><button class="btn btn-primary w-100" type="submit"><i class="bi bi-calculator"></i> Evaluar</button></div>
        <div class="col-12">
            <div class="form-label">Noches que superan el umbral (<?= numero($config['umbral_ocupacion']) ?> %)</div>
            <div class="d-flex flex-wrap gap-1">
                <?php foreach (array_slice($alertas, 0, 12, true) as $f => $a): ?>
                    <a href="<?= url('/panel/sobreventa', ['fecha' => $f] + ($e['fuente'] === 'MARKOV' ? ['fuente' => 'MARKOV'] : [])) ?>" class="insignia <?= $f === $e['fecha'] ? 'insignia-oro' : 'insignia-rojo' ?>"><?= e(fecha($f, 'dia')) ?> · <?= numero($a['porcentaje'], 0) ?> %</a>
                <?php endforeach; ?>
                <?php if (!$alertas): ?><span class="fs-sm texto-tenue">Ninguna en los próximos 30 días.</span><?php endif; ?>
            </div>
        </div>
    </form>
</div>

<div class="veredicto <?= $aceptar ? 'veredicto-aceptar' : 'veredicto-rechazar' ?> mb-4 animar-entrada">
    <span class="icono-chip"><i class="bi <?= $aceptar ? 'bi-hand-thumbs-up' : 'bi-hand-thumbs-down' ?>"></i></span>
    <div class="flex-grow-1">
        <div class="sobretitulo" style="color:inherit">Recomendación del modelo · <?= e(fecha($e['fecha'], 'larga')) ?></div>
        <h3><?= $aceptar ? 'ACEPTAR una reserva adicional' : 'RECHAZAR la reserva adicional' ?></h3>
        <div class="fs-sm mt-1"><?= e($e['motivo']) ?> VE(aceptar) = <strong><?= e(bs($e['ve_aceptar'])) ?></strong> frente a VE(rechazar) = Bs 0,00.</div>
    </div>
    <?php if (puede('sobreventa.calcular')): ?>
        <form method="post" action="<?= url('/panel/sobreventa/evaluar') ?>">
            <?= csrf_campo() ?><input type="hidden" name="fecha" value="<?= e($e['fecha']) ?>"><input type="hidden" name="ingreso" value="<?= e((string) $e['ingreso']) ?>"><input type="hidden" name="fuente" value="<?= e($e['fuente']) ?>">
            <button class="btn btn-claro" type="submit"><i class="bi bi-save"></i> Guardar evaluación</button>
        </form>
    <?php endif; ?>
</div>

<div class="row g-4-5 mb-4">
    <?php foreach ([
        ['Ocupación de la noche', numero($e['ocupacion'], 1) . ' %', "$n reservas / $cap habitaciones", 'bi-buildings', $e['supera_umbral'] ? 'chip-rojo' : 'chip-verde'],
        ['Prob. de no presentarse (p)', $pct($p), ($e['fuente'] === 'MARKOV' ? 'Cadena de Markov' : 'tasa histórica') . ($e['incluir_cancelaciones'] ? ' · no-show + cancelación' : ' · no-show'), 'bi-person-x', 'chip-violeta'],
        ['Prob. de que se libere una habitación', $pct($e['p_libera']), 'P(se presentan ≤ ' . ($cap - 1) . ')', 'bi-door-open', 'chip-azul'],
        ['Valor esperado de aceptar', bs($e['ve_aceptar'], 0), 'VE del informe: ' . bs($e['ve_simple'], 0), 'bi-graph-up-arrow', $aceptar ? 'chip-verde' : 'chip-rojo'],
        ['Nivel óptimo de sobreventa', '+' . $e['nivel_optimo'], 'reservas sobre la capacidad (máx. ' . $e['max_extra'] . ')', 'bi-bullseye', 'chip-oro'],
    ] as [$t, $v, $s, $i, $c]): ?>
        <div class="col-sm-6 col-xxl"><div class="tarjeta kpi"><div class="kpi-cabecera"><span class="kpi-titulo"><?= e($t) ?></span><span class="icono-chip <?= $c ?>"><i class="bi <?= $i ?>"></i></span></div><div class="kpi-valor"><?= e($v) ?></div><div class="kpi-pie"><?= e($s) ?></div></div></div>
    <?php endforeach; ?>
</div>

<div class="row g-4-5 mb-4">
    <div class="col-xl-6">
        <div class="tarjeta h-100">
            <div class="tarjeta-encabezado"><div><h2>Cálculo paso a paso</h2><div class="subtitulo">CU-18 · distribución binomial sobre las reservas de la noche</div></div></div>
            <div class="tarjeta-cuerpo">
                <div class="formula mb-3">S ~ Binomial(<em>n</em> = <?= $n ?>, <em>q</em> = <?= numero($q, 4) ?>)</div>
                <ol class="fs-sm ps-3" style="line-height:1.9">
                    <li><?php if ($e['fuente'] === 'MARKOV'): ?>Probabilidad de absorción en «No presentada» de la Cadena de Markov (<a href="<?= url('/panel/markov') ?>">ver cadena</a>, últimos <?= (int) $e['meses_historial'] ?> meses)<?php else: ?>Tasa histórica de ausencia (CU-17, últimos <?= (int) $e['meses_historial'] ?> meses)<?php endif; ?>: <strong>p = <?= numero($p, 4) ?></strong> → probabilidad de presentarse <strong>q = 1 − p = <?= numero($q, 4) ?></strong>.</li>
                    <li>Probabilidad de que se libere al menos una habitación: <strong>P(S ≤ <?= $cap - 1 ?>) = <?= numero($e['p_libera'], 4) ?></strong><?= $n === $cap ? ' (= 1 − q<sup>' . $n . '</sup>)' : '' ?>.</li>
                    <li>Fórmula del informe: <strong>VE = p·I − (1 − p)·C</strong> = <?= numero($e['p_libera'], 3) ?> × <?= numero($e['ingreso'], 0) ?> − <?= numero(1 - $e['p_libera'], 3) ?> × <?= numero($e['costo'], 0) ?> = <strong><?= e(bs($e['ve_simple'])) ?></strong></li>
                    <li>Como el huésped adicional también puede no presentarse: <strong>VE(aceptar) = q · VE</strong> = <?= numero($q, 3) ?> × <?= numero($e['ve_simple'], 2) ?> = <strong class="<?= $aceptar ? 'text-success' : 'text-danger' ?>"><?= e(bs($e['ve_aceptar'])) ?></strong></li>
                    <li>Se compara con <strong>VE(rechazar) = 0</strong> y con el límite de <?= (int) $e['max_extra'] ?> reservas en sobreventa (hay <?= (int) $e['extras_actuales'] ?>): decisión <strong><?= e($e['decision']) ?></strong>.</li>
                </ol>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="tarjeta h-100">
            <div class="tarjeta-encabezado"><div><h2>Matriz de decisión</h2><div class="subtitulo">Alternativas × estados de la naturaleza (en Bs)</div></div></div>
            <div class="tabla-contenedor">
                <table class="matriz">
                    <thead><tr><th>Estado de la naturaleza</th><th>Probabilidad</th><th>Aceptar</th><th>Rechazar</th></tr></thead>
                    <tbody>
                        <?php foreach ($e['matriz'] as $m): ?>
                            <tr><td><?= e($m['estado']) ?></td><td class="tabular"><?= $pct($m['probabilidad'], 2) ?></td><td class="tabular <?= $m['aceptar'] < 0 ? 'negativo' : ($m['aceptar'] > 0 ? 'positivo' : '') ?>"><?= numero($m['aceptar'], 2) ?></td><td class="tabular">0,00</td></tr>
                        <?php endforeach; ?>
                        <tr class="<?= $aceptar ? 'optima' : '' ?>"><td class="ve">Valor esperado (Σ prob × resultado)</td><td class="ve">100 %</td><td class="ve tabular <?= $e['ve_aceptar'] < 0 ? 'negativo' : 'positivo' ?>"><?= numero($e['ve_aceptar'], 2) ?></td><td class="ve tabular">0,00</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="tarjeta-cuerpo pt-3 fs-sm texto-suave">Ingreso I = <?= e(bs($e['ingreso'])) ?> (tarifa vigente de la noche) · costo de reubicación C = <?= e(bs($e['costo'])) ?> (traslado y noche en otro hotel).</div>
        </div>
    </div>
</div>

<div class="row g-4-5 mb-4">
    <div class="col-xl-6">
        <div class="tarjeta h-100">
            <div class="tarjeta-encabezado"><div><h2>¿Cuántos huéspedes se presentarán?</h2><div class="subtitulo">P(S = k) · en rojo, los casos sin habitación libre (k ≥ <?= $cap ?>)</div></div></div>
            <div class="tarjeta-cuerpo"><div class="grafico grafico-md"><canvas id="grafico-binomial"></canvas></div><div class="fs-xs texto-tenue mt-2">Huéspedes esperados: n·q = <?= numero($e['esperados'], 2) ?></div></div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="tarjeta h-100">
            <div class="tarjeta-encabezado"><div><h2>Nivel óptimo de sobreventa</h2><div class="subtitulo">Ganancia esperada si se aceptan cap + x reservas</div></div><span class="insignia insignia-oro"><i class="bi bi-bullseye"></i>x* = <?= (int) $e['nivel_optimo'] ?></span></div>
            <div class="tabla-contenedor">
                <table class="matriz">
                    <thead><tr><th>Reservas extra (x)</th><th>Total</th><th>P(desborde)</th><th>Reubicados esperados</th><th>Ganancia esperada</th><th>Marginal</th></tr></thead>
                    <tbody>
                    <?php foreach ($e['niveles'] as $nv): ?>
                        <tr class="<?= $nv['extra'] === $e['nivel_optimo'] ? 'optima' : '' ?>">
                            <td><?= $nv['extra'] === 0 ? 'Sin sobreventa' : '+' . $nv['extra'] ?><?= $nv['extra'] === $e['nivel_optimo'] ? ' <i class="bi bi-star-fill texto-oro"></i>' : '' ?></td>
                            <td class="tabular"><?= $nv['total'] ?></td>
                            <td class="tabular"><?= $pct($nv['p_desborde']) ?></td>
                            <td class="tabular"><?= numero($nv['reubicados'], 2) ?></td>
                            <td class="tabular <?= $nv['extra'] === $e['nivel_optimo'] ? 've' : '' ?>"><?= numero($nv['ganancia'], 0) ?></td>
                            <td class="tabular <?= ($nv['marginal'] ?? 0) < 0 ? 'negativo' : 'positivo' ?>"><?= $nv['marginal'] === null ? '—' : (($nv['marginal'] >= 0 ? '+' : '') . numero($nv['marginal'], 0)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$cr = $e['criterios'];
$K = count($e['prob_estados']) - 1;
$nombresCriterio = ['ve' => 'Valor esperado (bajo riesgo)', 'maximax' => 'Maximax (optimista)', 'maximin' => 'Maximin (pesimista)', 'laplace' => 'Laplace (equiprobable)', 'hurwicz' => 'Hurwicz (α = ' . numero($cr['alfa'], 2) . ')', 'savage' => 'Savage (mínimo arrepentimiento)'];
$etiquetaEstado = fn ($j) => 'E' . $j . ': ' . ($j === $K ? $j . ' o más' : $j) . ' no-show';
?>
<div class="row g-4-5 mb-4">
    <div class="col-xxl-6">
        <div class="tarjeta h-100">
            <div class="tarjeta-encabezado"><div><h2>Matriz de pagos</h2><div class="subtitulo">Aceptar k reservas extra con el hotel lleno · Pago(k, j) = I·mín(k, j) − C·máx(0, k − j) · en Bs</div></div><span class="insignia insignia-oro"><i class="bi bi-bullseye"></i>A<?= $cr['elegida']['ve'] ?></span></div>
            <div class="tabla-contenedor">
                <table class="matriz">
                    <thead><tr><th>Alternativa</th><?php foreach ($e['prob_estados'] as $j => $pj): ?><th><?= e($etiquetaEstado($j)) ?></th><?php endforeach; ?><th>VE</th></tr></thead>
                    <tbody>
                    <?php foreach ($cr['filas'] as $k => $f): ?>
                        <tr class="<?= $k === $cr['elegida']['ve'] ? 'optima' : '' ?>">
                            <td>A<?= $k ?>: <?= $k === 0 ? 'no aceptar extra' : 'aceptar ' . $k . ' extra' ?></td>
                            <?php foreach ($f['pagos'] as $v): ?><td class="tabular <?= $v < 0 ? 'negativo' : ($v > 0 ? 'positivo' : '') ?>"><?= numero($v, 0) ?></td><?php endforeach; ?>
                            <td class="tabular ve"><?= numero($f['ve'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                        <tr><td class="ve">Probabilidad P(E<sub>j</sub>)</td><?php foreach ($e['prob_estados'] as $pj): ?><td class="ve tabular"><?= numero($pj, 4) ?></td><?php endforeach; ?><td class="ve">1</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="tarjeta-cuerpo pt-3 fs-sm texto-suave">
                P(E<sub>j</sub>) = probabilidad de que falten j de los <?= $cap ?> huéspedes con reserva, Binomial(<?= $cap ?>, p = <?= numero($p, 4) ?>); el último estado agrupa «<?= $K ?> o más».
                Regla rápida: aceptar una reserva extra conviene si P(falta al menos uno) ≥ C / (I + C) = <strong><?= numero($e['umbral_p'], 3) ?></strong>; aquí P = <strong><?= numero(1 - $e['prob_estados'][0], 3) ?></strong>.
            </div>
        </div>
    </div>
    <div class="col-xxl-6">
        <div class="tarjeta h-100">
            <div class="tarjeta-encabezado"><div><h2>Criterios de decisión</h2><div class="subtitulo">Bajo riesgo y bajo incertidumbre · valor de cada alternativa en Bs</div></div></div>
            <div class="tabla-contenedor">
                <table class="matriz">
                    <thead><tr><th>Criterio</th><?php foreach ($cr['filas'] as $k => $_): ?><th>A<?= $k ?></th><?php endforeach; ?><th>Elige</th></tr></thead>
                    <tbody>
                    <?php foreach ($nombresCriterio as $c => $nombre): ?>
                        <tr>
                            <td><?= e($nombre) ?></td>
                            <?php foreach ($cr['filas'] as $k => $f): ?><td class="tabular<?= $k === $cr['elegida'][$c] ? ' ve' : '' ?>"><?= numero($f[$c], in_array($c, ['ve', 'laplace'], true) ? 1 : 0) ?></td><?php endforeach; ?>
                            <td class="ve">A<?= $cr['elegida'][$c] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="tarjeta-cuerpo pt-3 fs-sm">
                <div class="mb-1 texto-suave">Savage toma el mayor arrepentimiento de cada alternativa y elige el menor.</div>
                <div>VE con información perfecta = <strong><?= e(bs($cr['ve_ci'])) ?></strong> · <strong>VEIP = <?= e(bs($cr['veip'])) ?></strong> por noche: es lo máximo que valdría saber de antemano quién no llegará (por ejemplo, confirmando por WhatsApp el día anterior).</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4-5">
    <div class="col-xl-7">
        <div class="tarjeta h-100">
            <div class="tarjeta-encabezado"><div><h2>Tasas históricas (CU-17)</h2><div class="subtitulo">Cancelaciones y no-show sobre reservas del mes · tabla <code>tasa_historica</code></div></div>
                <span class="insignia insignia-violeta">No-show vigente <?= $pct($vigente['p_no_show']) ?></span></div>
            <div class="tarjeta-cuerpo"><div class="grafico grafico-sm"><canvas id="grafico-tasas"></canvas></div></div>
            <div class="tabla-contenedor" style="max-height: 260px">
                <table class="tabla tabla-compacta">
                    <thead><tr><th>Periodo</th><th class="text-end">Reservas</th><th class="text-end">Cancel.</th><th class="text-end">No-show</th><th class="text-end">Tasa cancel.</th><th class="text-end">Tasa no-show</th></tr></thead>
                    <tbody><?php foreach (array_reverse($tasas) as $t): ?>
                        <tr><td><?= e(ucfirst(MESES[(int) $t['mes']]) . ' ' . $t['anio']) ?></td><td class="text-end tabular"><?= (int) $t['total_reservas'] ?></td><td class="text-end tabular"><?= (int) $t['cancelaciones'] ?></td><td class="text-end tabular"><?= (int) $t['no_show'] ?></td><td class="text-end tabular"><?= $pct($t['tasa_cancelacion']) ?></td><td class="text-end tabular fw-semibold"><?= $pct($t['tasa_no_show']) ?></td></tr>
                    <?php endforeach; ?></tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="tarjeta mb-4" id="configuracion">
            <div class="tarjeta-encabezado"><div><h2>Parámetros de la política</h2><div class="subtitulo"><?= puede('sobreventa.configurar') ? 'Tabla configuracion_sobreventa (se versiona cada cambio)' : 'Sólo el administrador puede modificarlos' ?></div></div></div>
            <form method="post" action="<?= url('/panel/sobreventa/configuracion') ?>">
                <?= csrf_campo() ?>
                <fieldset class="tarjeta-cuerpo row g-3"<?= puede('sobreventa.configurar') ? '' : ' disabled' ?>>
                    <div class="col-6"><label class="form-label" for="umbral_ocupacion">Umbral de alerta (%)</label><input type="number" step="0.5" min="50" max="100" class="form-control<?= clase_error('umbral_ocupacion') ?>" id="umbral_ocupacion" name="umbral_ocupacion" value="<?= e((string) (float) $config['umbral_ocupacion']) ?>"><?= mensaje_error('umbral_ocupacion') ?></div>
                    <div class="col-6"><label class="form-label" for="costo_reubicacion">Costo de reubicación (Bs)</label><input type="number" step="1" min="1" class="form-control<?= clase_error('costo_reubicacion') ?>" id="costo_reubicacion" name="costo_reubicacion" value="<?= e((string) (float) $config['costo_reubicacion']) ?>"><?= mensaje_error('costo_reubicacion') ?></div>
                    <div class="col-6"><label class="form-label" for="max_reservas_extra">Máximo de reservas extra</label><input type="number" min="0" max="10" class="form-control<?= clase_error('max_reservas_extra') ?>" id="max_reservas_extra" name="max_reservas_extra" value="<?= (int) $config['max_reservas_extra'] ?>"><?= mensaje_error('max_reservas_extra') ?></div>
                    <div class="col-6"><label class="form-label" for="meses_historial">Meses de historial</label><input type="number" min="1" max="36" class="form-control<?= clase_error('meses_historial') ?>" id="meses_historial" name="meses_historial" value="<?= (int) $config['meses_historial'] ?>"><?= mensaje_error('meses_historial') ?></div>
                    <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="incluir_cancelaciones" name="incluir_cancelaciones" value="1"<?= marcado($config['incluir_cancelaciones']) ?>><label class="form-check-label fs-sm" for="incluir_cancelaciones">Sumar la tasa de cancelación a la probabilidad de ausencia</label></div></div>
                    <?php if (puede('sobreventa.configurar')): ?><div class="col-12 text-end"><button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> Guardar parámetros</button></div><?php endif; ?>
                </fieldset>
            </form>
        </div>
        <div class="tarjeta">
            <div class="tarjeta-encabezado"><div><h2>Últimas evaluaciones</h2><div class="subtitulo">Tabla evaluacion_sobreventa</div></div><a class="btn btn-sm btn-claro" href="<?= url('/panel/sobreventa/historial') ?>">Ver todas</a></div>
            <?php foreach ($historial as $h): ?>
                <div class="item-lista py-2"><?= insignia($h['decision']) ?><div class="flex-grow-1 min-w-0 fs-sm"><strong><?= e(fecha($h['fecha_objetivo'], 'dia')) ?></strong> · <?= numero($h['ocupacion'], 0) ?> % · VE <?= e(bs($h['ve_aceptar'], 0)) ?><div class="fs-xs texto-tenue"><?= e($h['origen'] === 'RESERVA' ? 'Durante la reserva ' . ($h['codigo'] ?? '') : 'Evaluación manual') ?> · @<?= e($h['nombre_usuario'] ?? '—') ?></div></div></div>
            <?php endforeach; ?>
            <?php if (!$historial): ?><div class="tarjeta-cuerpo fs-sm texto-suave">Aún no hay evaluaciones guardadas.</div><?php endif; ?>
        </div>
    </div>
</div>

<?php $this->seccion('scripts') ?>
<script src="<?= vendor('chartjs/chart.umd.min.js') ?>"></script>
<script>
(function () {
    var dist = <?= json_encode(array_map(fn ($d) => ['k' => $d['k'], 'p' => round($d['probabilidad'] * 100, 3), 'cupo' => $d['hay_cupo']], $dist)) ?>;
    HE.grafico(document.getElementById('grafico-binomial'), function (c) {
        return {
            type: 'bar',
            data: { labels: dist.map(function (d) { return d.k; }), datasets: [{ label: 'P(S = k)', data: dist.map(function (d) { return d.p; }), backgroundColor: dist.map(function (d) { return d.cupo ? HE.alfa(c.oscuro ? '#7FB0E3' : '#1F3452', .85) : c.rojo; }), borderRadius: 6 }] },
            options: { plugins: { legend: { display: false }, tooltip: { callbacks: { title: function (x) { return 'Se presentan ' + x[0].label + ' huéspedes'; }, label: function (x) { return x.parsed.y.toFixed(2) + ' %'; } } } },
                scales: { y: { ticks: { callback: function (v) { return v + ' %'; } }, grid: { color: c.rejilla } }, x: { title: { display: true, text: 'Huéspedes que se presentan (k)' }, grid: { display: false } } } }
        };
    });
    var tasas = <?= json_encode(array_map(fn ($t) => ['m' => MESES_CORTOS[(int) $t['mes']] . ' ' . substr((string) $t['anio'], 2), 'c' => round((float) $t['tasa_cancelacion'] * 100, 2), 'n' => round((float) $t['tasa_no_show'] * 100, 2)], $tasas)) ?>;
    HE.grafico(document.getElementById('grafico-tasas'), function (c) {
        return {
            type: 'bar',
            data: { labels: tasas.map(function (t) { return t.m; }), datasets: [
                { label: 'Cancelación', data: tasas.map(function (t) { return t.c; }), backgroundColor: HE.alfa('#C6A15B', .85), borderRadius: 6 },
                { label: 'No-show', data: tasas.map(function (t) { return t.n; }), backgroundColor: c.violeta, borderRadius: 6 }
            ] },
            options: { plugins: { legend: { position: 'bottom' }, tooltip: { callbacks: { label: function (x) { return x.dataset.label + ': ' + x.parsed.y.toFixed(1) + ' %'; } } } },
                scales: { y: { ticks: { callback: function (v) { return v + ' %'; } }, grid: { color: c.rejilla } }, x: { grid: { display: false } } } }
        };
    });
})();
</script>
<?php $this->fin() ?>
