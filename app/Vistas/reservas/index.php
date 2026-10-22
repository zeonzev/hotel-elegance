<?php
/** @var \App\Core\Vista $this */
$acciones = '<a class="btn btn-claro" href="' . url('/panel/reservas', array_filter($filtros) + ['formato' => 'csv']) . '"><i class="bi bi-filetype-csv"></i> Exportar</a>'
    . '<a class="btn btn-claro" href="' . url('/panel/disponibilidad') . '"><i class="bi bi-calendar3-week"></i> Calendario</a>';
if (puede('reservas.gestionar')) {
    $acciones .= '<a class="btn btn-primary" href="' . url('/panel/reservas/nueva') . '"><i class="bi bi-plus-lg"></i> Nueva reserva</a>';
}
echo $this->parcial('parciales/encabezado', [
    'titulo' => 'Reservas',
    'cu' => 'CU-11 · CU-13 · CU-14',
    'descripcion' => 'Todas las reservas del hotel con su estado, canal, habitaciones y saldo. Haga clic en una fila para ver el detalle.',
    'migas' => [['Reservas', null]],
    'acciones' => $acciones,
]);
$estados = ['' => 'Todas', 'PENDIENTE' => 'Pendientes', 'CONFIRMADA' => 'Confirmadas', 'EN_CURSO' => 'En curso', 'FINALIZADA' => 'Finalizadas', 'CANCELADA' => 'Canceladas', 'NO_SHOW' => 'No-show'];
$base = array_filter(['q' => $filtros['q'], 'canal' => $filtros['canal'], 'desde' => $filtros['desde'], 'hasta' => $filtros['hasta'], 'periodo' => $filtros['periodo'], 'sobreventa' => $filtros['sobreventa']]);
?>
<div class="mb-3 d-flex flex-wrap gap-2 align-items-center">
    <div class="segmentos">
        <?php foreach ($estados as $valor => $texto): ?>
            <a class="<?= (string) $filtros['estado'] === $valor ? 'active' : '' ?>" href="<?= url('/panel/reservas', $base + array_filter(['estado' => $valor])) ?>"><?= $texto ?> <span class="cuenta"><?= numero($conteo[$valor] ?? 0) ?></span></a>
        <?php endforeach; ?>
    </div>
</div>
<div class="tarjeta">
    <form class="filtros" method="get">
        <input type="hidden" name="estado" value="<?= e($filtros['estado']) ?>">
        <div class="con-icono buscar-tabla"><i class="bi bi-search"></i><input class="form-control" type="search" name="q" value="<?= e($filtros['q']) ?>" placeholder="Código HE-…, huésped o documento"></div>
        <select class="form-select w-auto" name="periodo" data-autoenviar>
            <option value="">Cualquier fecha</option>
            <option value="proximas"<?= seleccionado($filtros['periodo'], 'proximas') ?>>Próximas llegadas</option>
            <option value="hoy"<?= seleccionado($filtros['periodo'], 'hoy') ?>>Movimiento de hoy</option>
        </select>
        <select class="form-select w-auto" name="canal" data-autoenviar>
            <option value="">Todos los canales</option>
            <?php foreach ($canales as $c): ?><option value="<?= $c['id_canal'] ?>"<?= seleccionado($filtros['canal'], $c['id_canal']) ?>><?= e($c['nombre']) ?></option><?php endforeach; ?>
        </select>
        <input type="date" class="form-control w-auto" name="desde" value="<?= e($filtros['desde']) ?>" aria-label="Ingreso desde" title="Ingreso desde">
        <input type="date" class="form-control w-auto" name="hasta" value="<?= e($filtros['hasta']) ?>" aria-label="Ingreso hasta" title="Ingreso hasta">
        <div class="form-check ms-1"><input class="form-check-input" type="checkbox" id="f-sobreventa" name="sobreventa" value="1"<?= marcado($filtros['sobreventa']) ?> data-autoenviar><label class="form-check-label fs-sm" for="f-sobreventa">Sólo sobreventa</label></div>
        <button class="btn btn-claro" type="submit"><i class="bi bi-funnel"></i> Filtrar</button>
        <?php if (array_filter($filtros)): ?><a class="btn btn-fantasma" href="<?= url('/panel/reservas') ?>">Limpiar</a><?php endif; ?>
    </form>
    <?php if ($reservas): ?>
        <div class="tabla-contenedor">
            <table class="tabla">
                <thead><tr><th>Reserva</th><th>Huésped</th><th>Estadía</th><th>Habitación</th><th>Canal</th><th class="text-end">Monto</th><th class="text-end">Saldo</th><th>Estado</th></tr></thead>
                <tbody>
                <?php foreach ($reservas as $r): $saldo = (float) $r['monto_total'] + (float) $r['cargos_extra'] - (float) $r['pagado']; ?>
                    <tr class="fila-enlace" data-href="<?= url('/panel/reservas/' . $r['id_reserva']) ?>">
                        <td><span class="etiqueta-codigo"><?= e($r['codigo']) ?></span><div class="celda-secundaria mt-1">Registrada <?= e(fecha($r['fecha_registro'])) ?></div></td>
                        <td><div class="celda-principal"><?= e($r['huesped']) ?></div><div class="celda-secundaria"><?= e($r['nacionalidad']) ?> · <?= (int) $r['cantidad_personas'] ?> pers.</div></td>
                        <td class="text-nowrap"><div class="fw-medio"><?= e(fecha($r['fecha_ingreso'], 'dia')) ?> → <?= e(fecha($r['fecha_salida'], 'dia')) ?></div><div class="celda-secundaria"><?= (int) $r['noches'] ?> noche(s) · <?= e(substr($r['fecha_ingreso'], 0, 4)) ?></div></td>
                        <td><?= e($r['habitaciones']) ?><?php if ((int) $r['es_sobreventa']): ?><div><span class="insignia insignia-violeta mt-1"><i class="bi bi-shield-exclamation"></i>Sobreventa</span></div><?php endif; ?></td>
                        <td><span class="d-inline-flex align-items-center gap-1"><i class="bi <?= e($r['canal_icono']) ?> texto-tenue"></i><?= e($r['canal']) ?></span></td>
                        <td class="text-end tabular"><?= e(bs($r['monto_total'])) ?></td>
                        <td class="text-end tabular <?= $saldo > 0.009 && !in_array($r['estado'], ['CANCELADA', 'NO_SHOW'], true) ? 'texto-oro fw-semibold' : 'texto-tenue' ?>"><?= in_array($r['estado'], ['CANCELADA', 'NO_SHOW'], true) ? '—' : ($saldo > 0.009 ? e(bs($saldo)) : 'Pagado') ?></td>
                        <td><?= insignia($r['estado']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= $this->parcial('parciales/paginacion', ['paginador' => $paginador, 'etiqueta' => 'reservas']) ?>
    <?php else: ?>
        <?= $this->parcial('parciales/vacio', ['icono' => 'bi-journal-x', 'titulo' => 'Sin reservas', 'texto' => 'No hay reservas que coincidan con los filtros aplicados.', 'accion' => puede('reservas.gestionar') ? ['url' => url('/panel/reservas/nueva'), 'texto' => 'Registrar reserva'] : null]) ?>
    <?php endif; ?>
</div>
