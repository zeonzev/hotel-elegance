<?php
/** @var \App\Core\Vista $this */
use App\Core\Sesion;
use App\Servicios\PanelServicio;

$usuarioActual = usuario();
$contadores = PanelServicio::contadores();
?>
<!doctype html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo ?? 'Panel') ?> · Hotel Elegance</title>
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <meta name="he-url" content="<?= e(url('/__ruta__')) ?>">
    <link rel="icon" href="<?= asset('img/favicon.svg') ?>" type="image/svg+xml">
    <script>
        (function () {
            try {
                var t = localStorage.getItem('he-tema');
                if (!t) { t = matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'; }
                document.documentElement.setAttribute('data-bs-theme', t);
                if (localStorage.getItem('he-sidebar') === 'compacta') { document.documentElement.classList.add('sidebar-compacta'); }
            } catch (e) {}
        })();
    </script>
    <link rel="stylesheet" href="<?= vendor('bootstrap/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= vendor('bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/fuentes.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/elegance.css') ?>">
</head>
<body>
<a href="#contenido" class="visually-hidden-focusable position-absolute top-0 start-0 p-2 bg-white z-3">Saltar al contenido</a>
<div class="app">
    <aside class="sidebar" id="sidebar" aria-label="Menú principal">
        <a href="<?= url('/panel') ?>" class="sidebar-marca">
            <img src="<?= asset('img/logo-marca.svg') ?>" alt="">
            <span class="marca-texto"><strong>Elegance</strong><span>Hotel · La Paz</span></span>
        </a>
        <nav class="sidebar-nav">
            <?= $this->parcial('parciales/menu', ['contadores' => $contadores]) ?>
        </nav>
        <div class="sidebar-pie">
            <a class="sidebar-hotel" href="<?= url('/') ?>" target="_blank" rel="noopener" data-bs-toggle="tooltip" data-bs-placement="right" title="Abrir el sitio público">
                <i class="bi bi-globe2"></i>
                <div>Sitio público del hotel<br><span class="texto-tenue">hotelelegance.bo</span></div>
            </a>
        </div>
    </aside>
    <div class="sidebar-velo" data-accion="cerrar-sidebar"></div>

    <div class="app-principal">
        <header class="topbar">
            <button class="btn-icono d-lg-none" type="button" data-accion="abrir-sidebar" aria-label="Abrir menú"><i class="bi bi-list fs-5"></i></button>
            <button class="btn-icono d-none d-lg-inline-flex" type="button" data-accion="compactar-sidebar" aria-label="Contraer menú" data-bs-toggle="tooltip" title="Contraer menú"><i class="bi bi-layout-sidebar"></i></button>

            <form class="buscador" action="<?= url('/panel/buscar') ?>" method="get" role="search" autocomplete="off">
                <i class="bi bi-search"></i>
                <input type="search" name="q" id="buscador-global" placeholder="Buscar huésped, reserva o habitación…" aria-label="Búsqueda global" value="<?= e($_GET['q'] ?? '') ?>">
                <kbd>Ctrl K</kbd>
                <div class="buscador-resultados" id="buscador-resultados" role="listbox"></div>
            </form>

            <div class="ms-auto d-flex align-items-center gap-2">
                <?php if (puede('reservas.gestionar')): ?>
                    <a href="<?= url('/panel/reservas/nueva') ?>" class="btn btn-oro d-none d-md-inline-flex"><i class="bi bi-plus-lg"></i> Nueva reserva</a>
                <?php endif; ?>
                <div class="dropdown">
                    <button class="btn-icono" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notificaciones">
                        <i class="bi bi-bell"></i>
                        <?php if ($contadores['total_alertas'] > 0): ?><span class="punto-alerta"></span><?php endif; ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" style="width: 340px">
                        <h6 class="dropdown-header">Notificaciones</h6>
                        <?php foreach ($contadores['notificaciones'] as $n): ?>
                            <a class="dropdown-item align-items-start py-2" href="<?= e($n['url']) ?>">
                                <span class="icono-chip chip-<?= e($n['color']) ?>" style="width:34px;height:34px"><i class="bi <?= e($n['icono']) ?>" style="color:inherit"></i></span>
                                <span class="min-w-0"><strong class="d-block fs-sm"><?= e($n['titulo']) ?></strong><span class="texto-tenue fs-xs text-wrap d-block"><?= e($n['texto']) ?></span></span>
                            </a>
                        <?php endforeach; ?>
                        <?php if (!$contadores['notificaciones']): ?>
                            <div class="text-center py-4 texto-tenue fs-sm"><i class="bi bi-check2-circle fs-4 d-block mb-1 texto-oro"></i>Todo al día</div>
                        <?php endif; ?>
                    </div>
                </div>
                <button class="btn-icono" type="button" data-accion="cambiar-tema" aria-label="Cambiar tema" data-bs-toggle="tooltip" title="Modo claro / oscuro">
                    <i class="bi bi-moon-stars" data-icono-tema></i>
                </button>
                <div class="dropdown">
                    <button class="usuario-menu" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="avatar"><?= e(iniciales($usuarioActual['nombres'] . ' ' . $usuarioActual['apellidos'])) ?></span>
                        <span class="usuario-datos d-none d-sm-block">
                            <strong><?= e(explode(' ', $usuarioActual['nombres'])[0] . ' ' . explode(' ', $usuarioActual['apellidos'])[0]) ?></strong>
                            <span><?= e($usuarioActual['rol']) ?></span>
                        </span>
                        <i class="bi bi-chevron-down fs-xs texto-tenue d-none d-sm-inline"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" style="min-width: 240px">
                        <div class="px-3 py-2 mb-1">
                            <div class="fw-semibold"><?= e($usuarioActual['nombres'] . ' ' . $usuarioActual['apellidos']) ?></div>
                            <div class="texto-tenue fs-xs">@<?= e($usuarioActual['nombre_usuario']) ?> · <?= e($usuarioActual['cargo']) ?></div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="<?= url('/panel/perfil') ?>"><i class="bi bi-person-circle"></i> Mi perfil</a>
                        <a class="dropdown-item" href="<?= url('/panel/perfil') ?>#seguridad"><i class="bi bi-key"></i> Cambiar contraseña</a>
                        <a class="dropdown-item" href="<?= url('/') ?>" target="_blank" rel="noopener"><i class="bi bi-globe2"></i> Ver sitio público</a>
                        <div class="dropdown-divider"></div>
                        <form action="<?= url('/logout') ?>" method="post">
                            <?= csrf_campo() ?>
                            <button class="dropdown-item text-danger" type="submit"><i class="bi bi-box-arrow-right" style="color:inherit"></i> Cerrar sesión</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="app-contenido" id="contenido">
            <?= $contenido ?>
        </main>
        <footer class="px-4 pb-4 texto-tenue fs-xs d-flex flex-wrap justify-content-between gap-2 no-imprimir" style="max-width:1640px;margin:0 auto;width:100%">
            <span>© <?= date('Y') ?> Hotel Elegance · Av. Saavedra Díaz Romero N.º 1845, La Paz</span>
            <span>Sistema de Administración Web para el Seguimiento de Reservas · v1.0</span>
        </footer>
    </div>
</div>

<div class="toasts" id="toasts" aria-live="polite"></div>

<div class="modal fade" id="modal-confirmar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px">
        <div class="modal-content">
            <div class="modal-body text-center pt-4">
                <div class="icono-chip confirmar-icono chip-rojo" data-confirmar-icono><i class="bi bi-exclamation-triangle"></i></div>
                <h5 class="mb-2" data-confirmar-titulo>¿Confirmar acción?</h5>
                <p class="texto-suave mb-0 fs-sm" data-confirmar-texto></p>
            </div>
            <div class="modal-footer justify-content-center gap-2">
                <button type="button" class="btn btn-claro" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-peligro" data-confirmar-aceptar>Confirmar</button>
            </div>
        </div>
    </div>
</div>

<script>window.HE_MENSAJES = <?= json_encode(Sesion::mensajes(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
<script src="<?= vendor('bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= asset('js/panel.js') ?>"></script>
<?= $this->mostrar('scripts') ?>
</body>
</html>
