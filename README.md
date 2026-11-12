# Hotel Elegance – Sistema de reservas

Sistema web para el seguimiento de reservas del Hotel Elegance (La Paz, Bolivia).
Proyecto de la materia Análisis y Diseño II – UNIFRANZ, gestión 2026.

**Integrantes:** Israel Javier Quispe Escobar, Ademar Rodríguez Mamani, Fernando Jhamil Quispe Ramos y Jonathan Ticona Pérez (Scrum Master).

**Docentes:** Ing. Hilaria Adima Vasquez Duran, Ing. Juan Carlos Cuellar Sonco e Ing. Rubén Gonzalo Soria Soria.

El informe del proyecto está en `docs/informe.pdf`.

## Tecnologías

- PHP 8 con patrón MVC
<<<<<<< HEAD
- MySQL
=======
- MySQ
>>>>>>> 357a29d (Agrega carpeta de pruebas unitarias)
- HTML, CSS, JavaScript y Bootstrap 5
- Chart.js para los gráficos

## Requisitos

Solo se necesita XAMPP con PHP 8.0 o superior.

## Instalación

1. Descargar el proyecto (`git clone https://github.com/zeonzev/hotel-elegance.git` o **Code → Download ZIP**).
2. Copiar la carpeta a `C:\xampp\htdocs\hotel-elegance`.
3. Abrir el panel de XAMPP e iniciar **Apache** y **MySQL**.
4. Crear la base de datos, de una de estas dos formas:
   - Entrar a `http://localhost/hotel-elegance/instalar` y presionar **Instalar ahora**.
   - O en phpMyAdmin, pestaña **Importar**, subir el archivo `database/hotel_elegance.sql`.
5. Abrir `http://localhost/hotel-elegance/`.

Si MySQL tiene contraseña o usa otro puerto, copiar `.env.example` como `.env` y cambiar los datos.

## Usuarios de prueba

| Rol | Usuario | Contraseña |
|---|---|---|
| Administrador | admin | Admin123* |
| Recepcionista | recepcion | Recepcion123* |
| Gerente | gerente | Gerente123* |

## Módulos

1. **Sesiones** (CU-01 a CU-04): registro de personal, cuentas de usuario, roles e inicio de sesión.
2. **Gestión de huéspedes** (CU-05 a CU-07): registro, modificación e historial de estadías.
3. **Gestión de habitaciones** (CU-08 a CU-10): habitaciones, categorías y estado de cada habitación.
4. **Gestión de reservas** (CU-11 a CU-16): registrar, consultar disponibilidad, modificar, cancelar, check-in y check-out.
5. **Política de sobreventa** (CU-17 a CU-19): tasas de cancelación y no-show, valor esperado y alerta de sobreventa.
6. **Estimación de demanda y tarifas** (CU-20 y CU-21): tarifas por temporada y tarifa sugerida.
7. **Reportes** (CU-22 a CU-24): ocupación, ingresos y cancelaciones.

## Estructura del proyecto

```
app/        controladores, servicios, vistas y núcleo MVC
config/     configuración, rutas y permisos por rol
database/   script de la base de datos
docs/       informe y manual de usuario
public/     archivos públicos (css, js, imágenes)
tests/      pruebas
```

## Pruebas

C:\xampp\php\php.exe tests\ejecutar.php


## Implementacion de Pruebas unitarias

### 1. Descripción

En el proyecto Hotel Elegance implementamos pruebas automatizadas utilizando **PHPUnit**, con el objetivo de verificar el correcto funcionamiento de algunas operaciones del sistema.

Para esta parte trabajamos con dos archivos ubicados en la carpeta `tests/Unitarias/`:

- **SobreventaTest.php:** contiene 6 pruebas que verifican los cálculos del valor esperado y las probabilidades utilizadas en el módulo de sobreventa.
- **PagosReservaTest.php:** contiene 4 pruebas que comprueban el cálculo del total pagado por una reserva mediante consultas a la base de datos utilizando PDO.

### 2. Ejecución de las pruebas

Para ejecutar las pruebas debemos tener configurado el proyecto, las dependencias instaladas mediante Composer y MySQL iniciado en XAMPP, utilizando la base de datos correcta.

Abrimos una terminal CMD o la terminal de Visual Studio Code y nos ubicamos en la carpeta del proyecto:

```cmd
cd C:\xampp\htdocs\hotel-elegance
```

**Prueba 1. Módulo de sobreventa**

Ejecutamos:

```cmd
vendor\bin\phpunit --testdox tests\Unitarias\SobreventaTest.php
```

Este comando ejecuta las seis pruebas relacionadas con los cálculos matemáticos de sobreventa.

El resultado esperado es:

```text
OK (6 tests, 8 assertions)
```

**Prueba 2. Módulo de pagos de reservas**

Ejecutamos:

```cmd
vendor\bin\phpunit --testdox tests\Unitarias\PagosReservaTest.php
```

Este comando ejecuta cuatro pruebas para comprobar los pagos registrados, las reservas sin pagos, el registro temporal de un nuevo pago y las reservas inexistentes.

El resultado esperado es:

```text
OK (4 tests, 4 assertions)
```

**Ejecutar todas las pruebas**

También podemos ejecutar todas las pruebas configuradas en el proyecto mediante:

```cmd
vendor\bin\phpunit --testdox
```

Este comando muestra las pruebas disponibles y sus resultados. La cantidad total puede variar según las pruebas que se hayan incorporado al proyecto.

### 3. Resultados obtenidos

Después de ejecutar ambos archivos, obtuvimos los siguientes resultados:

| Archivo | Pruebas | Aserciones | Resultado |
|---|---:|---:|---|
| SobreventaTest.php | 6 | 8 | Aprobado |
| PagosReservaTest.php | 4 | 4 | Aprobado |
| **Total** | **10** | **12** | **Aprobado** |

Las diez pruebas se ejecutaron correctamente, sin presentar errores, utilizando PHPUnit 11.5.57.

### 4. Conclusión

Con estas pruebas logramos comprobar que los cálculos de sobreventa y las operaciones de pagos evaluadas funcionan correctamente. Además, aprendimos a utilizar PHPUnit para verificar automáticamente los resultados del sistema y detectar posibles errores durante el desarrollo.

Los archivos de pruebas también fueron subidos al repositorio de GitHub para que los demás integrantes puedan ejecutarlos y verificar su funcionamiento.