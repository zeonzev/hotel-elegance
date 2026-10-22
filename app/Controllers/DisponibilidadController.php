<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Autenticacion;
use App\Core\BaseDatos;
use App\Core\ExcepcionValidacion;
use App\Core\Respuesta;
use App\Servicios\DisponibilidadServicio;
use App\Servicios\SobreventaServicio;
use App\Servicios\TarifaServicio;
use DateTimeImmutable;

/**
 * Sprint 4 · CU-12 Consultar disponibilidad: buscador por rango de fechas y
 * calendario visual de ocupación (rack de habitaciones × días).
 */
final class DisponibilidadController extends Controlador
{
    public function index(): Respuesta
    {
        $dias = in_array($this->entero('dias', 14), [7, 14, 21, 31], true) ? $this->entero('dias', 14) : 14;
        $desde = $this->fechaValida((string) $this->solicitud->consulta('desde', ''), date('Y-m-d', strtotime('-1 day')));
        $categoria = $this->entero('categoria') ?: null;
        $piso = $this->entero('piso') ?: null;

        // Buscador CU-12
        $ingreso = (string) $this->solicitud->consulta('ingreso', '');
        $salida = (string) $this->solicitud->consulta('salida', '');
        $resultado = null;
        $errorBusqueda = null;
        if ($ingreso !== '' || $salida !== '') {
            try {
                $libres = DisponibilidadServicio::habitacionesDisponibles($ingreso, $salida, $categoria);
                $ocupacion = DisponibilidadServicio::ocupacionPorNoche($ingreso, (new DateTimeImmutable($salida))->modify('-1 day')->format('Y-m-d'));
                $resultado = ['libres' => $libres, 'ocupacion' => $ocupacion, 'noches' => noches($ingreso, $salida)];
            } catch (ExcepcionValidacion $e) {
                $errorBusqueda = current($e->errores());
            }
        }

        return $this->vista('disponibilidad/index', [
            'titulo' => 'Disponibilidad', 'rack' => DisponibilidadServicio::rack($desde, $dias, $categoria, $piso),
            'dias' => $dias, 'desde' => $desde, 'categoria' => $categoria, 'piso' => $piso,
            'categorias' => BaseDatos::filas('SELECT id_categoria, nombre FROM categoria_habitacion ORDER BY tarifa_base'),
            'pisos' => BaseDatos::consulta('SELECT DISTINCT piso FROM habitacion ORDER BY piso')->fetchAll(\PDO::FETCH_COLUMN),
            'ingreso' => $ingreso, 'salida' => $salida, 'resultado' => $resultado, 'errorBusqueda' => $errorBusqueda,
            'umbral' => (float) SobreventaServicio::configuracion()['umbral_ocupacion'],
        ]);
    }

    /** API del asistente de reserva (CU-11 paso 2 «include» CU-12, y alerta CU-19). */
    public function api(): Respuesta
    {
        $ingreso = (string) $this->solicitud->consulta('ingreso', '');
        $salida = (string) $this->solicitud->consulta('salida', '');
        $personas = max(0, $this->entero('personas'));
        $excluir = $this->entero('excluir') ?: null;
        DisponibilidadServicio::validarRango($ingreso, $salida);
        if (!$excluir && $ingreso < date('Y-m-d')) {
            throw ExcepcionValidacion::campo('fecha_ingreso', 'La fecha de ingreso no puede ser anterior a hoy.');
        }
        $libres = DisponibilidadServicio::habitacionesDisponibles($ingreso, $salida, $this->entero('categoria') ?: null, $excluir);

        $categorias = [];
        foreach (BaseDatos::filas('SELECT id_categoria, nombre, capacidad, imagen, camas FROM categoria_habitacion WHERE estado = 1 ORDER BY tarifa_base') as $c) {
            $categorias[(int) $c['id_categoria']] = $c + ['habitaciones' => [], 'cotizacion' => TarifaServicio::cotizar((int) $c['id_categoria'], $ingreso, $salida)];
        }
        foreach ($libres as $h) {
            $id = (int) $h['id_categoria'];
            if (isset($categorias[$id])) {
                $categorias[$id]['habitaciones'][] = ['id' => (int) $h['id_habitacion'], 'numero' => $h['numero'], 'piso' => (int) $h['piso'], 'descripcion' => $h['descripcion'], 'estado' => $h['estado']];
            }
        }
        $ultima = (new DateTimeImmutable($salida))->modify('-1 day')->format('Y-m-d');
        $ocupacion = DisponibilidadServicio::ocupacionPorNoche($ingreso, $ultima);
        $alerta = $excluir ? null : SobreventaServicio::alertaParaRango($ingreso, $salida);

        return $this->json([
            'ok' => true,
            'noches' => noches($ingreso, $salida),
            'categorias' => array_values(array_map(fn ($c) => [
                'id' => (int) $c['id_categoria'], 'nombre' => $c['nombre'], 'capacidad' => (int) $c['capacidad'], 'camas' => $c['camas'],
                'imagen' => asset('img/habitaciones/' . $c['imagen']),
                'total' => $c['cotizacion']['total'], 'promedio' => $c['cotizacion']['promedio'], 'temporadas' => $c['cotizacion']['temporadas'],
                'noches' => array_map(fn ($n) => ['fecha' => $n['fecha'], 'precio' => $n['precio'], 'temporada' => $n['temporada']], $c['cotizacion']['noches']),
                'habitaciones' => $c['habitaciones'],
            ], $categorias)),
            'ocupacion' => array_map(fn ($o) => $o['porcentaje'], $ocupacion),
            'alerta' => $alerta ? $this->resumenAlerta($alerta) : null,
            'puede_autorizar' => Autenticacion::puede('sobreventa.autorizar'),
        ]);
    }

    private function resumenAlerta(array $a): array
    {
        return [
            'fecha' => $a['fecha'], 'fecha_texto' => fecha($a['fecha'], 'larga'), 'ocupacion' => $a['ocupacion'], 'umbral' => $a['umbral'],
            'reservas' => $a['reservas'], 'capacidad' => $a['capacidad'], 'p_no_show' => round($a['p_no_show'] * 100, 1),
            'p_libera' => round($a['p_libera'] * 100, 1), 'ingreso' => $a['ingreso'], 'costo' => $a['costo'],
            've_simple' => round($a['ve_simple'], 2), 've_aceptar' => round($a['ve_aceptar'], 2), 'decision' => $a['decision'],
            'motivo' => $a['motivo'], 'nivel_optimo' => $a['nivel_optimo'], 'extras' => $a['extras_actuales'], 'max_extra' => $a['max_extra'],
        ];
    }

    private function fechaValida(string $f, string $porDefecto): string
    {
        $d = DateTimeImmutable::createFromFormat('Y-m-d', $f);
        return $d && $d->format('Y-m-d') === $f ? $f : $porDefecto;
    }
}
