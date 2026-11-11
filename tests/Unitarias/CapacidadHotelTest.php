<?php

declare(strict_types=1);

namespace Pruebas;

use App\Servicios\DisponibilidadServicio;

/**
 * Pruebas con PDO de la capacidad del hotel: habitaciones que se pueden
 * vender (todas las que no están en mantenimiento).
 */
final class CapacidadHotelTest extends PruebaBaseDatos
{
    public function testCapacidadCoincideConLasHabitacionesFueraDeMantenimiento(): void
    {
        $esperado = (int) $this->valor("SELECT COUNT(*) FROM habitacion WHERE estado <> 'MANTENIMIENTO'");

        $this->assertSame($esperado, DisponibilidadServicio::capacidad());
    }

    public function testCapacidadNuncaSuperaElTotalDeHabitaciones(): void
    {
        $this->assertLessThanOrEqual(DisponibilidadServicio::totalHabitaciones(), DisponibilidadServicio::capacidad());
    }

    public function testEnviarUnaHabitacionAMantenimientoReduceLaCapacidad(): void
    {
        $antes = DisponibilidadServicio::capacidad();
        $id = (int) $this->valor("SELECT id_habitacion FROM habitacion WHERE estado = 'DISPONIBLE' ORDER BY numero LIMIT 1");
        $this->assertGreaterThan(0, $id, 'Se necesita al menos una habitación disponible');

        $this->pdo->prepare("UPDATE habitacion SET estado = 'MANTENIMIENTO' WHERE id_habitacion = ?")->execute([$id]);

        $this->assertSame($antes - 1, DisponibilidadServicio::capacidad());
    }

    public function testDevolverUnaHabitacionDeMantenimientoAumentaLaCapacidad(): void
    {
        $antes = DisponibilidadServicio::capacidad();
        $id = (int) $this->valor("SELECT id_habitacion FROM habitacion WHERE estado = 'MANTENIMIENTO' LIMIT 1");
        if ($id === 0) {
            $this->markTestSkipped('No hay habitaciones en mantenimiento en los datos de demostración');
        }

        $this->pdo->prepare("UPDATE habitacion SET estado = 'DISPONIBLE' WHERE id_habitacion = ?")->execute([$id]);

        $this->assertSame($antes + 1, DisponibilidadServicio::capacidad());
    }
}
