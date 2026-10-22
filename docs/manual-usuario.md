# Manual de usuario — Hotel Elegance

Sistema de Administración Web para el Seguimiento de Reservas. Este manual explica las tareas más comunes de cada rol.

## Acceso

1. Abra `http://localhost/hotel-elegance/login`.
2. Ingrese su usuario y contraseña. En el primer ingreso el sistema le pedirá cambiar la contraseña por defecto.
3. Tras 5 intentos fallidos la cuenta se bloquea: pida al administrador que la desbloquee en **Usuarios y roles**.
4. La sesión se cierra sola después de 2 horas sin actividad. Para salir, use el menú de su nombre → **Cerrar sesión**.

Atajos útiles: **Ctrl + K** abre el buscador (huéspedes, reservas por código `HE-…`, habitaciones). El botón de la luna cambia a modo oscuro.

## Recepcionista

### Registrar una reserva (CU-11)
1. **Nueva reserva** (botón dorado de la barra superior).
2. Indique llegada, salida y personas → **Consultar disponibilidad** (CU-12). Se listan solo las habitaciones libres, con el precio de cada temporada.
3. Marque una o más habitaciones.
4. Busque al huésped por documento o nombre. Si no existe, use **Registrar huésped nuevo** (CU-05).
5. Elija el canal (teléfono, WhatsApp, presencial…), el estado y, si corresponde, el anticipo.
6. **Registrar reserva**. El sistema genera el código `HE-00xxxx`.

Si la ocupación de alguna noche supera el umbral (90 %), aparece la **alerta de sobreventa** con la recomendación del modelo (CU-19).
Si ya no quedan habitaciones de esa categoría, puede aceptarse **en sobreventa** cuando el modelo recomienda ACEPTAR.

### Consultar disponibilidad (CU-12)
**Disponibilidad**: busque por rango de fechas o revise el **calendario** (habitaciones × días). Haga clic en una celda libre para reservar esa habitación desde ese día.

### Modificar o cancelar (CU-13 / CU-14)
Abra la reserva → **Modificar** (cambia fechas, habitaciones u observaciones y recalcula el monto) o **Cancelar** (el motivo es obligatorio).
Solo se modifican o cancelan reservas pendientes o confirmadas.

### Check-in (CU-15)
**Recepción del día** → en *Llegadas*, **Check-in** → confirme que verificó el documento → **Confirmar ingreso**. La habitación pasa a OCUPADA.
Si la habitación aún figura ocupada por el huésped que sale hoy, primero registre su check-out.

### Check-out (CU-16)
**Recepción del día** → en *Salidas*, **Check-out** → agregue consumos si los hay, elija el método de pago → **Cobrar, emitir recibo y liberar**.
La habitación queda DISPONIBLE (o en LIMPIEZA si lo marca) y el recibo se puede imprimir o guardar como PDF.

### Estados de habitación (CU-10)
**Habitaciones** muestra el plano por piso con colores: verde disponible, azul ocupada, ámbar limpieza, rojo mantenimiento.
El botón ⟳ de cada habitación cambia su estado indicando el motivo; todo queda en el historial.

### Solicitudes web
Las reservas pedidas desde el sitio público llegan a **Solicitudes web**. **Atender** abre el registro de reserva con los datos precargados.

## Gerente

- **Dashboard**: ocupación de hoy, llegadas y salidas, ingresos del mes, ADR y RevPAR, reservas por canal y alertas.
- **Política de sobreventa** (CU-17 a CU-19): elija una noche y vea la ocupación, la tasa de no-show, la matriz de decisión,
  la distribución binomial, el valor esperado de aceptar una reserva más y el nivel óptimo de sobreventa. **Guardar evaluación** la registra.
- **Tarifas y demanda** (CU-20 / CU-21): mapa de demanda de las próximas semanas, temporadas y **tarifa referencial sugerida**.
- **Reportes** (CU-22 a CU-24): ocupación, ingresos y cancelaciones por periodo, exportables a **CSV**, **Excel** e **Imprimir / PDF**.

## Administrador

Además de todo lo anterior:

- **Personal** (CU-01): registrar empleados. **Crear cuenta** (CU-02) genera usuario y contraseña por defecto y pide el rol (CU-03).
- **Usuarios y roles**: cambiar rol, desbloquear cuentas, restablecer contraseñas, ver la matriz de permisos.
- **Habitaciones / Categorías** (CU-08 / CU-09): alta, edición y baja. No se elimina una habitación con reservas ni una categoría con habitaciones.
- **Tarifas**: crear temporadas (alta, baja o especial) con el precio de cada categoría; aplicar tarifas sugeridas.
- **Política de sobreventa → Parámetros**: umbral de alerta, costo de reubicación, máximo de reservas extra y meses de historial.
- **Bitácora**: quién hizo qué y cuándo. **Configuración**: datos del hotel que aparecen en recibos y en el sitio.
