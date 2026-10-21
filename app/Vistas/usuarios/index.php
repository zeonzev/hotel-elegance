<?php
/** @var \App\Core\Vista $this */
echo $this->parcial('parciales/encabezado', [
    'titulo' => 'Usuarios y roles',
    'cu' => 'CU-02 · CU-03 · CU-04',
    'descripcion' => 'Cuentas de acceso del personal, rol asignado, bloqueos por intentos fallidos y restablecimiento de contraseñas.',
    'migas' => [['Administración', null], ['Usuarios y roles', null]],
    'acciones' => '<a class="btn btn-claro" href="' . url('/panel/personal') . '"><i class="bi bi-person-vcard"></i> Personal</a>',
]);
$descripcionRol = ['Administrador' => 'bi-shield-fill-check', 'Recepcionista' => 'bi-bell-fill', 'Gerente' => 'bi-briefcase-fill'];
?>
<?php if ($credenciales): ?>
    <div class="tarjeta tarjeta-noche mb-4 animar-entrada">
        <div class="tarjeta-cuerpo d-flex flex-wrap gap-4 align-items-center">
            <span class="icono-chip chip-oro" style="width:56px;height:56px;font-size:1.5rem"><i class="bi bi-key-fill"></i></span>
            <div class="flex-grow-1">
                <div class="sobretitulo" style="color:var(--he-oro-300)">Cuenta <?= e($credenciales['accion']) ?> · entregue estas credenciales al empleado</div>
                <h3 class="h4 mb-1 mt-1"><?= e($credenciales['empleado'] ?? '@' . $credenciales['nombre_usuario']) ?></h3>
                <div class="texto-suave fs-sm">Esta es la única vez que se muestra la contraseña. Deberá cambiarla en su primer ingreso.</div>
            </div>
            <div class="d-flex gap-3">
                <div class="rounded-3 px-3 py-2" style="background:rgba(255,255,255,.06)"><div class="fs-xs texto-suave">Usuario</div><div class="fs-5 fw-semibold text-white font-monospace"><?= e($credenciales['nombre_usuario']) ?></div></div>
                <div class="rounded-3 px-3 py-2" style="background:rgba(255,255,255,.06)"><div class="fs-xs texto-suave">Contraseña por defecto</div><div class="fs-5 fw-semibold font-monospace" style="color:var(--he-oro-300)"><?= e($credenciales['clave']) ?></div></div>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="row g-4-5 mb-4">
    <?php foreach ($roles as $r): $n = count(array_filter($permisos, fn ($roles) => in_array($r['nombre'], $roles, true))); ?>
        <div class="col-md-4">
            <div class="tarjeta kpi">
                <div class="kpi-cabecera"><span class="kpi-titulo fw-semibold" style="color:var(--he-texto)"><?= e($r['nombre']) ?></span><span class="icono-chip chip-oro"><i class="bi <?= $descripcionRol[$r['nombre']] ?? 'bi-person' ?>"></i></span></div>
                <p class="fs-sm texto-suave mb-3"><?= e($r['descripcion']) ?></p>
                <div class="kpi-pie mt-0"><span class="insignia insignia-gris"><?= (int) $r['cuentas'] ?> cuenta(s)</span><span class="insignia insignia-oro"><?= $n ?> permisos</span></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-4-5">
    <div class="col-xl-8">
        <div class="tarjeta">
            <div class="tarjeta-encabezado"><div><h2>Cuentas de usuario</h2><div class="subtitulo"><?= count($usuarios) ?> cuentas registradas</div></div></div>
            <div class="tabla-contenedor">
                <table class="tabla">
                    <thead><tr><th>Usuario</th><th>Rol</th><th>Último acceso</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                    <tbody>
                    <?php foreach ($usuarios as $u): $esYo = (int) $u['id_usuario'] === \App\Core\Autenticacion::id(); ?>
                        <tr>
                            <td><div class="persona"><span class="avatar avatar-sm"><?= e(iniciales($u['nombres'] . ' ' . $u['apellidos'])) ?></span><div><div class="celda-principal"><?= e($u['nombres'] . ' ' . $u['apellidos']) ?><?= $esYo ? ' <span class="insignia insignia-oro ms-1">Usted</span>' : '' ?></div><div class="celda-secundaria">@<?= e($u['nombre_usuario']) ?> · <?= e($u['cargo']) ?></div></div></div></td>
                            <td>
                                <form method="post" action="<?= url('/panel/usuarios/' . $u['id_usuario'] . '/rol') ?>" class="d-flex gap-1">
                                    <?= csrf_campo() ?>
                                    <select class="form-select form-select-sm" name="id_rol" style="min-width:150px" onchange="this.form.requestSubmit()" aria-label="Rol">
                                        <?php foreach ($roles as $r): ?><option value="<?= $r['id_rol'] ?>"<?= seleccionado($u['id_rol'], $r['id_rol']) ?>><?= e($r['nombre']) ?></option><?php endforeach; ?>
                                    </select>
                                </form>
                            </td>
                            <td><?= $u['ultimo_acceso'] ? e(fecha($u['ultimo_acceso'], 'hora')) : '<span class="texto-tenue">Nunca</span>' ?><?php if ((int) $u['cambiar_contrasena']): ?><div class="celda-secundaria texto-oro"><i class="bi bi-exclamation-circle"></i> Debe cambiar su contraseña</div><?php endif; ?></td>
                            <td><?= insignia($u['estado']) ?><?php if ((int) $u['intentos_fallidos'] > 0 && $u['estado'] !== 'BLOQUEADO'): ?><div class="celda-secundaria"><?= (int) $u['intentos_fallidos'] ?> intento(s) fallido(s)</div><?php endif; ?></td>
                            <td class="acciones">
                                <?php if ($u['estado'] === 'BLOQUEADO'): ?>
                                    <form class="d-inline" method="post" action="<?= url('/panel/usuarios/' . $u['id_usuario'] . '/desbloquear') ?>"><?= csrf_campo() ?><button class="btn btn-sm btn-oro" type="submit"><i class="bi bi-unlock"></i> Desbloquear</button></form>
                                <?php endif; ?>
                                <form class="d-inline" method="post" action="<?= url('/panel/usuarios/' . $u['id_usuario'] . '/restablecer') ?>" data-confirmar="¿Restablecer la contraseña?" data-confirmar-texto="Se generará una nueva contraseña por defecto y el usuario deberá cambiarla al ingresar." data-confirmar-boton="Restablecer" data-confirmar-tipo="normal" data-confirmar-icono="bi-key">
                                    <?= csrf_campo() ?><button class="btn btn-claro btn-accion" type="submit" data-bs-toggle="tooltip" title="Restablecer contraseña"><i class="bi bi-key"></i></button>
                                </form>
                                <?php if (!$esYo): ?>
                                    <form class="d-inline" method="post" action="<?= url('/panel/usuarios/' . $u['id_usuario'] . '/estado') ?>" data-confirmar="<?= $u['estado'] === 'INACTIVO' ? '¿Habilitar la cuenta?' : '¿Deshabilitar la cuenta?' ?>" data-confirmar-texto="<?= $u['estado'] === 'INACTIVO' ? 'El usuario podrá volver a ingresar.' : 'El usuario no podrá iniciar sesión hasta que se habilite.' ?>" data-confirmar-boton="Confirmar">
                                        <?= csrf_campo() ?><button class="btn btn-accion <?= $u['estado'] === 'INACTIVO' ? 'btn-claro' : 'btn-peligro-suave' ?>" type="submit" data-bs-toggle="tooltip" title="<?= $u['estado'] === 'INACTIVO' ? 'Habilitar' : 'Deshabilitar' ?>"><i class="bi <?= $u['estado'] === 'INACTIVO' ? 'bi-toggle-off' : 'bi-toggle-on' ?>"></i></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="tarjeta mb-4">
            <div class="tarjeta-encabezado"><div><h2>Crear cuenta</h2><div class="subtitulo">Empleados activos sin acceso</div></div></div>
            <div class="tarjeta-cuerpo">
                <?php if ($sinCuenta): ?>
                    <form method="post" action="<?= url('/panel/usuarios') ?>">
                        <?= csrf_campo() ?>
                        <label class="form-label" for="id_empleado">Empleado</label>
                        <select class="form-select mb-3" id="id_empleado" name="id_empleado" required>
                            <?php foreach ($sinCuenta as $s): ?><option value="<?= $s['id_empleado'] ?>"><?= e($s['nombres'] . ' ' . $s['apellidos'] . ' — ' . $s['cargo']) ?></option><?php endforeach; ?>
                        </select>
                        <label class="form-label" for="id_rol">Rol</label>
                        <select class="form-select mb-3" id="id_rol" name="id_rol" required>
                            <?php foreach ($roles as $r): ?><option value="<?= $r['id_rol'] ?>"<?= seleccionado($r['nombre'], 'Recepcionista') ?>><?= e($r['nombre']) ?></option><?php endforeach; ?>
                        </select>
                        <button class="btn btn-primary w-100" type="submit"><i class="bi bi-key"></i> Generar credenciales</button>
                        <div class="form-text mt-2">Usuario = inicial del nombre + primer apellido (si existe, se agrega un número).</div>
                    </form>
                <?php else: ?>
                    <p class="texto-suave fs-sm mb-0">Todos los empleados activos ya tienen cuenta.</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="tarjeta">
            <div class="tarjeta-encabezado"><div><h2>Matriz de permisos</h2><div class="subtitulo">Aplicada en el menú y en el servidor</div></div></div>
            <div class="tabla-contenedor" style="max-height: 420px">
                <table class="tabla tabla-compacta">
                    <thead><tr><th>Permiso</th><?php foreach ($roles as $r): ?><th class="text-center"><?= e(mb_substr($r['nombre'], 0, 5)) ?>.</th><?php endforeach; ?></tr></thead>
                    <tbody>
                    <?php foreach ($permisos as $permiso => $rolesPermiso): ?>
                        <tr><td class="fs-xs font-monospace"><?= e($permiso) ?></td><?php foreach ($roles as $r): ?><td class="text-center"><?= in_array($r['nombre'], $rolesPermiso, true) ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-dash texto-tenue"></i>' ?></td><?php endforeach; ?></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
