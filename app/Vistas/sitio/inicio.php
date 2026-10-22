<?php /** @var \App\Core\Vista $this */ ?>
<section class="hero" style="background-image: url('<?= asset('img/hotel/fachada-noche.jpg') ?>')">
    <div class="contenedor">
        <span class="sobretitulo">Hotel boutique · Miraflores, La Paz</span>
        <h1>Elegancia andina, <em>a 3 640 metros</em> de altura.</h1>
        <p>Habitaciones serenas con vista al Illimani, desayuno de productos locales y una recepción que le espera las 24 horas.</p>
        <form class="buscador-reserva" action="<?= url('/reservar') ?>" method="get">
            <label><span>Llegada</span><input type="date" name="ingreso" value="<?= date('Y-m-d', strtotime('+7 days')) ?>" min="<?= hoy() ?>" required></label>
            <label><span>Salida</span><input type="date" name="salida" value="<?= date('Y-m-d', strtotime('+9 days')) ?>" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required></label>
            <label><span>Huéspedes</span><select name="personas"><?php for ($i = 1; $i <= 4; $i++): ?><option value="<?= $i ?>"<?= $i === 2 ? ' selected' : '' ?>><?= $i ?> persona<?= $i > 1 ? 's' : '' ?></option><?php endfor; ?></select></label>
            <button type="submit"><i class="bi bi-search"></i> Ver disponibilidad</button>
        </form>
        <div class="hero-indicadores">
            <div><strong>24</strong><span>habitaciones</span></div>
            <div><strong>4</strong><span>categorías</span></div>
            <div><strong>4,9</strong><span>valoración</span></div>
            <div><strong>24 h</strong><span>recepción</span></div>
        </div>
    </div>
</section>

