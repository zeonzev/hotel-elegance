<?php
/** @var \App\Core\Vista $this */
echo $this->parcial('parciales/encabezado', [
    'titulo' => 'Configuración del hotel',
    'descripcion' => 'Datos que aparecen en recibos, reportes impresos y en el sitio público.',
    'migas' => [['Administración', null], ['Configuración', null]],
]);
$v = fn ($c) => e(anterior($c, $valores[$c]['valor'] ?? ''));
$campo = function (string $clave, string $etiqueta, string $tipo = 'text', string $col = 'col-md-6', string $ayuda = '') use ($v) {
    return '<div class="' . $col . '"><label class="form-label" for="' . $clave . '">' . e($etiqueta) . '</label><input type="' . $tipo . '" class="form-control' . clase_error($clave) . '" id="' . $clave . '" name="' . $clave . '" value="' . $v($clave) . '">' . mensaje_error($clave) . ($ayuda ? '<div class="form-text">' . e($ayuda) . '</div>' : '') . '</div>';
};
?>
<form method="post" action="<?= url('/panel/configuracion') ?>">
    <?= csrf_campo() ?>
    <div class="row g-4-5">
        <div class="col-xl-8">
            <div class="tarjeta">
                <div class="tarjeta-cuerpo">
                    <div class="seccion-form"><h3>Identidad</h3><p>Nombre comercial y datos legales.</p>
                        <div class="row g-3"><?= $campo('hotel_nombre', 'Nombre comercial') ?><?= $campo('hotel_razon_social', 'Razón social') ?><?= $campo('hotel_nit', 'NIT', 'text', 'col-md-4') ?></div>
                    </div>
                    <div class="seccion-form"><h3>Ubicación y contacto</h3><p>Se muestran en el pie del sitio público y en los recibos.</p>
                        <div class="row g-3"><?= $campo('hotel_direccion', 'Dirección', 'text', 'col-md-8') ?><?= $campo('hotel_ciudad', 'Ciudad', 'text', 'col-md-4') ?><?= $campo('hotel_telefono', 'Teléfono', 'text', 'col-md-4') ?><?= $campo('hotel_whatsapp', 'WhatsApp', 'text', 'col-md-4') ?><?= $campo('hotel_correo', 'Correo de reservas', 'email', 'col-md-4') ?></div>
                    </div>
                    <div class="seccion-form"><h3>Operación</h3><p>Horarios y política de anticipo.</p>
                        <div class="row g-3"><?= $campo('hora_checkin', 'Hora de check-in', 'time', 'col-md-4') ?><?= $campo('hora_checkout', 'Hora de check-out', 'time', 'col-md-4') ?><?= $campo('anticipo_porcentaje', 'Anticipo sugerido (%)', 'number', 'col-md-4', 'Se propone al registrar reservas.') ?></div>
                    </div>
                </div>
                <div class="tarjeta-pie text-end"><button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> Guardar configuración</button></div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="tarjeta mb-4">
                <div class="tarjeta-cuerpo">
                    <span class="icono-chip chip-oro mb-3"><i class="bi bi-sliders"></i></span>
                    <h3 class="h5 fw-semibold" style="font-family:var(--he-sans)">Otros parámetros</h3>
                    <p class="fs-sm texto-suave">Los parámetros del modelo de decisión y de la estimación de tarifas se configuran en sus propios módulos.</p>
                    <div class="d-grid gap-2">
                        <a class="btn btn-claro" href="<?= url('/panel/sobreventa') ?>#configuracion"><i class="bi bi-shield-check"></i> Política de sobreventa</a>
                        <a class="btn btn-claro" href="<?= url('/panel/tarifas') ?>"><i class="bi bi-tags"></i> Tarifas por temporada</a>
                    </div>
                </div>
            </div>
            <div class="tarjeta">
                <div class="tarjeta-cuerpo fs-sm">
                    <h3 class="h6 fw-semibold" style="font-family:var(--he-sans)">Información del sistema</h3>
                    <dl class="datos mt-3">
                        <dt>Versión</dt><dd>1.0.0</dd>
                        <dt>PHP</dt><dd><?= e(PHP_VERSION) ?></dd>
                        <dt>Base de datos</dt><dd><?= e(\App\Core\BaseDatos::valor('SELECT VERSION()')) ?></dd>
                        <dt>Zona horaria</dt><dd>America/La_Paz (UTC−4)</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</form>
