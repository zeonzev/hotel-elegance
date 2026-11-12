<?php

declare(strict_types=1);

namespace Pruebas;

use App\Core\ExcepcionValidacion;
use App\Servicios\DisponibilidadServicio;

/**
 * Pruebas con PDO de la consulta de habitaciones disponibles (CU-12).
 * Se usan fechas del año 2030 porque ahí todavía no hay reservas.
 */
final class HabitacionesDisponiblesTest extends PruebaBaseDatos
{
    private const INGRESO = '2030-03-01';
    private const SALIDA = '2030-03-03';

    public function testSinReservasEstanLibresTodasLasHabitacionesFueraDeMantenimiento(): void
    {
        $esperado = (int) $this->valor("SELECT COUNT(*) FROM habitacion WHERE estado <> 'MANTENIMIENTO'");

        $libres = DisponibilidadServicio::habitacionesDisponibles(self::INGRESO, self::SALIDA);

        $this->assertCount($esperado, $libres);
    }

    public function testUnaHabitacionEnMantenimientoNoApareceComoDisponible(): void
    {
        $id = (int) $this->valor("SELECT id_habitacion FROM habitacion WHERE estado = 'DISPONIBLE' LIMIT 1");
        $this->pdo->prepare("UPDATE habitacion SET estado = 'MANTENIMIENTO' WHERE id_habitacion = ?")->execute([$id]);

        $libres = DisponibilidadServicio::habitacionesDisponibles(self::INGRESO, self::SALIDA);

        $this->assertNotContains($id, array_map('intval', array_column($libres, 'id_habitacion')));
    }

    public function testCadaHabitacionLibreTraeSuCotizacion(): void
    {
        $libres = DisponibilidadServicio::habitacionesDisponibles(self::INGRESO, self::SALIDA);

        $this->assertNotEmpty($libres);
        $this->assertArrayHasKey('cotizacion', $libres[0]);
    }

    public function testSiLaSalidaEsAntesDelIngresoLanzaError(): void
    {
        $this->expectException(ExcepcionValidacion::class);

        DisponibilidadServicio::habitacionesDisponibles('2030-03-05', '2030-03-01');
    }
}