<section class="seccion">
    <div class="contenedor">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6 revelar position-relative">
                <div class="imagen-marco" style="height: 520px"><img src="<?= asset('img/hotel/lobby.jpg') ?>" alt="Lobby del Hotel Elegance" loading="lazy"></div>
                <div class="sello d-none d-md-block"><strong>12</strong>años recibiendo<br>viajeros en La Paz</div>
            </div>
            <div class="col-lg-6 revelar">
                <span class="sobretitulo">Nuestra casa</span>
                <h2 class="display-5 mt-2 mb-4">Un refugio cálido entre la ciudad y la cordillera</h2>
                <p class="texto-suave fs-5">En la Avenida Saavedra, a minutos del centro histórico y del Teleférico, el Hotel Elegance combina la hospitalidad paceña con el confort contemporáneo: calefacción en cada habitación, ropa de cama de algodón egipcio y un equipo que conoce cada rincón de la ciudad.</p>
                <div class="row g-4 mt-2">
                    <div class="col-6"><i class="bi bi-shield-check fs-3 texto-oro"></i><h3 class="h6 fw-semibold mt-2" style="font-family:var(--he-sans)">Reserva confirmada al instante</h3><p class="fs-sm texto-suave mb-0">Su solicitud llega directo a nuestra recepción.</p></div>
                    <div class="col-6"><i class="bi bi-heart fs-3 texto-oro"></i><h3 class="h6 fw-semibold mt-2" style="font-family:var(--he-sans)">Atención personalizada</h3><p class="fs-sm texto-suave mb-0">Mate de coca de bienvenida y consejos para la altura.</p></div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="seccion pt-0">
    <div class="contenedor">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-5">
            <div class="seccion-titulo mb-0 revelar"><span class="sobretitulo">Habitaciones</span><h2>Cuatro maneras de descansar</h2><p>Desde la habitación simple para el viajero de negocios hasta la suite con vista panorámica al Illimani.</p></div>
            <a href="<?= url('/habitaciones') ?>" class="btn btn-claro btn-lg">Ver todas <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="row g-4">
            <?php foreach ($categorias as $c): ?>
                <div class="col-md-6 col-xl-3 revelar">
                    <a class="tarjeta-hab" href="<?= url('/habitaciones/' . $c['id_categoria']) ?>">
                        <div class="foto"><img src="<?= asset('img/habitaciones/' . $c['galeria'][0]) ?>" alt="Habitación <?= e($c['nombre']) ?>" loading="lazy"><span class="precio">desde <strong><?= e(bs($c['hoy']['precio'], 0)) ?></strong> / noche</span></div>
                        <div class="cuerpo">
                            <h3><?= e($c['nombre']) ?></h3>
                            <div class="rasgos"><span><i class="bi bi-people"></i> <?= (int) $c['capacidad'] ?> pers.</span><span><i class="bi bi-arrows-angle-expand"></i> <?= (int) $c['superficie_m2'] ?> m²</span><span><i class="bi bi-moon"></i> <?= e($c['camas']) ?></span></div>
                            <p class="texto-suave fs-sm flex-grow-1"><?= e($c['descripcion']) ?></p>
                            <span class="fw-semibold texto-oro fs-sm">Descubrir <i class="bi bi-arrow-right"></i></span>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="seccion" id="servicios" style="background: var(--he-arena-100)">
    <div class="contenedor">
        <div class="seccion-titulo centrado revelar"><span class="sobretitulo">Servicios</span><h2>Todo lo necesario para una estadía impecable</h2></div>
        <div class="row g-4">
            <?php foreach ([['bi-cup-hot', 'Desayuno andino', 'Buffet con quinua, api, frutas del Yungas y café de Caranavi.'], ['bi-wifi', 'Wi-Fi de alta velocidad', 'Fibra óptica en todo el hotel, ideal para viajes de trabajo.'], ['bi-airplane', 'Traslado al aeropuerto', 'Coordinamos su traslado desde El Alto, de día o de noche.'], ['bi-thermometer-sun', 'Calefacción y oxígeno', 'Habitaciones climatizadas y asistencia para la altura.'], ['bi-compass', 'Excursiones', 'Valle de la Luna, Tiwanaku, lago Titicaca y Chacaltaya.'], ['bi-bell', 'Recepción 24 horas', 'Personal bilingüe para lo que necesite, a cualquier hora.']] as [$i, $t, $d]): ?>
                <div class="col-md-6 col-lg-4 revelar"><div class="servicio"><i class="bi <?= $i ?>"></i><h3><?= $t ?></h3><p><?= $d ?></p></div></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="seccion seccion-oscura" id="la-paz">
    <div class="contenedor">
        <div class="row g-5 align-items-center">
            <div class="col-lg-7 revelar">
                <div class="panorama" style="background-image: url('<?= asset('img/hotel/la-paz-panorama.jpg') ?>')">
                    <div class="contenido"><span class="sobretitulo" style="color:var(--he-oro-300)">Nuestra ciudad</span><h2 class="display-6 mb-0">La Paz, bajo la mirada del Illimani</h2></div>
                </div>
            </div>
            <div class="col-lg-5 revelar">
                <div class="lugar"><i class="bi bi-cable-car"></i><div><strong>Mi Teleférico</strong><span>La red de teleféricos urbanos más grande del mundo, a 10 minutos.</span></div></div>
                <div class="lugar"><i class="bi bi-bank"></i><div><strong>Calle Jaén y casco histórico</strong><span>Museos, iglesias coloniales y la Plaza Murillo.</span></div></div>
                <div class="lugar"><i class="bi bi-moon-stars"></i><div><strong>Valle de la Luna</strong><span>Formaciones de arcilla a 30 minutos del hotel.</span></div></div>
                <div class="lugar border-0"><i class="bi bi-water"></i><div><strong>Lago Titicaca</strong><span>Excursión de un día a Copacabana y la Isla del Sol.</span></div></div>
            </div>
        </div>
        <?php if ($especiales): ?>
            <div class="mt-5 pt-3">
                <h3 class="h4 mb-4" style="font-family:var(--he-serif);color:#fff">Fechas especiales en La Paz</h3>
                <div class="row g-3">
                    <?php foreach ($especiales as $t): ?>
                        <div class="col-sm-6 col-lg-3 revelar"><div class="evento"><div class="fecha"><?= e(fecha($t['fecha_inicio'], 'dia')) ?></div><strong class="d-block text-white"><?= e($t['nombre']) ?></strong><span class="fs-sm"><?= e($t['descripcion']) ?></span></div></div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="seccion">
    <div class="contenedor">
        <div class="seccion-titulo centrado revelar"><span class="sobretitulo">Huéspedes</span><h2>Lo que dicen de nosotros</h2></div>
        <div class="row g-4">
            <?php foreach ([['Una atención impecable. El mate de coca al llegar y la vista al Illimani desde la suite hicieron que nuestro aniversario fuera inolvidable.', 'Daniela Rojas', 'Cochabamba'], ['Perfecto para viajes de trabajo: escritorio cómodo, internet rápido y a pasos de las oficinas de la Avenida Arce.', 'Martín González', 'Buenos Aires'], ['Nos ayudaron a organizar el tour al Titicaca y a adaptarnos a la altura. Volveremos para el Gran Poder.', 'Camille Dubois', 'Lyon']] as [$texto, $autor, $origen]): ?>
                <div class="col-md-4 revelar"><div class="testimonio"><div class="estrellas">★★★★★</div><blockquote>«<?= e($texto) ?>»</blockquote><div class="persona"><span class="avatar avatar-noche"><?= e(iniciales($autor)) ?></span><div><strong><?= e($autor) ?></strong><div class="fs-sm texto-tenue"><?= e($origen) ?></div></div></div></div></div>
            <?php endforeach; ?>
        </div>
        <div class="cta mt-5 revelar">
            <div class="row align-items-center g-4">
                <div class="col-lg-8"><span class="sobretitulo" style="color:var(--he-oro-300)">Reserve directo</span><h2 class="mt-2 mb-2">Su habitación le espera en Miraflores</h2><p class="mb-0" style="color:rgba(255,255,255,.75)">Consulte disponibilidad en línea y reciba la confirmación de nuestra recepción.</p></div>
                <div class="col-lg-4 text-lg-end"><a class="btn btn-oro btn-lg" href="<?= url('/reservar') ?>">Reservar ahora <i class="bi bi-arrow-right"></i></a></div>
            </div>
        </div>
    </div>
</section>
