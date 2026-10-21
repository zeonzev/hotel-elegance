<?php
/** @var \App\Core\Vista $this */
$u = usuario();
$hora = (int) date('G');
$saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
$ocupacionHoy = $noche['porcentaje'];
$varIngresos = \App\Servicios\DashboardServicio::variacion($ingresosMes, $ingresosAnterior);
$varAdr = \App\Servicios\DashboardServicio::variacion($mes['adr'], $mesAnterior['adr']);
$varRevpar = \App\Servicios\DashboardServicio::variacion($mes['revpar'], $mesAnterior['revpar']);
$delta = function (?float $v): string {
    if ($v === null) return '<span class="texto-tenue">sin referencia</span>';
    $clase = $v >= 0 ? 'delta-sube' : 'delta-baja';
    $icono = $v >= 0 ? 'bi-arrow-up-right' : 'bi-arrow-down-right';
    return '<span class="delta ' . $clase . '"><i class="bi ' . $icono . '"></i>' . numero(abs($v), 1) . ' %</span>';
};
?>
<div class="pagina-encabezado">
    <div>
        <div class="sobretitulo"><?= e(ucfirst(fecha(hoy(), 'larga'))) ?> · <?= e(DIAS[(int) date('N')]) ?></div>
        <h1><?= e($saludo) ?>, <?= e(explode(' ', $u['nombres'])[0]) ?></h1>
        <p>Resumen de la operación del Hotel Elegance: ocupación, movimiento del día, ingresos y alertas del modelo de sobreventa.</p>
    </div>
    <div class="pagina-acciones">
        <?php if (puede('recepcion.operar')): ?>
            <a href="<?= url('/panel/recepcion') ?>" class="btn btn-claro"><i class="bi bi-bell"></i> Recepción del día</a>
        <?php endif; ?>
        <?php if (puede('reportes.ver')): ?>
            <a href="<?= url('/panel/reportes') ?>" class="btn btn-claro"><i class="bi bi-bar-chart-line"></i> Reportes</a>
        <?php endif; ?>
        <?php if (puede('reservas.gestionar')): ?>
            <a href="<?= url('/panel/reservas/nueva') ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nueva reserva</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($alertas && puede('sobreventa.alertas')): ?>
    <div class="aviso aviso-rojo mb-4 animar-entrada">
        <i class="bi bi-shield-exclamation"></i>
        <div class="flex-grow-1">
            <strong>Alerta de sobreventa (CU-19): <?= count($alertas) ?> noche(s) superan el umbral del <?= numero($config['umbral_ocupacion']) ?> %</strong>
            <div class="d-flex flex-wrap gap-2 mt-2">
                <?php foreach (array_slice($alertas, 0, 6, true) as $f => $a): ?>
                    <span class="insignia" style="background: var(--he-superficie); color: var(--he-texto)"><?= e(fecha($f, 'dia')) ?> · <b class="text-danger"><?= numero($a['porcentaje'], 0) ?> %</b></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php if (puede('sobreventa.ver')): ?>
            <a href="<?= url('/panel/sobreventa', ['fecha' => array_key_first($alertas)]) ?>" class="btn btn-sm btn-peligro">Ver recomendación</a>
        <?php else: ?>
            <a href="<?= url('/panel/disponibilidad') ?>" class="btn btn-sm btn-peligro">Ver disponibilidad</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="row g-4-5 mb-4 animar-entrada">
    <div class="col-xl-5">
        <div class="tarjeta tarjeta-noche h-100">
            <div class="tarjeta-cuerpo p-4 d-flex flex-column h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="sobretitulo" style="color: var(--he-oro-300)">Ocupación esta noche</div>
                        <div class="d-flex align-items-baseline gap-2 mt-2">
                            <span class="serif" style="font-size: 4.2rem; line-height: .9; color: #fff"><?= numero($ocupacionHoy, 0) ?></span>
                            <span class="serif fs-2" style="color: var(--he-oro-300)">%</span>
                        </div>
                        <div class="texto-suave fs-sm mt-1"><?= (int) $noche['ocupadas'] ?> de <?= (int) $capacidad ?> habitaciones vendidas para hoy</div>
                    </div>
                    <div style="width: 132px; height: 132px; position: relative">
                        <canvas id="grafico-anillo" aria-label="Estado de habitaciones"></canvas>
                        <div class="position-absolute top-50 start-50 translate-middle text-center">
                            <div class="fw-semibold fs-5 text-white"><?= (int) $alojados['personas'] ?></div>
                            <div style="font-size:.62rem; letter-spacing:.12em; color:#A7AFBE">HUÉSPEDES</div>
                        </div>
                    </div>
                </div>
                <div class="row g-2 mt-auto pt-4">
                    <?php foreach (['DISPONIBLE' => ['Disponibles', '#5CC293'], 'OCUPADA' => ['Ocupadas', '#7FB0E3'], 'LIMPIEZA' => ['Limpieza', '#E3A953'], 'MANTENIMIENTO' => ['Mantenimiento', '#EE8A80']] as $estado => [$etiqueta, $color]): ?>
                        <div class="col-6 col-sm-3">
                            <a href="<?= url('/panel/habitaciones', ['estado' => $estado]) ?>" class="d-block rounded-3 p-2 px-3" style="background: rgba(255,255,255,.05); color: inherit">
                                <div class="d-flex align-items-center gap-2 fs-xs" style="color:#A7AFBE"><span style="width:8px;height:8px;flex-shrink:0;border-radius:50%;background:<?= $color ?>"></span><?= $etiqueta ?></div>
                                <div class="fs-4 fw-semibold text-white tabular"><?= (int) $estados[$estado] ?></div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-7">
        <div class="row g-4-5 h-100">
            <div class="col-sm-6">
                <div class="tarjeta kpi">
                    <div class="kpi-cabecera"><span class="kpi-titulo">Llegadas de hoy</span><span class="icono-chip chip-azul"><i class="bi bi-box-arrow-in-right"></i></span></div>
                    <div class="kpi-valor"><?= (int) $llegadas['total'] ?> <small>reservas</small></div>
                    <div class="barra-progreso azul mt-3"><span data-valor="<?= $llegadas['total'] ? $llegadas['hechas'] / $llegadas['total'] * 100 : 0 ?>" style="width:0"></span></div>
                    <div class="kpi-pie"><?= (int) $llegadas['hechas'] ?> con check-in · <?= (int) $llegadas['total'] - (int) $llegadas['hechas'] ?> pendientes</div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="tarjeta kpi">
                    <div class="kpi-cabecera"><span class="kpi-titulo">Salidas de hoy</span><span class="icono-chip chip-ambar"><i class="bi bi-box-arrow-right"></i></span></div>
                    <div class="kpi-valor"><?= (int) $salidas['total'] ?> <small>reservas</small></div>
                    <div class="barra-progreso mt-3"><span data-valor="<?= $salidas['total'] ? $salidas['hechas'] / $salidas['total'] * 100 : 0 ?>" style="width:0"></span></div>
                    <div class="kpi-pie"><?= (int) $salidas['hechas'] ?> con check-out · <?= (int) $salidas['total'] - (int) $salidas['hechas'] ?> pendientes</div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="tarjeta kpi">
                    <div class="kpi-cabecera"><span class="kpi-titulo">Ingresos cobrados del mes</span><span class="icono-chip chip-verde"><i class="bi bi-cash-coin"></i></span></div>
                    <div class="kpi-valor"><?= e(bs($ingresosMes, 0)) ?></div>
                    <div class="kpi-pie"><?= $delta($varIngresos) ?> vs. mismo periodo del mes anterior</div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="tarjeta kpi">
                    <div class="kpi-cabecera">
                        <span class="kpi-titulo" data-bs-toggle="tooltip" title="ADR: ingreso por habitaciones ÷ noches vendidas · RevPAR: ingreso ÷ noches disponibles">ADR · RevPAR del mes <i class="bi bi-info-circle texto-tenue"></i></span>
                        <span class="icono-chip chip-oro"><i class="bi bi-graph-up-arrow"></i></span>
                    </div>
                    <div class="d-flex gap-4">
                        <div><div class="kpi-valor"><?= e(bs($mes['adr'], 0)) ?></div><div class="kpi-pie mt-2"><?= $delta($varAdr) ?></div></div>
                        <div><div class="kpi-valor" style="color: var(--he-texto-suave)"><?= e(bs($mes['revpar'], 0)) ?></div><div class="kpi-pie mt-2"><?= $delta($varRevpar) ?></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4-5 mb-4">
    <div class="col-xl-8">
        <div class="tarjeta h-100">
            <div class="tarjeta-encabezado">
                <div><h2>Ocupación diaria</h2><div class="subtitulo">Últimos 30 días (real) y próximos 14 días (reservado)</div></div>
                <div class="leyenda"><span><i style="background:var(--he-noche-700)"></i>Ocupación</span><span><i style="background:var(--he-oro-500)"></i>Reservado a futuro</span><span><i style="background:var(--he-rojo)"></i>Umbral <?= numero($config['umbral_ocupacion']) ?> %</span></div>
            </div>
            <div class="tarjeta-cuerpo"><div class="grafico grafico-md"><canvas id="grafico-tendencia"></canvas></div></div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="tarjeta h-100">
            <div class="tarjeta-encabezado"><div><h2>Reservas por canal</h2><div class="subtitulo">Registradas en los últimos 90 días</div></div></div>
            <div class="tarjeta-cuerpo">
                <div class="grafico grafico-sm"><canvas id="grafico-canales"></canvas></div>
                <ul class="lista-limpia mt-3 fs-sm" id="leyenda-canales"></ul>
            </div>
        </div>
    </div>
