# Solución de Inteligencia de Negocios para el análisis de la demanda de pasajeros

Proyecto Final del curso **Inteligencia de Negocios (100000I62N)** - Universidad Tecnológica del Perú, Ciclo 1 2026.

Solución completa de BI sobre datos reales de validaciones de un operador de corredor complementario de transporte público urbano: modelo dimensional, proceso ETL, dashboard interactivo e interpretación automática de resultados mediante inteligencia artificial generativa.

**Autor:** Vega Carazas, Jonathan José (U20247082)
**Docente:** Pedro Hernán De La Cruz Velazco

---

## Cómo ejecutar el módulo de IA

No requiere instalar nada: PHP portátil y base de datos viajan dentro del repositorio.

### 1. Obtén una API Key de Google Gemini

Es gratuita y no pide tarjeta. En [ai.google.dev](https://ai.google.dev) → **Get API key** → **Create API key**. Copia la clave.

### 2. Configura tu clave

Copia el archivo `.env.example` y renómbralo a `.env`. Ábrelo y pega tu clave:

```
GEMINI_API_KEY=tu_clave_aqui
```

### 3. Ejecuta

Doble clic en **`INICIAR.bat`**. Se abre el navegador en `http://localhost:8010`.

**Credenciales de acceso:** usuario `gerente`, contraseña `demo2026`
(también aparecen en la propia pantalla de acceso)

Para detener el servidor, cierra la ventana de consola.

> **Si el análisis falla con error de cuota:** la capa gratuita no asigna la misma
> cuota a todos los modelos en todos los proyectos. El módulo reintenta
> automáticamente con modelos alternativos, pero puedes fijar otro en el `.env`
> mediante `GEMINI_MODELO`.

---

## Qué hace el módulo de IA

El usuario elige una ruta y un período. El módulo consulta el extracto del Data Mart, calcula los indicadores de ese corte y se los entrega al modelo, que devuelve un análisis ejecutivo en lenguaje natural con hallazgos, recomendaciones operativas y alertas.

**El modelo no calcula: interpreta.** Recibe los indicadores ya computados, de modo que las cifras del análisis coinciden siempre con las del dashboard. Puede comprobarse filtrando la misma ruta y período en ambos.

---

## Estructura del repositorio

```
├── ia/                  Módulo web de análisis con IA (PHP)
│   ├── index.php        Interfaz: indicadores y generación del análisis
│   ├── login.php        Pantalla de acceso
│   ├── api/             Endpoint que construye el contexto y llama a Gemini
│   └── lib/             Configuración, acceso a datos, autenticación, cliente de IA
├── data/                Extracto del Data Mart en SQLite (anonimizado)
├── db/                  Esquema del Data Mart y script de anonimización
├── etl/                 Proceso ETL en Python y exportador del extracto
├── powerbi/             Dashboard (.pbix) y medidas DAX
├── informe/             Informe escrito y figuras
├── ppt/                 Presentación de la exposición
├── php/                 PHP portátil (no se instala nada en el sistema)
└── INICIAR.bat          Lanzador del módulo
```

---

## Arquitectura

```
Sistema OLTP  →  ETL (Python)  →  Data Mart (MariaDB)  →  Power BI
                                          ↓
                                  Extracto SQLite  →  Módulo IA (PHP + Gemini)
```

**Modelo dimensional:** constelación de dos tablas de hechos con dimensiones conformadas.

- `fact_validacion_diaria` - grano: fecha × franja horaria × ruta × sentido × paradero × tipo de pasaje
- `fact_carrera` - grano: una fila por carrera, permite calcular el promedio de pasajeros por viaje sin doble conteo
- Dimensiones: tiempo, ruta, paradero, sentido, tipo de pasaje y franja horaria

**Stack:** MariaDB · Python (pandas) · Power BI · PHP · Google Gemini API · SQLite

---

## Sobre los datos

El conjunto publicado está **anonimizado**. Se aplicaron dos técnicas:

1. **Supresión de identificadores.** Los códigos de ruta, nombres de paradero y zonas del operador se sustituyeron por identificadores genéricos (`R-01`, `P-001`, `Zona Norte`). Se conserva la estructura, que es lo que el análisis requiere.

2. **Perturbación multiplicativa.** Las medidas de volumen se escalaron por un factor constante. Al escalar numerador y denominador con el mismo factor **los ratios se conservan**: participación por ruta, distribución por tipo de pasaje y concentración horaria quedan intactas. Solo las magnitudes absolutas dejan de corresponder a la organización real.

Adicionalmente se excluyeron del Data Mart la placa del vehículo y el identificador del conductor, por minimización de datos conforme a la Ley N° 29733 de Protección de Datos Personales.

**Cobertura de la fuente:** el sistema origen no registra todos los días del calendario (193 de 391) y la cobertura es casi exclusivamente de días laborables. El estudio se acota en consecuencia. El detalle está en la sección 6 del informe.

---

## Seguridad

- La **API Key** se lee de `.env`, que no se versiona, y se usa únicamente del lado del servidor. Nunca se envía al navegador.
- La **contraseña de acceso** no se almacena: solo su hash bcrypt. Se verifica con `password_verify`.
- El **endpoint de la API está protegido**: sin sesión responde 401, de modo que no puede consumirse la cuota llamándolo directamente.
- Todas las consultas usan **sentencias preparadas** con parámetros enlazados.
- Los volcados del sistema origen **no forman parte de este repositorio** y están excluidos por `.gitignore`.

---

## Regenerar el extracto de datos

Solo es necesario si se reconstruye el Data Mart. Requiere el Data Mart en MariaDB.

```bash
pip install -r etl/requirements.txt
python etl/exportar_kpis_sqlite.py
```
