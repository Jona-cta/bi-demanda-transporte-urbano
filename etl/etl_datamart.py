"""
============================================================================
 ETL — Data Mart de Validaciones  (Proyecto Final Inteligencia de Negocios, UTP)
============================================================================
 Extrae de la BD OLTP `transporte_oltp` (copia real de VALIDACIONES + catálogo
 2op_ruta), transforma (limpieza, normalización, dimensión de tiempo, derivación
 del tipo de pasaje por reglas de negocio time-aware) y carga el modelo estrella
 en `transporte_dm`.

 Estrategia:
   - DIMENSIONES  -> se construyen en pandas (datos pequeños) y se cargan.
   - HECHOS (14M) -> se agregan con push-down SQL (INSERT ... SELECT ... GROUP BY)
                     para no traer 14M filas a memoria. Ideal en rendimiento.

 Uso:
   python etl_datamart.py            # ETL completo
   python etl_datamart.py --check    # solo validaciones/KPIs sobre el Data Mart
============================================================================
"""
from __future__ import annotations
import argparse
import sys
import time
from pathlib import Path

# Consola Windows en UTF-8 (para →, tildes, S/ …)
for _s in (sys.stdout, sys.stderr):
    try:
        _s.reconfigure(encoding="utf-8")
    except Exception:
        pass

import pandas as pd
from dotenv import dotenv_values
from sqlalchemy import create_engine, text

# --------------------------------------------------------------------------
# Configuración (lee el .env de la raíz del proyecto)
# --------------------------------------------------------------------------
ROOT = Path(__file__).resolve().parent.parent
CFG = dotenv_values(ROOT / ".env")

DB_HOST = "127.0.0.1"
DB_PORT = CFG.get("DB_HOST_PORT", "3399")
DB_USER = "root"                              # root para el ETL (cross-db + DDL de staging)
DB_PASS = CFG.get("DB_ROOT_PASSWORD", "")
OLTP = CFG.get("DB_OLTP_NAME", "transporte_oltp")
DM = CFG.get("DB_DM_NAME", "transporte_dm")

ENGINE = create_engine(
    f"mysql+pymysql://{DB_USER}:{DB_PASS}@{DB_HOST}:{DB_PORT}/?charset=utf8mb4",
    pool_pre_ping=True,
)

# Umbral de clasificación: fracción de la tarifa adulto a partir de la cual se
# considera "Adulto" (pasaje completo). Debajo => "Medio".
UMBRAL_ADULTO = 0.75

MESES = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio",
         "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"]
DIAS = ["Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado", "Domingo"]


def log(stage: str, msg: str) -> None:
    print(f"[{time.strftime('%H:%M:%S')}] {stage:<9} | {msg}", flush=True)


# ==========================================================================
# EXTRACT
# ==========================================================================
def extract(conn):
    log("EXTRACT", "Leyendo catálogo de rutas, paraderos, sentidos y rango temporal…")

    rutas = pd.read_sql(text(f"SELECT id AS id_ruta, ruta AS codigo_ruta FROM `{OLTP}`.`2op_ruta`"), conn)

    # Paraderos con su zona predominante (paradero y zona son texto libre en la OLTP).
    # Data cleansing: estandariza a MAYÚSCULAS (casing inconsistente en el origen) y
    # descarta artefactos de Excel en zona (#REF!, #N/D, #¡REF!, …).
    paraderos = pd.read_sql(text(f"""
        SELECT UPPER(TRIM(paradero)) AS nombre_paradero,
               CASE WHEN zona IS NULL OR TRIM(zona)='' OR zona LIKE '#%%'
                    THEN 'SIN ZONA' ELSE UPPER(TRIM(zona)) END AS zona,
               COUNT(*) AS n
        FROM `{OLTP}`.VALIDACIONES
        GROUP BY UPPER(TRIM(paradero)),
                 CASE WHEN zona IS NULL OR TRIM(zona)='' OR zona LIKE '#%%'
                      THEN 'SIN ZONA' ELSE UPPER(TRIM(zona)) END
    """), conn)

    sentidos = pd.read_sql(text(f"""
        SELECT DISTINCT sentido AS codigo_origen
        FROM `{OLTP}`.VALIDACIONES WHERE sentido IS NOT NULL AND sentido <> ''
    """), conn)

    rango = pd.read_sql(text(f"SELECT MIN(fecha) AS d0, MAX(fecha) AS d1 FROM `{OLTP}`.VALIDACIONES"), conn)

    log("EXTRACT", f"{len(rutas)} rutas · {paraderos['nombre_paradero'].nunique()} paraderos · "
                   f"{len(sentidos)} sentidos · rango {rango.d0[0]} → {rango.d1[0]}")
    return rutas, paraderos, sentidos, rango.d0[0], rango.d1[0]


