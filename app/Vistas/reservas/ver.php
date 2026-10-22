<?php
/** @var \App\Core\Vista $this */
$hoy = hoy();
$id = (int) $r['id_reserva'];
$modificable = in_array($r['estado'], ['PENDIENTE', 'CONFIRMADA'], true);
$noches = noches($r['fecha_ingreso'], $r['fecha_salida']);
$acciones = '';
if ($r['estado'] === 'PENDIENTE' && puede('reservas.gestionar')) {
    $acciones .= '<form method="post" action="' . url("/panel/reservas/$id/confirmar") . '">' . csrf_campo() . '<button class="btn btn-claro" type="submit"><i class="bi bi-check2-circle"></i> Confirmar</button></form>';
}
if ($modificable && puede('reservas.gestionar')) {
    $acciones .= '<a class="btn btn-claro" href="' . url("/panel/reservas/$id/editar") . '"><i class="bi bi-pencil"></i> Modificar</a>';
    $acciones .= '<button class="btn btn-peligro-suave" type="button" data-bs-toggle="modal" data-bs-target="#modal-cancelar"><i class="bi bi-x-circle"></i> Cancelar</button>';
}
if ($modificable && $r['fecha_ingreso'] <= $hoy && puede('recepcion.operar')) {
    $acciones .= '<form method="post" action="' . url("/panel/reservas/$id/no-show") . '" data-confirmar="¿Marcar como no-show?" data-confirmar-texto="El huésped no se presentó. La reserva contará para la tasa histórica de no-show (CU-17)." data-confirmar-boton="Marcar no-show">' . csrf_campo() . '<button class="btn btn-claro" type="submit"><i class="bi bi-person-x"></i> No-show</button></form>';
    if ($r['fecha_salida'] > $hoy) {
        $acciones .= '<a class="btn btn-primary" href="' . url("/panel/reservas/$id/checkin") . '"><i class="bi bi-box-arrow-in-right"></i> Registrar check-in</a>';
    }
}
if ($r['estado'] === 'EN_CURSO' && puede('recepcion.operar')) {
    $acciones .= '<a class="btn btn-primary" href="' . url("/panel/reservas/$id/checkout") . '"><i class="bi bi-box-arrow-right"></i> Registrar check-out</a>';
}
$acciones .= '<a class="btn btn-claro" href="' . url("/panel/reservas/$id/recibo") . '" target="_blank" rel="noopener"><i class="bi bi-printer"></i> ' . ($recibo ? 'Recibo' : 'Comprobante') . '</a>';
echo $this->parcial('parciales/encabezado', [
    'titulo' => 'Reserva ' . $r['codigo'],
    'cu' => 'CU-11 a CU-16',
    'descripcion' => 'Registrada el ' . fecha($r['fecha_registro'], 'hora') . ' por @' . ($r['registrado_por'] ?? 'sistema') . ' vía ' . $r['canal'] . '.',
    'migas' => [['Reservas', '/panel/reservas'], [$r['codigo'], null]],
    'acciones' => $acciones,
]);
?>
<?php if ((int) \App\Core\Sesion::flashActual('imprimir_recibo', 0) === $id && $recibo): ?>
    <div class="aviso aviso-verde mb-4"><i class="bi bi-receipt"></i><div class="flex-grow-1"><strong>Recibo <?= e($recibo['numero']) ?> emitido por <?= e(bs($recibo['total'])) ?></strong><span class="fs-sm">La habitación quedó liberada automáticamente (CU-16).</span></div><a class="btn btn-sm btn-exito" href="<?= url("/panel/reservas/$id/recibo") ?>" target="_blank" rel="noopener"><i class="bi bi-printer"></i> Imprimir recibo</a></div>
