<?php
/**
 * Generador del archivo database/hotel_elegance.sql (estructura + datos de prueba).
 *
 * Uso (desde la raíz del proyecto):
 *     C:\xampp\php\php.exe database\scripts\generar_sql.php [AAAA-MM-DD]
 *
 * Las fechas de las reservas se escriben RELATIVAS a la fecha de importación
 * (CURDATE() + INTERVAL n DAY). Así, cuando el docente importa el SQL, siempre
 * hay llegadas y salidas "de hoy", huéspedes alojados y reservas futuras.
 * La fecha opcional sólo se usa para calcular estacionalidad y tarifas.
 */

declare(strict_types=1);

mt_srand(2026);
date_default_timezone_set('America/La_Paz');

$referencia = new DateTimeImmutable($argv[1] ?? 'today');
$raiz = dirname(__DIR__, 2);

// ---------------------------------------------------------------------
// Utilidades de escritura SQL
// ---------------------------------------------------------------------
function q(?string $v): string
{
    if ($v === null) {
        return 'NULL';
    }
    return "'" . str_replace(['\\', "'"], ['\\\\', "''"], $v) . "'";
}

function d(int $offset): string
{
    return $offset === 0 ? 'CURDATE()' : 'CURDATE() + INTERVAL ' . $offset . ' DAY';
}

function dt(int $offset, string $hora): string
{
    return 'TIMESTAMP(' . d($offset) . ", '" . $hora . ":00')";
}

function insertar(array &$salida, string $tabla, array $columnas, array $filas, int $lote = 250): void
{
    foreach (array_chunk($filas, $lote) as $bloque) {
        $valores = array_map(fn ($f) => '(' . implode(', ', $f) . ')', $bloque);
        $salida[] = 'INSERT INTO ' . $tabla . ' (' . implode(', ', $columnas) . ") VALUES\n " . implode(",\n ", $valores) . ';';
    }
}

function elegir(array $pesos)
{
    $total = array_sum($pesos);
    $r = mt_rand(1, (int) $total);
    foreach ($pesos as $clave => $peso) {
        $r -= $peso;
        if ($r <= 0) {
            return $clave;
        }
    }
    return array_key_last($pesos);
}

function al(array $lista)
{
    return $lista[mt_rand(0, count($lista) - 1)];
}

function prob(float $p): bool
{
    return mt_rand() / mt_getrandmax() < $p;
}

function hora(int $desde, int $hasta): string
{
    return sprintf('%02d:%02d', mt_rand($desde, $hasta), al([0, 5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55]));
}

function sinAcentos(string $s): string
{
    $s = strtr($s, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n', 'ü' => 'u', 'ç' => 'c',
        'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', 'Ñ' => 'n', 'è' => 'e', 'ã' => 'a', 'ê' => 'e', 'ô' => 'o']);
    return strtolower(preg_replace('/[^A-Za-z]/', '', $s));
}

$salida = [];
$salida[] = "-- =====================================================================";
$salida[] = "--  DATOS DE PRUEBA (generados por database/scripts/generar_sql.php)";
$salida[] = "--  Todas las fechas son relativas a CURDATE(): se recalculan al importar.";
$salida[] = "-- =====================================================================";
$salida[] = 'USE `hotel_elegance`;';
$salida[] = 'SET FOREIGN_KEY_CHECKS = 0;';

// ---------------------------------------------------------------------
// Personal y cuentas de usuario (CU-01, CU-02, CU-03)
// ---------------------------------------------------------------------
$empleados = [
    // ci, nombres, apellidos, cargo, telefono, correo, direccion, antiguedad (días)
    ['4785123', 'Patricia', 'Villarroel Aguilar', 1, '+591 71523480', 'patricia.villarroel@hotelelegance.bo', 'Calle 21 de Calacoto N.º 820', 2900],
    ['6034871', 'Carla', 'Mendoza Rojas', 2, '+591 72018845', 'carla.mendoza@hotelelegance.bo', 'Av. Busch N.º 1520, Miraflores', 1850],
    ['8452210', 'Luis Fernando', 'Mamani Quispe', 4, '+591 76540213', 'luis.mamani@hotelelegance.bo', 'Calle Lucas Jaimes N.º 455', 920],
    ['9120345', 'Rocío', 'Condori Choque', 4, '+591 69874512', 'rocio.condori@hotelelegance.bo', 'Av. Juan Pablo II, El Alto', 310],
    ['5873310', 'Javier', 'Gutiérrez Flores', 5, '+591 70152698', 'javier.gutierrez@hotelelegance.bo', 'Calle Méndez Arcos N.º 712, Sopocachi', 1400],
    ['7015642', 'María', 'Huanca Limachi', 6, '+591 73320145', null, 'Zona Villa Fátima', 1100],
    ['6648921', 'Juan Carlos', 'Apaza Ticona', 7, '+591 71189632', null, 'Zona Villa Copacabana', 760],
    ['5412987', 'Gabriela', 'Rocha Salinas', 3, '+591 72284511', 'gabriela.rocha@hotelelegance.bo', 'Av. 6 de Agosto N.º 2210', 1300],
];
$filas = [];
foreach ($empleados as $i => $e) {
    $filas[] = [(string) ($i + 1), q($e[0]), q($e[1]), q($e[2]), (string) $e[3], q($e[4]), q($e[5]), q($e[6]), d(-$e[7]), "'ACTIVO'"];
}
insertar($salida, 'empleado', ['id_empleado', 'ci', 'nombres', 'apellidos', 'id_cargo', 'telefono', 'correo', 'direccion', 'fecha_contratacion', 'estado'], $filas);

$opcionesHash = ['cost' => 10];
$usuarios = [
    // id, id_empleado, id_rol, usuario, contraseña, cambiar_contraseña
    [1, 2, 1, 'admin', 'Admin123*', 0],
    [2, 3, 2, 'recepcion', 'Recepcion123*', 0],
    [3, 1, 3, 'gerente', 'Gerente123*', 0],
    [4, 4, 2, 'rcondori', 'Elegance2026*', 1],
];
$filas = [];
foreach ($usuarios as $u) {
    $filas[] = [(string) $u[0], (string) $u[1], (string) $u[2], q($u[3]), q(password_hash($u[4], PASSWORD_BCRYPT, $opcionesHash)), "'ACTIVO'", (string) $u[5], dt(-mt_rand(0, 2), hora(7, 9)), dt(-400, '09:00')];
}
insertar($salida, 'usuario', ['id_usuario', 'id_empleado', 'id_rol', 'nombre_usuario', 'contrasena_hash', 'estado', 'cambiar_contrasena', 'ultimo_acceso', 'creado_en'], $filas);
$recepcionistas = [2, 2, 2, 4, 4, 1];

