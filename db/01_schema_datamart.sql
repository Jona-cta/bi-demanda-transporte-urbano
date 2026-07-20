-- ============================================================================
--  DATA MART — transporte_dm   (modelo estrella / constelación de 2 hechos)
--  Proyecto Final Inteligencia de Negocios — UTP
--  Motor: MariaDB 11.4 · InnoDB · utf8mb4
--
--  Constelación:
--    fact_validacion_diaria  (grano: fecha×ruta×sentido×paradero×tipo_pasaje)
--    fact_carrera            (grano: 1 fila por carrera)
--  Dimensiones conformadas: dim_tiempo, dim_ruta, dim_paradero, dim_sentido,
--  dim_tipo_pasaje.
--  Este script SOLO crea la estructura. La carga la hace el ETL en Python.
-- ============================================================================

CREATE DATABASE IF NOT EXISTS transporte_dm
  CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
-- Alinea el collation por defecto de la BD al del origen OLTP (general_ci),
-- para que las tablas de staging heredadas y los joins cross-database no choquen.
ALTER DATABASE transporte_dm CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE transporte_dm;

-- Orden de DROP respetando dependencias (hechos primero).
DROP TABLE IF EXISTS fact_validacion_diaria;
DROP TABLE IF EXISTS fact_carrera;
DROP TABLE IF EXISTS dim_tiempo;
DROP TABLE IF EXISTS dim_ruta;
DROP TABLE IF EXISTS dim_paradero;
DROP TABLE IF EXISTS dim_sentido;
DROP TABLE IF EXISTS dim_tipo_pasaje;
DROP TABLE IF EXISTS dim_franja_horaria;