</div>

<div class="row g-4-5 mb-4">
    <div class="col-xl-8">
        <div class="tarjeta h-100">
            <div class="tarjeta-encabezado">
                <div><h2>Ingresos cobrados por mes</h2><div class="subtitulo">Pagos registrados en bolivianos, últimos 12 meses</div></div>
                <span class="insignia insignia-oro"><i class="bi bi-calendar3"></i>Total: <?= e(bs(array_sum($ingresosMensuales), 0)) ?></span>
            </div>
            <div class="tarjeta-cuerpo"><div class="grafico grafico-md"><canvas id="grafico-ingresos"></canvas></div></div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="tarjeta h-100">
            <div class="tarjeta-encabezado">
                <div><h2>Próximas noches críticas</h2><div class="subtitulo">Ocupación ≥ <?= numero($config['umbral_ocupacion']) ?> % en 14 días</div></div>
                <span class="icono-chip chip-rojo" style="width:34px;height:34px"><i class="bi bi-shield-exclamation"></i></span>
            </div>
            <?php if ($alertas): ?>
                <div>
                    <?php foreach (array_slice($alertas, 0, 6, true) as $f => $a): ?>
                        <a class="item-lista" href="<?= url(puede('sobreventa.ver') ? '/panel/sobreventa' : '/panel/disponibilidad', ['fecha' => $f, 'desde' => $f]) ?>">
                            <div class="text-center" style="min-width:44px"><div class="fs-xs texto-tenue text-uppercase"><?= e(DIAS_CORTOS[(int) date('N', strtotime($f))]) ?></div><div class="serif fs-3 lh-1 fw-semibold"><?= (int) date('j', strtotime($f)) ?></div></div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex justify-content-between fs-sm"><span><?= (int) $a['ocupadas'] ?> / <?= (int) $a['capacidad'] ?> habitaciones</span><strong class="<?= $a['porcentaje'] >= 100 ? 'text-danger' : 'texto-oro' ?>"><?= numero($a['porcentaje'], 0) ?> %</strong></div>
                                <div class="barra-progreso rojo mt-2"><span data-valor="<?= min(100, $a['porcentaje']) ?>" style="width:0"></span></div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="vacio py-5"><div class="vacio-icono"><i class="bi bi-shield-check"></i></div><h3>Sin alertas</h3><p>Ninguna noche de las próximas dos semanas supera el umbral configurado.</p></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-4-5">
    <div class="col-xl-6">
        <div class="tarjeta h-100">
            <div class="tarjeta-encabezado">
                <div><h2>Llegadas de hoy</h2><div class="subtitulo"><?= (int) $llegadas['total'] ?> reservas con ingreso hoy</div></div>
                <?php if (puede('recepcion.operar')): ?><a class="btn btn-sm btn-claro" href="<?= url('/panel/recepcion') ?>">Ir a recepción</a><?php endif; ?>
            </div>
            <?php if ($listaLlegadas): ?>
                <div class="tabla-contenedor"><table class="tabla tabla-compacta"><tbody>
                    <?php foreach ($listaLlegadas as $r): ?>
                        <tr data-href="<?= url('/panel/reservas/' . $r['id_reserva']) ?>" class="fila-enlace">
                            <td><div class="persona"><span class="avatar avatar-sm avatar-noche"><?= e(iniciales($r['huesped'])) ?></span><div><div class="celda-principal truncar"><?= e($r['huesped']) ?></div><div class="celda-secundaria"><?= e($r['ciudad_procedencia']) ?> · <?= (int) $r['cantidad_personas'] ?> pers.</div></div></div></td>
                            <td><span class="etiqueta-codigo"><?= e($r['habitaciones']) ?></span></td>
                            <td class="text-end"><?= insignia($r['estado']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody></table></div>
            <?php else: ?>
                <div class="vacio py-5"><div class="vacio-icono"><i class="bi bi-door-open"></i></div><h3>Sin llegadas</h3><p>No hay ingresos programados para hoy.</p></div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="tarjeta h-100">
            <div class="tarjeta-encabezado"><div><h2>Salidas de hoy</h2><div class="subtitulo"><?= (int) $salidas['total'] ?> reservas con salida hoy</div></div></div>
            <?php if ($listaSalidas): ?>
                <div class="tabla-contenedor"><table class="tabla tabla-compacta"><tbody>
                    <?php foreach ($listaSalidas as $r): $saldo = (float) $r['monto_total'] + (float) $r['cargos_extra'] - (float) $r['pagado']; ?>
                        <tr data-href="<?= url('/panel/reservas/' . $r['id_reserva']) ?>" class="fila-enlace">
                            <td><div class="persona"><span class="avatar avatar-sm"><?= e(iniciales($r['huesped'])) ?></span><div><div class="celda-principal truncar"><?= e($r['huesped']) ?></div><div class="celda-secundaria"><?= e($r['codigo']) ?></div></div></div></td>
                            <td><span class="etiqueta-codigo"><?= e($r['habitaciones'] ?: '—') ?></span></td>
                            <td class="tabular <?= $saldo > 0 ? 'texto-oro fw-semibold' : 'texto-tenue' ?>"><?= $saldo > 0 ? 'Saldo ' . e(bs($saldo, 0)) : 'Pagado' ?></td>
                            <td class="text-end"><?= insignia($r['estado']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody></table></div>
            <?php else: ?>
                <div class="vacio py-5"><div class="vacio-icono"><i class="bi bi-luggage"></i></div><h3>Sin salidas</h3><p>No hay check-outs programados para hoy.</p></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (puede('bitacora.ver') && $actividad): ?>
    <div class="tarjeta mt-4">
        <div class="tarjeta-encabezado"><div><h2>Actividad reciente</h2><div class="subtitulo">Últimas acciones registradas en la bitácora</div></div><a href="<?= url('/panel/bitacora') ?>" class="btn btn-sm btn-claro">Ver bitácora</a></div>
        <div class="tarjeta-cuerpo">
            <ul class="linea-tiempo">
                <?php foreach ($actividad as $a): ?>
                    <li><span class="marca"><i class="bi bi-dot"></i></span><strong><?= e($a['accion']) ?></strong> <span class="texto-tenue fs-sm">· <?= e($a['modulo']) ?></span><small><?= e($a['descripcion']) ?> — @<?= e($a['nombre_usuario'] ?? 'sistema') ?>, <?= e(fecha($a['fecha'], 'hora')) ?></small></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>

<?php $this->seccion('scripts') ?>
<script src="<?= vendor('chartjs/chart.umd.min.js') ?>"></script>
<script>
(function () {
    var tendencia = <?= json_encode(array_map(fn ($f, $o) => ['f' => $f, 'p' => $o['porcentaje']], array_keys($tendencia), $tendencia)) ?>;
    var hoy = <?= json_encode(hoy()) ?>;
    var umbral = <?= (float) $config['umbral_ocupacion'] ?>;
    var dias = ['dom', 'lun', 'mar', 'mié', 'jue', 'vie', 'sáb'];
    var meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    var etiqueta = function (f) { var d = new Date(f + 'T12:00:00'); return d.getDate() + ' ' + meses[d.getMonth()]; };

    HE.grafico(document.getElementById('grafico-anillo'), function (c) {
        return {
            type: 'doughnut',
            data: { labels: ['Disponibles', 'Ocupadas', 'Limpieza', 'Mantenimiento'], datasets: [{ data: <?= json_encode(array_values($estados)) ?>, backgroundColor: ['#5CC293', '#7FB0E3', '#E3A953', '#EE8A80'], borderWidth: 0, spacing: 2, borderRadius: 4 }] },
            options: { cutout: '76%', plugins: { legend: { display: false } } }
        };
    });

    var lienzo = document.getElementById('grafico-tendencia');
    HE.grafico(lienzo, function (c) {
        return {
            type: 'line',
            data: {
                labels: tendencia.map(function (t) { return etiqueta(t.f); }),
                datasets: [
                    { label: 'Ocupación', data: tendencia.map(function (t) { return t.f <= hoy ? t.p : null; }), borderColor: c.noche, backgroundColor: HE.degradado(lienzo, c.noche, .18), fill: true, tension: .35, pointRadius: 0, pointHoverRadius: 5, borderWidth: 2.2 },
                    { label: 'Reservado a futuro', data: tendencia.map(function (t) { return t.f >= hoy ? t.p : null; }), borderColor: c.oro, backgroundColor: HE.degradado(lienzo, c.oro, .2), fill: true, tension: .35, pointRadius: 0, pointHoverRadius: 5, borderWidth: 2.2, borderDash: [6, 4] },
                    { label: 'Umbral', data: tendencia.map(function () { return umbral; }), borderColor: c.rojo, borderWidth: 1.2, borderDash: [3, 3], pointRadius: 0, fill: false }
                ]
            },
            options: {
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (x) { return x.dataset.label + ': ' + (x.parsed.y == null ? '—' : x.parsed.y.toFixed(1) + ' %'); } } } },
                scales: { y: { min: 0, max: 110, ticks: { callback: function (v) { return v + ' %'; }, stepSize: 25 }, grid: { color: c.rejilla } }, x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkipPadding: 18 } } }
            }
        };
    });

    var canales = <?= json_encode($canales) ?>;
    HE.grafico(document.getElementById('grafico-canales'), function (c) {
        return {
            type: 'doughnut',
            data: { labels: canales.map(function (x) { return x.nombre; }), datasets: [{ data: canales.map(function (x) { return +x.total; }), backgroundColor: c.serie, borderWidth: 2, borderColor: c.superficie, hoverOffset: 6 }] },
            options: { cutout: '62%', plugins: { legend: { display: false } } }
        };
    });
    var total = canales.reduce(function (s, x) { return s + (+x.total); }, 0) || 1;
    var colores = HE.colores().serie;
    document.getElementById('leyenda-canales').innerHTML = canales.map(function (x, i) {
        return '<li class="d-flex align-items-center gap-2 py-1"><span style="width:10px;height:10px;border-radius:3px;background:' + colores[i % colores.length] + '"></span><span class="flex-grow-1">' + HE.escapar(x.nombre) + '</span><span class="texto-tenue tabular">' + x.total + '</span><strong class="tabular" style="width:48px;text-align:right">' + Math.round(x.total / total * 100) + ' %</strong></li>';
    }).join('');

    var ingresos = <?= json_encode($ingresosMensuales) ?>;
    var lienzoIng = document.getElementById('grafico-ingresos');
    HE.grafico(lienzoIng, function (c) {
        var claves = Object.keys(ingresos);
        return {
            type: 'bar',
            data: { labels: claves.map(function (k) { var p = k.split('-'); return meses[+p[1] - 1] + ' ' + p[0].slice(2); }), datasets: [{ label: 'Ingresos', data: claves.map(function (k) { return ingresos[k]; }), backgroundColor: claves.map(function (k, i) { return i === claves.length - 1 ? c.oro : HE.alfa(c.oro === '#C6A15B' && !c.oscuro ? '#1F3452' : '#C6A15B', c.oscuro ? .55 : .85); }), borderRadius: 8, maxBarThickness: 38 }] },
            options: {
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (x) { return HE.bs(x.parsed.y, 0); } } } },
                scales: { y: { ticks: { callback: function (v) { return 'Bs ' + (v / 1000) + 'k'; } }, grid: { color: c.rejilla } }, x: { grid: { display: false } } }
            }
        };
    });
})();
</script>
<?php $this->fin() ?>
