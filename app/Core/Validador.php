<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Validador de datos del lado del servidor con mensajes en español.
 *
 *   $datos = Validador::validar($entrada, [
 *       'correo' => 'requerido|correo|max:120',
 *       'fecha_salida' => 'requerido|fecha|despues:fecha_ingreso',
 *   ]);
 *
 * Devuelve sólo los campos declarados (vacíos convertidos a null) o lanza
 * ExcepcionValidacion con el primer error de cada campo.
 */
final class Validador
{
    private const NOMBRES = [
        'ci' => 'CI', 'nro_documento' => 'número de documento', 'id_tipo_documento' => 'tipo de documento',
        'fecha_ingreso' => 'fecha de ingreso', 'fecha_salida' => 'fecha de salida', 'fecha_inicio' => 'fecha de inicio',
        'fecha_fin' => 'fecha de fin', 'id_categoria' => 'categoría', 'id_canal' => 'canal', 'tarifa_base' => 'tarifa base',
        'contrasena' => 'contraseña', 'contrasena_actual' => 'contraseña actual', 'nombre_usuario' => 'usuario',
        'id_cargo' => 'cargo', 'id_rol' => 'rol', 'fecha_contratacion' => 'fecha de contratación', 'tipo_motivo' => 'tipo de motivo',
        'cantidad_personas' => 'cantidad de personas', 'ciudad_procedencia' => 'ciudad de procedencia', 'id_metodo_pago' => 'método de pago',
    ];

    public static function validar(array $datos, array $reglas, array $nombres = []): array
    {
        $errores = [];
        $limpios = [];
        foreach ($reglas as $campo => $definicion) {
            $valor = $datos[$campo] ?? null;
            if (is_string($valor)) {
                $valor = trim($valor);
            }
            $lista = is_array($definicion) ? $definicion : explode('|', $definicion);
            $vacio = $valor === null || $valor === '' || $valor === [];
            $etiqueta = $nombres[$campo] ?? self::NOMBRES[$campo] ?? str_replace('_', ' ', $campo);

            foreach ($lista as $regla) {
                if ($regla instanceof \Closure) {
                    if (!$vacio && ($mensaje = $regla($valor, $datos)) !== true && $mensaje !== null) {
                        $errores[$campo] = (string) $mensaje;
                        break;
                    }
                    continue;
                }
                [$nombre, $parametro] = array_pad(explode(':', (string) $regla, 2), 2, null);
                if ($nombre === 'requerido') {
                    if ($vacio) {
                        $errores[$campo] = "El campo $etiqueta es obligatorio.";
                        break;
                    }
                    continue;
                }
                if ($nombre === 'opcional' || $vacio) {
                    continue;
                }
                $mensaje = self::aplicar($nombre, $parametro, $valor, $datos, $etiqueta);
                if ($mensaje !== null) {
                    $errores[$campo] = $mensaje;
                    break;
                }
            }
            $limpios[$campo] = $vacio ? null : $valor;
        }
        if ($errores) {
            throw new ExcepcionValidacion($errores);
        }
        return $limpios;
    }

    private static function aplicar(string $regla, ?string $p, mixed $valor, array $datos, string $etiqueta): ?string
    {
        $texto = is_scalar($valor) ? (string) $valor : '';
        switch ($regla) {
            case 'texto':
                return is_string($valor) ? null : "El campo $etiqueta no es válido.";
            case 'correo':
                return filter_var($texto, FILTER_VALIDATE_EMAIL) ? null : "Ingrese un correo electrónico válido.";
            case 'min':
                return mb_strlen($texto) >= (int) $p ? null : "El campo $etiqueta debe tener al menos $p caracteres.";
            case 'max':
                return mb_strlen($texto) <= (int) $p ? null : "El campo $etiqueta no debe superar $p caracteres.";
            case 'entero':
                return filter_var($texto, FILTER_VALIDATE_INT) !== false ? null : "El campo $etiqueta debe ser un número entero.";
            case 'numero':
                return is_numeric($texto) ? null : "El campo $etiqueta debe ser numérico.";
            case 'mayor':
                return is_numeric($texto) && (float) $texto > (float) $p ? null : "El campo $etiqueta debe ser mayor a $p.";
            case 'minimo':
                return is_numeric($texto) && (float) $texto >= (float) $p ? null : "El campo $etiqueta debe ser al menos $p.";
            case 'maximo':
                return is_numeric($texto) && (float) $texto <= (float) $p ? null : "El campo $etiqueta no debe ser mayor a $p.";
            case 'fecha':
                $f = \DateTime::createFromFormat('Y-m-d', $texto);
                return $f && $f->format('Y-m-d') === $texto ? null : "La $etiqueta no es una fecha válida.";
            case 'despues':
                $otra = (string) ($datos[$p] ?? '');
                $nombreOtra = self::NOMBRES[$p] ?? $p;
                return $otra === '' || $texto > $otra ? null : "La $etiqueta debe ser posterior a la $nombreOtra.";
            case 'despues_o_igual':
                $otra = (string) ($datos[$p] ?? '');
                $nombreOtra = self::NOMBRES[$p] ?? $p;
                return $otra === '' || $texto >= $otra ? null : "La $etiqueta no puede ser anterior a la $nombreOtra.";
            case 'en':
                return in_array($texto, explode(',', (string) $p), true) ? null : "El valor de $etiqueta no es válido.";
            case 'regex':
                return preg_match((string) $p, $texto) ? null : "El formato de $etiqueta no es válido.";
            case 'telefono':
                return preg_match('/^\+?[0-9\s\-()]{6,20}$/', $texto) ? null : 'Ingrese un teléfono válido (sólo números, espacios y +).';
            case 'documento':
                return preg_match('/^[A-Za-z0-9\-]{4,20}$/', $texto) ? null : 'El documento sólo admite letras, números y guiones (4 a 20 caracteres).';
            case 'nombre':
                return preg_match("/^[\p{L}\s'.\-]{2,80}$/u", $texto) ? null : "El campo $etiqueta sólo admite letras y espacios.";
            case 'confirmado':
                return ($datos[$p ?? ''] ?? null) === $valor ? null : 'La confirmación de la contraseña no coincide.';
            case 'contrasena':
                return self::contrasenaSegura($texto);
            case 'color':
                return preg_match('/^#[0-9A-Fa-f]{6}$/', $texto) ? null : 'Seleccione un color válido.';
        }
        return null;
    }

    /** Política: mínimo 8 caracteres, mayúscula, minúscula, número y símbolo. */
    public static function contrasenaSegura(string $clave): ?string
    {
        if (mb_strlen($clave) < 8
            || !preg_match('/[A-Z]/', $clave)
            || !preg_match('/[a-z]/', $clave)
            || !preg_match('/\d/', $clave)
            || !preg_match('/[^A-Za-z0-9]/', $clave)) {
            return 'La contraseña debe tener al menos 8 caracteres e incluir mayúscula, minúscula, número y símbolo.';
        }
        return null;
    }
}
