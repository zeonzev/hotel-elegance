<?php /** @var \App\Core\Vista $this */ ?>
<span class="sobretitulo">Instalador web</span>
<h1>Instalar la base de datos</h1>
<p class="intro">Crea la base <code><?= e($conexion['nombre']) ?></code> e importa estructura, disparadores y datos de prueba desde <code>database/hotel_elegance.sql</code>.</p>

<?php if ($resultado): ?>
    <div class="aviso aviso-verde mb-3"><i class="bi bi-check-circle"></i><div><strong>Instalación completada</strong><span class="fs-sm"><?= (int) $resultado['sentencias'] ?> sentencias ejecutadas en <?= e((string) $resultado['segundos']) ?> s.<?= $resultado['env'] ? '' : ' No se pudo escribir .env; créelo a mano.' ?></span></div></div>
    <a class="btn btn-oro btn-lg mb-3" href="<?= url('/login') ?>"><i class="bi bi-box-arrow-in-right"></i> Ir al inicio de sesión</a>
<?php endif; ?>

<div class="d-grid gap-2 mb-4 fs-sm">
    <div class="d-flex align-items-center gap-2"><i class="bi <?= $estado['servidor'] ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger' ?>"></i> Servidor MySQL <?= $estado['servidor'] ? 'conectado (' . e($estado['version']) . ')' : 'no disponible' ?></div>
    <div class="d-flex align-items-center gap-2"><i class="bi <?= $estado['existe'] && $estado['tablas'] ? 'bi-database-check text-success' : 'bi-database-dash texto-oro' ?>"></i> Base de datos: <?= $estado['existe'] ? ($estado['tablas'] ? 'instalada (' . $estado['tablas'] . ' tablas y vistas, ' . $estado['usuarios'] . ' usuarios)' : 'vacía') : 'todavía no existe' ?></div>
    <div class="d-flex align-items-center gap-2"><i class="bi bi-file-earmark-code texto-tenue"></i> Script: <?= is_file(\App\Servicios\InstaladorServicio::ARCHIVO) ? numero(filesize(\App\Servicios\InstaladorServicio::ARCHIVO) / 1024) . ' KB' : 'no encontrado' ?></div>
    <?php if ($estado['error'] && !$estado['servidor']): ?><div class="aviso aviso-rojo py-2"><i class="bi bi-plug"></i><div class="fs-xs"><?= e($estado['error']) ?><br>Inicie MySQL en el panel de XAMPP o corrija los datos de conexión.</div></div><?php endif; ?>
</div>

<?php if (!$habilitado): ?>
    <div class="aviso aviso-oro"><i class="bi bi-lock"></i><div><strong>Instalador deshabilitado</strong><span class="fs-sm">Cambie INSTALADOR_HABILITADO a true en .env para usarlo.</span></div></div>
<?php elseif ($bloqueado): ?>
    <div class="aviso aviso-azul"><i class="bi bi-shield-lock"></i><div><strong>El sistema ya está instalado</strong><span class="fs-sm">Por seguridad, sólo un administrador con sesión iniciada puede reinstalar la base.</span></div></div>
    <a class="btn btn-primary btn-lg mt-3" href="<?= url('/login') ?>">Iniciar sesión</a>
<?php else: ?>
    <form method="post" action="<?= url('/instalar') ?>">
        <?= csrf_campo() ?>
        <div class="row g-2 mb-3">
            <div class="col-8"><label class="form-label" for="host">Servidor</label><input class="form-control" id="host" name="host" value="<?= e($conexion['host']) ?>"></div>
            <div class="col-4"><label class="form-label" for="puerto">Puerto</label><input type="number" class="form-control" id="puerto" name="puerto" value="<?= (int) $conexion['puerto'] ?>"></div>
            <div class="col-6"><label class="form-label" for="usuario">Usuario</label><input class="form-control" id="usuario" name="usuario" value="<?= e($conexion['usuario']) ?>"></div>
            <div class="col-6"><label class="form-label" for="clave">Contraseña</label><input type="password" class="form-control" id="clave" name="clave" value="" placeholder="(vacía en XAMPP)"></div>
            <div class="col-12"><label class="form-label" for="nombre">Nombre de la base</label><input class="form-control" id="nombre" name="nombre" value="<?= e($conexion['nombre']) ?>" pattern="[A-Za-z0-9_]{1,64}"></div>
            <?php if ($estado['existe'] && $estado['tablas']): ?>
                <div class="col-12"><label class="form-label" for="confirmar">Escriba <strong>REINSTALAR</strong> para borrar y volver a crear la base</label><input class="form-control" id="confirmar" name="confirmar" autocomplete="off"></div>
            <?php endif; ?>
        </div>
        <button class="btn btn-oro btn-lg w-100" type="submit"><i class="bi bi-magic"></i> <?= $estado['existe'] && $estado['tablas'] ? 'Reinstalar' : 'Instalar ahora' ?></button>
        <p class="fs-xs texto-tenue mt-2 mb-0">Si cambia los datos de conexión, el instalador los guardará en <code>.env</code>.</p>
    </form>
<?php endif; ?>
