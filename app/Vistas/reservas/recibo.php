<?php /** @var \App\Core\Vista $this */ ?>
<div class="documento">
    <?= $this->parcial('parciales/membrete') ?>
    <div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
        <div>
            <div class="sobretitulo"><?= $recibo ? 'Recibo de pago' : 'Comprobante de reserva' ?></div>
            <h1 class="serif mb-0" style="font-size:2.2rem"><?= e($recibo['numero'] ?? $r['codigo']) ?></h1>
            <div class="fs-sm" style="color:#5E6675">Reserva <?= e($r['codigo']) ?> · emitido el <?= e(fecha($recibo['fecha_emision'] ?? date('Y-m-d H:i:s'), 'hora')) ?></div>
        </div>
        <div class="text-end">
            <div class="fs-xs text-uppercase" style="letter-spacing:.12em;color:#8D93A0">Huésped</div>
            <div class="fw-semibold fs-5"><?= e($r['huesped']) ?></div>
            <div class="fs-sm" style="color:#5E6675"><?= e($r['tipo_documento'] . ' ' . $r['nro_documento']) ?> · <?= e($r['ciudad_procedencia']) ?></div>
        </div>
    </div>
    <div class="row g-3 mb-4">
        <?php foreach ([['Ingreso', fecha($r['fecha_ingreso'], 'larga')], ['Salida', fecha($r['fecha_salida'], 'larga')], ['Noches', noches($r['fecha_ingreso'], $r['fecha_salida'])], ['Personas', $r['cantidad_personas']]] as [$t, $v]): ?>
            <div class="col-6 col-md-3"><div class="rounded-3 p-3" style="background:#FAF8F4;border:1px solid #EFEAE0"><div class="fs-xs text-uppercase" style="letter-spacing:.1em;color:#8D93A0"><?= $t ?></div><div class="fw-semibold"><?= e((string) $v) ?></div></div></div>
        <?php endforeach; ?>
    </div>
    <table class="mb-4">
        <thead><tr><th>Descripción</th><th class="text-center">Noches</th><th class="text-end">Precio / noche</th><th class="text-end">Importe</th></tr></thead>
        <tbody>
            <?php foreach ($detalle as $d): ?>
                <tr><td>Habitación <?= e($d['numero'] ?? 'por asignar') ?> · <?= e($d['categoria']) ?></td><td class="text-center"><?= (int) $d['noches'] ?></td><td class="text-end"><?= e(bs($d['precio_noche'])) ?></td><td class="text-end"><?= e(bs($d['subtotal'])) ?></td></tr>
            <?php endforeach; ?>
            <?php if ((float) $r['cargos_extra'] > 0): ?><tr><td>Cargos adicionales (consumos)</td><td></td><td></td><td class="text-end"><?= e(bs($r['cargos_extra'])) ?></td></tr><?php endif; ?>
        </tbody>
    </table>
    <div class="row justify-content-end">
        <div class="col-md-6">
            <?php $total = (float) $r['monto_total'] + (float) $r['cargos_extra']; ?>
            <div class="d-flex justify-content-between py-1"><span style="color:#5E6675">Total</span><strong><?= e(bs($total)) ?></strong></div>
            <?php foreach ($pagos as $p): ?>
                <div class="d-flex justify-content-between py-1 fs-sm"><span style="color:#5E6675"><?= e(ucfirst(strtolower($p['concepto']))) ?> · <?= e($p['metodo']) ?> · <?= e(fecha($p['fecha'])) ?></span><span>− <?= e(bs($p['monto'])) ?></span></div>
            <?php endforeach; ?>
            <div class="d-flex justify-content-between align-items-baseline pt-2 mt-2" style="border-top:2px solid #1A2230"><span class="fw-semibold">Saldo</span><span class="serif fw-semibold" style="font-size:1.8rem"><?= e(bs(max(0, $total - $pagado))) ?></span></div>
        </div>
    </div>
    <div class="d-flex justify-content-between align-items-end mt-5 pt-4 fs-xs" style="color:#8D93A0;border-top:1px dashed #E7E2D8">
        <span>Check-in desde las <?= e(ajuste('hora_checkin', '14:00')) ?> · check-out hasta las <?= e(ajuste('hora_checkout', '12:00')) ?><br>Documento generado por el Sistema de Administración Web de Reservas — Hotel Elegance</span>
        <span class="text-center" style="min-width:180px"><span class="d-block" style="border-top:1px solid #8D93A0;padding-top:.3rem">Recepción<?= !empty($recibo['nombre_usuario']) ? ' · @' . e($recibo['nombre_usuario']) : '' ?></span></span>
    </div>
</div>