// ---------------------------------------------------------------------
// Categorías y habitaciones (CU-08, CU-09)
// ---------------------------------------------------------------------
$categorias = [
    1 => ['Simple', 'Refugio sereno para el viajero de negocios: cama amplia, escritorio de trabajo y vista a la ciudad de La Paz.', 1, 320, '1 cama de 1½ plazas', 18,
        'Wi-Fi de alta velocidad|Escritorio de trabajo|Smart TV 43"|Ducha de lluvia|Calefacción|Caja de seguridad', 'simple-1.jpg'],
    2 => ['Doble', 'Dos camas independientes y espacio generoso, ideal para colegas o amigos que exploran la ciudad juntos.', 2, 460, '2 camas de 1½ plazas', 26,
        'Wi-Fi de alta velocidad|Dos camas individuales|Smart TV 50"|Minibar|Calefacción|Set de té andino', 'doble-1.jpg'],
    3 => ['Matrimonial', 'Cama Queen con ropa de cama de algodón egipcio, iluminación cálida y detalles de diseño boliviano contemporáneo.', 2, 520, '1 cama Queen', 28,
        'Wi-Fi de alta velocidad|Cama Queen premium|Smart TV 50"|Cafetera de cápsulas|Bata y pantuflas|Calefacción', 'matrimonial-1.jpg'],
    4 => ['Suite', 'Nuestra experiencia más exclusiva: sala independiente, tina, vista panorámica al Illimani y atención personalizada.', 4, 890, '1 cama King + sofá cama', 48,
        'Vista al Illimani|Sala de estar independiente|Tina de hidromasaje|Smart TV 65"|Minibar premium|Desayuno en la habitación', 'suite-1.jpg'],
];
$filas = [];
foreach ($categorias as $id => $c) {
    $filas[] = [(string) $id, q($c[0]), q($c[1]), (string) $c[2], number_format($c[3], 2, '.', ''), q($c[4]), (string) $c[5], q($c[6]), q($c[7]), '1'];
}
insertar($salida, 'categoria_habitacion', ['id_categoria', 'nombre', 'descripcion', 'capacidad', 'tarifa_base', 'camas', 'superficie_m2', 'comodidades', 'imagen', 'estado'], $filas);

$plano = [
    1 => ['101' => 1, '102' => 1, '103' => 1, '104' => 1, '105' => 2, '106' => 2],
    2 => ['201' => 1, '202' => 1, '203' => 1, '204' => 2, '205' => 2, '206' => 2, '207' => 3],
    3 => ['301' => 2, '302' => 2, '303' => 3, '304' => 3, '305' => 3, '306' => 3],
    4 => ['401' => 3, '402' => 3, '403' => 4, '404' => 4, '405' => 4],
];
$descripciones = [1 => 'Vista interior, zona tranquila', 2 => 'Vista a la avenida Saavedra', 3 => 'Vista a la ciudad', 4 => 'Vista panorámica al Illimani'];
$habitaciones = []; // id => [numero, piso, categoria]
$id = 0;
foreach ($plano as $piso => $nums) {
    foreach ($nums as $numero => $cat) {
        $habitaciones[++$id] = ['numero' => (string) $numero, 'piso' => $piso, 'categoria' => $cat];
    }
}
$enMantenimiento = 7;   // habitación 206: mantenimiento preventivo de calefacción
$enLimpieza = 2;        // habitación 102

// ---------------------------------------------------------------------
// Temporadas y tarifas (CU-20)
// ---------------------------------------------------------------------
$temporadas = [
    // nombre, tipo, inicio, fin, multiplicador, color, descripcion
    ['Fin de año 2025', 'ESPECIAL', '2025-12-28', '2026-01-02', 1.20, '#B4553F', 'Celebraciones de Año Nuevo en La Paz.'],
    ['Temporada baja de lluvias 2026', 'BAJA', '2026-01-05', '2026-03-31', 0.90, '#5B7FA3', 'Época de lluvias: menor flujo turístico.'],
    ['Feria de Alasitas 2026', 'ESPECIAL', '2026-01-24', '2026-01-31', 1.20, '#C6A15B', 'Feria de la miniatura y el Ekeko (Patrimonio UNESCO).'],
    ['Carnaval 2026', 'ESPECIAL', '2026-02-14', '2026-02-17', 1.30, '#9C4F8E', 'Carnaval paceño y feriados nacionales.'],
    ['Gran Poder 2026', 'ESPECIAL', '2026-05-29', '2026-05-31', 1.35, '#B4553F', 'Fiesta del Señor del Gran Poder, Patrimonio de la Humanidad.'],
    ['Temporada alta de invierno 2026', 'ALTA', '2026-06-15', '2026-08-31', 1.15, '#2F6F62', 'Vacaciones de invierno y turismo internacional.'],
    ['Fiestas Patrias 2026', 'ESPECIAL', '2026-08-05', '2026-08-07', 1.20, '#C6A15B', 'Aniversario de Bolivia (6 de agosto).'],
    ['Todos Santos 2026', 'ESPECIAL', '2026-10-31', '2026-11-02', 1.15, '#7A5C3E', 'Feriado de Todos Santos.'],
    ['Fin de año 2026', 'ESPECIAL', '2026-12-28', '2027-01-02', 1.20, '#B4553F', 'Celebraciones de Año Nuevo en La Paz.'],
    ['Temporada baja de lluvias 2027', 'BAJA', '2027-01-04', '2027-03-31', 0.90, '#5B7FA3', 'Época de lluvias: menor flujo turístico.'],
    ['Feria de Alasitas 2027', 'ESPECIAL', '2027-01-24', '2027-01-31', 1.20, '#C6A15B', 'Feria de la miniatura y el Ekeko (Patrimonio UNESCO).'],
    ['Carnaval 2027', 'ESPECIAL', '2027-02-06', '2027-02-09', 1.30, '#9C4F8E', 'Carnaval paceño y feriados nacionales.'],
    ['Gran Poder 2027', 'ESPECIAL', '2027-05-21', '2027-05-23', 1.35, '#B4553F', 'Fiesta del Señor del Gran Poder.'],
    ['Temporada alta de invierno 2027', 'ALTA', '2027-06-15', '2027-08-31', 1.15, '#2F6F62', 'Vacaciones de invierno y turismo internacional.'],
];
$filasT = [];
$filasTar = [];
$tarifas = []; // [id_temporada => [tipo, inicio, fin, [cat => precio]]]
foreach ($temporadas as $i => $t) {
    $idT = $i + 1;
    $filasT[] = [(string) $idT, q($t[0]), q($t[1]), q($t[2]), q($t[3]), q($t[6]), q($t[5]), '1'];
    $precios = [];
    foreach ($categorias as $idC => $c) {
        $precio = round($c[3] * $t[4] / 10) * 10;
        $precios[$idC] = $precio;
        $filasTar[] = [(string) $idT, (string) $idC, number_format($precio, 2, '.', '')];
    }
    $tarifas[$idT] = [$t[1], $t[2], $t[3], $precios];
}
insertar($salida, 'temporada', ['id_temporada', 'nombre', 'tipo', 'fecha_inicio', 'fecha_fin', 'descripcion', 'color', 'estado'], $filasT);
insertar($salida, 'tarifa_temporada', ['id_temporada', 'id_categoria', 'precio_noche'], $filasTar);

