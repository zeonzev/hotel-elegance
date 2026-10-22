<?php
/** @var \App\Core\Vista $this */
echo $this->parcial('parciales/encabezado', [
    'titulo' => 'Disponibilidad',
    'cu' => 'CU-12',
    'descripcion' => 'Consulte las habitaciones libres para un rango de fechas y revise el calendario de ocupación del hotel.',
    'migas' => [['Operación', null], ['Disponibilidad', null]],
    'acciones' => puede('reservas.gestionar') ? '<a class="btn btn-primary" href="' . url('/panel/reservas/nueva') . '"><i class="bi bi-plus-lg"></i> Nueva reserva</a>' : '',
]);
$fechas = $rack['fechas'];
$hoy = hoy();
$puedeReservar = puede('reservas.gestionar');
$colorPunto = ['DISPONIBLE' => 'var(--he-verde)', 'OCUPADA' => 'var(--he-azul)', 'LIMPIEZA' => 'var(--he-ambar)', 'MANTENIMIENTO' => 'var(--he-rojo)'];
$navegar = fn (int $delta) => url('/panel/disponibilidad', array_filter(['desde' => date('Y-m-d', strtotime("$desde $delta days")), 'dias' => $dias, 'categoria' => $categoria, 'piso' => $piso]));
?>
<div class="tarjeta mb-4">
    <div class="tarjeta-encabezado"><div><h2>Consultar disponibilidad</h2><div class="subtitulo">Habitaciones que no están en mantenimiento y sin reservas activas que se crucen con las fechas</div></div></div>
    <form class="tarjeta-cuerpo row g-3 align-items-end" method="get">
        <input type="hidden" name="desde" value="<?= e($desde) ?>"><input type="hidden" name="dias" value="<?= $dias ?>">
        <div class="col-sm-6 col-lg-3"><label class="form-label" for="ingreso">Fecha de ingreso</label><input type="date" class="form-control" id="ingreso" name="ingreso" value="<?= e($ingreso ?: $hoy) ?>" required></div>
        <div class="col-sm-6 col-lg-3"><label class="form-label" for="salida">Fecha de salida</label><input type="date" class="form-control" id="salida" name="salida" value="<?= e($salida ?: date('Y-m-d', strtotime('+2 days'))) ?>" required></div>
        <div class="col-sm-6 col-lg-3"><label class="form-label" for="categoria">Categoría</label>
            <select class="form-select" id="categoria" name="categoria"><option value="">Todas</option><?php foreach ($categorias as $c): ?><option value="<?= $c['id_categoria'] ?>"<?= seleccionado($categoria, $c['id_categoria']) ?>><?= e($c['nombre']) ?></option><?php endforeach; ?></select></div>
        <div class="col-sm-6 col-lg-3"><button class="btn btn-primary w-100" type="submit"><i class="bi bi-search"></i> Consultar</button></div>
    </form>
    <?php if ($errorBusqueda): ?>
        <div class="px-4 pb-4"><div class="aviso aviso-rojo"><i class="bi bi-exclamation-circle"></i><div><strong><?= e($errorBusqueda) ?></strong></div></div></div>
    <?php elseif ($resultado): ?>
        <div class="px-4 pb-4">
            <?php $grupos = []; foreach ($resultado['libres'] as $h) { $grupos[$h['categoria']][] = $h; } ?>
            <?php if (!$grupos): ?>
                <div class="aviso aviso-oro"><i class="bi bi-info-circle"></i><div><strong>No hay habitaciones disponibles para esas fechas (CU-12, flujo 3a).</strong>
                    <span class="fs-sm">Ocupación del rango: <?php foreach ($resultado['ocupacion'] as $f => $o): ?><span class="me-2"><?= e(fecha($f, 'dia')) ?> <b><?= numero($o['porcentaje'], 0) ?> %</b></span><?php endforeach; ?></span></div></div>
            <?php else: ?>
                <div class="fs-sm texto-suave mb-3"><strong class="texto-oro"><?= count($resultado['libres']) ?> habitación(es) libre(s)</strong> del <?= e(fecha($ingreso, 'larga')) ?> al <?= e(fecha($salida, 'larga')) ?> · <?= $resultado['noches'] ?> noche(s)</div>
                <div class="row g-3">
                    <?php foreach ($grupos as $nombre => $lista): $cot = $lista[0]['cotizacion']; ?>
                        <div class="col-md-6 col-xl-3">
                            <div class="rounded-4 border p-3 h-100" style="border-color: var(--he-borde) !important">
                                <div class="d-flex justify-content-between"><strong><?= e($nombre) ?></strong><span class="insignia insignia-verde"><?= count($lista) ?> libres</span></div>
                                <div class="fs-xs texto-tenue mb-2"><?= (int) $lista[0]['capacidad'] ?> pers. · <?= e(bs($cot['promedio'], 0)) ?>/noche · total <?= e(bs($cot['total'], 0)) ?></div>
                                <?php if ($cot['temporadas']): ?><div class="fs-xs texto-oro mb-2"><i class="bi bi-tag"></i> <?= e(implode(', ', $cot['temporadas'])) ?></div><?php endif; ?>
                                <div class="d-flex flex-wrap gap-1">
                                    <?php foreach ($lista as $h): ?>
                                        <?php if ($puedeReservar): ?>
                                            <a class="etiqueta-codigo" href="<?= url('/panel/reservas/nueva', ['ingreso' => $ingreso, 'salida' => $salida, 'habitacion' => $h['id_habitacion']]) ?>" title="Reservar la <?= e($h['numero']) ?>"><?= e($h['numero']) ?></a>
                                        <?php else: ?><span class="etiqueta-codigo"><?= e($h['numero']) ?></span><?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<div class="tarjeta">
    <div class="tarjeta-encabezado">
        <div><h2>Calendario de ocupación</h2><div class="subtitulo"><?= e(fecha($desde, 'larga')) ?> — <?= e(fecha(end($fechas), 'larga')) ?></div></div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <div class="leyenda me-2 d-none d-xl-flex"><span><i style="background:#2F5F8F"></i>Confirmada</span><span><i style="background:#B7791F"></i>Pendiente</span><span><i style="background:#2F7D5B"></i>En curso</span><span><i style="background:#6B7280"></i>Finalizada</span></div>
            <a class="btn btn-claro btn-accion" href="<?= $navegar(-$dias) ?>" aria-label="Anterior"><i class="bi bi-chevron-left"></i></a>
            <a class="btn btn-claro btn-sm" href="<?= url('/panel/disponibilidad', array_filter(['dias' => $dias, 'categoria' => $categoria, 'piso' => $piso])) ?>">Hoy</a>
            <a class="btn btn-claro btn-accion" href="<?= $navegar($dias) ?>" aria-label="Siguiente"><i class="bi bi-chevron-right"></i></a>
            <form method="get" class="d-flex gap-2">
                <input type="hidden" name="desde" value="<?= e($desde) ?>">
                <select class="form-select form-select-sm w-auto" name="dias" data-autoenviar><?php foreach ([7, 14, 21, 31] as $d): ?><option value="<?= $d ?>"<?= seleccionado($dias, $d) ?>><?= $d ?> días</option><?php endforeach; ?></select>
                <select class="form-select form-select-sm w-auto" name="categoria" data-autoenviar><option value="">Categoría</option><?php foreach ($categorias as $c): ?><option value="<?= $c['id_categoria'] ?>"<?= seleccionado($categoria, $c['id_categoria']) ?>><?= e($c['nombre']) ?></option><?php endforeach; ?></select>
                <select class="form-select form-select-sm w-auto" name="piso" data-autoenviar><option value="">Piso</option><?php foreach ($pisos as $p): ?><option value="<?= $p ?>"<?= seleccionado($piso, $p) ?>>Piso <?= $p ?></option><?php endforeach; ?></select>
            </form>
        </div>
    </div>
    <div class="rack" style="--dias: <?= count($fechas) ?>">
        <div class="rack-tabla">
            <div class="rack-esquina">Habitación</div>
            <?php foreach ($fechas as $f): $n = (int) date('N', strtotime($f)); $o = $rack['ocupacion'][$f] ?? ['porcentaje' => 0]; ?>
                <div class="rack-cab<?= $n >= 6 ? ' finde' : '' ?><?= $f === $hoy ? ' hoy' : '' ?>">
                    <?= e(DIAS_CORTOS[$n]) ?><strong><?= (int) date('j', strtotime($f)) ?></strong><?= e(MESES_CORTOS[(int) date('n', strtotime($f))]) ?>
                </div>
            <?php endforeach; ?>

            <?php foreach ($rack['habitaciones'] as $h): $reservas = $rack['porHabitacion'][(int) $h['id_habitacion']] ?? []; ?>
                <div class="rack-hab">
                    <span class="punto-estado" style="background: <?= $colorPunto[$h['estado']] ?>" title="<?= e(estado_info($h['estado'])[0]) ?>"></span>
                    <div><strong><?= e($h['numero']) ?></strong><small><?= e($h['categoria']) ?></small></div>
                </div>
                <?php foreach ($fechas as $i => $f):
                    $n = (int) date('N', strtotime($f));
                    $ocupada = false;
                    $barra = null;
                    foreach ($reservas as $r) {
                        if ($r['fecha_ingreso'] <= $f && $r['fecha_salida'] > $f) {
                            $ocupada = true;
                            if ($r['fecha_ingreso'] === $f || $i === 0) {
                                $fin = min($r['fecha_salida'], $rack['hasta']);
                                $barra = $r + ['span' => noches($f, $fin), 'izq' => $r['fecha_ingreso'] < $f, 'der' => $r['fecha_salida'] > $rack['hasta']];
                            }
                        }
                    }
                    $libre = !$ocupada && $h['estado'] !== 'MANTENIMIENTO' && $f >= $hoy;
                ?>
                    <div class="rack-celda<?= $n >= 6 ? ' finde' : '' ?><?= $f === $hoy ? ' hoy' : '' ?><?= $libre && $puedeReservar ? ' libre' : '' ?>"<?= $libre && $puedeReservar ? ' data-reservar="' . e(url('/panel/reservas/nueva', ['ingreso' => $f, 'salida' => date('Y-m-d', strtotime("$f +1 day")), 'habitacion' => $h['id_habitacion']])) . '" title="Reservar la ' . e($h['numero']) . ' desde el ' . e(fecha($f)) . '"' : '' ?>>
                        <?php if ($h['estado'] === 'MANTENIMIENTO' && $f >= $hoy && !$ocupada): ?><span class="rack-mantenimiento" title="En mantenimiento"></span><?php endif; ?>
                        <?php if ($barra): ?>
                            <a class="rack-barra <?= $barra['estado'] ?><?= $barra['izq'] ? ' continua-izq' : '' ?><?= $barra['der'] ? ' continua-der' : '' ?>" style="width: calc(<?= $barra['span'] ?> * 100% - 6px)" href="<?= url('/panel/reservas/' . $barra['id_reserva']) ?>" data-bs-toggle="tooltip" title="<?= e($barra['codigo'] . ' · ' . $barra['huesped'] . ' · ' . fecha($barra['fecha_ingreso']) . ' → ' . fecha($barra['fecha_salida'])) ?>">
                                <?= $barra['estado'] === 'EN_CURSO' ? '<i class="bi bi-person-fill"></i>' : '' ?><?= e($barra['huesped']) ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>

            <div class="rack-pie">Ocupación</div>
            <?php foreach ($fechas as $f): $p = $rack['ocupacion'][$f]['porcentaje'] ?? 0; ?>
                <div class="rack-ocupacion <?= $p >= $umbral ? 'ocup-alta' : ($p >= 70 ? 'ocup-media' : 'ocup-baja') ?>"><?= numero($p, 0) ?> %</div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php if ($rack['sinAsignar']): ?>
        <div class="tarjeta-pie">
            <div class="fw-semibold fs-sm mb-2"><i class="bi bi-shield-exclamation texto-oro"></i> Reservas en sobreventa pendientes de habitación</div>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($rack['sinAsignar'] as $r): ?>
                    <a class="insignia insignia-violeta" href="<?= url('/panel/reservas/' . $r['id_reserva']) ?>"><?= e($r['codigo']) ?> · <?= e($r['huesped']) ?> · <?= e(fecha($r['fecha_ingreso'], 'dia')) ?> → <?= e(fecha($r['fecha_salida'], 'dia')) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php $this->seccion('scripts') ?>
<script>
document.querySelectorAll('[data-reservar]').forEach(function (c) {
    c.addEventListener('click', function (e) { if (!e.target.closest('a')) window.location.href = c.dataset.reservar; });
});
</script>
<?php $this->fin() ?>
