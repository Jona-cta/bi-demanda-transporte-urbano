-- ============================================================================
--  ANONIMIZACION DEL DATA MART - metodo aplicado
--  Proyecto Final Inteligencia de Negocios - UTP
--
--  ADVERTENCIA IMPORTANTE
--  Este archivo documenta el METODO, no la correspondencia concreta.
--
--  El script real de anonimizacion contiene la tabla de equivalencias entre
--  los identificadores originales del operador y los genericos publicados.
--  Esa tabla es, en la practica, la LLAVE para revertir la anonimizacion:
--  quien la tuviera podria reconstruir los nombres reales de rutas, paraderos
--  y zonas. Por eso no se versiona ni se distribuye.
--
--  Publicar el metodo permite auditar y reproducir la tecnica; publicar la
--  correspondencia anularia la proteccion. Es la misma logica por la que se
--  publica un algoritmo de cifrado pero no la clave.
-- ============================================================================

USE transporte_dm;

-- ---------------------------------------------------------------------------
-- TECNICA 1 - SUPRESION DE IDENTIFICADORES
--
-- Los identificadores del operador se sustituyen por codigos genericos
-- correlativos. Se conserva la ESTRUCTURA, que es lo que el analisis necesita:
-- el tipo de ruta (Regular, Expreso, Auxiliar, Escolar), la agrupacion de
-- paraderos por zona y la cardinalidad de cada catalogo.
--
-- Rutas:     codigo original -> 'R-01', 'R-02', ... conservando el prefijo o
--            sufijo que indica el tipo (AUX, EXPRESO).
-- Paraderos: nombre original -> 'P-001' .. 'P-170', en orden estable por id.
-- Zonas:     denominacion original -> 'Zona Norte' / 'Zona Centro' /
--            'Zona Sur' / 'Zona Este' / 'Zona Oeste' / 'Sin zona'.
--
-- La forma general de la sustitucion, sin los valores reales:
-- ---------------------------------------------------------------------------

-- Paraderos: renombrado correlativo (esta parte no depende de los nombres
-- originales, por lo que se muestra completa).
UPDATE dim_paradero p
JOIN (
  SELECT id_paradero,
         CONCAT('P-', LPAD(ROW_NUMBER() OVER (ORDER BY id_paradero), 3, '0')) AS nuevo
  FROM dim_paradero
) x ON x.id_paradero = p.id_paradero
SET p.nombre_paradero = x.nuevo;

-- Rutas: se extrae la parte numerica del codigo original y se sustituye por un
-- correlativo, preservando los calificativos del codigo.
--
--   UPDATE dim_ruta d
--   JOIN <mapa_de_rutas> m ON m.original = REGEXP_SUBSTR(d.codigo_ruta,'[0-9]+')
--   SET d.codigo_ruta = REPLACE(d.codigo_ruta, m.original, m.generico);
--
-- Zonas: reasignacion de cada denominacion original a una de las seis
-- categorias genericas, conservando la particion geografica.
--
--   UPDATE dim_paradero SET zona = CASE
--     WHEN zona IN (<denominaciones del sector norte>)  THEN 'Zona Norte'
--     ...
--   END;

-- ---------------------------------------------------------------------------
-- TECNICA 2 - PERTURBACION MULTIPLICATIVA
--
-- Las medidas de volumen se escalan por un factor constante.
--
-- Propiedad que justifica esta eleccion: al multiplicar numerador y
-- denominador por el mismo factor, TODOS LOS RATIOS SE CONSERVAN. La
-- participacion de cada ruta en el ingreso, la distribucion por tipo de
-- pasaje, la concentracion en hora punta y el ticket promedio quedan
-- exactamente iguales. Solo las magnitudes absolutas dejan de corresponder a
-- la organizacion real.
--
-- Por eso las conclusiones del estudio siguen siendo validas sobre el conjunto
-- publicado: el analisis se apoya en proporciones, no en totales absolutos.
--
-- El factor concreto no se publica, por el mismo motivo que el mapa de
-- equivalencias: conocerlo permitiria recuperar las cifras originales.
-- ---------------------------------------------------------------------------

-- SET @factor = <no se publica>;
--
-- UPDATE fact_validacion_diaria
-- SET cant_validaciones = GREATEST(1, ROUND(cant_validaciones * @factor)),
--     monto_total       = ROUND(monto_total * @factor, 2);
--
-- UPDATE fact_carrera
-- SET num_pasajeros = GREATEST(1, ROUND(num_pasajeros * @factor)),
--     monto_total   = ROUND(monto_total * @factor, 2);

-- Ambos hechos se escalan con el MISMO factor para que sigan conciliando entre
-- si: la suma de pasajeros de fact_carrera mantiene su relacion con la suma de
-- validaciones de fact_validacion_diaria.

-- ---------------------------------------------------------------------------
-- QUE NO SE MODIFICA Y POR QUE
--
-- Fechas:          no identifican a ninguna persona ni organizacion, y
--                  conservarlas mantiene la estacionalidad y la trazabilidad
--                  de la cobertura de la fuente.
-- stg_tarifa_ref:  la tarifa de referencia se mantiene en su valor real, para
--                  que siga siendo coherente con el ticket promedio, que la
--                  perturbacion conserva.
-- Cardinalidades:  el numero de rutas, paraderos y zonas se conserva: son
--                  conteos de catalogo que por si solos no identifican al
--                  operador.
-- ---------------------------------------------------------------------------