/** Misma regla que App\Servicios\TarifaServicio: ESPECIAL > ALTA > BAJA > tarifa base. */
function precioNoche(int $cat, string $fecha, array $tarifas, array $categorias): float
{
    $prioridad = ['ESPECIAL' => 3, 'ALTA' => 2, 'BAJA' => 1];
    $mejor = null;
    foreach ($tarifas as $t) {
        if ($fecha >= $t[1] && $fecha <= $t[2] && ($mejor === null || $prioridad[$t[0]] > $prioridad[$mejor[0]])) {
            $mejor = $t;
        }
    }
    return $mejor ? (float) $mejor[3][$cat] : (float) $categorias[$cat][3];
}

// ---------------------------------------------------------------------
// Huéspedes (CU-05)
// ---------------------------------------------------------------------
$nombresBo = [
    'M' => ['Juan', 'Carlos', 'Luis', 'José', 'Miguel', 'Jorge', 'Fernando', 'Marco Antonio', 'Rodrigo', 'Álvaro', 'Diego', 'Sergio', 'Gonzalo', 'Ramiro', 'Wilfredo', 'Edwin', 'Freddy', 'Hugo', 'Mauricio', 'Iván', 'Gustavo', 'Rubén', 'Ronald', 'Grover', 'Limbert', 'Marcelo', 'Javier', 'René', 'Óscar', 'Reynaldo'],
    'F' => ['María', 'Ana', 'Carla', 'Patricia', 'Gabriela', 'Daniela', 'Verónica', 'Lucía', 'Paola', 'Silvia', 'Rocío', 'Fabiola', 'Mónica', 'Andrea', 'Ximena', 'Valeria', 'Lourdes', 'Roxana', 'Sandra', 'Wendy', 'Jhenny', 'Mariela', 'Claudia', 'Noemí', 'Evelyn', 'Cecilia', 'Brenda', 'Tatiana', 'Mireya', 'Delia'],
];
$apellidosBo = ['Mamani', 'Quispe', 'Condori', 'Choque', 'Flores', 'Gutiérrez', 'Rojas', 'Vargas', 'Mendoza', 'Torrez', 'Huanca', 'Apaza', 'Limachi', 'Ticona', 'Aguilar', 'Villarroel', 'Salinas', 'Rocha', 'Morales', 'Fernández', 'López', 'Pérez', 'Rodríguez', 'García', 'Céspedes', 'Arce', 'Suárez', 'Paz', 'Antezana', 'Zeballos', 'Calle', 'Chura', 'Laura', 'Copa', 'Poma', 'Callisaya', 'Alanoca', 'Gemio', 'Saavedra', 'Peñaranda', 'Siles', 'Urquidi', 'Montaño', 'Justiniano', 'Vaca', 'Roca', 'Saucedo', 'Égüez', 'Terrazas', 'Ribera'];
$paises = [
    // nacionalidad => [peso, ciudades, nombresM, nombresF, apellidos, tipoDoc, prefijoTel]
    'Boliviana' => [58, ['Cochabamba' => 26, 'Santa Cruz de la Sierra' => 30, 'La Paz' => 6, 'El Alto' => 3, 'Oruro' => 9, 'Potosí' => 7, 'Sucre' => 9, 'Tarija' => 8, 'Trinidad' => 4, 'Cobija' => 2, 'Uyuni' => 2], null, null, null, 1, '+591'],
    'Peruana' => [10, ['Lima, Perú' => 5, 'Cusco, Perú' => 3, 'Arequipa, Perú' => 2, 'Puno, Perú' => 2], ['Luis', 'Jorge', 'César', 'Renzo', 'Piero', 'Walter'], ['Rosa', 'Milagros', 'Katherine', 'Fiorella', 'Giuliana', 'Nathaly'], ['Huamán', 'Ramos', 'Castillo', 'Chávez', 'Torres', 'Sánchez', 'Paredes', 'Vásquez', 'Gonzales'], 3, '+51'],
    'Argentina' => [7, ['Buenos Aires, Argentina' => 5, 'Salta, Argentina' => 2, 'Córdoba, Argentina' => 2, 'Jujuy, Argentina' => 1], ['Martín', 'Lucas', 'Mateo', 'Joaquín', 'Santiago', 'Facundo'], ['Sofía', 'Valentina', 'Camila', 'Florencia', 'Agustina', 'Micaela'], ['González', 'Rodríguez', 'Fernández', 'Gómez', 'Martínez', 'Romero', 'Sosa', 'Álvarez', 'Benítez'], 3, '+54'],
    'Chilena' => [5, ['Santiago, Chile' => 4, 'Arica, Chile' => 2, 'Iquique, Chile' => 1], ['Benjamín', 'Tomás', 'Vicente', 'Matías', 'Cristóbal'], ['Ignacia', 'Catalina', 'Javiera', 'Antonia', 'Francisca'], ['Muñoz', 'Soto', 'Contreras', 'Silva', 'Araya', 'Espinoza', 'Reyes'], 2, '+56'],
    'Brasileña' => [4, ['São Paulo, Brasil' => 3, 'Río de Janeiro, Brasil' => 2, 'Curitiba, Brasil' => 1], ['João', 'Pedro', 'Gabriel', 'Rafael', 'Thiago'], ['Ana Clara', 'Beatriz', 'Mariana', 'Larissa', 'Juliana'], ['Silva', 'Santos', 'Oliveira', 'Souza', 'Lima', 'Pereira', 'Costa'], 2, '+55'],
    'Española' => [3, ['Madrid, España' => 3, 'Barcelona, España' => 2, 'Valencia, España' => 1], ['Pablo', 'Alejandro', 'Javier', 'Sergio', 'Daniel'], ['Lucía', 'Marta', 'Elena', 'Carmen', 'Irene'], ['García', 'Martínez', 'López', 'Sánchez', 'Romero', 'Navarro', 'Molina'], 2, '+34'],
    'Francesa' => [3, ['París, Francia' => 3, 'Lyon, Francia' => 1, 'Marsella, Francia' => 1], ['Julien', 'Antoine', 'Nicolas', 'Hugo', 'Louis'], ['Camille', 'Chloé', 'Léa', 'Manon', 'Inès'], ['Martin', 'Bernard', 'Dubois', 'Moreau', 'Laurent', 'Lefèvre'], 2, '+33'],
    'Alemana' => [2, ['Berlín, Alemania' => 2, 'Múnich, Alemania' => 1, 'Hamburgo, Alemania' => 1], ['Lukas', 'Jonas', 'Felix', 'Maximilian', 'Paul'], ['Anna', 'Lena', 'Laura', 'Hannah', 'Mia'], ['Müller', 'Schmidt', 'Schneider', 'Fischer', 'Weber', 'Becker'], 2, '+49'],
    'Estadounidense' => [3, ['Nueva York, EE. UU.' => 2, 'Miami, EE. UU.' => 2, 'Austin, EE. UU.' => 1], ['Michael', 'David', 'James', 'Ryan', 'Kevin'], ['Emily', 'Sarah', 'Jessica', 'Ashley', 'Megan'], ['Smith', 'Johnson', 'Brown', 'Miller', 'Davis', 'Wilson'], 2, '+1'],
    'Colombiana' => [2, ['Bogotá, Colombia' => 2, 'Medellín, Colombia' => 1], ['Andrés', 'Camilo', 'Sebastián', 'Felipe'], ['Natalia', 'Juliana', 'Manuela', 'Laura'], ['Restrepo', 'Ramírez', 'Cárdenas', 'Ospina', 'Gómez'], 2, '+57'],
    'Mexicana' => [2, ['Ciudad de México, México' => 2, 'Guadalajara, México' => 1], ['Alejandro', 'Emiliano', 'Diego', 'Rodrigo'], ['Fernanda', 'Ximena', 'Regina', 'Daniela'], ['Hernández', 'Ramírez', 'Cruz', 'Flores', 'Morales'], 2, '+52'],
];
$dominios = ['gmail.com' => 60, 'hotmail.com' => 18, 'outlook.com' => 10, 'yahoo.com' => 7, 'icloud.com' => 5];

