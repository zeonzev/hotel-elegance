<?php
/** @var \App\Core\Vista $this */
$id = (int) $r['id_reserva'];
$saldoHospedaje = max(0, (float) $r['monto_total'] + (float) $r['cargos_extra'] - $pagado);
$anticipada = hoy() < $r['fecha_salida'];
echo $this->parcial('parciales/encabezado', [
    'titulo' => 'Check-out · ' . $r['codigo'],
    'cu' => 'CU-16 «include» CU-10',
    'descripcion' => 'Registre consumos adicionales, cobre el saldo y emita el recibo. La habitación se libera automáticamente.',
    'migas' => [['Recepción', '/panel/recepcion'], [$r['codigo'], '/panel/reservas/' . $id], ['Check-out', null]],
]);
?>
<form method="post" action="<?= url("/panel/reservas/$id/checkout") ?>" id="form-checkout">
    <?= csrf_campo() ?>
    <div class="row g-4-5">
        <div class="col-xl-7">
            <?php if ($anticipada): ?><div class="aviso aviso-oro mb-4"><i class="bi bi-alarm"></i><div><strong>Salida anticipada</strong><span class="fs-sm">La salida prevista es el <?= e(fecha($r['fecha_salida'], 'larga')) ?>. Se registrará la fecha real y quedará anotado en observaciones (CU-16, flujo 2a).</span></div></div><?php endif; ?>
            <div class="tarjeta mb-4">
                <div class="tarjeta-encabezado"><div><h2>Estadía</h2><div class="subtitulo">Check-in el <?= e(fecha($estadia['fecha_checkin'] ?? '', 'hora')) ?></div></div></div>
                <div class="tarjeta-cuerpo">
                    <div class="persona mb-3"><span class="avatar"><?= e(iniciales($r['huesped'])) ?></span><div><div class="fw-semibold"><?= e($r['huesped']) ?></div><div class="fs-xs texto-tenue"><?= e($r['tipo_documento'] . ' ' . $r['nro_documento']) ?></div></div></div>
                    <?php foreach ($detalle as $d): ?><div class="resumen-linea"><span>Habitación <strong><?= e($d['numero'] ?? '—') ?></strong> · <?= e($d['categoria']) ?> · <?= (int) $d['noches'] ?> noche(s)</span><span class="tabular"><?= e(bs($d['subtotal'])) ?></span></div><?php endforeach; ?>
                    <?php foreach ($pagos as $p): ?><div class="resumen-linea texto-suave fs-sm"><span><?= e(ucfirst(strtolower($p['concepto']))) ?> · <?= e($p['metodo']) ?> · <?= e(fecha($p['fecha'])) ?></span><span class="tabular text-success">− <?= e(bs($p['monto'])) ?></span></div><?php endforeach; ?>
                </div>
            </div>
            <div class="tarjeta mb-4">
                <div class="tarjeta-encabezado"><div><h2>Cargos adicionales</h2><div class="subtitulo">Minibar, lavandería, restaurante…</div></div></div>
                <div class="tarjeta-cuerpo row g-3">
                    <div class="col-md-4"><label class="form-label" for="cargos_extra">Monto (Bs)</label><input type="number" step="0.01" min="0" class="form-control<?= clase_error('cargos_extra') ?>" id="cargos_extra" name="cargos_extra" value="<?= e(anterior('cargos_extra', '0')) ?>"></div>
                    <div class="col-md-8"><label class="form-label" for="concepto_extra">Concepto</label><input class="form-control" id="concepto_extra" name="concepto_extra" maxlength="120" value="<?= e(anterior('concepto_extra')) ?>" placeholder="Ej.: minibar y lavandería"></div>
                </div>
            </div>
            <div class="tarjeta">
                <div class="tarjeta-encabezado"><h2>Cobro y liberación</h2></div>
                <div class="tarjeta-cuerpo row g-3">
                    <div class="col-md-6"><label class="form-label" for="id_metodo_pago">Método de pago del saldo</label><select class="form-select<?= clase_error('id_metodo_pago') ?>" id="id_metodo_pago" name="id_metodo_pago"><?php foreach ($metodos as $m): ?><option value="<?= $m['id_metodo_pago'] ?>"<?= seleccionado(anterior('id_metodo_pago', 1), $m['id_metodo_pago']) ?>><?= e($m['nombre']) ?></option><?php endforeach; ?></select><?= mensaje_error('id_metodo_pago') ?></div>
                    <div class="col-md-6"><label class="form-label" for="referencia">Referencia (QR, POS, transferencia)</label><input class="form-control<?= clase_error('referencia') ?>" id="referencia" name="referencia" maxlength="80" value="<?= e(anterior('referencia')) ?>"><?= mensaje_error('referencia') ?></div>
                    <div class="col-12"><label class="form-label" for="observaciones">Observaciones de salida</label><input class="form-control" id="observaciones" name="observaciones" maxlength="255" value="<?= e(anterior('observaciones')) ?>"></div>
                    <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="enviar_limpieza" name="enviar_limpieza" value="1"><label class="form-check-label" for="enviar_limpieza">Enviar la habitación a <strong>limpieza</strong> en lugar de dejarla disponible</label></div></div>
                </div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="tarjeta tarjeta-noche resumen-reserva">
                <div class="tarjeta-cuerpo">
                    <div class="sobretitulo" style="color:var(--he-oro-300)">Total a cobrar</div>
                    <div class="d-flex justify-content-between mt-3"><span class="texto-suave">Saldo del hospedaje</span><span class="text-white tabular"><?= e(bs($saldoHospedaje)) ?></span></div>
                    <div class="d-flex justify-content-between"><span class="texto-suave">Cargos adicionales</span><span class="text-white tabular" id="v-cargos">Bs 0,00</span></div>
                    <hr style="border-color: rgba(255,255,255,.12)">
                    <div class="d-flex justify-content-between align-items-baseline"><span class="texto-suave">A cobrar ahora</span><span class="serif fw-semibold" style="font-size:2.4rem;color:var(--he-oro-300)" id="v-total"><?= e(bs($saldoHospedaje)) ?></span></div>
                    <button class="btn btn-oro btn-lg w-100 mt-4" type="submit"><i class="bi bi-receipt"></i> Cobrar, emitir recibo y liberar</button>
                    <a class="btn btn-fantasma w-100 mt-2 text-white" href="<?= url('/panel/reservas/' . $id) ?>">Volver</a>
                </div>
            </div>
        </div>
    </div>
</form>
<?php $this->seccion('scripts') ?>
<script>
(function () {
    var saldo = <?= json_encode(round($saldoHospedaje, 2)) ?>;
    var campo = document.getElementById('cargos_extra');
    function actualizar() {
        var c = Math.max(0, parseFloat(campo.value) || 0);
        document.getElementById('v-cargos').textContent = HE.bs(c);
        document.getElementById('v-total').textContent = HE.bs(saldo + c);
    }
    campo.addEventListener('input', actualizar);
    actualizar();
})();
</script>
<?php $this->fin() ?>