-- ---------------------------------------------------------------------------
-- DIMENSIÓN TIEMPO  — jerarquía Año > Trimestre > Mes > Semana > Día
-- Clave inteligente YYYYMMDD (int) para joins rápidos y legibles.
-- ---------------------------------------------------------------------------
CREATE TABLE dim_tiempo (
  id_tiempo      INT           NOT NULL,          -- YYYYMMDD
  fecha          DATE          NOT NULL,
  anio           SMALLINT      NOT NULL,
  trimestre      TINYINT       NOT NULL,          -- 1..4
  mes            TINYINT       NOT NULL,          -- 1..12
  mes_nombre     VARCHAR(12)   NOT NULL,          -- Enero..Diciembre
  anio_mes       CHAR(7)       NOT NULL,          -- YYYY-MM (para eje temporal)
  semana_anio    TINYINT       NOT NULL,          -- semana ISO 1..53
  dia            TINYINT       NOT NULL,          -- 1..31
  dia_semana     TINYINT       NOT NULL,          -- 1=Lunes .. 7=Domingo
  dia_nombre     VARCHAR(10)   NOT NULL,          -- Lunes..Domingo
  es_fin_semana  TINYINT(1)    NOT NULL DEFAULT 0,
  PRIMARY KEY (id_tiempo),
  UNIQUE KEY uk_fecha (fecha),
  KEY idx_anio_mes (anio, mes)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------------
-- DIMENSIÓN RUTA  — desde el catálogo OLTP 2op_ruta
-- ---------------------------------------------------------------------------
CREATE TABLE dim_ruta (
  id_ruta      SMALLINT      NOT NULL,            -- = 2op_ruta.id (natural)
  codigo_ruta  VARCHAR(20)   NOT NULL,            -- 301, 303, ESCOLAR, ...
  tipo_ruta    VARCHAR(15)   NOT NULL,            -- Regular / Expreso / Auxiliar / Escolar / Otro (derivado)
  PRIMARY KEY (id_ruta),
  KEY idx_codigo (codigo_ruta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------------
-- DIMENSIÓN PARADERO  — desde DISTINCT(paradero) con su zona predominante
-- (paradero y zona vienen como texto libre en la OLTP)
-- ---------------------------------------------------------------------------
CREATE TABLE dim_paradero (
  id_paradero      INT           NOT NULL AUTO_INCREMENT,  -- surrogate
  nombre_paradero  VARCHAR(60)   NOT NULL,
  zona             VARCHAR(50)   NOT NULL DEFAULT 'SIN ZONA',
  PRIMARY KEY (id_paradero),
  UNIQUE KEY uk_paradero (nombre_paradero),
  KEY idx_zona (zona)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------------
-- DIMENSIÓN SENTIDO  — normaliza NS/SN/IDA/VUELTA/EO/OE a Ida/Vuelta,
-- conservando el código de origen (trazabilidad ETL).
-- ---------------------------------------------------------------------------
CREATE TABLE dim_sentido (
  id_sentido     TINYINT       NOT NULL AUTO_INCREMENT,
  codigo_origen  VARCHAR(10)   NOT NULL,          -- NS, SN, IDA, ...
  sentido        VARCHAR(10)   NOT NULL,          -- Ida / Vuelta / Otro
  PRIMARY KEY (id_sentido),
  UNIQUE KEY uk_codigo (codigo_origen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------------
-- DIMENSIÓN TIPO DE PASAJE  — DERIVADA del monto por reglas de negocio
-- ---------------------------------------------------------------------------
CREATE TABLE dim_tipo_pasaje (
  id_tipo_pasaje  TINYINT      NOT NULL AUTO_INCREMENT,
  tipo            VARCHAR(15)  NOT NULL,          -- Adulto / Medio / Gratuito / Sin dato
  descripcion     VARCHAR(80)  NOT NULL,
  PRIMARY KEY (id_tipo_pasaje),
  UNIQUE KEY uk_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------------
-- DIMENSIÓN FRANJA HORARIA  — hora del día (0-23) con su banda operativa.
-- Franjas y horas punta definidas con la distribución REAL de la demanda:
-- punta mañana 6-8h (pico 7h) y punta tarde 17-19h.
-- ---------------------------------------------------------------------------
CREATE TABLE dim_franja_horaria (
  id_franja_horaria TINYINT     NOT NULL,          -- = HOUR(hora), 0..23
  hora_texto        CHAR(5)     NOT NULL,          -- "07:00"
  franja            VARCHAR(20) NOT NULL,          -- Madrugada / Punta Mañana / ...
  es_hora_punta     TINYINT(1)  NOT NULL DEFAULT 0,
  PRIMARY KEY (id_franja_horaria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------------
-- HECHO 1 — VALIDACIÓN DIARIA (agregado)
--   Grano: 1 fila por fecha × franja_horaria × ruta × sentido × paradero × tipo_pasaje
-- ---------------------------------------------------------------------------
CREATE TABLE fact_validacion_diaria (
  id_validacion_diaria BIGINT   NOT NULL AUTO_INCREMENT,
  id_tiempo         INT         NOT NULL,
  id_franja_horaria TINYINT     NOT NULL,
  id_ruta           SMALLINT    NOT NULL,
  id_sentido        TINYINT     NOT NULL,
  id_paradero       INT         NOT NULL,
  id_tipo_pasaje    TINYINT     NOT NULL,
  cant_validaciones INT         NOT NULL,         -- COUNT(*)
  monto_total       DECIMAL(14,2) NOT NULL,       -- SUM(monto)
  PRIMARY KEY (id_validacion_diaria),
  UNIQUE KEY uk_grano (id_tiempo, id_franja_horaria, id_ruta, id_sentido, id_paradero, id_tipo_pasaje),
  KEY idx_tiempo (id_tiempo),
  KEY idx_ruta (id_ruta),
  CONSTRAINT fk_fvd_tiempo   FOREIGN KEY (id_tiempo)         REFERENCES dim_tiempo(id_tiempo),
  CONSTRAINT fk_fvd_franja   FOREIGN KEY (id_franja_horaria) REFERENCES dim_franja_horaria(id_franja_horaria),
  CONSTRAINT fk_fvd_ruta     FOREIGN KEY (id_ruta)        REFERENCES dim_ruta(id_ruta),
  CONSTRAINT fk_fvd_sentido  FOREIGN KEY (id_sentido)     REFERENCES dim_sentido(id_sentido),
  CONSTRAINT fk_fvd_paradero FOREIGN KEY (id_paradero)    REFERENCES dim_paradero(id_paradero),
  CONSTRAINT fk_fvd_tipo     FOREIGN KEY (id_tipo_pasaje) REFERENCES dim_tipo_pasaje(id_tipo_pasaje)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------------
-- HECHO 2 — CARRERA (viaje)
--   Grano: 1 fila por carrera. Habilita "promedio pasajeros por viaje".
-- ---------------------------------------------------------------------------
CREATE TABLE fact_carrera (
  id_carrera     BIGINT        NOT NULL,          -- = `id carrera` de la OLTP (natural)
  id_tiempo      INT           NOT NULL,
  id_ruta        SMALLINT      NOT NULL,
  id_sentido     TINYINT       NOT NULL,
  num_pasajeros  INT           NOT NULL,          -- COUNT(*) de validaciones de la carrera
  monto_total    DECIMAL(12,2) NOT NULL,          -- SUM(monto) de la carrera
  PRIMARY KEY (id_carrera),
  KEY idx_tiempo (id_tiempo),
  KEY idx_ruta (id_ruta),
  CONSTRAINT fk_fc_tiempo  FOREIGN KEY (id_tiempo)  REFERENCES dim_tiempo(id_tiempo),
  CONSTRAINT fk_fc_ruta    FOREIGN KEY (id_ruta)    REFERENCES dim_ruta(id_ruta),
  CONSTRAINT fk_fc_sentido FOREIGN KEY (id_sentido) REFERENCES dim_sentido(id_sentido)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
