<?php

declare(strict_types=1);

namespace Pruebas;

use App\Servicios\ReservaServicio;

/**
 * Pruebas con PDO del total pagado de una reserva: suma de los pagos
 * registrados en la tabla pago.
 */
final class PagosReservaTest extends PruebaBaseDatos
{
    public function testTotalPagadoCoincideConLaSumaDeLaTablaPago(): void
    {
        $id = (int) $this->valor('SELECT id_reserva FROM pago LIMIT 1');
        $suma = (float) $this->valor('SELECT SUM(monto) FROM pago WHERE id_reserva = ?', [$id]);

        $this->assertEqualsWithDelta($suma, ReservaServicio::pagado($id), 0.001);
    }

    public function testReservaSinPagosDevuelveCero(): void
    {
        $id = (int) $this->valor('SELECT r.id_reserva FROM reserva r WHERE NOT EXISTS (SELECT 1 FROM pago p WHERE p.id_reserva = r.id_reserva) LIMIT 1');

        $this->assertSame(0.0, ReservaServicio::pagado($id));
    }

    public function testRegistrarUnPagoAumentaElTotalPagado(): void
    {
        $id = (int) $this->valor('SELECT id_reserva FROM reserva LIMIT 1');
        $antes = ReservaServicio::pagado($id);

        // 1 = primer método de pago registrado en el sistema
        $this->pdo->prepare("INSERT INTO pago (id_reserva, id_metodo_pago, concepto, monto) VALUES (?, 1, 'ANTICIPO', 150.00)")
            ->execute([$id]);

        $this->assertEqualsWithDelta($antes + 150, ReservaServicio::pagado($id), 0.001);
    }

    public function testReservaQueNoExisteDevuelveCero(): void
    {
        $this->assertSame(0.0, ReservaServicio::pagado(999999));
    }
}
