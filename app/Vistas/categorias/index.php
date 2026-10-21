<?php
/** @var \App\Core\Vista $this */
echo $this->parcial('parciales/encabezado', [
    'titulo' => 'Categorías de habitación',
    'cu' => 'CU-09',
    'descripcion' => 'Simple, Doble, Matrimonial y Suite: capacidad, tarifa base y comodidades que se muestran también en el sitio público.',
    'migas' => [['Hotel', null], ['Categorías', null]],
    'acciones' => '<a class="btn btn-claro" href="' . url('/panel/tarifas') . '"><i class="bi bi-tags"></i> Tarifas por temporada</a><a class="btn btn-primary" href="' . url('/panel/categorias/nueva') . '"><i class="bi bi-plus-lg"></i> Nueva categoría</a>',
]);
?>
<div class="row g-4-5 animar-entrada">
    <?php foreach ($categorias as $c): ?>
        <div class="col-md-6 col-xxl-3">
            <div class="tarjeta h-100 overflow-hidden tarjeta-interactiva d-flex flex-column">
                <div class="position-relative">
                    <img src="<?= asset('img/habitaciones/' . $c['imagen']) ?>" alt="<?= e($c['nombre']) ?>" class="w-100" style="height:180px;object-fit:cover">
                    <span class="position-absolute top-0 start-0 m-3"><?= $c['estado'] ? '<span class="insignia insignia-verde" style="background:#fff">Activa</span>' : '<span class="insignia insignia-gris" style="background:#fff">Inactiva</span>' ?></span>
                </div>
                <div class="tarjeta-cuerpo d-flex flex-column flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <h2 class="h3 mb-1"><?= e($c['nombre']) ?></h2>
                        <div class="text-end"><div class="fw-semibold fs-5 tabular"><?= e(bs($c['tarifa_base'], 0)) ?></div><div class="fs-xs texto-tenue">tarifa base / noche</div></div>
                    </div>
                    <p class="fs-sm texto-suave"><?= e($c['descripcion']) ?></p>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="insignia insignia-gris"><i class="bi bi-people"></i><?= (int) $c['capacidad'] ?> pers.</span>
                        <span class="insignia insignia-gris"><i class="bi bi-arrows-angle-expand"></i><?= (int) $c['superficie_m2'] ?> m²</span>
                        <span class="insignia insignia-gris"><i class="bi bi-door-closed"></i><?= (int) $c['habitaciones'] ?> hab. (<?= (int) $c['disponibles'] ?> libres)</span>
                    </div>
                    <?php if ($c['tarifa_hoy']['tipo'] !== 'BASE'): ?>
                        <div class="aviso aviso-oro py-2 px-3 mb-3 fs-xs"><i class="bi bi-tag"></i><div>Hoy rige <strong class="d-inline"><?= e($c['tarifa_hoy']['temporada']) ?></strong>: <?= e(bs($c['tarifa_hoy']['precio'], 0)) ?></div></div>
                    <?php endif; ?>
                    <div class="mt-auto d-flex gap-2">
                        <a class="btn btn-claro btn-sm flex-grow-1" href="<?= url('/panel/categorias/' . $c['id_categoria'] . '/editar') ?>"><i class="bi bi-pencil"></i> Editar</a>
                        <a class="btn btn-claro btn-sm" href="<?= url('/panel/habitaciones', ['categoria' => $c['id_categoria'], 'vista' => 'lista']) ?>" data-bs-toggle="tooltip" title="Ver habitaciones"><i class="bi bi-door-closed"></i></a>
                        <form method="post" action="<?= url('/panel/categorias/' . $c['id_categoria'] . '/eliminar') ?>" data-confirmar="¿Eliminar la categoría <?= e($c['nombre']) ?>?" data-confirmar-texto="Sólo es posible si no tiene habitaciones asignadas." data-confirmar-boton="Eliminar">
                            <?= csrf_campo() ?><button class="btn btn-peligro-suave btn-sm" type="submit" aria-label="Eliminar"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
