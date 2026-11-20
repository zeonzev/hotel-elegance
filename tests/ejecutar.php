<?php
/**
 * Pruebas automatizadas de la lógica crítica del sistema.
 *
 * Uso:  C:\xampp\php\php.exe tests\ejecutar.php
 *
 * Las pruebas que usan la base de datos se ejecutan dentro de una transacción
 * que se revierte al final: no modifican los datos de prueba.
 */

declare(strict_types=1);

define('RAIZ', dirname(__DIR__));
require RAIZ . '/app/bootstrap.php';

use App\Core\Autenticacion;
use App\Core\BaseDatos;
use App\Core\ExcepcionValidacion;
use App\Core\Validador;
use App\Servicios\CadenaMarkov;
use App\Servicios\CuentaServicio;
use App\Servicios\DemandaServicio;
use App\Servicios\DisponibilidadServicio;
use App\Servicios\MarkovServicio;
use App\Servicios\ModeloDecision;
use App\Servicios\ReservaServicio;
use App\Servicios\SobreventaServicio;
use App\Servicios\TarifaServicio;

$_SESSION = [];
$aprobadas = 0;
$fallidas = [];

function prueba(string $nombre, callable $fn): void
{
    global $aprobadas, $fallidas;
    try {
        $fn();
        $aprobadas++;
        echo "  \u{2714} $nombre\n";
    } catch (Throwable $e) {
        $fallidas[] = $nombre;
        echo "  \u{2718} $nombre\n      → " . get_class($e) . ': ' . $e->getMessage() . "\n";
    }
}

function afirmar(bool $condicion, string $mensaje = 'La afirmación no se cumplió'): void
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
}

function cerca(float $a, float $b, float $tolerancia = 0.0001): void
{
    afirmar(abs($a - $b) <= $tolerancia, "Se esperaba $b y se obtuvo $a");
}

function lanza(callable $fn, string $clase = ExcepcionValidacion::class): Throwable
{
    try {
        $fn();
    } catch (Throwable $e) {
        afirmar($e instanceof $clase, 'Se esperaba ' . $clase . ' y se lanzó ' . get_class($e) . ': ' . $e->getMessage());
        return $e;
    }
    throw new RuntimeException("Se esperaba la excepción $clase y no se lanzó ninguna.");
}

// ======================================================================
echo "\nModelo de decisión (Sprint 5 · CU-18)\n";
// ======================================================================
prueba('La distribución binomial suma 1', function () {
    foreach ([[1, .5], [23, .945], [40, .8], [5, 0.0], [5, 1.0]] as [$n, $q]) {
        cerca(array_sum(ModeloDecision::binomial($n, $q)), 1.0, 1e-9);
    }
});
prueba('P(todos se presentan) = q^n', function () {
    $d = ModeloDecision::binomial(23, 0.945);
    cerca($d[23], 0.945 ** 23, 1e-12);
});
prueba('Fórmula del informe VE = p·I − (1 − p)·C', function () {
    cerca(ModeloDecision::veSimple(0.728, 520, 650), 0.728 * 520 - 0.272 * 650);
});
prueba('Ejemplo del README: hotel lleno 23/23, p = 5,5 % → VE ≈ Bs 190,7 y ACEPTAR', function () {
    $r = ModeloDecision::evaluarAdicional(23, 23, 0.945, 520, 650);
    cerca($r['p_cupo'], 1 - 0.945 ** 23, 1e-9);
    cerca($r['ve_aceptar'], 190.7, 0.5);
    afirmar($r['ve_aceptar'] > 0);
});
prueba('Con habitaciones libres el VE es q·I (no hay riesgo de reubicación)', function () {
    $r = ModeloDecision::evaluarAdicional(10, 23, 0.9, 500, 650);
    cerca($r['ve_aceptar'], 0.9 * 500, 1e-6);
});
prueba('Si nadie falla nunca (q = 1) con hotel lleno, aceptar cuesta C', function () {
    $r = ModeloDecision::evaluarAdicional(23, 23, 1.0, 500, 650);
    cerca($r['ve_aceptar'], -650);
});
prueba('Las probabilidades de la matriz de decisión suman 1', function () {
    $r = ModeloDecision::evaluarAdicional(25, 23, 0.93, 500, 650);
    cerca(array_sum(array_column($r['matriz'], 'probabilidad')), 1.0, 1e-9);
});
prueba('Nivel óptimo: con no-show 0 no conviene sobrevender; con no-show alto sí', function () {
    afirmar(ModeloDecision::niveles(23, 1.0, 500, 650, 6)['optimo'] === 0);
    afirmar(ModeloDecision::niveles(23, 0.8, 500, 650, 8)['optimo'] >= 2);
});

