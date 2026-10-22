<?php
/** @var \App\Core\Vista $this */
$actuales = array_values(array_filter(array_map(fn ($d) => $d['id_habitacion'] ? (int) $d['id_habitacion'] : null, $detalle)));
$seleccion = array_map('intval', (array) anterior('habitaciones', $actuales));
echo $this->parcial('parciales/encabezado', [
    'titulo' => 'Modificar ' . $r['codigo'],
    'cu' => 'CU-13 «include» CU-12',
    'descripcion' => 'Al cambiar fechas o habitaciones se vuelve a consultar la disponibilidad y se recalcula el monto con la tarifa de cada noche.',
    'migas' => [['Reservas', '/panel/reservas'], [$r['codigo'], '/panel/reservas/' . $r['id_reserva']], ['Modificar', null]],
]);
?>
<form method="post" action="<?= url('/panel/reservas/' . $r['id_reserva']) ?>" id="form-editar">
    <?= csrf_campo() ?>
    <div class="row g-4-5">
        <div class="col-xl-8">
            <div class="tarjeta mb-4">
                <div class="tarjeta-encabezado"><div><h2>Fechas y datos</h2><div class="subtitulo">Huésped: <?= e($r['huesped']) ?> · Monto actual <?= e(bs($r['monto_total'])) ?></div></div></div>
                <div class="tarjeta-cuerpo row g-3">
                    <div class="col-sm-6 col-lg-3"><label class="form-label" for="fecha_ingreso">Ingreso</label><input type="date" class="form-control<?= clase_error('fecha_ingreso') ?>" id="fecha_ingreso" name="fecha_ingreso" value="<?= e(anterior('fecha_ingreso', $r['fecha_ingreso'])) ?>" required><?= mensaje_error('fecha_ingreso') ?></div>
                    <div class="col-sm-6 col-lg-3"><label class="form-label" for="fecha_salida">Salida</label><input type="date" class="form-control<?= clase_error('fecha_salida') ?>" id="fecha_salida" name="fecha_salida" value="<?= e(anterior('fecha_salida', $r['fecha_salida'])) ?>" required><?= mensaje_error('fecha_salida') ?></div>
                    <div class="col-sm-6 col-lg-2"><label class="form-label" for="cantidad_personas">Personas</label><input type="number" min="1" max="20" class="form-control<?= clase_error('cantidad_personas') ?>" id="cantidad_personas" name="cantidad_personas" value="<?= e(anterior('cantidad_personas', $r['cantidad_personas'])) ?>"><?= mensaje_error('cantidad_personas') ?></div>
                    <div class="col-sm-6 col-lg-4"><label class="form-label" for="id_canal">Canal</label><select class="form-select" id="id_canal" name="id_canal"><?php foreach ($canales as $c): ?><option value="<?= $c['id_canal'] ?>"<?= seleccionado(anterior('id_canal', $r['id_canal']), $c['id_canal']) ?>><?= e($c['nombre']) ?></option><?php endforeach; ?></select></div>
                    <div class="col-12"><label class="form-label" for="observaciones">Observaciones</label><textarea class="form-control" id="observaciones" name="observaciones" rows="2" maxlength="1000"><?= e(anterior('observaciones', $r['observaciones'])) ?></textarea></div>
                </div>
            </div>
            <div class="tarjeta">
                <div class="tarjeta-encabezado"><div><h2>Habitaciones</h2><div class="subtitulo">Se muestran las libres en las nuevas fechas (la reserva actual no se cuenta como ocupación)</div></div><span class="insignia insignia-gris" id="contador">—</span></div>
                <?php if ($e = error_campo('habitaciones')): ?><div class="px-4 pt-3"><div class="aviso aviso-rojo"><i class="bi bi-exclamation-octagon"></i><div><strong><?= e($e) ?></strong></div></div></div><?php endif; ?>
                <div class="tarjeta-cuerpo" id="habitaciones"><div class="text-center texto-tenue py-3"><span class="spinner-border spinner-border-sm"></span> Consultando…</div></div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="tarjeta resumen-reserva">
                <div class="tarjeta-encabezado"><h2>Nuevo monto</h2></div>
                <div class="tarjeta-cuerpo">
                    <div id="resumen" class="fs-sm"></div>
                    <div class="resumen-total"><span class="texto-suave">Total</span><strong class="tabular" id="total">—</strong></div>
                    <div class="fs-xs texto-tenue mt-1">Monto anterior: <?= e(bs($r['monto_total'])) ?></div>
                    <button class="btn btn-primary btn-lg w-100 mt-4" type="submit"><i class="bi bi-check2"></i> Guardar cambios</button>
                    <a class="btn btn-fantasma w-100 mt-2" href="<?= url('/panel/reservas/' . $r['id_reserva']) ?>">Cancelar</a>
                </div>
            </div>
        </div>
    </div>
