<?php
/**
 * Rutas del sistema. Cada módulo corresponde a un sprint del informe.
 */

use App\Controllers\AutenticacionController;
use App\Controllers\BitacoraController;
use App\Controllers\BusquedaController;
use App\Controllers\CategoriaController;
use App\Controllers\ConfiguracionController;
use App\Controllers\DashboardController;
use App\Controllers\DisponibilidadController;
use App\Controllers\HabitacionController;
use App\Controllers\HuespedController;
use App\Controllers\InstaladorController;
use App\Controllers\PerfilController;
use App\Controllers\PersonalController;
use App\Controllers\RecepcionController;
use App\Controllers\ReporteController;
use App\Controllers\ReservaController;
use App\Controllers\SitioController;
use App\Controllers\SobreventaController;
use App\Controllers\SolicitudController;
use App\Controllers\TarifaController;
use App\Controllers\UsuarioController;
use App\Core\Enrutador;

return static function (Enrutador $r): void {
    // ------------------------------------------------------------------
    // Sitio público del hotel
    // ------------------------------------------------------------------
    $r->get('/', [SitioController::class, 'inicio']);
    $r->get('/habitaciones', [SitioController::class, 'habitaciones']);
    $r->get('/habitaciones/{id}', [SitioController::class, 'habitacion']);
    $r->get('/reservar', [SitioController::class, 'reservar']);
    $r->post('/reservar', [SitioController::class, 'enviarSolicitud']);
    $r->get('/reservar/gracias', [SitioController::class, 'gracias']);
    $r->get('/contacto', [SitioController::class, 'contacto']);
    $r->post('/contacto', [SitioController::class, 'enviarContacto']);
    $r->get('/api/disponibilidad', [SitioController::class, 'disponibilidad']);

    // Instalador opcional en el navegador
    $r->get('/instalar', [InstaladorController::class, 'index']);
    $r->post('/instalar', [InstaladorController::class, 'instalar']);

    // ------------------------------------------------------------------
    // Sprint 1 · Sesiones (CU-04)
    // ------------------------------------------------------------------
    $r->get('/login', [AutenticacionController::class, 'formulario'], ['invitado']);
    $r->post('/login', [AutenticacionController::class, 'ingresar'], ['invitado']);
    $r->post('/logout', [AutenticacionController::class, 'salir'], ['auth']);
    $r->get('/cambiar-contrasena', [AutenticacionController::class, 'formularioCambio'], ['auth']);
    $r->post('/cambiar-contrasena', [AutenticacionController::class, 'cambiar'], ['auth']);

    // ------------------------------------------------------------------
    // Panel administrativo
    // ------------------------------------------------------------------
    $r->grupo(['prefijo' => '/panel', 'middleware' => ['auth']], static function (Enrutador $r): void {
        $r->get('', [DashboardController::class, 'index'], ['permiso:dashboard.ver']);
        $r->get('/buscar', [BusquedaController::class, 'index']);
        $r->get('/api/buscar', [BusquedaController::class, 'api']);

        $r->get('/perfil', [PerfilController::class, 'index']);
        $r->post('/perfil', [PerfilController::class, 'actualizar']);
        $r->post('/perfil/contrasena', [PerfilController::class, 'contrasena']);

        // Sprint 1 · Personal y cuentas (CU-01, CU-02, CU-03)
        $r->grupo(['middleware' => ['permiso:personal.gestionar']], static function (Enrutador $r): void {
            $r->get('/personal', [PersonalController::class, 'index']);
            $r->get('/personal/nuevo', [PersonalController::class, 'crear']);
            $r->post('/personal', [PersonalController::class, 'guardar']);
            $r->get('/personal/{id}/editar', [PersonalController::class, 'editar']);
            $r->post('/personal/{id}', [PersonalController::class, 'actualizar']);
            $r->post('/personal/{id}/estado', [PersonalController::class, 'cambiarEstado']);
        });
        $r->grupo(['middleware' => ['permiso:usuarios.gestionar']], static function (Enrutador $r): void {
            $r->get('/usuarios', [UsuarioController::class, 'index']);
            $r->post('/usuarios', [UsuarioController::class, 'crearCuenta']);
            $r->post('/usuarios/{id}/rol', [UsuarioController::class, 'asignarRol']);
            $r->post('/usuarios/{id}/desbloquear', [UsuarioController::class, 'desbloquear']);
            $r->post('/usuarios/{id}/restablecer', [UsuarioController::class, 'restablecer']);
            $r->post('/usuarios/{id}/estado', [UsuarioController::class, 'cambiarEstado']);
        });
        $r->get('/bitacora', [BitacoraController::class, 'index'], ['permiso:bitacora.ver']);
        $r->get('/configuracion', [ConfiguracionController::class, 'index'], ['permiso:configuracion.gestionar']);
        $r->post('/configuracion', [ConfiguracionController::class, 'guardar'], ['permiso:configuracion.gestionar']);

        // Sprint 2 · Gestión de huéspedes (CU-05, CU-06, CU-07)
        $r->get('/huespedes', [HuespedController::class, 'index'], ['permiso:huespedes.ver']);
        $r->get('/huespedes/nuevo', [HuespedController::class, 'crear'], ['permiso:huespedes.gestionar']);
        $r->post('/huespedes', [HuespedController::class, 'guardar'], ['permiso:huespedes.gestionar']);
        $r->get('/huespedes/{id}', [HuespedController::class, 'ver'], ['permiso:huespedes.historial']);
        $r->get('/huespedes/{id}/editar', [HuespedController::class, 'editar'], ['permiso:huespedes.gestionar']);
        $r->post('/huespedes/{id}', [HuespedController::class, 'actualizar'], ['permiso:huespedes.gestionar']);
        $r->get('/api/huespedes', [HuespedController::class, 'buscar'], ['permiso:huespedes.ver']);
        $r->post('/api/huespedes', [HuespedController::class, 'guardarRapido'], ['permiso:huespedes.gestionar']);

        // Sprint 3 · Gestión de habitaciones (CU-08, CU-09, CU-10)
        $r->get('/habitaciones', [HabitacionController::class, 'index'], ['permiso:habitaciones.ver']);
        $r->get('/habitaciones/nueva', [HabitacionController::class, 'crear'], ['permiso:habitaciones.gestionar']);
        $r->post('/habitaciones', [HabitacionController::class, 'guardar'], ['permiso:habitaciones.gestionar']);
        $r->get('/habitaciones/{id}', [HabitacionController::class, 'ver'], ['permiso:habitaciones.ver']);
        $r->get('/habitaciones/{id}/editar', [HabitacionController::class, 'editar'], ['permiso:habitaciones.gestionar']);
        $r->post('/habitaciones/{id}', [HabitacionController::class, 'actualizar'], ['permiso:habitaciones.gestionar']);
        $r->post('/habitaciones/{id}/eliminar', [HabitacionController::class, 'eliminar'], ['permiso:habitaciones.gestionar']);
        $r->post('/habitaciones/{id}/estado', [HabitacionController::class, 'cambiarEstado'], ['permiso:habitaciones.estado']);

        $r->grupo(['middleware' => ['permiso:habitaciones.gestionar']], static function (Enrutador $r): void {
            $r->get('/categorias', [CategoriaController::class, 'index']);
            $r->get('/categorias/nueva', [CategoriaController::class, 'crear']);
            $r->post('/categorias', [CategoriaController::class, 'guardar']);
            $r->get('/categorias/{id}/editar', [CategoriaController::class, 'editar']);
            $r->post('/categorias/{id}', [CategoriaController::class, 'actualizar']);
            $r->post('/categorias/{id}/eliminar', [CategoriaController::class, 'eliminar']);
        });

        // Sprint 4 · Gestión de reservas (CU-11 a CU-16)
        $r->get('/reservas', [ReservaController::class, 'index'], ['permiso:reservas.ver']);
        $r->get('/reservas/nueva', [ReservaController::class, 'crear'], ['permiso:reservas.gestionar']);
        $r->post('/reservas', [ReservaController::class, 'guardar'], ['permiso:reservas.gestionar']);
        $r->get('/reservas/{id}', [ReservaController::class, 'ver'], ['permiso:reservas.ver']);
        $r->get('/reservas/{id}/editar', [ReservaController::class, 'editar'], ['permiso:reservas.gestionar']);
        $r->post('/reservas/{id}', [ReservaController::class, 'actualizar'], ['permiso:reservas.gestionar']);
        $r->post('/reservas/{id}/cancelar', [ReservaController::class, 'cancelar'], ['permiso:reservas.gestionar']);
        $r->post('/reservas/{id}/confirmar', [ReservaController::class, 'confirmar'], ['permiso:reservas.gestionar']);
        $r->post('/reservas/{id}/no-show', [ReservaController::class, 'noShow'], ['permiso:recepcion.operar']);
        $r->post('/reservas/{id}/pagos', [ReservaController::class, 'registrarPago'], ['permiso:reservas.gestionar']);
        $r->post('/reservas/{id}/asignar', [ReservaController::class, 'asignarHabitacion'], ['permiso:reservas.gestionar']);
        $r->get('/reservas/{id}/recibo', [ReservaController::class, 'recibo'], ['permiso:reservas.ver']);
        $r->get('/reservas/{id}/checkin', [RecepcionController::class, 'formularioCheckin'], ['permiso:recepcion.operar']);
        $r->post('/reservas/{id}/checkin', [RecepcionController::class, 'checkin'], ['permiso:recepcion.operar']);
        $r->get('/reservas/{id}/checkout', [RecepcionController::class, 'formularioCheckout'], ['permiso:recepcion.operar']);
        $r->post('/reservas/{id}/checkout', [RecepcionController::class, 'checkout'], ['permiso:recepcion.operar']);
        $r->get('/recepcion', [RecepcionController::class, 'index'], ['permiso:recepcion.operar']);
        $r->get('/disponibilidad', [DisponibilidadController::class, 'index'], ['permiso:reservas.ver']);
        $r->get('/api/disponibilidad', [DisponibilidadController::class, 'api'], ['permiso:reservas.ver']);
        $r->get('/solicitudes', [SolicitudController::class, 'index'], ['permiso:solicitudes.gestionar']);
        $r->post('/solicitudes/{id}/descartar', [SolicitudController::class, 'descartar'], ['permiso:solicitudes.gestionar']);
        $r->post('/mensajes/{id}/leido', [SolicitudController::class, 'marcarLeido'], ['permiso:solicitudes.gestionar']);

        // Sprint 5 · Política de sobreventa (CU-17, CU-18, CU-19)
        $r->get('/sobreventa', [SobreventaController::class, 'index'], ['permiso:sobreventa.ver']);
        $r->post('/sobreventa/tasas', [SobreventaController::class, 'recalcularTasas'], ['permiso:sobreventa.calcular']);
        $r->post('/sobreventa/configuracion', [SobreventaController::class, 'guardarConfiguracion'], ['permiso:sobreventa.configurar']);
        $r->post('/sobreventa/evaluar', [SobreventaController::class, 'guardarEvaluacion'], ['permiso:sobreventa.calcular']);
        $r->get('/sobreventa/historial', [SobreventaController::class, 'historial'], ['permiso:sobreventa.ver']);
        $r->get('/api/sobreventa/alerta', [SobreventaController::class, 'alerta'], ['permiso:sobreventa.alertas']);

        // Sprint 6 · Estimación de demanda y tarifas (CU-20, CU-21)
        $r->get('/tarifas', [TarifaController::class, 'index'], ['permiso:tarifas.ver']);
        $r->get('/tarifas/nueva', [TarifaController::class, 'crear'], ['permiso:tarifas.gestionar']);
        $r->post('/tarifas', [TarifaController::class, 'guardar'], ['permiso:tarifas.gestionar']);
        $r->get('/tarifas/sugerencia', [TarifaController::class, 'sugerencia'], ['permiso:tarifas.ver']);
        $r->post('/tarifas/sugerencia/aplicar', [TarifaController::class, 'aplicarSugerencia'], ['permiso:tarifas.gestionar']);
        $r->get('/tarifas/{id}/editar', [TarifaController::class, 'editar'], ['permiso:tarifas.gestionar']);
        $r->post('/tarifas/{id}', [TarifaController::class, 'actualizar'], ['permiso:tarifas.gestionar']);
        $r->post('/tarifas/{id}/eliminar', [TarifaController::class, 'eliminar'], ['permiso:tarifas.gestionar']);

        // Sprint 7 · Reportes (CU-22, CU-23, CU-24)
        $r->grupo(['middleware' => ['permiso:reportes.ver']], static function (Enrutador $r): void {
            $r->get('/reportes', [ReporteController::class, 'index']);
            $r->get('/reportes/ocupacion', [ReporteController::class, 'ocupacion']);
            $r->get('/reportes/ingresos', [ReporteController::class, 'ingresos']);
            $r->get('/reportes/cancelaciones', [ReporteController::class, 'cancelaciones']);
            $r->get('/reportes/procedencia', [ReporteController::class, 'procedencia']);
        });
    });
};
