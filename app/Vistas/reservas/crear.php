<?php
/** @var \App\Core\Vista $this */
$ingreso = (string) anterior('fecha_ingreso', $_GET['ingreso'] ?? ($solicitud['fecha_ingreso'] ?? hoy()));
$salida = (string) anterior('fecha_salida', $_GET['salida'] ?? ($solicitud['fecha_salida'] ?? date('Y-m-d', strtotime('+2 days'))));
$personas = (int) anterior('cantidad_personas', $solicitud['cantidad_personas'] ?? 2);
$preseleccion = array_map('intval', (array) anterior('habitaciones', isset($_GET['habitacion']) ? [(int) $_GET['habitacion']] : []));
$estado = anterior('estado', 'CONFIRMADA');
$canalPorDefecto = $solicitud ? 4 : 3;
$datosJs = [
    'preseleccion' => $preseleccion,
    'sobreventa' => (int) anterior('sobreventa_categoria', 0),
    'categoriaSolicitud' => $solicitud ? (int) $solicitud['id_categoria'] : null,
    'anticipoPorcentaje' => $anticipo,
    'huesped' => $huesped ? ['id_huesped' => (int) $huesped['id_huesped'], 'nombres' => $huesped['nombres'], 'apellidos' => $huesped['apellidos'], 'nro_documento' => $huesped['nro_documento'], 'sigla' => $huesped['sigla'] ?? '', 'nacionalidad' => $huesped['nacionalidad'], 'ciudad_procedencia' => $huesped['ciudad_procedencia'], 'telefono' => $huesped['telefono'], 'correo' => $huesped['correo']] : null,
    'idHuesped' => (int) anterior('id_huesped', 0),
];
echo $this->parcial('parciales/encabezado', [
    'titulo' => 'Nueva reserva',
    'cu' => 'CU-11 · CU-12 · CU-05 · CU-19',
    'descripcion' => 'Indique las fechas, elija habitaciones disponibles, identifique al huésped y confirme. La tarifa se calcula noche por noche según la temporada.',
    'migas' => [['Reservas', '/panel/reservas'], ['Nueva', null]],
]);
?>
<?php if ($solicitud): ?>
    <div class="aviso aviso-oro mb-4"><i class="bi bi-envelope-paper"></i><div class="flex-grow-1"><strong>Atendiendo la solicitud web de <?= e($solicitud['nombres'] . ' ' . $solicitud['apellidos']) ?></strong>
        <span class="fs-sm"><?= e($solicitud['correo']) ?> · <?= e($solicitud['telefono']) ?> · <?= (int) $solicitud['cantidad_personas'] ?> persona(s)<?= $solicitud['mensaje'] ? ' · «' . e($solicitud['mensaje']) . '»' : '' ?></span></div></div>
<?php endif; ?>

