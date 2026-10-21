<?php /** @var \App\Core\Vista $this */ ?>
<span class="sobretitulo">Primer ingreso</span>
<h1>Defina su contraseña</h1>
<p class="intro">Hola, <?= e(explode(' ', $usuario['nombres'])[0]) ?>. Su cuenta usa una contraseña por defecto; reemplácela por una personal para continuar.</p>

<form method="post" action="<?= url('/cambiar-contrasena') ?>" class="validar" novalidate>
    <?= csrf_campo() ?>
    <div class="mb-3">
        <label class="form-label" for="contrasena_actual">Contraseña actual (por defecto)</label>
        <div class="campo-clave">
            <input type="password" class="form-control<?= clase_error('contrasena_actual') ?>" id="contrasena_actual" name="contrasena_actual" required autocomplete="current-password">
            <button type="button" data-ver-clave aria-label="Mostrar"><i class="bi bi-eye"></i></button>
        </div>
        <?= mensaje_error('contrasena_actual') ?>
    </div>
    <div class="mb-3">
        <label class="form-label" for="contrasena">Nueva contraseña</label>
        <div class="campo-clave">
            <input type="password" class="form-control<?= clase_error('contrasena') ?>" id="contrasena" name="contrasena" required minlength="8" autocomplete="new-password" data-medir>
            <button type="button" data-ver-clave aria-label="Mostrar"><i class="bi bi-eye"></i></button>
        </div>
        <div class="medidor" data-medidor><span></span><span></span><span></span><span></span></div>
        <ul class="requisitos" data-requisitos>
            <li data-r="largo">8 caracteres o más</li>
            <li data-r="mayus">Una mayúscula</li>
            <li data-r="minus">Una minúscula</li>
            <li data-r="num">Un número</li>
            <li data-r="simbolo">Un símbolo (* # ! …)</li>
        </ul>
        <?= mensaje_error('contrasena') ?>
    </div>
    <div class="mb-4">
        <label class="form-label" for="contrasena_confirmacion">Confirmar nueva contraseña</label>
        <input type="password" class="form-control<?= clase_error('contrasena_confirmacion') ?>" id="contrasena_confirmacion" name="contrasena_confirmacion" required autocomplete="new-password">
        <?= mensaje_error('contrasena_confirmacion') ?>
    </div>
    <button type="submit" class="btn btn-primary btn-lg">Guardar y continuar <i class="bi bi-arrow-right"></i></button>
</form>
<form method="post" action="<?= url('/logout') ?>" class="text-center mt-3">
    <?= csrf_campo() ?>
    <button class="btn btn-fantasma btn-sm" type="submit"><i class="bi bi-box-arrow-left"></i> Salir</button>
</form>

<?php $this->seccion('scripts') ?>
<?= $this->parcial('parciales/medidor-clave') ?>
<?php $this->fin() ?>
