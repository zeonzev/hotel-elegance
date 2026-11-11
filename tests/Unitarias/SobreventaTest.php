<?php

declare(strict_types=1);

namespace Pruebas;

use App\Servicios\ModeloDecision;
use PHPUnit\Framework\TestCase;

/**
 * Pruebas del modelo de decisión de sobreventa (CU-18):
 * valor esperado VE = p · I − (1 − p) · C y distribución binomial.
 */
final class SobreventaTest extends TestCase
{
    public function testValorEsperadoConLaFormulaDelInforme(): void
    {
        // 0.9 · 500 − 0.1 · 300 = 450 − 30 = 420
        $this->assertEqualsWithDelta(420.0, ModeloDecision::veSimple(0.9, 500, 300), 0.0001);
    }

    public function testSiElHuespedSiempreSePresentaSeGanaTodoElIngreso(): void
    {
        $this->assertEqualsWithDelta(500.0, ModeloDecision::veSimple(1.0, 500, 300), 0.0001);
    }

    public function testSiElHuespedNuncaSePresentaSePierdeElCosto(): void
    {
        $this->assertEqualsWithDelta(-300.0, ModeloDecision::veSimple(0.0, 500, 300), 0.0001);
    }

    public function testLasProbabilidadesDeLaBinomialSumanUno(): void
    {
        $distribucion = ModeloDecision::binomial(10, 0.8);

        $this->assertCount(11, $distribucion); // de 0 a 10 huéspedes
        $this->assertEqualsWithDelta(1.0, array_sum($distribucion), 0.000001);
    }

    public function testBinomialDeUnSoloHuesped(): void
    {
        $distribucion = ModeloDecision::binomial(1, 0.8);

        $this->assertEqualsWithDelta(0.2, $distribucion[0], 0.000001); // no se presenta
        $this->assertEqualsWithDelta(0.8, $distribucion[1], 0.000001); // se presenta
    }

    public function testLaAcumuladaHastaElTotalEsUno(): void
    {
        $distribucion = ModeloDecision::binomial(5, 0.7);

        $this->assertEqualsWithDelta(1.0, ModeloDecision::acumulada($distribucion, 5), 0.000001);
    }
}