<?php endif; ?>
<div class="row g-4-5">
    <div class="col-xl-8">
        <div class="tarjeta mb-4">
            <div class="tarjeta-cuerpo">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div class="d-flex align-items-center gap-4">
                        <div class="text-center"><div class="sobretitulo">Ingreso</div><div class="serif fs-1 fw-semibold lh-1"><?= (int) date('j', strtotime($r['fecha_ingreso'])) ?></div><div class="fs-sm texto-suave"><?= e(MESES_CORTOS[(int) date('n', strtotime($r['fecha_ingreso']))] . ' ' . date('Y', strtotime($r['fecha_ingreso']))) ?> · <?= e(DIAS_CORTOS[(int) date('N', strtotime($r['fecha_ingreso']))]) ?></div></div>
                        <div class="text-center texto-tenue"><i class="bi bi-moon-stars fs-4 d-block"></i><span class="fs-xs"><?= $noches ?> noche(s)</span></div>
                        <div class="text-center"><div class="sobretitulo">Salida</div><div class="serif fs-1 fw-semibold lh-1"><?= (int) date('j', strtotime($r['fecha_salida'])) ?></div><div class="fs-sm texto-suave"><?= e(MESES_CORTOS[(int) date('n', strtotime($r['fecha_salida']))] . ' ' . date('Y', strtotime($r['fecha_salida']))) ?> · <?= e(DIAS_CORTOS[(int) date('N', strtotime($r['fecha_salida']))]) ?></div></div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <?= insignia($r['estado']) ?>
                        <?php if ((int) $r['es_sobreventa']): ?><span class="insignia insignia-violeta"><i class="bi bi-shield-exclamation"></i>Sobreventa</span><?php endif; ?>
                        <span class="insignia insignia-gris"><i class="bi bi-people"></i><?= (int) $r['cantidad_personas'] ?> persona(s)</span>
                        <span class="insignia insignia-gris"><i class="bi <?= e($r['canal_icono']) ?>"></i><?= e($r['canal']) ?></span>
                    </div>
                </div>
                <?php if ($r['observaciones']): ?><div class="aviso mt-3 py-2"><i class="bi bi-chat-left-text texto-tenue"></i><div class="fs-sm"><?= nl2br(e($r['observaciones'])) ?></div></div><?php endif; ?>
            </div>
        </div>

        <div class="tarjeta mb-4">
            <div class="tarjeta-encabezado"><div><h2>Habitaciones</h2><div class="subtitulo">Precio promedio por noche según la temporada (CU-11 paso 5)</div></div></div>
            <div class="tabla-contenedor">
                <table class="tabla">
                    <thead><tr><th>Habitación</th><th>Categoría</th><th class="text-end">Precio / noche</th><th class="text-center">Noches</th><th class="text-end">Subtotal</th></tr></thead>
                    <tbody>
                    <?php foreach ($detalle as $d): ?>
                        <tr>
                            <td>
                                <?php if ($d['id_habitacion']): ?>
                                    <a class="serif fs-4 fw-semibold text-reset" href="<?= url('/panel/habitaciones/' . $d['id_habitacion']) ?>"><?= e($d['numero']) ?></a> <span class="fs-xs texto-tenue">piso <?= (int) $d['piso'] ?></span>
                                <?php else: ?>
                                    <span class="insignia insignia-violeta"><i class="bi bi-hourglass-split"></i>Por asignar</span>
                                    <?php if ($libres && puede('reservas.gestionar')): ?>
                                        <form method="post" action="<?= url("/panel/reservas/$id/asignar") ?>" class="d-flex gap-2 mt-2">
                                            <?= csrf_campo() ?><input type="hidden" name="id_detalle" value="<?= (int) $d['id_detalle'] ?>">
                                            <select class="form-select form-select-sm" name="id_habitacion" required><?php foreach ($libres as $l): ?><option value="<?= $l['id_habitacion'] ?>"><?= e($l['numero'] . ' · ' . $l['categoria']) ?></option><?php endforeach; ?></select>
                                            <button class="btn btn-sm btn-oro" type="submit">Asignar</button>
                                        </form>
                                    <?php elseif ($modificable): ?><div class="fs-xs texto-tenue mt-1">Sin habitaciones libres por ahora; se asignará al check-in.</div><?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td><?= e($d['categoria']) ?><div class="celda-secundaria"><?= (int) $d['capacidad'] ?> pers.</div></td>
                            <td class="text-end tabular"><?= e(bs($d['precio_noche'])) ?></td>
                            <td class="text-center tabular"><?= (int) $d['noches'] ?></td>
                            <td class="text-end tabular fw-semibold"><?= e(bs($d['subtotal'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot><tr><td colspan="4">Total del hospedaje</td><td class="text-end tabular"><?= e(bs($r['monto_total'])) ?></td></tr></tfoot>
                </table>
            </div>
        </div>

        <div class="tarjeta mb-4">
            <div class="tarjeta-encabezado">
                <div><h2>Pagos</h2><div class="subtitulo">Anticipos, hospedaje y consumos en bolivianos</div></div>
                <?php if (!in_array($r['estado'], ['CANCELADA', 'FINALIZADA', 'NO_SHOW'], true) && $saldo > 0 && puede('reservas.gestionar')): ?>
                    <button class="btn btn-sm btn-claro" type="button" data-bs-toggle="modal" data-bs-target="#modal-pago"><i class="bi bi-plus-lg"></i> Registrar pago</button>
                <?php endif; ?>
            </div>
            <?php if ($pagos): ?>
                <div class="tabla-contenedor"><table class="tabla tabla-compacta">
                    <thead><tr><th>Fecha</th><th>Concepto</th><th>Método</th><th>Referencia</th><th class="text-end">Monto</th></tr></thead>
                    <tbody><?php foreach ($pagos as $p): ?>
                        <tr><td class="tabular"><?= e(fecha($p['fecha'], 'hora')) ?></td><td><?= e(ucfirst(strtolower($p['concepto']))) ?></td><td><?= e($p['metodo']) ?></td><td class="font-monospace fs-xs"><?= e($p['referencia'] ?? '—') ?></td><td class="text-end tabular fw-semibold"><?= e(bs($p['monto'])) ?></td></tr>
                    <?php endforeach; ?></tbody>
                </table></div>
            <?php else: ?><div class="tarjeta-cuerpo texto-suave fs-sm">Todavía no se registraron pagos.</div><?php endif; ?>
        </div>

        <div class="tarjeta">
            <div class="tarjeta-encabezado"><h2>Seguimiento</h2></div>
            <div class="tarjeta-cuerpo">
                <ul class="linea-tiempo">
                    <li><span class="marca"><i class="bi bi-journal-plus"></i></span><strong>Reserva registrada</strong><small><?= e(fecha($r['fecha_registro'], 'hora')) ?> · @<?= e($r['registrado_por'] ?? 'sistema') ?> · <?= e($r['canal']) ?></small></li>
                    <?php if ($r['fecha_modificacion'] && !$cancelacion): ?><li><span class="marca"><i class="bi bi-pencil"></i></span><strong>Última modificación</strong><small><?= e(fecha($r['fecha_modificacion'], 'hora')) ?></small></li><?php endif; ?>
                    <?php if ($estadia): ?>
                        <li><span class="marca" style="color:var(--he-verde)"><i class="bi bi-box-arrow-in-right"></i></span><strong>Check-in</strong><small><?= e(fecha($estadia['fecha_checkin'], 'hora')) ?> · @<?= e($estadia['usuario_checkin'] ?? '—') ?></small></li>
                        <?php if ($estadia['fecha_checkout']): ?><li><span class="marca" style="color:var(--he-azul)"><i class="bi bi-box-arrow-right"></i></span><strong>Check-out</strong><small><?= e(fecha($estadia['fecha_checkout'], 'hora')) ?> · @<?= e($estadia['usuario_checkout'] ?? '—') ?><?= $estadia['observaciones'] ? ' · ' . e($estadia['observaciones']) : '' ?></small></li><?php endif; ?>
                    <?php endif; ?>
                    <?php if ($cancelacion): ?><li><span class="marca" style="color:var(--he-rojo)"><i class="bi bi-x-circle"></i></span><strong>Cancelada · <?= e(MOTIVOS_CANCELACION[$cancelacion['tipo_motivo']] ?? $cancelacion['tipo_motivo']) ?></strong><small><?= e($cancelacion['descripcion']) ?> · <?= e(fecha($cancelacion['fecha'], 'hora')) ?> · @<?= e($cancelacion['nombre_usuario'] ?? '—') ?></small></li><?php endif; ?>
                    <?php if ($r['estado'] === 'NO_SHOW'): ?><li><span class="marca" style="color:var(--he-violeta)"><i class="bi bi-person-x"></i></span><strong>No se presentó (no-show)</strong><small><?= e(fecha($r['fecha_modificacion'], 'hora')) ?></small></li><?php endif; ?>
                    <?php if ($recibo): ?><li><span class="marca" style="color:var(--he-oro-600)"><i class="bi bi-receipt"></i></span><strong>Recibo <?= e($recibo['numero']) ?></strong><small><?= e(fecha($recibo['fecha_emision'], 'hora')) ?> · <?= e(bs($recibo['total'])) ?></small></li><?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="tarjeta tarjeta-noche mb-4">
            <div class="tarjeta-cuerpo">
                <div class="sobretitulo" style="color:var(--he-oro-300)">Estado de cuenta</div>
                <div class="d-flex justify-content-between align-items-baseline mt-2"><span class="texto-suave fs-sm">Hospedaje</span><span class="tabular text-white"><?= e(bs($r['monto_total'])) ?></span></div>
                <div class="d-flex justify-content-between align-items-baseline mt-1"><span class="texto-suave fs-sm">Cargos adicionales</span><span class="tabular text-white"><?= e(bs($r['cargos_extra'])) ?></span></div>
                <div class="d-flex justify-content-between align-items-baseline mt-1"><span class="texto-suave fs-sm">Pagado</span><span class="tabular" style="color:#5CC293">− <?= e(bs($pagado)) ?></span></div>
                <hr style="border-color: rgba(255,255,255,.12)">
                <div class="d-flex justify-content-between align-items-baseline"><span class="texto-suave">Saldo</span><span class="serif fw-semibold" style="font-size:2.2rem; color: <?= $saldo > 0 && !in_array($r['estado'], ['CANCELADA', 'NO_SHOW'], true) ? 'var(--he-oro-300)' : '#5CC293' ?>"><?= e(bs(max(0, $saldo))) ?></span></div>
                <?php if (in_array($r['estado'], ['CANCELADA', 'NO_SHOW'], true) && $pagado > 0): ?><div class="fs-xs texto-suave">El anticipo quedó retenido según la política del hotel.</div><?php endif; ?>
            </div>
        </div>
        <div class="tarjeta mb-4">
            <div class="tarjeta-encabezado"><h2>Huésped titular</h2><?php if (puede('huespedes.historial')): ?><a class="btn btn-sm btn-claro" href="<?= url('/panel/huespedes/' . $r['id_huesped']) ?>">Historial</a><?php endif; ?></div>
            <div class="tarjeta-cuerpo">
                <div class="persona mb-3"><span class="avatar"><?= e(iniciales($r['huesped'])) ?></span><div><div class="fw-semibold"><?= e($r['huesped']) ?></div><div class="fs-xs texto-tenue"><?= e($r['tipo_documento'] . ' ' . $r['nro_documento']) ?></div></div></div>
                <dl class="datos">
                    <dt>Procedencia</dt><dd><?= e($r['ciudad_procedencia']) ?> (<?= e($r['nacionalidad']) ?>)</dd>
                    <dt>Teléfono</dt><dd><?= e($r['telefono'] ?: '—') ?></dd>
                    <dt>Correo</dt><dd><?= e($r['correo'] ?: '—') ?></dd>
                </dl>
            </div>
        </div>
        <?php if ($evaluacion): ?>
            <div class="tarjeta">
                <div class="tarjeta-encabezado"><div><h2>Decisión de sobreventa</h2><div class="subtitulo">Evaluación registrada (CU-18 / CU-19)</div></div><?= insignia($evaluacion['decision']) ?></div>
                <div class="tarjeta-cuerpo">
                    <dl class="datos">
                        <dt>Noche crítica</dt><dd><?= e(fecha($evaluacion['fecha_objetivo'], 'larga')) ?></dd>
                        <dt>Ocupación</dt><dd><?= numero($evaluacion['ocupacion'], 1) ?> % (<?= (int) $evaluacion['reservas_confirmadas'] ?>/<?= (int) $evaluacion['capacidad'] ?>)</dd>
                        <dt>Prob. de ausencia</dt><dd><?= numero($evaluacion['p_no_show'] * 100, 1) ?> %</dd>
                        <dt>VE(aceptar)</dt><dd class="<?= $evaluacion['ve_aceptar'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= e(bs($evaluacion['ve_aceptar'])) ?></dd>
                        <dt>Decisión del usuario</dt><dd><?= e(ucfirst(strtolower((string) $evaluacion['decision_usuario']))) ?></dd>
                    </dl>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($modificable): ?>
<div class="modal fade" id="modal-cancelar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" action="<?= url("/panel/reservas/$id/cancelar") ?>">
            <?= csrf_campo() ?>
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-x-circle text-danger"></i> Cancelar reserva <?= e($r['codigo']) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
            <div class="modal-body">
                <p class="fs-sm texto-suave">CU-14: el motivo es obligatorio. Las habitaciones quedarán libres para esas fechas y la cancelación contará en la tasa histórica (CU-17).</p>
                <label class="form-label" for="tipo_motivo">Tipo de motivo *</label>
                <select class="form-select mb-3" id="tipo_motivo" name="tipo_motivo" required><option value="">Seleccione…</option><?php foreach (MOTIVOS_CANCELACION as $clave => $texto): ?><option value="<?= $clave ?>"><?= e($texto) ?></option><?php endforeach; ?></select>
                <label class="form-label" for="descripcion">Descripción del motivo *</label>
                <textarea class="form-control" id="descripcion" name="descripcion" rows="3" required minlength="5" maxlength="255" placeholder="Ej.: Vuelo cancelado por mal tiempo"></textarea>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-claro" data-bs-dismiss="modal">Volver</button><button class="btn btn-peligro" type="submit"><i class="bi bi-x-circle"></i> Cancelar reserva</button></div>
        </form>
    </div>
</div>
<?php endif; ?>
<?php if ($saldo > 0): ?>
<div class="modal fade" id="modal-pago" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" action="<?= url("/panel/reservas/$id/pagos") ?>">
            <?= csrf_campo() ?>
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-cash-coin texto-oro"></i> Registrar pago</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
            <div class="modal-body row g-3">
                <div class="col-6"><label class="form-label" for="monto">Monto (Bs)</label><input type="number" step="0.01" min="0.01" max="<?= $saldo ?>" class="form-control" id="monto" name="monto" value="<?= $saldo ?>" required></div>
                <div class="col-6"><label class="form-label" for="concepto">Concepto</label><select class="form-select" id="concepto" name="concepto"><option value="ANTICIPO">Anticipo</option><option value="HOSPEDAJE">Hospedaje</option><option value="CONSUMO">Consumo</option></select></div>
                <div class="col-6"><label class="form-label" for="metodo">Método</label><select class="form-select" id="metodo" name="id_metodo_pago"><?php foreach ($metodos as $m): ?><option value="<?= $m['id_metodo_pago'] ?>"><?= e($m['nombre']) ?></option><?php endforeach; ?></select></div>
                <div class="col-6"><label class="form-label" for="ref">Referencia</label><input class="form-control" id="ref" name="referencia" maxlength="80" placeholder="QR, POS o transferencia"></div>
                <div class="col-12 fs-xs texto-tenue">Saldo pendiente: <?= e(bs($saldo)) ?></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-claro" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> Registrar</button></div>
        </form>
    </div>
</div>
<?php endif; ?>
