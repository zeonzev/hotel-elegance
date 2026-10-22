<?php

declare(strict_types=1);

namespace App\Servicios;

use App\Core\BaseDatos;
use PDO;
use RuntimeException;
use Throwable;

/**
 * Instalador opcional: crea la base de datos e importa database/hotel_elegance.sql
 * (incluye el manejo de DELIMITER para disparadores) sin usar phpMyAdmin.
 */
final class InstaladorServicio
{
    public const ARCHIVO = RAIZ . '/database/hotel_elegance.sql';

    /** Estado actual: conexión al servidor y si la base ya tiene datos. */
    public static function estado(string $host, int $puerto, string $usuario, string $clave, string $nombre): array
    {
        $estado = ['servidor' => false, 'version' => null, 'existe' => false, 'tablas' => 0, 'usuarios' => 0, 'error' => null];
        try {
            $pdo = BaseDatos::crear($host, $puerto, $usuario, $clave);
            $estado['servidor'] = true;
            $estado['version'] = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
            $existe = $pdo->prepare('SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?');
            $existe->execute([$nombre]);
            $estado['existe'] = (bool) $existe->fetchColumn();
            if ($estado['existe']) {
                $t = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ?');
                $t->execute([$nombre]);
                $estado['tablas'] = (int) $t->fetchColumn();
                if ($estado['tablas'] > 0) {
                    try {
                        $estado['usuarios'] = (int) $pdo->query('SELECT COUNT(*) FROM `' . str_replace('`', '', $nombre) . '`.usuario')->fetchColumn();
                    } catch (Throwable) {
                        $estado['usuarios'] = 0;
                    }
                }
            }
        } catch (Throwable $e) {
            $estado['error'] = $e->getMessage();
        }
        return $estado;
    }

    /** Importa el script completo. Devuelve la cantidad de sentencias ejecutadas. */
    public static function instalar(string $host, int $puerto, string $usuario, string $clave, string $nombre): int
    {
        if (!preg_match('/^[A-Za-z0-9_]{1,64}$/', $nombre)) {
            throw new RuntimeException('El nombre de la base de datos sólo admite letras, números y guion bajo.');
        }
        if (!is_file(self::ARCHIVO)) {
            throw new RuntimeException('No se encontró database/hotel_elegance.sql.');
        }
        @set_time_limit(300);
        $pdo = BaseDatos::crear($host, $puerto, $usuario, $clave);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
        $sql = (string) file_get_contents(self::ARCHIVO);
        if ($nombre !== 'hotel_elegance') {
            $sql = str_replace('`hotel_elegance`', '`' . $nombre . '`', $sql);
        }
        $ejecutadas = 0;
        foreach (self::sentencias($sql) as $sentencia) {
            $pdo->exec($sentencia);
            $ejecutadas++;
        }
        return $ejecutadas;
    }

    /** Divide el script en sentencias respetando DELIMITER, comentarios y cadenas. */
    public static function sentencias(string $sql): array
    {
        $sentencias = [];
        $delimitador = ';';
        $actual = '';
        foreach (preg_split('/\R/', $sql) as $linea) {
            $recortada = trim($linea);
            if ($actual === '' && ($recortada === '' || str_starts_with($recortada, '--'))) {
                continue;
            }
            if (preg_match('/^DELIMITER\s+(\S+)$/i', $recortada, $m)) {
                $delimitador = $m[1];
                continue;
            }
            $actual .= $linea . "\n";
            if (str_ends_with(rtrim($linea), $delimitador) && !self::dentroDeCadena($actual)) {
                $sentencia = trim(substr(rtrim($actual), 0, -strlen($delimitador)));
                if ($sentencia !== '') {
                    $sentencias[] = $sentencia;
                }
                $actual = '';
            }
        }
        if (trim($actual) !== '') {
            $sentencias[] = trim($actual);
        }
        return $sentencias;
    }

    private static function dentroDeCadena(string $texto): bool
    {
        // Cuenta comillas simples no escapadas: un número impar indica una cadena abierta
        $limpio = str_replace(["\\\\", "\\'", "''"], '', $texto);
        return substr_count($limpio, "'") % 2 === 1;
    }

    /** Escribe el archivo .env con la conexión indicada. */
    public static function guardarEnv(string $host, int $puerto, string $usuario, string $clave, string $nombre): bool
    {
        $contenido = "# Generado por el instalador web de Hotel Elegance (" . date('Y-m-d H:i') . ")\n"
            . "DB_HOST=$host\nDB_PUERTO=$puerto\nDB_NOMBRE=$nombre\nDB_USUARIO=$usuario\nDB_CLAVE=\"" . str_replace('"', '', $clave) . "\"\n"
            . "APP_DEBUG=false\nSESION_INACTIVIDAD=120\n";
        return @file_put_contents(RAIZ . '/.env', $contenido) !== false;
    }
}
