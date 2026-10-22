<?php /** @var \App\Core\Vista $this */ ?>
<section class="cabecera-pagina pb-5">
    <div class="contenedor"><span class="sobretitulo">Contacto</span><h1>Estamos para ayudarle</h1><p>Escríbanos para convenios corporativos, grupos, traslados o cualquier consulta sobre su estadía.</p></div>
</section>
<section class="seccion pt-5">
    <div class="contenedor">
        <div class="row g-5">
            <div class="col-lg-5">
                <div class="panorama mb-4" style="min-height: 280px; background-image: url('<?= asset('img/hotel/fachada-noche.jpg') ?>')"><div class="contenido"><strong class="d-block fs-4" style="font-family:var(--he-serif)">Hotel Elegance</strong><span><?= e(ajuste('hotel_direccion')) ?></span></div></div>
                <div class="lugar border-0 py-2"><i class="bi bi-geo-alt texto-oro"></i><div><strong class="text-reset">Dirección</strong><span class="texto-suave"><?= e(ajuste('hotel_direccion')) ?>, <?= e(ajuste('hotel_ciudad')) ?></span></div></div>
                <div class="lugar border-0 py-2"><i class="bi bi-telephone texto-oro"></i><div><strong class="text-reset">Teléfono</strong><span class="texto-suave"><?= e(ajuste('hotel_telefono')) ?></span></div></div>
                <div class="lugar border-0 py-2"><i class="bi bi-whatsapp texto-oro"></i><div><strong class="text-reset">WhatsApp</strong><span class="texto-suave"><?= e(ajuste('hotel_whatsapp')) ?></span></div></div>
                <div class="lugar border-0 py-2"><i class="bi bi-envelope texto-oro"></i><div><strong class="text-reset">Correo</strong><span class="texto-suave"><?= e(ajuste('hotel_correo')) ?></span></div></div>
            </div>
            <div class="col-lg-7">
                <form method="post" action="<?= url('/contacto') ?>" class="panel-reserva position-static validar" novalidate>
                    <?= csrf_campo() ?>
                    <input type="text" name="sitio_web" class="d-none" tabindex="-1" autocomplete="off" aria-hidden="true">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label" for="nombre">Nombre completo *</label><input class="form-control<?= clase_error('nombre') ?>" id="nombre" name="nombre" value="<?= e(anterior('nombre')) ?>" required maxlength="120"><?= mensaje_error('nombre') ?></div>
                        <div class="col-md-6"><label class="form-label" for="correo">Correo *</label><input type="email" class="form-control<?= clase_error('correo') ?>" id="correo" name="correo" value="<?= e(anterior('correo')) ?>" required><?= mensaje_error('correo') ?><div class="invalid-feedback">Correo no válido.</div></div>
                        <div class="col-md-6"><label class="form-label" for="telefono">Teléfono</label><input class="form-control<?= clase_error('telefono') ?>" id="telefono" name="telefono" value="<?= e(anterior('telefono')) ?>"><?= mensaje_error('telefono') ?></div>
                        <div class="col-md-6"><label class="form-label" for="asunto">Asunto *</label><input class="form-control<?= clase_error('asunto') ?>" id="asunto" name="asunto" value="<?= e(anterior('asunto')) ?>" required maxlength="120"><?= mensaje_error('asunto') ?></div>
                        <div class="col-12"><label class="form-label" for="mensaje">Mensaje *</label><textarea class="form-control<?= clase_error('mensaje') ?>" id="mensaje" name="mensaje" rows="6" required minlength="10" maxlength="2000"><?= e(anterior('mensaje')) ?></textarea><?= mensaje_error('mensaje') ?><div class="invalid-feedback">Escriba su mensaje (mínimo 10 caracteres).</div></div>
                        <div class="col-12"><button class="btn btn-oro btn-lg" type="submit"><i class="bi bi-send"></i> Enviar mensaje</button></div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
