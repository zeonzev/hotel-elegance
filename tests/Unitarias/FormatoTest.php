<?php

declare(strict_types=1);

namespace Pruebas;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Pruebas de las funciones de formato de app/helpers.php: montos en bolivianos,
 * números, fechas en español, cálculo de noches e iniciales de los avatares.
 */
final class FormatoTest extends TestCase
{
    public function testBsFormateaMontoConSeparadoresBolivianos(): void
    {
        $this->assertSame("Bs\u{00A0}12.345,50", bs(12345.5));
    }

    public function testBsAceptaTextoNumericoYRedondea(): void
    {
        $this->assertSame("Bs\u{00A0}1.000", bs('999.6', 0));
    }

    public function testBsDeCero(): void
    {
        $this->assertSame("Bs\u{00A0}0,00", bs(0));
    }

    public function testNumeroSinDecimales(): void
    {
        $this->assertSame('1.234.567', numero(1234567));
    }

    public function testNumeroConDecimales(): void
    {
        $this->assertSame('48,4', numero(48.38, 1));
    }

    public function testPorcentaje(): void
    {
        $this->assertSame('90,0 %', porcentaje(90));
    }

    /** @return array<string, array{string, string, string}> */
    public static function estilosDeFecha(): array
    {
        return [
            'corta' => ['2026-10-21', 'corta', '21 oct 2026'],
            'larga' => ['2026-10-21', 'larga', '21 de octubre de 2026'],
            'día de la semana' => ['2026-10-21', 'dia', 'mié 21 oct'],
            'mes' => ['2026-10-21', 'mes', 'Octubre 2026'],
            'numérica' => ['2026-01-05', 'numerica', '05/01/2026'],
            'con hora' => ['2026-10-21 14:30:00', 'hora', '21 oct 2026, 14:30'],
        ];
    }

    #[DataProvider('estilosDeFecha')]
    public function testFechaEnEspanol(string $valor, string $estilo, string $esperado): void
    {
        $this->assertSame($esperado, fecha($valor, $estilo));
    }

    public function testFechaVaciaMuestraGuion(): void
    {
        $this->assertSame('—', fecha(null));
        $this->assertSame('—', fecha(''));
    }

    public function testNochesEntreDosFechas(): void
    {
        $this->assertSame(3, noches('2026-10-20', '2026-10-23'));
    }

    public function testNochesCruzandoFinDeMes(): void
    {
        $this->assertSame(4, noches('2026-10-29', '2026-11-02'));
    }

    public function testNochesEnAnioBisiesto(): void
    {
        $this->assertSame(2, noches('2028-02-28', '2028-03-01'));
    }

    public function testInicialesDeNombreYApellido(): void
    {
        $this->assertSame('CM', iniciales('Carla Mendoza'));
    }

    public function testInicialesUsaSoloLasDosPrimerasPalabras(): void
    {
        $this->assertSame('RP', iniciales('Reynaldo Paz Mendoza'));
    }

    public function testInicialesConTildesYEspaciosDeMas(): void
    {
        $this->assertSame('ÁÑ', iniciales('  álvaro   ñuflo '));
    }
}