prueba('Matriz de pagos del Hito 3 (I = 250, C = 400)', function () {
    $m = ModeloDecision::matrizPagos(3, 250, 400);
    afirmar($m[1] === [-400.0, 250.0, 250.0, 250.0], 'Fila A1 incorrecta');
    afirmar($m[3] === [-1200.0, -550.0, 100.0, 750.0], 'Fila A3 incorrecta');
});
prueba('Criterios del Hito 3: VE elige A1 (Bs 87,5), VEIP = Bs 237,5', function () {
    $c = ModeloDecision::criterios(ModeloDecision::matrizPagos(3, 250, 400), [0.25, 0.35, 0.25, 0.15], 0.6);
    cerca($c['filas'][1]['ve'], 87.5);
    cerca($c['filas'][2]['ve'], -52.5);
    cerca($c['filas'][3]['ve'], -355);
    afirmar($c['elegida']['ve'] === 1);
    cerca($c['ve_ci'], 325);
    cerca($c['veip'], 237.5);
});
prueba('Criterios bajo incertidumbre del Hito 3: Maximax A3, Maximin A0, Laplace A1, Hurwicz A0, Savage A1', function () {
    $c = ModeloDecision::criterios(ModeloDecision::matrizPagos(3, 250, 400), [0.25, 0.35, 0.25, 0.15], 0.6);
    afirmar($c['elegida']['maximax'] === 3 && $c['elegida']['maximin'] === 0);
    afirmar($c['elegida']['laplace'] === 1 && $c['elegida']['hurwicz'] === 0 && $c['elegida']['savage'] === 1);
    cerca($c['filas'][2]['laplace'], 12.5);
    cerca($c['filas'][1]['hurwicz'], -10);
    afirmar(array_column($c['filas'], 'savage') === [750.0, 500.0, 800.0, 1200.0], 'Arrepentimientos máximos incorrectos');
});
prueba('Las probabilidades de los estados de no-show suman 1', function () {
    $p = ModeloDecision::probabilidadesNoShow(24, 0.08, 3);
    afirmar(count($p) === 4);
    cerca(array_sum($p), 1.0, 1e-9);
    cerca($p[0], 0.92 ** 24, 1e-12);
});
prueba('Regla del Hito 3: aceptar si p ≥ C / (I + C) = 400 / 650', function () {
    cerca(ModeloDecision::umbralAceptar(250, 400), 400 / 650);
});

