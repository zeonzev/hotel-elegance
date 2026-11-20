<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseDatos;
use App\Core\Respuesta;
use App\Servicios\MarkovServicio;
use App\Servicios\SobreventaServicio;
use DateTimeImmutable;

/**
 * Investigación Operativa II · Cadenas de Markov del ciclo de una reserva.
 * El sistema genera la matriz de transición con el historial de reservas y
 * entrega la probabilidad de no-show a la Teoría de Decisiones (sobreventa).
 */
final class MarkovController extends Controlador
{
    public function index(): Respuesta
    {
        $hoy = date('Y-m-d');
        $desde = $this->fecha((string) $this->solicitud->consulta('desde', ''), (new DateTimeImmutable())->modify('-12 months')->format('Y-m-d'));
        $hasta = $this->fecha((string) $this->solicitud->consulta('hasta', ''), $hoy);
        if ($hasta < $desde) {
            [$desde, $hasta] = [$hasta, $desde];
        }
        $categoria = (int) $this->solicitud->consulta('categoria', 0) ?: null;
        $pasos = (int) $this->solicitud->consulta('n', 7) ?: 7;
        $inicial = $this->solicitud->consulta('inicial') === 'H' ? 'H' : 'C';

        $analisis = MarkovServicio::analizar($desde, $hasta, $categoria, $pasos, $inicial);
        $config = SobreventaServicio::configuracion();

        return $this->vista('markov/index', [
            'titulo'     => 'Cadenas de Markov',
            'a'          => $analisis,
            'categorias' => BaseDatos::filas('SELECT id_categoria, nombre FROM categoria_habitacion ORDER BY id_categoria'),
            'historica'  => SobreventaServicio::tasaVigente($config),
            'config'     => $config,
        ]);
    }

    private function fecha(string $f, string $porDefecto): string
    {
        $d = DateTimeImmutable::createFromFormat('Y-m-d', $f);
        return $d && $d->format('Y-m-d') === $f ? $f : $porDefecto;
    }
}
