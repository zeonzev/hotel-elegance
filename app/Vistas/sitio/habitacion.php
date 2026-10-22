<?php /** @var \App\Core\Vista $this */ ?>
<section class="cabecera-pagina pb-5">
    <div class="contenedor"><a href="<?= url('/habitaciones') ?>" class="fs-sm" style="color:#A7AFBE"><i class="bi bi-arrow-left"></i> Todas las habitaciones</a><h1><?= e($c['nombre']) ?></h1><p><?= e($c['descripcion']) ?></p></div>
</section>
<section class="seccion pt-5">
    <div class="contenedor">
        <div class="galeria mb-5">
            <?php foreach ($c['galeria'] as $img): ?><img src="<?= asset('img/habitaciones/' . $img) ?>" alt="<?= e($c['nombre']) ?>" loading="lazy"><?php endforeach; ?>
        </div>
        <div class="row g-5">
            <div class="col-lg-7">
                <div class="rasgos fs-5 mb-4"><span><i class="bi bi-people"></i> Hasta <?= (int) $c['capacidad'] ?> personas</span><span><i class="bi bi-arrows-angle-expand"></i> <?= (int) $c['superficie_m2'] ?> m²</span><span><i class="bi bi-moon"></i> <?= e($c['camas']) ?></span></div>
                <h2 class="h3 mb-3">Comodidades</h2>
                <ul class="lista-comodidades mb-5"><?php foreach ($c['comodidades_lista'] as $x): ?><li><i class="bi bi-check2-circle"></i><?= e($x) ?></li><?php endforeach; ?><li><i class="bi bi-check2-circle"></i>Desayuno andino incluido</li><li><i class="bi bi-check2-circle"></i>Limpieza diaria</li></ul>
                <h2 class="h3 mb-3">Tarifa de los próximos días</h2>
                <div class="d-flex flex-wrap gap-2 mb-2">
                    <?php foreach ($proximos as $p): ?>
                        <div class="text-center rounded-4 p-2 border bg-white" style="min-width:82px"><div class="fs-xs texto-tenue"><?= e(fecha($p['fecha'], 'dia')) ?></div><div class="fw-semibold tabular"><?= e(bs($p['precio'], 0)) ?></div><?php if ($p['temporada']): ?><div class="fs-xs texto-oro text-truncate" style="max-width:76px" title="<?= e($p['temporada']) ?>"><?= e($p['temporada']) ?></div><?php endif; ?></div>
                    <?php endforeach; ?>
                </div>
                <p class="fs-sm texto-tenue">Las tarifas varían según la temporada (alta, baja y fechas especiales como Carnaval, Alasitas o el Gran Poder).</p>
            </div>
            <div class="col-lg-5">
                <form class="panel-reserva" action="<?= url('/reservar') ?>" method="get">
                    <input type="hidden" name="categoria" value="<?= (int) $c['id_categoria'] ?>">
                    <div class="d-flex align-items-baseline gap-2 mb-3"><span class="serif fs-1 fw-semibold"><?= e(bs($c['hoy']['precio'], 0)) ?></span><span class="texto-suave">/ noche hoy</span></div>
                    <div class="row g-2 mb-3">
                        <div class="col-6"><label class="form-label">Llegada</label><input type="date" class="form-control" name="ingreso" value="<?= date('Y-m-d', strtotime('+7 days')) ?>" min="<?= hoy() ?>" required></div>
                        <div class="col-6"><label class="form-label">Salida</label><input type="date" class="form-control" name="salida" value="<?= date('Y-m-d', strtotime('+9 days')) ?>" required></div>
                        <div class="col-12"><label class="form-label">Huéspedes</label><select class="form-select" name="personas"><?php for ($i = 1; $i <= (int) $c['capacidad']; $i++): ?><option value="<?= $i ?>"><?= $i ?> persona<?= $i > 1 ? 's' : '' ?></option><?php endfor; ?></select></div>
                    </div>
                    <button class="btn btn-oro btn-lg w-100" type="submit">Consultar y reservar</button>
                    <p class="fs-xs texto-tenue text-center mt-3 mb-0"><i class="bi bi-shield-check texto-oro"></i> Sin cargo hasta la confirmación de recepción</p>
                </form>
            </div>
        </div>
        <h2 class="h3 mt-5 mb-4">Otras habitaciones</h2>
        <div class="row g-4">
            <?php foreach ($otras as $o): if ((int) $o['id_categoria'] === (int) $c['id_categoria']) continue; ?>
                <div class="col-md-4"><a class="tarjeta-hab" href="<?= url('/habitaciones/' . $o['id_categoria']) ?>"><div class="foto"><img src="<?= asset('img/habitaciones/' . $o['galeria'][0]) ?>" alt="" loading="lazy"><span class="precio">desde <strong><?= e(bs($o['hoy']['precio'], 0)) ?></strong></span></div><div class="cuerpo"><h3 class="mb-0"><?= e($o['nombre']) ?></h3></div></a></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
