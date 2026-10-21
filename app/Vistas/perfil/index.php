<?php
/** @var \App\Core\Vista $this */
echo $this->parcial('parciales/encabezado', [
    'titulo' => 'Mi perfil',
    'descripcion' => 'Sus datos dentro del sistema, su actividad reciente y la seguridad de su cuenta.',
    'migas' => [['Mi perfil', null]],
]);
$permisosRol = array_keys(array_filter(\App\Core\Autenticacion::permisos(), fn ($roles) => in_array($u['rol'], $roles, true)));
?>
<div class="row g-4-5">
    <div class="col-xl-4">
        <div class="tarjeta tarjeta-noche mb-4">
            <div class="tarjeta-cuerpo text-center p-4">
                <span class="avatar avatar-xl mb-3"><?= e(iniciales($u['nombres'] . ' ' . $u['apellidos'])) ?></span>
                <h2 class="h3 mb-0"><?= e($u['nombres'] . ' ' . $u['apellidos']) ?></h2>
                <div class="texto-suave">@<?= e($u['nombre_usuario']) ?> · <?= e($u['cargo']) ?></div>
                <span class="insignia insignia-oro mt-3"><i class="bi bi-shield-check"></i>Rol: <?= e($u['rol']) ?></span>
                <div class="row g-2 mt-4">
                    <?php foreach ([['Reservas', $estadisticas['reservas']], ['Check-in', $estadisticas['checkins']], ['Check-out', $estadisticas['checkouts']]] as [$t, $n]): ?>
                        <div class="col-4"><div class="rounded-3 py-2" style="background:rgba(255,255,255,.05)"><div class="fs-4 fw-semibold text-white tabular"><?= numero($n) ?></div><div class="fs-xs texto-suave"><?= $t ?></div></div></div>
                    <?php endforeach; ?>
                </div>
                <div class="texto-suave fs-xs mt-3">Último acceso: <?= e(fecha($u['ultimo_acceso'], 'hora')) ?></div>
            </div>
        </div>
        <div class="tarjeta">
            <div class="tarjeta-encabezado"><div><h2>Sus permisos</h2><div class="subtitulo">Según el rol <?= e($u['rol']) ?></div></div></div>
            <div class="tarjeta-cuerpo d-flex flex-wrap gap-2">
                <?php foreach ($permisosRol as $p): ?><span class="etiqueta-codigo fs-xs"><?= e($p) ?></span><?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="col-xl-8">
        <div class="tarjeta mb-4">
            <div class="tarjeta-encabezado"><div><h2>Datos de contacto</h2><div class="subtitulo">Los datos de identificación sólo los modifica el administrador</div></div></div>
            <form method="post" action="<?= url('/panel/perfil') ?>" class="validar" novalidate>
                <?= csrf_campo() ?>
                <div class="tarjeta-cuerpo row g-3">
                    <div class="col-md-6"><label class="form-label">Nombres</label><input class="form-control" value="<?= e($u['nombres'] . ' ' . $u['apellidos']) ?>" disabled></div>
                    <div class="col-md-6"><label class="form-label">Cargo</label><input class="form-control" value="<?= e($u['cargo']) ?>" disabled></div>
                    <div class="col-md-6"><label class="form-label" for="telefono">Teléfono</label><input class="form-control<?= clase_error('telefono') ?>" id="telefono" name="telefono" value="<?= e(anterior('telefono', $u['telefono'])) ?>"><?= mensaje_error('telefono') ?></div>
                    <div class="col-md-6"><label class="form-label" for="correo">Correo</label><input type="email" class="form-control<?= clase_error('correo') ?>" id="correo" name="correo" value="<?= e(anterior('correo', $u['correo'])) ?>"><?= mensaje_error('correo') ?></div>
                </div>
                <div class="tarjeta-pie text-end"><button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> Guardar</button></div>
            </form>
        </div>
        <div class="tarjeta mb-4" id="seguridad">
            <div class="tarjeta-encabezado"><div><h2>Cambiar contraseña</h2><div class="subtitulo">Mínimo 8 caracteres con mayúscula, minúscula, número y símbolo</div></div><span class="icono-chip chip-oro"><i class="bi bi-shield-lock"></i></span></div>
            <form method="post" action="<?= url('/panel/perfil/contrasena') ?>" class="validar" novalidate>
                <?= csrf_campo() ?>
                <div class="tarjeta-cuerpo row g-3">
                    <div class="col-md-4"><label class="form-label" for="contrasena_actual">Contraseña actual</label><div class="campo-clave position-relative"><input type="password" class="form-control<?= clase_error('contrasena_actual') ?>" id="contrasena_actual" name="contrasena_actual" required autocomplete="current-password"></div><?= mensaje_error('contrasena_actual') ?></div>
                    <div class="col-md-4"><label class="form-label" for="contrasena">Nueva contraseña</label><div><input type="password" class="form-control<?= clase_error('contrasena') ?>" id="contrasena" name="contrasena" required minlength="8" autocomplete="new-password" data-medir></div><div class="medidor" data-medidor><span></span><span></span><span></span><span></span></div><?= mensaje_error('contrasena') ?></div>
                    <div class="col-md-4"><label class="form-label" for="contrasena_confirmacion">Confirmar</label><input type="password" class="form-control<?= clase_error('contrasena_confirmacion') ?>" id="contrasena_confirmacion" name="contrasena_confirmacion" required autocomplete="new-password"><?= mensaje_error('contrasena_confirmacion') ?></div>
                </div>
                <div class="tarjeta-pie text-end"><button class="btn btn-primary" type="submit"><i class="bi bi-key"></i> Actualizar contraseña</button></div>
            </form>
        </div>
        <div class="tarjeta">
            <div class="tarjeta-encabezado"><div><h2>Su actividad reciente</h2></div></div>
            <div class="tarjeta-cuerpo">
                <?php if ($actividad): ?>
                    <ul class="linea-tiempo">
                        <?php foreach ($actividad as $a): ?><li><span class="marca"><i class="bi bi-dot"></i></span><strong><?= e($a['accion']) ?></strong><small><?= e($a['descripcion']) ?> · <?= e(fecha($a['fecha'], 'hora')) ?></small></li><?php endforeach; ?>
                    </ul>
                <?php else: ?><p class="texto-suave mb-0">Sin actividad registrada.</p><?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php $this->seccion('scripts') ?><?= $this->parcial('parciales/medidor-clave') ?><?php $this->fin() ?>
