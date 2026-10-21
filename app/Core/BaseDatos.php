<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;
use Throwable;

/**
 * Conexión única PDO a MySQL/MariaDB con consultas preparadas.
 */
final class BaseDatos
{
    private static ?PDO $pdo = null;

    public static function conexion(): PDO
    {
        if (self::$pdo === null) {
            self::$pdo = self::crear(
                (string) Configuracion::obtener('bd.host'),
                (int) Configuracion::obtener('bd.puerto'),
                (string) Configuracion::obtener('bd.usuario'),
                (string) Configuracion::obtener('bd.clave'),
                (string) Configuracion::obtener('bd.nombre')
            );
        }
        return self::$pdo;
    }

    public static function crear(string $host, int $puerto, string $usuario, string $clave, ?string $nombre = null): PDO
    {
        $dsn = sprintf('mysql:host=%s;port=%d;charset=utf8mb4%s', $host, $puerto, $nombre ? ';dbname=' . $nombre : '');
        $pdo = new PDO($dsn, $usuario, $clave, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
            PDO::ATTR_TIMEOUT            => 5,
        ]);
        // Bolivia: UTC-4 todo el año (sin horario de verano)
        $pdo->exec("SET time_zone = '-04:00', NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        return $pdo;
    }

    /** Reemplaza la conexión (pruebas e instalador). */
    public static function usar(?PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    public static function consulta(string $sql, array $parametros = []): PDOStatement
    {
        $sentencia = self::conexion()->prepare($sql);
        foreach ($parametros as $clave => $valor) {
            $nombre = is_int($clave) ? $clave + 1 : (str_starts_with($clave, ':') ? $clave : ':' . $clave);
            $tipo = match (true) {
                is_int($valor)  => PDO::PARAM_INT,
                is_bool($valor) => PDO::PARAM_BOOL,
                $valor === null => PDO::PARAM_NULL,
                default         => PDO::PARAM_STR,
            };
            $sentencia->bindValue($nombre, $valor, $tipo);
        }
        $sentencia->execute();
        return $sentencia;
    }

    public static function fila(string $sql, array $parametros = []): ?array
    {
        $fila = self::consulta($sql, $parametros)->fetch();
        return $fila === false ? null : $fila;
    }

    public static function filas(string $sql, array $parametros = []): array
    {
        return self::consulta($sql, $parametros)->fetchAll();
    }

    public static function valor(string $sql, array $parametros = []): mixed
    {
        $valor = self::consulta($sql, $parametros)->fetchColumn();
        return $valor === false ? null : $valor;
    }

    public static function insertar(string $tabla, array $datos): int
    {
        $columnas = array_keys($datos);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $tabla,
            implode(', ', $columnas),
            implode(', ', array_map(fn ($c) => ':' . $c, $columnas))
        );
        self::consulta($sql, $datos);
        return (int) self::conexion()->lastInsertId();
    }

    public static function actualizar(string $tabla, array $datos, string $donde, array $parametros = []): int
    {
        $asignaciones = implode(', ', array_map(fn ($c) => "$c = :set_$c", array_keys($datos)));
        $valores = [];
        foreach ($datos as $c => $v) {
            $valores['set_' . $c] = $v;
        }
        return self::consulta("UPDATE $tabla SET $asignaciones WHERE $donde", $valores + $parametros)->rowCount();
    }

    /**
     * Ejecuta la función dentro de una transacción. Si ya hay una transacción
     * abierta (por ejemplo en las pruebas), sólo ejecuta la función.
     */
    public static function transaccion(callable $funcion): mixed
    {
        $pdo = self::conexion();
        if ($pdo->inTransaction()) {
            return $funcion($pdo);
        }
        $pdo->beginTransaction();
        try {
            $resultado = $funcion($pdo);
            $pdo->commit();
            return $resultado;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
