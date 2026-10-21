<?php
/** @var \App\Core\Vista $this */
$h = $huesped;
$acciones = '';
if (puede('huespedes.gestionar')) {
    $acciones .= '<a class="btn btn-claro" href="' . url('/panel/huespedes/' . $h['id_huesped'] . '/editar') . '"><i class="bi bi-pencil"></i> Editar datos</a>';
}
if (puede('reservas.gestionar')) {
    $acciones .= '<a class="btn btn-primary" href="' . url('/panel/reservas/nueva', ['huesped' => $h['id_huesped']]) . '"><i class="bi bi-calendar-plus"></i> Nueva reserva</a>';
}
echo $this->parcial('parciales/encabezado', [
    'titulo' => $h['nombres'] . ' ' . $h['apellidos'],
    'cu' => 'CU-07',
    'descripcion' => 'Ficha del huésped e historial completo de reservas y estadías.',
    'migas' => [['Huéspedes', '/panel/huespedes'], [$h['nombres'] . ' ' . $h['apellidos'], null]],
    'acciones' => $acciones,
]);
?>
<div class="row g-4-5">
    <div class="col-xl-4">
        <div class="tarjeta mb-4">
            <div class="tarjeta-cuerpo">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <span class="avatar avatar-lg"><?= e(iniciales($h['nombres'] . ' ' . $h['apellidos'])) ?></span>
                    <div>
                        <div class="fw-semibold fs-5"><?= e($h['nombres'] . ' ' . $h['apellidos']) ?></div>
                        <div class="texto-tenue fs-sm"><?= e($h['nacionalidad']) ?> · <?= e($h['ciudad_procedencia']) ?></div>
                        <?php if ($resumen['estadias'] >= 5): ?><span class="insignia insignia-oro mt-1"><i class="bi bi-star-fill"></i>Huésped frecuente</span><?php endif; ?>
                    </div>
                </div>
                <dl class="datos">
                    <dt>Documento</dt><dd><?= e($h['tipo_documento']) ?> · <span class="font-monospace"><?= e($h['nro_documento']) ?></span></dd>
                    <dt>Teléfono</dt><dd><?= e($h['telefono'] ?: '—') ?></dd>
                    <dt>Correo</dt><dd><?= e($h['correo'] ?: '—') ?></dd>
                    <dt>Nacimiento</dt><dd><?= $h['fecha_nacimiento'] ? e(fecha($h['fecha_nacimiento'], 'larga')) . ' (' . (int) (new DateTime($h['fecha_nacimiento']))->diff(new DateTime())->y . ' años)' : '—' ?></dd>
                    <dt>Registrado</dt><dd><?= e(fecha($h['creado_en'])) ?></dd>
                    <?php if ($h['observaciones']): ?><dt>Observaciones</dt><dd><?= nl2br(e($h['observaciones'])) ?></dd><?php endif; ?>
                </dl>
            </div>
        </div>
        <div class="row g-3">
            <?php foreach ([['Estadías', numero($resumen['estadias']), 'bi-luggage', 'chip-oro'], ['Noches', numero($resumen['noches']), 'bi-moon-stars', 'chip-noche'], ['Consumo', bs($resumen['gasto'], 0), 'bi-cash-coin', 'chip-verde'], ['Cancel. / no-show', numero($resumen['cancelaciones']), 'bi-x-circle', 'chip-rojo']] as [$t, $v, $i, $c]): ?>
                <div class="col-6"><div class="tarjeta kpi"><span class="icono-chip <?= $c ?> mb-2"><i class="bi <?= $i ?>"></i></span><div class="kpi-titulo"><?= $t ?></div><div class="fs-5 fw-semibold tabular"><?= e($v) ?></div></div></div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="col-xl-8">
        <?php if ($resumen['proxima']): $p = $resumen['proxima']; ?>
            <div class="aviso aviso-oro mb-4">
                <i class="bi bi-calendar-check"></i>
                <div class="flex-grow-1"><strong><?= $p['estado'] === 'EN_CURSO' ? 'Actualmente alojado' : 'Próxima reserva' ?>: <?= e($p['codigo']) ?></strong><span class="fs-sm"><?= e(fecha($p['fecha_ingreso'], 'larga')) ?> al <?= e(fecha($p['fecha_salida'], 'larga')) ?> · Habitación <?= e($p['habitaciones']) ?></span></div>
                <a class="btn btn-sm btn-claro" href="<?= url('/panel/reservas/' . $p['id_reserva']) ?>">Ver reserva</a>
            </div>
        <?php endif; ?>
        <div class="tarjeta">
            <div class="tarjeta-encabezado"><div><h2>Historial de estadías</h2><div class="subtitulo">Fuente: vista <code>vw_historial_huesped</code></div></div><span class="insignia insignia-gris"><?= count($historial) ?> reservas</span></div>
            <?php if ($historial): ?>
                <div class="tabla-contenedor">
                    <table class="tabla">
                        <thead><tr><th>Reserva</th><th>Fechas</th><th>Habitación</th><th>Check-in / out</th><th class="text-end">Monto</th><th>Estado</th></tr></thead>
                        <tbody>
                        <?php foreach ($historial as $r): ?>
                            <tr class="fila-enlace" data-href="<?= url('/panel/reservas/' . $r['id_reserva']) ?>">
                                <td><span class="etiqueta-codigo"><?= e($r['codigo']) ?></span><div class="celda-secundaria mt-1"><?= e($r['canal']) ?></div></td>
                                <td><div class="fw-medio"><?= e(fecha($r['fecha_ingreso'])) ?> → <?= e(fecha($r['fecha_salida'])) ?></div><div class="celda-secundaria"><?= (int) $r['noches'] ?> noche(s)</div></td>
                                <td><div><?= e($r['habitaciones']) ?></div><div class="celda-secundaria"><?= e($r['categorias']) ?></div></td>
                                <td class="fs-sm"><?php if ($r['fecha_checkin']): ?><div><i class="bi bi-box-arrow-in-right texto-tenue"></i> <?= e(fecha($r['fecha_checkin'], 'hora')) ?></div><div><i class="bi bi-box-arrow-right texto-tenue"></i> <?= $r['fecha_checkout'] ? e(fecha($r['fecha_checkout'], 'hora')) : '<span class="texto-oro">Alojado</span>' ?></div><?php else: ?><span class="texto-tenue">—</span><?php endif; ?></td>
                                <td class="text-end tabular"><?= e(bs((float) $r['monto_total'] + (float) $r['cargos_extra'])) ?></td>
                                <td><?= insignia($r['estado']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <?= $this->parcial('parciales/vacio', ['icono' => 'bi-journal-x', 'titulo' => 'Sin historial', 'texto' => 'Este huésped todavía no tiene estadías registradas (CU-07, flujo 2a).']) ?>
            <?php endif; ?>
        </div>
    </div>
</div>