$huespedes = [];
$documentos = [];
$totalHuespedes = 320;
$pesoHuesped = [];
for ($i = 1; $i <= $totalHuespedes; $i++) {
    $nac = elegir(array_map(fn ($p) => $p[0], $paises));
    $p = $paises[$nac];
    $sexo = prob(0.5) ? 'M' : 'F';
    if ($nac === 'Boliviana') {
        $nombre = al($nombresBo[$sexo]);
        $apellidos = al($apellidosBo) . ' ' . al($apellidosBo);
    } else {
        $nombre = al($sexo === 'M' ? $p[2] : $p[3]);
        $apellidos = in_array($nac, ['Peruana', 'Chilena', 'Colombiana', 'Mexicana', 'Española'], true)
            ? al($p[4]) . ' ' . al($p[4]) : al($p[4]);
    }
    $ciudad = elegir($p[1]);
    $tipoDoc = $p[5];
    do {
        $doc = match ($tipoDoc) {
            1 => (string) mt_rand(2100000, 14999999),
            3 => (string) mt_rand(10000000, 49999999),
            default => strtoupper(substr(sinAcentos($nac), 0, 2)) . mt_rand(1000000, 9999999),
        };
    } while (isset($documentos[$tipoDoc . $doc]));
    $documentos[$tipoDoc . $doc] = true;
    $tel = $p[6] === '+591' ? '+591 ' . al(['7', '6']) . mt_rand(1000000, 9999999) : $p[6] . ' ' . mt_rand(100000000, 999999999);
    $primerNombre = explode(' ', $nombre)[0];
    $primerApellido = explode(' ', $apellidos)[0];
    $correo = prob(0.88) ? sinAcentos($primerNombre) . al(['.', '_', '']) . sinAcentos($primerApellido) . (prob(0.4) ? (string) mt_rand(1, 99) : '') . '@' . elegir($dominios) : null;
    $nacimiento = $referencia->modify('-' . mt_rand(22 * 365, 68 * 365) . ' days')->format('Y-m-d');
    $huespedes[$i] = [
        'tipo' => $tipoDoc, 'doc' => $doc, 'nombres' => $nombre, 'apellidos' => $apellidos, 'nac' => $nac,
        'ciudad' => $ciudad, 'tel' => $tel, 'correo' => $correo, 'nacimiento' => $nacimiento,
    ];
    // Unos pocos huéspedes frecuentes (viajeros corporativos) concentran más estadías
    $pesoHuesped[$i] = $i <= 18 ? 14 : ($i <= 60 ? 4 : 1);
}

