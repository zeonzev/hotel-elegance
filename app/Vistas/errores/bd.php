<?php
$explicacion = match ($codigoBd ?? 0) {
    1049 => 'La base de datos «' . \App\Core\Configuracion::obtener('bd.nombre') . '» todavía no existe.',
    1146 => 'La base de datos existe pero está incompleta (faltan tablas).',
    2002, 2006 => 'No se pudo conectar con MySQL. ¿Está iniciado en el panel de XAMPP?',
    1045 => 'MySQL rechazó el usuario o la contraseña configurados.',
    default => 'No se pudo acceder a la base de datos.',
};
?>
<div class="error-pantalla">
    <div class="tarjeta" style="max-width: 640px; width: 100%">
        <div class="tarjeta-cuerpo p-4 p-md-5">
            <div class="d-flex align-items-center gap-3 mb-4">
                <img src="<?= asset('img/logo-marca.svg') ?>" alt="" width="48" height="48">
                <div>
                    <div class="sobretitulo">Hotel Elegance</div>
                    <h1 class="h3 mb-0">Falta un paso para empezar</h1>
                </div>
            </div>
            <div class="aviso aviso-oro mb-4">
                <i class="bi bi-database-exclamation"></i>
                <div><strong><?= e($explicacion) ?></strong><span class="fs-sm texto-suave">Código MySQL: <?= (int) ($codigoBd ?? 0) ?></span></div>
            </div>
            <h2 class="h6 fw-semibold">Elija una de estas opciones:</h2>
            <ol class="fs-sm texto-suave ps-3 mb-4" style="line-height:1.8">
                <li>Abra el <strong>Panel de control de XAMPP</strong> y presione <em>Start</em> en <strong>Apache</strong> y <strong>MySQL</strong>.</li>
                <li>Use el <strong>instalador automático</strong> (crea la base e importa los datos de prueba con un clic).</li>
                <li>O importe <code>database/hotel_elegance.sql</code> en phpMyAdmin (pestaña <em>Importar</em>).</li>
                <li>Si su MySQL usa otro usuario, contraseña o puerto, copie <code>.env.example</code> como <code>.env</code> y ajústelo.</li>
            </ol>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-oro btn-lg" href="<?= url('/instalar') ?>"><i class="bi bi-magic"></i> Abrir el instalador</a>
                <a class="btn btn-claro btn-lg" href="/phpmyadmin/" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i> phpMyAdmin</a>
            </div>
        </div>
    </div>
</div>
