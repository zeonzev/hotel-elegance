<?php

declare(strict_types=1);

namespace Pruebas;

use App\Core\BaseDatos;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;

/**
 * Clase base para las pruebas que consultan la base de datos con PDO.
 *
 * Cada prueba se ejecuta dentro de una transacción que se revierte al
 * terminar, así los datos de demostración quedan intactos. Si MySQL no está
 * encendido, la prueba se marca como omitida en lugar de fallar.
 */
abstract class PruebaBaseDatos extends TestCase
{
    protected PDO $pdo;

    protected function setUp(): void
    {
        try {
            $this->pdo = BaseDatos::conexion();
        } catch (PDOException $e) {
            $this->markTestSkipped('No hay conexión con MySQL (encienda XAMPP): ' . $e->getMessage());
        }
        $this->pdo->beginTransaction();
    }

    protected function tearDown(): void
    {
        if (isset($this->pdo) && $this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    /** Ejecuta una consulta preparada y devuelve el primer valor. */
    protected function valor(string $sql, array $parametros = []): mixed
    {
        $sentencia = $this->pdo->prepare($sql);
        $sentencia->execute($parametros);
        return $sentencia->fetchColumn();
    }
}