// ---------------------------------------------------------------------
// Simulación de ocupación: reservas en los últimos 12 meses y próximos 2
// ---------------------------------------------------------------------
$ocupacionMes = [1 => 0.58, 2 => 0.60, 3 => 0.46, 4 => 0.50, 5 => 0.62, 6 => 0.76, 7 => 0.84, 8 => 0.80, 9 => 0.64, 10 => 0.68, 11 => 0.60, 12 => 0.54];
$ajusteCategoria = [1 => 0.02, 2 => -0.04, 3 => 0.04, 4 => -0.10];
$duraciones = [1 => 24, 2 => 30, 3 => 21, 4 => 11, 5 => 7, 6 => 3, 7 => 4];
$duracionMedia = 0;
foreach ($duraciones as $n => $peso) {
    $duracionMedia += $n * $peso;
}
$duracionMedia /= array_sum($duraciones);

function ocupacionObjetivo(int $offset, int $cat, DateTimeImmutable $ref, array $ocupacionMes, array $ajusteCategoria, array $tarifas): float
{
    $fecha = $ref->modify(($offset >= 0 ? '+' : '') . $offset . ' days');
    $o = $ocupacionMes[(int) $fecha->format('n')] + $ajusteCategoria[$cat];
    if (in_array((int) $fecha->format('N'), [5, 6], true)) {
        $o += 0.07;
    }
    $f = $fecha->format('Y-m-d');
    foreach ($tarifas as $t) {
        if ($t[0] === 'ESPECIAL' && $f >= $t[1] && $f <= $t[2]) {
            $o += 0.22;
        }
    }
    if ($offset >= 3 && $offset <= 6) {
        $o = 0.99; // fin de semana con congreso internacional: hotel casi lleno (alerta de sobreventa)
    } elseif ($offset > 2) {
        // Curva de reservas anticipadas: cuanto más lejana la fecha, menos reservas "en libros"
        $o *= max(0.28, 1 - ($offset - 2) * 0.013);
    }
    return max(0.05, min(0.97, $o));
}

$reservas = [];
foreach ($habitaciones as $idH => $h) {
    $dia = -372;
    while ($dia <= 60) {
        $bloqueada = ($idH === $enMantenimiento && $dia >= -3 && $dia <= 8);
        if ($bloqueada) {
            $dia++;
            continue;
        }
        $o = ocupacionObjetivo($dia, $h['categoria'], $referencia, $ocupacionMes, $ajusteCategoria, $tarifas);
        $s = $o / ($o + $duracionMedia * (1 - $o));
        if (prob($s)) {
            $noches = (int) elegir($duraciones);
            if ($idH === $enMantenimiento && $dia < -3 && $dia + $noches > -3) {
                $noches = max(1, -3 - $dia);
            }
            if ($idH === $enLimpieza && $dia < 0 && $dia + $noches > 0) {
                $noches = -$dia; // la habitación 102 tuvo salida hoy temprano y espera limpieza
            }
            if ($dia + $noches > -372) {
                $reservas[] = ['hab' => $idH, 'cat' => $h['categoria'], 'ing' => $dia, 'sal' => $dia + $noches];
            }
            $dia += $noches;
        } else {
            $dia++;
        }
    }
}
// Se descartan las reservas que terminan antes de la ventana de 12 meses
$reservas = array_values(array_filter($reservas, fn ($r) => $r['sal'] > -366));

// Reservas en sobreventa (sin habitación asignada) para el pico de demanda
$reservas[] = ['hab' => null, 'cat' => 3, 'ing' => 4, 'sal' => 6, 'sobreventa' => true];
$reservas[] = ['hab' => null, 'cat' => 2, 'ing' => 5, 'sal' => 7, 'sobreventa' => true];

$canales = [1 => 12, 2 => 16, 3 => 28, 4 => 18, 5 => 20, 6 => 6];
$anticipacionCanal = [1 => [0, 2], 2 => [1, 20], 3 => [1, 25], 4 => [3, 45], 5 => [2, 60], 6 => [15, 75]];

$todas = [];
foreach ($reservas as $r) {
    $r['canal'] = (int) elegir($canales);
    [$min, $max] = $anticipacionCanal[$r['canal']];
    $r['registro'] = min($r['ing'] - mt_rand($min, $max), 0);
    if ($r['ing'] >= 0 && $r['registro'] > 0) {
        $r['registro'] = 0;
    }
    $r['huesped'] = (int) elegir($pesoHuesped);
    $r['cancelada'] = false;
    $todas[] = $r;

    // Algunas reservas fueron canceladas antes de que la habitación se volviera a vender
    if (empty($r['sobreventa']) && prob(0.135)) {
        $c = $r;
        $c['cancelada'] = true;
        $c['huesped'] = (int) elegir($pesoHuesped);
        $c['canal'] = (int) elegir($canales);
        $desplazamiento = mt_rand(-1, 1);
        $c['ing'] += $desplazamiento;
        $c['sal'] = max($c['ing'] + 1, $c['sal'] + $desplazamiento + mt_rand(-1, 1));
        [$min, $max] = $anticipacionCanal[$c['canal']];
        $c['registro'] = min($c['ing'] - mt_rand(max(2, $min), $max + 10), -1);
        $limite = min($c['ing'] - 1, 0);
        $c['fecha_cancelacion'] = mt_rand(min($c['registro'] + 1, $limite), $limite);
        $todas[] = $c;
    }
}
usort($todas, fn ($a, $b) => [$a['registro'], $a['ing']] <=> [$b['registro'], $b['ing']]);

$motivos = [
    'CAMBIO_DE_PLANES' => [30, ['El huésped reprogramó su viaje para otra fecha.', 'Cambio de itinerario del grupo familiar.', 'Postergó el viaje por compromisos personales.', 'Decidió acortar su recorrido por Bolivia.']],
    'TRABAJO' => [18, ['Se suspendió la reunión de trabajo en La Paz.', 'La empresa canceló el viaje de negocios.', 'Congreso reprogramado por los organizadores.']],
    'SALUD' => [12, ['Problemas de salud del titular.', 'Mal de altura reportado en el viaje previo; decidió no venir.', 'Enfermedad de un familiar.']],
    'BLOQUEOS_O_CLIMA' => [14, ['Bloqueo de carreteras en la ruta Oruro - La Paz.', 'Vuelo cancelado por mal tiempo.', 'Paro de transporte interdepartamental.', 'Lluvias intensas impidieron el viaje.']],
    'PRECIO' => [8, ['Consideró la tarifa elevada para la temporada.', 'Solicitó descuento que no aplicaba.']],
    'OTRA_OPCION' => [10, ['Se hospedará en casa de familiares.', 'Eligió otro hotel más cercano al evento.', 'Reservó un departamento por plataforma.']],
    'ERROR_DE_REGISTRO' => [3, ['Reserva duplicada por el canal de WhatsApp.', 'Fechas registradas por error.']],
    'OTRO' => [5, ['Sin explicación del huésped.', 'Motivos personales no especificados.']],
];
$metodos = [1 => 35, 2 => 36, 3 => 19, 4 => 10];

