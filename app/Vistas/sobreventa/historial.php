<?php
/** @var \App\Core\Vista $this */
echo $this->parcial('parciales/encabezado', [
    'titulo' => 'Historial de evaluaciones',
    'cu' => 'CU-18 · CU-19',
    'descripcion' => 'Cada evaluación del modelo de decisión, ya sea manual o generada al registrar una reserva con la ocupación sobre el umbral.',
    'migas' => [['Sobreventa', '/panel/sobreventa'], ['Historial', null]],
]);
?>
<div class="tarjeta">
    <?php if ($evaluaciones): ?>
        <div class="tabla-contenedor">
            <table class="tabla">
                <thead><tr><th>Registrada</th><th>Noche</th><th class="text-end">Ocupación</th><th class="text-end">p ausencia</th><th class="text-end">Ingreso / costo</th><th class="text-end">VE informe</th><th class="text-end">VE binomial</th><th class="text-center">x*</th><th>Decisión</th><th>Origen</th></tr></thead>
                <tbody>
                <?php foreach ($evaluaciones as $h): ?>
                    <tr>
                        <td class="tabular text-nowrap"><?= e(fecha($h['fecha'], 'hora')) ?><div class="celda-secundaria">@<?= e($h['nombre_usuario'] ?? '—') ?></div></td>
                        <td><a href="<?= url('/panel/sobreventa', ['fecha' => $h['fecha_objetivo']]) ?>"><?= e(fecha($h['fecha_objetivo'])) ?></a></td>
                        <td class="text-end tabular"><?= numero($h['ocupacion'], 1) ?> %<div class="celda-secundaria"><?= (int) $h['reservas_confirmadas'] ?>/<?= (int) $h['capacidad'] ?></div></td>
                        <td class="text-end tabular"><?= numero($h['p_no_show'] * 100, 1) ?> %</td>
                        <td class="text-end tabular"><?= e(bs($h['ingreso'], 0)) ?> / <?= e(bs($h['costo_reubicacion'], 0)) ?></td>
                        <td class="text-end tabular"><?= e(bs($h['ve_simple'])) ?></td>
                        <td class="text-end tabular fw-semibold <?= $h['ve_aceptar'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= e(bs($h['ve_aceptar'])) ?></td>
                        <td class="text-center tabular">+<?= (int) $h['nivel_optimo'] ?></td>
                        <td><?= insignia($h['decision']) ?></td>
                        <td><?php if ($h['origen'] === 'RESERVA' && $h['codigo']): ?><a class="etiqueta-codigo" href="<?= url('/panel/reservas/' . $h['id_reserva']) ?>"><?= e($h['codigo']) ?></a><?php else: ?><span class="texto-tenue">Manual</span><?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <?= $this->parcial('parciales/vacio', ['icono' => 'bi-calculator', 'titulo' => 'Sin evaluaciones', 'texto' => 'Las evaluaciones aparecen al guardarlas desde la política de sobreventa o al registrar reservas con alerta.', 'accion' => ['url' => url('/panel/sobreventa'), 'texto' => 'Evaluar una noche', 'icono' => 'bi-calculator']]) ?>
    <?php endif; ?>
</div>
