<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\ExcepcionValidacion;
use App\Core\Respuesta;
use App\Servicios\DashboardServicio;
use App\Servicios\DisponibilidadServicio;
use App\Servicios\ExportadorServicio;
use App\Servicios\ReporteServicio;
use DateTimeImmutable;

/**
 * Sprint 7 · Reportes para la gerencia, con filtros por periodo y exportación
 * a CSV, Excel (.xlsx) e impresión / PDF.
 */
final class ReporteController extends Controlador
{
    public function index(): Respuesta
    {
        $mes = ReporteServicio::rangos()['mes'];
        $total = DisponibilidadServicio::totalHabitaciones();
        $ocupacion = ReporteServicio::ocupacion($mes[1], $mes[2])['resumen'];
        $ingresos = ReporteServicio::ingresos($mes[1], $mes[2])['resumen'];
        $cancel = ReporteServicio::cancelaciones($mes[1], $mes[2])['resumen'];
        $procedencia = ReporteServicio::procedencia($mes[1], $mes[2]);
        return $this->vista('reportes/index', [
            'titulo' => 'Reportes', 'ocupacion' => $ocupacion, 'ingresos' => $ingresos, 'cancel' => $cancel, 'procedencia' => $procedencia,
            'rendimiento' => DashboardServicio::rendimiento($mes[1], $mes[2], $total), 'periodo' => $mes,
        ]);
    }

    /** CU-22 Generar reporte de ocupación. */
    public function ocupacion(): Respuesta
    {
        [$desde, $hasta] = $this->periodo();
        $datos = ReporteServicio::ocupacion($desde, $hasta);
        $formato = $this->solicitud->consulta('formato');
        if (in_array($formato, ['csv', 'xlsx'], true)) {
            $filas = [];
            foreach ($datos['diario'] as $f => $o) {
                $filas[] = [$f, DIAS[(int) date('N', strtotime($f))], $o['ocupadas'], $o['capacidad'], round($o['porcentaje'], 1)];
            }
            $cab = ['Fecha', 'Día', 'Habitaciones ocupadas', 'Habitaciones disponibles', 'Ocupación (%)'];
            $this->bitacora('Reportes', 'Exportar reporte', 'Ocupación ' . $desde . ' a ' . $hasta . ' (' . strtoupper($formato) . ')');
            return $formato === 'csv'
                ? ExportadorServicio::csv('ocupacion', $cab, $filas)
                : ExportadorServicio::xlsx('ocupacion', 'Reporte de ocupación', $cab, $filas, [
                    'Periodo' => fecha($desde) . ' al ' . fecha($hasta), 'Ocupación promedio (%)' => round($datos['resumen']['porcentaje'], 1),
                    'Noches vendidas' => $datos['resumen']['ocupadas'], 'ADR (Bs)' => round($datos['resumen']['adr'], 2), 'RevPAR (Bs)' => round($datos['resumen']['revpar'], 2),
                ]);
        }
        return $this->vista('reportes/ocupacion', ['titulo' => 'Reporte de ocupación', 'desde' => $desde, 'hasta' => $hasta, 'd' => $datos, 'imprimir' => $formato === 'imprimir'], $formato === 'imprimir' ? 'impresion' : 'panel');
    }

    /** CU-23 Generar reporte de ingresos. */
    public function ingresos(): Respuesta
    {
        [$desde, $hasta] = $this->periodo();
        $datos = ReporteServicio::ingresos($desde, $hasta);
        $formato = $this->solicitud->consulta('formato');
        if (in_array($formato, ['csv', 'xlsx'], true)) {
            $filas = [];
            foreach ($datos['meses'] as $mes => $cats) {
                foreach ($cats as $categoria => $monto) {
                    $filas[] = [$mes, $categoria, round($monto, 2)];
                }
            }
            $cab = ['Mes', 'Categoría', 'Ingreso estimado (Bs)'];
            $this->bitacora('Reportes', 'Exportar reporte', 'Ingresos ' . $desde . ' a ' . $hasta . ' (' . strtoupper($formato) . ')');
            return $formato === 'csv'
                ? ExportadorServicio::csv('ingresos', $cab, $filas)
                : ExportadorServicio::xlsx('ingresos', 'Reporte de ingresos', $cab, $filas, [
                    'Periodo' => fecha($desde) . ' al ' . fecha($hasta), 'Ingreso estimado (Bs)' => round($datos['resumen']['estimado'], 2),
                    'Cobrado en el periodo (Bs)' => round($datos['resumen']['cobrado'], 2), 'Reservas' => $datos['resumen']['reservas'],
                ]);
        }
        return $this->vista('reportes/ingresos', ['titulo' => 'Reporte de ingresos', 'desde' => $desde, 'hasta' => $hasta, 'd' => $datos, 'imprimir' => $formato === 'imprimir'], $formato === 'imprimir' ? 'impresion' : 'panel');
    }

