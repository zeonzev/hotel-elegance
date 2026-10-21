<?php
$descripciones = [
    403 => 'Su rol no tiene permiso para ver esta sección. Si cree que es un error, consulte con el administrador del sistema.',
    404 => 'La página que busca no existe o fue movida. Revise la dirección o vuelva al inicio.',
    405 => 'La acción solicitada no está permitida para esta dirección.',
    419 => 'Por seguridad, el formulario expiró. Vuelva atrás, recargue la página e inténtelo nuevamente.',
    500 => 'Algo salió mal en el servidor. El error quedó registrado para su revisión; por favor, inténtelo otra vez.',
];
$iconos = [403 => 'bi-shield-lock', 404 => 'bi-signpost-split', 405 => 'bi-slash-circle', 419 => 'bi-hourglass-bottom', 500 => 'bi-tools'];
$autenticado = false;
try { $autenticado = \App\Core\Autenticacion::verificado(); } catch (\Throwable) {}
?>
<div class="error-pantalla">
    <div class="text-center" style="max-width: 560px">
        <a href="<?= url('/') ?>" class="d-inline-flex align-items-center gap-2 mb-4 text-reset">
            <img src="<?= asset('img/logo-marca.svg') ?>" alt="" width="40" height="40">
            <span class="serif fs-4 fw-semibold">Hotel Elegance</span>
        </a>
        <div class="error-codigo"><?= (int) $codigo ?></div>
        <div class="d-inline-flex align-items-center gap-2 insignia insignia-oro my-3"><i class="bi <?= $iconos[$codigo] ?? 'bi-exclamation-circle' ?>"></i><?= e($titulo) ?></div>
        <h1 class="h2 mb-3"><?= e($titulo) ?></h1>
        <p class="texto-suave mb-2"><?= e($descripciones[$codigo] ?? $descripciones[500]) ?></p>
        <?php if (!empty($mensaje)): ?>
            <p class="fs-sm texto-tenue"><?= e($mensaje) ?></p>
        <?php endif; ?>
        <div class="d-flex flex-wrap gap-2 justify-content-center mt-4">
            <a href="javascript:history.back()" class="btn btn-claro"><i class="bi bi-arrow-left"></i> Volver</a>
            <a href="<?= url($autenticado ? '/panel' : '/') ?>" class="btn btn-primary"><i class="bi bi-house"></i> <?= $autenticado ? 'Ir al panel' : 'Ir al inicio' ?></a>
        </div>
    </div>
</div>
