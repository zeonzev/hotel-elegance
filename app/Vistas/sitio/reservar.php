<?php
/** @var \App\Core\Vista $this */
$ingreso = (string) anterior('fecha_ingreso', $_GET['ingreso'] ?? date('Y-m-d', strtotime('+7 days')));
$salida = (string) anterior('fecha_salida', $_GET['salida'] ?? date('Y-m-d', strtotime('+9 days')));
$personas = (int) anterior('cantidad_personas', $_GET['personas'] ?? 2);
$categoria = (int) anterior('id_categoria', $_GET['categoria'] ?? 0);
$imagenes = [];
foreach ($categorias as $c) {
    $imagenes[(int) $c['id_categoria']] = asset('img/habitaciones/' . $c['galeria'][0]);
}
?>
<section class="cabecera-pagina pb-5">
    <div class="contenedor"><span class="sobretitulo">Reserva en línea</span><h1>Solicite su reserva</h1><p>Elija fechas y habitación; nuestra recepción confirmará su reserva por correo o WhatsApp en minutos.</p></div>
</section>
<section class="seccion pt-5">
    <div class="contenedor">
        <form method="post" action="<?= url('/reservar') ?>" id="form-publico" class="validar" novalidate>
            <?= csrf_campo() ?>
            <input type="text" name="sitio_web" class="d-none" tabindex="-1" autocomplete="off" aria-hidden="true">
            <div class="row g-5">
                <div class="col-lg-8">
                    <h2 class="h3 mb-3">1. Fechas</h2>
                    <div class="row g-3 mb-4">
                        <div class="col-sm-4"><label class="form-label" for="fecha_ingreso">Llegada</label><input type="date" class="form-control<?= clase_error('fecha_ingreso') ?>" id="fecha_ingreso" name="fecha_ingreso" value="<?= e($ingreso) ?>" min="<?= hoy() ?>" required><?= mensaje_error('fecha_ingreso') ?></div>
                        <div class="col-sm-4"><label class="form-label" for="fecha_salida">Salida</label><input type="date" class="form-control<?= clase_error('fecha_salida') ?>" id="fecha_salida" name="fecha_salida" value="<?= e($salida) ?>" required><?= mensaje_error('fecha_salida') ?></div>
                        <div class="col-sm-4"><label class="form-label" for="cantidad_personas">Huéspedes</label><input type="number" min="1" max="8" class="form-control<?= clase_error('cantidad_personas') ?>" id="cantidad_personas" name="cantidad_personas" value="<?= $personas ?>" required><?= mensaje_error('cantidad_personas') ?></div>
                    </div>
                    <h2 class="h3 mb-3">2. Habitación</h2>
                    <?= mensaje_error('id_categoria') ?>
                    <div id="categorias-publicas" class="d-grid gap-3 mb-5"><div class="texto-tenue py-3"><span class="spinner-border spinner-border-sm"></span> Consultando disponibilidad…</div></div>
                    <h2 class="h3 mb-3">3. Sus datos</h2>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label" for="nombres">Nombres *</label><input class="form-control<?= clase_error('nombres') ?>" id="nombres" name="nombres" value="<?= e(anterior('nombres')) ?>" required maxlength="80"><?= mensaje_error('nombres') ?><div class="invalid-feedback">Ingrese sus nombres.</div></div>
                        <div class="col-md-6"><label class="form-label" for="apellidos">Apellidos *</label><input class="form-control<?= clase_error('apellidos') ?>" id="apellidos" name="apellidos" value="<?= e(anterior('apellidos')) ?>" required maxlength="80"><?= mensaje_error('apellidos') ?><div class="invalid-feedback">Ingrese sus apellidos.</div></div>
                        <div class="col-md-4"><label class="form-label" for="id_tipo_documento">Documento *</label><select class="form-select" id="id_tipo_documento" name="id_tipo_documento"><?php foreach ($tipos as $t): ?><option value="<?= $t['id_tipo_documento'] ?>"<?= seleccionado(anterior('id_tipo_documento', 1), $t['id_tipo_documento']) ?>><?= e($t['nombre']) ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-4"><label class="form-label" for="nro_documento">Número *</label><input class="form-control<?= clase_error('nro_documento') ?>" id="nro_documento" name="nro_documento" value="<?= e(anterior('nro_documento')) ?>" required pattern="[A-Za-z0-9\-]{4,20}"><?= mensaje_error('nro_documento') ?><div class="invalid-feedback">Documento no válido.</div></div>
                        <div class="col-md-4"><label class="form-label" for="nacionalidad">Nacionalidad *</label><input class="form-control" id="nacionalidad" name="nacionalidad" value="<?= e(anterior('nacionalidad', 'Boliviana')) ?>" required maxlength="60"></div>
                        <div class="col-md-4"><label class="form-label" for="ciudad_procedencia">Ciudad de procedencia *</label><input class="form-control<?= clase_error('ciudad_procedencia') ?>" id="ciudad_procedencia" name="ciudad_procedencia" value="<?= e(anterior('ciudad_procedencia')) ?>" required maxlength="80"><div class="invalid-feedback">Indique su ciudad.</div></div>
                        <div class="col-md-4"><label class="form-label" for="correo">Correo *</label><input type="email" class="form-control<?= clase_error('correo') ?>" id="correo" name="correo" value="<?= e(anterior('correo')) ?>" required maxlength="120"><?= mensaje_error('correo') ?><div class="invalid-feedback">Correo no válido.</div></div>
                        <div class="col-md-4"><label class="form-label" for="telefono">Teléfono / WhatsApp *</label><input class="form-control<?= clase_error('telefono') ?>" id="telefono" name="telefono" value="<?= e(anterior('telefono')) ?>" required maxlength="25"><?= mensaje_error('telefono') ?><div class="invalid-feedback">Ingrese un teléfono.</div></div>
                        <div class="col-12"><label class="form-label" for="mensaje">Comentarios <span class="opcional">(hora de llegada, ocasión especial…)</span></label><textarea class="form-control" id="mensaje" name="mensaje" rows="3" maxlength="600"><?= e(anterior('mensaje')) ?></textarea></div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="panel-reserva">
                        <h3 class="h4 mb-3">Su estadía</h3>
                        <div class="resumen-linea"><span class="texto-suave">Fechas</span><strong id="p-fechas">—</strong></div>
                        <div class="resumen-linea"><span class="texto-suave">Noches</span><strong id="p-noches">—</strong></div>
                        <div class="resumen-linea"><span class="texto-suave">Habitación</span><strong id="p-hab">Sin elegir</strong></div>
                        <div class="resumen-total"><span class="texto-suave">Total estimado</span><strong id="p-total">—</strong></div>
                        <p class="fs-xs texto-tenue mt-2">Tarifa calculada noche por noche según la temporada. No se realiza ningún cobro en línea.</p>
                        <button class="btn btn-oro btn-lg w-100 mt-2" type="submit">Enviar solicitud</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>
