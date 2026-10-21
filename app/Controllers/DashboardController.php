<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Respuesta;
use App\Servicios\DashboardServicio;
use App\Servicios\ReservaServicio;

/**
 * Dashboard ejecutivo: ocupación, llegadas/salidas, ingresos, ADR, RevPAR,
 * canales y alertas de sobreventa.
 */
final class DashboardController extends Controlador
{
    public function index(): Respuesta
    {
        ReservaServicio::procesarNoShowVencidos();
        return $this->vista('dashboard/index', ['titulo' => 'Dashboard'] + DashboardServicio::datos());
    }
}
