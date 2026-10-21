-- =====================================================================
--  Hotel Elegance — Sistema de Administración Web para el Seguimiento
--  de Reservas. Esquema de base de datos (MySQL 5.7+ / MariaDB 10.4+)
--
--  Paquetes del informe (Fig. 2): Sesiones, Gestión de Huéspedes,
--  Gestión de Habitaciones, Gestión de Reservas, Política de Sobreventa,
--  Estimación de Demanda y Tarifas, Reportes.
-- =====================================================================

SET NAMES utf8mb4;
SET time_zone = '-04:00';
SET FOREIGN_KEY_CHECKS = 0;

DROP DATABASE IF EXISTS `hotel_elegance`;
CREATE DATABASE `hotel_elegance` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `hotel_elegance`;

-- ---------------------------------------------------------------------
--  Configuración general del hotel (clave / valor)
-- ---------------------------------------------------------------------
CREATE TABLE configuracion (
  clave        VARCHAR(60)  NOT NULL,
  valor        TEXT         NULL,
  descripcion  VARCHAR(255) NULL,
  PRIMARY KEY (clave)
) ENGINE=InnoDB;

-- =====================================================================
--  PAQUETE: SESIONES (Usuario, Rol, Empleado)
-- =====================================================================
CREATE TABLE rol (
  id_rol       TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre       VARCHAR(30)  NOT NULL,
  descripcion  VARCHAR(255) NULL,
  estado       TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (id_rol),
  UNIQUE KEY uq_rol_nombre (nombre)
) ENGINE=InnoDB;

CREATE TABLE cargo (
  id_cargo     SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre       VARCHAR(80)  NOT NULL,
  estado       TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (id_cargo),
  UNIQUE KEY uq_cargo_nombre (nombre)
) ENGINE=InnoDB;

CREATE TABLE empleado (
  id_empleado        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ci                 VARCHAR(20)  NOT NULL,
  nombres            VARCHAR(80)  NOT NULL,
  apellidos          VARCHAR(80)  NOT NULL,
  id_cargo           SMALLINT UNSIGNED NOT NULL,
  telefono           VARCHAR(25)  NULL,
  correo             VARCHAR(120) NULL,
  direccion          VARCHAR(200) NULL,
  fecha_contratacion DATE         NOT NULL,
  estado             ENUM('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO',
  creado_en          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en     DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_empleado),
  UNIQUE KEY uq_empleado_ci (ci),
  UNIQUE KEY uq_empleado_correo (correo),
  KEY idx_empleado_cargo (id_cargo),
  CONSTRAINT fk_empleado_cargo FOREIGN KEY (id_cargo) REFERENCES cargo (id_cargo)
) ENGINE=InnoDB;

CREATE TABLE usuario (
  id_usuario          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_empleado         INT UNSIGNED NOT NULL,
  id_rol              TINYINT UNSIGNED NOT NULL,
  nombre_usuario      VARCHAR(40)  NOT NULL,
  contrasena_hash     VARCHAR(255) NOT NULL,
  estado              ENUM('ACTIVO','BLOQUEADO','INACTIVO') NOT NULL DEFAULT 'ACTIVO',
  cambiar_contrasena  TINYINT(1)   NOT NULL DEFAULT 1,
  intentos_fallidos   TINYINT UNSIGNED NOT NULL DEFAULT 0,
  ultimo_acceso       DATETIME     NULL,
  creado_en           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en      DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_usuario),
  UNIQUE KEY uq_usuario_nombre (nombre_usuario),
  UNIQUE KEY uq_usuario_empleado (id_empleado),
  KEY idx_usuario_rol (id_rol),
  CONSTRAINT fk_usuario_empleado FOREIGN KEY (id_empleado) REFERENCES empleado (id_empleado),
  CONSTRAINT fk_usuario_rol      FOREIGN KEY (id_rol)      REFERENCES rol (id_rol)
) ENGINE=InnoDB;

-- =====================================================================
--  PAQUETE: GESTIÓN DE HUÉSPEDES
-- =====================================================================
CREATE TABLE tipo_documento (
  id_tipo_documento TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre            VARCHAR(60) NOT NULL,
  sigla             VARCHAR(10) NOT NULL,
  PRIMARY KEY (id_tipo_documento),
  UNIQUE KEY uq_tipo_documento_sigla (sigla)
) ENGINE=InnoDB;