// ======================================================================
echo "\nCadenas de Markov (IO II · ciclo de una reserva)\n";
// ======================================================================
prueba('Matriz desde conteos: p_ij = n_ij / Σ n_ik y filas que suman 1', function () {
    $n = CadenaMarkov::conteosVacios();
    $n['C'] = ['C' => 10, 'H' => 75, 'X' => 10, 'N' => 5, 'F' => 0];
    $n['H'] = ['C' => 0, 'H' => 20, 'X' => 0, 'N' => 0, 'F' => 80];
    $p = CadenaMarkov::matrizDesdeConteos($n);
    cerca($p['C']['H'], 0.75);
    cerca($p['H']['F'], 0.80);
    afirmar($p['X']['X'] === 1.0 && $p['F']['F'] === 1.0, 'Los absorbentes deben tener p_ii = 1');
    afirmar(CadenaMarkov::filasSumanUno($p));
});
prueba('Segundo Aporte: V2 y V3 partiendo de Confirmada', function () {
    $v = CadenaMarkov::evolucion(CadenaMarkov::vectorInicial('C'), MarkovServicio::MATRIZ_DEMOSTRATIVA, 3);
    cerca($v[2]['F'], 0.60);
    cerca($v[2]['H'], 0.225);
    cerca($v[2]['X'], 0.11);
    cerca($v[3]['F'], 0.78);
    cerca($v[3]['N'], 0.0555);
});
prueba('V_n = V_0 · P^n coincide con la evolución paso a paso', function () {
    $p = MarkovServicio::MATRIZ_DEMOSTRATIVA;
    $p3 = CadenaMarkov::potencia($p, 3);
    $v = CadenaMarkov::evolucion(CadenaMarkov::vectorInicial('C'), $p, 3);
    foreach ($p3['C'] as $j => $valor) {
        cerca($valor, $v[3][$j], 1e-12);
    }
});
prueba('Segundo Aporte: absorción desde C → F 83,33 %, X 11,11 %, N 5,56 %', function () {
    $b = CadenaMarkov::absorcion(MarkovServicio::MATRIZ_DEMOSTRATIVA)['B'];
    cerca($b['C']['F'], 0.8333, 0.0001);
    cerca($b['C']['X'], 0.1111, 0.0001);
    cerca($b['C']['N'], 0.0556, 0.0001);
    cerca($b['H']['F'], 1.0, 1e-9);
});
prueba('Matriz fundamental N = (I − Q)^-1 y tiempo esperado t = N·1', function () {
    $a = CadenaMarkov::absorcion(MarkovServicio::MATRIZ_DEMOSTRATIVA);
    cerca($a['N']['C']['C'], 1 / 0.9);
    cerca($a['N']['H']['H'], 1 / 0.8);
    cerca($a['N']['C']['H'], (0.75 / 0.9) / 0.8);
    cerca($a['t']['C'], 1 / 0.9 + (0.75 / 0.9) / 0.8);
});
prueba('Inversa por Gauss-Jordan: A · A^-1 = I', function () {
    $m = [[4.0, 7.0], [2.0, 6.0]];
    $i = CadenaMarkov::multiplicar($m, CadenaMarkov::inversa($m));
    cerca($i[0][0], 1); cerca($i[0][1], 0); cerca($i[1][0], 0); cerca($i[1][1], 1);
    afirmar(CadenaMarkov::inversa([[1.0, 2.0], [2.0, 4.0]]) === null, 'Una matriz singular no tiene inversa');
});
prueba('Estado por día de una reserva: C → H → F', function () {
    $r = ['registro' => '2026-03-01', 'estado' => 'FINALIZADA', 'fecha_ingreso' => '2026-03-05', 'fecha_salida' => '2026-03-07',
          'checkin' => '2026-03-05', 'checkout' => '2026-03-07', 'cancelacion' => null];
    afirmar(MarkovServicio::estadoEnDia($r, '2026-02-28') === null);
    afirmar(MarkovServicio::estadoEnDia($r, '2026-03-04') === 'C');
    afirmar(MarkovServicio::estadoEnDia($r, '2026-03-06') === 'H');
    afirmar(MarkovServicio::estadoEnDia($r, '2026-03-07') === 'F');
    $n = MarkovServicio::contarTransiciones([$r], '2026-01-01', '2026-12-31');
    afirmar($n['C']['C'] === 3 && $n['C']['H'] === 1 && $n['H']['H'] === 1 && $n['H']['F'] === 1, 'Conteos incorrectos: ' . json_encode($n));
});
prueba('Cancelación y no-show son absorbentes; una reserva abierta no se cuenta más allá de lo conocido', function () {
    $cancelada = ['registro' => '2026-03-01', 'estado' => 'CANCELADA', 'fecha_ingreso' => '2026-03-10', 'fecha_salida' => '2026-03-12', 'checkin' => null, 'checkout' => null, 'cancelacion' => '2026-03-03'];
    $noShow = ['registro' => '2026-03-01', 'estado' => 'NO_SHOW', 'fecha_ingreso' => '2026-03-02', 'fecha_salida' => '2026-03-04', 'checkin' => null, 'checkout' => null, 'cancelacion' => null];
    $futura = ['registro' => '2026-03-01', 'estado' => 'CONFIRMADA', 'fecha_ingreso' => '2026-03-20', 'fecha_salida' => '2026-03-22', 'checkin' => null, 'checkout' => null, 'cancelacion' => null];
    $n = MarkovServicio::contarTransiciones([$cancelada, $noShow, $futura], '2026-01-01', '2026-03-10');
    afirmar($n['C']['X'] === 1 && $n['C']['N'] === 1, 'Faltan transiciones a absorbentes');
    // cancelada: 1 día C→C; no-show: 0; futura: del 1 al 10 de marzo = 9 días C→C
    afirmar($n['C']['C'] === 10, 'Se esperaban 10 transiciones C→C y hubo ' . $n['C']['C']);
    afirmar(array_sum($n['X']) === 0 && array_sum($n['N']) === 0, 'No se cuenta nada desde un absorbente');
});
prueba('Con el historial de la base de datos: matriz válida y cada fila de B suma 1', function () {
    $a = MarkovServicio::analizar(date('Y-m-d', strtotime('-12 months')), date('Y-m-d'), null, 7);
    afirmar($a['valida'], 'Alguna fila de P no suma 1');
    foreach ($a['absorcion']['B'] as $fila) {
        cerca(array_sum($fila), 1.0, 1e-9);
    }
    cerca(array_sum($a['evolucion'][7]), 1.0, 1e-9);
});
prueba('La sobreventa puede usar las probabilidades de Markov', function () {
    $e = SobreventaServicio::evaluar(date('Y-m-d', strtotime('+3 days')), 500, null, 0, 'MARKOV', 0.6);
    afirmar($e['fuente'] === 'MARKOV');
    afirmar($e['p_ausencia'] > 0 && $e['p_ausencia'] < 1);
    cerca(array_sum($e['prob_estados']), 1.0, 1e-9);
    afirmar(isset($e['criterios']['elegida']['savage']));
});

