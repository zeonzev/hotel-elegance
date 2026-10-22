<?php /** @var \App\Core\Vista $this */ ?>
<section class="cabecera-pagina">
    <div class="contenedor"><span class="sobretitulo">Habitaciones y suites</span><h1>Descanso a la altura de La Paz</h1><p>Cuatro categorías pensadas para cada tipo de viaje. Todas incluyen calefacción, Wi-Fi de alta velocidad y desayuno andino.</p></div>
</section>
<section class="seccion">
    <div class="contenedor">
        <?php foreach ($categorias as $i => $c): ?>
            <div class="row g-5 align-items-center mb-5 pb-4 revelar<?= $i % 2 ? ' flex-row-reverse' : '' ?>">
                <div class="col-lg-6"><div class="imagen-marco" style="height: 420px"><img src="<?= asset('img/habitaciones/' . $c['galeria'][0]) ?>" alt="Habitación <?= e($c['nombre']) ?>" loading="lazy"></div></div>
                <div class="col-lg-6">
                    <span class="sobretitulo">Desde <?= e(bs($c['hoy']['precio'], 0)) ?> por noche</span>
                    <h2 class="display-5 mt-2"><?= e($c['nombre']) ?></h2>
                    <div class="rasgos fs-6"><span><i class="bi bi-people"></i> Hasta <?= (int) $c['capacidad'] ?> personas</span><span><i class="bi bi-arrows-angle-expand"></i> <?= (int) $c['superficie_m2'] ?> m²</span><span><i class="bi bi-moon"></i> <?= e($c['camas']) ?></span></div>
                    <p class="texto-suave fs-5"><?= e($c['descripcion']) ?></p>
                    <ul class="lista-comodidades fs-sm mb-4"><?php foreach (array_slice($c['comodidades_lista'], 0, 6) as $x): ?><li><i class="bi bi-check2"></i><?= e($x) ?></li><?php endforeach; ?></ul>
                    <div class="d-flex flex-wrap gap-2"><a class="btn btn-primary btn-lg" href="<?= url('/reservar', ['categoria' => $c['id_categoria']]) ?>">Reservar</a><a class="btn btn-claro btn-lg" href="<?= url('/habitaciones/' . $c['id_categoria']) ?>">Ver detalles</a></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
