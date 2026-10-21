<?php
/**
 * Configuración general. Cada valor puede sobrescribirse en el archivo .env
 * (ver .env.example). Los valores por defecto funcionan con un XAMPP recién
 * instalado: MySQL en 127.0.0.1:3306, usuario root sin contraseña.
 */

use App\Core\Entorno;

return [
    'app' => [
        'nombre'   => 'Hotel Elegance',
        'entorno'  => Entorno::obtener('APP_ENTORNO', 'produccion'),
        'debug'    => (bool) Entorno::obtener('APP_DEBUG', false),
        'version'  => '1.0.0',
        'zona'     => 'America/La_Paz',
    ],
    'bd' => [
        'host'      => Entorno::obtener('DB_HOST', '127.0.0.1'),
        'puerto'    => (int) Entorno::obtener('DB_PUERTO', 3306),
        'nombre'    => Entorno::obtener('DB_NOMBRE', 'hotel_elegance'),
        'usuario'   => Entorno::obtener('DB_USUARIO', 'root'),
        'clave'     => (string) Entorno::obtener('DB_CLAVE', ''),
        'charset'   => 'utf8mb4',
    ],
    'sesion' => [
        'nombre'              => 'HE_SESION',
        'inactividad_minutos' => (int) Entorno::obtener('SESION_INACTIVIDAD', 120),
        'intentos_maximos'    => 5,
    ],
    'instalador' => [
        // Si es false, /instalar sólo informa el estado y no permite reinstalar.
        'habilitado' => (bool) Entorno::obtener('INSTALADOR_HABILITADO', true),
    ],
];