// ======================================================================
echo "\nEstimación de demanda y tarifas (Sprint 6 · CU-21)\n";
// ======================================================================
prueba('Factor de ajuste f = 1 + α(Ō − meta)', function () {
    cerca(DemandaServicio::factor(0.88, 0.70, 0.8, 0.85, 1.40), 1.144);
});
prueba('El factor se acota entre el mínimo y el máximo', function () {
    cerca(DemandaServicio::factor(1.0, 0.70, 5, 0.85, 1.40), 1.40);
    cerca(DemandaServicio::factor(0.0, 0.70, 5, 0.85, 1.40), 0.85);
});
prueba('La tarifa sugerida se redondea a Bs 10 (ejemplo: 520 × 1,144 → 590)', function () {
    cerca(DemandaServicio::redondear(520 * 1.144), 590);
});

// ======================================================================
echo "\nValidación\n";
// ======================================================================
prueba('Reglas «fecha» y «numero» validan (no se confunden con funciones globales)', function () {
    $d = Validador::validar(['f' => '2026-02-30', 'n' => '12.5'], ['f' => 'opcional', 'n' => 'requerido|numero']);
    afirmar($d['n'] === '12.5');
    lanza(fn () => Validador::validar(['f' => '2026-02-30'], ['f' => 'requerido|fecha']));
    lanza(fn () => Validador::validar(['n' => 'abc'], ['n' => 'requerido|numero']));
});
prueba('Política de contraseñas', function () {
    afirmar(Validador::contrasenaSegura('Admin123*') === null);
    afirmar(Validador::contrasenaSegura('admin123') !== null);
    afirmar(Validador::contrasenaSegura('Corta1*') !== null);
});
prueba('La salida debe ser posterior al ingreso (CU-12, flujo 1a)', function () {
    lanza(fn () => DisponibilidadServicio::validarRango('2030-01-10', '2030-01-10'));
    lanza(fn () => DisponibilidadServicio::validarRango('2030-01-10', '2030-01-09'));
});

// ======================================================================
echo "\nBase de datos (se revierte al final)\n";
// ======================================================================
try {
    $pdo = BaseDatos::conexion();
    $pdo->beginTransaction();
} catch (Throwable $e) {
    echo "  ! No se pudo conectar a MySQL: {$e->getMessage()}\n  Importe database/hotel_elegance.sql e inicie MySQL.\n";
    exit(1);
}

// Fechas lejanas en el futuro: no hay reservas de prueba ahí
$ingreso = date('Y-m-d', strtotime('+400 days'));
$salida = date('Y-m-d', strtotime('+403 days'));
$idHab = (int) BaseDatos::valor("SELECT id_habitacion FROM habitacion WHERE estado <> 'MANTENIMIENTO' AND id_categoria = 3 ORDER BY numero LIMIT 1");
$idHuesped = (int) BaseDatos::valor('SELECT id_huesped FROM huesped ORDER BY id_huesped LIMIT 1');
$datos = ['fecha_ingreso' => $ingreso, 'fecha_salida' => $salida, 'cantidad_personas' => 2, 'id_canal' => 1, 'id_huesped' => $idHuesped, 'habitaciones' => [$idHab]];
$idReserva = 0;

