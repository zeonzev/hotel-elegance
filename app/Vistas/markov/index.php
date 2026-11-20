<?php
/** @var \App\Core\Vista $this */
use App\Servicios\CadenaMarkov;

$acciones = '';
if (puede('sobreventa.ver')) {
    $acciones = '<a class="btn btn-primary" href="' . url('/panel/sobreventa', ['fuente' => 'MARKOV']) . '"><i class="bi bi-shield-check"></i> Usar en Teoría de Decisiones</a>';
}
echo $this->parcial('parciales/encabezado', [
    'titulo' => 'Cadenas de Markov',
    'cu' => 'IO II · Segundo aporte',
    'descripcion' => 'El sistema arma la cadena del ciclo de una reserva con el historial guardado: cuenta cómo cambian de estado día a día, calcula la matriz de transición y estima cómo terminará una reserva.',
    'migas' => [['Inteligencia', null], ['Cadenas de Markov', null]],
    'acciones' => $acciones,
]);

$E = CadenaMarkov::ESTADOS;
$P = $a['P'];
$B = $a['absorcion']['B'];
$t = $a['absorcion']['t'];
$N = $a['absorcion']['N'];
$pct = fn ($v, $d = 1) => numero($v * 100, $d) . ' %';
$ini = $a['inicial'];
$Vn = $a['evolucion'][$a['pasos']];
// Pasos que se muestran en la tabla de evolución (todos si n ≤ 10)
$filasV = $a['pasos'] <= 10 ? range(0, $a['pasos']) : array_values(array_unique(array_filter([0, 1, 2, 3, 5, 7, 10, 14, 21, 30, $a['pasos']], fn ($k) => $k <= $a['pasos'])));
sort($filasV);

