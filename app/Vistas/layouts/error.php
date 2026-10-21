<!doctype html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title><?= e($titulo ?? 'Error') ?> · Hotel Elegance</title>
    <link rel="icon" href="<?= asset('img/favicon.svg') ?>" type="image/svg+xml">
    <script>try{var t=localStorage.getItem('he-tema');if(t)document.documentElement.setAttribute('data-bs-theme',t);}catch(e){}</script>
    <link rel="stylesheet" href="<?= vendor('bootstrap/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= vendor('bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/fuentes.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/elegance.css') ?>">
</head>
<body>
    <?= $contenido ?>
</body>
</html>