prueba('Registrar una reserva calcula el monto noche por noche', function () use ($datos, $idHab, &$idReserva) {
    $idReserva = ReservaServicio::registrar($datos);
    $r = ReservaServicio::obtener($idReserva);
    $cot = TarifaServicio::cotizar(3, $datos['fecha_ingreso'], $datos['fecha_salida']);
    afirmar($r['estado'] === 'CONFIRMADA');
    afirmar(str_starts_with($r['codigo'], 'HE-'));
    cerca((float) $r['monto_total'], $cot['total'], 0.01);
    afirmar(!DisponibilidadServicio::estaDisponible($idHab, $datos['fecha_ingreso'], $datos['fecha_salida']));
});
prueba('NO permite reservar dos veces la misma habitación en fechas que se cruzan', function () use ($datos, $ingreso) {
    lanza(fn () => ReservaServicio::registrar($datos));
    lanza(fn () => ReservaServicio::registrar(['fecha_ingreso' => date('Y-m-d', strtotime($ingreso . ' +2 days')), 'fecha_salida' => date('Y-m-d', strtotime($ingreso . ' +6 days'))] + $datos));
    lanza(fn () => ReservaServicio::registrar(['fecha_ingreso' => date('Y-m-d', strtotime($ingreso . ' -2 days')), 'fecha_salida' => date('Y-m-d', strtotime($ingreso . ' +1 days'))] + $datos));
});
prueba('Sí permite una reserva que ingresa el mismo día de la salida anterior', function () use ($datos, $salida) {
    $id = ReservaServicio::registrar(['fecha_ingreso' => $salida, 'fecha_salida' => date('Y-m-d', strtotime($salida . ' +2 days'))] + $datos);
    afirmar($id > 0);
});
prueba('El disparador de MySQL rechaza un traslape insertado por fuera del sistema', function () use ($datos, $idHab, $idHuesped) {
    BaseDatos::consulta(
        "INSERT INTO reserva (codigo, id_huesped, id_canal, fecha_ingreso, fecha_salida, estado) VALUES ('TEST-1', ?, 1, ?, ?, 'CONFIRMADA')",
        [$idHuesped, $datos['fecha_ingreso'], $datos['fecha_salida']]
    );
    $idOtra = (int) BaseDatos::conexion()->lastInsertId();
    $e = lanza(fn () => BaseDatos::consulta('INSERT INTO detalle_reserva (id_reserva, id_categoria, id_habitacion, precio_noche, noches, subtotal) VALUES (?, 3, ?, 500, 3, 1500)', [$idOtra, $idHab]), PDOException::class);
    afirmar(str_contains($e->getMessage(), 'HABITACION_NO_DISPONIBLE'));
});
prueba('Cancelar exige motivo y, por disparador, deja la reserva CANCELADA y la habitación libre', function () use (&$idReserva, $datos, $idHab) {
    lanza(fn () => ReservaServicio::cancelar($idReserva, 'CAMBIO_DE_PLANES', ''));
    ReservaServicio::cancelar($idReserva, 'CAMBIO_DE_PLANES', 'Prueba automatizada');
    afirmar(ReservaServicio::obtener($idReserva)['estado'] === 'CANCELADA');
    afirmar(DisponibilidadServicio::estaDisponible($idHab, $datos['fecha_ingreso'], $datos['fecha_salida']));
});
prueba('Una reserva cancelada no se puede modificar (CU-13, flujo 1a)', function () use (&$idReserva, $datos) {
    lanza(fn () => ReservaServicio::modificar($idReserva, $datos));
});
prueba('Las habitaciones en mantenimiento no aparecen como disponibles', function () use ($ingreso, $salida) {
    $id = (int) BaseDatos::valor("SELECT id_habitacion FROM habitacion WHERE estado = 'MANTENIMIENTO' LIMIT 1");
    $libres = array_column(DisponibilidadServicio::habitacionesDisponibles($ingreso, $salida), 'id_habitacion');
    afirmar($id === 0 || !in_array($id, array_map('intval', $libres), true));
});
prueba('Prioridad de tarifas: especial > alta > baja > base', function () use ($ingreso) {
    TarifaServicio::reiniciarCache();
    $base = TarifaServicio::precioNoche(3, $ingreso);
    foreach ([['BAJA', 400], ['ALTA', 600], ['ESPECIAL', 900]] as [$tipo, $precio]) {
        $id = BaseDatos::insertar('temporada', ['nombre' => "Prueba $tipo", 'tipo' => $tipo, 'fecha_inicio' => $ingreso, 'fecha_fin' => $ingreso]);
        BaseDatos::insertar('tarifa_temporada', ['id_temporada' => $id, 'id_categoria' => 3, 'precio_noche' => $precio]);
    }
    TarifaServicio::reiniciarCache();
    $n = TarifaServicio::precioNoche(3, $ingreso);
    afirmar($n['precio'] === 900.0 && $n['tipo'] === 'ESPECIAL', 'Debía aplicarse la tarifa especial');
    afirmar($base['tipo'] !== 'ESPECIAL' || $base['precio'] !== 900.0);
    TarifaServicio::reiniciarCache();
});
prueba('CU-17: la tasa histórica de no-show está entre 0 y 1', function () {
    $mes = new DateTimeImmutable('first day of last month');
    $t = SobreventaServicio::calcularTasa((int) $mes->format('Y'), (int) $mes->format('n'));
    afirmar($t !== null && $t['tasa_no_show'] >= 0 && $t['tasa_no_show'] <= 1 && $t['total_reservas'] > 0);
});
prueba('CU-18: la evaluación entrega una decisión coherente con el VE', function () {
    $e = SobreventaServicio::evaluar(date('Y-m-d', strtotime('+4 days')));
    afirmar(in_array($e['decision'], ['ACEPTAR', 'RECHAZAR'], true));
    if ($e['ve_aceptar'] <= 0) {
        afirmar($e['decision'] === 'RECHAZAR');
    }
});