# ==========================================================================
# TRANSFORM
# ==========================================================================
def clasifica_tipo_ruta(codigo: str) -> str:
    c = (codigo or "").upper().strip()
    if "EXPRESO" in c:
        return "Expreso"
    if c.startswith("AUX"):
        return "Auxiliar"
    if c == "ESCOLAR":
        return "Escolar"
    if c in ("ND", ""):
        return "Otro"
    if any(ch.isdigit() for ch in c) and c.replace(" ", "").isdigit():
        return "Regular"
    return "Especial"


def clasifica_sentido(codigo: str) -> str:
    c = (codigo or "").upper().strip()
    if c in ("NS", "IDA", "EO"):
        return "Ida"
    if c in ("SN", "VUELTA", "OE"):
        return "Vuelta"
    return "Otro"


def clasifica_franja(h: int) -> tuple[str, int]:
    """Devuelve (franja, es_hora_punta) según la demanda real observada."""
    if h <= 4:
        return "Madrugada", 0
    if h == 5:
        return "Temprano", 0
    if 6 <= h <= 8:
        return "Punta Mañana", 1          # pico 6-8h (máx 7h)
    if 9 <= h <= 11:
        return "Media Mañana", 0
    if 12 <= h <= 14:
        return "Mediodía", 0
    if 15 <= h <= 16:
        return "Tarde", 0
    if 17 <= h <= 19:
        return "Punta Tarde", 1           # pico 17-19h
    return "Noche", 0                      # 20-23h


def transform(rutas, paraderos, sentidos, d0, d1):
    log("TRANSFORM", "Construyendo dimensiones…")

    # --- dim_tiempo: calendario completo del rango observado ---
    fechas = pd.date_range(d0, d1, freq="D")
    dim_tiempo = pd.DataFrame({"fecha": fechas})
    dim_tiempo["id_tiempo"] = dim_tiempo.fecha.dt.strftime("%Y%m%d").astype(int)
    dim_tiempo["anio"] = dim_tiempo.fecha.dt.year
    dim_tiempo["trimestre"] = dim_tiempo.fecha.dt.quarter
    dim_tiempo["mes"] = dim_tiempo.fecha.dt.month
    dim_tiempo["mes_nombre"] = dim_tiempo.mes.map(lambda m: MESES[m - 1])
    dim_tiempo["anio_mes"] = dim_tiempo.fecha.dt.strftime("%Y-%m")
    dim_tiempo["semana_anio"] = dim_tiempo.fecha.dt.isocalendar().week.astype(int)
    dim_tiempo["dia"] = dim_tiempo.fecha.dt.day
    dim_tiempo["dia_semana"] = dim_tiempo.fecha.dt.dayofweek + 1        # 1=Lunes
    dim_tiempo["dia_nombre"] = dim_tiempo.fecha.dt.dayofweek.map(lambda d: DIAS[d])
    dim_tiempo["es_fin_semana"] = (dim_tiempo.dia_semana >= 6).astype(int)
    dim_tiempo["fecha"] = dim_tiempo.fecha.dt.date

    # --- dim_ruta ---
    dim_ruta = rutas.copy()
    dim_ruta["tipo_ruta"] = dim_ruta.codigo_ruta.map(clasifica_tipo_ruta)

    # --- dim_paradero: una fila por paradero con su zona predominante ---
    par = paraderos.copy()
    par["nombre_paradero"] = par.nombre_paradero.replace("", None).fillna("SIN PARADERO")
    idx = par.groupby("nombre_paradero")["n"].idxmax()          # zona más frecuente
    dim_paradero = par.loc[idx, ["nombre_paradero", "zona"]].reset_index(drop=True)

    # --- dim_sentido ---
    dim_sentido = sentidos.copy()
    dim_sentido["sentido"] = dim_sentido.codigo_origen.map(clasifica_sentido)

    # --- dim_tipo_pasaje: catálogo derivado (fijo) ---
    dim_tipo_pasaje = pd.DataFrame([
        ("Adulto",   "Pasaje completo (tarifa plena adulto de la ruta/período)"),
        ("Medio",    "Medio pasaje (universitario/escolar; ~50% de la tarifa adulto)"),
        ("Gratuito", "Pase libre / gratuito (monto = 0.00)"),
        ("Sin dato", "Monto nulo en el origen; no clasificable"),
    ], columns=["tipo", "descripcion"])

    # --- dim_franja_horaria: 24 horas del día con su banda operativa ---
    filas_fr = []
    for h in range(24):
        franja, punta = clasifica_franja(h)
        filas_fr.append((h, f"{h:02d}:00", franja, punta))
    dim_franja = pd.DataFrame(filas_fr,
                              columns=["id_franja_horaria", "hora_texto", "franja", "es_hora_punta"])

    log("TRANSFORM", f"dim_tiempo={len(dim_tiempo)} · dim_ruta={len(dim_ruta)} · "
                     f"dim_paradero={len(dim_paradero)} · dim_sentido={len(dim_sentido)} · "
                     f"dim_tipo_pasaje={len(dim_tipo_pasaje)} · dim_franja={len(dim_franja)}")
    return dim_tiempo, dim_ruta, dim_paradero, dim_sentido, dim_tipo_pasaje, dim_franja