$filasR = [];
$filasD = [];
$filasE = [];
$filasC = [];
$filasP = [];
$filasRec = [];
$filasHist = [];
$ocupadasHoy = [];
$idR = 0;
$numRecibo = 0;

foreach ($todas as $r) {
    $idR++;
    $codigo = 'HE-' . str_pad((string) (1000 + $idR), 6, '0', STR_PAD_LEFT);
    $noches = $r['sal'] - $r['ing'];
    $cat = $r['cat'];
    $total = 0.0;
    for ($n = 0; $n < $noches; $n++) {
        $total += precioNoche($cat, $referencia->modify(($r['ing'] + $n >= 0 ? '+' : '') . ($r['ing'] + $n) . ' days')->format('Y-m-d'), $tarifas, $categorias);
    }
    $personas = match ($cat) {
        1 => 1,
        2 => prob(0.8) ? 2 : 1,
        3 => 2,
        default => mt_rand(2, 4),
    };

    if ($r['cancelada']) {
        $estado = 'CANCELADA';
    } elseif ($r['sal'] <= 0 && !($r['sal'] === 0 && $r['ing'] < 0 && $r['hab'] !== $enLimpieza)) {
        $estado = prob(0.068) && $r['ing'] < -1 ? 'NO_SHOW' : 'FINALIZADA';
        if ($r['sal'] === 0) {
            $estado = 'FINALIZADA';
        }
    } elseif ($r['ing'] < 0) {
        $estado = 'EN_CURSO';
    } elseif ($r['ing'] === 0) {
        $estado = 'CONFIRMADA';
    } else {
        $estado = prob(0.84) ? 'CONFIRMADA' : 'PENDIENTE';
    }
    if (!empty($r['sobreventa'])) {
        $estado = 'CONFIRMADA';
    }

    $usuario = (string) al($recepcionistas);
    $extra = 0.0;
    if ($estado === 'FINALIZADA' && prob(0.22)) {
        $extra = (float) (mt_rand(4, 36) * 5);
    }
    $obs = null;
    if (!empty($r['sobreventa'])) {
        $obs = 'Aceptada en sobreventa según la recomendación del modelo de decisión. Asignar habitación al check-in.';
    } elseif (prob(0.12)) {
        $obs = al(['Solicita habitación en piso alto.', 'Llegada estimada en vuelo nocturno.', 'Requiere factura a nombre de empresa.', 'Huésped frecuente: ofrecer upgrade si hay disponibilidad.', 'Solicita cuna para bebé.', 'Celebra aniversario de bodas.', 'Requiere traslado desde el aeropuerto de El Alto.']);
    }
    $filasR[] = [(string) $idR, q($codigo), (string) $r['huesped'], (string) $r['canal'], $usuario, d($r['ing']), d($r['sal']), (string) $personas, q($estado),
        number_format($total, 2, '.', ''), number_format($extra, 2, '.', ''), !empty($r['sobreventa']) ? '1' : '0', q($obs), dt($r['registro'], hora(8, 21)),
        $estado === 'CANCELADA' ? dt($r['fecha_cancelacion'], hora(8, 21)) : 'NULL'];
    $filasD[] = [(string) $idR, (string) $cat, $r['hab'] === null ? 'NULL' : (string) $r['hab'], number_format($total / $noches, 2, '.', ''), (string) $noches, number_format($total, 2, '.', '')];

    if ($estado === 'CANCELADA') {
        $tipo = elegir(array_map(fn ($m) => $m[0], $motivos));
        $filasC[] = [(string) $idR, q($tipo), q(al($motivos[$tipo][1])), $usuario, dt($r['fecha_cancelacion'], hora(8, 21))];
        continue;
    }

    // Estadías: check-in y check-out
    if (in_array($estado, ['FINALIZADA', 'EN_CURSO'], true)) {
        $checkout = $estado === 'FINALIZADA' ? dt($r['sal'], hora(7, 11)) : 'NULL';
        $filasE[] = [(string) $idR, dt($r['ing'], $r['ing'] === 0 ? hora(9, 11) : hora(13, 21)), $checkout, (string) al($recepcionistas), $estado === 'FINALIZADA' ? (string) al($recepcionistas) : 'NULL', 'NULL'];
        if ($estado === 'EN_CURSO') {
            $ocupadasHoy[$r['hab']] = true;
            $filasHist[] = [(string) $r['hab'], "'DISPONIBLE'", "'OCUPADA'", q('Check-in de la reserva ' . $codigo), $usuario, dt($r['ing'], hora(13, 20))];
        }
    }

    // Pagos y recibos
    $metodo = (int) elegir($metodos);
    $ref = fn (int $m) => $m === 1 ? 'NULL' : q(['', '', 'QR-', 'POS-', 'TRF-'][$m] . strtoupper(substr(md5((string) mt_rand()), 0, 10)));
    $anticipo = 0.0;
    $conAnticipo = match ($estado) {
        'FINALIZADA' => prob(0.42),
        'EN_CURSO' => prob(0.55),
        'CONFIRMADA' => prob(0.48),
        'NO_SHOW' => prob(0.45),
        default => false,
    };
    if ($conAnticipo) {
        $anticipo = round($total * 0.3, 2);
        $filasP[] = [(string) $idR, (string) $metodo, "'ANTICIPO'", number_format($anticipo, 2, '.', ''), $ref($metodo), dt(min($r['registro'] + mt_rand(0, 2), 0), hora(9, 20)), $usuario];
    }
    if ($estado === 'FINALIZADA') {
        $m2 = (int) elegir($metodos);
        $filasP[] = [(string) $idR, (string) $m2, "'HOSPEDAJE'", number_format($total - $anticipo, 2, '.', ''), $ref($m2), dt($r['sal'], hora(8, 11)), (string) al($recepcionistas)];
        if ($extra > 0) {
            $filasP[] = [(string) $idR, (string) $m2, "'CONSUMO'", number_format($extra, 2, '.', ''), $ref($m2), dt($r['sal'], hora(8, 11)), (string) al($recepcionistas)];
        }
        $numRecibo++;
        $filasRec[] = [q('REC-' . str_pad((string) $numRecibo, 6, '0', STR_PAD_LEFT)), (string) $idR, number_format($total + $extra, 2, '.', ''), dt($r['sal'], hora(8, 11)), (string) al($recepcionistas)];
    }
}

