<?php
/**
 * Arranque de las pruebas unitarias con PHPUnit.
 *
 * Carga las dependencias de Composer (PHPUnit) y luego el arranque propio
 * del sistema, que registra la autocarga de App\ y los helpers.
 *
 * Uso:  vendor\bin\phpunit
 */

declare(strict_types=1);

define('RAIZ', dirname(__DIR__));

require RAIZ . '/vendor/autoload.php';
require RAIZ . '/app/bootstrap.php';

$_SESSION = [];
