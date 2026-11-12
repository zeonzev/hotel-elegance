<?php

declare(strict_types=1);

namespace Pruebas;

use App\Servicios\DemandaServicio;
use PHPUnit\Framework\TestCase;

/**
 * Pruebas del factor de tarifa sugerida por demanda:
 * f = 1 + α · (ocupación − meta), limitado entre un mínimo y un máximo.
 */
final class TarifaSugeridaTest extends TestCase
{
    // Valores por defecto del sistema: meta 70 %, α = 0.8, factor entre 0.85 y 1.40
    private const META = 0.70;
    private const ALFA = 0.8;
    private const MINIMO = 0.85;
    private const MAXIMO = 1.40;

    public function testSiLaOcupacionEsIgualALaMetaLaTarifaNoCambia(): void
    {
        $this->assertSame(1.0, DemandaServicio::factor(0.70, self::META, self::ALFA, self::MINIMO, self::MAXIMO));
    }

    public function testConMasDemandaLaTarifaSube(): void
    {
        // 1 + 0.8 · (0.90 − 0.70) = 1.16
        $this->assertEqualsWithDelta(1.16, DemandaServicio::factor(0.90, self::META, self::ALFA, self::MINIMO, self::MAXIMO), 0.001);
    }

    public function testConMenosDemandaLaTarifaBaja(): void
    {
        // 1 + 0.8 · (0.60 − 0.70) = 0.92
        $this->assertEqualsWithDelta(0.92, DemandaServicio::factor(0.60, self::META, self::ALFA, self::MINIMO, self::MAXIMO), 0.001);
    }

    public function testLaTarifaNuncaSubeMasDelMaximo(): void
    {
        $this->assertSame(self::MAXIMO, DemandaServicio::factor(1.0, self::META, 3.0, self::MINIMO, self::MAXIMO));
    }

    public function testLaTarifaNuncaBajaMenosDelMinimo(): void
    {
        $this->assertSame(self::MINIMO, DemandaServicio::factor(0.0, self::META, 3.0, self::MINIMO, self::MAXIMO));
    }

    public function testRedondeaLaTarifaADecenasDeBolivianos(): void
    {
        $this->assertSame(350.0, DemandaServicio::redondear(347));
        $this->assertSame(340.0, DemandaServicio::redondear(344));
    }
}