$salida[] = '-- Huéspedes';
$filas = [];
foreach ($huespedes as $idHu => $h) {
    $filas[] = [(string) $idHu, (string) $h['tipo'], q($h['doc']), q($h['nombres']), q($h['apellidos']), q($h['nac']), q($h['ciudad']), q($h['tel']), q($h['correo']), q($h['nacimiento']), dt(-mt_rand(380, 900), hora(8, 20))];
}
insertar($salida, 'huesped', ['id_huesped', 'id_tipo_documento', 'nro_documento', 'nombres', 'apellidos', 'nacionalidad', 'ciudad_procedencia', 'telefono', 'correo', 'fecha_nacimiento', 'creado_en'], $filas);

$salida[] = '-- Habitaciones';
$filas = [];
foreach ($habitaciones as $idH => $h) {
    $estado = isset($ocupadasHoy[$idH]) ? 'OCUPADA' : ($idH === $enMantenimiento ? 'MANTENIMIENTO' : ($idH === $enLimpieza ? 'LIMPIEZA' : 'DISPONIBLE'));
    $filas[] = [(string) $idH, q($h['numero']), (string) $h['piso'], (string) $h['categoria'], q($descripciones[$h['categoria']]), q($estado)];
}
insertar($salida, 'habitacion', ['id_habitacion', 'numero', 'piso', 'id_categoria', 'descripcion', 'estado'], $filas);
$filasHist[] = [(string) $enMantenimiento, "'DISPONIBLE'", "'MANTENIMIENTO'", q('Mantenimiento preventivo del sistema de calefacción'), '2', dt(-3, '10:15')];
$filasHist[] = [(string) $enLimpieza, "'OCUPADA'", "'DISPONIBLE'", q('Check-out registrado'), '2', dt(0, '08:40')];
$filasHist[] = [(string) $enLimpieza, "'DISPONIBLE'", "'LIMPIEZA'", q('Limpieza profunda posterior a la salida'), '2', dt(0, '08:45')];
$filasHist[] = ['18', "'MANTENIMIENTO'", "'DISPONIBLE'", q('Reparación de grifería concluida'), '1', dt(-40, '16:30')];
$filasHist[] = ['18', "'DISPONIBLE'", "'MANTENIMIENTO'", q('Fuga en la grifería del baño'), '2', dt(-42, '09:10')];

$salida[] = '-- Reservas, detalle, estadías, cancelaciones, pagos y recibos';
insertar($salida, 'reserva', ['id_reserva', 'codigo', 'id_huesped', 'id_canal', 'id_usuario', 'fecha_ingreso', 'fecha_salida', 'cantidad_personas', 'estado', 'monto_total', 'cargos_extra', 'es_sobreventa', 'observaciones', 'fecha_registro', 'fecha_modificacion'], $filasR);
insertar($salida, 'detalle_reserva', ['id_reserva', 'id_categoria', 'id_habitacion', 'precio_noche', 'noches', 'subtotal'], $filasD);
insertar($salida, 'estadia', ['id_reserva', 'fecha_checkin', 'fecha_checkout', 'id_usuario_checkin', 'id_usuario_checkout', 'observaciones'], $filasE);
// Las cancelaciones se insertan con el disparador desactivado lógicamente: las reservas ya están en estado CANCELADA.
insertar($salida, 'cancelacion', ['id_reserva', 'tipo_motivo', 'descripcion', 'id_usuario', 'fecha'], $filasC);
insertar($salida, 'pago', ['id_reserva', 'id_metodo_pago', 'concepto', 'monto', 'referencia', 'fecha', 'id_usuario'], $filasP);
insertar($salida, 'recibo', ['numero', 'id_reserva', 'total', 'fecha_emision', 'id_usuario'], $filasRec);
usort($filasHist, fn ($a, $b) => strcmp($a[5], $b[5]));
insertar($salida, 'historial_estado_habitacion', ['id_habitacion', 'estado_anterior', 'estado_nuevo', 'motivo', 'id_usuario', 'fecha'], $filasHist);

// ---------------------------------------------------------------------
// Sitio público: solicitudes y mensajes
// ---------------------------------------------------------------------
$solicitudes = [
    ['Andrea', 'Salazar Ponce', 1, '7845123', 'Boliviana', 'Cochabamba', 'andrea.salazar@gmail.com', '+591 70744521', 3, 9, 12, 2, 'Viajamos por nuestro aniversario, ¿es posible decorar la habitación?'],
    ['Thomas', 'Wagner', 2, 'AL8812345', 'Alemana', 'Berlín, Alemania', 'thomas.wagner@outlook.com', '+49 1523344556', 1, 14, 17, 1, 'Llego desde Uyuni en bus nocturno, ¿puedo hacer check-in temprano?'],
    ['Milagros', 'Huamán Ríos', 3, '45781236', 'Peruana', 'Cusco, Perú', 'milagros.huaman@hotmail.com', '+51 984512367', 4, 20, 23, 3, 'Somos tres adultos, consulto por la suite con vista al Illimani.'],
    ['Diego', 'Arancibia Terrazas', 1, '5512347', 'Boliviana', 'Tarija', 'diego.arancibia@gmail.com', '+591 72958841', 2, 7, 9, 2, null],
    ['Camille', 'Dubois', 2, 'FR3398127', 'Francesa', 'Lyon, Francia', 'camille.dubois@gmail.com', '+33 612345678', 3, 26, 30, 2, 'Nous aimerions une chambre calme. ¡Gracias!'],
];
$filas = [];
foreach ($solicitudes as $i => $s) {
    $monto = 0.0;
    for ($n = $s[9]; $n < $s[10]; $n++) {
        $monto += precioNoche($s[8], $referencia->modify('+' . $n . ' days')->format('Y-m-d'), $tarifas, $categorias);
    }
    $filas[] = [q($s[0]), q($s[1]), (string) $s[2], q($s[3]), q($s[4]), q($s[5]), q($s[6]), q($s[7]), (string) $s[8], d($s[9]), d($s[10]), (string) $s[11], q($s[12]), number_format($monto, 2, '.', ''), "'NUEVA'", "'190.104.12." . mt_rand(2, 250) . "'", dt(-$i, hora(7, 22))];
}
insertar($salida, 'solicitud_reserva', ['nombres', 'apellidos', 'id_tipo_documento', 'nro_documento', 'nacionalidad', 'ciudad_procedencia', 'correo', 'telefono', 'id_categoria', 'fecha_ingreso', 'fecha_salida', 'cantidad_personas', 'mensaje', 'monto_estimado', 'estado', 'ip', 'fecha'], $filas);

