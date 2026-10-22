<?php
/** @var \App\Core\Vista $this */
echo $this->parcial('parciales/encabezado', [
    'titulo' => 'Reportes',
    'cu' => 'CU-22 · CU-23 · CU-24',
    'descripcion' => 'Información consolidada para la toma de decisiones. Cada reporte permite elegir el periodo y exportar a CSV, Excel o PDF.',
    'migas' => [['Inteligencia', null], ['Reportes', null]],
]);
$tarjetas = [
    ['/panel/reportes/ocupacion', 'bi-buildings', 'chip-noche', 'CU-22', 'Reporte de ocupación', 'Noches ocupadas sobre noches disponibles, por día, por categoría y por día de la semana. Incluye ADR y RevPAR.', [['Ocupación del mes', numero($ocupacion['porcentaje'], 1) . ' %'], ['Noches vendidas', numero($ocupacion['ocupadas'])], ['ADR', bs($rendimiento['adr'], 0)]]],
    ['/panel/reportes/ingresos', 'bi-cash-stack', 'chip-verde', 'CU-23', 'Reporte de ingresos', 'Ingresos estimados a partir de las reservas (excluye canceladas y no-show) y cobros por método de pago y canal.', [['Estimado del mes', bs($ingresos['estimado'], 0)], ['Cobrado', bs($ingresos['cobrado'], 0)], ['Por reserva', bs($ingresos['promedio'], 0)]]],
    ['/panel/reportes/cancelaciones', 'bi-x-octagon', 'chip-rojo', 'CU-24', 'Reporte de cancelaciones', 'Reservas canceladas agrupadas por tipo de motivo, canal y anticipación, además de los no-show del periodo.', [['Cancelaciones', numero($cancel['cantidad'])], ['Tasa', numero($cancel['tasa'], 1) . ' %'], ['No-show', numero($cancel['no_show'])]]],
];
?>
<div class="sobretitulo mb-3">Indicadores de <?= e(MESES[(int) date('n')]) ?> (del <?= e(fecha($periodo[1])) ?> a hoy)</div>
<div class="row g-4-5 animar-entrada">
    <?php foreach ($tarjetas as [$ruta, $icono, $chip, $cu, $titulo, $texto, $kpis]): ?>
        <div class="col-xl-4">
            <a class="tarjeta tarjeta-interactiva d-flex flex-column h-100 text-reset" href="<?= url($ruta) ?>">
                <div class="tarjeta-cuerpo flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start mb-3"><span class="icono-chip <?= $chip ?>" style="width:52px;height:52px;font-size:1.4rem"><i class="bi <?= $icono ?>"></i></span><span class="cu-etiqueta"><?= $cu ?></span></div>
                    <h2 class="h3"><?= e($titulo) ?></h2>
                    <p class="texto-suave fs-sm"><?= e($texto) ?></p>
                    <div class="row g-2 mt-2">
                        <?php foreach ($kpis as [$t, $v]): ?><div class="col-4"><div class="rounded-3 p-2" style="background:var(--he-superficie-2);border:1px solid var(--he-borde)"><div class="fs-xs texto-tenue"><?= e($t) ?></div><div class="fw-semibold tabular"><?= e($v) ?></div></div></div><?php endforeach; ?>
                    </div>
                </div>
                <div class="tarjeta-pie d-flex justify-content-between align-items-center"><span class="fs-sm fw-semibold">Abrir reporte</span><i class="bi bi-arrow-right"></i></div>
            </a>
        </div>
    <?php endforeach; ?>
</div>
