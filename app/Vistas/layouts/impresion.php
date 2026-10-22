<!doctype html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo ?? 'Documento') ?> · Hotel Elegance</title>
    <link rel="icon" href="<?= asset('img/favicon.svg') ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= vendor('bootstrap/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= vendor('bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/fuentes.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/elegance.css') ?>">
    <style>
        body { background: #EDEAE3; }
        .barra-impresion { position: sticky; top: 0; z-index: 5; background: rgba(15, 27, 45, .96); color: #fff; padding: .75rem 1.25rem; display: flex; gap: .75rem; align-items: center; justify-content: space-between; }
        @media print { body { background: #fff; } .documento { padding: 0 !important; border-radius: 0; } }
    </style>
</head>
<body>
    <div class="barra-impresion no-imprimir">
        <span class="fs-sm"><i class="bi bi-file-earmark-text text-warning"></i> <?= e($titulo ?? 'Documento') ?> · use «Imprimir» y elija «Guardar como PDF» para obtener el archivo</span>
        <span class="d-flex gap-2">
            <a class="btn btn-sm btn-claro" href="javascript:history.length > 1 ? history.back() : window.close()"><i class="bi bi-arrow-left"></i> Volver</a>
            <button class="btn btn-sm btn-oro" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir / PDF</button>
        </span>
    </div>
    <div class="py-4 px-2">
        <?= $contenido ?>
    </div>
</body>
</html>
