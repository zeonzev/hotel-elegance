<?php
/** @var \App\Core\Vista $this */
echo $this->parcial('parciales/encabezado', [
    'titulo' => 'Bitácora de auditoría',
    'descripcion' => 'Registro inalterable de las acciones importantes: quién hizo qué, cuándo y desde qué equipo.',
    'migas' => [['Administración', null], ['Bitácora', null]],
    'acciones' => '<a class="btn btn-claro" href="' . url('/panel/bitacora', ['formato' => 'csv']) . '"><i class="bi bi-filetype-csv"></i> Exportar CSV</a>',
]);
$iconos = ['Sesiones' => ['bi-person-lock', 'chip-noche'], 'Reservas' => ['bi-journal-bookmark', 'chip-azul'], 'Habitaciones' => ['bi-door-closed', 'chip-verde'], 'Huéspedes' => ['bi-people', 'chip-oro'], 'Tarifas' => ['bi-tags', 'chip-violeta'], 'Sobreventa' => ['bi-shield-check', 'chip-rojo'], 'Reportes' => ['bi-bar-chart', 'chip-ambar']];
?>
<div class="tarjeta">
    <form class="filtros" method="get">
        <div class="con-icono buscar-tabla"><i class="bi bi-search"></i><input class="form-control" type="search" name="q" value="<?= e($filtros['q']) ?>" placeholder="Buscar acción o descripción"></div>
        <select class="form-select w-auto" name="modulo" data-autoenviar><option value="">Todos los módulos</option><?php foreach ($modulos as $m): ?><option<?= seleccionado($filtros['modulo'], $m) ?>><?= e($m) ?></option><?php endforeach; ?></select>
        <select class="form-select w-auto" name="usuario" data-autoenviar><option value="">Todos los usuarios</option><?php foreach ($usuarios as $u): ?><option value="<?= $u['id_usuario'] ?>"<?= seleccionado($filtros['usuario'], $u['id_usuario']) ?>>@<?= e($u['nombre_usuario']) ?></option><?php endforeach; ?></select>
        <input type="date" class="form-control w-auto" name="desde" value="<?= e($filtros['desde']) ?>" aria-label="Desde">
        <input type="date" class="form-control w-auto" name="hasta" value="<?= e($filtros['hasta']) ?>" aria-label="Hasta">
        <button class="btn btn-claro" type="submit"><i class="bi bi-funnel"></i> Filtrar</button>
        <?php if (array_filter($filtros)): ?><a class="btn btn-fantasma" href="<?= url('/panel/bitacora') ?>">Limpiar</a><?php endif; ?>
    </form>
    <?php if ($registros): ?>
        <div class="tabla-contenedor">
            <table class="tabla">
                <thead><tr><th>Fecha y hora</th><th>Usuario</th><th>Módulo</th><th>Acción</th><th>Descripción</th><th>IP</th></tr></thead>
                <tbody>
                <?php foreach ($registros as $r): [$ico, $chip] = $iconos[$r['modulo']] ?? ['bi-dot', 'chip-gris']; ?>
                    <tr>
                        <td class="tabular text-nowrap"><?= e(fecha($r['fecha'], 'hora')) ?></td>
                        <td><?php if ($r['nombre_usuario']): ?><div class="fw-medio">@<?= e($r['nombre_usuario']) ?></div><div class="celda-secundaria"><?= e($r['rol']) ?></div><?php else: ?><span class="texto-tenue">Sistema / anónimo</span><?php endif; ?></td>
                        <td><span class="d-inline-flex align-items-center gap-2"><span class="icono-chip <?= $chip ?>" style="width:28px;height:28px;font-size:.8rem;border-radius:8px"><i class="bi <?= $ico ?>"></i></span><?= e($r['modulo']) ?></span></td>
                        <td class="fw-medio"><?= e($r['accion']) ?></td>
                        <td class="texto-suave"><?= e($r['descripcion']) ?></td>
                        <td class="font-monospace fs-xs texto-tenue"><?= e($r['ip']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= $this->parcial('parciales/paginacion', ['paginador' => $paginador, 'etiqueta' => 'eventos']) ?>
    <?php else: ?>
        <?= $this->parcial('parciales/vacio', ['icono' => 'bi-clock-history', 'titulo' => 'Sin eventos', 'texto' => 'No hay registros de auditoría con esos filtros.']) ?>
    <?php endif; ?>
</div>
