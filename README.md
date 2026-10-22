# Hotel Elegance – Sistema de reservas

Sistema web para el seguimiento de reservas del Hotel Elegance (La Paz, Bolivia).
Proyecto de la materia Análisis y Diseño II – UNIFRANZ, gestión 2026.

**Integrantes:** Israel Javier Quispe Escobar, Ademar Rodríguez Mamani, Fernando Jhamil Quispe Ramos y Jonathan Ticona Pérez (Scrum Master).

**Docentes:** Ing. Hilaria Adima Vasquez Duran, Ing. Juan Carlos Cuellar Sonco e Ing. Rubén Gonzalo Soria Soria.

El informe del proyecto está en `docs/informe.pdf`.

## Tecnologías

- PHP 8 con patrón MVC
- MySQL / MariaDB
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

```bash
C:\xampp\php\php.exe tests\ejecutar.php
```
