<?php
/**
 * Prueba de humo: recorre las rutas GET del panel con cada rol y verifica
 * que no haya errores 500 ni avisos de PHP en el HTML.
 *
 * Uso:  C:\xampp\php\php.exe tests\humo.php [http://localhost/hotel-elegance]
 */

declare(strict_types=1);

$base = rtrim($argv[1] ?? 'http://localhost/hotel-elegance', '/');
$usuarios = ['admin' => 'Admin123*', 'recepcion' => 'Recepcion123*', 'gerente' => 'Gerente123*'];
$rutas = [
    '/panel', '/panel/perfil', '/panel/buscar?q=mam', '/panel/api/buscar?q=101',
    '/panel/personal', '/panel/personal/nuevo', '/panel/personal/1/editar', '/panel/usuarios', '/panel/bitacora', '/panel/configuracion',
    '/panel/huespedes', '/panel/huespedes/nuevo', '/panel/huespedes/1', '/panel/huespedes/1/editar', '/panel/api/huespedes?q=ma',
    '/panel/habitaciones', '/panel/habitaciones?vista=lista', '/panel/habitaciones/1', '/panel/habitaciones/nueva', '/panel/habitaciones/1/editar',
    '/panel/categorias', '/panel/categorias/nueva', '/panel/categorias/1/editar',
    '/panel/reservas', '/panel/reservas?estado=CONFIRMADA', '/panel/reservas/nueva', '/panel/reservas/1', '/panel/reservas/1/recibo', '/panel/recepcion', '/panel/disponibilidad', '/panel/disponibilidad?ingreso=2030-01-01&salida=2030-01-03', '/panel/solicitudes', '/panel/solicitudes?pestana=mensajes',
    '/panel/sobreventa', '/panel/tarifas', '/panel/reportes', '/panel/no-existe',
];

function pedir(string $url, string $cookies, ?array $post = null): array
{
    $c = curl_init($url);
    curl_setopt_array($c, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_COOKIEJAR => $cookies,
        CURLOPT_COOKIEFILE => $cookies, CURLOPT_TIMEOUT => 30,
    ]);
    if ($post !== null) {
        curl_setopt($c, CURLOPT_POST, true);
        curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $cuerpo = (string) curl_exec($c);
    $codigo = (int) curl_getinfo($c, CURLINFO_HTTP_CODE);
    curl_close($c);
    return [$codigo, $cuerpo];
}

$fallos = 0;
foreach ($usuarios as $usuario => $clave) {
    $cookies = tempnam(sys_get_temp_dir(), 'he');
    [, $html] = pedir("$base/login", $cookies);
    preg_match('/name="_token" value="([a-f0-9]+)"/', $html, $m);
    [$codigo] = pedir("$base/login", $cookies, ['_token' => $m[1] ?? '', 'usuario' => $usuario, 'clave' => $clave]);
    echo "\n== $usuario (login HTTP $codigo) ==\n";
    foreach ($rutas as $ruta) {
        [$codigo, $cuerpo] = pedir($base . $ruta, $cookies);
        $avisoPhp = preg_match('/(Fatal error|Warning:|Notice:|Deprecated:|Uncaught)/', $cuerpo);
        $esperado = in_array($codigo, [200, 302, 403, 404, 501], true) && !$avisoPhp;
        if (!$esperado) {
            $fallos++;
        }
        printf("  %s %3d  %s\n", $esperado ? 'OK ' : 'ERR', $codigo, $ruta);
    }
    @unlink($cookies);
}
echo $fallos ? "\n$fallos ruta(s) con errores.\n" : "\nTodas las rutas respondieron sin errores.\n";
exit($fallos ? 1 : 0);
