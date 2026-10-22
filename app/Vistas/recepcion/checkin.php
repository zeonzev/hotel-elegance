<?php
/** @var \App\Core\Vista $this */
$id = (int) $r['id_reserva'];
$saldo = (float) $r['monto_total'] - $pagado;
$anticipada = $r['fecha_ingreso'] > hoy();
$ocupada = (bool) array_filter($detalle, fn ($d) => $d['estado_habitacion'] === 'OCUPADA');
echo $this->parcial('parciales/encabezado', [
    'titulo' => 'Check-in · ' . $r['codigo'],
    'cu' => 'CU-15 «include» CU-10',
    'descripcion' => 'Verifique la identidad del huésped y confirme su ingreso. La reserva pasará a EN CURSO y la habitación a OCUPADA.',
    'migas' => [['Recepción', '/panel/recepcion'], [$r['codigo'], '/panel/reservas/' . $id], ['Check-in', null]],
]);
?>
<form method="post" action="<?= url("/panel/reservas/$id/checkin") ?>">
    <?= csrf_campo() ?>
    <div class="row g-4-5">
        <div class="col-xl-7">
            <?php if ($anticipada): ?><div class="aviso aviso-oro mb-4"><i class="bi bi-calendar-x"></i><div><strong>La fecha de ingreso es el <?= e(fecha($r['fecha_ingreso'], 'larga')) ?>.</strong><span class="fs-sm">El check-in sólo puede registrarse a partir de ese día (precondición de CU-15).</span></div></div><?php endif; ?>
            <div class="tarjeta mb-4">
                <div class="tarjeta-encabezado"><h2>1 · Verificar identidad</h2></div>
                <div class="tarjeta-cuerpo">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="avatar avatar-lg"><?= e(iniciales($r['huesped'])) ?></span>
                        <div><div class="fw-semibold fs-5"><?= e($r['huesped']) ?></div><div class="texto-suave">Documento: <span class="etiqueta-codigo"><?= e($r['tipo_documento'] . ' ' . $r['nro_documento']) ?></span></div><div class="fs-xs texto-tenue"><?= e($r['nacionalidad']) ?> · <?= e($r['ciudad_procedencia']) ?> · <?= e($r['telefono'] ?? '') ?></div></div>
                    </div>
                    <label class="opcion-tarjeta"><input type="checkbox" name="documento_verificado" value="1" required>
                        <span class="contenido d-flex align-items-center gap-2"><i class="bi bi-person-vcard fs-4 texto-oro"></i><span><strong class="d-block">Verifiqué el documento físico del huésped</strong><span class="fs-xs texto-tenue">El número coincide con el registrado en la reserva.</span></span></span></label>
                    <?= mensaje_error('documento_verificado') ?>
                </div>
            </div>
            <div class="tarjeta mb-4">
                <div class="tarjeta-encabezado"><h2>2 · Habitación</h2></div>
                <div class="tarjeta-cuerpo">
                    <?php foreach ($detalle as $d): ?>
                        <?php if ($d['id_habitacion']): ?>
                            <div class="d-flex align-items-center gap-3 mb-2"><span class="serif fs-2 fw-semibold"><?= e($d['numero']) ?></span><div><div class="fw-medio"><?= e($d['categoria']) ?> · piso <?= (int) $d['piso'] ?></div><div><?= insignia($d['estado_habitacion']) ?></div></div></div>
                            <?php if ($d['estado_habitacion'] === 'OCUPADA' && ($x = \App\Servicios\HabitacionServicio::estadiaAbierta((int) $d['id_habitacion']))): ?><div class="aviso aviso-oro mb-2"><i class="bi bi-hourglass-split"></i><div class="flex-grow-1"><strong>La habitación todavía está ocupada por <?= e($x['huesped']) ?></strong><span class="fs-sm">Registre primero su check-out (salida <?= e(fecha($x['fecha_salida'], 'dia')) ?>).</span></div><a class="btn btn-sm btn-oro" href="<?= url('/panel/reservas/' . $x['id_reserva'] . '/checkout') ?>">Ir al check-out</a></div><?php endif; ?>
                            <?php if ($d['estado_habitacion'] === 'LIMPIEZA'): ?><div class="fs-xs texto-oro mb-2"><i class="bi bi-stars"></i> La habitación figura en limpieza; confirme con housekeeping que esté lista.</div><?php endif; ?>
                        <?php else: ?>
                            <div class="aviso aviso-oro mb-2"><i class="bi bi-shield-exclamation"></i><div class="flex-grow-1"><strong>Reserva en sobreventa: asigne una habitación libre (<?= e($d['categoria']) ?>)</strong>
                                <?php if ($libres): ?>
                                    <select class="form-select mt-2<?= clase_error('asignacion') ?>" name="asignacion[<?= (int) $d['id_detalle'] ?>]" required><?php foreach ($libres as $l): ?><option value="<?= $l['id_habitacion'] ?>"<?= $l['id_categoria'] == $d['id_categoria'] ? ' selected' : '' ?>><?= e($l['numero'] . ' · ' . $l['categoria'] . ' · ' . estado_info($l['estado'])[0]) ?></option><?php endforeach; ?></select>
                                <?php else: ?>
                                    <span class="fs-sm text-danger d-block mt-1">No hay habitaciones libres: corresponde reubicar al huésped en otro hotel (costo previsto por el modelo).</span>
                                <?php endif; ?>
                            </div></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <?= mensaje_error('asignacion') ?><?= mensaje_error('estado') ?>
                </div>
            </div>
            <div class="tarjeta">
                <div class="tarjeta-encabezado"><h2>3 · Observaciones de ingreso</h2></div>
                <div class="tarjeta-cuerpo"><textarea class="form-control" name="observaciones" rows="2" maxlength="500" placeholder="Hora de llegada, pedidos especiales, vehículo…"></textarea></div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="tarjeta tarjeta-noche resumen-reserva">
                <div class="tarjeta-cuerpo">
                    <div class="sobretitulo" style="color:var(--he-oro-300)">Resumen de la estadía</div>
                    <h3 class="h2 mt-2 mb-1"><?= e(fecha($r['fecha_ingreso'], 'dia')) ?> → <?= e(fecha($r['fecha_salida'], 'dia')) ?></h3>
                    <div class="texto-suave mb-3"><?= noches($r['fecha_ingreso'], $r['fecha_salida']) ?> noche(s) · <?= (int) $r['cantidad_personas'] ?> persona(s) · <?= e($r['canal']) ?></div>
                    <div class="d-flex justify-content-between"><span class="texto-suave">Total</span><span class="text-white tabular"><?= e(bs($r['monto_total'])) ?></span></div>
                    <div class="d-flex justify-content-between"><span class="texto-suave">Anticipo pagado</span><span class="tabular" style="color:#5CC293"><?= e(bs($pagado)) ?></span></div>
                    <div class="d-flex justify-content-between mt-2"><span class="texto-suave">Saldo (se cobra al check-out)</span><strong class="tabular" style="color:var(--he-oro-300)"><?= e(bs(max(0, $saldo))) ?></strong></div>
                    <button class="btn btn-oro btn-lg w-100 mt-4" type="submit"<?= $anticipada || $ocupada ? ' disabled' : '' ?>><i class="bi bi-box-arrow-in-right"></i> Confirmar ingreso</button>
                    <a class="btn btn-fantasma w-100 mt-2 text-white" href="<?= url('/panel/reservas/' . $id) ?>">Volver a la reserva</a>
                </div>
            </div>
        </div>
    </div>
</form>
