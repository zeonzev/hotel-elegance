<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseDatos;
use App\Core\Respuesta;

/**
 * Datos generales del hotel y parámetros de operación.
 */
final class ConfiguracionController extends Controlador
{
    private const CAMPOS = [
        'hotel_nombre' => 'requerido|max:80', 'hotel_razon_social' => 'requerido|max:120', 'hotel_nit' => 'requerido|max:20',
        'hotel_direccion' => 'requerido|max:200', 'hotel_ciudad' => 'requerido|max:80', 'hotel_telefono' => 'requerido|telefono',
        'hotel_whatsapp' => 'opcional|telefono', 'hotel_correo' => 'requerido|correo', 'hora_checkin' => 'requerido|regex:/^\d{2}:\d{2}$/',
        'hora_checkout' => 'requerido|regex:/^\d{2}:\d{2}$/', 'anticipo_porcentaje' => 'requerido|numero|minimo:0|maximo:100',
    ];

    public function index(): Respuesta
    {
        $valores = [];
        foreach (BaseDatos::filas('SELECT * FROM configuracion') as $f) {
            $valores[$f['clave']] = $f;
        }
        return $this->vista('configuracion/index', ['titulo' => 'Configuración', 'valores' => $valores]);
    }

    public function guardar(): Respuesta
    {
        $datos = $this->validar(self::CAMPOS, ['hotel_nombre' => 'nombre comercial', 'hotel_correo' => 'correo', 'hotel_telefono' => 'teléfono']);
        BaseDatos::transaccion(function () use ($datos) {
            foreach ($datos as $clave => $valor) {
                BaseDatos::consulta('INSERT INTO configuracion (clave, valor) VALUES (:c, :v) ON DUPLICATE KEY UPDATE valor = VALUES(valor)', ['c' => $clave, 'v' => (string) $valor]);
            }
        });
        $this->bitacora('Configuración', 'Actualizar datos del hotel', 'Parámetros generales actualizados');
        return $this->redirigir('/panel/configuracion', 'Configuración guardada.');
    }
}