</form>
<?php $this->seccion('scripts') ?>
<script>
(function () {
    var seleccion = new Set(<?= json_encode($seleccion) ?>);
    var sinAsignar = <?= json_encode(count($detalle) - count($actuales)) ?>;
    var datos = null;
    var ingreso = document.getElementById('fecha_ingreso'), salida = document.getElementById('fecha_salida');
    var caja = document.getElementById('habitaciones');
    function resumen() {
        var total = 0, html = '';
        if (datos) datos.categorias.forEach(function (c) {
            c.habitaciones.forEach(function (h) {
                if (seleccion.has(h.id)) { total += c.total; html += '<div class="resumen-linea"><span>Hab. <b>' + HE.escapar(h.numero) + '</b> · ' + HE.escapar(c.nombre) + '</span><span class="tabular">' + HE.bs(c.total) + '</span></div>'; }
            });
        });
        if (!seleccion.size && sinAsignar) html += '<div class="texto-tenue">Línea en sobreventa sin habitación (se mantiene)</div>';
        document.getElementById('resumen').innerHTML = html || '<span class="texto-tenue">Ninguna habitación seleccionada</span>';
        document.getElementById('total').textContent = seleccion.size ? HE.bs(total) : '—';
    }
    async function consultar() {
        if (!ingreso.value || !salida.value || salida.value <= ingreso.value) { caja.innerHTML = '<div class="aviso aviso-rojo"><i class="bi bi-exclamation-circle"></i><div><strong>La salida debe ser posterior al ingreso.</strong></div></div>'; return; }
        try {
            datos = await HE.api('panel/api/disponibilidad?excluir=<?= (int) $r['id_reserva'] ?>&ingreso=' + ingreso.value + '&salida=' + salida.value);
        } catch (e) { caja.innerHTML = '<div class="aviso aviso-rojo"><i class="bi bi-exclamation-circle"></i><div><strong>' + HE.escapar(e.message) + '</strong></div></div>'; return; }
        var libres = new Set(), html = '';
        datos.categorias.forEach(function (c) {
            if (!c.habitaciones.length) return;
            html += '<div class="mb-3"><div class="fw-semibold mb-2">' + HE.escapar(c.nombre) + ' <span class="fs-xs texto-tenue">· ' + HE.bs(c.promedio, 0) + '/noche · ' + c.capacidad + ' pers.</span></div><div class="d-flex flex-wrap gap-2">';
            c.habitaciones.forEach(function (h) {
                libres.add(h.id);
                html += '<label class="opcion-tarjeta habitacion-opcion" style="min-width:120px"><input type="checkbox" name="habitaciones[]" value="' + h.id + '"' + (seleccion.has(h.id) ? ' checked' : '') + '><span class="contenido py-2"><span class="numero">' + HE.escapar(h.numero) + '</span><span class="fs-xs texto-tenue">Piso ' + h.piso + '</span></span></label>';
            });
            html += '</div></div>';
        });
        seleccion.forEach(function (id) { if (!libres.has(id)) seleccion.delete(id); });
        document.getElementById('contador').textContent = libres.size + ' libres · ' + datos.noches + ' noche(s)';
        caja.innerHTML = html || '<div class="aviso aviso-oro"><i class="bi bi-info-circle"></i><div><strong>No hay habitaciones libres en esas fechas.</strong></div></div>';
        resumen();
    }
    caja.addEventListener('change', function (e) { var id = +e.target.value; e.target.checked ? seleccion.add(id) : seleccion.delete(id); resumen(); });
    [ingreso, salida].forEach(function (c) { c.addEventListener('change', consultar); });
    consultar();
})();
</script>
<?php $this->fin() ?>
