<?php

declare(strict_types=1);

namespace Pruebas;

use App\Servicios\CadenaMarkov;
use App\Servicios\MarkovServicio;
use App\Servicios\ModeloDecision;
use PHPUnit\Framework\TestCase;

/**
 * Pruebas de Investigación Operativa II: Cadenas de Markov del ciclo de una reserva
 * y criterios de Teoría de Decisiones. Se comprueban con los números de los aportes
 * entregados (Hito 3 y Segundo Aporte).
 */
final class CadenaMarkovTest extends TestCase
{
    public function testCadaFilaDeLaMatrizSumaUno(): void
    {
        $conteos = CadenaMarkov::conteosVacios();
        $conteos['C'] = ['C' => 10, 'H' => 75, 'X' => 10, 'N' => 5, 'F' => 0];
        $conteos['H'] = ['C' => 0, 'H' => 20, 'X' => 0, 'N' => 0, 'F' => 80];
        $p = CadenaMarkov::matrizDesdeConteos($conteos);

        $this->assertEqualsWithDelta(0.75, $p['C']['H'], 1e-9);
        $this->assertTrue(CadenaMarkov::filasSumanUno($p));
    }

    public function testProbabilidadesDeDosPasosDelSegundoAporte(): void
    {
        $v = CadenaMarkov::evolucion(CadenaMarkov::vectorInicial('C'), MarkovServicio::MATRIZ_DEMOSTRATIVA, 2);

        $this->assertEqualsWithDelta(0.60, $v[2]['F'], 1e-9);
        $this->assertEqualsWithDelta(0.11, $v[2]['X'], 1e-9);
        $this->assertEqualsWithDelta(0.055, $v[2]['N'], 1e-9);
    }

    public function testProbabilidadesDeAbsorcionDelSegundoAporte(): void
    {
        $b = CadenaMarkov::absorcion(MarkovServicio::MATRIZ_DEMOSTRATIVA)['B'];

        $this->assertEqualsWithDelta(5 / 6, $b['C']['F'], 1e-9);
        $this->assertEqualsWithDelta(1 / 9, $b['C']['X'], 1e-9);
        $this->assertEqualsWithDelta(1 / 18, $b['C']['N'], 1e-9);
    }

    public function testReservaQueLlegaYSale(): void
    {
        $r = ['registro' => '2026-03-01', 'estado' => 'FINALIZADA', 'fecha_ingreso' => '2026-03-05', 'fecha_salida' => '2026-03-07',
              'checkin' => '2026-03-05', 'checkout' => '2026-03-07', 'cancelacion' => null];
        $n = MarkovServicio::contarTransiciones([$r], '2026-01-01', '2026-12-31');

        $this->assertSame(3, $n['C']['C']);
        $this->assertSame(1, $n['C']['H']);
        $this->assertSame(1, $n['H']['F']);
    }

    public function testCriteriosDelHito3(): void
    {
        $c = ModeloDecision::criterios(ModeloDecision::matrizPagos(3, 250, 400), [0.25, 0.35, 0.25, 0.15], 0.6);

        $this->assertSame(1, $c['elegida']['ve']);
        $this->assertEqualsWithDelta(87.5, $c['ve_optimo'], 1e-9);
        $this->assertEqualsWithDelta(237.5, $c['veip'], 1e-9);
        $this->assertSame(3, $c['elegida']['maximax']);
        $this->assertSame(0, $c['elegida']['maximin']);
        $this->assertSame(1, $c['elegida']['savage']);
    }
}