# ==========================================================================
# LOAD
# ==========================================================================
def cargar_dim(conn, df, tabla, cols):
    df[cols].to_sql(tabla, conn, schema=DM, if_exists="append", index=False)
    log("LOAD", f"{tabla}: {len(df)} filas")


def load_dims(conn, dims):
    dim_tiempo, dim_ruta, dim_paradero, dim_sentido, dim_tipo_pasaje, dim_franja = dims
    log("LOAD", "Vaciando Data Mart (FK checks off) y recargando dimensiones…")
    conn.execute(text("SET FOREIGN_KEY_CHECKS=0"))
    for t in ("fact_validacion_diaria", "fact_carrera", "dim_tiempo", "dim_ruta",
              "dim_paradero", "dim_sentido", "dim_tipo_pasaje", "dim_franja_horaria"):
        conn.execute(text(f"TRUNCATE TABLE `{DM}`.`{t}`"))

    cargar_dim(conn, dim_tiempo, "dim_tiempo",
               ["id_tiempo", "fecha", "anio", "trimestre", "mes", "mes_nombre",
                "anio_mes", "semana_anio", "dia", "dia_semana", "dia_nombre", "es_fin_semana"])
    cargar_dim(conn, dim_ruta, "dim_ruta", ["id_ruta", "codigo_ruta", "tipo_ruta"])
    cargar_dim(conn, dim_paradero, "dim_paradero", ["nombre_paradero", "zona"])
    cargar_dim(conn, dim_sentido, "dim_sentido", ["codigo_origen", "sentido"])
    cargar_dim(conn, dim_tipo_pasaje, "dim_tipo_pasaje", ["tipo", "descripcion"])
    cargar_dim(conn, dim_franja, "dim_franja_horaria",
               ["id_franja_horaria", "hora_texto", "franja", "es_hora_punta"])
    conn.execute(text("SET FOREIGN_KEY_CHECKS=1"))


