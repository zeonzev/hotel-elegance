<?php

declare(strict_types=1);

namespace App\Servicios;

use App\Core\Autenticacion;
use App\Core\BaseDatos;
use App\Core\ExcepcionValidacion;

/**
 * CU-10 Actualizar estado de habitación, con historial (historial_estado_habitacion).
 * Lo usan también el check-in (OCUPADA) y el check-out (DISPONIBLE / LIMPIEZA).
 */
final class HabitacionServicio
{
    public const ESTADOS = ['DISPONIBLE', 'OCUPADA', 'LIMPIEZA', 'MANTENIMIENTO'];

    /**
     * Cambia el estado y deja el registro en el historial.
     * Devuelve false si el estado elegido es igual al actual (flujo 2a: no hay cambios).
     */
    public static function cambiarEstado(int $idHabitacion, string $nuevo, ?string $motivo = null, bool $desdeRecepcion = false): bool
    {
        if (!in_array($nuevo, self::ESTADOS, true)) {
            throw new ExcepcionValidacion(['estado' => 'Seleccione un estado válido.']);
        }
        $habitacion = BaseDatos::fila('SELECT id_habitacion, numero, estado FROM habitacion WHERE id_habitacion = ? FOR UPDATE', [$idHabitacion]);
        if (!$habitacion) {
            throw new ExcepcionValidacion(['estado' => 'La habitación no existe.']);
        }
        if ($habitacion['estado'] === $nuevo) {
            return false;
        }
        if (!$desdeRecepcion && $habitacion['estado'] === 'OCUPADA' && self::estadiaAbierta($idHabitacion)) {
            throw new ExcepcionValidacion(['estado' => "La habitación {$habitacion['numero']} tiene un huésped alojado. Registre primero su check-out."]);
        }
        BaseDatos::actualizar('habitacion', ['estado' => $nuevo], 'id_habitacion = :id', ['id' => $idHabitacion]);
        BaseDatos::insertar('historial_estado_habitacion', [
            'id_habitacion'   => $idHabitacion,
            'estado_anterior' => $habitacion['estado'],
            'estado_nuevo'    => $nuevo,
            'motivo'          => $motivo ? mb_substr($motivo, 0, 255) : null,
            'id_usuario'      => Autenticacion::id(),
        ]);
        return true;
    }

    public static function estadiaAbierta(int $idHabitacion): ?array
    {
        return BaseDatos::fila(
            "SELECT r.id_reserva, r.codigo, r.fecha_salida, CONCAT(h.nombres, ' ', h.apellidos) AS huesped
               FROM reserva r
               JOIN detalle_reserva d ON d.id_reserva = r.id_reserva
               JOIN huesped h ON h.id_huesped = r.id_huesped
              WHERE d.id_habitacion = ? AND r.estado = 'EN_CURSO' LIMIT 1",
            [$idHabitacion]
        );
    }

    public static function reservasFuturas(int $idHabitacion): int
    {
        return (int) BaseDatos::valor(
            "SELECT COUNT(*) FROM detalle_reserva d JOIN reserva r ON r.id_reserva = d.id_reserva
              WHERE d.id_habitacion = ? AND r.estado IN ('PENDIENTE', 'CONFIRMADA') AND r.fecha_salida > CURDATE()",
            [$idHabitacion]
        );
    }
}
