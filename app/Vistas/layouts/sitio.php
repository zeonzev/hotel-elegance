<?php
/** @var \App\Core\Vista $this */
use App\Core\Sesion;

$ruta = \App\Core\Solicitud::rutaActual();
$transparente = true; // todas las páginas públicas empiezan con una cabecera oscura
$menu = [['/', 'Inicio'], ['/habitaciones', 'Habitaciones'], ['/#servicios', 'Servicios'], ['/#la-paz', 'La Paz'], ['/contacto', 'Contacto']];
?>
<!doctype html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo ?? 'Hotel Elegance') ?> · Hotel Elegance La Paz</title>
    <meta name="description" content="Hotel Elegance: hotel boutique en Miraflores, La Paz – Bolivia. Habitaciones Simple, Doble, Matrimonial y Suite con vista al Illimani.">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <meta name="he-url" content="<?= e(url('/__ruta__')) ?>">
    <link rel="icon" href="<?= asset('img/favicon.svg') ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= vendor('bootstrap/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= vendor('bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/fuentes.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/elegance.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/sitio.css') ?>">
</head>
<body class="sitio">
    <header class="sitio-nav<?= $transparente ? ' transparente' : '' ?>" id="sitio-nav">
        <div class="contenedor d-flex align-items-center gap-4">
            <a href="<?= url('/') ?>" class="sitio-marca">
                <img src="<?= asset('img/logo-marca.svg') ?>" alt="">
                <span><strong>Hotel Elegance</strong><small>La Paz · Bolivia</small></span>
            </a>
            <nav class="sitio-menu ms-auto" id="sitio-menu">
                <?php foreach ($menu as [$enlace, $texto]): ?>
                    <a href="<?= url($enlace) ?>" class="<?= $ruta === $enlace ? 'activo' : '' ?>"><?= e($texto) ?></a>
                <?php endforeach; ?>
                <a href="<?= url('/login') ?>" class="d-lg-none">Acceso del personal</a>
            </nav>
            <a href="<?= url('/reservar') ?>" class="btn btn-oro d-none d-sm-inline-flex">Reservar</a>
            <button class="sitio-hamburguesa d-lg-none" type="button" aria-label="Abrir menú" onclick="document.getElementById('sitio-menu').classList.toggle('abierto')"><i class="bi bi-list"></i></button>
        </div>
    </header>

    <main><?= $contenido ?></main>

    <footer class="sitio-pie">
        <div class="contenedor">
            <div class="row g-5">
                <div class="col-lg-4">
                    <a href="<?= url('/') ?>" class="sitio-marca mb-3"><img src="<?= asset('img/logo-marca.svg') ?>" alt=""><span><strong>Hotel Elegance</strong><small>La Paz · Bolivia</small></span></a>
                    <p class="mt-3">Hospitalidad boutique en Miraflores, a minutos del centro histórico y con vista al Illimani.</p>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <h4>Contacto</h4>
                    <ul class="lista-limpia">
                        <li><i class="bi bi-geo-alt"></i> <?= e(ajuste('hotel_direccion')) ?>, <?= e(ajuste('hotel_ciudad')) ?></li>
                        <li><i class="bi bi-telephone"></i> <?= e(ajuste('hotel_telefono')) ?></li>
                        <li><i class="bi bi-whatsapp"></i> <?= e(ajuste('hotel_whatsapp')) ?></li>
                        <li><i class="bi bi-envelope"></i> <?= e(ajuste('hotel_correo')) ?></li>
                    </ul>
                </div>
                <div class="col-sm-6 col-lg-2">
                    <h4>Explorar</h4>
                    <ul class="lista-limpia"><?php foreach ($menu as [$enlace, $texto]): ?><li><a href="<?= url($enlace) ?>"><?= e($texto) ?></a></li><?php endforeach; ?></ul>
                </div>
                <div class="col-lg-3">
                    <h4>Horarios</h4>
                    <ul class="lista-limpia">
                        <li>Check-in desde las <?= e(ajuste('hora_checkin', '14:00')) ?></li>
                        <li>Check-out hasta las <?= e(ajuste('hora_checkout', '12:00')) ?></li>
                        <li>Recepción las 24 horas</li>
                    </ul>
                    <a href="<?= url('/login') ?>" class="btn btn-sm btn-claro mt-2"><i class="bi bi-person-lock"></i> Acceso del personal</a>
                </div>
            </div>
            <div class="sitio-pie-legal">
                <span>© <?= date('Y') ?> <?= e(ajuste('hotel_razon_social', 'Hotel Elegance S.R.L.')) ?> · NIT <?= e(ajuste('hotel_nit')) ?></span>
                <span>Proyecto académico UNIFRANZ · Fotografías: Unsplash y René Denis (Wikimedia Commons, CC BY 3.0)</span>
            </div>
        </div>
    </footer>

    <div class="toasts" id="toasts" aria-live="polite"></div>
    <script>window.HE_MENSAJES = <?= json_encode(Sesion::mensajes(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
    <script src="<?= vendor('bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= asset('js/panel.js') ?>"></script>
    <script>
    (function () {
        var nav = document.getElementById('sitio-nav');
        var actualizar = function () { nav.classList.toggle('solida', window.scrollY > 40); };
        window.addEventListener('scroll', actualizar, { passive: true });
        actualizar();
        var observador = 'IntersectionObserver' in window ? new IntersectionObserver(function (entradas) {
            entradas.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('visible'); observador.unobserve(en.target); } });
        }, { threshold: 0.12 }) : null;
        var revelables = document.querySelectorAll('.revelar');
        revelables.forEach(function (el) { observador ? observador.observe(el) : el.classList.add('visible'); });
        // Respaldo: nada queda oculto si el navegador no reporta el desplazamiento (impresión, capturas)
        setTimeout(function () { revelables.forEach(function (el) { el.classList.add('visible'); }); }, 2500);
    })();
    </script>
    <?= $this->mostrar('scripts') ?>
</body>
</html>
