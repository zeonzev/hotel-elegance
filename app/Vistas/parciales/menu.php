<?php
/**
 * Menú lateral construido a partir de la matriz de permisos: cada rol sólo ve
 * las opciones que puede usar (la restricción real está en el servidor).
 */
$grupos = [
    'Operación' => [
        ['/panel', 'bi-grid-1x2', 'Dashboard', 'dashboard.ver', null, true],
        ['/panel/recepcion', 'bi-bell', 'Recepción del día', 'recepcion.operar', $contadores['llegadas_pendientes'] ?: null],
        ['/panel/disponibilidad', 'bi-calendar3-week', 'Disponibilidad', 'reservas.ver'],
        ['/panel/calendario', 'bi-calendar-month', 'Calendario', 'reservas.ver'],
    ],
    'Reservas' => [
        ['/panel/reservas', 'bi-journal-bookmark', 'Reservas', 'reservas.ver'],
        ['/panel/cobros', 'bi-cash-coin', 'Cobros pendientes', 'recepcion.operar'],
        ['/panel/solicitudes', 'bi-envelope-paper', 'Solicitudes web', 'solicitudes.gestionar', $contadores['solicitudes'] ?: null],
        ['/panel/huespedes', 'bi-people', 'Huéspedes', 'huespedes.ver'],
    ],
    'Hotel' => [
        ['/panel/habitaciones', 'bi-door-closed', 'Habitaciones', 'habitaciones.ver'],
        ['/panel/categorias', 'bi-collection', 'Categorías', 'habitaciones.gestionar'],
    ],
    'Inteligencia' => [
        ['/panel/sobreventa', 'bi-shield-check', 'Política de sobreventa', 'sobreventa.ver', $contadores['alertas_sobreventa'] ? ['valor' => $contadores['alertas_sobreventa'], 'alerta' => true] : null],
        ['/panel/tarifas', 'bi-tags', 'Tarifas y demanda', 'tarifas.ver'],
        ['/panel/reportes', 'bi-bar-chart-line', 'Reportes', 'reportes.ver'],
    ],
    'Administración' => [
        ['/panel/personal', 'bi-person-vcard', 'Personal', 'personal.gestionar'],
        ['/panel/usuarios', 'bi-shield-lock', 'Usuarios y roles', 'usuarios.gestionar'],
        ['/panel/bitacora', 'bi-clock-history', 'Bitácora', 'bitacora.ver'],
        ['/panel/configuracion', 'bi-sliders', 'Configuración', 'configuracion.gestionar'],
    ],
];
foreach ($grupos as $titulo => $items):
    $visibles = array_filter($items, fn ($i) => puede($i[3]));
    if (!$visibles) {
        continue;
    }
?>
    <div class="nav-grupo">
        <div class="nav-titulo"><?= e($titulo) ?></div>
        <?php foreach ($visibles as $i):
            $contador = $i[4] ?? null;
            $exacto = $i[5] ?? false;
        ?>
            <a class="nav-enlace<?= activo($i[0], $exacto) ?>" href="<?= url($i[0]) ?>" data-bs-toggle="tooltip" data-bs-placement="right" data-solo-compacta title="<?= e($i[2]) ?>">
                <i class="bi <?= $i[1] ?>"></i><span><?= e($i[2]) ?></span>
                <?php if (is_array($contador)): ?>
                    <span class="nav-contador alerta"><?= (int) $contador['valor'] ?></span>
                <?php elseif ($contador): ?>
                    <span class="nav-contador"><?= (int) $contador ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>
