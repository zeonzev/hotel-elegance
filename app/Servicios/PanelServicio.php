<?php

declare(strict_types=1);

namespace App\Servicios;

use App\Core\Autenticacion;
use App\Core\BaseDatos;
use Throwable;

/**
 * Contadores del menú lateral y de la campana de notificaciones.
 */
final class PanelServicio
{
    private static ?array $cache = null;

    public static function contadores(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }
        $c = ['solicitudes' => 0, 'mensajes' => 0, 'llegadas_pendientes' => 0, 'alertas_sobreventa' => 0, 'bloqueados' => 0, 'notificaciones' => [], 'total_alertas' => 0];
        try {
            if (Autenticacion::puede('solicitudes.gestionar')) {
                $c['solicitudes'] = (int) BaseDatos::valor("SELECT COUNT(*) FROM solicitud_reserva WHERE estado = 'NUEVA'");
                $c['mensajes'] = (int) BaseDatos::valor('SELECT COUNT(*) FROM mensaje_contacto WHERE leido = 0');
            }
            if (Autenticacion::puede('recepcion.operar')) {
                $c['llegadas_pendientes'] = (int) BaseDatos::valor("SELECT COUNT(*) FROM reserva WHERE fecha_ingreso = CURDATE() AND estado IN ('PENDIENTE', 'CONFIRMADA')");
            }
            if (Autenticacion::puede('sobreventa.alertas')) {
                $c['alertas_sobreventa'] = count(SobreventaServicio::alertasProximas(14));
            }
            if (Autenticacion::puede('usuarios.gestionar')) {
                $c['bloqueados'] = (int) BaseDatos::valor("SELECT COUNT(*) FROM usuario WHERE estado = 'BLOQUEADO'");
            }
        } catch (Throwable) {
            // si algo falla, el menú se muestra sin contadores
        }

        $n = [];
        if ($c['alertas_sobreventa']) {
            $n[] = ['titulo' => 'Alerta de sobreventa', 'texto' => $c['alertas_sobreventa'] . ' noche(s) de las próximas 2 semanas superan el umbral de ocupación.', 'icono' => 'bi-shield-exclamation', 'color' => 'rojo', 'url' => url(Autenticacion::puede('sobreventa.ver') ? '/panel/sobreventa' : '/panel/disponibilidad')];
        }
        if ($c['llegadas_pendientes']) {
            $n[] = ['titulo' => 'Llegadas de hoy', 'texto' => $c['llegadas_pendientes'] . ' huésped(es) esperan su check-in.', 'icono' => 'bi-box-arrow-in-right', 'color' => 'azul', 'url' => url('/panel/recepcion')];
        }
        if ($c['solicitudes']) {
            $n[] = ['titulo' => 'Solicitudes del sitio web', 'texto' => $c['solicitudes'] . ' solicitud(es) de reserva sin atender.', 'icono' => 'bi-envelope-paper', 'color' => 'oro', 'url' => url('/panel/solicitudes')];
        }
        if ($c['mensajes']) {
            $n[] = ['titulo' => 'Mensajes de contacto', 'texto' => $c['mensajes'] . ' mensaje(s) sin leer.', 'icono' => 'bi-chat-left-text', 'color' => 'violeta', 'url' => url('/panel/solicitudes?pestana=mensajes')];
        }
        if ($c['bloqueados']) {
            $n[] = ['titulo' => 'Cuentas bloqueadas', 'texto' => $c['bloqueados'] . ' cuenta(s) bloqueada(s) por intentos fallidos.', 'icono' => 'bi-lock', 'color' => 'ambar', 'url' => url('/panel/usuarios')];
        }
        $c['notificaciones'] = $n;
        $c['total_alertas'] = count($n);
        return self::$cache = $c;
    }
}
