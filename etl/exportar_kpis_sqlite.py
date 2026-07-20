#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Exporta los KPIs del Data Mart (MariaDB) a un SQLite portatil.

Por que existe este paso
------------------------
El Data Mart vive en MariaDB (Docker) y pesa 1.6 M de filas de hechos. El modulo
de IA tiene que poder ejecutarse en CUALQUIER maquina - la del docente incluida -
sin instalar Docker ni MariaDB. Este script materializa los agregados que el
modulo de IA necesita en un unico archivo SQLite (< 5 MB) que viaja con el
proyecto.

No es una copia del Data Mart: es un EXTRACTO agregado. El Data Mart sigue
siendo la fuente de verdad; aqui solo se pre-calculan los cortes que alimentan
el analisis en lenguaje natural.

Uso:
    python exportar_kpis_sqlite.py
    python exportar_kpis_sqlite.py --salida ../data/kpi_datamart.sqlite
"""

import argparse
import os
import sqlite3
import sys
from decimal import Decimal

try:
    import mysql.connector
except ImportError:
    sys.exit("Falta mysql-connector-python. Instalar con: pip install -r requirements.txt")

# --- Conexion al Data Mart (se puede sobreescribir por variables de entorno) ---
DB = {
    "host": os.getenv("DM_HOST", "127.0.0.1"),
    "port": int(os.getenv("DM_PORT", "3399")),
    "user": os.getenv("DM_USER", "bi_user"),
    "password": os.getenv("DM_PASSWORD", "bi_pass_local_2026"),
    "database": os.getenv("DM_NAME", "transporte_dm"),
}

# ---------------------------------------------------------------------------
# Cada entrada: (tabla_destino, DDL en SQLite, SELECT contra el Data Mart)
# Todos los cortes se agregan por anio_mes para que el modulo de IA pueda
# filtrar por periodo sin arrastrar el grano diario.
# ---------------------------------------------------------------------------
EXPORTS = [
    (
        "kpi_mensual_ruta",
        # dias_con_dato es imprescindible: la fuente NO tiene cobertura diaria
        # continua (193 de 391 dias). Sin este contador, comparar totales
        # mensuales confunde falta de registro con caida de demanda.
        """CREATE TABLE kpi_mensual_ruta (
             anio_mes TEXT, codigo_ruta TEXT, tipo_ruta TEXT,
             validaciones INTEGER, ingreso REAL, dias_con_dato INTEGER)""",
        """SELECT t.anio_mes, r.codigo_ruta, r.tipo_ruta,
                  SUM(f.cant_validaciones), SUM(f.monto_total),
                  COUNT(DISTINCT f.id_tiempo)
             FROM fact_validacion_diaria f
             JOIN dim_tiempo t ON t.id_tiempo = f.id_tiempo
             JOIN dim_ruta   r ON r.id_ruta   = f.id_ruta
            GROUP BY t.anio_mes, r.codigo_ruta, r.tipo_ruta""",
    ),
    (
        "kpi_mensual_tipo_pasaje",
        """CREATE TABLE kpi_mensual_tipo_pasaje (
             anio_mes TEXT, codigo_ruta TEXT, tipo TEXT,
             validaciones INTEGER, ingreso REAL)""",
        """SELECT t.anio_mes, r.codigo_ruta, tp.tipo,
                  SUM(f.cant_validaciones), SUM(f.monto_total)
             FROM fact_validacion_diaria f
             JOIN dim_tiempo      t  ON t.id_tiempo      = f.id_tiempo
             JOIN dim_ruta        r  ON r.id_ruta        = f.id_ruta
             JOIN dim_tipo_pasaje tp ON tp.id_tipo_pasaje = f.id_tipo_pasaje
            GROUP BY t.anio_mes, r.codigo_ruta, tp.tipo""",
    ),
    (
        "kpi_mensual_hora",
        """CREATE TABLE kpi_mensual_hora (
             anio_mes TEXT, codigo_ruta TEXT, hora INTEGER, hora_texto TEXT,
             franja TEXT, es_hora_punta INTEGER,
             validaciones INTEGER, ingreso REAL)""",
        """SELECT t.anio_mes, r.codigo_ruta, fh.id_franja_horaria, fh.hora_texto,
                  fh.franja, fh.es_hora_punta,
                  SUM(f.cant_validaciones), SUM(f.monto_total)
             FROM fact_validacion_diaria f
             JOIN dim_tiempo         t  ON t.id_tiempo         = f.id_tiempo
             JOIN dim_ruta           r  ON r.id_ruta           = f.id_ruta
             JOIN dim_franja_horaria fh ON fh.id_franja_horaria = f.id_franja_horaria
            GROUP BY t.anio_mes, r.codigo_ruta, fh.id_franja_horaria,
                     fh.hora_texto, fh.franja, fh.es_hora_punta""",
    ),
    (
        "kpi_mensual_paradero",
        """CREATE TABLE kpi_mensual_paradero (
             anio_mes TEXT, codigo_ruta TEXT, paradero TEXT, zona TEXT,
             validaciones INTEGER, ingreso REAL)""",
        """SELECT t.anio_mes, r.codigo_ruta, p.nombre_paradero, p.zona,
                  SUM(f.cant_validaciones), SUM(f.monto_total)
             FROM fact_validacion_diaria f
             JOIN dim_tiempo   t ON t.id_tiempo   = f.id_tiempo
             JOIN dim_ruta     r ON r.id_ruta     = f.id_ruta
             JOIN dim_paradero p ON p.id_paradero = f.id_paradero
            GROUP BY t.anio_mes, r.codigo_ruta, p.nombre_paradero, p.zona""",
    ),
    (
        "kpi_mensual_dia_semana",
        """CREATE TABLE kpi_mensual_dia_semana (
             anio_mes TEXT, codigo_ruta TEXT, dia_semana INTEGER, dia_nombre TEXT,
             es_fin_semana INTEGER, validaciones INTEGER, ingreso REAL)""",
        """SELECT t.anio_mes, r.codigo_ruta, t.dia_semana, t.dia_nombre,
                  t.es_fin_semana,
                  SUM(f.cant_validaciones), SUM(f.monto_total)
             FROM fact_validacion_diaria f
             JOIN dim_tiempo t ON t.id_tiempo = f.id_tiempo
             JOIN dim_ruta   r ON r.id_ruta   = f.id_ruta
            GROUP BY t.anio_mes, r.codigo_ruta, t.dia_semana, t.dia_nombre,
                     t.es_fin_semana""",
    ),
    (
        # Segundo hecho: da el KPI "promedio de pasajeros por viaje" sin doble conteo.
        "kpi_mensual_carrera",
        """CREATE TABLE kpi_mensual_carrera (
             anio_mes TEXT, codigo_ruta TEXT,
             carreras INTEGER, pasajeros INTEGER, ingreso REAL)""",
        """SELECT t.anio_mes, r.codigo_ruta,
                  COUNT(*), SUM(c.num_pasajeros), SUM(c.monto_total)
             FROM fact_carrera c
             JOIN dim_tiempo t ON t.id_tiempo = c.id_tiempo
             JOIN dim_ruta   r ON r.id_ruta   = c.id_ruta
            GROUP BY t.anio_mes, r.codigo_ruta""",
    ),
]

INDICES = [
    "CREATE INDEX ix_ruta_mes  ON kpi_mensual_ruta (anio_mes, codigo_ruta)",
    "CREATE INDEX ix_tipo_mes  ON kpi_mensual_tipo_pasaje (anio_mes, codigo_ruta)",
    "CREATE INDEX ix_hora_mes  ON kpi_mensual_hora (anio_mes, codigo_ruta)",
    "CREATE INDEX ix_par_mes   ON kpi_mensual_paradero (anio_mes, codigo_ruta)",
    "CREATE INDEX ix_dia_mes   ON kpi_mensual_dia_semana (anio_mes, codigo_ruta)",
    "CREATE INDEX ix_carr_mes  ON kpi_mensual_carrera (anio_mes, codigo_ruta)",
]


def a_tipos_sqlite(filas):
    """MariaDB devuelve DECIMAL como Decimal, que sqlite3 no sabe enlazar.
    Los montos se convierten a float (los agregados ya estan redondeados a 2)."""
    return [
        tuple(float(v) if isinstance(v, Decimal) else v for v in fila)
        for fila in filas
    ]


def main():
    ap = argparse.ArgumentParser(description="Exporta KPIs del Data Mart a SQLite")
    ap.add_argument("--salida", default=os.path.join(
        os.path.dirname(os.path.abspath(__file__)), "..", "data", "kpi_datamart.sqlite"))
    args = ap.parse_args()

    salida = os.path.abspath(args.salida)
    os.makedirs(os.path.dirname(salida), exist_ok=True)
    if os.path.exists(salida):
        os.remove(salida)  # se regenera completo: el extracto no se actualiza incremental

    print(f"Data Mart : {DB['user']}@{DB['host']}:{DB['port']}/{DB['database']}")
    print(f"Salida    : {salida}\n")

    origen = mysql.connector.connect(**DB)
    destino = sqlite3.connect(salida)

    total_filas = 0
    try:
        cur_o = origen.cursor()
        for tabla, ddl, consulta in EXPORTS:
            destino.execute(ddl)
            cur_o.execute(consulta)
            filas = a_tipos_sqlite(cur_o.fetchall())
            marcadores = ",".join("?" * len(filas[0])) if filas else ""
            if filas:
                destino.executemany(
                    f"INSERT INTO {tabla} VALUES ({marcadores})", filas)
            destino.commit()
            total_filas += len(filas)
            print(f"  {tabla:<26} {len(filas):>7,} filas")

        for idx in INDICES:
            destino.execute(idx)

        # Metadatos: el modulo de IA los usa para acotar los filtros y para
        # declarar en pantalla que los datos estan anonimizados.
        destino.execute("""CREATE TABLE meta (clave TEXT PRIMARY KEY, valor TEXT)""")
        cur_o.execute("""SELECT MIN(anio_mes), MAX(anio_mes) FROM dim_tiempo""")
        desde, hasta = cur_o.fetchone()
        cur_o.execute("""SELECT COUNT(DISTINCT id_tiempo) FROM fact_validacion_diaria""")
        dias_con_dato = cur_o.fetchone()[0]
        cur_o.execute("""SELECT COUNT(*) FROM dim_tiempo""")
        dias_calendario = cur_o.fetchone()[0]
        destino.executemany("INSERT INTO meta VALUES (?,?)", [
            ("periodo_desde", desde),
            ("periodo_hasta", hasta),
            ("dias_con_dato", str(dias_con_dato)),
            ("dias_calendario", str(dias_calendario)),
            ("cobertura_parcial",
             "La fuente no registra todos los dias del calendario. Comparar "
             "totales mensuales sin normalizar por dias con dato induce a error."),
            ("anonimizado", "1"),
            ("nota_anonimizacion",
             "Codigos de ruta, paradero y zona sustituidos; medidas de volumen "
             "perturbadas por un factor constante. Los ratios se conservan."),
        ])
        destino.commit()
        destino.execute("VACUUM")

        mb = os.path.getsize(salida) / (1024 * 1024)
        print(f"\nOK - {total_filas:,} filas en total  ({mb:.2f} MB)")
        print(f"Periodo disponible: {desde} a {hasta}")
    finally:
        destino.close()
        origen.close()


if __name__ == "__main__":
    main()
