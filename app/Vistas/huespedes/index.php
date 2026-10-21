<?php
/** @var \App\Core\Vista $this */
echo $this->parcial('parciales/encabezado', [
    'titulo' => 'Huéspedes',
    'cu' => 'CU-05 · CU-06 · CU-07',
    'descripcion' => 'Registro centralizado de huéspedes con su documento, procedencia, contacto e historial de estadías.',
    'migas' => [['Reservas', null], ['Huéspedes', null]],
    'acciones' => puede('huespedes.gestionar') ? '<a class="btn btn-primary" href="' . url('/panel/huespedes/nuevo') . '"><i class="bi bi-person-plus"></i> Nuevo huésped</a>' : '',
]);
?>
<div class="row g-4-5 mb-4">
    <?php foreach ([['Huéspedes registrados', $resumen['total'], 'bi-people', 'chip-noche'], ['Nacionales', $resumen['nacionales'], 'bi-geo-alt', 'chip-verde'], ['Extranjeros', $resumen['total'] - $resumen['nacionales'], 'bi-airplane', 'chip-azul'], ['Alojados ahora', $resumen['alojados'], 'bi-door-open', 'chip-oro']] as [$t, $v, $i, $c]): ?>
        <div class="col-6 col-xl-3"><div class="tarjeta kpi d-flex align-items-center gap-3"><span class="icono-chip <?= $c ?>"><i class="bi <?= $i ?>"></i></span><div><div class="kpi-titulo"><?= $t ?></div><div class="fs-4 fw-semibold tabular"><?= numero($v) ?></div></div></div></div>
    <?php endforeach; ?>
</div>

<div class="tarjeta">
    <form class="filtros" method="get">
        <div class="con-icono buscar-tabla"><i class="bi bi-search"></i><input class="form-control" type="search" name="q" value="<?= e($filtros['q']) ?>" placeholder="Nombre, documento, correo o teléfono" autofocus></div>
        <div class="segmentos">
            <?php foreach (['' => 'Todos', 'nacional' => 'Nacionales', 'extranjero' => 'Extranjeros'] as $val => $txt): ?>
                <a class="<?= ($filtros['origen'] ?? '') === $val ? 'active' : '' ?>" href="<?= url('/panel/huespedes', array_filter(['origen' => $val, 'q' => $filtros['q'], 'orden' => $filtros['orden']])) ?>"><?= $txt ?></a>
            <?php endforeach; ?>
        </div>
        <select class="form-select w-auto" name="nacionalidad" data-autoenviar>
            <option value="">Nacionalidad</option>
            <?php foreach ($nacionalidades as $n): ?><option<?= seleccionado($filtros['nacionalidad'], $n) ?>><?= e($n) ?></option><?php endforeach; ?>
        </select>
        <select class="form-select w-auto" name="orden" data-autoenviar>
            <?php foreach (['' => 'Orden alfabético', 'estadias' => 'Más estadías', 'reciente' => 'Visita más reciente', 'gasto' => 'Mayor consumo'] as $val => $txt): ?><option value="<?= $val ?>"<?= seleccionado($filtros['orden'], $val) ?>><?= $txt ?></option><?php endforeach; ?>
        </select>
        <input type="hidden" name="origen" value="<?= e($filtros['origen']) ?>">
        <button class="btn btn-claro" type="submit"><i class="bi bi-funnel"></i> Filtrar</button>
        <?php if (array_filter($filtros)): ?><a class="btn btn-fantasma" href="<?= url('/panel/huespedes') ?>">Limpiar</a><?php endif; ?>
    </form>
    <?php if ($huespedes): ?>
        <div class="tabla-contenedor">
            <table class="tabla">
                <thead><tr><th>Huésped</th><th>Documento</th><th>Procedencia</th><th>Contacto</th><th class="text-center">Estadías</th><th>Última visita</th><th class="text-end">Consumo total</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($huespedes as $h): ?>
                    <tr class="fila-enlace" data-href="<?= url('/panel/huespedes/' . $h['id_huesped']) ?>">
                        <td><div class="persona"><span class="avatar avatar-sm<?= $h['nacionalidad'] === 'Boliviana' ? '' : ' avatar-noche' ?>"><?= e(iniciales($h['nombres'] . ' ' . $h['apellidos'])) ?></span><div><div class="celda-principal"><?= e($h['nombres'] . ' ' . $h['apellidos']) ?></div><div class="celda-secundaria"><?= e($h['nacionalidad']) ?><?= (int) $h['alojado'] ? ' · <span class="texto-oro fw-semibold">Alojado</span>' : '' ?></div></div></div></td>
                        <td><span class="etiqueta-codigo"><?= e($h['sigla']) ?> <?= e($h['nro_documento']) ?></span></td>
                        <td><?= e($h['ciudad_procedencia']) ?></td>
                        <td><div class="tabular"><?= e($h['telefono'] ?: '—') ?></div><div class="celda-secundaria"><?= e($h['correo'] ?: '') ?></div></td>
                        <td class="text-center"><?php if ((int) $h['estadias'] >= 5): ?><span class="insignia insignia-oro"><i class="bi bi-star-fill"></i><?= (int) $h['estadias'] ?></span><?php else: ?><span class="tabular fw-medio"><?= (int) $h['estadias'] ?></span><?php endif; ?></td>
                        <td><?= $h['ultima_visita'] ? e(fecha($h['ultima_visita'])) : '<span class="texto-tenue">Sin estadías</span>' ?></td>
                        <td class="text-end tabular"><?= e(bs($h['gasto'], 0)) ?></td>
                        <td class="acciones">
                            <?php if (puede('huespedes.gestionar')): ?><a class="btn btn-claro btn-accion" href="<?= url('/panel/huespedes/' . $h['id_huesped'] . '/editar') ?>" data-bs-toggle="tooltip" title="Editar"><i class="bi bi-pencil"></i></a><?php endif; ?>
                            <?php if (puede('reservas.gestionar')): ?><a class="btn btn-claro btn-accion" href="<?= url('/panel/reservas/nueva', ['huesped' => $h['id_huesped']]) ?>" data-bs-toggle="tooltip" title="Nueva reserva"><i class="bi bi-calendar-plus"></i></a><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= $this->parcial('parciales/paginacion', ['paginador' => $paginador, 'etiqueta' => 'huéspedes']) ?>
    <?php else: ?>
        <?= $this->parcial('parciales/vacio', ['icono' => 'bi-person-x', 'titulo' => 'No se encontró al huésped', 'texto' => 'Revise el documento o el nombre. Si es la primera vez que se hospeda, regístrelo (CU-06, flujo 1a).', 'accion' => puede('huespedes.gestionar') ? ['url' => url('/panel/huespedes/nuevo', ['documento' => $filtros['q']]), 'texto' => 'Registrar huésped', 'icono' => 'bi-person-plus'] : null]) ?>
    <?php endif; ?>
</div>