def construir_tarifa_ref(conn):
    """Tabla de referencia: tarifa ADULTO (monto modal) por ruta y mes -> time-aware."""
    log("LOAD", "Calculando tarifa adulto por (ruta, mes) [staging]…")
    conn.execute(text(f"DROP TABLE IF EXISTS `{DM}`.stg_tarifa_ref"))
    conn.execute(text(f"""
        CREATE TABLE `{DM}`.stg_tarifa_ref (
            id_ruta SMALLINT NOT NULL,
            anio_mes CHAR(7) NOT NULL,
            tarifa_adulto DECIMAL(10,2) NOT NULL,
            PRIMARY KEY (id_ruta, anio_mes)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    """))
    conn.execute(text(f"""
        INSERT INTO `{DM}`.stg_tarifa_ref (id_ruta, anio_mes, tarifa_adulto)
        SELECT id_ruta, ym, monto FROM (
            SELECT id_ruta, DATE_FORMAT(fecha,'%Y-%m') ym, monto,
                   ROW_NUMBER() OVER (PARTITION BY id_ruta, DATE_FORMAT(fecha,'%Y-%m')
                                      ORDER BY COUNT(*) DESC) rk
            FROM `{OLTP}`.VALIDACIONES
            WHERE monto IS NOT NULL AND monto > 0
            GROUP BY id_ruta, ym, monto
        ) x WHERE rk = 1
    """))
    n = conn.execute(text(f"SELECT COUNT(*) FROM `{DM}`.stg_tarifa_ref")).scalar()
    log("LOAD", f"stg_tarifa_ref: {n} pares (ruta, mes)")


def load_fact_validacion(conn):
    log("LOAD", "Agregando fact_validacion_diaria (push-down SQL sobre 14M filas)…")
    t0 = time.time()
    conn.execute(text(f"""
        INSERT INTO `{DM}`.fact_validacion_diaria
              (id_tiempo, id_franja_horaria, id_ruta, id_sentido, id_paradero, id_tipo_pasaje,
               cant_validaciones, monto_total)
        SELECT
            CAST(DATE_FORMAT(v.fecha,'%Y%m%d') AS UNSIGNED) AS id_tiempo,
            HOUR(v.hora) AS id_franja_horaria,
            v.id_ruta,
            ds.id_sentido,
            dp.id_paradero,
            dtp.id_tipo_pasaje,
            COUNT(*)               AS cant_validaciones,
            COALESCE(SUM(v.monto),0) AS monto_total
        FROM `{OLTP}`.VALIDACIONES v
        JOIN `{DM}`.dim_ruta    dr ON dr.id_ruta = v.id_ruta
        JOIN `{DM}`.dim_sentido ds ON ds.codigo_origen = v.sentido
        JOIN `{DM}`.dim_paradero dp
             ON dp.nombre_paradero = CASE
                    WHEN v.paradero IS NULL OR TRIM(v.paradero)='' THEN 'SIN PARADERO'
                    ELSE UPPER(TRIM(v.paradero)) END
        LEFT JOIN `{DM}`.stg_tarifa_ref tr
             ON tr.id_ruta = v.id_ruta AND tr.anio_mes = DATE_FORMAT(v.fecha,'%Y-%m')
        JOIN `{DM}`.dim_tipo_pasaje dtp
             ON dtp.tipo = CASE
                    WHEN v.monto IS NULL              THEN 'Sin dato'
                    WHEN v.monto = 0                  THEN 'Gratuito'
                    WHEN tr.tarifa_adulto IS NULL     THEN 'Sin dato'
                    WHEN v.monto >= {UMBRAL_ADULTO} * tr.tarifa_adulto THEN 'Adulto'
                    ELSE 'Medio' END
        GROUP BY id_tiempo, HOUR(v.hora), v.id_ruta, ds.id_sentido, dp.id_paradero, dtp.id_tipo_pasaje
    """))
    n = conn.execute(text(f"SELECT COUNT(*) FROM `{DM}`.fact_validacion_diaria")).scalar()
    log("LOAD", f"fact_validacion_diaria: {n} filas  ({time.time()-t0:.1f}s)")


