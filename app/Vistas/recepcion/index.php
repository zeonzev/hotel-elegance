<?php
/** @var \App\Core\Vista $this */
echo $this->parcial('parciales/encabezado', [
    'titulo' => 'Recepción del día',
    'cu' => 'CU-15 · CU-16',
    'descripcion' => 'Llegadas, salidas y huéspedes alojados hoy, ' . fecha(hoy(), 'larga') . '. Las reservas que no hicieron check-in en su día pasan automáticamente a no-show.',
    'migas' => [['Operación', null], ['Recepción', null]],
    'acciones' => '<a class="btn btn-claro" href="' . url('/panel/disponibilidad') . '"><i class="bi bi-calendar3-week"></i> Calendario</a><a class="btn btn-primary" href="' . url('/panel/reservas/nueva', ['ingreso' => hoy()]) . '"><i class="bi bi-person-walking"></i> Reserva de mostrador</a>',
]);
$pendLlegadas = count(array_filter($llegadas, fn ($r) => $r['estado'] !== 'EN_CURSO'));
$pendSalidas = count(array_filter($salidas, fn ($r) => $r['estado'] === 'EN_CURSO'));
$fila = function (array $r, string $tipo) {
    $saldo = (float) $r['monto_total'] + (float) $r['cargos_extra'] - (float) $r['pagado'];
    ob_start(); ?>
    <div class="item-lista">
        <span class="avatar avatar-noche"><?= e(iniciales($r['huesped'])) ?></span>
        <div class="flex-grow-1 min-w-0">
            <div class="d-flex flex-wrap gap-2 align-items-center"><a class="fw-semibold text-reset" href="<?= url('/panel/reservas/' . $r['id_reserva']) ?>"><?= e($r['huesped']) ?></a><span class="etiqueta-codigo"><?= e($r['codigo']) ?></span><?php if ((int) $r['es_sobreventa']): ?><span class="insignia insignia-violeta">Sobreventa</span><?php endif; ?></div>
            <div class="fs-xs texto-tenue"><?= e($r['sigla'] . ' ' . $r['nro_documento']) ?> · <?= e($r['ciudad_procedencia']) ?> · <?= (int) $r['cantidad_personas'] ?> pers. · Hab. <strong><?= e($r['habitaciones']) ?></strong><?= $tipo === 'alojado' ? ' · sale ' . e(fecha($r['fecha_salida'], 'dia')) : '' ?></div>
        </div>
        <div class="text-end d-none d-md-block me-2"><?php if ($saldo > 0.009): ?><div class="fs-xs texto-tenue">Saldo</div><div class="fw-semibold texto-oro tabular"><?= e(bs($saldo, 0)) ?></div><?php else: ?><span class="insignia insignia-verde">Pagado</span><?php endif; ?></div>
        <?php if ($tipo === 'llegada' && $r['estado'] !== 'EN_CURSO' && (int) $r['ocupadas'] > 0): ?>
            <span class="text-end"><a class="btn btn-sm btn-claro" href="<?= url('/panel/reservas/' . $r['id_reserva'] . '/checkin') ?>"><i class="bi bi-hourglass-split"></i> Check-in</a><span class="d-block fs-xs texto-oro mt-1">Hab. aún ocupada: falta el check-out</span></span>
        <?php elseif ($tipo === 'llegada' && $r['estado'] !== 'EN_CURSO'): ?>
            <a class="btn btn-sm btn-primary" href="<?= url('/panel/reservas/' . $r['id_reserva'] . '/checkin') ?>"><i class="bi bi-box-arrow-in-right"></i> Check-in</a>
        <?php elseif (($tipo === 'salida' || $tipo === 'atrasada') && $r['estado'] === 'EN_CURSO'): ?>
            <a class="btn btn-sm btn-oro" href="<?= url('/panel/reservas/' . $r['id_reserva'] . '/checkout') ?>"><i class="bi bi-box-arrow-right"></i> Check-out</a>
        <?php else: ?>
            <?= insignia($r['estado']) ?>
        <?php endif; ?>
    </div>
    <?php return ob_get_clean();
};
?>
<?php if ($alertas): ?>
    <div class="aviso aviso-rojo mb-4"><i class="bi bi-shield-exclamation"></i><div class="flex-grow-1"><strong>Alerta de sobreventa (CU-19): la ocupación supera el umbral en <?= count($alertas) ?> noche(s) de esta semana.</strong><span class="fs-sm">Antes de aceptar reservas para <?= e(implode(', ', array_map(fn ($f) => fecha($f, 'dia'), array_keys($alertas)))) ?>, el sistema le mostrará la recomendación del modelo.</span></div></div>
