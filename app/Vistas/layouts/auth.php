<?php use App\Core\Sesion; ?>
<!doctype html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo ?? 'Acceso') ?> · Hotel Elegance</title>
    <link rel="icon" href="<?= asset('img/favicon.svg') ?>" type="image/svg+xml">
    <script>try{var t=localStorage.getItem('he-tema');if(t)document.documentElement.setAttribute('data-bs-theme',t);}catch(e){}</script>
    <link rel="stylesheet" href="<?= vendor('bootstrap/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= vendor('bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/fuentes.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/elegance.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/acceso.css') ?>">
</head>
<body class="acceso">
    <div class="acceso-grid">
        <section class="acceso-visual" style="background-image: url('<?= asset('img/hotel/lobby.jpg') ?>')">
            <div class="acceso-visual-velo"></div>
            <a href="<?= url('/') ?>" class="acceso-marca">
                <img src="<?= asset('img/logo-marca.svg') ?>" alt="">
                <span class="marca-texto"><strong>Hotel Elegance</strong><span>La Paz · Bolivia</span></span>
            </a>
            <div class="acceso-cita">
                <span class="sobretitulo">Sistema de Administración Web</span>
                <h2>Cada reserva, en su lugar.<br><em>Cada huésped, en casa.</em></h2>
                <p>Seguimiento de reservas, huéspedes, habitaciones y decisiones de sobreventa en una sola plataforma.</p>
                <div class="acceso-datos">
                    <div><strong>24</strong><span>habitaciones</span></div>
                    <div><strong>7</strong><span>módulos</span></div>
                    <div><strong>24/7</strong><span>recepción</span></div>
                </div>
            </div>
        </section>
        <main class="acceso-panel">
            <div class="acceso-caja">
                <?= $contenido ?>
            </div>
            <p class="acceso-pie">© <?= date('Y') ?> Hotel Elegance · Av. Saavedra Díaz Romero N.º 1845, Miraflores</p>
        </main>
    </div>
    <div class="toasts" id="toasts" aria-live="polite"></div>
    <script>window.HE_MENSAJES = <?= json_encode(Sesion::mensajes(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
    <script src="<?= vendor('bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= asset('js/panel.js') ?>"></script>
    <?= $this->mostrar('scripts') ?>
</body>
</html>