    /** CU-24 Generar reporte de cancelaciones. */
    public function cancelaciones(): Respuesta
    {
        [$desde, $hasta] = $this->periodo();
        $datos = ReporteServicio::cancelaciones($desde, $hasta);
        $formato = $this->solicitud->consulta('formato');
        if (in_array($formato, ['csv', 'xlsx'], true)) {
            $filas = array_map(fn ($c) => [
                substr($c['fecha_cancelacion'], 0, 16), $c['codigo'], $c['huesped'], $c['canal'], $c['fecha_ingreso'], $c['fecha_salida'],
                MOTIVOS_CANCELACION[$c['tipo_motivo']] ?? $c['tipo_motivo'], $c['descripcion'], (int) $c['dias_anticipacion'], (float) $c['monto_total'],
            ], $datos['lista']);
            $cab = ['Fecha cancelación', 'Código', 'Huésped', 'Canal', 'Ingreso', 'Salida', 'Tipo de motivo', 'Descripción', 'Días de anticipación', 'Monto (Bs)'];
            $this->bitacora('Reportes', 'Exportar reporte', 'Cancelaciones ' . $desde . ' a ' . $hasta . ' (' . strtoupper($formato) . ')');
            return $formato === 'csv'
                ? ExportadorServicio::csv('cancelaciones', $cab, $filas)
                : ExportadorServicio::xlsx('cancelaciones', 'Reporte de cancelaciones', $cab, $filas, [
                    'Periodo' => fecha($desde) . ' al ' . fecha($hasta), 'Cancelaciones' => $datos['resumen']['cantidad'],
                    'Tasa de cancelación (%)' => round($datos['resumen']['tasa'], 1), 'Monto no realizado (Bs)' => round($datos['resumen']['monto'], 2),
                ]);
        }
        return $this->vista('reportes/cancelaciones', ['titulo' => 'Reporte de cancelaciones', 'desde' => $desde, 'hasta' => $hasta, 'd' => $datos, 'imprimir' => $formato === 'imprimir'], $formato === 'imprimir' ? 'impresion' : 'panel');
    }

    /** Reporte de procedencia de los huéspedes (países y ciudades de origen). */
    public function procedencia(): Respuesta
    {
        [$desde, $hasta] = $this->periodo();
        $datos = ReporteServicio::procedencia($desde, $hasta);
        $formato = $this->solicitud->consulta('formato');
        if (in_array($formato, ['csv', 'xlsx'], true)) {
            $filas = [];
            foreach ($datos['ciudades'] as $ciudad => $c) {
                $filas[] = [$ciudad, $c['pais'], $c['estadias'], $c['personas'], $c['noches'], round($c['ingreso'], 2)];
            }
            $cab = ['Ciudad de procedencia', 'País', 'Estadías', 'Personas', 'Noches', 'Ingreso (Bs)'];
            $this->bitacora('Reportes', 'Exportar reporte', 'Procedencia ' . $desde . ' a ' . $hasta . ' (' . strtoupper($formato) . ')');
            return $formato === 'csv'
                ? ExportadorServicio::csv('procedencia', $cab, $filas)
                : ExportadorServicio::xlsx('procedencia', 'Procedencia de los huéspedes', $cab, $filas, [
                    'Periodo' => fecha($desde) . ' al ' . fecha($hasta), 'Estadías' => $datos['resumen']['estadias'],
                    'Nacionales (%)' => round($datos['origen']['nacional']['porcentaje'], 1),
                    'Extranjeros (%)' => round($datos['origen']['extranjero']['porcentaje'], 1),
                ]);
        }
        return $this->vista('reportes/procedencia', ['titulo' => 'Procedencia de los huéspedes', 'desde' => $desde, 'hasta' => $hasta, 'd' => $datos, 'imprimir' => $formato === 'imprimir'], $formato === 'imprimir' ? 'impresion' : 'panel');
    }

    /** Periodo del reporte (por defecto, los últimos 3 meses). */
    private function periodo(): array
    {
        $rangos = ReporteServicio::rangos();
        $rango = (string) $this->solicitud->consulta('rango', '');
        if (isset($rangos[$rango])) {
            return [$rangos[$rango][1], $rangos[$rango][2]];
        }
        $desde = (string) $this->solicitud->consulta('desde', $rangos['trimestre'][1]);
        $hasta = (string) $this->solicitud->consulta('hasta', $rangos['trimestre'][2]);
        foreach (['desde' => $desde, 'hasta' => $hasta] as $campo => $f) {
            $d = DateTimeImmutable::createFromFormat('Y-m-d', $f);
            if (!$d || $d->format('Y-m-d') !== $f) {
                throw ExcepcionValidacion::campo($campo, 'Ingrese una fecha válida.');
            }
        }
        if ($hasta < $desde) {
            throw ExcepcionValidacion::campo('hasta', 'La fecha final no puede ser anterior a la inicial.');
        }
        if (noches($desde, $hasta) > 731) {
            throw ExcepcionValidacion::campo('hasta', 'El periodo no puede superar 2 años.');
        }
        return [$desde, $hasta];
    }
}