<?php endif; ?>
<?php if ($atrasadas): ?>
    <div class="tarjeta mb-4" style="border-color: var(--he-ambar)">
        <div class="tarjeta-encabezado"><div><h2 class="text-warning-emphasis"><i class="bi bi-clock-history"></i> Salidas vencidas sin check-out</h2><div class="subtitulo">Su fecha de salida ya pasó</div></div></div>
        <?php foreach ($atrasadas as $r) { echo $fila($r, 'atrasada'); } ?>
    </div>
<?php endif; ?>
<div class="row g-4-5 mb-4">
    <div class="col-xl-6">
        <div class="tarjeta h-100">
            <div class="tarjeta-encabezado"><div><h2><i class="bi bi-box-arrow-in-right texto-oro"></i> Llegadas</h2><div class="subtitulo"><?= $pendLlegadas ?> pendiente(s) de <?= count($llegadas) ?></div></div><span class="insignia insignia-azul"><?= count($llegadas) ?></span></div>
            <?php if ($llegadas): foreach ($llegadas as $r) { echo $fila($r, 'llegada'); } else: ?>
                <?= $this->parcial('parciales/vacio', ['icono' => 'bi-door-open', 'titulo' => 'Sin llegadas hoy', 'texto' => 'No hay reservas con ingreso para hoy.']) ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="tarjeta h-100">
            <div class="tarjeta-encabezado"><div><h2><i class="bi bi-box-arrow-right texto-oro"></i> Salidas</h2><div class="subtitulo"><?= $pendSalidas ?> pendiente(s) de <?= count($salidas) ?> · check-out hasta las <?= e(ajuste('hora_checkout', '12:00')) ?></div></div><span class="insignia insignia-ambar"><?= count($salidas) ?></span></div>
            <?php if ($salidas): foreach ($salidas as $r) { echo $fila($r, 'salida'); } else: ?>
                <?= $this->parcial('parciales/vacio', ['icono' => 'bi-luggage', 'titulo' => 'Sin salidas hoy', 'texto' => 'No hay check-outs programados para hoy.']) ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<div class="row g-4-5">
    <div class="col-xl-8">
        <div class="tarjeta">
            <div class="tarjeta-encabezado"><div><h2><i class="bi bi-house-heart texto-oro"></i> Huéspedes alojados</h2><div class="subtitulo">Estadías en curso que continúan después de hoy</div></div><span class="insignia insignia-verde"><?= count($alojados) ?></span></div>
            <?php if ($alojados): foreach ($alojados as $r) { echo $fila($r, 'alojado'); } else: ?>
                <?= $this->parcial('parciales/vacio', ['icono' => 'bi-moon-stars', 'titulo' => 'Sin huéspedes alojados', 'texto' => 'No hay estadías en curso.']) ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="tarjeta">
            <div class="tarjeta-encabezado"><div><h2><i class="bi bi-stars texto-oro"></i> Habitaciones en limpieza</h2><div class="subtitulo">Márquelas como disponibles al terminar</div></div></div>
            <div class="tarjeta-cuerpo d-flex flex-wrap gap-2">
                <?php foreach ($limpieza as $h): ?>
                    <form method="post" action="<?= url('/panel/habitaciones/' . $h['id_habitacion'] . '/estado') ?>">
                        <?= csrf_campo() ?><input type="hidden" name="estado" value="DISPONIBLE"><input type="hidden" name="motivo" value="Limpieza concluida">
                        <button class="btn btn-claro btn-sm" type="submit" data-bs-toggle="tooltip" title="Marcar como disponible"><span class="serif fs-5 fw-semibold"><?= e($h['numero']) ?></span> <i class="bi bi-check2 text-success"></i></button>
                    </form>
                <?php endforeach; ?>
                <?php if (!$limpieza): ?><span class="texto-suave fs-sm">Ninguna habitación está en limpieza.</span><?php endif; ?>
            </div>
        </div>
    </div>
</div>
