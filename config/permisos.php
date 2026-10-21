<?php
/**
 * Matriz de permisos por rol. Se usa en el servidor (middleware «permiso:»)
 * y para construir el menú lateral: lo que un rol no puede hacer, no lo ve.
 *
 *  - Administrador: gestiona todo.
 *  - Recepcionista: opera huéspedes, reservas, check-in/out y estados de habitación.
 *  - Gerente: dashboard, reportes, política de sobreventa y tarifas (consulta).
 */

$adm = 'Administrador';
$rec = 'Recepcionista';
$ger = 'Gerente';

return [
    // Sesiones (Sprint 1)
    'dashboard.ver'           => [$adm, $rec, $ger],
    'personal.gestionar'      => [$adm],
    'usuarios.gestionar'      => [$adm],
    'bitacora.ver'            => [$adm],
    'configuracion.gestionar' => [$adm],

    // Gestión de huéspedes (Sprint 2)
    'huespedes.ver'           => [$adm, $rec],
    'huespedes.gestionar'     => [$adm, $rec],
    'huespedes.historial'     => [$adm, $rec],

    // Gestión de habitaciones (Sprint 3)
    'habitaciones.ver'        => [$adm, $rec],
    'habitaciones.gestionar'  => [$adm],
    'habitaciones.estado'     => [$adm, $rec],

    // Gestión de reservas (Sprint 4)
    'reservas.ver'            => [$adm, $rec],
    'reservas.gestionar'      => [$adm, $rec],
    'recepcion.operar'        => [$adm, $rec],
    'solicitudes.gestionar'   => [$adm, $rec],

    // Política de sobreventa (Sprint 5)
    'sobreventa.ver'          => [$adm, $ger],
    'sobreventa.calcular'     => [$adm, $ger],
    'sobreventa.configurar'   => [$adm],
    'sobreventa.autorizar'    => [$adm],
    'sobreventa.alertas'      => [$adm, $rec, $ger],

    // Estimación de demanda y tarifas (Sprint 6)
    'tarifas.ver'             => [$adm, $ger],
    'tarifas.gestionar'       => [$adm],

    // Reportes (Sprint 7)
    'reportes.ver'            => [$adm, $ger],
];
