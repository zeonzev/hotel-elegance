<?php
/**
 * Página de avance: sección perteneciente a un sprint que aún está en desarrollo.
 */
$sprints = [
    '/panel/reservas'      => [4, 'Gestión de Reservas', 'CU-11 a CU-16'],
    '/panel/recepcion'     => [4, 'Gestión de Reservas · Check-in y check-out', 'CU-15 · CU-16'],
    '/panel/disponibilidad' => [4, 'Gestión de Reservas · Consultar disponibilidad', 'CU-12'],
    '/panel/solicitudes'   => [4, 'Gestión de Reservas · Solicitudes del sitio web', 'CU-11'],
    '/panel/sobreventa'    => [5, 'Política de Sobreventa', 'CU-17 · CU-18 · CU-19'],
    '/panel/api/sobreventa' => [5, 'Política de Sobreventa', 'CU-19'],
    '/panel/tarifas'       => [6, 'Estimación de Demanda y Tarifas', 'CU-20 · CU-21'],
    '/panel/reportes'      => [7, 'Reportes', 'CU-22 · CU-23 · CU-24'],
    '/instalar'            => [0, 'Instalador web', 'Extra'],
];
$info = [0, 'Sitio público del hotel', 'Extra'];
foreach ($sprints as $prefijo => $datos) {
    if ($ruta === $prefijo || str_starts_with($ruta, $prefijo . '/')) {
        $info = $datos;
        break;
    }
}
[$sprint, $modulo, $cu] = $info;
$avance = [1 => 'Sesiones', 2 => 'Gestión de Huéspedes', 3 => 'Gestión de Habitaciones', 4 => 'Gestión de Reservas', 5 => 'Política de Sobreventa', 6 => 'Estimación de Demanda y Tarifas', 7 => 'Reportes'];
?>
<div class="<?= $conSesion ? '' : 'error-pantalla' ?>">
    <div class="tarjeta mx-auto" style="max-width: 760px">
        <div class="tarjeta-cuerpo p-4 p-md-5">
            <div class="d-flex align-items-center gap-3 mb-4">
                <span class="icono-chip chip-oro" style="width:56px;height:56px;font-size:1.5rem"><i class="bi bi-cone-striped"></i></span>
                <div>
                    <div class="sobretitulo"><?= $sprint ? 'Sprint ' . $sprint . ' · ' : '' ?><?= e($cu) ?></div>
                    <h1 class="h2 mb-0"><?= e($modulo) ?></h1>
                </div>
            </div>
            <p class="texto-suave">Esta sección forma parte del Product Backlog y se encuentra <strong>en desarrollo</strong>. El modelo de datos, los disparadores y la lógica de negocio del módulo ya están implementados; la interfaz se entrega en el sprint correspondiente.</p>
            <h2 class="h6 fw-semibold mt-4 mb-3" style="font-family:var(--he-sans)">Avance del proyecto por sprint</h2>
            <ul class="lista-limpia">
                <?php foreach ($avance as $n => $nombre): $estado = 'listo'; ?>
                    <li class="d-flex align-items-center gap-3 py-2 border-bottom">
                        <span class="icono-chip <?= $estado === 'listo' ? 'chip-verde' : ($estado === 'parcial' ? 'chip-ambar' : 'chip-gris') ?>" style="width:30px;height:30px;font-size:.85rem"><i class="bi <?= $estado === 'listo' ? 'bi-check-lg' : ($estado === 'parcial' ? 'bi-hourglass-split' : 'bi-circle') ?>"></i></span>
                        <span class="flex-grow-1">Sprint <?= $n ?> · <?= e($nombre) ?></span>
                        <span class="insignia <?= $estado === 'listo' ? 'insignia-verde' : ($estado === 'parcial' ? 'insignia-ambar' : 'insignia-gris') ?>"><?= $estado === 'listo' ? 'Completado' : ($estado === 'parcial' ? 'En curso' : 'Planificado') ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="d-flex flex-wrap gap-2 mt-4">
                <a href="javascript:history.back()" class="btn btn-claro"><i class="bi bi-arrow-left"></i> Volver</a>
                <a href="<?= url($conSesion ? '/panel' : '/login') ?>" class="btn btn-primary"><i class="bi bi-grid-1x2"></i> <?= $conSesion ? 'Ir al dashboard' : 'Iniciar sesión' ?></a>
            </div>
        </div>
    </div>
</div>
