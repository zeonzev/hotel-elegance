<?php
/** @var \App\Core\Vista $this */
$acciones = '';
if (puede('habitaciones.estado')) {
    $acciones .= '<button class="btn btn-claro" type="button" data-bs-toggle="modal" data-bs-target="#modal-estado" data-id="' . $h['id_habitacion'] . '" data-numero="' . e($h['numero']) . '" data-estado="' . $h['estado'] . '"><i class="bi bi-arrow-repeat"></i> Cambiar estado</button>';
}
if (puede('habitaciones.gestionar')) {
    $acciones .= '<a class="btn btn-claro" href="' . url('/panel/habitaciones/' . $h['id_habitacion'] . '/editar') . '"><i class="bi bi-pencil"></i> Editar</a>';
}
echo $this->parcial('parciales/encabezado', [
    'titulo' => 'Habitación ' . $h['numero'],
    'cu' => 'CU-08 · CU-10',
    'descripcion' => $h['categoria'] . ' · piso ' . $h['piso'] . ($h['descripcion'] ? ' · ' . $h['descripcion'] : ''),
    'migas' => [['Habitaciones', '/panel/habitaciones'], [$h['numero'], null]],
    'acciones' => $acciones,
]);
?>
<div class="row g-4-5">
    <div class="col-xl-4">
        <div class="tarjeta mb-4 overflow-hidden">
            <img src="<?= asset('img/habitaciones/' . $h['imagen']) ?>" alt="Habitación <?= e($h['categoria']) ?>" class="w-100" style="height:200px;object-fit:cover">
            <div class="tarjeta-cuerpo">
                <div class="d-flex justify-content-between align-items-center mb-3"><span class="serif fs-1 fw-semibold lh-1"><?= e($h['numero']) ?></span><?= insignia($h['estado']) ?></div>
                <dl class="datos">
                    <dt>Categoría</dt><dd><?= e($h['categoria']) ?></dd>
                    <dt>Capacidad</dt><dd><?= (int) $h['capacidad'] ?> persona(s)</dd>
                    <dt>Camas</dt><dd><?= e($h['camas']) ?></dd>
                    <dt>Superficie</dt><dd><?= (int) $h['superficie_m2'] ?> m²</dd>
                    <dt>Tarifa base</dt><dd><?= e(bs($h['tarifa_base'])) ?> / noche</dd>
                </dl>
            </div>
        </div>
        <div class="row g-3">
            <?php foreach ([['Estadías (12 m)', numero($estadisticas['estadias'])], ['Noches vendidas', numero($estadisticas['noches'])], ['Ingresos (12 m)', bs($estadisticas['ingresos'], 0)], ['Ocupación', numero($estadisticas['noches'] / 365 * 100, 0) . ' %']] as [$t, $v]): ?>
                <div class="col-6"><div class="tarjeta kpi"><div class="kpi-titulo"><?= $t ?></div><div class="fs-5 fw-semibold tabular mt-1"><?= e($v) ?></div></div></div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="col-xl-8">
        <?php if ($alojado): ?>
            <div class="aviso aviso-azul mb-4"><i class="bi bi-person-fill"></i><div class="flex-grow-1"><strong>Ocupada por <?= e($alojado['huesped']) ?></strong><span class="fs-sm">Reserva <?= e($alojado['codigo']) ?> · salida prevista el <?= e(fecha($alojado['fecha_salida'], 'larga')) ?></span></div>
                <?php if (puede('recepcion.operar')): ?><a class="btn btn-sm btn-primary" href="<?= url('/panel/reservas/' . $alojado['id_reserva'] . '/checkout') ?>">Registrar check-out</a><?php endif; ?></div>
        <?php endif; ?>
        <div class="tarjeta mb-4">
            <div class="tarjeta-encabezado"><div><h2>Reservas activas y próximas</h2></div></div>
            <?php if ($reservas): ?>
                <div class="tabla-contenedor"><table class="tabla tabla-compacta"><tbody>
                    <?php foreach ($reservas as $r): ?>
                        <tr class="fila-enlace" data-href="<?= url('/panel/reservas/' . $r['id_reserva']) ?>"><td><span class="etiqueta-codigo"><?= e($r['codigo']) ?></span></td><td class="fw-medio"><?= e($r['huesped']) ?></td><td><?= e(fecha($r['fecha_ingreso'])) ?> → <?= e(fecha($r['fecha_salida'])) ?></td><td class="text-end"><?= insignia($r['estado']) ?></td></tr>
                    <?php endforeach; ?>
                </tbody></table></div>
            <?php else: ?><div class="tarjeta-cuerpo texto-suave fs-sm">Sin reservas activas para esta habitación.</div><?php endif; ?>
        </div>
        <div class="tarjeta">
            <div class="tarjeta-encabezado"><div><h2>Historial de estados</h2><div class="subtitulo">Tabla <code>historial_estado_habitacion</code></div></div></div>
            <div class="tarjeta-cuerpo">
                <?php if ($historial): ?>
                    <ul class="linea-tiempo">
                        <?php foreach ($historial as $x): [$etq, $color, $ico] = estado_info($x['estado_nuevo']); ?>
                            <li><span class="marca" style="color: var(--he-<?= $color ?>)"><i class="bi <?= $ico ?>"></i></span>
                                <strong><?= e(estado_info($x['estado_anterior'])[0]) ?> → <?= e($etq) ?></strong>
                                <small><?= e($x['motivo'] ?: 'Sin motivo') ?> · @<?= e($x['nombre_usuario'] ?? 'sistema') ?> · <?= e(fecha($x['fecha'], 'hora')) ?></small></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?><p class="texto-suave fs-sm mb-0">Todavía no hay cambios de estado registrados.</p><?php endif; ?>
            </div>
        </div>
        <?php if (puede('habitaciones.gestionar')): ?>
            <form method="post" action="<?= url('/panel/habitaciones/' . $h['id_habitacion'] . '/eliminar') ?>" class="mt-4 text-end" data-confirmar="¿Eliminar la habitación <?= e($h['numero']) ?>?" data-confirmar-texto="Si tiene reservas asociadas, el sistema no lo permitirá y sugerirá ponerla en mantenimiento." data-confirmar-boton="Eliminar">
                <?= csrf_campo() ?><button class="btn btn-peligro-suave btn-sm" type="submit"><i class="bi bi-trash"></i> Eliminar habitación</button>
            </form>
        <?php endif; ?>
    </div>
</div>
<?= $this->parcial('habitaciones/modal-estado') ?>
