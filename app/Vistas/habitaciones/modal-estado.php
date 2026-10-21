<?php if (puede('habitaciones.estado')): ?>
<div class="modal fade" id="modal-estado" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" data-form-estado>
            <?= csrf_campo() ?>
            <div class="modal-header"><h5 class="modal-title">Cambiar estado · Habitación <span data-numero></span></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
            <div class="modal-body">
                <p class="fs-sm texto-suave">CU-10: el cambio se guarda con su usuario, la fecha y el motivo en el historial de la habitación.</p>
                <div class="row g-2 mb-3">
                    <?php foreach (['DISPONIBLE' => ['bi-check-circle', 'verde', 'Lista para vender'], 'OCUPADA' => ['bi-person-fill', 'azul', 'Huésped alojado'], 'LIMPIEZA' => ['bi-stars', 'ambar', 'En limpieza'], 'MANTENIMIENTO' => ['bi-tools', 'rojo', 'Fuera de venta']] as $estado => [$ico, $color, $ayuda]): ?>
                        <div class="col-6">
                            <label class="opcion-tarjeta"><input type="radio" name="estado" value="<?= $estado ?>" required>
                                <span class="contenido d-flex align-items-center gap-2"><span class="icono-chip chip-<?= $color ?>" style="width:34px;height:34px"><i class="bi <?= $ico ?>"></i></span><span><strong class="d-block fs-sm"><?= estado_info($estado)[0] ?></strong><span class="fs-xs texto-tenue"><?= $ayuda ?></span></span></span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <label class="form-label" for="motivo-estado">Motivo</label>
                <input class="form-control" id="motivo-estado" name="motivo" maxlength="255" placeholder="p. ej. Fuga en la grifería, limpieza profunda…">
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-claro" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> Guardar estado</button></div>
        </form>
    </div>
</div>
<?php $this->seccion('scripts') ?>
<script>
document.getElementById('modal-estado').addEventListener('show.bs.modal', function (e) {
    var b = e.relatedTarget;
    var form = this.querySelector('[data-form-estado]');
    form.action = HE.url('panel/habitaciones/' + b.dataset.id + '/estado');
    this.querySelector('[data-numero]').textContent = b.dataset.numero;
    form.querySelectorAll('input[name=estado]').forEach(function (r) { r.checked = r.value === b.dataset.estado; });
    form.querySelector('[name=motivo]').value = '';
});
</script>
<?php $this->fin() ?>
<?php endif; ?>