def load_fact_carrera(conn):
    log("LOAD", "Agregando fact_carrera (1 fila por carrera)…")
    t0 = time.time()
    conn.execute(text(f"""
        INSERT INTO `{DM}`.fact_carrera
              (id_carrera, id_tiempo, id_ruta, id_sentido, num_pasajeros, monto_total)
        SELECT
            v.`id carrera` AS id_carrera,
            CAST(DATE_FORMAT(MIN(v.fecha),'%Y%m%d') AS UNSIGNED) AS id_tiempo,
            MAX(v.id_ruta) AS id_ruta,
            MAX(ds.id_sentido) AS id_sentido,
            COUNT(*) AS num_pasajeros,
            COALESCE(SUM(v.monto),0) AS monto_total
        FROM `{OLTP}`.VALIDACIONES v
        JOIN `{DM}`.dim_sentido ds ON ds.codigo_origen = v.sentido
        JOIN `{DM}`.dim_ruta    dr ON dr.id_ruta = v.id_ruta
        WHERE v.`id carrera` IS NOT NULL
        GROUP BY v.`id carrera`
    """))
    n = conn.execute(text(f"SELECT COUNT(*) FROM `{DM}`.fact_carrera")).scalar()
    log("LOAD", f"fact_carrera: {n} filas  ({time.time()-t0:.1f}s)")


# ==========================================================================
# VALIDACIÓN / KPIs de control
# ==========================================================================
def check(conn):
    log("CHECK", "Conciliación y KPIs de control sobre el Data Mart:")
    q = lambda s: conn.execute(text(s)).scalar()

    src = q(f"SELECT COUNT(*) FROM `{OLTP}`.VALIDACIONES")
    fvd = q(f"SELECT COALESCE(SUM(cant_validaciones),0) FROM `{DM}`.fact_validacion_diaria")
    print(f"    · Validaciones OLTP origen : {src:,}")
    print(f"    · Suma cant_validaciones DM: {fvd:,}   (dif: {src-fvd:,})")

    ing = conn.execute(text(f"""
        SELECT r.codigo_ruta, SUM(f.monto_total) ingreso, SUM(f.cant_validaciones) n
        FROM `{DM}`.fact_validacion_diaria f JOIN `{DM}`.dim_ruta r ON r.id_ruta=f.id_ruta
        GROUP BY r.codigo_ruta ORDER BY ingreso DESC LIMIT 5""")).fetchall()
    print("    · Top 5 ingresos por ruta:")
    for row in ing:
        print(f"        {row[0]:<12} S/ {row[1]:>14,.2f}   ({row[2]:,} val.)")

    tipos = conn.execute(text(f"""
        SELECT t.tipo, SUM(f.cant_validaciones) n
        FROM `{DM}`.fact_validacion_diaria f JOIN `{DM}`.dim_tipo_pasaje t ON t.id_tipo_pasaje=f.id_tipo_pasaje
        GROUP BY t.tipo ORDER BY n DESC""")).fetchall()
    print("    · Distribución por tipo de pasaje:")
    tot = sum(r[1] for r in tipos)
    for row in tipos:
        print(f"        {row[0]:<10} {row[1]:>12,}   ({100*row[1]/tot:5.2f}%)")

    prom = q(f"""SELECT ROUND(AVG(num_pasajeros),2) FROM `{DM}`.fact_carrera""")
    print(f"    · Promedio pasajeros por viaje (carrera): {prom}")


# ==========================================================================
# MAIN
# ==========================================================================
def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--check", action="store_true", help="Solo validar/KPIs del Data Mart")
    args = ap.parse_args()

    with ENGINE.begin() as conn:
        if args.check:
            check(conn)
            return
        t0 = time.time()
        log("INICIO", f"ETL Data Mart · OLTP={OLTP} · DM={DM}")
        rutas, paraderos, sentidos, d0, d1 = extract(conn)
        dims = transform(rutas, paraderos, sentidos, d0, d1)
        load_dims(conn, dims)
        construir_tarifa_ref(conn)
        load_fact_validacion(conn)
        load_fact_carrera(conn)
        check(conn)
        log("FIN", f"ETL completado en {time.time()-t0:.1f}s")


if __name__ == "__main__":
    try:
        main()
    except Exception as e:
        log("ERROR", str(e))
        sys.exit(1)
