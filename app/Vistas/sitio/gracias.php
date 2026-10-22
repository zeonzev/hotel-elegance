<?php /** @var \App\Core\Vista $this */ ?>
<section class="cabecera-pagina pb-5">
    <div class="contenedor"><span class="sobretitulo">Solicitud recibida</span><h1><?= $solicitud ? '¡Gracias, ' . e($solicitud['nombre']) . '!' : '¡Gracias!' ?></h1><p>Nuestra recepción revisará la disponibilidad y le confirmará la reserva en breve.</p></div>
</section>
<section class="seccion pt-5">
    <div class="contenedor" style="max-width: 760px">
        <?php if ($solicitud): ?>
            <div class="panel-reserva position-static mb-4">
                <div class="d-flex align-items-center gap-3 mb-3"><span class="icono-chip chip-verde" style="width:52px;height:52px;font-size:1.5rem"><i class="bi bi-check2-circle"></i></span><div><div class="sobretitulo">Solicitud N.º <?= (int) $solicitud['id'] ?></div><strong class="fs-5"><?= e($solicitud['categoria']) ?></strong></div></div>
                <div class="resumen-linea"><span class="texto-suave">Llegada</span><strong><?= e(fecha($solicitud['ingreso'], 'larga')) ?></strong></div>
                <div class="resumen-linea"><span class="texto-suave">Salida</span><strong><?= e(fecha($solicitud['salida'], 'larga')) ?></strong></div>
                <div class="resumen-total"><span class="texto-suave">Total estimado</span><strong><?= e(bs($solicitud['total'])) ?></strong></div>
                <p class="fs-sm texto-suave mt-3 mb-0">Enviaremos la confirmación a <strong><?= e($solicitud['correo']) ?></strong>. Si lo prefiere, escríbanos al <?= e(ajuste('hotel_whatsapp')) ?>.</p>
            </div>
        <?php endif; ?>
        <div class="d-flex flex-wrap gap-2"><a class="btn btn-primary btn-lg" href="<?= url('/') ?>">Volver al inicio</a><a class="btn btn-claro btn-lg" href="<?= url('/habitaciones') ?>">Ver habitaciones</a></div>
    </div>
</section>