<form method="post" action="<?= url('/panel/reservas') ?>" id="form-reserva" novalidate>
    <?= csrf_campo() ?>
    <input type="hidden" name="id_huesped" id="id_huesped" value="<?= $datosJs['idHuesped'] ?: ($huesped['id_huesped'] ?? '') ?>">
    <input type="hidden" name="alerta_vista" id="alerta_vista" value="">
    <?php if ($solicitud): ?><input type="hidden" name="id_solicitud" value="<?= (int) $solicitud['id_solicitud'] ?>"><?php endif; ?>

    <div class="row g-4-5">
        <div class="col-xl-8">
            <div class="pasos mb-3" id="pasos">
                <span class="paso activo" data-paso="1"><span class="num">1</span>Fechas</span>
                <span class="paso" data-paso="2"><span class="num">2</span>Habitaciones</span>
                <span class="paso" data-paso="3"><span class="num">3</span>Huésped</span>
                <span class="paso" data-paso="4"><span class="num">4</span>Confirmación</span>
            </div>

            <div class="tarjeta mb-4">
                <div class="tarjeta-encabezado"><div><h2>1 · Fechas y personas</h2><div class="subtitulo">CU-12: la salida debe ser posterior al ingreso</div></div></div>
                <div class="tarjeta-cuerpo row g-3 align-items-end">
                    <div class="col-sm-6 col-lg-3"><label class="form-label" for="fecha_ingreso">Ingreso</label><input type="date" class="form-control<?= clase_error('fecha_ingreso') ?>" id="fecha_ingreso" name="fecha_ingreso" value="<?= e($ingreso) ?>" min="<?= hoy() ?>" required></div>
                    <div class="col-sm-6 col-lg-3"><label class="form-label" for="fecha_salida">Salida</label><input type="date" class="form-control<?= clase_error('fecha_salida') ?>" id="fecha_salida" name="fecha_salida" value="<?= e($salida) ?>" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required></div>
                    <div class="col-sm-6 col-lg-2"><label class="form-label" for="cantidad_personas">Personas</label><input type="number" class="form-control<?= clase_error('cantidad_personas') ?>" id="cantidad_personas" name="cantidad_personas" value="<?= $personas ?>" min="1" max="20" required></div>
                    <div class="col-sm-6 col-lg-4"><button type="button" class="btn btn-primary w-100" id="btn-consultar"><i class="bi bi-search"></i> Consultar disponibilidad</button></div>
                    <div class="col-12"><?= mensaje_error('fecha_ingreso') ?><?= mensaje_error('fecha_salida') ?><?= mensaje_error('cantidad_personas') ?><div class="invalid-feedback d-block" id="error-fechas"></div></div>
                </div>
            </div>

            <div id="alerta-sobreventa"></div>

            <div class="tarjeta mb-4">
                <div class="tarjeta-encabezado"><div><h2>2 · Habitaciones disponibles</h2><div class="subtitulo">Puede elegir una o más habitaciones</div></div><span class="insignia insignia-gris" id="contador-libres">—</span></div>
                <?php if ($e = error_campo('habitaciones')): ?><div class="px-4 pt-3"><div class="aviso aviso-rojo"><i class="bi bi-exclamation-octagon"></i><div><strong><?= e($e) ?></strong></div></div></div><?php endif; ?>
                <div class="tarjeta-cuerpo" id="lista-habitaciones">
                    <div class="text-center texto-tenue py-4"><i class="bi bi-calendar-range fs-3 d-block mb-2"></i>Indique las fechas y presione «Consultar disponibilidad».</div>
                </div>
            </div>

            <div class="tarjeta mb-4">
                <div class="tarjeta-encabezado"><div><h2>3 · Huésped titular</h2><div class="subtitulo">Busque por documento o nombre; si es nuevo, regístrelo aquí mismo (CU-05)</div></div></div>
                <div class="tarjeta-cuerpo">
                    <?php if ($e = error_campo('id_huesped')): ?><div class="aviso aviso-rojo mb-3"><i class="bi bi-person-x"></i><div><strong><?= e($e) ?></strong></div></div><?php endif; ?>
                    <div id="huesped-seleccionado" class="mb-3"></div>
                    <div id="huesped-buscador">
                        <div class="con-icono"><i class="bi bi-search"></i><input class="form-control" type="search" id="buscar-huesped" placeholder="Número de documento o nombre del huésped" autocomplete="off"></div>
                        <div id="resultados-huesped" class="mt-2"></div>
                        <button type="button" class="btn btn-claro btn-sm mt-2" data-bs-toggle="collapse" data-bs-target="#nuevo-huesped"><i class="bi bi-person-plus"></i> Registrar huésped nuevo</button>
                        <div class="collapse mt-3" id="nuevo-huesped">
                            <div class="rounded-4 p-3" style="background: var(--he-superficie-2); border: 1px dashed var(--he-borde-fuerte)">
                                <div class="row g-2">
                                    <div class="col-md-4"><label class="form-label">Tipo</label><select class="form-select" data-h="id_tipo_documento"><?php foreach ($tipos as $t): ?><option value="<?= $t['id_tipo_documento'] ?>"<?= seleccionado($solicitud['id_tipo_documento'] ?? 1, $t['id_tipo_documento']) ?>><?= e($t['sigla']) ?> · <?= e($t['nombre']) ?></option><?php endforeach; ?></select></div>
                                    <div class="col-md-4"><label class="form-label">Documento *</label><input class="form-control" data-h="nro_documento" value="<?= e($solicitud['nro_documento'] ?? '') ?>"></div>
                                    <div class="col-md-4"><label class="form-label">Nacionalidad *</label><input class="form-control" data-h="nacionalidad" value="<?= e($solicitud['nacionalidad'] ?? 'Boliviana') ?>" list="lista-nac"><datalist id="lista-nac"><?php foreach (['Boliviana', 'Peruana', 'Argentina', 'Chilena', 'Brasileña', 'Colombiana', 'Española', 'Francesa', 'Alemana', 'Estadounidense'] as $n): ?><option value="<?= $n ?>"><?php endforeach; ?></datalist></div>
                                    <div class="col-md-6"><label class="form-label">Nombres *</label><input class="form-control" data-h="nombres" value="<?= e($solicitud['nombres'] ?? '') ?>"></div>
                                    <div class="col-md-6"><label class="form-label">Apellidos *</label><input class="form-control" data-h="apellidos" value="<?= e($solicitud['apellidos'] ?? '') ?>"></div>
                                    <div class="col-md-4"><label class="form-label">Procedencia *</label><input class="form-control" data-h="ciudad_procedencia" value="<?= e($solicitud['ciudad_procedencia'] ?? '') ?>" placeholder="Ciudad"></div>
                                    <div class="col-md-4"><label class="form-label">Teléfono</label><input class="form-control" data-h="telefono" value="<?= e($solicitud['telefono'] ?? '') ?>"></div>
                                    <div class="col-md-4"><label class="form-label">Correo</label><input class="form-control" data-h="correo" value="<?= e($solicitud['correo'] ?? '') ?>"></div>
                                    <div class="col-12 d-flex align-items-center gap-2"><button type="button" class="btn btn-oro btn-sm" id="btn-registrar-huesped"><i class="bi bi-check2"></i> Registrar y usar</button><span class="fs-xs text-danger" id="error-huesped"></span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tarjeta">
                <div class="tarjeta-encabezado"><div><h2>4 · Confirmación</h2><div class="subtitulo">Canal, estado, anticipo y observaciones</div></div></div>
                <div class="tarjeta-cuerpo row g-3">
                    <div class="col-12">
                        <label class="form-label">Canal de la reserva</label>
                        <div class="row g-2">
                            <?php foreach ($canales as $c): ?>
                                <div class="col-6 col-md-4 col-xxl-2"><label class="opcion-tarjeta"><input type="radio" name="id_canal" value="<?= $c['id_canal'] ?>"<?= marcado((int) anterior('id_canal', $canalPorDefecto) === (int) $c['id_canal']) ?>><span class="contenido text-center py-2"><i class="bi <?= e($c['icono']) ?> d-block fs-5 texto-oro"></i><span class="fs-xs fw-semibold"><?= e($c['nombre']) ?></span></span></label></div>
                            <?php endforeach; ?>
                        </div>
                        <?= mensaje_error('id_canal') ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Estado inicial</label>
                        <div class="segmentos w-100">
                            <label class="flex-fill"><input type="radio" class="btn-check" name="estado" value="CONFIRMADA" id="est-conf"<?= marcado($estado === 'CONFIRMADA') ?>><span class="btn btn-sm w-100 <?= $estado === 'CONFIRMADA' ? 'active' : '' ?>" data-seg>Confirmada</span></label>
                            <label class="flex-fill"><input type="radio" class="btn-check" name="estado" value="PENDIENTE" id="est-pend"<?= marcado($estado === 'PENDIENTE') ?>><span class="btn btn-sm w-100 <?= $estado === 'PENDIENTE' ? 'active' : '' ?>" data-seg>Pendiente</span></label>
                        </div>
                    </div>
                    <div class="col-md-6"><label class="form-label" for="anticipo">Anticipo (Bs) <span class="opcional">sugerido <?= numero($anticipo) ?> %</span></label><input type="number" step="0.01" min="0" class="form-control<?= clase_error('anticipo') ?>" id="anticipo" name="anticipo" value="<?= e(anterior('anticipo')) ?>" placeholder="0,00"><?= mensaje_error('anticipo') ?></div>
                    <div class="col-md-6"><label class="form-label" for="id_metodo_pago">Método del anticipo</label><select class="form-select" id="id_metodo_pago" name="id_metodo_pago"><?php foreach ($metodos as $m): ?><option value="<?= $m['id_metodo_pago'] ?>" data-ref="<?= (int) $m['requiere_referencia'] ?>"<?= seleccionado(anterior('id_metodo_pago', 2), $m['id_metodo_pago']) ?>><?= e($m['nombre']) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-6"><label class="form-label" for="referencia">Referencia / N.º de transacción</label><input class="form-control" id="referencia" name="referencia" value="<?= e(anterior('referencia')) ?>" maxlength="80"></div>
                    <div class="col-12"><label class="form-label" for="observaciones">Observaciones</label><textarea class="form-control" id="observaciones" name="observaciones" rows="2" maxlength="1000" placeholder="Hora estimada de llegada, pedidos especiales…"><?= e(anterior('observaciones')) ?></textarea></div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="tarjeta resumen-reserva">
                <div class="tarjeta-encabezado"><h2>Resumen</h2><span class="sobretitulo">Bolivianos</span></div>
                <div class="tarjeta-cuerpo">
                    <div class="resumen-linea"><span class="texto-suave">Estadía</span><strong id="r-fechas">—</strong></div>
                    <div class="resumen-linea"><span class="texto-suave">Noches</span><strong id="r-noches">—</strong></div>
                    <div class="resumen-linea"><span class="texto-suave">Huésped</span><strong id="r-huesped" class="text-end truncar" style="max-width:60%">Sin seleccionar</strong></div>
                    <hr>
                    <div id="r-habitaciones" class="fs-sm"><span class="texto-tenue">Ninguna habitación seleccionada</span></div>
                    <div class="resumen-total"><span class="texto-suave">Total</span><strong class="tabular" id="r-total">Bs 0,00</strong></div>
                    <div class="fs-xs texto-tenue mt-1" id="r-anticipo"></div>
                    <button type="submit" class="btn btn-oro btn-lg w-100 mt-4" id="btn-guardar"><i class="bi bi-check2-circle"></i> Registrar reserva</button>
                    <a href="<?= url('/panel/reservas') ?>" class="btn btn-fantasma w-100 mt-2">Cancelar</a>
                </div>
            </div>
        </div>
    </div>
</form>

<?php $this->seccion('scripts') ?>
<script>window.HE_RESERVA = <?= json_encode($datosJs, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
<script src="<?= asset('js/reserva.js') ?>"></script>
<?php $this->fin() ?>