// --- Sesiones (CU-04) ---
prueba('Inicio de sesión correcto con usuario y contraseña', function () {
    $u = CuentaServicio::iniciarSesion('admin', 'Admin123*', '127.0.0.1');
    afirmar($u['rol'] === 'Administrador');
});
prueba('Contraseña incorrecta: mensaje genérico; a los 5 intentos la cuenta se BLOQUEA', function () {
    BaseDatos::consulta("UPDATE usuario SET intentos_fallidos = 0, estado = 'ACTIVO' WHERE nombre_usuario = 'gerente'");
    for ($i = 1; $i <= 5; $i++) {
        $e = lanza(fn () => CuentaServicio::iniciarSesion('gerente', 'Incorrecta' . $i, '127.0.0.1'));
    }
    afirmar(BaseDatos::valor("SELECT estado FROM usuario WHERE nombre_usuario = 'gerente'") === 'BLOQUEADO');
    lanza(fn () => CuentaServicio::iniciarSesion('gerente', 'Gerente123*', '127.0.0.1'));
});
prueba('CU-02: el nombre de usuario propuesto no se repite (agrega un número)', function () {
    $n = CuentaServicio::proponerNombreUsuario('Carla', 'Mendoza Rojas');
    afirmar($n !== 'admin' && preg_match('/^cmendoza\d*$/', $n) === 1);
});

// --- Permisos por rol ---
prueba('Permisos: recepción opera reservas pero no ve reportes ni usuarios', function () {
    afirmar(Autenticacion::puede('reservas.gestionar', 'Recepcionista'));
    afirmar(Autenticacion::puede('recepcion.operar', 'Recepcionista'));
    afirmar(!Autenticacion::puede('reportes.ver', 'Recepcionista'));
    afirmar(!Autenticacion::puede('usuarios.gestionar', 'Recepcionista'));
});
prueba('Permisos: el gerente ve reportes, sobreventa y tarifas, pero no gestiona reservas', function () {
    foreach (['reportes.ver', 'sobreventa.ver', 'tarifas.ver', 'dashboard.ver'] as $p) {
        afirmar(Autenticacion::puede($p, 'Gerente'), $p);
    }
    afirmar(!Autenticacion::puede('reservas.gestionar', 'Gerente'));
    afirmar(!Autenticacion::puede('tarifas.gestionar', 'Gerente'));
});
prueba('Permisos: el administrador puede todo', function () {
    foreach (array_keys(Autenticacion::permisos()) as $p) {
        afirmar(Autenticacion::puede($p, 'Administrador'), $p);
    }
});

$pdo->rollBack();

echo "\n" . str_repeat('─', 60) . "\n";
echo $fallidas ? "  $aprobadas aprobadas, " . count($fallidas) . " fallidas\n" : "  Todas las pruebas aprobadas ($aprobadas)\n";
exit($fallidas ? 1 : 0);
