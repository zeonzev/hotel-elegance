<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 pb-4 mb-4" style="border-bottom: 2px solid #C6A15B">
    <div class="marca-doc">
        <img src="<?= asset('img/logo-marca.svg') ?>" alt="">
        <div><strong><?= e(ajuste('hotel_nombre', 'Hotel Elegance')) ?></strong><span>La Paz · Bolivia</span></div>
    </div>
    <div class="text-end fs-sm" style="color:#5E6675; line-height:1.5">
        <div class="fw-semibold" style="color:#1A2230"><?= e(ajuste('hotel_razon_social', 'Hotel Elegance S.R.L.')) ?> · NIT <?= e(ajuste('hotel_nit', '')) ?></div>
        <div><?= e(ajuste('hotel_direccion', '')) ?>, <?= e(ajuste('hotel_ciudad', '')) ?></div>
        <div><?= e(ajuste('hotel_telefono', '')) ?> · <?= e(ajuste('hotel_correo', '')) ?></div>
    </div>
</div>