$mensajes = [
    ['Rodrigo Paredes', 'rparedes@empresa.com.bo', '+591 71234567', 'Convenio corporativo', 'Buenas tardes, representamos a una empresa minera y quisiéramos un convenio de tarifas corporativas para nuestros ejecutivos que viajan a La Paz cada mes.', 0, -1],
    ['Lucía Fernández', 'lucia.fdez@gmail.com', null, 'Estacionamiento', '¿El hotel cuenta con estacionamiento privado para una vagoneta? Llegamos desde Cochabamba por carretera.', 0, 0],
    ['Kevin Miller', 'kevin.miller@gmail.com', '+1 3055550199', 'Airport transfer', 'Hello! Do you offer airport transfer from El Alto International Airport? Thanks.', 1, -4],
];
$filas = [];
foreach ($mensajes as $m) {
    $filas[] = [q($m[0]), q($m[1]), q($m[2]), q($m[3]), q($m[4]), (string) $m[5], dt($m[6], hora(8, 22))];
}
insertar($salida, 'mensaje_contacto', ['nombre', 'correo', 'telefono', 'asunto', 'mensaje', 'leido', 'fecha'], $filas);

// ---------------------------------------------------------------------
// Bitácora de ejemplo
// ---------------------------------------------------------------------
$filas = [];
$eventos = [
    [1, 'Sesiones', 'Inicio de sesión', 'Ingreso al sistema'],
    [2, 'Sesiones', 'Inicio de sesión', 'Ingreso al sistema'],
    [3, 'Sesiones', 'Inicio de sesión', 'Ingreso al sistema'],
    [2, 'Reservas', 'Registrar reserva', 'Nueva reserva registrada desde recepción'],
    [2, 'Reservas', 'Check-in', 'Ingreso del huésped registrado'],
    [2, 'Reservas', 'Check-out', 'Salida del huésped y emisión de recibo'],
    [2, 'Habitaciones', 'Cambio de estado', 'Habitación enviada a limpieza'],
    [1, 'Tarifas', 'Configurar temporada', 'Tarifas por temporada actualizadas'],
    [3, 'Reportes', 'Exportar reporte', 'Reporte de ocupación exportado a Excel'],
    [1, 'Sobreventa', 'Calcular tasas', 'Tasas históricas de cancelación y no-show recalculadas'],
];
for ($i = 0; $i < 60; $i++) {
    $e = al($eventos);
    $filas[] = [(string) $e[0], q($e[1]), q($e[2]), q($e[3]), "'192.168.1." . mt_rand(10, 40) . "'", dt(-mt_rand(0, 20), hora(7, 22))];
}
usort($filas, fn ($a, $b) => strcmp($a[5], $b[5]));
insertar($salida, 'bitacora', ['id_usuario', 'modulo', 'accion', 'descripcion', 'ip', 'fecha'], $filas);

// ---------------------------------------------------------------------
// CU-17: tasas históricas de cancelación y no-show de los meses cerrados
// (misma regla que App\Servicios\SobreventaServicio::calcularTasas)
// ---------------------------------------------------------------------
$salida[] = <<<SQL
INSERT INTO tasa_historica (anio, mes, total_reservas, cancelaciones, no_show, tasa_cancelacion, tasa_no_show, fecha_calculo)
SELECT YEAR(fecha_ingreso), MONTH(fecha_ingreso), COUNT(*),
       SUM(estado = 'CANCELADA'), SUM(estado = 'NO_SHOW'),
       ROUND(SUM(estado = 'CANCELADA') / COUNT(*), 4), ROUND(SUM(estado = 'NO_SHOW') / COUNT(*), 4), NOW()
  FROM reserva
 WHERE fecha_ingreso >= DATE_FORMAT(CURDATE() - INTERVAL 12 MONTH, '%Y-%m-01')
   AND fecha_ingreso <  DATE_FORMAT(CURDATE(), '%Y-%m-01')
 GROUP BY YEAR(fecha_ingreso), MONTH(fecha_ingreso);
SQL;

$salida[] = 'SET FOREIGN_KEY_CHECKS = 1;';

// ---------------------------------------------------------------------
// Ensamblado del archivo final
// ---------------------------------------------------------------------
$esquema = file_get_contents(__DIR__ . '/esquema.sql');
$cabecera = "-- =====================================================================\n"
    . "--  HOTEL ELEGANCE — Base de datos completa (estructura + datos de prueba)\n"
    . "--  Importar en phpMyAdmin (pestaña «Importar», sin seleccionar base) o con:\n"
    . "--      C:\\xampp\\mysql\\bin\\mysql.exe -u root < database\\hotel_elegance.sql\n"
    . "--  ATENCIÓN: elimina y vuelve a crear la base `hotel_elegance`.\n"
    . "-- =====================================================================\n\n";
file_put_contents($raiz . '/database/hotel_elegance.sql', $cabecera . $esquema . "\n\n" . implode("\n\n", $salida) . "\n");

$conteo = array_count_values(array_map(fn ($f) => trim($f[8], "'"), $filasR));
ksort($conteo);
fwrite(STDOUT, sprintf("Archivo generado: database/hotel_elegance.sql\nHuéspedes: %d · Reservas: %d · Pagos: %d · Recibos: %d\n", count($huespedes), count($filasR), count($filasP), count($filasRec)));
foreach ($conteo as $estado => $n) {
    fwrite(STDOUT, "  $estado: $n\n");
}
