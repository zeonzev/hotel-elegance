<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Respuesta;
use App\Servicios\SobreventaServicio;
use DateTimeImmutable;

/**
 * Sprint 5 · Política de sobreventa (Teoría de Decisiones):
 * CU-17 Calcular tasa de cancelación y no-show, CU-18 Calcular valor esperado
 * de sobreventa y CU-19 Alertar sobreventa.
 */
final class SobreventaController extends Controlador
{
    public function index(): Respuesta
    {
        $config = SobreventaServicio::configuracion();
        $alertas = SobreventaServicio::alertasProximas(30);
        // Por defecto se evalúa la noche más crítica del próximo mes
        $critica = $alertas ? array_search(max(array_column($alertas, 'porcentaje')), array_column($alertas, 'porcentaje', null), true) : null;
        $porDefecto = $critica !== null ? array_keys($alertas)[$critica] : date('Y-m-d', strtotime('+1 day'));
        $fecha = $this->fecha((string) $this->solicitud->consulta('fecha', ''), $porDefecto);
        $ingreso = $this->solicitud->consulta('ingreso');
        $evaluacion = SobreventaServicio::evaluar($fecha, is_numeric($ingreso) && $ingreso > 0 ? (float) $ingreso : null, $config);
        return $this->vista('sobreventa/index', [
            'titulo' => 'Política de sobreventa', 'config' => $config, 'tasas' => SobreventaServicio::tasas(12),
            'vigente' => SobreventaServicio::tasaVigente($config), 'alertas' => $alertas, 'e' => $evaluacion,
            'historial' => SobreventaServicio::historial(6),
        ]);
    }

    /** CU-17 */
    public function recalcularTasas(): Respuesta
    {
        $config = SobreventaServicio::configuracion();
        $meses = SobreventaServicio::recalcularTasas(max(12, (int) $config['meses_historial']));
        $this->bitacora('Sobreventa', 'Calcular tasas', "Tasas de cancelación y no-show recalculadas ($meses meses)");
        return $this->volver($meses ? "Tasas históricas recalculadas para $meses mes(es)." : 'No hay reservas en el periodo: no se calcularon tasas (CU-17, flujo 2a).', $meses ? 'exito' : 'aviso');
    }

    public function guardarConfiguracion(): Respuesta
    {
        $datos = $this->solicitud->todo();
        SobreventaServicio::validarConfiguracion($datos);
        SobreventaServicio::guardarConfiguracion($datos);
        $this->bitacora('Sobreventa', 'Configurar política', "Umbral {$datos['umbral_ocupacion']} %, costo Bs {$datos['costo_reubicacion']}, máximo {$datos['max_reservas_extra']} extra");
        return $this->redirigir('/panel/sobreventa', 'Parámetros de la política de sobreventa actualizados.');
    }

    /** CU-18 paso 5: registra la evaluación con la decisión. */
    public function guardarEvaluacion(): Respuesta
    {
        $fecha = $this->fecha((string) $this->solicitud->entrada('fecha', ''), date('Y-m-d'));
        $ingreso = $this->solicitud->entrada('ingreso');
        $e = SobreventaServicio::evaluar($fecha, is_numeric($ingreso) && $ingreso > 0 ? (float) $ingreso : null);
        SobreventaServicio::guardarEvaluacion($e);
        $this->bitacora('Sobreventa', 'Calcular valor esperado', 'Evaluación del ' . fecha($fecha) . ': ' . $e['decision'] . ' (VE ' . bs($e['ve_aceptar']) . ')');
        return $this->redirigir('/panel/sobreventa?fecha=' . $fecha, 'Evaluación guardada: la recomendación es ' . $e['decision'] . '.');
    }

    public function historial(): Respuesta
    {
        return $this->vista('sobreventa/historial', ['titulo' => 'Historial de evaluaciones', 'evaluaciones' => SobreventaServicio::historial(200)]);
    }

    /** CU-19 para otras pantallas: alerta de un rango de fechas. */
    public function alerta(): Respuesta
    {
        $ingreso = $this->fecha((string) $this->solicitud->consulta('ingreso', ''), date('Y-m-d'));
        $salida = $this->fecha((string) $this->solicitud->consulta('salida', ''), date('Y-m-d', strtotime($ingreso . ' +1 day')));
        $a = $salida > $ingreso ? SobreventaServicio::alertaParaRango($ingreso, $salida) : null;
        return $this->json(['ok' => true, 'alerta' => $a ? array_intersect_key($a, array_flip(['fecha', 'ocupacion', 'umbral', 've_aceptar', 've_simple', 'decision', 'motivo', 'nivel_optimo'])) : null]);
    }

    private function fecha(string $f, string $porDefecto): string
    {
        $d = DateTimeImmutable::createFromFormat('Y-m-d', $f);
        return $d && $d->format('Y-m-d') === $f ? $f : $porDefecto;
    }
}
