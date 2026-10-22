<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseDatos;
use App\Core\ExcepcionHttp;
use App\Core\ExcepcionValidacion;
use App\Core\Respuesta;
use App\Core\Sesion;
use App\Core\Validador;
use App\Servicios\DisponibilidadServicio;
use App\Servicios\TarifaServicio;

/**
 * Sitio público del hotel conectado a la misma base de datos: las solicitudes
 * de reserva y los mensajes llegan al panel de recepción.
 */
final class SitioController extends Controlador
{
    public function inicio(): Respuesta
    {
        $especiales = BaseDatos::filas("SELECT * FROM temporada WHERE tipo = 'ESPECIAL' AND estado = 1 AND fecha_fin >= CURDATE() ORDER BY fecha_inicio LIMIT 4");
        return $this->vista('sitio/inicio', ['titulo' => 'Hotel boutique en La Paz', 'categorias' => $this->categorias(), 'especiales' => $especiales], 'sitio');
    }

    public function habitaciones(): Respuesta
    {
        return $this->vista('sitio/habitaciones', ['titulo' => 'Habitaciones y suites', 'categorias' => $this->categorias()], 'sitio');
    }

    public function habitacion(string $id): Respuesta
    {
        $categoria = null;
        foreach ($this->categorias() as $c) {
            if ((int) $c['id_categoria'] === (int) $id) {
                $categoria = $c;
            }
        }
        if (!$categoria) {
            throw new ExcepcionHttp(404, 'La habitación no existe.');
        }
        $proximos = [];
        for ($i = 0; $i < 14; $i++) {
            $proximos[] = TarifaServicio::precioNoche((int) $id, date('Y-m-d', strtotime("+$i days")));
        }
        return $this->vista('sitio/habitacion', ['titulo' => $categoria['nombre'], 'c' => $categoria, 'otras' => $this->categorias(), 'proximos' => $proximos], 'sitio');
    }

    public function reservar(): Respuesta
    {
        return $this->vista('sitio/reservar', [
            'titulo' => 'Reservar', 'categorias' => $this->categorias(),
            'tipos' => BaseDatos::filas('SELECT * FROM tipo_documento ORDER BY id_tipo_documento'),
        ], 'sitio');
    }

    public function enviarSolicitud(): Respuesta
    {
        if ($this->solicitud->entrada('sitio_web')) {
            return $this->redirigir('/reservar/gracias'); // trampa para bots (campo oculto)
        }
        $enviadas = array_filter((array) Sesion::obtener('solicitudes_web', []), fn ($t) => $t > time() - 3600);
        if (count($enviadas) >= 5) {
            throw ExcepcionValidacion::campo('nombres', 'Recibimos varias solicitudes desde este navegador. Por favor, comuníquese por teléfono o WhatsApp.');
        }
        $d = Validador::validar($this->solicitud->todo(), [
            'fecha_ingreso' => 'requerido|fecha', 'fecha_salida' => 'requerido|fecha|despues:fecha_ingreso',
            'cantidad_personas' => 'requerido|entero|minimo:1|maximo:8', 'id_categoria' => 'requerido|entero',
            'nombres' => 'requerido|nombre|max:80', 'apellidos' => 'requerido|nombre|max:80',
            'id_tipo_documento' => 'requerido|entero', 'nro_documento' => 'requerido|documento',
            'nacionalidad' => 'requerido|max:60', 'ciudad_procedencia' => 'requerido|max:80',
            'correo' => 'requerido|correo|max:120', 'telefono' => 'requerido|telefono', 'mensaje' => 'opcional|max:600',
        ], ['id_categoria' => 'habitación']);
        if ($d['fecha_ingreso'] < date('Y-m-d')) {
            throw ExcepcionValidacion::campo('fecha_ingreso', 'La fecha de llegada no puede ser anterior a hoy.');
        }
        $disponible = null;
        foreach (DisponibilidadServicio::resumenPorCategoria((string) $d['fecha_ingreso'], (string) $d['fecha_salida'], 1) as $c) {
            if ($c['id_categoria'] === (int) $d['id_categoria']) {
                $disponible = $c;
            }
        }
        if (!$disponible) {
            throw ExcepcionValidacion::campo('id_categoria', 'Esa habitación ya no tiene disponibilidad para las fechas elegidas. Pruebe otra categoría o fechas.');
        }
        if ((int) $d['cantidad_personas'] > $disponible['capacidad'] * $disponible['libres']) {
            throw ExcepcionValidacion::campo('cantidad_personas', 'La cantidad de personas supera la capacidad disponible de esa categoría.');
        }
        $id = BaseDatos::insertar('solicitud_reserva', [
            'nombres' => $d['nombres'], 'apellidos' => $d['apellidos'], 'id_tipo_documento' => (int) $d['id_tipo_documento'],
            'nro_documento' => strtoupper((string) $d['nro_documento']), 'nacionalidad' => $d['nacionalidad'], 'ciudad_procedencia' => $d['ciudad_procedencia'],
            'correo' => mb_strtolower((string) $d['correo']), 'telefono' => $d['telefono'], 'id_categoria' => (int) $d['id_categoria'],
            'fecha_ingreso' => $d['fecha_ingreso'], 'fecha_salida' => $d['fecha_salida'], 'cantidad_personas' => (int) $d['cantidad_personas'],
            'mensaje' => $d['mensaje'], 'monto_estimado' => $disponible['total'], 'ip' => $this->solicitud->ip(),
        ]);
        $enviadas[] = time();
        Sesion::establecer('solicitudes_web', $enviadas);
        Sesion::flash('solicitud', ['id' => $id, 'nombre' => $d['nombres'], 'categoria' => $disponible['categoria'], 'ingreso' => $d['fecha_ingreso'], 'salida' => $d['fecha_salida'], 'total' => $disponible['total'], 'correo' => $d['correo']]);
        return $this->redirigir('/reservar/gracias');
    }