<?php $this->seccion('scripts') ?>
<script>
(function () {
    var seleccion = <?= $categoria ?>;
    var imagenes = <?= json_encode($imagenes) ?>;
    var datos = null;
    var ingreso = document.getElementById('fecha_ingreso'), salida = document.getElementById('fecha_salida'), personas = document.getElementById('cantidad_personas');
    var caja = document.getElementById('categorias-publicas');
    function resumen() {
        var c = datos && datos.categorias.filter(function (x) { return x.id === seleccion; })[0];
        var f = function (v) { return new Date(v + 'T12:00:00').toLocaleDateString('es-BO', { day: 'numeric', month: 'short' }); };
        document.getElementById('p-fechas').textContent = ingreso.value && salida.value ? f(ingreso.value) + ' → ' + f(salida.value) : '—';
        document.getElementById('p-noches').textContent = datos ? datos.noches : '—';
        document.getElementById('p-hab').textContent = c ? c.nombre : 'Sin elegir';
        document.getElementById('p-total').textContent = c ? HE.bs(c.total) : '—';
    }
    async function consultar() {
        if (!ingreso.value || !salida.value || salida.value <= ingreso.value) { caja.innerHTML = '<div class="aviso aviso-oro"><i class="bi bi-calendar-x"></i><div><strong>La salida debe ser posterior a la llegada.</strong></div></div>'; datos = null; resumen(); return; }
        try { datos = await HE.api('api/disponibilidad?ingreso=' + ingreso.value + '&salida=' + salida.value + '&personas=1'); }
        catch (e) { caja.innerHTML = '<div class="aviso aviso-rojo"><i class="bi bi-exclamation-circle"></i><div><strong>' + HE.escapar(e.message) + '</strong></div></div>'; datos = null; resumen(); return; }
        if (!datos.categorias.length) { caja.innerHTML = '<div class="aviso aviso-oro"><i class="bi bi-emoji-frown"></i><div><strong>No tenemos habitaciones libres en esas fechas.</strong><span class="fs-sm">Pruebe otras fechas o escríbanos por WhatsApp.</span></div></div>'; resumen(); return; }
        if (!datos.categorias.some(function (c) { return c.id === seleccion; })) seleccion = 0;
        caja.innerHTML = datos.categorias.map(function (c) {
            var cabe = c.capacidad >= (+personas.value || 1);
            return '<label class="opcion-tarjeta opcion-categoria"><input type="radio" name="id_categoria" value="' + c.id + '"' + (c.id === seleccion ? ' checked' : '') + ' required><span class="contenido"><img src="' + imagenes[c.id] + '" alt=""><span class="flex-grow-1"><strong class="d-block fs-5">' + HE.escapar(c.nombre) + '</strong><span class="fs-sm texto-tenue">Hasta ' + c.capacidad + ' personas' + (cabe ? '' : ' · <span class="texto-oro">necesitará más de una habitación</span>') + (c.pocas ? ' · <span class="text-danger">¡Quedan pocas!</span>' : '') + '</span></span><span class="text-end"><strong class="d-block fs-5 tabular">' + HE.bs(c.total, 0) + '</strong><span class="fs-xs texto-tenue">' + datos.noches + ' noche(s) · ' + HE.bs(c.promedio, 0) + '/noche</span></span></span></label>';
        }).join('');
        resumen();
    }
    caja.addEventListener('change', function (e) { if (e.target.name === 'id_categoria') { seleccion = +e.target.value; resumen(); } });
    [ingreso, salida, personas].forEach(function (c) { c.addEventListener('change', consultar); });
    consultar();
})();
</script>
<?php $this->fin() ?>