CREATE TABLE huesped (
  id_huesped          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_tipo_documento   TINYINT UNSIGNED NOT NULL,
  nro_documento       VARCHAR(30)  NOT NULL,
  nombres             VARCHAR(80)  NOT NULL,
  apellidos           VARCHAR(80)  NOT NULL,
  nacionalidad        VARCHAR(60)  NOT NULL DEFAULT 'Boliviana',
  ciudad_procedencia  VARCHAR(80)  NULL,
  telefono            VARCHAR(25)  NULL,
  correo              VARCHAR(120) NULL,
  fecha_nacimiento    DATE         NULL,
  observaciones       TEXT         NULL,
  creado_en           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en      DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_huesped),
  UNIQUE KEY uq_huesped_documento (id_tipo_documento, nro_documento),
  KEY idx_huesped_nombre (apellidos, nombres),
  CONSTRAINT fk_huesped_tipo_documento FOREIGN KEY (id_tipo_documento) REFERENCES tipo_documento (id_tipo_documento)
) ENGINE=InnoDB;

-- =====================================================================
--  PAQUETE: GESTIÓN DE HABITACIONES
-- =====================================================================
CREATE TABLE categoria_habitacion (
  id_categoria   SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre         VARCHAR(60)   NOT NULL,
  descripcion    TEXT          NULL,
  capacidad      TINYINT UNSIGNED NOT NULL,
  tarifa_base    DECIMAL(10,2) NOT NULL,
  camas          VARCHAR(80)   NULL,
  superficie_m2  SMALLINT UNSIGNED NULL,
  comodidades    TEXT          NULL COMMENT 'Lista separada por |',
  imagen         VARCHAR(120)  NULL,
  estado         TINYINT(1)    NOT NULL DEFAULT 1,
  PRIMARY KEY (id_categoria),
  UNIQUE KEY uq_categoria_nombre (nombre),
  CONSTRAINT chk_categoria_capacidad CHECK (capacidad > 0),
  CONSTRAINT chk_categoria_tarifa    CHECK (tarifa_base > 0)
) ENGINE=InnoDB;

