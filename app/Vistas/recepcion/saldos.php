<?php
/** @var \App\Core\Vista $this */
echo $this->parcial('parciales/encabezado', [
    'titulo' => 'Cobros pendientes',
    'descripcion' => 'Reservas activas que todavía tienen saldo por cobrar: huéspedes alojados, próximas llegadas sin anticipo y reservas confirmadas.',
    'migas' => [['Reservas', null], ['Cobros pendientes', null]],
    'acciones' => '<a class="btn btn-claro" href="' . url('/panel/cobros', ['vista' => $vista, 'formato' => 'csv']) . '"><i class="bi bi-filetype-csv"></i> Exportar</a>',
]);
$pestanas = ['todas' => ['Todas', $resumen['reservas']], 'alojados' => ['Huéspedes alojados', $resumen['alojados']], 'sin_anticipo' => ['Llegan esta semana sin anticipo', $resumen['sin_anticipo']]];
?>
<div class="row g-4-5 mb-4">
    <?php foreach ([
        ['Total por cobrar', bs($resumen['total'], 0), numero($resumen['reservas']) . ' reservas con saldo', 'bi-cash-stack', 'chip-oro'],
        ['Huéspedes alojados con saldo', numero($resumen['alojados']), bs($resumen['saldo_alojados'], 0) . ' a cobrar en el check-out', 'bi-door-open', 'chip-azul'],
        ['Llegadas sin anticipo', numero($resumen['sin_anticipo']), 'en los próximos 7 días', 'bi-exclamation-triangle', 'chip-ambar'],
    ] as [$t, $v, $s, $i, $c]): ?>
        <div class="col-md-4"><div class="tarjeta kpi"><div class="kpi-cabecera"><span class="kpi-titulo"><?= e($t) ?></span><span class="icono-chip <?= $c ?>"><i class="bi <?= $i ?>"></i></span></div><div class="kpi-valor" style="font-size:1.6rem"><?= e($v) ?></div><div class="kpi-pie"><?= e($s) ?></div></div></div>
    <?php endforeach; ?>
</div>

<div class="tarjeta">
    <div class="filtros">
        <div class="segmentos">
            <?php foreach ($pestanas as $clave => [$texto, $cuenta]): ?>
                <a class="<?= $vista === $clave ? 'active' : '' ?>" href="<?= url('/panel/cobros', $clave === 'todas' ? [] : ['vista' => $clave]) ?>"><?= e($texto) ?> <span class="cuenta"><?= (int) $cuenta ?></span></a>
            <?php endforeach; ?>
        </div>
        <span class="ms-auto fs-sm texto-tenue"><?= count($lista) ?> reserva(s) · <?= e(bs(array_sum(array_column($lista, 'saldo')))) ?></span>
    </div>
    <?php if ($lista): ?>
        <div class="tabla-contenedor">
            <table class="tabla">
                <thead><tr><th>Reserva</th><th>Huésped</th><th>Estadía</th><th class="text-end">Total</th><th style="width:18%">Pagado</th><th class="text-end">Saldo</th><th class="text-end">Acciones</th></tr></thead>
                <tbody>
                <?php foreach ($lista as $r):
                    $total = (float) $r['monto_total'] + (float) $r['cargos_extra'];
                    $avance = $total > 0 ? (float) $r['pagado'] / $total * 100 : 0;
                    $telefono = preg_replace('/\D/', '', (string) $r['telefono']);
                ?>
                    <tr>
                        <td><a class="etiqueta-codigo" href="<?= url('/panel/reservas/' . $r['id_reserva']) ?>"><?= e($r['codigo']) ?></a><div class="mt-1"><?= insignia($r['estado']) ?></div></td>
                        <td><div class="celda-principal"><?= e($r['huesped']) ?></div><div class="celda-secundaria"><?= e($r['telefono'] ?: 'Sin teléfono') ?></div></td>
                        <td class="text-nowrap"><div class="fw-medio"><?= e(fecha($r['fecha_ingreso'], 'dia')) ?> → <?= e(fecha($r['fecha_salida'], 'dia')) ?></div><div class="celda-secundaria">Hab. <?= e($r['habitaciones']) ?></div></td>
                        <td class="text-end tabular"><?= e(bs($total)) ?></td>
                        <td><div class="d-flex align-items-center gap-2"><div class="barra-progreso flex-grow-1"><span style="width:<?= min(100, $avance) ?>%"></span></div><span class="fs-xs texto-tenue tabular" style="width:34px"><?= numero($avance) ?>%</span></div><div class="celda-secundaria tabular"><?= e(bs($r['pagado'])) ?></div></td>
                        <td class="text-end tabular fw-semibold texto-oro"><?= e(bs($r['saldo'])) ?></td>
                        <td class="acciones">
                            <?php if ($telefono): ?>
                                <a class="btn btn-claro btn-accion" href="https://wa.me/<?= e($telefono) ?>?text=<?= rawurlencode('Hola ' . explode(' ', $r['huesped'])[0] . ', le escribimos del Hotel Elegance sobre su reserva ' . $r['codigo'] . '. Su saldo pendiente es de ' . bs($r['saldo']) . '.') ?>" target="_blank" rel="noopener" data-bs-toggle="tooltip" title="Recordar por WhatsApp"><i class="bi bi-whatsapp"></i></a>
                            <?php endif; ?>
                            <?php if ($r['estado'] === 'EN_CURSO'): ?>
                                <a class="btn btn-sm btn-oro" href="<?= url('/panel/reservas/' . $r['id_reserva'] . '/checkout') ?>">Check-out</a>
                            <?php else: ?>
                                <a class="btn btn-sm btn-claro" href="<?= url('/panel/reservas/' . $r['id_reserva']) ?>">Registrar pago</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <?= $this->parcial('parciales/vacio', ['icono' => 'bi-check2-circle', 'titulo' => 'Sin saldos pendientes', 'texto' => 'No hay reservas con saldo por cobrar en esta vista.']) ?>
    <?php endif; ?>
</div>