    public function gracias(): Respuesta
    {
        return $this->vista('sitio/gracias', ['titulo' => 'Solicitud recibida', 'solicitud' => Sesion::flashActual('solicitud')], 'sitio');
    }

    public function contacto(): Respuesta
    {
        return $this->vista('sitio/contacto', ['titulo' => 'Contacto'], 'sitio');
    }

    public function enviarContacto(): Respuesta
    {
        if ($this->solicitud->entrada('sitio_web')) {
            return $this->redirigir('/contacto');
        }
        $d = $this->validar(['nombre' => 'requerido|max:120', 'correo' => 'requerido|correo|max:120', 'telefono' => 'opcional|telefono', 'asunto' => 'requerido|max:120', 'mensaje' => 'requerido|min:10|max:2000']);
        BaseDatos::insertar('mensaje_contacto', $d);
        return $this->redirigir('/contacto', '¡Gracias! Recibimos su mensaje y le responderemos a la brevedad.');
    }

    /** API pública: disponibilidad por categoría (sin revelar números de habitación). */
    public function disponibilidad(): Respuesta
    {
        $ingreso = (string) $this->solicitud->consulta('ingreso', '');
        $salida = (string) $this->solicitud->consulta('salida', '');
        if ($ingreso < date('Y-m-d')) {
            throw ExcepcionValidacion::campo('fecha_ingreso', 'La fecha de llegada no puede ser anterior a hoy.');
        }
        $resumen = DisponibilidadServicio::resumenPorCategoria($ingreso, $salida, max(1, $this->entero('personas', 1)));
        return $this->json(['ok' => true, 'noches' => noches($ingreso, $salida), 'categorias' => array_map(fn ($c) => [
            'id' => $c['id_categoria'], 'nombre' => $c['categoria'], 'capacidad' => $c['capacidad'],
            'pocas' => $c['libres'] <= 2, 'total' => $c['total'], 'promedio' => $c['promedio'],
        ], $resumen)]);
    }

    private function categorias(): array
    {
        $categorias = BaseDatos::filas('SELECT * FROM categoria_habitacion WHERE estado = 1 ORDER BY tarifa_base');
        $galerias = ['simple' => ['simple-1.jpg', 'simple-2.jpg', 'detalle-1.jpg'], 'doble' => ['doble-1.jpg', 'doble-2.jpg', 'detalle-2.jpg'], 'matrimonial' => ['matrimonial-1.jpg', 'matrimonial-2.jpg', 'detalle-1.jpg'], 'suite' => ['suite-3.jpg', 'matrimonial-2.jpg', 'detalle-2.jpg']];
        foreach ($categorias as &$c) {
            $clave = strtolower(strtr($c['nombre'], ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u']));
            $c['galeria'] = $galerias[$clave] ?? [$c['imagen'] ?: 'detalle-1.jpg', 'detalle-1.jpg', 'detalle-2.jpg'];
            $c['galeria'][0] = $c['imagen'] ?: $c['galeria'][0];
            $c['comodidades_lista'] = array_filter(explode('|', (string) $c['comodidades']));
            $c['hoy'] = TarifaServicio::precioNoche((int) $c['id_categoria'], date('Y-m-d'));
        }
        unset($c);
        return $categorias;
    }
}