CREATE TABLE habitacion (
  id_habitacion  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  numero         VARCHAR(10)  NOT NULL,
  piso           TINYINT      NOT NULL,
  id_categoria   SMALLINT UNSIGNED NOT NULL,
  descripcion    VARCHAR(255) NULL,
  estado         ENUM('DISPONIBLE','OCUPADA','LIMPIEZA','MANTENIMIENTO') NOT NULL DEFAULT 'DISPONIBLE',
  creado_en      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_habitacion),
  UNIQUE KEY uq_habitacion_numero (numero),
  KEY idx_habitacion_categoria (id_categoria),
  KEY idx_habitacion_estado (estado),
  -- CU-09 3a: no se puede eliminar una categoría con habitaciones asignadas
  CONSTRAINT fk_habitacion_categoria FOREIGN KEY (id_categoria) REFERENCES categoria_habitacion (id_categoria) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE historial_estado_habitacion (
  id_historial     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_habitacion    INT UNSIGNED NOT NULL,
  estado_anterior  VARCHAR(20)  NOT NULL,
  estado_nuevo     VARCHAR(20)  NOT NULL,
  motivo           VARCHAR(255) NULL,
  id_usuario       INT UNSIGNED NULL,
  fecha            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_historial),
  KEY idx_historial_habitacion (id_habitacion, fecha),
  CONSTRAINT fk_historial_habitacion FOREIGN KEY (id_habitacion) REFERENCES habitacion (id_habitacion) ON DELETE CASCADE,
  CONSTRAINT fk_historial_usuario    FOREIGN KEY (id_usuario)    REFERENCES usuario (id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================================
--  PAQUETE: GESTIÓN DE RESERVAS (Reserva, DetalleReserva, Estadia, Cancelacion)
-- =====================================================================
CREATE TABLE canal_reserva (
  id_canal  TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre    VARCHAR(40) NOT NULL,
  icono     VARCHAR(40) NULL,
  estado    TINYINT(1)  NOT NULL DEFAULT 1,
  PRIMARY KEY (id_canal),
  UNIQUE KEY uq_canal_nombre (nombre)
) ENGINE=InnoDB;

CREATE TABLE reserva (
  id_reserva          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  codigo              VARCHAR(20)  NOT NULL,
  id_huesped          INT UNSIGNED NOT NULL,
  id_canal            TINYINT UNSIGNED NOT NULL,
  id_usuario          INT UNSIGNED NULL COMMENT 'Usuario que registró la reserva',
  fecha_ingreso       DATE         NOT NULL,
  fecha_salida        DATE         NOT NULL,
  cantidad_personas   TINYINT UNSIGNED NOT NULL DEFAULT 1,
  estado              ENUM('PENDIENTE','CONFIRMADA','EN_CURSO','FINALIZADA','CANCELADA','NO_SHOW') NOT NULL DEFAULT 'CONFIRMADA',
  monto_total         DECIMAL(10,2) NOT NULL DEFAULT 0,
  cargos_extra        DECIMAL(10,2) NOT NULL DEFAULT 0,
  es_sobreventa       TINYINT(1)   NOT NULL DEFAULT 0,
  observaciones       TEXT         NULL,
  fecha_registro      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_modificacion  DATETIME     NULL,
  PRIMARY KEY (id_reserva),
  UNIQUE KEY uq_reserva_codigo (codigo),
  KEY idx_reserva_huesped (id_huesped),
  KEY idx_reserva_periodo (fecha_ingreso, fecha_salida),
  KEY idx_reserva_estado (estado),
  CONSTRAINT fk_reserva_huesped FOREIGN KEY (id_huesped) REFERENCES huesped (id_huesped) ON DELETE RESTRICT,
  CONSTRAINT fk_reserva_canal   FOREIGN KEY (id_canal)   REFERENCES canal_reserva (id_canal),
  CONSTRAINT fk_reserva_usuario FOREIGN KEY (id_usuario) REFERENCES usuario (id_usuario) ON DELETE SET NULL,
  CONSTRAINT chk_reserva_fechas CHECK (fecha_salida > fecha_ingreso)
) ENGINE=InnoDB;

CREATE TABLE detalle_reserva (
  id_detalle     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_reserva     INT UNSIGNED NOT NULL,
  id_categoria   SMALLINT UNSIGNED NOT NULL,
  id_habitacion  INT UNSIGNED NULL COMMENT 'NULL = reserva en sobreventa sin habitación asignada',
  precio_noche   DECIMAL(10,2) NOT NULL COMMENT 'Precio promedio por noche aplicado',
  noches         SMALLINT UNSIGNED NOT NULL,
  subtotal       DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (id_detalle),
  UNIQUE KEY uq_detalle_reserva_habitacion (id_reserva, id_habitacion),
  KEY idx_detalle_habitacion (id_habitacion),
  KEY idx_detalle_categoria (id_categoria),
  CONSTRAINT fk_detalle_reserva    FOREIGN KEY (id_reserva)    REFERENCES reserva (id_reserva) ON DELETE CASCADE,
  CONSTRAINT fk_detalle_categoria  FOREIGN KEY (id_categoria)  REFERENCES categoria_habitacion (id_categoria),
  -- CU-08 4a: no se puede eliminar una habitación con reservas asociadas
  CONSTRAINT fk_detalle_habitacion FOREIGN KEY (id_habitacion) REFERENCES habitacion (id_habitacion) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE estadia (
  id_estadia           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_reserva           INT UNSIGNED NOT NULL,
  fecha_checkin        DATETIME     NOT NULL,
  fecha_checkout       DATETIME     NULL,
  id_usuario_checkin   INT UNSIGNED NULL,
  id_usuario_checkout  INT UNSIGNED NULL,
  observaciones        TEXT         NULL,
  PRIMARY KEY (id_estadia),
  UNIQUE KEY uq_estadia_reserva (id_reserva),
  KEY idx_estadia_abierta (fecha_checkout),
  CONSTRAINT fk_estadia_reserva  FOREIGN KEY (id_reserva)          REFERENCES reserva (id_reserva) ON DELETE CASCADE,
  CONSTRAINT fk_estadia_checkin  FOREIGN KEY (id_usuario_checkin)  REFERENCES usuario (id_usuario) ON DELETE SET NULL,
  CONSTRAINT fk_estadia_checkout FOREIGN KEY (id_usuario_checkout) REFERENCES usuario (id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE cancelacion (
  id_cancelacion  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_reserva      INT UNSIGNED NOT NULL,
  tipo_motivo     ENUM('CAMBIO_DE_PLANES','SALUD','TRABAJO','BLOQUEOS_O_CLIMA','PRECIO','OTRA_OPCION','ERROR_DE_REGISTRO','OTRO') NOT NULL,
  descripcion     VARCHAR(255) NOT NULL,
  id_usuario      INT UNSIGNED NULL,
  fecha           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_cancelacion),
  UNIQUE KEY uq_cancelacion_reserva (id_reserva),
  KEY idx_cancelacion_tipo (tipo_motivo),
  CONSTRAINT fk_cancelacion_reserva FOREIGN KEY (id_reserva) REFERENCES reserva (id_reserva) ON DELETE CASCADE,
  CONSTRAINT fk_cancelacion_usuario FOREIGN KEY (id_usuario) REFERENCES usuario (id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE metodo_pago (
  id_metodo_pago       TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre               VARCHAR(40) NOT NULL,
  requiere_referencia  TINYINT(1)  NOT NULL DEFAULT 0,
  estado               TINYINT(1)  NOT NULL DEFAULT 1,
  PRIMARY KEY (id_metodo_pago),
  UNIQUE KEY uq_metodo_pago_nombre (nombre)
) ENGINE=InnoDB;

CREATE TABLE pago (
  id_pago         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_reserva      INT UNSIGNED NOT NULL,
  id_metodo_pago  TINYINT UNSIGNED NOT NULL,
  concepto        ENUM('ANTICIPO','HOSPEDAJE','CONSUMO','PENALIDAD') NOT NULL DEFAULT 'HOSPEDAJE',
  monto           DECIMAL(10,2) NOT NULL,
  referencia      VARCHAR(80)   NULL,
  fecha           DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  id_usuario      INT UNSIGNED  NULL,
  PRIMARY KEY (id_pago),
  KEY idx_pago_reserva (id_reserva),
  KEY idx_pago_fecha (fecha),
  CONSTRAINT fk_pago_reserva FOREIGN KEY (id_reserva)     REFERENCES reserva (id_reserva) ON DELETE CASCADE,
  CONSTRAINT fk_pago_metodo  FOREIGN KEY (id_metodo_pago) REFERENCES metodo_pago (id_metodo_pago),
  CONSTRAINT fk_pago_usuario FOREIGN KEY (id_usuario)     REFERENCES usuario (id_usuario) ON DELETE SET NULL,
  CONSTRAINT chk_pago_monto CHECK (monto > 0)
) ENGINE=InnoDB;

CREATE TABLE recibo (
  id_recibo      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  numero         VARCHAR(20)   NOT NULL,
  id_reserva     INT UNSIGNED  NOT NULL,
  total          DECIMAL(10,2) NOT NULL,
  fecha_emision  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  id_usuario     INT UNSIGNED  NULL,
  PRIMARY KEY (id_recibo),
  UNIQUE KEY uq_recibo_numero (numero),
  KEY idx_recibo_reserva (id_reserva),
  CONSTRAINT fk_recibo_reserva FOREIGN KEY (id_reserva) REFERENCES reserva (id_reserva) ON DELETE CASCADE,
  CONSTRAINT fk_recibo_usuario FOREIGN KEY (id_usuario) REFERENCES usuario (id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================================
--  PAQUETE: POLÍTICA DE SOBREVENTA (TasaHistorica, ConfiguracionSobreventa,
--  EvaluacionSobreventa)
-- =====================================================================
CREATE TABLE tasa_historica (
  id_tasa           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  anio              SMALLINT UNSIGNED NOT NULL,
  mes               TINYINT UNSIGNED  NOT NULL,
  total_reservas    INT UNSIGNED NOT NULL,
  cancelaciones     INT UNSIGNED NOT NULL,
  no_show           INT UNSIGNED NOT NULL,
  tasa_cancelacion  DECIMAL(6,4) NOT NULL,
  tasa_no_show      DECIMAL(6,4) NOT NULL,
  fecha_calculo     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_tasa),
  UNIQUE KEY uq_tasa_periodo (anio, mes)
) ENGINE=InnoDB;

CREATE TABLE configuracion_sobreventa (
  id_configuracion       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  umbral_ocupacion       DECIMAL(5,2)  NOT NULL DEFAULT 90.00 COMMENT 'Porcentaje',
  costo_reubicacion      DECIMAL(10,2) NOT NULL DEFAULT 650.00 COMMENT 'Bs por huésped reubicado',
  max_reservas_extra     TINYINT UNSIGNED NOT NULL DEFAULT 3,
  meses_historial        TINYINT UNSIGNED NOT NULL DEFAULT 12,
  incluir_cancelaciones  TINYINT(1)    NOT NULL DEFAULT 0 COMMENT '1 = la probabilidad de ausencia suma cancelaciones tardías',
  vigente                TINYINT(1)    NOT NULL DEFAULT 1,
  id_usuario             INT UNSIGNED  NULL,
  fecha                  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_configuracion),
  CONSTRAINT fk_config_sobreventa_usuario FOREIGN KEY (id_usuario) REFERENCES usuario (id_usuario) ON DELETE SET NULL,
  CONSTRAINT chk_config_umbral CHECK (umbral_ocupacion BETWEEN 50 AND 100)
) ENGINE=InnoDB;

CREATE TABLE evaluacion_sobreventa (
  id_evaluacion          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  fecha_objetivo         DATE          NOT NULL,
  capacidad              SMALLINT UNSIGNED NOT NULL,
  reservas_confirmadas   SMALLINT UNSIGNED NOT NULL,
  ocupacion              DECIMAL(5,2)  NOT NULL,
  p_no_show              DECIMAL(6,4)  NOT NULL,
  ingreso                DECIMAL(10,2) NOT NULL,
  costo_reubicacion      DECIMAL(10,2) NOT NULL,
  ve_simple              DECIMAL(12,2) NOT NULL COMMENT 'VE = p·I − (1 − p)·C (informe CU-18)',
  ve_aceptar             DECIMAL(12,2) NOT NULL COMMENT 'Modelo binomial',
  ve_rechazar            DECIMAL(12,2) NOT NULL DEFAULT 0,
  nivel_optimo           TINYINT UNSIGNED NOT NULL DEFAULT 0,
  decision               ENUM('ACEPTAR','RECHAZAR') NOT NULL,
  origen                 ENUM('MANUAL','RESERVA') NOT NULL DEFAULT 'MANUAL',
  decision_usuario       ENUM('CONTINUAR','CANCELAR') NULL,
  id_reserva             INT UNSIGNED  NULL,
  id_usuario             INT UNSIGNED  NULL,
  fecha                  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_evaluacion),
  KEY idx_evaluacion_fecha (fecha_objetivo),
  CONSTRAINT fk_evaluacion_reserva FOREIGN KEY (id_reserva) REFERENCES reserva (id_reserva) ON DELETE SET NULL,
  CONSTRAINT fk_evaluacion_usuario FOREIGN KEY (id_usuario) REFERENCES usuario (id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================================
--  PAQUETE: ESTIMACIÓN DE DEMANDA Y TARIFAS (Temporada, TarifaTemporada,
--  SugerenciaTarifa)
-- =====================================================================
CREATE TABLE temporada (
  id_temporada  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre        VARCHAR(80)  NOT NULL,
  tipo          ENUM('ALTA','BAJA','ESPECIAL') NOT NULL,
  fecha_inicio  DATE         NOT NULL,
  fecha_fin     DATE         NOT NULL,
  descripcion   VARCHAR(255) NULL,
  color         CHAR(7)      NOT NULL DEFAULT '#C6A15B',
  estado        TINYINT(1)   NOT NULL DEFAULT 1,
  creado_en     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_temporada),
  KEY idx_temporada_fechas (fecha_inicio, fecha_fin),
  CONSTRAINT chk_temporada_fechas CHECK (fecha_fin >= fecha_inicio)
) ENGINE=InnoDB;

CREATE TABLE tarifa_temporada (
  id_tarifa     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_temporada  INT UNSIGNED NOT NULL,
  id_categoria  SMALLINT UNSIGNED NOT NULL,
  precio_noche  DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (id_tarifa),
  UNIQUE KEY uq_tarifa_temporada_categoria (id_temporada, id_categoria),
  CONSTRAINT fk_tarifa_temporada FOREIGN KEY (id_temporada) REFERENCES temporada (id_temporada) ON DELETE CASCADE,
  CONSTRAINT fk_tarifa_categoria FOREIGN KEY (id_categoria) REFERENCES categoria_habitacion (id_categoria) ON DELETE CASCADE,
  CONSTRAINT chk_tarifa_precio CHECK (precio_noche > 0)
) ENGINE=InnoDB;

CREATE TABLE sugerencia_tarifa (
  id_sugerencia        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  fecha_desde          DATE          NOT NULL,
  fecha_hasta          DATE          NOT NULL,
  id_categoria         SMALLINT UNSIGNED NOT NULL,
  ocupacion_historica  DECIMAL(5,2)  NOT NULL,
  dias_muestra         SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  factor               DECIMAL(5,3)  NOT NULL,
  tarifa_base          DECIMAL(10,2) NOT NULL,
  tarifa_sugerida      DECIMAL(10,2) NOT NULL,
  aplicada             TINYINT(1)    NOT NULL DEFAULT 0,
  id_temporada         INT UNSIGNED  NULL,
  id_usuario           INT UNSIGNED  NULL,
  fecha                DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_sugerencia),
  CONSTRAINT fk_sugerencia_categoria FOREIGN KEY (id_categoria) REFERENCES categoria_habitacion (id_categoria) ON DELETE CASCADE,
  CONSTRAINT fk_sugerencia_temporada FOREIGN KEY (id_temporada) REFERENCES temporada (id_temporada) ON DELETE SET NULL,
  CONSTRAINT fk_sugerencia_usuario   FOREIGN KEY (id_usuario)   REFERENCES usuario (id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================================
--  SITIO PÚBLICO Y AUDITORÍA
-- =====================================================================
CREATE TABLE solicitud_reserva (
  id_solicitud       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombres            VARCHAR(80)  NOT NULL,
  apellidos          VARCHAR(80)  NOT NULL,
  id_tipo_documento  TINYINT UNSIGNED NOT NULL DEFAULT 1,
  nro_documento      VARCHAR(30)  NOT NULL,
  nacionalidad       VARCHAR(60)  NULL,
  ciudad_procedencia VARCHAR(80)  NULL,
  correo             VARCHAR(120) NOT NULL,
  telefono           VARCHAR(25)  NOT NULL,
  id_categoria       SMALLINT UNSIGNED NOT NULL,
  fecha_ingreso      DATE         NOT NULL,
  fecha_salida       DATE         NOT NULL,
  cantidad_personas  TINYINT UNSIGNED NOT NULL DEFAULT 1,
  mensaje            TEXT         NULL,
  monto_estimado     DECIMAL(10,2) NULL,
  estado             ENUM('NUEVA','ATENDIDA','DESCARTADA') NOT NULL DEFAULT 'NUEVA',
  id_reserva         INT UNSIGNED NULL,
  ip                 VARCHAR(45)  NULL,
  fecha              DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_solicitud),
  KEY idx_solicitud_estado (estado),
  CONSTRAINT fk_solicitud_categoria FOREIGN KEY (id_categoria) REFERENCES categoria_habitacion (id_categoria),
  CONSTRAINT fk_solicitud_reserva   FOREIGN KEY (id_reserva)   REFERENCES reserva (id_reserva) ON DELETE SET NULL,
  CONSTRAINT fk_solicitud_tipo_doc  FOREIGN KEY (id_tipo_documento) REFERENCES tipo_documento (id_tipo_documento)
) ENGINE=InnoDB;

CREATE TABLE mensaje_contacto (
  id_mensaje  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre      VARCHAR(120) NOT NULL,
  correo      VARCHAR(120) NOT NULL,
  telefono    VARCHAR(25)  NULL,
  asunto      VARCHAR(120) NOT NULL,
  mensaje     TEXT         NOT NULL,
  leido       TINYINT(1)   NOT NULL DEFAULT 0,
  fecha       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_mensaje)
) ENGINE=InnoDB;

CREATE TABLE bitacora (
  id_bitacora  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_usuario   INT UNSIGNED NULL,
  modulo       VARCHAR(40)  NOT NULL,
  accion       VARCHAR(60)  NOT NULL,
  descripcion  VARCHAR(255) NULL,
  ip           VARCHAR(45)  NULL,
  fecha        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_bitacora),
  KEY idx_bitacora_fecha (fecha),
  KEY idx_bitacora_modulo (modulo),
  CONSTRAINT fk_bitacora_usuario FOREIGN KEY (id_usuario) REFERENCES usuario (id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================================
--  VISTAS (CU-07, CU-23, CU-24)
-- =====================================================================
CREATE OR REPLACE VIEW vw_historial_huesped AS
SELECT r.id_reserva, r.id_huesped, r.codigo, r.fecha_ingreso, r.fecha_salida,
       DATEDIFF(r.fecha_salida, r.fecha_ingreso) AS noches,
       r.estado, r.monto_total, r.cargos_extra, r.es_sobreventa,
       c.nombre AS canal,
       e.fecha_checkin, e.fecha_checkout,
       (SELECT GROUP_CONCAT(COALESCE(h.numero, 'Sin asignar') ORDER BY h.numero SEPARATOR ', ')
          FROM detalle_reserva d LEFT JOIN habitacion h ON h.id_habitacion = d.id_habitacion
         WHERE d.id_reserva = r.id_reserva) AS habitaciones,
       (SELECT GROUP_CONCAT(DISTINCT ch.nombre SEPARATOR ', ')
          FROM detalle_reserva d JOIN categoria_habitacion ch ON ch.id_categoria = d.id_categoria
         WHERE d.id_reserva = r.id_reserva) AS categorias,
       (SELECT COALESCE(SUM(p.monto), 0) FROM pago p WHERE p.id_reserva = r.id_reserva) AS pagado
  FROM reserva r
  JOIN canal_reserva c ON c.id_canal = r.id_canal
  LEFT JOIN estadia e ON e.id_reserva = r.id_reserva;

CREATE OR REPLACE VIEW vw_ingresos_mensuales AS
SELECT YEAR(r.fecha_ingreso) AS anio, MONTH(r.fecha_ingreso) AS mes,
       ch.id_categoria, ch.nombre AS categoria,
       COUNT(DISTINCT r.id_reserva) AS reservas,
       SUM(d.noches) AS noches,
       SUM(d.subtotal) AS ingreso
  FROM reserva r
  JOIN detalle_reserva d ON d.id_reserva = r.id_reserva
  JOIN categoria_habitacion ch ON ch.id_categoria = d.id_categoria
 WHERE r.estado NOT IN ('CANCELADA', 'NO_SHOW')
 GROUP BY YEAR(r.fecha_ingreso), MONTH(r.fecha_ingreso), ch.id_categoria, ch.nombre;

CREATE OR REPLACE VIEW vw_reporte_cancelaciones AS
SELECT ca.id_cancelacion, ca.tipo_motivo, ca.descripcion, ca.fecha AS fecha_cancelacion,
       r.id_reserva, r.codigo, r.fecha_ingreso, r.fecha_salida, r.monto_total,
       DATEDIFF(r.fecha_ingreso, DATE(ca.fecha)) AS dias_anticipacion,
       CONCAT(h.nombres, ' ', h.apellidos) AS huesped, h.nacionalidad,
       cr.nombre AS canal,
       u.nombre_usuario AS usuario
  FROM cancelacion ca
  JOIN reserva r ON r.id_reserva = ca.id_reserva
  JOIN huesped h ON h.id_huesped = r.id_huesped
  JOIN canal_reserva cr ON cr.id_canal = r.id_canal
  LEFT JOIN usuario u ON u.id_usuario = ca.id_usuario;

-- =====================================================================
--  DISPARADORES
--  Defensa en profundidad: además de la validación transaccional en PHP,
--  la base de datos rechaza cualquier traslape de fechas en una habitación.
-- =====================================================================
DELIMITER $$

CREATE TRIGGER trg_detalle_reserva_antitraslape_ins
BEFORE INSERT ON detalle_reserva FOR EACH ROW
BEGIN
  DECLARE v_ingreso DATE;
  DECLARE v_salida DATE;
  DECLARE v_estado VARCHAR(20);
  DECLARE v_cruces INT DEFAULT 0;
  IF NEW.id_habitacion IS NOT NULL THEN
    SELECT fecha_ingreso, fecha_salida, estado INTO v_ingreso, v_salida, v_estado
      FROM reserva WHERE id_reserva = NEW.id_reserva;
    IF v_estado IN ('PENDIENTE', 'CONFIRMADA', 'EN_CURSO') THEN
      SELECT COUNT(*) INTO v_cruces
        FROM detalle_reserva d
        JOIN reserva r ON r.id_reserva = d.id_reserva
       WHERE d.id_habitacion = NEW.id_habitacion
         AND r.id_reserva <> NEW.id_reserva
         AND r.estado IN ('PENDIENTE', 'CONFIRMADA', 'EN_CURSO')
         AND r.fecha_ingreso < v_salida
         AND r.fecha_salida > v_ingreso;
      IF v_cruces > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'HABITACION_NO_DISPONIBLE: la habitación ya está reservada en esas fechas';
      END IF;
    END IF;
  END IF;
END$$

CREATE TRIGGER trg_detalle_reserva_antitraslape_upd
BEFORE UPDATE ON detalle_reserva FOR EACH ROW
BEGIN
  DECLARE v_ingreso DATE;
  DECLARE v_salida DATE;
  DECLARE v_estado VARCHAR(20);
  DECLARE v_cruces INT DEFAULT 0;
  IF NEW.id_habitacion IS NOT NULL AND NOT (NEW.id_habitacion <=> OLD.id_habitacion) THEN
    SELECT fecha_ingreso, fecha_salida, estado INTO v_ingreso, v_salida, v_estado
      FROM reserva WHERE id_reserva = NEW.id_reserva;
    IF v_estado IN ('PENDIENTE', 'CONFIRMADA', 'EN_CURSO') THEN
      SELECT COUNT(*) INTO v_cruces
        FROM detalle_reserva d
        JOIN reserva r ON r.id_reserva = d.id_reserva
       WHERE d.id_habitacion = NEW.id_habitacion
         AND r.id_reserva <> NEW.id_reserva
         AND r.estado IN ('PENDIENTE', 'CONFIRMADA', 'EN_CURSO')
         AND r.fecha_ingreso < v_salida
         AND r.fecha_salida > v_ingreso;
      IF v_cruces > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'HABITACION_NO_DISPONIBLE: la habitación ya está reservada en esas fechas';
      END IF;
    END IF;
  END IF;
END$$

CREATE TRIGGER trg_reserva_antitraslape_upd
BEFORE UPDATE ON reserva FOR EACH ROW
BEGIN
  DECLARE v_cruces INT DEFAULT 0;
  IF NEW.estado IN ('PENDIENTE', 'CONFIRMADA', 'EN_CURSO')
     AND (NEW.fecha_ingreso <> OLD.fecha_ingreso OR NEW.fecha_salida <> OLD.fecha_salida
          OR OLD.estado NOT IN ('PENDIENTE', 'CONFIRMADA', 'EN_CURSO')) THEN
    SELECT COUNT(*) INTO v_cruces
      FROM detalle_reserva d1
      JOIN detalle_reserva d2 ON d2.id_habitacion = d1.id_habitacion AND d2.id_reserva <> d1.id_reserva
      JOIN reserva r2 ON r2.id_reserva = d2.id_reserva
     WHERE d1.id_reserva = NEW.id_reserva
       AND d1.id_habitacion IS NOT NULL
       AND r2.estado IN ('PENDIENTE', 'CONFIRMADA', 'EN_CURSO')
       AND r2.fecha_ingreso < NEW.fecha_salida
       AND r2.fecha_salida > NEW.fecha_ingreso;
    IF v_cruces > 0 THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'HABITACION_NO_DISPONIBLE: las nuevas fechas se cruzan con otra reserva';
    END IF;
  END IF;
END$$

-- CU-14 paso 3: al registrar la cancelación, el disparador cambia la reserva a CANCELADA
CREATE TRIGGER trg_cancelacion_reserva
AFTER INSERT ON cancelacion FOR EACH ROW
BEGIN
  UPDATE reserva
     SET estado = 'CANCELADA', fecha_modificacion = NEW.fecha
   WHERE id_reserva = NEW.id_reserva;
END$$

DELIMITER ;

-- =====================================================================
--  CATÁLOGOS BASE
-- =====================================================================
INSERT INTO rol (id_rol, nombre, descripcion) VALUES
 (1, 'Administrador', 'Gestiona todo el sistema: personal, cuentas, inventario, tarifas y parámetros.'),
 (2, 'Recepcionista', 'Opera huéspedes, reservas, check-in/check-out y el estado de las habitaciones.'),
 (3, 'Gerente',       'Consulta el dashboard, los reportes, la política de sobreventa y las tarifas.');

INSERT INTO cargo (id_cargo, nombre) VALUES
 (1, 'Gerente General'), (2, 'Administrador(a)'), (3, 'Contador(a)'), (4, 'Recepcionista'),
 (5, 'Jefe(a) de Recepción'), (6, 'Camarera de piso'), (7, 'Técnico de mantenimiento');

INSERT INTO tipo_documento (id_tipo_documento, nombre, sigla) VALUES
 (1, 'Cédula de Identidad', 'CI'), (2, 'Pasaporte', 'PAS'), (3, 'DNI extranjero', 'DNI');

INSERT INTO canal_reserva (id_canal, nombre, icono) VALUES
 (1, 'Presencial', 'bi-person-badge'), (2, 'Teléfono', 'bi-telephone'), (3, 'WhatsApp', 'bi-whatsapp'),
 (4, 'Sitio web', 'bi-globe2'), (5, 'Booking.com', 'bi-buildings'), (6, 'Agencia de viajes', 'bi-briefcase');

INSERT INTO metodo_pago (id_metodo_pago, nombre, requiere_referencia) VALUES
 (1, 'Efectivo', 0), (2, 'QR Simple', 1), (3, 'Tarjeta de débito/crédito', 1), (4, 'Transferencia bancaria', 1);

INSERT INTO configuracion (clave, valor, descripcion) VALUES
 ('hotel_nombre',      'Hotel Elegance', 'Nombre comercial'),
 ('hotel_razon_social','Hotel Elegance S.R.L.', 'Razón social'),
 ('hotel_nit',         '1029384021', 'NIT'),
 ('hotel_direccion',   'Av. Saavedra Díaz Romero N.º 1845, zona Miraflores', 'Dirección'),
 ('hotel_ciudad',      'La Paz, Bolivia', 'Ciudad'),
 ('hotel_telefono',    '+591 2 222 1845', 'Teléfono de recepción'),
 ('hotel_whatsapp',    '+591 712 18450', 'WhatsApp'),
 ('hotel_correo',      'reservas@hotelelegance.bo', 'Correo de reservas'),
 ('hora_checkin',      '14:00', 'Hora de check-in'),
 ('hora_checkout',     '12:00', 'Hora de check-out'),
 ('anticipo_porcentaje','30', 'Porcentaje sugerido de anticipo'),
 ('tarifa_meta_ocupacion','70', 'Ocupación objetivo (%) para la tarifa sugerida'),
 ('tarifa_sensibilidad','0.80', 'Sensibilidad α del factor de ajuste de tarifa'),
 ('tarifa_factor_min', '0.85', 'Factor mínimo de ajuste'),
 ('tarifa_factor_max', '1.40', 'Factor máximo de ajuste');

INSERT INTO configuracion_sobreventa (umbral_ocupacion, costo_reubicacion, max_reservas_extra, meses_historial, incluir_cancelaciones, vigente)
VALUES (90.00, 650.00, 3, 12, 0, 1);

SET FOREIGN_KEY_CHECKS = 1;
