<?php
/**
 * Front controller: toda petición web del sistema entra por aquí.
 */

declare(strict_types=1);

define('RAIZ', dirname(__DIR__));

require RAIZ . '/app/bootstrap.php';

App\Core\Aplicacion::ejecutar();
