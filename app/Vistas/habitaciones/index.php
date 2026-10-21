<?php
/** @var \App\Core\Vista $this */
$vista = $filtros['vista'] === 'lista' ? 'lista' : 'plano';
$acciones = '';
if (puede('habitaciones.gestionar')) {
    $acciones = '<a class="btn btn-claro" href="' . url('/panel/categorias') . '"><i class="bi bi-collection"></i> Categorías</a><a class="btn btn-primary" href="' . url('/panel/habitaciones/nueva') . '"><i class="bi bi-plus-lg"></i> Nueva habitación</a>';
}
echo $this->parcial('parciales/encabezado', [
    'titulo' => 'Habitaciones',
    'cu' => 'CU-08 · CU-10',
    'descripcion' => 'Plano del hotel por piso con el estado actual de cada habitación. Cada cambio de estado queda registrado en su historial.',
    'migas' => [['Hotel', null], ['Habitaciones', null]],
    'acciones' => $acciones,
]);
$colores = ['DISPONIBLE' => 'verde', 'OCUPADA' => 'azul', 'LIMPIEZA' => 'ambar', 'MANTENIMIENTO' => 'rojo'];
$iconos = ['DISPONIBLE' => 'bi-check-circle', 'OCUPADA' => 'bi-person-fill', 'LIMPIEZA' => 'bi-stars', 'MANTENIMIENTO' => 'bi-tools'];
$total = array_sum($conteo);
?>
<div class="row g-4-5 mb-4">
    <?php foreach ($conteo as $estado => $n): [$etq] = estado_info($estado); ?>
        <div class="col-6 col-xl-3">
            <a href="<?= url('/panel/habitaciones', array_filter(['estado' => $filtros['estado'] === $estado ? null : $estado, 'vista' => $filtros['vista']])) ?>" class="tarjeta kpi d-block tarjeta-interactiva text-reset<?= $filtros['estado'] === $estado ? ' border-2' : '' ?>" style="<?= $filtros['estado'] === $estado ? 'border-color: var(--he-oro-500)' : '' ?>">
                <div class="kpi-cabecera"><span class="kpi-titulo"><?= e($etq) ?></span><span class="icono-chip chip-<?= $colores[$estado] ?>"><i class="bi <?= $iconos[$estado] ?>"></i></span></div>
                <div class="kpi-valor"><?= $n ?> <small>/ <?= $total ?></small></div>
                <div class="barra-progreso <?= $colores[$estado] === 'ambar' ? '' : $colores[$estado] ?> mt-3"><span data-valor="<?= $total ? $n / $total * 100 : 0 ?>" style="width:0"></span></div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<div class="tarjeta">
    <form class="filtros" method="get">
        <div class="segmentos">
            <a class="<?= $vista === 'plano' ? 'active' : '' ?>" href="<?= url('/panel/habitaciones', array_filter(['estado' => $filtros['estado'], 'categoria' => $filtros['categoria'], 'piso' => $filtros['piso']])) ?>"><i class="bi bi-grid-3x3-gap"></i> Plano</a>
            <a class="<?= $vista === 'lista' ? 'active' : '' ?>" href="<?= url('/panel/habitaciones', array_filter(['vista' => 'lista', 'estado' => $filtros['estado'], 'categoria' => $filtros['categoria'], 'piso' => $filtros['piso']])) ?>"><i class="bi bi-list-ul"></i> Lista</a>
        </div>
        <input type="hidden" name="vista" value="<?= e($filtros['vista']) ?>">
        <select class="form-select w-auto" name="categoria" data-autoenviar><option value="">Todas las categorías</option><?php foreach ($categorias as $c): ?><option value="<?= $c['id_categoria'] ?>"<?= seleccionado($filtros['categoria'], $c['id_categoria']) ?>><?= e($c['nombre']) ?></option><?php endforeach; ?></select>
        <select class="form-select w-auto" name="piso" data-autoenviar><option value="">Todos los pisos</option><?php foreach ($listaPisos as $p): ?><option value="<?= $p ?>"<?= seleccionado($filtros['piso'], $p) ?>>Piso <?= $p ?></option><?php endforeach; ?></select>
        <select class="form-select w-auto" name="estado" data-autoenviar><option value="">Todos los estados</option><?php foreach ($conteo as $estado => $n): ?><option value="<?= $estado ?>"<?= seleccionado($filtros['estado'], $estado) ?>><?= estado_info($estado)[0] ?></option><?php endforeach; ?></select>
        <?php if ($filtros['estado'] || $filtros['categoria'] || $filtros['piso']): ?><a class="btn btn-fantasma" href="<?= url('/panel/habitaciones', array_filter(['vista' => $filtros['vista']])) ?>">Limpiar</a><?php endif; ?>
        <div class="leyenda ms-auto d-none d-lg-flex">
            <span><i style="background:var(--he-verde)"></i>Disponible</span><span><i style="background:var(--he-azul)"></i>Ocupada</span><span><i style="background:var(--he-ambar)"></i>Limpieza</span><span><i style="background:var(--he-rojo)"></i>Mantenimiento</span>
        </div>
    </form>

    <?php if (!$habitaciones): ?>
        <?= $this->parcial('parciales/vacio', ['icono' => 'bi-door-closed', 'titulo' => 'Sin habitaciones', 'texto' => 'No hay habitaciones con esos filtros.']) ?>
    <?php elseif ($vista === 'plano'): ?>
        <?php foreach ($pisos as $piso => $lista): ?>
            <div class="piso">
                <div class="piso-etiqueta"><strong><?= $piso ?>.º</strong><span>Piso · <?= count($lista) ?> hab.</span></div>
                <div class="piso-habitaciones">
                    <?php foreach ($lista as $h): $ocupante = $h['ocupante'] ? explode('|', $h['ocupante']) : null; ?>
                        <div class="position-relative">
                            <a class="hab hab-<?= $h['estado'] ?>" href="<?= url('/panel/habitaciones/' . $h['id_habitacion']) ?>">
                                <i class="bi <?= $iconos[$h['estado']] ?> hab-icono"></i>
                                <div class="hab-numero"><?= e($h['numero']) ?></div>
                                <div class="hab-categoria"><?= e($h['categoria']) ?> · <?= (int) $h['capacidad'] ?> pers.</div>
                                <span class="hab-estado"><?= e(estado_info($h['estado'])[0]) ?></span>
                                <div class="hab-huesped">
                                    <?php if ($ocupante): ?><i class="bi bi-person"></i> <?= e($ocupante[0]) ?> · sale <?= e(fecha($ocupante[1], 'dia')) ?>
                                    <?php elseif ($h['proxima_llegada']): ?><i class="bi bi-calendar-event"></i> Llegada <?= $h['proxima_llegada'] === hoy() ? '<strong>hoy</strong>' : e(fecha($h['proxima_llegada'], 'dia')) ?>
                                    <?php else: ?><span class="texto-tenue">Sin reservas próximas</span><?php endif; ?>
                                </div>
                            </a>
                            <?php if (puede('habitaciones.estado')): ?>
                                <button type="button" class="btn btn-fantasma btn-sm position-absolute bottom-0 end-0 m-1 px-2" data-bs-toggle="modal" data-bs-target="#modal-estado" data-id="<?= $h['id_habitacion'] ?>" data-numero="<?= e($h['numero']) ?>" data-estado="<?= $h['estado'] ?>" aria-label="Cambiar estado" title="Cambiar estado"><i class="bi bi-arrow-repeat"></i></button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="tabla-contenedor">
            <table class="tabla">
                <thead><tr><th>Habitación</th><th>Piso</th><th>Categoría</th><th class="text-end">Tarifa base</th><th>Ocupante / próxima llegada</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                <tbody>
                <?php foreach ($habitaciones as $h): $ocupante = $h['ocupante'] ? explode('|', $h['ocupante']) : null; ?>
                    <tr>
                        <td><a class="serif fs-4 fw-semibold text-reset" href="<?= url('/panel/habitaciones/' . $h['id_habitacion']) ?>"><?= e($h['numero']) ?></a><div class="celda-secundaria"><?= e($h['descripcion']) ?></div></td>
                        <td><?= (int) $h['piso'] ?>.º</td>
                        <td><?= e($h['categoria']) ?><div class="celda-secundaria"><?= (int) $h['capacidad'] ?> persona(s)</div></td>
                        <td class="text-end tabular"><?= e(bs($h['tarifa_base'], 0)) ?></td>
                        <td><?= $ocupante ? e($ocupante[0]) . '<div class="celda-secundaria">Sale ' . e(fecha($ocupante[1])) . '</div>' : ($h['proxima_llegada'] ? 'Llegada ' . e(fecha($h['proxima_llegada'])) : '<span class="texto-tenue">—</span>') ?></td>
                        <td><?= insignia($h['estado']) ?></td>
                        <td class="acciones">
                            <?php if (puede('habitaciones.estado')): ?><button type="button" class="btn btn-claro btn-accion" data-bs-toggle="modal" data-bs-target="#modal-estado" data-id="<?= $h['id_habitacion'] ?>" data-numero="<?= e($h['numero']) ?>" data-estado="<?= $h['estado'] ?>" title="Cambiar estado"><i class="bi bi-arrow-repeat"></i></button><?php endif; ?>
                            <?php if (puede('habitaciones.gestionar')): ?><a class="btn btn-claro btn-accion" href="<?= url('/panel/habitaciones/' . $h['id_habitacion'] . '/editar') ?>" title="Editar"><i class="bi bi-pencil"></i></a><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?= $this->parcial('habitaciones/modal-estado') ?>