// ---- Diagrama de la cadena (SVG) ----
$nodos = ['C' => [62, 112], 'H' => [262, 112], 'F' => [462, 112], 'X' => [180, 250], 'N' => [350, 250]];
$radio = 30;
$flecha = function (string $i, string $j) use ($nodos, $radio, $P): string {
    [$x1, $y1] = $nodos[$i];
    [$x2, $y2] = $nodos[$j];
    $d = hypot($x2 - $x1, $y2 - $y1);
    $ux = ($x2 - $x1) / $d;
    $uy = ($y2 - $y1) / $d;
    $ax = $x1 + $ux * $radio; $ay = $y1 + $uy * $radio;
    $bx = $x2 - $ux * ($radio + 4); $by = $y2 - $uy * ($radio + 4);
    $lx = $ax + ($bx - $ax) * 0.5; $ly = $ay + ($by - $ay) * 0.5;
    $texto = numero($P[$i][$j], 3);
    return sprintf('<line x1="%.1f" y1="%.1f" x2="%.1f" y2="%.1f" class="mk-arista" marker-end="url(#mk-punta)"/>', $ax, $ay, $bx, $by)
        . sprintf('<rect x="%.1f" y="%.1f" width="54" height="22" rx="6" class="mk-etiqueta-fondo"/><text x="%.1f" y="%.1f" class="mk-etiqueta">%s</text>', $lx - 27, $ly - 11, $lx, $ly + 5, $texto);
};
$lazo = function (string $i) use ($nodos, $radio, $P): string {
    [$x, $y] = $nodos[$i];
    $abajo = in_array($i, ['X', 'N'], true);
    $s = $abajo ? 1 : -1;
    $y0 = $y + $s * ($radio - 6);
    $camino = sprintf('M %.1f %.1f C %.1f %.1f, %.1f %.1f, %.1f %.1f', $x - 12, $y0, $x - 34, $y + $s * 78, $x + 34, $y + $s * 78, $x + 14, $y0 + $s * 3);
    $ty = $y + $s * 70 + ($abajo ? 12 : -2);
    return '<path d="' . $camino . '" class="mk-arista" fill="none" marker-end="url(#mk-punta)"/>'
        . sprintf('<rect x="%.1f" y="%.1f" width="54" height="22" rx="6" class="mk-etiqueta-fondo"/><text x="%.1f" y="%.1f" class="mk-etiqueta">%s</text>', $x - 27, $ty - 16, $x, $ty, numero($P[$i][$i], 3));
};
?>
<style>
.mk-arista { stroke: var(--he-texto-suave); stroke-width: 1.6; }
.mk-punta { fill: var(--he-texto-suave); }
.mk-etiqueta-fondo { fill: var(--he-superficie); stroke: var(--he-borde); }
.mk-etiqueta { font-size: 14px; font-weight: 600; text-anchor: middle; fill: var(--he-texto); font-family: Inter, system-ui, sans-serif; }
.mk-nodo-t { fill: var(--he-azul-suave); stroke: var(--he-azul); stroke-width: 2; }
.mk-nodo-a { fill: var(--he-oro-suave, #F6EEDC); stroke: #C6A15B; stroke-width: 2; }
.mk-letra { font-size: 20px; font-weight: 700; text-anchor: middle; fill: var(--he-texto); font-family: Inter, system-ui, sans-serif; }
.mk-nombre { font-size: 13px; text-anchor: middle; fill: var(--he-texto-suave); font-family: Inter, system-ui, sans-serif; }
.matriz .conteo { font-size: .72rem; color: var(--he-texto-tenue); display: block; margin-top: .15rem; font-weight: 400; }
</style>

<div class="tarjeta mb-4">
    <form class="tarjeta-cuerpo row g-3 align-items-end" method="get">
        <div class="col-md-6 col-xl-2"><label class="form-label" for="desde">Historial desde</label><input type="date" class="form-control" id="desde" name="desde" value="<?= e($a['desde']) ?>"></div>
        <div class="col-md-6 col-xl-2"><label class="form-label" for="hasta">Hasta</label><input type="date" class="form-control" id="hasta" name="hasta" value="<?= e($a['hasta']) ?>"></div>
        <div class="col-md-6 col-xl-3"><label class="form-label" for="categoria">Tipo de habitación</label>
            <select class="form-select" id="categoria" name="categoria"><option value="">Todas</option>
                <?php foreach ($categorias as $c): ?><option value="<?= (int) $c['id_categoria'] ?>"<?= (int) $c['id_categoria'] === (int) $a['categoria'] ? ' selected' : '' ?>><?= e($c['nombre']) ?></option><?php endforeach; ?>
            </select></div>
        <div class="col-md-3 col-xl-1"><label class="form-label" for="n" title="Número de pasos (días)">Horizonte n</label><input type="number" min="1" max="60" class="form-control" id="n" name="n" value="<?= (int) $a['pasos'] ?>"></div>
        <div class="col-md-3 col-xl-2"><label class="form-label" for="inicial">Estado inicial</label>
            <select class="form-select" id="inicial" name="inicial"><option value="C"<?= $ini === 'C' ? ' selected' : '' ?>>C · Confirmada</option><option value="H"<?= $ini === 'H' ? ' selected' : '' ?>>H · En estadía</option></select></div>
        <div class="col-md-6 col-xl-2"><button class="btn btn-primary w-100" type="submit"><i class="bi bi-diagram-3"></i> Generar análisis</button></div>
    </form>
</div>

<?php if ($a['demostrativo']): ?>
    <div class="aviso aviso-oro mb-4"><i class="bi bi-exclamation-triangle"></i><div><strong>Modo demostrativo (datos simulados)</strong>En el periodo elegido hay menos de 30 transiciones desde «Confirmada», así que se muestra la matriz simulada del Segundo Aporte. Amplíe el rango de fechas para usar el historial real.</div></div>
<?php else: ?>
    <div class="aviso aviso-azul mb-4"><i class="bi bi-database-check"></i><div><strong>Matriz generada con el historial real del hotel</strong><?= number_format($a['reservas'], 0, ',', '.') ?> reservas y <?= number_format($a['transiciones'], 0, ',', '.') ?> transiciones día a día entre el <?= e(fecha($a['desde'])) ?> y el <?= e(fecha(min($a['hasta'], date('Y-m-d')))) ?>. Un paso de la cadena = un día.</div></div>
<?php endif; ?>

<div class="row g-4-5 mb-4">
    <?php foreach ([
        ['Reservas analizadas', number_format($a['reservas'], 0, ',', '.'), number_format($a['transiciones'], 0, ',', '.') . ' transiciones observadas', 'bi-journal-bookmark', 'chip-azul'],
        ['Terminará Finalizada', $pct($B[$ini]['F']), 'probabilidad de absorción desde ' . $ini, 'bi-check2-circle', 'chip-verde'],
        ['Terminará Cancelada', $pct($B[$ini]['X']), 'probabilidad de absorción desde ' . $ini, 'bi-x-circle', 'chip-rojo'],
        ['Terminará No presentada', $pct($B[$ini]['N']), 'se envía a Teoría de Decisiones', 'bi-person-x', 'chip-violeta'],
        ['Días hasta resolverse', numero($t[$ini] ?? 0, 1), 'tiempo esperado de absorción t = N·1', 'bi-hourglass-split', 'chip-oro'],
    ] as [$tt, $v, $s, $i, $c]): ?>
        <div class="col-sm-6 col-xxl"><div class="tarjeta kpi"><div class="kpi-cabecera"><span class="kpi-titulo"><?= e($tt) ?></span><span class="icono-chip <?= $c ?>"><i class="bi <?= $i ?>"></i></span></div><div class="kpi-valor"><?= e($v) ?></div><div class="kpi-pie"><?= e($s) ?></div></div></div>
    <?php endforeach; ?>
</div>

<div class="row g-4-5 mb-4">
    <div class="col-xl-5">
        <div class="tarjeta h-100">
            <div class="tarjeta-encabezado"><div><h2>Diagrama de la cadena</h2><div class="subtitulo">Azul: transitorios · dorado: absorbentes · flechas con p<sub>ij</sub></div></div></div>
            <div class="tarjeta-cuerpo">
                <svg viewBox="0 0 525 345" width="100%" role="img" aria-label="Diagrama de estados de la cadena de Markov">
                    <defs><marker id="mk-punta" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0,0 L10,5 L0,10 z" class="mk-punta"/></marker></defs>
                    <?= $flecha('C', 'H') ?><?= $flecha('H', 'F') ?><?= $flecha('C', 'X') ?><?= $flecha('C', 'N') ?>
                    <?php foreach (array_keys($E) as $s): ?><?= $lazo($s) ?><?php endforeach; ?>
                    <?php foreach ($nodos as $s => [$x, $y]): ?>
                        <circle cx="<?= $x ?>" cy="<?= $y ?>" r="<?= $radio ?>" class="<?= in_array($s, CadenaMarkov::TRANSITORIOS, true) ? 'mk-nodo-t' : 'mk-nodo-a' ?>"/>
                        <text x="<?= $x ?>" y="<?= $y + 6 ?>" class="mk-letra"><?= $s ?></text>
                        <text x="<?= $x + ($s === 'X' ? -38 : ($s === 'N' ? 38 : 0)) ?>" y="<?= in_array($s, ['X', 'N'], true) ? $y + 5 : $y + 50 ?>" class="mk-nombre" style="text-anchor: <?= $s === 'X' ? 'end' : ($s === 'N' ? 'start' : 'middle') ?>"><?= e($E[$s]) ?></text>
                    <?php endforeach; ?>
                </svg>
            </div>
        </div>
    </div>
    <div class="col-xl-7">
        <div class="tarjeta h-100">
            <div class="tarjeta-encabezado"><div><h2>Matriz de transición P</h2><div class="subtitulo">p<sub>ij</sub> = n<sub>ij</sub> / Σ<sub>k</sub> n<sub>ik</sub> · debajo, el conteo n<sub>ij</sub> de días observados</div></div>
                <span class="insignia <?= $a['valida'] ? 'insignia-verde' : 'insignia-rojo' ?>"><i class="bi <?= $a['valida'] ? 'bi-check2' : 'bi-x' ?>"></i>Cada fila suma 1</span></div>
            <div class="tabla-contenedor">
                <table class="matriz">
                    <thead><tr><th>Actual \ siguiente</th><?php foreach ($E as $j => $nombre): ?><th><?= $j ?><span class="prob"><?= e($nombre) ?></span></th><?php endforeach; ?><th>Total n<sub>i</sub></th></tr></thead>
                    <tbody>
                    <?php foreach ($E as $i => $nombre): ?>
                        <tr>
                            <td><strong><?= $i ?></strong> · <?= e($nombre) ?></td>
                            <?php foreach ($E as $j => $_): ?>
                                <td class="tabular<?= $P[$i][$j] > 0 ? ' fw-semibold' : ' texto-tenue' ?>"><?= numero($P[$i][$j], 4) ?><?php if (!$a['demostrativo'] && in_array($i, CadenaMarkov::TRANSITORIOS, true)): ?><span class="conteo"><?= number_format($a['conteos'][$i][$j], 0, ',', '.') ?></span><?php endif; ?></td>
                            <?php endforeach; ?>
                            <td class="tabular ve"><?= in_array($i, CadenaMarkov::TRANSITORIOS, true) ? number_format(array_sum($a['conteos'][$i]), 0, ',', '.') : 'absorbente' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="tarjeta-cuerpo pt-3 fs-sm texto-suave">Ejemplo: de <?= number_format(array_sum($a['conteos']['C']), 0, ',', '.') ?> días en que una reserva estaba Confirmada, al día siguiente pasó a En estadía <?= number_format($a['conteos']['C']['H'], 0, ',', '.') ?> veces → p<sub>CH</sub> = <?= numero($P['C']['H'], 4) ?>. Los estados X, N y F son absorbentes (p<sub>ii</sub> = 1).</div>
        </div>
    </div>
</div>

<div class="row g-4-5 mb-4">
    <div class="col-xl-7">
        <div class="tarjeta h-100">
            <div class="tarjeta-encabezado"><div><h2>Evolución V<sub>n</sub> = V<sub>0</sub> · P<sup>n</sup></h2><div class="subtitulo">Probabilidad de estar en cada estado después de n días, partiendo de <?= e($E[$ini]) ?></div></div></div>
            <div class="tarjeta-cuerpo"><div class="grafico grafico-md"><canvas id="grafico-evolucion"></canvas></div></div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="tarjeta h-100">
            <div class="tarjeta-encabezado"><div><h2>Probabilidades de n pasos</h2><div class="subtitulo">V<sub>0</sub> = [<?= implode(', ', array_map(fn ($v) => (int) $v, CadenaMarkov::vectorInicial($ini))) ?>] · horizonte n = <?= (int) $a['pasos'] ?></div></div></div>
            <div class="tabla-contenedor">
                <table class="matriz">
                    <thead><tr><th>Periodo</th><?php foreach ($E as $j => $_): ?><th><?= $j ?></th><?php endforeach; ?></tr></thead>
                    <tbody>
                    <?php foreach ($filasV as $k): ?>
                        <tr class="<?= $k === $a['pasos'] ? 'optima' : '' ?>"><td>V<sub><?= $k ?></sub></td><?php foreach ($a['evolucion'][$k] as $v): ?><td class="tabular"><?= numero($v, 4) ?></td><?php endforeach; ?></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="tarjeta-cuerpo pt-3 fs-sm">Después de <strong><?= (int) $a['pasos'] ?> días</strong>, una reserva que hoy está <?= e($E[$ini]) ?> tiene <strong><?= $pct($Vn['F']) ?></strong> de haber finalizado, <?= $pct($Vn['X']) ?> de estar cancelada, <?= $pct($Vn['N']) ?> de ser no-show y <?= $pct($Vn['C'] + $Vn['H']) ?> de seguir activa.</div>
        </div>
    </div>
</div>

<div class="row g-4-5 mb-4">
    <div class="col-xl-7">
        <div class="tarjeta h-100">
            <div class="tarjeta-encabezado"><div><h2>Probabilidades de absorción</h2><div class="subtitulo">Forma canónica: Q (transitorios), R (hacia absorbentes) · N = (I − Q)<sup>−1</sup> · B = N·R · t = N·1</div></div></div>
            <div class="tarjeta-cuerpo">
                <div class="row g-3">
                    <?php
                    $mini = function (string $titulo, ?array $m, int $dec = 4): string {
                        if ($m === null) {
                            return '<div class="fs-sm texto-tenue">' . e($titulo) . ': no tiene inversa</div>';
                        }
                        $cols = array_keys(reset($m));
                        $h = '<div class="fs-xs fw-semibold mb-1">' . $titulo . '</div><table class="matriz"><thead><tr><th></th>';
                        foreach ($cols as $c) { $h .= '<th>' . e($c) . '</th>'; }
                        $h .= '</tr></thead><tbody>';
                        foreach ($m as $i => $fila) {
                            $h .= '<tr><td><strong>' . e((string) $i) . '</strong></td>';
                            foreach ($fila as $v) { $h .= '<td class="tabular">' . numero($v, $dec) . '</td>'; }
                            $h .= '</tr>';
                        }
                        return $h . '</tbody></table>';
                    };
                    ?>
                    <div class="col-md-6"><?= $mini('Q', $a['absorcion']['Q']) ?></div>
                    <div class="col-md-6"><?= $mini('R', $a['absorcion']['R']) ?></div>
                    <div class="col-md-6"><?= $mini('I − Q', $a['absorcion']['I_Q']) ?></div>
                    <div class="col-md-6"><?= $mini('N = (I − Q)<sup>−1</sup> (días esperados en cada estado)', $N) ?></div>
                    <div class="col-md-8"><?= $mini('B = N · R (probabilidad de terminar en cada absorbente)', $B) ?></div>
                    <div class="col-md-4"><?= $mini('t = N · 1 (días)', $t === null ? null : array_map(fn ($v) => ['días' => $v], $t), 2) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="tarjeta mb-4">
            <div class="tarjeta-encabezado"><div><h2>Validación con datos reales</h2><div class="subtitulo">Modelo (fila C de B) frente a cómo terminaron las <?= number_format($a['observado']['total'], 0, ',', '.') ?> reservas ya cerradas</div></div></div>
            <div class="tabla-contenedor">
                <table class="matriz">
                    <thead><tr><th>Resultado final</th><th>Markov</th><th>Real</th><th>Diferencia</th></tr></thead>
                    <tbody>
                    <?php foreach (['F', 'X', 'N'] as $s): ?>
                        <tr><td><?= $s ?> · <?= e($E[$s]) ?></td><td class="tabular"><?= $pct($B['C'][$s], 2) ?></td><td class="tabular"><?= $pct($a['observado']['proporcion'][$s], 2) ?> <span class="conteo d-inline">(<?= number_format($a['observado']['cuenta'][$s], 0, ',', '.') ?>)</span></td><td class="tabular"><?= numero(($B['C'][$s] - $a['observado']['proporcion'][$s]) * 100, 2) ?> pp</td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="tarjeta">
            <div class="tarjeta-encabezado"><div><h2>Clasificación de estados</h2></div></div>
            <?php foreach ($a['clases'] as $s => $clase): ?>
                <div class="item-lista py-2"><span class="insignia <?= $clase === 'Transitorio' ? 'insignia-azul' : 'insignia-oro' ?>"><?= $s ?></span><div class="flex-grow-1 fs-sm"><strong><?= e($E[$s]) ?></strong> · <?= e($clase) ?></div></div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="tarjeta">
    <div class="tarjeta-encabezado"><div><h2>Conexión con Teoría de Decisiones</h2><div class="subtitulo">Historial → Markov → P(no-show) → Teoría de Decisiones → recomendación de sobreventa</div></div></div>
    <div class="tarjeta-cuerpo row g-4 align-items-center">
        <div class="col-lg-8 fs-sm">
            La política de sobreventa necesita la probabilidad <em>p</em> de que un huésped con reserva no llegue. Antes se usaba sólo la tasa mensual (no-show ÷ reservas del mes, CU-17).
            Con la cadena, <em>p</em> es la probabilidad de que una reserva Confirmada termine absorbida en «No presentada»:
            <div class="formula my-2">p<sub>Markov</sub> = B<sub>C,N</sub> = <?= numero($B['C']['N'], 4) ?> &nbsp;·&nbsp; p<sub>tasa mensual</sub> = <?= numero($historica['p_no_show'], 4) ?></div>
            La tasa mensual sale de la tabla <code>tasa_historica</code> y sólo cambia cuando alguien pulsa «Recalcular tasas». La cadena se arma en el momento con todo el historial, así que siempre está al día. Proporción real de no-show en las reservas ya cerradas: <?= $pct($a['observado']['proporcion']['N'], 2) ?>.
        </div>
        <div class="col-lg-4 text-lg-end">
            <?php if (puede('sobreventa.ver')): ?>
                <a class="btn btn-primary" href="<?= url('/panel/sobreventa', ['fuente' => 'MARKOV']) ?>"><i class="bi bi-shield-check"></i> Evaluar sobreventa con Markov</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php $this->seccion('scripts') ?>
<script src="<?= vendor('chartjs/chart.umd.min.js') ?>"></script>
<script>
(function () {
    var V = <?= json_encode(array_map(fn ($v) => array_map(fn ($x) => round($x * 100, 2), $v), $a['evolucion'])) ?>;
    var nombres = <?= json_encode($E) ?>;
    HE.grafico(document.getElementById('grafico-evolucion'), function (c) {
        var colores = { C: c.noche, H: c.azul, X: c.rojo, N: c.violeta, F: c.verde };
        return {
            type: 'line',
            data: { labels: V.map(function (_, k) { return k; }), datasets: Object.keys(nombres).map(function (s) {
                return { label: s + ' · ' + nombres[s], data: V.map(function (v) { return v[s]; }), borderColor: colores[s], backgroundColor: colores[s], tension: .25, pointRadius: 0, borderWidth: 2.2 };
            }) },
            options: { interaction: { mode: 'index', intersect: false }, plugins: { legend: { position: 'bottom' }, tooltip: { callbacks: { title: function (x) { return 'Día ' + x[0].label; }, label: function (x) { return x.dataset.label + ': ' + x.parsed.y.toFixed(2) + ' %'; } } } },
                scales: { y: { min: 0, max: 100, ticks: { callback: function (v) { return v + ' %'; } }, grid: { color: c.rejilla } }, x: { title: { display: true, text: 'Días (n)' }, grid: { display: false } } } }
        };
    });
})();
</script>
<?php $this->fin() ?>
