<?php
/** @var \App\Core\Vista $this */
echo $this->parcial('parciales/encabezado', [
    'titulo' => 'Solicitudes del sitio web',
    'cu' => 'CU-11',
    'descripcion' => 'Pedidos de reserva y mensajes enviados desde el sitio público del hotel. Al atender una solicitud se abre el registro de reserva con los datos precargados.',
    'migas' => [['Reservas', null], ['Solicitudes web', null]],
    'acciones' => '<a class="btn btn-claro" href="' . url('/') . '" target="_blank" rel="noopener"><i class="bi bi-globe2"></i> Ver sitio público</a>',
]);
?>
<div class="segmentos mb-3">
    <a class="<?= $pestana === 'solicitudes' ? 'active' : '' ?>" href="<?= url('/panel/solicitudes') ?>"><i class="bi bi-envelope-paper"></i> Solicitudes de reserva <span class="cuenta"><?= (int) ($conteo['NUEVA'] ?? 0) ?></span></a>
    <a class="<?= $pestana === 'mensajes' ? 'active' : '' ?>" href="<?= url('/panel/solicitudes', ['pestana' => 'mensajes']) ?>"><i class="bi bi-chat-left-text"></i> Mensajes de contacto <span class="cuenta"><?= $noLeidos ?></span></a>
</div>

<?php if ($pestana === 'solicitudes'): ?>
<div class="tarjeta">
    <div class="filtros">
        <div class="segmentos">
            <?php foreach (['NUEVA' => 'Nuevas', 'ATENDIDA' => 'Atendidas', 'DESCARTADA' => 'Descartadas', '' => 'Todas'] as $v => $t): ?>
                <a class="<?= $estado === $v ? 'active' : '' ?>" href="<?= url('/panel/solicitudes', ['estado' => $v]) ?>"><?= $t ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php if ($solicitudes): ?>
        <?php foreach ($solicitudes as $s): ?>
            <div class="item-lista align-items-start flex-wrap">
                <span class="avatar avatar-noche"><?= e(iniciales($s['nombres'] . ' ' . $s['apellidos'])) ?></span>
                <div class="flex-grow-1 min-w-0" style="flex-basis: 300px">
                    <div class="d-flex flex-wrap gap-2 align-items-center"><strong><?= e($s['nombres'] . ' ' . $s['apellidos']) ?></strong><?= insignia($s['estado']) ?><?php if ($s['codigo']): ?><a class="etiqueta-codigo" href="<?= url('/panel/reservas/' . $s['id_reserva']) ?>"><?= e($s['codigo']) ?></a><?php endif; ?></div>
                    <div class="fs-sm mt-1"><i class="bi bi-calendar-range texto-oro"></i> <?= e(fecha($s['fecha_ingreso'], 'dia')) ?> → <?= e(fecha($s['fecha_salida'], 'dia')) ?> · <?= noches($s['fecha_ingreso'], $s['fecha_salida']) ?> noche(s) · <strong><?= e($s['categoria']) ?></strong> · <?= (int) $s['cantidad_personas'] ?> pers. · estimado <?= e(bs($s['monto_estimado'], 0)) ?></div>
                    <div class="fs-xs texto-tenue mt-1"><?= e($s['sigla'] . ' ' . $s['nro_documento']) ?> · <?= e($s['ciudad_procedencia']) ?> · <?= e($s['correo']) ?> · <?= e($s['telefono']) ?> · recibida <?= e(fecha($s['fecha'], 'hora')) ?></div>
                    <?php if ($s['mensaje']): ?><div class="fs-sm mt-2 p-2 rounded-3" style="background: var(--he-superficie-2)">«<?= e($s['mensaje']) ?>»</div><?php endif; ?>
                </div>
                <?php if ($s['estado'] === 'NUEVA'): ?>
                    <div class="d-flex gap-2">
                        <a class="btn btn-sm btn-primary" href="<?= url('/panel/reservas/nueva', ['solicitud' => $s['id_solicitud']]) ?>"><i class="bi bi-calendar-plus"></i> Atender</a>
                        <form method="post" action="<?= url('/panel/solicitudes/' . $s['id_solicitud'] . '/descartar') ?>" data-confirmar="¿Descartar esta solicitud?" data-confirmar-texto="No se registrará ninguna reserva." data-confirmar-boton="Descartar"><?= csrf_campo() ?><button class="btn btn-sm btn-claro" type="submit">Descartar</button></form>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <?= $this->parcial('parciales/vacio', ['icono' => 'bi-envelope-open', 'titulo' => 'Sin solicitudes', 'texto' => 'No hay solicitudes en este estado.']) ?>
    <?php endif; ?>
</div>
<?php else: ?>
<div class="tarjeta">
    <?php if ($mensajes): foreach ($mensajes as $m): ?>
        <div class="item-lista align-items-start<?= $m['leido'] ? '' : '" style="background: var(--he-oro-suave)' ?>">
            <span class="icono-chip <?= $m['leido'] ? 'chip-gris' : 'chip-oro' ?>"><i class="bi <?= $m['leido'] ? 'bi-envelope-open' : 'bi-envelope-fill' ?>"></i></span>
            <div class="flex-grow-1 min-w-0">
                <div class="d-flex flex-wrap gap-2"><strong><?= e($m['asunto']) ?></strong><span class="texto-tenue fs-sm">· <?= e($m['nombre']) ?> · <?= e($m['correo']) ?><?= $m['telefono'] ? ' · ' . e($m['telefono']) : '' ?></span></div>
                <div class="fs-sm mt-1"><?= nl2br(e($m['mensaje'])) ?></div>
                <div class="fs-xs texto-tenue mt-1"><?= e(fecha($m['fecha'], 'hora')) ?></div>
            </div>
            <a class="btn btn-sm btn-claro" href="mailto:<?= e($m['correo']) ?>?subject=<?= rawurlencode('Re: ' . $m['asunto']) ?>"><i class="bi bi-reply"></i></a>
            <?php if (!$m['leido']): ?><form method="post" action="<?= url('/panel/mensajes/' . $m['id_mensaje'] . '/leido') ?>"><?= csrf_campo() ?><button class="btn btn-sm btn-claro" type="submit" title="Marcar como leído"><i class="bi bi-check2"></i></button></form><?php endif; ?>
        </div>
    <?php endforeach; else: ?>
        <?= $this->parcial('parciales/vacio', ['icono' => 'bi-chat-left', 'titulo' => 'Sin mensajes', 'texto' => 'Todavía no llegaron mensajes desde el formulario de contacto.']) ?>
    <?php endif; ?>
</div>
<?php endif; ?>
