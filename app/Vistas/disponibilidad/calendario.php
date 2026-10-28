<?php
/** @var \App\Core\Vista $this */
echo $this->parcial('parciales/encabezado', [
    'titulo' => 'Calendario',
    'descripcion' => 'Vista mensual de la ocupación del hotel con las llegadas y salidas de cada día. Seleccione un día para ver sus reservas.',
    'migas' => [['Operación', null], ['Calendario', null]],
    'acciones' => '<a class="btn btn-claro" href="' . url('/panel/disponibilidad') . '"><i class="bi bi-calendar3-week"></i> Ver por habitación</a>',
]);
$hoy = hoy();
[$anio, $numeroMes] = array_map('intval', explode('-', $mes));
$enlaceMes = fn (string $m) => url('/panel/calendario', ['mes' => $m]);
$vacios = (int) date('N', strtotime($inicio)) - 1;
$promedio = $ocupacion ? array_sum(array_column($ocupacion, 'porcentaje')) / count($ocupacion) : 0;
$pico = $ocupacion ? array_search(max(array_column($ocupacion, 'porcentaje')), array_combine(array_keys($ocupacion), array_column($ocupacion, 'porcentaje')), true) : null;
$grupos = ['Llegan' => [], 'Salen' => [], 'Alojados' => []];
foreach ($reservas as $r) {
    $grupos[$r['fecha_ingreso'] === $dia ? 'Llegan' : ($r['fecha_salida'] === $dia ? 'Salen' : 'Alojados')][] = $r;
}
?>
<div class="row g-4-5">
    <div class="col-xl-8">
        <div class="tarjeta">
            <div class="tarjeta-encabezado">
                <div><h2><?= e(ucfirst(MESES[$numeroMes]) . ' de ' . $anio) ?></h2><div class="subtitulo">Ocupación promedio <?= numero($promedio, 1) ?> % · <?= numero(array_sum($llegadas)) ?> llegadas · <?= numero(array_sum($salidas)) ?> salidas</div></div>
                <div class="d-flex gap-2">
                    <a class="btn btn-claro btn-accion" href="<?= $enlaceMes(date('Y-m', strtotime("$inicio -1 month"))) ?>" title="Mes anterior"><i class="bi bi-chevron-left"></i></a>
                    <a class="btn btn-claro btn-sm" href="<?= url('/panel/calendario') ?>">Hoy</a>
                    <a class="btn btn-claro btn-accion" href="<?= $enlaceMes(date('Y-m', strtotime("$inicio +1 month"))) ?>" title="Mes siguiente"><i class="bi bi-chevron-right"></i></a>
                </div>
            </div>
            <div class="tarjeta-cuerpo">
                <div class="calendario">
                    <?php foreach (DIAS_CORTOS as $d): ?><div class="calendario-cab"><?= e($d) ?></div><?php endforeach; ?>
                    <?php for ($i = 0; $i < $vacios; $i++): ?><div class="calendario-vacio"></div><?php endfor; ?>
                    <?php foreach ($ocupacion as $f => $o):
                        $clases = ['calendario-dia'];
                        if ($f === $hoy) { $clases[] = 'hoy'; }
                        if ($f === $dia) { $clases[] = 'seleccionado'; }
                        if ($o['porcentaje'] >= $umbral) { $clases[] = 'alta'; }
                        if ($f < $hoy) { $clases[] = 'pasado'; }
                    ?>
                        <a class="<?= implode(' ', $clases) ?>" style="--p: <?= (float) $o['porcentaje'] ?>" href="<?= url('/panel/calendario', ['mes' => $mes, 'dia' => $f]) ?>" title="<?= e(fecha($f, 'larga')) ?>: <?= $o['ocupadas'] ?> de <?= $o['capacidad'] ?> habitaciones">
                            <span class="calendario-numero"><?= (int) substr($f, 8) ?></span>
                            <span class="calendario-ocupacion"><?= numero($o['porcentaje'], 0) ?>%</span>
                            <span class="calendario-movimientos">
                                <?php if (!empty($llegadas[$f])): ?><span class="llega" title="Llegadas"><i class="bi bi-box-arrow-in-right"></i><?= (int) $llegadas[$f] ?></span><?php endif; ?>
                                <?php if (!empty($salidas[$f])): ?><span class="sale" title="Salidas"><i class="bi bi-box-arrow-right"></i><?= (int) $salidas[$f] ?></span><?php endif; ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="leyenda mt-3">
                    <span><i style="background: color-mix(in srgb, var(--he-oro-500) 6%, var(--he-superficie)); border: 1px solid var(--he-borde)"></i>Ocupación baja</span>
                    <span><i style="background: color-mix(in srgb, var(--he-oro-500) 40%, var(--he-superficie))"></i>Ocupación alta</span>
                    <span><i style="border: 2px solid var(--he-rojo)"></i>Sobre el umbral de sobreventa (<?= numero($umbral, 0) ?> %)</span>
                    <span style="color: var(--he-verde)"><i class="bi bi-box-arrow-in-right" style="width:auto;height:auto"></i>Llegadas</span>
                    <span style="color: var(--he-azul)"><i class="bi bi-box-arrow-right" style="width:auto;height:auto"></i>Salidas</span>
                    <?php if ($pico): ?><span class="ms-auto">Día más lleno: <strong class="texto-oro"><?= e(fecha($pico, 'dia')) ?> (<?= numero($ocupacion[$pico]['porcentaje'], 0) ?> %)</strong></span><?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="tarjeta">
            <div class="tarjeta-encabezado">
                <div><h2><?= e(ucfirst(DIAS[(int) date('N', strtotime($dia))]) . ' ' . fecha($dia, 'larga')) ?></h2><div class="subtitulo"><?= $ocupacion[$dia]['ocupadas'] ?> de <?= $ocupacion[$dia]['capacidad'] ?> habitaciones ocupadas (<?= numero($ocupacion[$dia]['porcentaje'], 0) ?> %)</div></div>
                <?php if (puede('reservas.gestionar') && $dia >= $hoy): ?>
                    <a class="btn btn-sm btn-oro" href="<?= url('/panel/reservas/nueva', ['ingreso' => $dia, 'salida' => date('Y-m-d', strtotime("$dia +1 day"))]) ?>"><i class="bi bi-plus-lg"></i> Reservar</a>
                <?php endif; ?>
            </div>
            <?php if ($reservas): ?>
                <div class="calendario-lista">
                <?php foreach ($grupos as $titulo => $lista): if (!$lista) { continue; } ?>
                    <div class="calendario-grupo"><?= e($titulo) ?> <span><?= count($lista) ?></span></div>
                    <ul class="lista-limpia">
                        <?php foreach ($lista as $r): ?>
                            <li class="calendario-reserva">
                                <div class="flex-grow-1 min-w-0">
                                    <div class="celda-principal text-truncate"><?= e($r['huesped']) ?></div>
                                    <div class="celda-secundaria">Hab. <?= e($r['habitaciones']) ?> · <?= e(fecha($r['fecha_ingreso'], 'dia')) ?> → <?= e(fecha($r['fecha_salida'], 'dia')) ?> · <?= (int) $r['cantidad_personas'] ?> pers.</div>
                                </div>
                                <div class="text-end">
                                    <a class="etiqueta-codigo" href="<?= url('/panel/reservas/' . $r['id_reserva']) ?>"><?= e($r['codigo']) ?></a>
                                    <div class="mt-1"><?= insignia($r['estado']) ?></div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endforeach; ?>
                </div>
            <?php else: ?>
                <?= $this->parcial('parciales/vacio', ['icono' => 'bi-calendar-check', 'titulo' => 'Día sin reservas', 'texto' => 'No hay huéspedes que lleguen, salgan o estén alojados este día.']) ?>
            <?php endif; ?>
        </div>
    </div>
</div>
