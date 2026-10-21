<?php /** @var \App\Core\Vista $this */ ?>
<span class="sobretitulo">Panel administrativo</span>
<h1>Bienvenido de vuelta</h1>
<p class="intro">Ingrese con su usuario y contraseña del hotel.</p>

<form method="post" action="<?= url('/login') ?>" class="validar" novalidate>
    <?= csrf_campo() ?>
    <?php if ($error = error_campo('usuario')): ?>
        <div class="aviso aviso-rojo mb-3" role="alert"><i class="bi bi-shield-exclamation"></i><div><?= e($error) ?></div></div>
    <?php endif; ?>
    <div class="mb-3">
        <label class="form-label" for="usuario">Usuario</label>
        <div class="con-icono">
            <i class="bi bi-person"></i>
            <input type="text" class="form-control" id="usuario" name="usuario" value="<?= e(anterior('usuario')) ?>" required autocomplete="username" autofocus maxlength="40" placeholder="p. ej. recepcion">
        </div>
        <div class="invalid-feedback">Ingrese su usuario.</div>
    </div>
    <div class="mb-2">
        <label class="form-label" for="clave">Contraseña</label>
        <div class="campo-clave con-icono">
            <i class="bi bi-lock"></i>
            <input type="password" class="form-control" id="clave" name="clave" required autocomplete="current-password" maxlength="100" placeholder="••••••••">
            <button type="button" data-ver-clave aria-label="Mostrar contraseña"><i class="bi bi-eye"></i></button>
        </div>
        <div class="invalid-feedback">Ingrese su contraseña.</div>
    </div>
    <div class="d-flex justify-content-between align-items-center mb-4 fs-sm">
        <span class="texto-tenue"><i class="bi bi-shield-check texto-oro"></i> Bloqueo tras 5 intentos fallidos</span>
    </div>
    <button type="submit" class="btn btn-primary btn-lg">Ingresar al sistema <i class="bi bi-arrow-right"></i></button>
</form>

<div class="acceso-demo">
    <div class="d-flex align-items-center gap-2 mb-2 fw-semibold"><i class="bi bi-mortarboard texto-oro"></i> Usuarios de prueba (clic para completar)</div>
    <div class="d-flex gap-2">
        <button type="button" data-demo="admin" data-clave="Admin123*"><strong>admin</strong><span>Administrador</span></button>
        <button type="button" data-demo="recepcion" data-clave="Recepcion123*"><strong>recepcion</strong><span>Recepcionista</span></button>
        <button type="button" data-demo="gerente" data-clave="Gerente123*"><strong>gerente</strong><span>Gerente</span></button>
    </div>
</div>

<?php $this->seccion('scripts') ?>
<script>
document.querySelectorAll('[data-demo]').forEach(function (b) {
    b.addEventListener('click', function () {
        document.getElementById('usuario').value = b.dataset.demo;
        document.getElementById('clave').value = b.dataset.clave;
        document.querySelector('.acceso-caja form button[type=submit]').focus();
    });
});
</script>
<?php $this->fin() ?>
