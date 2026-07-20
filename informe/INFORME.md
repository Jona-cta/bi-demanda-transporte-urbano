# UNIVERSIDAD TECNOLÓGICA DEL PERÚ

## FACULTAD DE INGENIERÍA

---

### PROYECTO FINAL

# SOLUCIÓN DE INTELIGENCIA DE NEGOCIOS PARA EL ANÁLISIS DE LA DEMANDA DE PASAJEROS EN UN OPERADOR DE CORREDOR COMPLEMENTARIO DE TRANSPORTE PÚBLICO URBANO

**Data Mart, dashboard interactivo e interpretación automatizada con inteligencia artificial generativa**

---

**Curso:** Inteligencia de Negocios (100000I62N)

**Docente:** Pedro Hernán De La Cruz Velazco

**Ciclo:** 1 - 2026

**Modalidad del proyecto:** Individual (autorizado por el docente)

---

**Autor y roles asumidos:**

| Apellidos y nombres | Código | Roles desempeñados |
|---|---|---|
| Vega Carazas, Jonathan José | U20247082 | Líder de Proyecto · Analista de Datos · Ingeniero ETL · Diseñador de Dashboards · Especialista IA/API · Documentador |

---

**Lima, Perú - 2026**

---
---

## RESUMEN EJECUTIVO

El presente proyecto desarrolla una solución integral de Inteligencia de Negocios para un operador de corredor complementario de transporte público urbano en una ciudad peruana, en adelante "el operador". La organización enfrentaba un problema concreto: no contaba con una visión histórica consolidada de la demanda de pasajeros por ruta, franja horaria y paradero, ya que la información residía dispersa en reportes semanales generados por el sistema embarcado de validación. Esta fragmentación limitaba la planificación de frecuencias y la asignación eficiente de flota.

La solución consolidó las validaciones del sistema OLTP en un Data Mart dimensional implementado en MariaDB 11.4 sobre Docker, bajo un esquema de constelación con dos tablas de hechos y seis dimensiones conformadas. El proceso ETL, desarrollado en Python con pandas y estrategia híbrida de push-down SQL, procesó el histórico completo del período febrero 2025 a febrero 2026 y fue validado mediante conciliación de totales contra el origen, con diferencia cero. Los resultados se expusieron en un dashboard interactivo en Power BI con medidas DAX, y se incorporó un módulo web en PHP que consume la API de Google Gemini para generar interpretaciones ejecutivas en lenguaje natural a partir de los indicadores del corte seleccionado.

El análisis sobre 11,737,931 validaciones y S/ 23,706,412.98 de ingreso reveló tres hallazgos estructurales: una concentración del 90.7% del ingreso en solo cuatro de las veintidós rutas, lo que constituye un riesgo operativo y comercial relevante; una concentración del 45.67% de la demanda en horas punta, con picos a las 7h y entre 17h y 19h; y un ticket promedio de S/ 2.02, inferior a la tarifa de adulto de referencia debido a la participación de pasajes gratuitos y de medio pasaje. El perfilado detectó además que la fuente presenta cobertura temporal parcial, con registro en 193 de los 391 días del período, por lo que el estudio no formula conclusiones sobre estacionalidad ni sobre evolución mensual.

El proyecto aplicó minimización y anonimización de datos conforme a la Ley N° 29733 de Protección de Datos Personales.

*(Extensión: 300 palabras)*

---
---

## ÍNDICE

1. Introducción
2. Marco Teórico
3. Análisis de Requerimientos
4. Arquitectura de la Solución
5. Modelado Dimensional
6. Proceso ETL
7. Dashboard e Informes
8. Integración de IA y API Key
9. Análisis de Resultados
10. Consideraciones Éticas
11. Conclusiones
12. Referencias Bibliográficas
- Anexos

---
---

# 1. INTRODUCCIÓN

## 1.1. Contexto general y organización

En las principales ciudades peruanas millones de desplazamientos diarios dependen de buses operados bajo concesión. Los corredores complementarios operan con flota estandarizada, paraderos fijos y validación electrónica embarcada: cada validación registra fecha, hora, ruta, sentido, paradero, unidad, conductor y monto, de modo que el operador dispone de un censo continuo y exhaustivo de su demanda, no de una muestra. La paradoja que motiva este proyecto es que disponer del dato no equivale a disponer de la información: el registro existe, pero permanece encapsulado en un sistema transaccional diseñado para liquidar recaudación, no para analizar comportamiento.

La organización objeto de estudio, referida como "el operador", presta servicio en un corredor complementario de una ciudad peruana; por razones de confidencialidad comercial y de protección de datos, desarrolladas en la sección 10, no se identifica la ciudad, la empresa ni el corredor. Administra veintidós rutas activas, ciento setenta paraderos, veintiséis zonas en la nomenclatura original, ciento sesenta unidades de flota y trescientos treinta y cinco conductores, y durante el período analizado ejecutó 310,678 carreras, entendiendo por carrera un recorrido completo de una unidad en un sentido determinado.

## 1.2. Planteamiento del problema

**El operador no dispone de una visión histórica consolidada de la demanda de pasajeros desagregada por ruta, franja horaria y paradero.** El problema se manifiesta en tres planos. La **fragmentación de la información**: los datos se consumen mediante reportes semanales, cada uno una fotografía aislada de siete días, sin repositorio que permita comparar meses, identificar tendencias o detectar el efecto de un cambio operativo. La **orientación transaccional del dato**: el sistema fuente responde preguntas de liquidación, no preguntas analíticas sobre en qué franja reforzar frecuencias o qué paraderos concentran abordajes. Y las **decisiones sin sustento cuantitativo**: la programación de frecuencias y la asignación de flota, las de mayor impacto económico de la operación, descansan en la experiencia del personal, insumo valioso pero no auditable ni transferible.

## 1.3. Justificación

En el plano **operativo**, ajustar frecuencias según la distribución real de la demanda por franja permite reducir el intervalo entre unidades en los momentos de mayor afluencia y ampliarlo en los valle, mejorando a la vez la experiencia del usuario y la eficiencia del uso de flota. En el plano **económico**, identificar la contribución de cada ruta al ingreso permite dimensionar riesgos y priorizar inversión; como demuestra la sección 9, esa estructura presenta una concentración que no era visible sin consolidación histórica y que compromete la sostenibilidad del operador. En el plano **académico**, el proyecto aplica de forma integrada el ciclo completo de una solución de Inteligencia de Negocios sobre datos auténticos del contexto peruano.

## 1.4. Objetivos

**Objetivo general.** Diseñar e implementar una solución de Inteligencia de Negocios que consolide el histórico de validaciones del operador en un Data Mart dimensional, exponga los indicadores clave de demanda e ingreso en un dashboard interactivo, y automatice la interpretación analítica de los resultados mediante inteligencia artificial generativa consumida vía API.

**OE1.** Consolidar el histórico completo de validaciones del período febrero 2025 a febrero 2026 en un Data Mart implementado en MariaDB bajo modelo dimensional de constelación, mediante un proceso ETL desarrollado en Python, verificando la integridad de la carga por conciliación de totales contra el origen con diferencia cero.

**OE2.** Construir un dashboard interactivo en Power BI que exponga como mínimo cuatro indicadores clave de desempeño (total de validaciones, ingreso por ruta, promedio de pasajeros por viaje y distribución por tipo de pasaje), con filtros por ruta y período y jerarquía temporal navegable.

**OE3.** Integrar la API de Google Gemini en un módulo web desarrollado en PHP, que genere de forma automática un análisis ejecutivo en lenguaje natural a partir de los indicadores del corte seleccionado, gestionando la credencial bajo prácticas seguras de manejo de secretos.

**OE4.** Aplicar al conjunto de datos las medidas de minimización y anonimización necesarias para su uso académico conforme a la Ley N° 29733 de Protección de Datos Personales, preservando la validez analítica de los resultados.

## 1.5. Alcance y estructura del documento

El alcance comprende el análisis de la demanda de pasajeros medida a través de validaciones y del ingreso asociado, sobre un horizonte de trece meses. Quedan fuera los costos operativos, el consumo de combustible, el desempeño individual de conductores (excluido deliberadamente por razones éticas expuestas en la sección 10), el mantenimiento de flota y la georreferenciación de recorridos. El perfilado de la fuente reveló además dos delimitaciones que acotan el alcance efectivo del estudio y que se documentan en la sección 6.6: la cobertura temporal es parcial y corresponde casi exclusivamente a días laborables. Las limitaciones metodológicas se detallan en la sección 10.5.

El documento se organiza en doce secciones: marco teórico (2) y requerimientos (3); arquitectura, modelo dimensional y proceso ETL (4, 5 y 6); dashboard (7), integración de IA (8) y análisis de resultados (9); consideraciones éticas (10), conclusiones (11) y referencias (12). Los anexos reúnen el diccionario de datos, las medidas DAX, el código del módulo de IA y el material de detalle que respalda al cuerpo del informe.

---
---

# 2. MARCO TEÓRICO

## 2.1. Inteligencia de Negocios

La Inteligencia de Negocios es el conjunto de arquitecturas, herramientas, bases de datos, métodos analíticos y metodologías que habilitan el acceso interactivo a los datos y proporcionan a los gestores capacidad de análisis para la toma de decisiones (Sharda, Delen y Turban, 2020). El énfasis no recae en la tecnología sino en su finalidad: transformar datos operativos en conocimiento accionable. Un proyecto que produce reportes pero no altera decisiones ha fallado, con independencia de su sofisticación técnica.

## 2.2. Sistemas OLTP y sistemas OLAP

La distinción entre procesamiento transaccional en línea y procesamiento analítico en línea constituye el fundamento arquitectónico de toda solución de Inteligencia de Negocios.

**Tabla 1. Comparación entre sistemas OLTP y sistemas OLAP**

| Criterio | Sistema OLTP | Sistema OLAP |
|---|---|---|
| Propósito | Sostener la operación diaria | Analizar el comportamiento histórico |
| Unidad de trabajo | Transacción individual | Consulta agregada sobre grandes volúmenes |
| Diseño de datos | Normalizado (3FN) | Desnormalizado, orientado a lectura |
| Operaciones dominantes | INSERT, UPDATE, DELETE | SELECT con agregación |
| Horizonte y volumen | Datos actuales, pocas filas | Datos históricos, millones de filas |
| Usuario típico | Operador, sistema embarcado | Analista, gerente, planificador |
| En este proyecto | Tabla `VALIDACIONES` | Data Mart `transporte_dm` |

Ejecutar análisis históricos sobre el sistema OLTP degrada el rendimiento del sistema que sostiene la operación en tiempo real y obliga a reconstruir en cada consulta la lógica de negocio que debería estar materializada de forma estable. La separación de ambos entornos no es una preferencia de diseño sino una necesidad operativa.

## 2.3. Data Warehouse y Data Mart

Inmon (2005) define el Data Warehouse como una colección de datos orientada a temas, integrada, no volátil y variante en el tiempo, destinada a apoyar la toma de decisiones gerenciales. El **Data Mart** es un subconjunto orientado a un área funcional específica, de menor tiempo de implementación y mayor cercanía a las necesidades de un grupo de usuarios. Kimball y Ross (2013) proponen una estrategia ascendente en la que el Data Warehouse corporativo emerge como unión de Data Marts construidos sobre dimensiones conformadas. Este proyecto implementa un Data Mart del proceso "validación de pasaje", con dimensiones diseñadas como conformadas para permitir su reutilización futura: un eventual Data Mart de costos operativos podría compartir `dim_tiempo` y `dim_ruta` sin reconstruirlas.

## 2.4. Modelado dimensional

El modelado dimensional es una técnica de diseño lógico orientada a presentar los datos en un marco estándar, intuitivo y de alto rendimiento para consultas analíticas (Kimball y Ross, 2013). Se estructura sobre **tablas de hechos**, que almacenan las medidas numéricas del proceso y las claves foráneas hacia las dimensiones, y **tablas de dimensión**, que almacenan los atributos descriptivos con los que el usuario filtra, agrupa y etiqueta.

La **granularidad** es el nivel de detalle representado por una fila de la tabla de hechos. Su declaración explícita constituye el paso más importante del diseño, anterior incluso a la selección de dimensiones y medidas, porque toda decisión posterior depende de él. La elección implica un compromiso permanente entre detalle y volumen: un grano atómico ofrece máxima flexibilidad analítica al costo de un volumen elevado, mientras que un grano agregado reduce el volumen pero cierra de manera irreversible la posibilidad de analizar por debajo de ese nivel.

En cuanto a la topología del modelo, el **esquema estrella** dispone una tabla de hechos central rodeada de dimensiones desnormalizadas y constituye el esquema de referencia por su simplicidad y su rendimiento al minimizar uniones. El **esquema copo de nieve** normaliza las dimensiones en tablas jerárquicas adicionales, reduciendo redundancia a costa de incrementar uniones y complejidad, por lo que la literatura dominante lo desaconseja salvo casos específicos. El **esquema de constelación**, adoptado en este proyecto, dispone múltiples tablas de hechos que comparten **dimensiones conformadas**, esto es, dimensiones utilizadas de manera idéntica en más de una tabla de hechos, con las mismas claves, atributos y significado. Su valor radica en que habilitan el análisis cruzado entre procesos, técnica conocida como *drill across*; sin ellas, dos tablas de hechos son dos islas informativas incomunicables.

Por último, las medidas se clasifican según su comportamiento frente a la agregación en aditivas, semiaditivas y no aditivas. Las **no aditivas**, categoría a la que pertenecen ratios y porcentajes, deben calcularse siempre como cociente de agregados y nunca como agregado de cocientes. Este punto tiene consecuencia directa sobre el diseño de las medidas DAX del dashboard: el ticket promedio se calcula como suma de montos dividida entre suma de validaciones, y jamás como promedio de tickets individuales.

Las operaciones analíticas estándar sobre el cubo de datos (*roll-up*, *drill-down*, *slice*, *dice* y *pivot*), así como los criterios de correspondencia entre tipo de análisis y visualización recomendada, se desarrollan en el **Anexo E**, y su materialización concreta en la solución se documenta en la sección 7.

## 2.5. Indicadores clave de desempeño

Un indicador clave de desempeño (KPI) es una métrica que refleja el grado de cumplimiento de un factor crítico de éxito del negocio. Parmenter (2015) advierte contra la proliferación indiscriminada de indicadores y propone criterios de selección: un KPI debe ser medible con la información disponible, ser accionable, tener un responsable identificable y estar vinculado a un objetivo estratégico. Un indicador que se observa pero no modifica ninguna decisión no es un KPI sino una curiosidad estadística. Bajo este criterio se seleccionaron los seis indicadores del proyecto, cuya vinculación con decisiones concretas se detalla en la sección 3.4.

## 2.6. Visualización de datos

Few (2013) define el dashboard como una presentación visual de la información más importante, consolidada y organizada en una sola pantalla, de modo que pueda ser monitoreada de un vistazo. Los tres elementos de la definición son restrictivos: *información más importante* excluye el relleno, *una sola pantalla* excluye el desplazamiento y *de un vistazo* excluye la interpretación laboriosa. Tufte (2001) aporta el principio de la razón datos-tinta: todo elemento decorativo que no codifique información compite por la atención del lector y degrada la comunicación. De aquí se derivan las reglas prácticas aplicadas en el dashboard de este proyecto, esto es, ausencia de efectos tridimensionales, sombras y degradados, líneas de cuadrícula tenues y uso del color como codificación semántica y no ornamental.

## 2.7. Inteligencia artificial generativa aplicada al análisis de datos

Los grandes modelos de lenguaje son redes neuronales basadas en la arquitectura *transformer* propuesta por Vaswani et al. (2017), entrenadas sobre corpus masivos de texto para predecir la continuación más probable de una secuencia. Brown et al. (2020) demostraron que, alcanzada cierta escala, exhiben capacidad de aprendizaje en contexto: resuelven tareas nuevas a partir de instrucciones incluidas en la propia entrada, sin reentrenamiento.

Esta propiedad habilita un caso de uso específico y valioso en Inteligencia de Negocios, la **generación de narrativa analítica**: un dashboard comunica de manera eficiente qué ocurrió, pero exige del lector la competencia para interpretar lo que observa, mientras que un modelo generativo alimentado con los indicadores del período puede producir la lectura interpretada. La técnica empleada en este proyecto corresponde a la **generación aumentada por recuperación**: el modelo no consulta la base de datos ni ejecuta cálculos, sino que recibe en el prompt un contexto cuantitativo previamente calculado y verificado sobre el Data Mart. Esta arquitectura es deliberada y responde a la principal limitación conocida de los modelos generativos, la **alucinación**, entendida como la generación de contenido fluido pero factualmente incorrecto o no fundamentado en la fuente (Ji et al., 2023).

## 2.8. Seguridad en el consumo de APIs

El acceso a servicios de inteligencia artificial en la nube se autentica habitualmente mediante una **API Key**, una cadena secreta cuyo compromiso permite a un tercero consumir la cuota del titular, generar costos a su cargo y, en escenarios de mayor gravedad, acceder a servicios asociados a la misma credencial. El principio de seguridad aplicable es inequívoco: **una credencial jamás debe residir en código que se ejecuta en el cliente ni en un repositorio de código fuente**, puesto que todo lo que llega al navegador es inspeccionable por el usuario, incluido el JavaScript ofuscado. La práctica correcta consiste en mantener la credencial exclusivamente en el servidor, inyectada como variable de entorno, y en versionar únicamente una plantilla de configuración sin valores reales. Su implementación en el proyecto se detalla en la sección 8.3.

---
---

# 3. ANÁLISIS DE REQUERIMIENTOS

## 3.1. Descripción del proceso de negocio

El proceso de negocio modelado es la **validación de pasaje**. Una unidad de flota inicia una **carrera**, entendida como un recorrido completo sobre una ruta en un sentido determinado, y se detiene en los paraderos establecidos; en cada detención los pasajeros que abordan validan su pasaje contra el equipo embarcado, generando un registro transaccional que consigna fecha, hora, unidad, conductor, ruta, sentido, paradero, orden de la parada, zona, identificador de la carrera y monto cobrado.

De esta descripción se derivan dos observaciones de diseño relevantes. **Primera:** existen dos granos naturales en el proceso, el abordaje individual y la carrera completa, ambos legítimos y correspondientes a preguntas de negocio distintas, observación que fundamenta el diseño de constelación adoptado. **Segunda:** el monto cobrado es el único indicio disponible sobre la condición del pasajero, ya que el sistema no registra si quien aborda es adulto, escolar, universitario o beneficiario de gratuidad, sino únicamente cuánto pagó; esta restricción determina el diseño de la dimensión de tipo de pasaje descrito en la sección 5.5.

## 3.2. Matriz de procesos de negocio

Kimball y Ross (2013) proponen la matriz de bus como herramienta de planificación de la arquitectura dimensional: filas para los procesos de negocio, columnas para las dimensiones, y marcas en las intersecciones donde el proceso utiliza la dimensión. La matriz revela qué dimensiones deben conformarse.

**Tabla 2. Matriz de bus del Data Mart de validaciones**

| Proceso de negocio | dim_tiempo | dim_franja_horaria | dim_ruta | dim_sentido | dim_paradero | dim_tipo_pasaje |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| Validación de pasaje (`fact_validacion_diaria`) | X | X | X | X | X | X |
| Ejecución de carrera (`fact_carrera`) | X | | X | X | | |
| *Costos operativos (futuro, fuera de alcance)* | *X* | | *X* | | | |
| *Disponibilidad de flota (futuro, fuera de alcance)* | *X* | | *X* | | | |

La matriz evidencia que `dim_tiempo` y `dim_ruta` son las dimensiones de mayor conformidad, al participar en ambos procesos implementados y en los procesos futuros identificados. Su diseño se realizó en consecuencia, con atributos suficientemente generales para servir a procesos distintos del de validación.

## 3.3. Identificación de interesados

Se identificaron seis interesados internos con necesidades de información diferenciadas: la Gerencia General, orientada a la sostenibilidad de la estructura de ingresos; las jefaturas de Operaciones y de Flota, que deciden frecuencias y asignación de unidades sobre base semanal; el Área de Planeamiento, interesada en patrones de demanda; el Área Comercial y de Recaudación, responsable del ticket promedio y la composición del pasaje; y el Área de Tecnología, que supervisa la ejecución del proceso de carga. La matriz completa de interesados, con su pregunta de negocio principal, sus indicadores de interés y su frecuencia de consulta, se presenta en el **Anexo F**.

## 3.4. Requerimientos de información e indicadores clave

Los indicadores se seleccionaron aplicando el criterio de accionabilidad expuesto en la sección 2.5. La tabla siguiente vincula cada indicador con la decisión que habilita.

**Tabla 3. Indicadores clave de desempeño y decisiones asociadas**

| N° | Indicador | Origen en el modelo | Decisión que habilita |
|---|---|---|---|
| KPI 1 | Total de validaciones | `SUM(fact_validacion_diaria.cant_validaciones)` | Dimensionamiento global de la demanda y línea base de comparación |
| KPI 2 | Ingreso por ruta | `SUM(monto_total)` agrupado por `dim_ruta` | Priorización de inversión y evaluación de continuidad de rutas |
| KPI 3 | Promedio de pasajeros por viaje | `SUM(fact_carrera.num_pasajeros) / COUNT(fact_carrera)` | Ajuste de frecuencias y detección de sobreoferta o saturación |
| KPI 4 | Distribución por tipo de pasaje | `fact_validacion_diaria` agrupado por `dim_tipo_pasaje` | Proyección de recaudación y dimensionamiento del subsidio implícito |
| KPI 5 | Concentración en hora punta | Filtrado por `dim_franja_horaria.es_hora_punta` | Programación diferenciada de frecuencias por franja |
| KPI 6 | Ticket promedio | `SUM(monto_total) / SUM(cant_validaciones)` | Control de la recaudación media efectiva por abordaje |

Los indicadores KPI 1 a KPI 4 corresponden a los requeridos por la guía del curso. KPI 5 y KPI 6 se incorporaron por su relevancia analítica, evidenciada durante la fase exploratoria.

## 3.5. Requerimientos funcionales y no funcionales

Se formalizaron nueve requerimientos funcionales y ocho no funcionales, cada uno con su criterio de aceptación. Los funcionales cubren la consolidación del histórico completo, su verificabilidad por conciliación contra el origen, el análisis por las seis dimensiones del modelo, la exposición de los seis indicadores con filtros y jerarquía temporal navegable, la generación del análisis en lenguaje natural y la portabilidad de la demostración. Los no funcionales establecen exigencias de rendimiento del proceso de carga y de las consultas, integridad referencial declarada, reproducibilidad del entorno, seguridad de las credenciales, privacidad del conjunto de datos, portabilidad y trazabilidad de las transformaciones. El catálogo completo, con la redacción íntegra de cada requerimiento y su criterio de aceptación, se presenta en el **Anexo F**.

## 3.6. Análisis y calidad de la fuente de datos

La fuente única del proyecto es la tabla `VALIDACIONES` del sistema OLTP embarcado, complementada por el catálogo de rutas `2op_ruta`. Sus cardinalidades corresponden a las declaradas en la sección 1.1. La estructura completa de la tabla fuente, columna por columna, con el tratamiento dado a cada atributo en el Data Mart, se presenta en el **Anexo G**.

Se destaca una particularidad técnica de la fuente: la columna `id carrera` contiene un espacio en su nombre, lo que obliga a delimitarla con acentos graves en toda sentencia SQL. Este tipo de anomalía es característico de esquemas legados y su detección temprana durante la exploración evitó fallos en el desarrollo del ETL.

Se realizó además un perfilado de calidad previo al diseño del ETL, evaluando completitud, consistencia, unicidad y validez, cuyo detalle figura igualmente en el **Anexo G**. La calidad general de la fuente se califica como **alta**: no hay nulos en `fecha`, `hora` ni `id_ruta`, la clave primaria no presenta duplicados y la integridad referencial contra el catálogo de rutas está verificada. Los dos hallazgos con consecuencia de diseño son la presencia de **0.56% de valores nulos en `monto`** y la existencia de **seis codificaciones distintas del atributo `sentido` para expresar dos conceptos**. El primero es suficientemente bajo como para no comprometer las conclusiones, pero suficientemente distinto de cero como para exigir una decisión explícita de tratamiento, que se documenta en la sección 6.5; el segundo determina el diseño de `dim_sentido` descrito en la sección 5.4.

---
---

# 4. ARQUITECTURA DE LA SOLUCIÓN

## 4.1. Visión general

La arquitectura adopta el patrón clásico de capas de una solución de Inteligencia de Negocios, con una extensión en la capa de consumo: además del dashboard tradicional, se incorpora un módulo de interpretación asistida por inteligencia artificial generativa.

`[FIGURA 1: Diagrama de arquitectura general de la solución, mostrando las cinco capas (fuente, integración, almacenamiento, servicio, consumo) con sus componentes y el flujo de datos entre ellas]`

La **capa de fuente** aloja la copia del sistema OLTP embarcado sobre MariaDB 11.4 en Docker. La **capa de integración** comprende el proceso ETL en Python, con pandas, SQLAlchemy y PyMySQL, y un área de staging que materializa la tarifa de referencia por ruta y mes. La **capa de almacenamiento** contiene el Data Mart `transporte_dm` en MariaDB con motor InnoDB, y un extracto portátil en SQLite. La **capa de servicio** la componen el modelo semántico de Power BI, con sus medidas DAX, y el módulo de interpretación en PHP 8 con cURL. La **capa de consumo** entrega el dashboard interactivo y la página de análisis en lenguaje natural. El componente externo es la API de Google Gemini con el modelo `gemini-2.0-flash`. El detalle de componentes, tecnologías y funciones por capa se presenta en el **Anexo H**.

## 4.2. Decisiones de arquitectura

**Se trabajó sobre una copia del sistema fuente, nunca sobre producción.** El esquema se importó a una instancia local de MariaDB 11.4 desplegada en un contenedor Docker. La decisión responde a tres razones: **aislamiento operativo**, ya que ninguna consulta del proyecto, incluidas las agregaciones sobre millones de filas, puede degradar el rendimiento del sistema que sostiene la operación diaria; **reproducibilidad**, pues el entorno completo se reconstruye desde cero mediante un archivo `docker-compose.yml` versionado y un volcado comprimido de la base de origen, lo que satisface el requerimiento RNF-04 y es condición de la demostración en vivo; y **seguridad**, dado que el proyecto no requiere ni posee credenciales del entorno productivo del operador.

**La integración se implementó en Python y no en una herramienta ETL gráfica.** Un script de Python es texto plano, comparable y auditable en un sistema de control de versiones, a diferencia de los archivos binarios o XML que generan Pentaho, SQL Server Integration Services o Talend. Ofrece además mayor expresividad para la lógica de negocio compleja del proyecto, señaladamente la derivación del tipo de pasaje descrita en la sección 5.5, que exige una lógica condicional dependiente de ruta y de mes cuya formulación resulta más clara en código que en un flujo gráfico. A ello se suman la ausencia de costo de licencia y la continuidad tecnológica con el resto de la solución, desarrollada íntegramente sobre herramientas libres.

**El Data Mart reside en una base independiente dentro del mismo motor.** La separación en bases distintas es una decisión pragmática justificada por el contexto académico y el volumen manejado: preserva las ventajas conceptuales de la separación OLTP-OLAP, esto es, esquemas, permisos y propósitos distintos, sin incurrir en la complejidad de administrar dos motores. Se destaca una decisión técnica adoptada durante la implementación: la base se creó explícitamente con la intercalación `utf8mb4_general_ci`, coincidente con la del esquema legado de origen, porque sin esta alineación las uniones entre tablas de bases distintas producen el error de intercalaciones incompatibles, problema recurrente al integrar esquemas heredados que se resolvió de forma preventiva en el propio script de creación. Todas las tablas emplean el motor InnoDB, y la declaración explícita de las restricciones de clave foránea exigida por RNF-03 garantiza que ningún hecho pueda referenciar una fila de dimensión inexistente.

**La demostración se apoya en un extracto portátil en SQLite.** El requerimiento RNF-07 exige que la solución sea ejecutable en un equipo distinto al de desarrollo, sin depender de contenedores en ejecución ni de un motor de base de datos instalado. Para satisfacerlo se generó un extracto que replica las tablas de dimensión y los agregados necesarios para el módulo de IA. SQLite es un motor embebido: la base de datos completa es un único archivo y el motor se enlaza como biblioteca dentro de la aplicación, sin proceso servidor, lo que convierte al extracto en un artefacto autocontenido que acompaña al código del módulo.

## 4.3. Flujo de datos extremo a extremo

Un pasajero valida su pasaje y el sistema embarcado registra una fila en `VALIDACIONES`; el volcado del sistema fuente se importa a la base `transporte_oltp` del contenedor local. El proceso ETL extrae los valores distintos de los atributos descriptivos y construye las dimensiones en memoria mediante pandas, calcula la tarifa de referencia por ruta y mes y la materializa en la tabla de staging, ejecuta mediante push-down SQL las sentencias de agregación que pueblan las dos tablas de hechos, y concilia el total de validaciones del origen contra la suma de la tabla de hechos reportando la diferencia. A continuación se aplica el procedimiento de anonimización sobre el Data Mart, descrito en la sección 10.3; Power BI importa el modelo, aplica las medidas DAX y renderiza el dashboard; y un script exporta el extracto SQLite. Finalmente, el módulo PHP consulta el extracto, compone el contexto cuantitativo y solicita el análisis al servicio de inteligencia artificial, de modo que el usuario recibe simultáneamente la evidencia visual y su interpretación en lenguaje natural.

---
---

# 5. MODELADO DIMENSIONAL

## 5.1. Los cuatro pasos del diseño dimensional

Kimball y Ross (2013) prescriben una secuencia de cuatro decisiones. El **proceso de negocio** seleccionado fue la validación de pasaje, por ser el que genera el dato de demanda y por responder directamente al problema planteado; se identificó adicionalmente un segundo proceso instrumental, la ejecución de carrera, cuya necesidad se justifica en la sección 5.3. Se declararon dos **granos**, uno por cada tabla de hechos. Se identificaron seis **dimensiones** y dos **medidas** por tabla de hechos, todas aditivas.

## 5.2. Tabla de hechos 1: `fact_validacion_diaria`

> **Grano:** una fila por cada combinación de fecha, franja horaria, ruta, sentido, paradero y tipo de pasaje.

Formalmente, el grano es el producto cartesiano observado de seis dimensiones, `fecha × franja_horaria × ruta × sentido × paradero × tipo_pasaje`, y la tabla resultante contiene **1,305,099 filas**.

El grano atómico natural del proceso sería una fila por validación individual. Se optó por un grano agregado a partir de tres argumentos. Por **volumen**, el grano atómico habría producido una tabla del orden de las decenas de millones de filas, y el adoptado lo reduce aproximadamente en un factor de nueve, lo que se traduce en tiempos de consulta sustancialmente menores y en un modelo importable a Power BI sin dificultad. Por **suficiencia analítica**, ninguna de las consultas del proyecto pregunta qué ocurrió en una validación concreta: todas las preguntas de negocio identificadas en la sección 3.4 son preguntas de agregado, y el grano elegido conserva íntegramente la capacidad de responderlas. Y por **privacidad**, al no existir una fila por transacción individual se elimina de raíz la posibilidad de rastrear el comportamiento de un abordaje concreto, de modo que la agregación es en sí misma una técnica de protección.

Se reconoce explícitamente el costo de la decisión: la agregación es irreversible, pues el detalle que no se carga no puede recuperarse desde el Data Mart, y el modelo no permite el análisis de secuencias individuales de abordaje. Dado que ninguno de los requerimientos identificados lo demanda, el costo se considera aceptable.

**Tabla 4. Medidas de las tablas de hechos del modelo**

| Tabla de hechos | Medida | Tipo | Cálculo en el origen | Aditividad |
|---|---|---|---|---|
| `fact_validacion_diaria` | `cant_validaciones` | INT | `COUNT(*)` del grupo | Aditiva sobre todas las dimensiones |
| `fact_validacion_diaria` | `monto_total` | DECIMAL(14,2) | `SUM(monto)` del grupo | Aditiva sobre todas las dimensiones |
| `fact_carrera` | `num_pasajeros` | INT | `COUNT(*)` de la carrera | Aditiva |
| `fact_carrera` | `monto_total` | DECIMAL(12,2) | `SUM(monto)` de la carrera | Aditiva |

Las cuatro medidas son plenamente aditivas, propiedad que simplifica el diseño de las medidas DAX y garantiza que el usuario obtenga resultados correctos con independencia del nivel de agregación que aplique en el dashboard.

## 5.3. Tabla de hechos 2: `fact_carrera` y el problema del doble conteo

> **Grano:** una fila por carrera ejecutada.

La tabla contiene **310,678 filas**, coincidentes con el número de carreras identificadas en el origen.

### 5.3.1. Fundamento del diseño: por qué una segunda tabla de hechos

Esta es la decisión de diseño más relevante del modelo y constituye el argumento técnico central del proyecto. El indicador KPI 3, promedio de pasajeros por viaje, exige un denominador: el **número de carreras** ejecutadas.

La alternativa de incorporar el identificador de carrera al grano de `fact_validacion_diaria` presenta dos defectos graves. El primero es de volumen: aproximaría el grano al atómico, anulando el beneficio de la agregación, puesto que una carrera comparte fecha y ruta pero se distribuye entre muchos paraderos y franjas. El segundo, determinante, es de correctitud. Una misma carrera genera validaciones en múltiples paraderos, franjas y tipos de pasaje, de modo que **aparecería en numerosas filas de la tabla de hechos**; al contar carreras mediante un conteo de filas, o incluso de valores distintos sobre esa tabla, cualquier filtro aplicado sobre paradero, franja o tipo de pasaje alteraría el denominador de forma inconsistente, produciendo un indicador cuyo valor depende de filtros aplicados a dimensiones que no guardan relación con su definición. Este es el fenómeno conocido como **doble conteo** o *fan trap*. Un ejemplo numérico lo aclara: si una carrera registra cien validaciones distribuidas en veinte paraderos y el usuario filtra el dashboard por un único paradero, el cálculo podría producir el valor cinco pasajeros por viaje cuando la carrera efectivamente transportó cien. El indicador sería sencillamente falso.

La **alternativa adoptada** es una tabla de hechos independiente con grano de carrera. Al existir exactamente una fila por carrera, el conteo de carreras es un simple conteo de filas, insensible a la estructura de las demás dimensiones, y el indicador se calcula como cociente entre la suma de `num_pasajeros` y el conteo de filas, resultando correcto en cualquier contexto de filtro sobre las dimensiones que la tabla comparte.

### 5.3.2. Por qué esto configura una constelación

Las dos tablas de hechos comparten `dim_tiempo`, `dim_ruta` y `dim_sentido`, construidas una sola vez con las mismas claves y los mismos atributos: son **dimensiones conformadas**. Esta propiedad permite que un segmentador de ruta o un filtro de período aplicado en el dashboard afecten simultáneamente y de manera coherente a los dos hechos, habilitando la técnica de *drill across*. Si las dimensiones no fuesen conformadas, seleccionar la ruta R-01 filtraría un indicador pero no el otro, y el dashboard mostraría cifras incoherentes entre sí. La conformidad no es un refinamiento teórico: es la condición que hace utilizable el diseño.

`[FIGURA 2: Diagrama entidad-relación del modelo dimensional completo, mostrando las dos tablas de hechos al centro, las seis dimensiones y las relaciones, con indicación de cuáles dimensiones son conformadas]`

### 5.3.3. Cobertura de `fact_carrera` respecto del total de validaciones

Se documenta una precisión metodológica. La suma de `num_pasajeros` sobre `fact_carrera` asciende a 11,445,378 pasajeros, cifra inferior al total de 11,737,931 validaciones registradas en `fact_validacion_diaria`. La diferencia corresponde a validaciones cuyo identificador de carrera no está informado en el origen o no resuelve contra una carrera identificable, de modo que la cobertura de `fact_carrera` es del **97.51%** de las validaciones. Esta proporción se considera suficiente para sostener el indicador, pero se declara explícitamente porque explica por qué el cociente entre validaciones totales y carreras totales (37.78) no coincide con el indicador reportado (36.84). El indicador correcto es el segundo, por cuanto se calcula exclusivamente sobre las carreras cuyo conteo de pasajeros está efectivamente determinado.

## 5.4. Dimensiones del modelo

**Tabla 5. Resumen de las dimensiones del Data Mart**

| Dimensión | Filas | Clave primaria | Tipo de clave | Conformada |
|---|---|---|---|---|
| `dim_tiempo` | 391 | `id_tiempo` | Inteligente (AAAAMMDD) | Sí (ambos hechos) |
| `dim_ruta` | 22 | `id_ruta` | Natural (heredada del catálogo) | Sí (ambos hechos) |
| `dim_sentido` | 6 | `id_sentido` | Subrogada | Sí (ambos hechos) |
| `dim_paradero` | 170 | `id_paradero` | Subrogada | No (solo hecho 1) |
| `dim_tipo_pasaje` | 4 | `id_tipo_pasaje` | Subrogada | No (solo hecho 1) |
| `dim_franja_horaria` | 24 | `id_franja_horaria` | Inteligente (hora 0 a 23) | No (solo hecho 1) |

La composición de atributos de cada dimensión, con tipos, claves e índices, figura en el diccionario de datos del **Anexo A**. Tres decisiones de diseño merecen desarrollo aquí.

**El calendario de `dim_tiempo` es continuo y deliberadamente más extenso que los datos.** Es la única dimensión que se construye por generación y no por extracción, y cubre los 391 días del rango del período aunque solo 193 presenten hechos asociados, según se documenta en la sección 6.6. La continuidad es necesaria porque una dimensión temporal con días faltantes rompería las funciones de inteligencia de tiempo de DAX y produciría ejes temporales visualmente engañosos; conservar los 391 días hace que la ausencia resulte visible en lugar de quedar disimulada. La dimensión declara la jerarquía **Año > Trimestre > Mes > Semana > Día**, que habilita las operaciones de *roll-up* y *drill-down*, e incorpora atributos como `anio_mes` y `es_fin_semana` que constituyen desnormalización deliberada: podrían derivarse por cálculo, pero se materializan como columnas para que el usuario final los emplee directamente sin escribir expresiones.

**`dim_sentido` normaliza sin destruir información.** El origen codifica el sentido de circulación mediante seis valores distintos, NS, SN, IDA, VUELTA, EO y OE, que expresan en realidad dos conceptos con nomenclaturas heredadas de distintos momentos o corredores. La dimensión normaliza los seis códigos a Ida y Vuelta **conservando simultáneamente el código de origen** en el atributo `codigo_origen`. Esta doble representación es una buena práctica de trazabilidad: el usuario analiza con el vocabulario normalizado y el analista técnico puede verificar en todo momento de qué codificación original proviene cada fila.

**Las horas punta de `dim_franja_horaria` son empíricas y no convencionales.** El indicador booleano `es_hora_punta` no se definió por convención ni por criterio a priori, sino a partir de la distribución empírica de la demanda observada en los propios datos, que identificó las concentraciones entre las 6h y las 8h, con máximo a las 7h, y entre las 17h y las 19h. La dimensión materializa ese hallazgo, de modo que el indicador de concentración en hora punta refleja el comportamiento real del sistema y no una definición arbitraria.

Las restantes dimensiones son de construcción directa. `dim_ruta` contiene las veintidós rutas del catálogo `2op_ruta`, conservando su identificador como clave natural e incorporando el atributo derivado `tipo_ruta`, que clasifica el servicio a partir del patrón del código porque el negocio agrupa por esa categoría aunque el origen no la registre explícitamente. `dim_paradero` contiene los ciento setenta paraderos obtenidos por extracción de valores distintos sobre el campo de texto libre, con clave subrogada por la ausencia de un identificador de paradero en la fuente y con el atributo `zona`, imputado como "SIN ZONA" cuando el origen no lo informa.

## 5.5. Derivación de la dimensión de tipo de pasaje

### 5.5.1. El problema y la regla de negocio

El sistema fuente **no registra el tipo de pasajero**: no existe columna alguna que indique si quien aborda es adulto, escolar, universitario o beneficiario de gratuidad. Sin embargo, el indicador KPI 4 requiere la distribución de la demanda por tipo de pasaje, y la única evidencia disponible sobre la condición del pasajero es el **monto cobrado**. En el transporte público urbano peruano la estructura tarifaria distingue el pasaje adulto de tarifa completa, el medio pasaje aplicable a estudiantes de educación básica y superior, y la gratuidad aplicable a determinados beneficiarios, lo que permite inferir la condición del pasajero a partir de lo que pagó.

**Tabla 6. Reglas de derivación del tipo de pasaje**

| Condición sobre el monto | Tipo asignado | Fundamento |
|---|---|---|
| `monto = 0` | **Gratuito** | El abordaje se produjo sin cobro |
| `monto ≥ 0.75 × tarifa_adulto` | **Adulto** | Tarifa completa, con tolerancia por redondeos y variantes |
| `0 < monto < 0.75 × tarifa_adulto` | **Medio** | Pago reducido, compatible con tarifa preferencial de estudiante |
| `monto` es nulo | **Sin dato** | Ausencia de información; no se descarta la validación |

El umbral de 0.75 sobre la tarifa adulto se seleccionó por ser el punto medio entre la tarifa completa, equivalente a 1.00 del valor de referencia, y el medio pasaje, equivalente a 0.50, maximizando la separación entre ambas clases y otorgando tolerancia frente a redondeos y variantes tarifarias menores.

### 5.5.2. La derivación es sensible a la ruta y al tiempo

Las tarifas no son uniformes en el sistema del operador: las rutas troncales aplican una tarifa distinta de la de las alimentadoras, de modo que aplicar un umbral único clasificaría erróneamente como medio pasaje montos que constituyen tarifa completa en una ruta alimentadora. En consecuencia, la **tarifa de referencia se calcula por ruta**, tomando la **moda** del monto observado. La moda es el estadístico apropiado para este caso: en una distribución de montos dominada por la tarifa completa, el valor más frecuente es precisamente esa tarifa, mientras que la media resultaría contaminada por los medios pasajes y las gratuidades, y la mediana, aun siendo más robusta, seguiría sin identificar el valor tarifario nominal.

Durante el período analizado se produjo además un **ajuste tarifario**: la tarifa de adulto de referencia pasó de S/ 2.40 a S/ 2.45. Una tarifa de referencia calculada sobre todo el período sería un promedio de dos regímenes tarifarios distintos y clasificaría erróneamente los abordajes cercanos al umbral en uno de los dos. La solución adoptada consiste en calcular la tarifa de referencia **por ruta y por mes**, característica denominada *time-aware*: la regla de clasificación no es estática sino que evoluciona con la realidad tarifaria del sistema, y cada validación se clasifica contra la tarifa vigente en su ruta durante su mes. El resultado se materializa en la tabla de staging `stg_tarifa_ref`, descrita en la sección 6.4.

### 5.5.3. Limitación reconocida: escolar y universitario no son separables

Los estudiantes de educación básica y los de educación superior pagan **la misma tarifa preferencial**, por lo que resulta **matemáticamente imposible** distinguirlos a partir del monto: ambos producen exactamente el mismo valor en el único atributo disponible.

Ante esta restricción se adoptó una decisión metodológica explícita: **reformular el indicador**. El KPI 4 no se denomina "distribución por tipo de pasajero" sino **"distribución por tipo de pasaje"**. El cambio no es cosmético. "Tipo de pasajero" afirma algo sobre la persona que aborda, afirmación que los datos no sostienen; "tipo de pasaje" afirma algo sobre la transacción tarifaria, que es exactamente lo que los datos registran. Reportar una categoría "escolar" y una categoría "universitario" a partir de esta fuente habría constituido una fabricación de información inexistente, y ajustar la denominación del indicador a lo que el dato efectivamente soporta constituye una exigencia de rigor analítico. La dimensión resultante contiene cuatro filas, Adulto, Medio, Gratuito y Sin dato, definidas en el **Anexo A**.

## 5.6. Justificación integral del esquema adoptado

**Tabla 7. Evaluación comparativa de los esquemas dimensionales**

| Esquema | Ventajas en este caso | Desventajas en este caso | Decisión |
|---|---|---|---|
| Estrella simple (un hecho) | Máxima simplicidad | Imposibilita el cálculo correcto del promedio de pasajeros por viaje | Descartado |
| Copo de nieve | Menor redundancia en zona y tipo de ruta | Uniones adicionales y mayor complejidad para el usuario, sin beneficio de almacenamiento relevante dado el tamaño de las dimensiones | Descartado |
| **Constelación (dos hechos, dimensiones conformadas)** | **Resuelve el doble conteo, permite análisis cruzado y mantiene dimensiones desnormalizadas y simples** | **Mayor complejidad de mantenimiento del ETL** | **Adoptado** |

La mayor complejidad del ETL se considera un costo asumible y contenido: se traduce en una sentencia de agregación adicional.

---
---

# 6. PROCESO ETL

## 6.1. Visión general del proceso

El proceso ETL se implementó en un script de Python ejecutable desde línea de comandos, con dos modos de operación: ejecución completa del proceso y modo de verificación, que recalcula los indicadores sobre el Data Mart ya cargado sin volver a procesar el origen. Este segundo modo permite auditar la carga en cualquier momento sin costo de reproceso.

`[FIGURA 3: Diagrama de flujo del proceso ETL, mostrando las etapas de extracción, construcción de dimensiones en pandas, cálculo de la tarifa de referencia en staging, agregación de hechos mediante push-down SQL y conciliación final]`

El proceso se estructura en once etapas: conexión y verificación del origen, construcción sucesiva de las seis dimensiones, cálculo de la tarifa de referencia en staging, carga de las dos tablas de hechos mediante push-down SQL, y conciliación final con perfilado. El detalle de cada etapa, con su estrategia de ejecución y su salida en número de filas, se presenta en el **Anexo H**.

## 6.2. Extracción

La extracción se realiza sobre la copia local de la base OLTP y es **completa**: se procesa la totalidad del histórico disponible en cada ejecución, sin lógica incremental. Una carga incremental resulta indispensable cuando la ventana de proceso es insuficiente para reprocesar todo el histórico, lo que no ocurre en este caso. La carga completa aporta a cambio dos ventajas relevantes: es **idempotente**, es decir, ejecutarla dos veces produce exactamente el mismo resultado, y es **autocorrectiva**, puesto que cualquier corrección de la lógica de transformación se aplica retroactivamente sobre todo el histórico sin necesidad de procedimientos de reproceso selectivo. Se documenta como recomendación de evolución que un despliegue productivo debería incorporar carga incremental por fecha, dado que el histórico crece de forma continua.

## 6.3. Transformación: estrategia híbrida

La decisión técnica de mayor impacto en el rendimiento del proceso fue el reparto del trabajo de transformación entre Python y el motor de base de datos.

Las **dimensiones se construyen íntegramente en memoria mediante pandas**. Se trata de conjuntos pequeños, entre cuatro y trescientas noventa y una filas, sobre los que la lógica de transformación es densa: normalización de codificaciones, generación de calendario, derivación de atributos y clasificación por reglas. pandas ofrece en este escenario una expresividad muy superior a la de SQL para lógica condicional compleja, y el costo de traer los datos a memoria es despreciable dado el tamaño de los conjuntos.

Los **hechos siguen la estrategia opuesta**. Traer las decenas de millones de filas del origen a la memoria de Python para agruparlas con pandas era **inviable**: habría exigido una cantidad de memoria del orden de varios gigabytes, además de transferir un volumen de datos que solo se iba a colapsar mediante agregación. La técnica aplicada es el ***push-down***: en lugar de traer los datos al procesamiento, se envía el procesamiento a los datos. La agregación se expresa como una sentencia `INSERT INTO ... SELECT ... GROUP BY` que se ejecuta **íntegramente dentro del motor de base de datos**, sin que una sola fila de detalle atraviese la frontera hacia Python. Las ventajas son un consumo de memoria constante e independiente del volumen de la fuente, el aprovechamiento del optimizador, los índices y los algoritmos de agregación del motor, la eliminación del costo de transferencia y la atomicidad de la operación. Se destaca que esta es una decisión de ingeniería y no una preferencia estilística: bajo el enfoque alternativo el proceso sencillamente no se habría completado en el equipo de desarrollo.

El catálogo completo de transformaciones aplicadas, con la regla precisa de cada generación, derivación, normalización, estandarización, imputación, cálculo estadístico, clasificación, agregación y sustitución de claves, se presenta en el **Anexo H**.

## 6.4. Área de staging: la tabla `stg_tarifa_ref`

La derivación del tipo de pasaje presenta una dependencia circular aparente: para clasificar cada validación se necesita conocer la tarifa de referencia de su ruta y mes, pero esa tarifa se calcula a partir del conjunto de validaciones de esa ruta y mes. La dependencia se resuelve mediante **materialización intermedia**: el proceso ejecuta primero una consulta de agregación que determina, para cada combinación de ruta y mes, el monto más frecuente, y persiste el resultado en la tabla de staging `stg_tarifa_ref`, cuya estructura figura en el **Anexo A**.

Esta tabla es a la vez el insumo de la clasificación y un **artefacto de auditoría**. Su inspección permite verificar directamente que la lógica de derivación es correcta: si la tarifa modal detectada para una ruta troncal en un mes determinado es S/ 2.40 y en un mes posterior es S/ 2.45, el ajuste tarifario queda evidenciado en el propio Data Mart y la clasificación *time-aware* descrita en la sección 5.5.2 queda demostrada. Una vez cargados los hechos la tabla podría eliminarse; se conservó deliberadamente por su valor documental y de auditoría.

## 6.5. Manejo de valores nulos y erróneos

La política de tratamiento de valores ausentes se diseñó bajo un principio rector: **no descartar registros salvo que sea estrictamente inevitable**.

**Tabla 8. Política de tratamiento de datos incompletos**

| Situación | Volumen | Tratamiento adoptado | Justificación |
|---|---|---|---|
| `monto` nulo | 0.56% de los registros | Clasificación como "Sin dato"; la validación se conserva en el conteo de demanda y aporta cero al ingreso | La validación es evidencia de un abordaje real; descartarla subestimaría la demanda |
| `paradero` o `zona` vacíos | Proporción marginal | Imputación de la etiqueta "SIN ZONA" | Evita filas huérfanas en las dimensiones y preserva la validación |
| `sentido` con codificación heterogénea | Seis codificaciones | Normalización con conservación del código de origen | Homogeniza el análisis sin destruir trazabilidad |

La decisión sobre el monto nulo merece desarrollo porque ilustra que el tratamiento de nulos es una decisión de negocio y no meramente técnica. Existían tres alternativas. **Descartar** los registros habría sido el camino simple, pero introduce un sesgo direccional: cada registro descartado es un abordaje que efectivamente ocurrió, y eliminarlos subestima la demanda, que es precisamente la magnitud central del estudio. **Imputar** un monto, por ejemplo la tarifa modal de la ruta, habría preservado el conteo de demanda pero habría **fabricado ingreso inexistente**, inflando el ingreso total en aproximadamente sesenta y seis mil validaciones multiplicadas por la tarifa, sin respaldo documental alguno. **Conservar en categoría propia**, alternativa adoptada, satisface simultáneamente ambos objetivos: la validación cuenta para la demanda, aporta cero al ingreso y su condición de dato incompleto queda visible en la dimensión, de modo que cualquier usuario del dashboard puede cuantificar exactamente la magnitud de la incertidumbre.

## 6.6. Perfilado y cobertura de la fuente

Durante la fase de control de calidad posterior a la carga se detectaron dos características de la fuente que condicionan la interpretación de los resultados y que se documentan aquí de forma explícita. Ambas son delimitaciones del alcance efectivo del estudio, no defectos del análisis: describen qué porción de la operación está representada en el dato disponible.

### 6.6.1. La cobertura temporal es parcial

**La fuente no presenta cobertura diaria continua.** Aunque el período abarca 391 días de calendario entre febrero de 2025 y febrero de 2026, únicamente **193 días registran validaciones**, lo que representa una cobertura del **49.36%** del calendario del período. Adicionalmente, **el mes de diciembre de 2025 está ausente por completo**: no existe ningún día con registro en ese mes.

**Tabla 9. Cobertura temporal de la fuente por mes**

| Mes | Validaciones | Días con registro | Validaciones promedio por día con registro |
|---|---:|---:|---:|
| 2025-02 | 1,632,406 | 15 | 108,827 |
| 2025-03 | 64,028 | 1 | 64,028 |
| 2025-04 | 1,054,251 | 23 | 45,837 |
| 2025-05 | 1,054,938 | 20 | 52,747 |
| 2025-06 | 254,948 | 4 | 63,737 |
| 2025-07 | 1,211,261 | 23 | 52,663 |
| 2025-08 | 1,244,871 | 22 | 56,585 |
| 2025-09 | 1,054,828 | 16 | 65,927 |
| 2025-10 | 1,150,871 | 21 | 54,803 |
| 2025-11 | 749,217 | 12 | 62,435 |
| 2025-12 | 0 | 0 | Sin dato |
| 2026-01 | 1,129,585 | 15 | 75,306 |
| 2026-02 | 1,136,727 | 21 | 54,130 |
| **Total** | **11,737,931** | **193** | **60,818** |

`[FIGURA 4: Gráfico de barras de días con registro por mes, evidenciando la irregularidad de la cobertura y la ausencia total de diciembre de 2025]`

### 6.6.2. La cobertura corresponde a la operación de días laborables

El perfilado por día de la semana revela una segunda característica, de mayor consecuencia para la interpretación que la anterior: **la fuente cubre la operación de días laborables y no representa el fin de semana.**

**Tabla 10. Cobertura de la fuente por día de la semana**

| Día de la semana | Días con registro | Validaciones |
|---|---:|---:|
| Lunes | 39 | 2,588,797 |
| Martes | 41 | 2,667,158 |
| Miércoles | 37 | 2,168,715 |
| Jueves | 34 | 2,117,271 |
| Viernes | 34 | 2,195,524 |
| Sábado | 8 | 466 |
| Domingo | 0 | Sin registro |
| **Total** | **193** | **11,737,931** |

De lunes a viernes la cobertura es sustancial y homogénea, con entre 34 y 41 días registrados por día de la semana y volúmenes del orden de los dos millones de validaciones en cada uno. El sábado, en cambio, aparece con ocho días de registro pero únicamente 466 validaciones, cifra que no corresponde a una jornada de operación normal y que debe leerse como registro residual antes que como demanda de sábado. El domingo está enteramente ausente.

En consecuencia, **el estudio se acota explícitamente a la demanda laborable**. Todos los indicadores estructurales que se presentan en la sección 9 describen el comportamiento de la demanda en días laborables, que es la porción de la operación efectivamente representada en la fuente. **Ninguna conclusión de este informe puede extenderse al comportamiento de fin de semana**, cuyo perfil de demanda, composición tarifaria y distribución horaria podrían diferir sustancialmente del laborable sin que la información disponible permita constatarlo. Esta delimitación afecta también al indicador `es_fin_semana` de `dim_tiempo`, que se conserva en el modelo por completitud estructural pero que carece de base empírica suficiente para sustentar análisis comparativo.

### 6.6.3. Origen de la discontinuidad

Se establece con precisión que **el proceso ETL no introdujo estas características**. La verificación se realizó contrastando el conteo de fechas distintas presentes en la tabla `VALIDACIONES` del origen contra el conteo de fechas distintas presentes en `fact_validacion_diaria`, obteniéndose el mismo valor de 193 en ambos casos.

La discontinuidad proviene del **sistema fuente**. Conforme al planteamiento del problema expuesto en la sección 1.2, la información de validación del operador se genera mediante reportes periódicos del sistema embarcado, y esos reportes no cubren la totalidad de los días del calendario ni incorporan la operación de fin de semana. La consolidación disponible es, en consecuencia, un conjunto de ventanas de observación sobre la operación laborable y no una serie continua. Este hecho constituye, paradójicamente, una **confirmación empírica del problema de negocio** que motivó el proyecto: la ausencia de una consolidación histórica sistemática en la organización se manifiesta materialmente en la fragmentación de su propio registro histórico.

### 6.6.4. Consecuencias metodológicas

Los hallazgos obligan a distinguir dos clases de indicador.

**Indicadores estructurales, válidos dentro del alcance laborable.** Son aquellos que expresan una proporción calculada sobre el conjunto de días efectivamente registrados: la distribución por tipo de pasaje, la concentración horaria de la demanda, la participación de cada ruta en el ingreso, el ticket promedio y el promedio de pasajeros por viaje. Estos indicadores describen **cómo se comporta la demanda laborable cuando se observa**, y 11,737,931 validaciones distribuidas en 193 días constituyen una base de observación amplia y sólida para caracterizar esa estructura.

**Indicadores de evolución temporal, no válidos con esta fuente.** Cualquier comparación de totales absolutos entre meses mide **cobertura del registro y no demanda del sistema**: un mes con cuatro días de registro exhibirá un total bajo, e interpretar junio de 2025 como un mes de demanda deprimida sería un error de lectura elemental.

En consecuencia, el análisis de la sección 9 no presenta serie temporal mensual de totales, emplea exclusivamente el indicador normalizado de validaciones promedio por día con registro cuando requiere comparar entre meses, y no formula comparación alguna entre régimen laborable y fin de semana.

### 6.6.5. Medida adoptada en el modelo

Se optó deliberadamente por **no ocultar** la discontinuidad. La dimensión `dim_tiempo` conserva los 391 días del calendario, incluidos los 198 sin hechos asociados, de modo que un eje temporal construido sobre esta dimensión muestra los vacíos como vacíos. La alternativa, restringir la dimensión a los 193 días con dato, habría producido gráficos visualmente continuos y por ello engañosos: días separados por semanas aparecerían contiguos, sugiriendo una continuidad inexistente. Se privilegió la honestidad de la representación sobre su apariencia.

## 6.7. Carga y conciliación

La carga respeta el orden impuesto por las restricciones de integridad referencial: primero la totalidad de las dimensiones, después las tablas de hechos. Un intento de cargar hechos antes que dimensiones produciría el rechazo de las filas por violación de clave foránea, que es exactamente el comportamiento deseado del motor y la razón por la que las restricciones se declaran. El proceso es **idempotente por reconstrucción**, pues cada ejecución completa parte de estructuras vacías, y la restricción de unicidad declarada sobre el conjunto de columnas del grano en `fact_validacion_diaria` constituye una salvaguarda adicional: aunque la lógica del proceso fallase, el motor impediría físicamente la existencia de dos filas con el mismo grano.

Un proceso ETL que se ejecuta sin error no es, sin embargo, un proceso ETL correcto. La ausencia de excepciones únicamente acredita que ninguna sentencia falló; no acredita que el resultado represente fielmente el origen. Un filtro mal escrito, una unión que pierde filas o una condición de agrupación incorrecta producen un Data Mart internamente coherente y silenciosamente equivocado. La **conciliación** es el mecanismo que cierra esta brecha: consiste en verificar que una magnitud conocida y calculable de forma independiente en el origen coincide exactamente con la magnitud correspondiente en el destino.

La magnitud conciliada es el **conteo total de validaciones**, obtenido en el origen como conteo de filas de la tabla `VALIDACIONES` y en el destino como suma de la medida `cant_validaciones` sobre la totalidad de `fact_validacion_diaria`. **El resultado obtenido es una diferencia igual a cero:** ninguna validación se perdió durante el proceso y ninguna se duplicó.

Este resultado es más informativo de lo que aparenta. Una diferencia distinta de cero habría revelado alguno de los siguientes defectos: validaciones excluidas por no satisfacer ninguna de las reglas de clasificación, por ejemplo si la regla de nulos no estuviera contemplada; validaciones excluidas por unión interna contra una dimensión con filas faltantes, por ejemplo un paradero no incorporado; o validaciones duplicadas por una unión que produce coincidencias múltiples. La conciliación con diferencia cero descarta simultáneamente los tres escenarios y constituye, por ello, el **control de calidad central del proceso** y no un mero trámite de verificación. Junto a ella se ejecutaron ocho controles complementarios sobre montos, carreras, rangos de fechas, cardinalidades, claves foráneas huérfanas, participaciones por tipo de pasaje y consistencia de la tarifa de referencia, todos ellos superados y detallados en el **Anexo H**.

`[FIGURA 5: Captura de la salida por consola de la ejecución del proceso ETL, mostrando el registro de etapas, los tiempos y el resultado de la conciliación con diferencia cero]`

---
---
# 7. DASHBOARD E INFORMES

## 7.1. Criterios de diseño y modelo semántico

El dashboard se construyó en Power BI Desktop aplicando los principios expuestos en la sección 2.6. La información se organiza bajo una **jerarquía visual explícita** de dos niveles de lectura: las tarjetas de indicadores en la banda superior, legibles en menos de dos segundos y suficientes para responder la pregunta de magnitud; y los gráficos de composición, ranking y distribución, que responden la pregunta de estructura para quien necesita ir más allá del agregado. Por **economía visual** se eliminaron efectos tridimensionales, sombras, degradados y todo elemento decorativo carente de función informativa. El **color cumple función semántica** y no ornamental: la paleta se limita a un color de acento para las medidas de demanda, uno secundario para las de ingreso y una escala neutra para los elementos contextuales. El **formato es consistente**, con importes en moneda peruana y dos decimales, conteos con separador de miles y porcentajes con dos decimales. Y la **reactividad es total**: toda selección aplicada sobre cualquier visual o segmentador se propaga a la totalidad de la página.

Antes de la construcción visual se configuró el modelo semántico. Se establecieron relaciones de uno a muchos desde cada dimensión hacia las tablas de hechos, con **dirección de filtro simple**; la dirección simple es deliberada, puesto que la propagación bidireccional introduce ambigüedad en modelos con múltiples tablas de hechos y puede generar resultados inesperados. Se marcó `dim_tiempo` como tabla de fechas del modelo, requisito para el funcionamiento de las funciones de inteligencia de tiempo, y se declaró la jerarquía temporal Año, Trimestre, Mes y Día, que habilita la navegación por niveles sobre el eje de tiempo. Todas las medidas DAX se agruparon en una tabla dedicada denominada `_Medidas`, práctica que separa el vocabulario de negocio de las estructuras de datos y facilita el mantenimiento, y las columnas de clave subrogada se ocultaron de la vista de informe por tratarse de artefactos técnicos sin significado para el usuario. La definición completa de las medidas figura en el **Anexo B**.

`[FIGURA 6: Captura de la vista de modelo de Power BI, mostrando las dos tablas de hechos, las seis dimensiones y las relaciones establecidas]`

## 7.2. Composición del dashboard

El informe se organiza en **dos páginas**, cada una asociada a un tipo de pregunta de negocio. La composición responde a un criterio deliberado de economía: se prefirió un conjunto reducido de visualizaciones bien elegidas y legibles antes que una acumulación de gráficos que compitieran por la atención del usuario.

La **primera página, Visión general de la demanda**, responde a la pregunta de cuánto y de qué composición. Contiene cuatro tarjetas con los indicadores globales del corte seleccionado (total de validaciones, ingreso total, número de carreras y promedio de pasajeros por viaje), un gráfico de barras horizontales con el ranking de rutas por ingreso y un gráfico de anillo con la distribución por tipo de pasaje. El ranking se dispuso en barras horizontales por dos razones: el ordenamiento descendente convierte la comparación en una lectura inmediata, y la orientación horizontal acomoda las etiquetas de ruta sin rotarlas. El anillo se reservó para la distribución por tipo de pasaje por tratarse de una partición de cuatro categorías sobre un total, único caso en que la codificación angular resulta apropiada.

La **segunda página, Demanda por hora y paradero**, responde a la pregunta de cuándo y dónde. Contiene un gráfico de columnas con la demanda por franja horaria y un gráfico de barras con los diez paraderos de mayor demanda. La elección de columnas verticales para la distribución horaria no es arbitraria: el eje horizontal representa el transcurso del día, y esa correspondencia entre la dimensión espacial del gráfico y la dimensión temporal del dato hace que el perfil de doble pico se reconozca sin necesidad de leer los valores.

Ambas páginas incorporan segmentadores de **ruta** y de **período**, de modo que cualquier corte puede examinarse indistintamente desde la perspectiva de composición o desde la perspectiva temporal y geográfica. La especificación visual por visual, con el tipo de gráfico elegido y la justificación de esa elección, se presenta en el **Anexo I**.

Sobre el tratamiento del hallazgo de cobertura documentado en la sección 6.6, el dashboard aplica una decisión explícita: **no incorpora ninguna visualización de evolución mensual de totales**. La razón es la expuesta en 6.6.5, a saber, que una serie de totales por mes sobre una fuente de cobertura irregular induce a leer como caída de demanda lo que es ausencia de registro. Antes que acompañar ese gráfico de advertencias que el usuario podría pasar por alto, se optó por no ofrecerlo. La comparación entre períodos se realiza mediante el segmentador de período aplicado sobre indicadores de composición, que sí son robustos frente a la cobertura desigual.

`[FIGURA 7: Captura de la página 1 del dashboard, visión general de la demanda, con las cuatro tarjetas de indicadores, el ranking de rutas por ingreso y la distribución por tipo de pasaje]`

`[FIGURA 8: Captura de la página 2 del dashboard, demanda por hora y paradero, con los segmentadores, el perfil horario y el ranking de paraderos]`

## 7.3. Interactividad: las operaciones OLAP en la práctica

Las operaciones analíticas descritas en el Anexo E se materializan en el dashboard mediante interacciones concretas: el segmentador de ruta implementa el *slice*, la combinación de los segmentadores de ruta y período el *dice*, la jerarquía temporal de `dim_tiempo` habilita el *roll-up* y el *drill-down* sobre el eje de tiempo, y el cambio entre las dos páginas ofrece dos proyecciones distintas del mismo cubo. La correspondencia completa entre cada operación y su interacción, con ejemplos de uso, se presenta en el **Anexo I**.

El filtrado cruzado merece destacarse por ser la funcionalidad que transforma un conjunto de gráficos en un instrumento analítico. Al seleccionar una ruta en el ranking de ingresos, la totalidad de la página se recalcula para ese subconjunto, permitiendo al usuario formular y responder preguntas encadenadas sin escribir una sola línea de código. Esta reactividad es además el mecanismo que permite verificar la coherencia entre el dashboard y el módulo de inteligencia artificial descrito en la sección 8: aplicando el mismo corte de ruta y período en ambos, los indicadores coinciden exactamente, lo que evidencia que el modelo de lenguaje interpreta cifras calculadas por el Data Mart y no las produce por su cuenta.

## 7.4. Publicación en Power BI Service

El informe se publicó en Power BI Service, lo que aporta acceso desde navegador sin instalación de software cliente, control de acceso por usuario y disponibilidad desde dispositivos móviles. Se documenta una consideración de arquitectura pertinente: dado que el Data Mart reside en una instancia local, la actualización programada desde el servicio requeriría la instalación de una puerta de enlace de datos sobre el equipo anfitrión. En el alcance del proyecto el modelo se publica en modo de importación con los datos incorporados, solución adecuada para la demostración y para el volumen manejado.

`[FIGURA 9: Captura del informe publicado en Power BI Service, visualizado desde navegador web]`

---
---

# 8. INTEGRACIÓN DE IA Y API KEY

## 8.1. Planteamiento y descripción funcional

Un dashboard bien construido comunica con eficiencia **qué ocurrió**, pero transfiere íntegramente al lector la carga de la interpretación. Observar que el 45.67% de la demanda se concentra en hora punta requiere, para convertirse en decisión, que alguien sepa que esa cifra es alta, comprenda su implicancia sobre el dimensionamiento de flota y formule la acción correspondiente. Esta competencia interpretativa no está uniformemente distribuida en una organización: el analista la posee, mientras que el jefe operativo que debe programar frecuencias la próxima semana puede no poseerla y difícilmente dispondrá de tiempo para adquirirla. El módulo desarrollado atiende precisamente esa brecha, convirtiendo la evidencia visual en **lectura interpretada**.

El módulo es una aplicación web desarrollada en PHP. El usuario accede desde un navegador, selecciona una **ruta** de interés o la totalidad del sistema y un **período** de análisis, y ejecuta la generación. El módulo consulta entonces el extracto del Data Mart y calcula los indicadores del corte solicitado, compone con ellos un contexto cuantitativo estructurado, lo remite al servicio de inteligencia artificial junto con las instrucciones de análisis, recibe la respuesta y la presenta en pantalla acompañada de la tabla de indicadores que la sustenta.

`[FIGURA 10: Captura de la interfaz del módulo de IA, mostrando los controles de selección de ruta y período]`

`[FIGURA 11: Captura del análisis generado por el modelo, mostrando el texto interpretativo junto a la tabla de indicadores del corte que le sirvió de contexto]`

La integración se realiza contra la **API de Google Gemini** con el modelo `gemini-2.0-flash`, autenticación por API Key, protocolo HTTPS con método POST, intercambio en JSON y cliente cURL de PHP. La selección del modelo respondió a tres criterios: **latencia**, dado que la demostración en vivo exige respuesta en pocos segundos; **disponibilidad de una capa gratuita**, compatible con el contexto académico del proyecto; y **suficiencia de capacidad** para la tarea encomendada, que consiste en interpretar un conjunto acotado de indicadores y no en razonamiento complejo de múltiples pasos.

## 8.2. Arquitectura: el modelo interpreta, no calcula

La decisión arquitectónica central del módulo es la siguiente: **el modelo de lenguaje no accede a la base de datos, no ejecuta consultas y no calcula cifras.** Todos los valores numéricos se calculan previamente mediante consultas SQL deterministas sobre el extracto del Data Mart, y el modelo recibe esos valores ya calculados y verificados, con su tarea restringida a interpretarlos y redactar el análisis.

La justificación de esta restricción es directa. La limitación más documentada de los modelos generativos es la **alucinación**, es decir, la producción de contenido fluido y verosímil pero factualmente incorrecto (Ji et al., 2023). Un modelo al que se le solicite calcular un porcentaje puede producir un número plausible y equivocado, expresado con la misma seguridad retórica que uno correcto, y en un contexto de decisión operativa un indicador inventado es peor que la ausencia de indicador. Al confinar el rol del modelo a la interpretación de cifras suministradas, la aritmética la ejecuta el motor de base de datos, cuya corrección es verificable, y el lenguaje lo produce el modelo, que es aquello en lo que efectivamente es competente. Como salvaguarda adicional, la interfaz presenta **siempre** la tabla de indicadores junto al texto generado, lo que permite al usuario verificar cada cifra citada contra la fuente que se le suministró y convierte el análisis en auditable.

El contexto remitido al modelo se estructura en tres bloques. El **contexto de dominio** ofrece una descripción sintética del negocio: operador de transporte público urbano, naturaleza de una validación como abordaje, significado de una carrera y estructura tarifaria vigente; sin este bloque el modelo carecería del marco para interpretar las cifras. Los **indicadores del corte** comprenden el total de validaciones, el ingreso, el número de carreras, el promedio de pasajeros por viaje, el ticket promedio, la distribución por tipo de pasaje y por franja horaria, y la participación en el ingreso total del sistema. Las **instrucciones de análisis** especifican la tarea solicitada, que comprende la interpretación de la magnitud de la demanda, la identificación de patrones relevantes y la formulación de recomendaciones operativas concretas sobre frecuencias y asignación de flota, e incorporan restricciones explícitas: no introducir cifras ausentes del contexto, no formular afirmaciones sobre estacionalidad ni sobre comportamiento de fin de semana dadas las delimitaciones de cobertura documentadas en la sección 6.6, y mantener un registro ejecutivo destinado a personal operativo.

`[FIGURA 12: Diagrama de secuencia de la integración, mostrando el flujo desde la selección del usuario hasta la presentación del análisis, con la separación explícita entre el cálculo determinista en el motor de base de datos y la generación de lenguaje en el servicio de IA]`

El módulo contempla además el tratamiento de las condiciones de fallo previsibles: ausencia o invalidez de la credencial, agotamiento de la cuota de uso, tiempo de espera excedido y respuesta con estructura inesperada. En todos los casos se presenta al usuario un mensaje comprensible y **se conserva la presentación de la tabla de indicadores**, de modo que la indisponibilidad del servicio externo degrada la funcionalidad pero no la anula.

## 8.3. Seguridad de la credencial de acceso

La API Key es una credencial de autenticación: quien la posee puede consumir el servicio en nombre de su titular, generar costos imputables a este y, según la configuración de la credencial, acceder a otros servicios asociados a la misma cuenta.

Existe una implementación incorrecta y frecuente que consiste en invocar la API directamente desde JavaScript ejecutado en el navegador, incorporando la clave en el código del cliente. Esta implementación es **irreparablemente insegura**: todo lo que llega al navegador es inspeccionable, y basta abrir las herramientas de desarrollo para examinar el código fuente o el tráfico de red y extraer la credencial. La ofuscación del código no constituye protección, únicamente añade un paso trivial al procedimiento de extracción; la codificación en base 64 tampoco lo es, por tratarse de una codificación y no de un cifrado. El principio subyacente es categórico: **no existe forma alguna de mantener un secreto en un código que se entrega al usuario para su ejecución.**

La solución implementada aplica el patrón de **intermediación en el servidor**. El navegador nunca conoce la credencial y envía al servidor únicamente los parámetros de la consulta, esto es, la ruta y el período seleccionados; el código PHP se ejecuta en el servidor, donde reside la credencial, invoca la API de Google incorporándola y remite al navegador exclusivamente el texto del análisis. La credencial no atraviesa en ningún momento la frontera hacia el cliente: lo que el navegador recibe es el resultado, jamás el medio para obtenerlo.

**Tabla 11. Medidas de gestión de la credencial**

| Medida | Implementación | Riesgo mitigado |
|---|---|---|
| Externalización de la configuración | La credencial se lee de una variable de entorno del servidor, no está escrita en el código fuente | Exposición por lectura del código |
| Exclusión del control de versiones | El archivo `.env` figura en `.gitignore` y nunca se incorpora al repositorio | Filtración por publicación del repositorio |
| Plantilla versionada | Se versiona `.env.example` con los nombres de las variables y valores de ejemplo, sin valores reales | Pérdida de la documentación de configuración |
| Separación de entornos | Credenciales distintas para desarrollo y demostración | Propagación del compromiso entre entornos |
| Exclusión de la entrega | La credencial no se incluye en el material entregado ni se muestra en las capturas del informe | Exposición por distribución del entregable |

Se destaca el papel del archivo `.env.example`, cuya función consiste en documentar **qué** variables requiere la aplicación sin revelar **cuánto** valen. Quien clona el proyecto conoce de inmediato la configuración necesaria y provee sus propios valores. Es la resolución estándar de la tensión entre la necesidad de documentar la configuración y la de proteger los secretos.

`[FIGURA 13: Captura del archivo .env.example versionado, mostrando la declaración de la variable de la credencial sin su valor, junto al archivo .gitignore que excluye el .env real]`

## 8.4. Portabilidad y valor agregado

El módulo consume el **extracto SQLite** del Data Mart en lugar de conectarse a MariaDB, decisión que responde al requerimiento RNF-07 y a la exigencia de demostración en vivo. Sus consecuencias prácticas son la ausencia de dependencia de infraestructura, ya que no se requiere contenedor en ejecución ni configuración de red; la disponibilidad de un artefacto autocontenido, puesto que la base completa es un archivo que acompaña al código; y la robustez de la demostración, dado que un fallo del entorno de virtualización no la compromete.

Respecto de su aporte, el módulo responde a una pregunta distinta de la del dashboard. El dashboard responde qué ocurrió y exige del usuario competencia en lectura e interpretación de gráficos; el módulo responde qué significa y qué conviene hacer, sin exigir competencia técnica alguna. El dashboard entrega salida visual y el módulo salida narrativa; la elaboración del análisis pasa de manual, a cargo de un analista, a automática y disponible bajo demanda para cada corte, con estructura y criterios uniformes en lugar de variables según quien lo redacte. Se sostiene que el módulo **complementa** al dashboard y no lo sustituye: el dashboard aporta la evidencia y la capacidad de exploración, el módulo la síntesis interpretada, y su presentación conjunta es también un mecanismo de control, pues permite contrastar la afirmación del modelo contra la evidencia visual.

## 8.5. Consulta en lenguaje natural sobre el Data Mart

Sobre la arquitectura descrita se implementó una segunda modalidad de uso: la **consulta en lenguaje natural**, conocida en la literatura como *texto a SQL*. El usuario formula una pregunta en lenguaje corriente, por ejemplo cuáles son las cinco rutas de mayor ingreso o en qué hora se concentra la demanda, y el módulo responde con las cifras del Data Mart.

El procedimiento consta de cinco etapas. Primero se entrega al modelo la descripción del esquema del extracto, anotada con el significado de negocio de cada tabla y con las reglas de interpretación pertinentes, junto a la pregunta del usuario. Segundo, el modelo devuelve una consulta SQL. Tercero, y este es el punto crítico, **la consulta se valida antes de ejecutarse**. Cuarto, se ejecuta sobre una conexión en modo de solo lectura. Y quinto, el resultado retorna al modelo, que redacta la respuesta citando las cifras obtenidas.

La validación merece detenimiento porque define la diferencia entre una funcionalidad y una vulnerabilidad. El SQL que llega a ejecución fue redactado por un modelo de lenguaje a partir de un texto tecleado por un usuario: es, por definición, código de origen no confiable. Ejecutarlo sin filtro permitiría que una pregunta formulada con intención maliciosa terminara alterando o destruyendo datos. Por ello se aplican tres barreras independientes: la conexión se abre en modo de solo lectura a nivel del propio controlador de base de datos; se admite una única sentencia, que debe iniciar con `SELECT` o `WITH`, de modo que el encadenamiento de instrucciones queda descartado; y se rechaza toda aparición de palabras clave de escritura o administración. Cualquiera de las tres sería suficiente por separado; están las tres porque una defensa en capas no depende de que ninguna de ellas sea perfecta.

Dos decisiones de diseño complementan el planteamiento. La interfaz **muestra la consulta SQL ejecutada** junto a la respuesta, de modo que el usuario puede verificar el origen de cada cifra en lugar de confiar en la palabra del modelo; se transforma así una caja negra en un procedimiento auditable. Y cuando la pregunta no puede responderse con los datos disponibles, por ejemplo si se interroga por el número de conductores, dato deliberadamente excluido del Data Mart por minimización, el módulo lo declara explícitamente en lugar de improvisar una respuesta.

El módulo distingue además un **segundo tipo de pregunta**. No toda consulta de la gerencia pide una cifra: hay preguntas que piden un criterio, del tipo qué convendría hacer para mejorar los ingresos. Ninguna consulta SQL devuelve una recomendación, de modo que traducir esas preguntas a SQL carece de sentido. El módulo las identifica y las encamina por una vía distinta: en lugar de generar una consulta, construye el contexto cuantitativo completo del corte activo, el mismo que alimenta el análisis ejecutivo, y exige al modelo que **cada afirmación de la respuesta se apoye en una cifra concreta**. La recomendación queda así anclada a los datos y no a la plausibilidad del lenguaje.

La distinción entre ambos modos la realiza el propio modelo en la primera llamada, y la interfaz la hace visible: cuando la respuesta es de tipo consultivo se indica que no se ejecutó consulta alguna y sobre qué corte se elaboró. Esta transparencia sobre el procedimiento seguido es coherente con el criterio general del módulo, que consiste en no pedir al usuario que confíe en aquello que puede mostrársele.

La Figura 14 ilustra el modo consultivo. Conviene detenerse en dos rasgos de esa respuesta. El primero es que la recomendación de auditar las rutas R-12 y R-13 no procede de una intuición sino de un contraste cuantitativo: el ticket promedio de esas rutas, S/ 1.01 y S/ 0.92 respectivamente, se aparta del promedio general de S/ 2.02, y el modelo cuantifica además en S/ 132,738.24 el ingreso no percibido asociado a las validaciones sin clasificar. El segundo, y más relevante desde el punto de vista metodológico, es que el propio módulo **declara qué no puede concluir**: reconoce que los datos disponibles no permiten discriminar entre una alta proporción legítima de medio pasaje y una deficiencia en la recaudación, y especifica qué información adicional resolvería la ambigüedad. Esa declaración no fue solicitada en la pregunta; se produce porque el contexto entregado al modelo incluye las limitaciones de cobertura documentadas en la sección 6.6.

`[FIGURA 14: Captura del módulo respondiendo una pregunta de tipo consultivo, con las recomendaciones sustentadas en cifras del corte y la declaración explícita de las limitaciones del dato]`

## 8.6. Limitaciones reconocidas del componente de IA

Por rigor se documentan las limitaciones del módulo. Existe **dependencia de un servicio externo**, ya que la funcionalidad requiere conectividad y disponibilidad del servicio de Google; la degradación controlada descrita en la sección 8.2 mitiga pero no elimina esta dependencia. Existe **ausencia de determinismo**: dos ejecuciones sobre el mismo corte pueden producir redacciones distintas, si bien las conclusiones sustantivas se mantienen estables porque el contexto cuantitativo es idéntico. Persiste un **riesgo residual de alucinación**, pues la arquitectura lo acota pero no lo suprime y el modelo podría formular una inferencia no sostenida por los datos, por ejemplo atribuyendo una causa a un patrón observado; por ello la interfaz presenta siempre la tabla de indicadores y el análisis se rotula expresamente como generado automáticamente. Existe **ausencia de conocimiento contextual no suministrado**, ya que el modelo desconoce incidencias operativas, obras en la vía, cambios de recorrido o eventos locales, y puede describir un patrón pero no explicarlo cuando la explicación reside fuera de los datos. Finalmente, subsiste la **necesidad de validación humana**: las recomendaciones generadas constituyen insumo para la decisión y no la decisión misma, cuya responsabilidad permanece íntegramente en la persona competente.

---
---

# 9. ANÁLISIS DE RESULTADOS

## 9.1. Alcance y advertencia metodológica

Los resultados corresponden al período comprendido entre febrero de 2025 y febrero de 2026, sobre un total de 193 días con registro efectivo, conforme a la cobertura documentada en la sección 6.6. Tres precisiones acotan su lectura.

**Los resultados describen la demanda laborable.** Como se estableció en la sección 6.6.2, la fuente cubre la operación de lunes a viernes y no representa el fin de semana, con el sábado en registro marginal y el domingo ausente. Todos los indicadores que siguen caracterizan por tanto el comportamiento de la demanda en días laborables, y ninguna conclusión de esta sección puede extenderse al comportamiento de fin de semana.

**Los indicadores estructurales son válidos y los totales absolutos por mes no son comparables entre sí.** Los primeros se calculan sobre la totalidad de los días efectivamente registrados y describen la composición y el comportamiento de la demanda observada; los segundos reflejan la cobertura del registro y no la magnitud de la demanda, por lo que toda comparación intermensual de este análisis emplea exclusivamente el indicador normalizado de validaciones promedio por día con registro.

**Las magnitudes absolutas han sido perturbadas.** Conforme al procedimiento de anonimización descrito en la sección 10.3, las medidas de volumen fueron escaladas por un factor constante que preserva íntegramente todos los ratios y proporciones.

## 9.2. Indicadores globales del período

**Tabla 12. Indicadores globales del período analizado**

| Indicador | Valor |
|---|---:|
| Total de validaciones | 11,737,931 |
| Ingreso total | S/ 23,706,412.98 |
| Carreras ejecutadas | 310,678 |
| Promedio de pasajeros por viaje | 36.84 |
| Ticket promedio por validación | S/ 2.02 |
| Días con registro efectivo | 193 |
| Validaciones promedio por día con registro | 60,818 |
| Ingreso promedio por día con registro | S/ 122,831 |
| Carreras promedio por día con registro | 1,610 |
| Rutas activas | 22 |
| Paraderos con actividad | 170 |

`[FIGURA 15: Captura de la banda de tarjetas de indicadores del dashboard, mostrando los valores globales del período]`

La lectura de conjunto describe una operación de escala relevante: aproximadamente sesenta mil abordajes y mil seiscientas carreras por cada día laborable de operación registrado, con una recaudación diaria del orden de los ciento veintitrés mil soles.

El indicador de **36.84 pasajeros por viaje** admite una lectura operativa directa. Considerando que un bus de corredor urbano dispone de una capacidad del orden de ochenta a cien plazas entre asientos y espacio de pie, la ocupación media se sitúa aproximadamente entre el 37% y el 46% de la capacidad. Esta cifra es coherente con la naturaleza de un promedio que integra carreras de hora punta, previsiblemente saturadas, con carreras de franjas valle y de rutas de baja demanda. El indicador no debe interpretarse como sobreoferta generalizada, sino como evidencia de una **alta dispersión** en la utilización de la capacidad, cuya gestión constituye precisamente la oportunidad de optimización que el proyecto pone de manifiesto.

## 9.3. Análisis de la composición del pasaje

**Tabla 13. Distribución de la demanda y del ingreso por tipo de pasaje**

| Tipo de pasaje | Validaciones | Participación en validaciones | Ingreso (S/) | Participación en ingreso | Ticket medio del tipo (S/) |
|---|---:|---:|---:|---:|---:|
| Adulto | 9,389,037 | 79.99% | 21,583,174.79 | 91.04% | 2.30 |
| Medio | 1,904,472 | 16.22% | 2,123,238.19 | 8.96% | 1.11 |
| Gratuito | 378,710 | 3.23% | 0.00 | 0.00% | 0.00 |
| Sin dato | 65,712 | 0.56% | 0.00 | 0.00% | Indeterminado |
| **Total** | **11,737,931** | **100.00%** | **23,706,412.98** | **100.00%** | **2.02** |

`[FIGURA 16: Captura del gráfico de distribución por tipo de pasaje del dashboard]`

Cuatro de cada cinco abordajes corresponden a pasaje de tarifa completa. Este segmento sostiene el 91.04% del ingreso del sistema, participación superior a su peso en la demanda, lo que resulta aritméticamente esperable dado que es el único segmento que aporta la tarifa íntegra. El segmento de **medio pasaje** representa el 16.22% de los abordajes pero solo el 8.96% del ingreso, asimetría que cuantifica el efecto de la tarifa preferencial. El segmento **gratuito** alcanza el 3.23% de los abordajes y aporta ingreso nulo: constituye una magnitud modesta pero no despreciable, equivalente a 378,710 abordajes transportados sin contraprestación durante el período, cuya cuantificación resulta relevante para el dimensionamiento del subsidio implícito asumido por el operador.

### 9.3.1. Análisis del ticket promedio

El ticket promedio del sistema asciende a **S/ 2.02**, valor inferior a la tarifa de adulto de referencia de S/ 2.45. Esta divergencia debe explicarse de forma explícita, por cuanto su omisión conduce a una lectura errónea del desempeño comercial. **La divergencia no obedece a evasión ni a error de registro, sino a la composición tarifaria de la demanda:** el ticket promedio es una media ponderada del monto efectivamente cobrado sobre la totalidad de los abordajes, incluidos aquellos que por definición aportan un monto reducido o nulo.

**Tabla 14. Descomposición del ticket promedio por segmento tarifario**

| Componente | Participación | Ticket del segmento | Contribución al ticket promedio |
|---|---:|---:|---:|
| Adulto | 79.99% | S/ 2.30 | S/ 1.84 |
| Medio | 16.22% | S/ 1.11 | S/ 0.18 |
| Gratuito | 3.23% | S/ 0.00 | S/ 0.00 |
| Sin dato | 0.56% | S/ 0.00 registrado | S/ 0.00 |
| **Ticket promedio del sistema** | **100.00%** | | **S/ 2.02** |

Dos factores explican la totalidad de la brecha. El primero es la **composición del pasaje**: el 3.23% de abordajes gratuitos y el 16.22% de medios pasajes reducen mecánicamente la media del sistema, de modo que un 19.45% de la demanda aporta ingreso nulo o reducido a la mitad. El segundo es la **heterogeneidad tarifaria entre rutas**, pues incluso el ticket medio del segmento adulto, S/ 2.30, se sitúa por debajo de la tarifa de referencia de S/ 2.45; ello se explica porque las rutas alimentadoras aplican tarifas inferiores a las de las troncales, de manera que el ticket medio del segmento adulto es un promedio ponderado de varios regímenes tarifarios y no el valor de uno solo.

La conclusión de gestión es que **S/ 2.45 no constituye una referencia válida para proyectar recaudación**. La referencia correcta para todo ejercicio de proyección es el ticket promedio efectivo de S/ 2.02, o bien el ticket promedio específico de la ruta considerada. Emplear la tarifa nominal sobrestimaría los ingresos proyectados en aproximadamente un 21%.

## 9.4. Análisis de la distribución horaria de la demanda

El **45.67%** de la demanda se produce en las franjas declaradas punta, esto es, entre las 6h y las 8h y entre las 17h y las 19h, correspondiendo el **54.33%** restante al resto del día.

`[FIGURA 17: Captura del gráfico de validaciones por franja horaria del dashboard, mostrando el perfil de doble pico característico]`

El perfil de demanda presenta la estructura de **doble pico** característica de los sistemas de transporte urbano orientados al desplazamiento laboral y educativo: una concentración matinal entre las 6h y las 8h, con máximo a las 7h, y una concentración vespertina entre las 17h y las 19h. Este perfil es plenamente coherente con el alcance laborable del estudio establecido en la sección 6.6.2. La magnitud de la concentración es el dato relevante: **el 45.67% de la demanda del sistema se produce en seis de las aproximadamente dieciocho horas de operación efectiva**, es decir, en torno a un tercio del tiempo de operación se absorbe cerca de la mitad de la demanda.

Esta concentración tiene consecuencias directas sobre el dimensionamiento del sistema. **Sobre el dimensionamiento de flota**, el requerimiento de unidades queda determinado por la demanda punta y no por la demanda media, ya que la flota necesaria para atender la punta matinal excede sustancialmente la necesaria para atender la franja valle; esta característica es estructural del transporte urbano y explica que la utilización media de la capacidad, medida en la sección 9.2, se sitúe en niveles moderados: la capacidad se dimensiona para el máximo, no para el promedio. **Sobre la programación de frecuencias**, el dato habilita una programación diferenciada por franja, pues reducir el intervalo entre unidades durante las puntas mejora la experiencia del usuario en el momento de mayor exposición, mientras que ampliarlo durante las franjas valle reduce costos de operación sin afectar significativamente el nivel de servicio percibido. **Sobre la gestión de recursos humanos**, la estructura de doble pico favorece esquemas de turno partido, cuya implementación debe evaluarse considerando la normativa laboral aplicable y sus efectos sobre las condiciones de trabajo, aspectos que exceden el alcance de este análisis. Finalmente, la concentración observada constituye la **línea base** contra la cual evaluar el efecto de eventuales incentivos orientados a desplazar demanda hacia franjas valle.

## 9.5. Análisis de la concentración del ingreso por ruta

**Tabla 15. Ranking de rutas por ingreso generado**

| Posición | Ruta | Ingreso (S/) | Participación | Participación acumulada | Validaciones |
|---:|---|---:|---:|---:|---:|
| 1 | R-01 | 11,199,233.02 | 47.24% | 47.24% | 5,268,194 |
| 2 | R-05 | 4,315,952.70 | 18.21% | 65.45% | - |
| 3 | R-03 | 3,285,466.76 | 13.86% | 79.31% | - |
| 4 | R-09 | 2,704,997.85 | 11.41% | 90.72% | - |
| 5 | R-08 | 686,149.08 | 2.89% | 93.61% | - |
| 6 | R-12 | 529,585.79 | 2.23% | 95.84% | - |
| 7 | R-02 | 499,777.94 | 2.11% | 97.95% | - |
| 8 | R-13 | 275,870.68 | 1.16% | 99.11% | - |
| 9 a 22 | Resto (14 rutas) | 209,379.16 | 0.89% | 100.00% | - |
| | **Total** | **23,706,412.98** | **100.00%** | | **11,737,931** |

`[FIGURA 18: Captura del gráfico de ranking de rutas por ingreso del dashboard, con las barras ordenadas descendentemente]`

### 9.5.1. El hallazgo central: concentración extrema del ingreso

**Cuatro rutas de veintidós concentran el 90.72% del ingreso del sistema.** Este es el hallazgo de mayor relevancia estratégica del estudio. La estructura excede ampliamente la concentración descrita por el principio de Pareto: bajo una distribución de Pareto clásica, en torno al 20% de los elementos concentraría el 80% del resultado, mientras que en el caso analizado el 18.2% de las rutas concentra el 90.7% del ingreso y una sola ruta, R-01, aporta prácticamente la mitad del total del sistema. En el extremo opuesto, **catorce rutas aportan conjuntamente el 0.89% del ingreso**, cifra inferior a la que aporta individualmente cualquiera de las ocho primeras. La existencia de una cola tan extensa y de aporte tan reducido es en sí misma un hallazgo que demanda explicación operativa.

La ruta R-01 concentra el 47.24% del ingreso y el 44.88% de las validaciones del sistema. La comparación de ambas participaciones es informativa: **R-01 aporta proporcionalmente más ingreso que demanda**. Su ticket promedio es de S/ 2.13, superior al promedio del sistema de S/ 2.02, diferencia que admite dos explicaciones compatibles, una tarifa nominal superior propia de una ruta troncal y una composición de pasaje con menor participación de segmentos de tarifa reducida. En cualquier caso, la conclusión de gestión es inequívoca: **R-01 no es únicamente la ruta de mayor volumen del sistema, es también la de mayor calidad de ingreso por abordaje.**

### 9.5.2. Implicancias del riesgo de concentración

La concentración identificada configura una exposición al riesgo que debe explicitarse. El **riesgo operativo** se manifiesta en que una interrupción prolongada de R-01, sea por obras en la vía, siniestro, conflictividad social o decisión de la autoridad, comprometería de manera inmediata cerca de la mitad de los ingresos del operador, y la capacidad de las restantes veintiún rutas para compensar tal pérdida es prácticamente nula, puesto que las diecisiete de menor aporte, sumadas, no alcanzan el 10% del ingreso. El **riesgo regulatorio y contractual** deriva de que, en un esquema de concesión, la continuidad de la operación sobre una ruta determinada depende de decisiones de la autoridad de transporte, de modo que una modificación de trazado, una redistribución de rutas entre operadores o la no renovación de un contrato tiene sobre esta estructura de ingresos un impacto potencialmente existencial. El **riesgo de competencia** consiste en que la entrada de un servicio alternativo sobre el corredor de R-01, formal o informal, afectaría desproporcionadamente al operador respecto de lo que afectaría a un competidor con cartera diversificada. Y el **riesgo de asignación de recursos** opera internamente: la atención de las rutas de menor aporte compite por flota, personal y mantenimiento con la ruta que sostiene la operación.

### 9.5.3. Recomendaciones derivadas

**R1. Establecer un protocolo de contingencia específico para R-01.** Dada su criticidad, la ruta requiere un plan documentado de continuidad operativa que contemple rutas alternativas de desvío, reasignación prioritaria de flota y protocolo de comunicación a usuarios. La ausencia de tal protocolo constituye una vulnerabilidad no gestionada.

**R2. Evaluar la continuidad de las rutas de la cola.** Las catorce rutas que aportan conjuntamente el 0.89% del ingreso deben someterse a evaluación individual de rentabilidad, contrastando su ingreso contra su costo de operación efectivo. La evaluación debe distinguir tres situaciones: rutas de servicio obligatorio por contrato de concesión, cuya continuidad no es discrecional; rutas de baja demanda con función de alimentación de las rutas troncales, cuyo valor reside en el aporte indirecto que generan; y rutas sin ninguna de ambas justificaciones, candidatas a reestructuración o discontinuación. Se advierte expresamente que esta evaluación **excede el alcance del presente estudio**, que no incorpora datos de costo.

**R3. Priorizar la protección de la calidad de servicio en las rutas principales.** El nivel de servicio en las cuatro rutas que sostienen el 90.72% del ingreso debe ser objeto de monitoreo diferenciado, por cuanto un deterioro de la puntualidad o de la frecuencia en R-01 tiene un impacto económico desproporcionado respecto del mismo deterioro en cualquier otra ruta.

**R4. Incorporar la concentración como indicador de seguimiento.** La participación de la ruta principal y la participación acumulada de las cuatro primeras deben incorporarse al tablero de seguimiento de la gerencia como indicadores de riesgo estructural, con revisión periódica de su evolución.

## 9.6. Cobertura temporal y comparación normalizada

Conforme a lo establecido en las secciones 6.6 y 9.1, la comparación entre meses se realiza exclusivamente sobre el indicador normalizado de validaciones promedio por día con registro, cuyos valores por mes figuran en la Tabla 9. La cobertura efectiva que sustenta esa normalización se representa en la Figura 4, donde la irregularidad del registro resulta evidente: dos meses con menos de cinco días de datos y uno enteramente ausente.

Esta comparación se deja fuera del dashboard de forma deliberada, según se argumentó en la sección 7.2. El indicador normalizado exige que quien lo lea conozca la razón por la que existe; entregado como un gráfico más entre otros, invita a interpretaciones que la fuente no sustenta. En su lugar, el análisis normalizado se documenta aquí, donde puede acompañarse de la advertencia metodológica que le da sentido, y el módulo de inteligencia artificial de la sección 8 lo incorpora en su contexto para que sus interpretaciones queden sujetas a la misma restricción.

La lectura de esos valores exige prudencia y se formula con reservas explícitas. Febrero de 2025 presenta el mayor valor normalizado, con 108,827 validaciones por día con registro, superior en un 79% al de abril de 2025, que con 45,837 presenta el menor. Sin embargo, **no es posible atribuir esta diferencia a variación de la demanda con la información disponible**, por al menos tres razones. **Primera:** los meses con muy pocos días de registro, como marzo de 2025 con un solo día y junio de 2025 con cuatro, producen promedios de altísima varianza, pues un único día registrado puede corresponder a un día de alta demanda o a uno atípico. **Segunda:** se desconoce el criterio de selección de los días registrados, de modo que si los reportes disponibles privilegiaron determinados días de la semana, el promedio quedaría sesgado por la composición de la muestra y no por la demanda subyacente. **Tercera:** no es posible separar el efecto de la variación de demanda del efecto de la variación de oferta, ya que un mes puede presentar menor demanda promedio por menor afluencia de pasajeros o por menor número de carreras operadas.

En consecuencia, el estudio **no formula conclusiones sobre estacionalidad ni sobre tendencia de la demanda**. Los valores se presentan por transparencia y como insumo para el diseño de la recolección futura, no como evidencia de un patrón temporal.

## 9.7. Síntesis de hallazgos y recomendación transversal

**Tabla 16. Síntesis de hallazgos, evidencia y acción asociada**

| N° | Hallazgo | Evidencia | Acción recomendada |
|---|---|---|---|
| H1 | El ingreso está extremadamente concentrado: 4 rutas de 22 aportan el 90.72% | Tabla 15 | Protocolo de contingencia para R-01 y evaluación de la cola de rutas |
| H2 | R-01 aporta el 47.24% del ingreso por sí sola, con ticket superior al promedio | Tabla 15, sección 9.5.1 | Monitoreo diferenciado de su nivel de servicio |
| H3 | El 45.67% de la demanda se concentra en seis horas del día | Sección 9.4 | Programación de frecuencias diferenciada por franja |
| H4 | El ticket promedio efectivo es S/ 2.02, un 17.6% inferior a la tarifa nominal | Tabla 14 | Emplear el ticket efectivo, y no la tarifa nominal, en toda proyección de ingresos |
| H5 | El 19.45% de la demanda corresponde a pasaje gratuito o reducido | Tabla 13 | Cuantificación del subsidio implícito asumido por el operador |
| H6 | La ocupación media por carrera es de 36.84 pasajeros, con alta dispersión implícita | Tabla 12 | Análisis de utilización de capacidad por ruta y franja |
| H7 | La fuente presenta cobertura temporal parcial: 193 de 391 días, con diciembre de 2025 ausente | Tabla 9 | Establecer un proceso de consolidación diaria sistemática |
| H8 | La cobertura corresponde a días laborables: el sábado tiene registro marginal y el domingo está ausente | Tabla 10 | Incorporar el fin de semana a la consolidación para habilitar el análisis de ese régimen |

Los hallazgos H7 y H8 fundamentan una recomendación que trasciende el análisis de la demanda y atañe a la gestión de la información del operador. La organización dispone de un sistema que registra cada abordaje de manera exhaustiva, pero su consolidación histórica cubre menos de la mitad de los días del período y omite el fin de semana: el activo informacional existe, su preservación sistemática no.

Se recomienda establecer un **proceso de carga incremental diaria automatizada** hacia el Data Mart, que incorpore la totalidad de los días de operación. La solución desarrollada en este proyecto constituye la infraestructura necesaria para ello, puesto que el modelo dimensional está definido, el proceso de transformación implementado y los controles de calidad especificados; la modificación requerida consiste en incorporar la lógica de carga incremental por fecha descrita en la sección 6.2 y su programación periódica. El beneficio esperado es directo: con doce meses de cobertura continua y completa, las limitaciones que hoy impiden el análisis de estacionalidad, de tendencia y del régimen de fin de semana quedarían superadas.

---
---

# 10. CONSIDERACIONES ÉTICAS

## 10.1. Marco normativo aplicable

El tratamiento de datos personales en el Perú se rige por la **Ley N° 29733, Ley de Protección de Datos Personales**, promulgada en 2011, y por su reglamento aprobado mediante **Decreto Supremo N° 003-2013-JUS**. La norma tiene por objeto garantizar el derecho fundamental a la protección de los datos personales, reconocido en el artículo 2, numeral 6, de la Constitución Política del Perú.

La ley define el **dato personal** como toda información sobre una persona natural que la identifica o la hace identificable a través de medios que puedan ser razonablemente utilizados. La extensión de esta definición es determinante para el presente proyecto: **no se requiere que el dato nombre a la persona; basta con que permita identificarla**. Un identificador numérico de conductor no contiene su nombre, pero permite identificarlo de manera inequívoca dentro de la organización mediante un simple cruce con los registros de personal.

**Tabla 17. Principios de la Ley N° 29733 aplicados al proyecto**

| Principio | Contenido normativo | Aplicación en el proyecto |
|---|---|---|
| **Finalidad** | Los datos deben recopilarse para una finalidad determinada, explícita y lícita, y no ser tratados de manera incompatible con ella | La finalidad declarada es el análisis agregado de la demanda de pasajeros. Todo tratamiento que la excediera, señaladamente la evaluación del desempeño individual de trabajadores, fue excluido del diseño |
| **Proporcionalidad** | El tratamiento debe ser adecuado, relevante y no excesivo respecto de la finalidad | Se excluyeron del Data Mart los atributos que no resultan necesarios para el análisis de demanda, conforme a la sección 10.2 |
| **Calidad** | Los datos deben ser veraces, exactos y actualizados; no deben conservarse más allá de lo necesario | El proceso ETL incorpora los controles de calidad y la conciliación documentados en la sección 6.7. Las limitaciones de exactitud se declaran en la sección 10.5 |
| **Seguridad** | Deben adoptarse las medidas técnicas y organizativas necesarias para garantizar la seguridad de los datos | El entorno opera de forma local y aislada, las credenciales se gestionan conforme a la sección 8.3, y el conjunto entregado fue anonimizado |
| **Consentimiento** | El tratamiento requiere consentimiento previo, informado, expreso e inequívoco del titular | Se aborda a continuación |
| **Legalidad** | El tratamiento no puede realizarse por medios fraudulentos, desleales o ilícitos | Se operó sobre una copia autorizada del esquema, sin acceso a entornos productivos ni obtención irregular de información |

El principio de consentimiento presenta una particularidad relevante en este caso. Los datos de validación se generan en el curso ordinario de la prestación del servicio de transporte, y su tratamiento primario, la liquidación de la recaudación, resulta inherente a la relación contractual entre el pasajero y el operador. El tratamiento realizado en este proyecto es en cambio **secundario y estadístico**: no se dirige a persona alguna, no se adoptan decisiones sobre individuos y el resultado se expresa exclusivamente en agregados. La normativa contempla un tratamiento diferenciado para las finalidades estadísticas cuando los datos han sido sometidos a un procedimiento de anonimización que impide la identificación de sus titulares. En atención a ello, el proyecto adoptó una postura conservadora: **se anonimizó el conjunto de forma tal que ninguna persona natural resulte identificable**, según se detalla en las secciones 10.2 y 10.3, y bajo esta condición el resultado deja de constituir un tratamiento de datos personales en sentido estricto.

## 10.2. Minimización de datos: exclusión de identificadores de trabajadores

El sistema fuente registra, en cada validación, dos atributos que no fueron incorporados al Data Mart: la **`placa`** de la unidad de flota y el **`id_conductor`** del conductor que la operaba. Ambos atributos estaban disponibles, eran de calidad adecuada y su incorporación no presentaba dificultad técnica alguna. **Fueron excluidos deliberadamente**, por cuatro razones.

**Constituyen datos personales.** El identificador de conductor apunta de manera inequívoca a una persona natural determinada. La placa, aun siendo el identificador de un bien y no de una persona, es igualmente identificatoria en la práctica: el operador dispone de los registros de asignación de unidad a conductor, de modo que la placa unida a la fecha y la hora permite establecer quién conducía. Se trata, por tanto, de un **identificador indirecto**.

**Su incorporación excedería la finalidad declarada.** La finalidad del proyecto es el análisis de la demanda de pasajeros, y la identidad de quien conducía es irrelevante para determinar cuántas personas abordan en un paradero a una hora determinada. Incorporar el atributo habría vulnerado el principio de proporcionalidad.

**Habilitaría un uso no declarado y de mayor sensibilidad.** Este es el argumento determinante. Un Data Mart que incorporase el identificador del conductor permitiría, con una consulta elemental, construir un ranking de conductores por recaudación o por número de pasajeros transportados, es decir, **permitiría la evaluación del desempeño individual de los trabajadores**. Tal uso presenta dos problemas distintos y acumulativos. El primero es de licitud: constituye una finalidad diferente de la declarada, sobre la cual los titulares no fueron informados. El segundo es de validez metodológica: la recaudación de un conductor depende primordialmente de la ruta y la franja horaria que le fueron asignadas, factores ajenos a su control, de modo que un ranking construido sobre esta base mediría la asignación recibida y no el desempeño, y su empleo para adoptar decisiones sobre personas resultaría a la vez ilícito e injusto.

**El diseño previene el uso indebido.** Se sostiene un principio de diseño defensivo: la manera más eficaz de garantizar que una capacidad no se emplee indebidamente es **no construirla**. Un control de acceso puede modificarse y una política puede incumplirse, pero un atributo ausente del modelo no puede consultarse.

La evaluación atributo por atributo de la fuente bajo el criterio de minimización, con la decisión adoptada en cada caso, se presenta en el **Anexo J**. Se destaca por último que la decisión de grano agregado documentada en la sección 5.2, adoptada por razones de rendimiento, produce un efecto favorable adicional sobre la privacidad: al no existir una fila por transacción individual, no es posible aislar el registro de un abordaje concreto. La agregación constituye, por sí misma, una técnica de protección de datos reconocida.

## 10.3. Anonimización del conjunto de datos

La minimización protege a las personas naturales, pero resulta insuficiente para proteger a la **organización**. El proyecto aplicó por ello un segundo procedimiento mediante dos técnicas complementarias.

### 10.3.1. Técnica 1: supresión de identificadores

Los valores de los atributos descriptivos que permitían identificar al operador real fueron sustituidos por identificadores genéricos.

**Tabla 18. Sustituciones aplicadas por supresión de identificadores**

| Atributo | Valor original | Valor sustituido | Estructura preservada |
|---|---|---|---|
| `dim_ruta.codigo_ruta` | Códigos reales de las rutas del operador | R-01 a R-22, y etiquetas genéricas para las rutas sin numeración | Se conserva `tipo_ruta`, que mantiene la clasificación del servicio |
| `dim_paradero.nombre_paradero` | Nombres reales de los paraderos | P-001 a P-170 | Se conserva la identidad de cada paradero como entidad distinta y su pertenencia a una zona |
| `dim_paradero.zona` | Denominaciones geográficas reales | Zona Norte, Sur, Centro, Este, Oeste y Sin zona | Se conserva la partición geográfica: los paraderos continúan agrupados exactamente igual que en el origen |

La supresión se aplicó a los **valores**, no a la **estructura**, distinción esencial para la validez del estudio. El análisis no requiere conocer que un paradero determinado se denomina de tal modo; requiere conocer que ese paradero concentra un volumen determinado de abordajes, que pertenece a una zona determinada y que se distingue de los demás paraderos, y todas esas propiedades se preservan íntegramente. La sustitución se realizó mediante una correspondencia **uno a uno y estable**, lo que garantiza que las relaciones de cardinalidad, las agrupaciones y las uniones del modelo permanezcan inalteradas. La tabla de correspondencia entre valores originales y sustitutos no forma parte del material entregado, por lo que la reversión del procedimiento resulta impracticable a partir del conjunto publicado.

### 10.3.2. Técnica 2: perturbación multiplicativa

Todas las medidas de volumen del Data Mart, esto es, `cant_validaciones`, `monto_total` en ambos hechos y `num_pasajeros`, fueron multiplicadas por un **factor constante**. El fundamento de esta técnica reside en una propiedad elemental pero de consecuencias decisivas: **al escalar el numerador y el denominador de un cociente por el mismo factor, el cociente permanece invariante.** Formalmente, siendo *k* el factor de perturbación aplicado y siendo *a* y *b* dos medidas cualesquiera del conjunto:

$$\frac{k \cdot a}{k \cdot b} = \frac{a}{b}$$

**Tabla 19. Efecto de la perturbación multiplicativa sobre los indicadores del estudio**

| Indicador | Forma de cálculo | ¿Se conserva? | Fundamento |
|---|---|---|---|
| Participación de cada ruta en el ingreso | Ingreso de la ruta entre ingreso total | **Sí, exactamente** | Cociente entre dos medidas escaladas |
| Distribución por tipo de pasaje | Validaciones del tipo entre validaciones totales | **Sí, exactamente** | Cociente entre dos medidas escaladas |
| Concentración en hora punta | Validaciones en punta entre validaciones totales | **Sí, exactamente** | Cociente entre dos medidas escaladas |
| Ticket promedio | Ingreso total entre validaciones totales | **Sí, exactamente** | Cociente entre dos medidas escaladas |
| Promedio de pasajeros por viaje | Pasajeros entre carreras | **Sí, con precisión suficiente** | Ambas medidas escaladas por el mismo factor |
| Perfil horario de la demanda | Distribución relativa entre franjas | **Sí, exactamente** | Conjunto de cocientes entre medidas escaladas |
| Ranking y orden de las rutas | Ordenación por ingreso | **Sí, exactamente** | La multiplicación por una constante positiva preserva el orden |
| Total absoluto de validaciones | Suma de medidas | **No** | Es precisamente la magnitud que se pretende proteger |
| Ingreso absoluto del operador | Suma de medidas | **No** | Idem |

La consecuencia para la validez del estudio es que la totalidad de las conclusiones formuladas en la sección 9 se apoya en indicadores de la primera categoría. Que cuatro rutas concentren el 90.72% del ingreso, que el 45.67% de la demanda ocurra en hora punta y que el ticket promedio se sitúe un 17.6% por debajo de la tarifa nominal son afirmaciones **exactamente verdaderas respecto del operador real**; únicamente las magnitudes absolutas dejan de corresponder a su operación efectiva.

Se documentan tres precisiones del procedimiento. *Primera:* el factor se aplicó **uniformemente a ambas tablas de hechos**, pues de haberse empleado factores distintos los dos hechos habrían dejado de conciliar entre sí y el modelo habría perdido coherencia interna. *Segunda:* **las fechas no fueron perturbadas**, ya que una fecha no identifica a persona alguna y su alteración habría destruido la estructura temporal del conjunto sin aportar protección. *Tercera:* **el valor del factor no se divulga en este documento**, puesto que su publicación permitiría revertir la perturbación por simple división. Cabe añadir que, siendo las medidas magnitudes discretas, el resultado de la multiplicación se redondea, con una desviación despreciable frente a los volúmenes manejados; esta es la razón por la cual el promedio de pasajeros por viaje se cataloga en la Tabla 19 como conservado "con precisión suficiente" y no como exactamente conservado. Finalmente, el Data Mart previo a la anonimización se conserva en un volcado bajo control del autor y no forma parte del material entregado, lo que permite reproducir o auditar el procedimiento si fuera requerido por la instancia académica, sin que el conjunto entregado sea reversible.

## 10.4. Anonimización de la organización: fundamento

Se ha anonimizado no solo a las personas naturales sino también a la **empresa**, decisión que requiere justificación expresa por no derivar directamente de la Ley N° 29733, cuyo ámbito de protección son las personas naturales. La sostienen cinco fundamentos. **Primero**, la información es **comercialmente sensible**: el volumen de demanda y el ingreso por ruta de un operador de transporte constituyen información competitiva de primer orden, cuya divulgación identificada revelaría a competidores actuales o potenciales qué corredores resultan rentables y en qué magnitud, orientando decisiones de entrada al mercado o de participación en procesos de concesión. **Segundo**, la información **revela vulnerabilidades**: el hallazgo H1 es simultáneamente un hallazgo analítico valioso y la descripción precisa de una debilidad estructural del operador, de modo que publicarlo asociado a una empresa identificable equivaldría a difundir su punto de mayor exposición. **Tercero**, el **titular no ha consentido la publicación**: el acceso a los datos se obtuvo en un contexto determinado, y la publicación de un análisis identificado con la denominación de la empresa constituiría un uso que excede aquel contexto y respecto del cual no media autorización expresa. **Cuarto**, y de forma decisiva, **la identificación no aporta valor al estudio**, cuyo objetivo es demostrar la aplicación de una metodología de Inteligencia de Negocios sobre datos reales del contexto peruano; cuando un dato no aporta valor y sí genera riesgo, la decisión correcta es no incluirlo, que es precisamente el principio de minimización aplicado a la dimensión organizacional. **Quinto**, la **protección se extiende a terceros**, ya que la identificación del operador y de sus paraderos permitiría inferencias sobre la localización de la demanda en zonas específicas de la ciudad, información que, agregada con otras fuentes públicas, podría emplearse para finalidades ajenas al análisis de transporte.

## 10.5. Limitaciones y sesgos del análisis

Se documentan de forma exhaustiva las limitaciones del estudio. Su declaración explícita constituye una exigencia de rigor: un análisis cuyas limitaciones se omiten induce a los usuarios a extraer conclusiones que la evidencia no sostiene.

Se identificaron nueve limitaciones, que se enuncian a continuación con su efecto sobre las conclusiones. El desarrollo completo de cada una, con su descripción, el sesgo que introduce, su magnitud estimada y el tratamiento adoptado, se presenta en el **Anexo K**.

**L1. El tipo de pasaje es derivado, no declarado.** La dimensión `dim_tipo_pasaje` se construye por inferencia a partir del monto, según las reglas de la sección 5.5, por lo que es una clasificación inferida sujeta a error. Afecta al KPI 4 y, por derivación, al ticket promedio, pero no a los indicadores de demanda total, concentración horaria ni participación por ruta.

**L2. Escolar y universitario no son distinguibles.** Ambos segmentos abonan la misma tarifa preferencial y el monto no permite separarlos, de modo que el estudio informa una categoría "Medio" que los agrupa y **no es posible dimensionar la demanda escolar de forma independiente de la universitaria**. Conforme a la sección 5.5.3, el indicador fue reformulado de "tipo de pasajero" a "tipo de pasaje" para que su denominación no afirme más de lo que los datos sostienen.

**L3. La validación mide abordajes, no personas únicas.** Es la limitación conceptualmente más relevante. Un trasbordo genera dos validaciones, y un viaje de ida y retorno en el mismo día, dos adicionales. La cifra de 11,737,931 validaciones **no significa que once millones setecientas mil personas hayan utilizado el sistema**: el término "demanda" debe entenderse en este estudio como demanda de abordajes, y el indicador de pasajeros por viaje mide abordajes por carrera, magnitud correcta para dimensionar la ocupación pero no el número de personas distintas atendidas.

**L4. Registros sin monto informado.** El 0.56% de las validaciones, equivalente a 65,712 registros, carece de monto en el origen; se clasificaron como "Sin dato" y se conservaron en el conteo de demanda conforme a la sección 6.5. Si el cobro se hubiera producido sin quedar registrado, el ingreso total estaría subestimado en torno al 0.6%, magnitud inferior al margen de error aceptable para las decisiones que el estudio informa.

**L5. La cobertura temporal de la fuente es parcial.** La fuente cubre 193 de los 391 días del período, esto es, el 49.36% del calendario, con diciembre de 2025 ausente en su totalidad y una cobertura mensual irregular. En consecuencia **no son sostenibles** las afirmaciones sobre estacionalidad, tendencia, evolución mensual, comparación interanual ni efecto de eventos localizados en el tiempo. **Sí son sostenibles** los indicadores estructurales, esto es, distribución por tipo de pasaje, concentración horaria, participación por ruta, ticket promedio y ocupación media, por calcularse como proporciones sobre una base de observación amplia de casi doce millones de validaciones.

**L6. El alcance se restringe a la demanda laborable.** Conforme a la sección 6.6.2, de lunes a viernes se registran entre 34 y 41 días con volúmenes del orden de los dos millones de validaciones cada uno, mientras que el sábado presenta ocho días con únicamente 466 validaciones y el domingo está enteramente ausente. **El estudio caracteriza por tanto la demanda laborable y ninguna de sus conclusiones puede extenderse al régimen de fin de semana**, cuyo perfil horario, composición tarifaria y volumen podrían diferir de forma significativa sin que la información disponible permita constatarlo. Se subraya que esta es una delimitación del universo representado y no un defecto del análisis: los indicadores presentados son válidos y precisos dentro de ese universo.

**L7. Horizonte insuficiente para el análisis interanual.** Con independencia de la cobertura, el período abarca trece meses, horizonte insuficiente para caracterizar estacionalidad interanual, que exige al menos dos ciclos anuales completos.

**L8. Ausencia de datos de costo.** El estudio analiza demanda e ingreso y no incorpora costos operativos, por lo que no puede determinar la rentabilidad de ruta alguna. Ello acota expresamente la recomendación R2 de la sección 9.5.3: el estudio identifica las rutas de menor aporte al ingreso, pero **no concluye que deban discontinuarse**.

**L9. Efecto de la perturbación sobre las magnitudes absolutas.** Conforme a la sección 10.3.2, las cifras absolutas no corresponden a la operación real del operador, mientras que ratios, proporciones y órdenes se conservan exactamente.

### 10.5.8. Síntesis de limitaciones

**Tabla 20. Síntesis de limitaciones del estudio**

| N° | Limitación | Indicadores afectados | Severidad |
|---|---|---|---|
| L1 | Tipo de pasaje derivado del monto | KPI 4, ticket promedio | Media |
| L2 | Escolar y universitario no separables | KPI 4 | Media, con alcance acotado |
| L3 | La validación mide abordajes, no personas únicas | Todos los de demanda, en su interpretación | Alta en interpretación, nula en cálculo |
| L4 | 0.56% de registros sin monto | Ingreso total | Baja |
| L5 | Cobertura temporal parcial, 193 de 391 días, diciembre ausente | Todos los de evolución temporal | Alta para análisis temporal, nula para indicadores estructurales |
| L6 | Cobertura restringida a días laborables, sábado marginal y domingo ausente | Todos, en cuanto a su extensión al fin de semana | Alta como delimitación del universo, nula dentro del alcance laborable |
| L7 | Horizonte de trece meses | Análisis interanual | Alta para estacionalidad |
| L8 | Ausencia de datos de costo | Análisis de rentabilidad | Alta, define el alcance del estudio |
| L9 | Perturbación de magnitudes absolutas | Cifras absolutas | Nula sobre las conclusiones, por construcción |

## 10.6. Uso responsable del componente de inteligencia artificial

Se documentan las consideraciones éticas específicas del módulo descrito en la sección 8. En cuanto a la **transparencia respecto del origen del contenido**, el análisis presentado se rotula expresamente como generado automáticamente, puesto que el usuario debe conocer que el texto que lee no fue redactado por un analista humano y presentarlo como de autoría humana constituiría una práctica engañosa. En cuanto a la **verificabilidad**, la interfaz presenta invariablemente la tabla de indicadores junto al texto generado, de modo que toda afirmación cuantitativa resulta contrastable contra su fuente. Respecto de la **no transferencia de datos personales al servicio externo**, el contexto remitido a la API contiene exclusivamente indicadores agregados: ningún dato personal, ningún identificador de trabajador y ninguna transacción individual abandona el entorno local, propiedad que es consecuencia directa de las decisiones de minimización adoptadas en la sección 10.2. En cuanto a que **la decisión permanece en el ámbito humano**, las recomendaciones generadas constituyen insumo para la deliberación, correspondiendo la decisión operativa, con sus consecuencias sobre trabajadores y usuarios, íntegramente a la persona competente en la organización. Finalmente, se deja constancia expresa del **riesgo residual**: conforme a lo expuesto en la sección 8.5, la arquitectura acota el riesgo de alucinación pero no lo elimina, y el componente no se presenta como infalible.

---
---

# 11. CONCLUSIONES

## 11.1. Conclusiones sobre el cumplimiento de los objetivos

Respecto del **objetivo general**, se diseñó e implementó una solución integral de Inteligencia de Negocios que abarca el arco completo desde el sistema transaccional hasta la interpretación asistida por inteligencia artificial. La solución se encuentra operativa, es reproducible desde el material versionado y es demostrable en vivo.

En cuanto a **OE1**, se consolidó el histórico disponible en un Data Mart dimensional en MariaDB, bajo esquema de constelación con dos tablas de hechos y seis dimensiones conformadas, poblado mediante un proceso ETL en Python, verificándose la integridad de la carga por conciliación de totales contra el origen con **diferencia cero**. En cuanto a **OE2**, se construyó un dashboard interactivo en Power BI organizado en tres páginas, que expone seis indicadores mediante medidas DAX, con segmentadores por ruta, período y tipo de pasaje y con jerarquías de navegación temporal y geográfica, superando el mínimo de cuatro indicadores requerido. En cuanto a **OE3**, se integró la API de Google Gemini en un módulo web en PHP que genera análisis ejecutivos en lenguaje natural a partir de los indicadores del corte seleccionado, gestionando la credencial mediante variable de entorno del lado del servidor y excluyéndola del control de versiones. Y en cuanto a **OE4**, se aplicaron medidas de minimización, excluyendo los identificadores de trabajadores, y de anonimización del conjunto, demostrándose formalmente que el procedimiento preserva la totalidad de los ratios en que se sustentan las conclusiones. Los cuatro objetivos específicos se cumplieron íntegramente.

## 11.2. Conclusiones sobre el diseño técnico

**Primera. El diseño de constelación resultó necesario y no opcional.** El indicador de promedio de pasajeros por viaje es incalculable de forma correcta sobre una única tabla de hechos con el grano requerido por los demás indicadores. La segunda tabla de hechos con grano de carrera no constituye un refinamiento del diseño: es la condición que hace correcto al indicador. Se concluye que la declaración explícita del grano, conforme prescribe la metodología de Kimball, es efectivamente la decisión que determina la corrección del modelo.

**Segunda. La estrategia híbrida de transformación fue determinante para la viabilidad del proceso.** El reparto del trabajo entre pandas, para las dimensiones de bajo volumen y lógica densa, y push-down SQL, para la agregación de los hechos, permitió procesar la totalidad del histórico con consumo de memoria constante; bajo el enfoque alternativo el proceso no se habría completado en el equipo de desarrollo. Se concluye que la elección del lugar de ejecución de cada transformación es una decisión de ingeniería de primer orden y no un detalle de implementación.

**Tercera. La conciliación es el control de calidad que valida el proceso.** La ausencia de errores de ejecución acredita únicamente que ninguna sentencia falló; la conciliación con diferencia cero acredita que el Data Mart representa fielmente el origen, descartando simultáneamente pérdida de registros, duplicación y exclusión por uniones defectuosas. Se concluye que ningún proceso ETL debe considerarse completo sin un mecanismo de conciliación explícito.

**Cuarta. La derivación de atributos ausentes es posible pero debe declararse con precisión.** La reconstrucción del tipo de pasaje a partir del monto permitió obtener un indicador que la fuente no soportaba directamente, si bien la imposibilidad de separar escolar de universitario obligó a reformularlo de "tipo de pasajero" a "tipo de pasaje". Se concluye que cuando el dato no sostiene una afirmación, la respuesta correcta es ajustar la afirmación y no forzar el dato.

**Quinta. El control de calidad debe alcanzar a la cobertura y no solo a la corrección.** El hallazgo de que la fuente registra 193 de 391 días, y de que esos días corresponden casi exclusivamente a la operación laborable, se produjo en la fase de validación posterior a la carga. Un proceso que hubiera verificado únicamente la ausencia de nulos y la integridad referencial no lo habría detectado, y el análisis habría podido presentar variaciones de cobertura como variaciones de demanda, o extender al fin de semana conclusiones que solo describen la operación laborable. Se concluye que el perfilado de completitud temporal, incluida su distribución por día de la semana, debe integrarse como control estándar del proceso ETL.

## 11.3. Conclusiones sobre los hallazgos del negocio

**Sexta. El ingreso del operador presenta una concentración estructural crítica.** Cuatro rutas de veintidós aportan el 90.72% del ingreso y una sola de ellas el 47.24%. Esta estructura excede la concentración descrita por el principio de Pareto y configura una exposición al riesgo operativo, regulatorio y competitivo que debe gestionarse de manera explícita. Se concluye que la organización requiere un protocolo de contingencia específico para su ruta principal y una evaluación de la continuidad de las rutas de la cola, esta última necesariamente complementada con información de costos.

**Séptima. La demanda laborable presenta una concentración horaria que determina el dimensionamiento del sistema.** El 45.67% de la demanda se produce en seis de las aproximadamente dieciocho horas de operación, con un perfil de doble pico característico. Se concluye que el requerimiento de flota queda determinado por la demanda punta y no por la media, y que existe margen para una programación de frecuencias diferenciada por franja horaria.

**Octava. El ticket promedio efectivo difiere sustancialmente de la tarifa nominal.** El ticket promedio de S/ 2.02 se sitúa un 17.6% por debajo de la tarifa de adulto de referencia de S/ 2.45, diferencia explicada íntegramente por la composición del pasaje, con un 19.45% de la demanda gratuita o de tarifa reducida, y por la heterogeneidad tarifaria entre rutas. Se concluye que toda proyección de ingresos debe emplear el ticket efectivo y no la tarifa nominal, so pena de sobrestimar los ingresos en aproximadamente un 21%.

**Novena. La organización no preserva sistemáticamente su propio activo informacional.** El sistema registra cada abordaje de forma exhaustiva, pero la consolidación histórica disponible cubre menos de la mitad de los días del período, con un mes íntegramente ausente y sin representación del fin de semana. Se concluye que la implementación de una carga incremental diaria automatizada, extendida a la totalidad de los días de operación, constituye la recomendación de mayor impacto potencial del estudio.

**Décima. Se declara expresamente el alcance de las conclusiones anteriores.** Este estudio no sostiene conclusión alguna sobre estacionalidad ni sobre evolución temporal de la demanda, por impedirlo la cobertura parcial de la fuente, ni sobre el comportamiento de la demanda en fin de semana, por no estar ese régimen representado en el dato disponible. Las conclusiones sexta, séptima y octava son de naturaleza estructural, se calculan sobre la totalidad de los días efectivamente registrados y describen la operación laborable del sistema.

## 11.4. Conclusiones sobre la integración de inteligencia artificial

**Undécima. La arquitectura que separa cálculo de interpretación es la que hace confiable al componente de IA.** Al confinar el rol del modelo a interpretar cifras calculadas de forma determinista sobre el Data Mart, y al no delegarle su cómputo, se acota sustancialmente el riesgo de alucinación. Se concluye que en aplicaciones de Inteligencia de Negocios el modelo generativo debe emplearse como capa de lenguaje y no como capa de cálculo.

**Duodécima. El componente aporta valor al ampliar el alcance del análisis a usuarios sin competencia técnica.** El dashboard exige del lector la capacidad de interpretar gráficos; el módulo entrega la interpretación elaborada. Se concluye que ambos componentes son complementarios y que su presentación conjunta opera además como mecanismo de verificación.

**Decimotercera. La gestión de la credencial es un requisito no negociable de la integración.** Ninguna medida de ofuscación protege un secreto entregado al cliente. El patrón de intermediación en el servidor, con la credencial en variable de entorno y excluida del control de versiones, es la única implementación correcta.

## 11.5. Líneas de trabajo futuro

**Tabla 21. Líneas de evolución de la solución**

| N° | Línea de trabajo | Fundamento | Prioridad |
|---|---|---|---|
| F1 | Implementar carga incremental diaria automatizada | Resolver la limitación L5 de cobertura temporal | Alta |
| F2 | Extender la consolidación a sábados y domingos | Resolver la limitación L6 y habilitar el análisis del régimen de fin de semana | Alta |
| F3 | Incorporar un Data Mart de costos operativos con dimensiones conformadas | Habilitar el análisis de rentabilidad por ruta, hoy fuera de alcance por L8 | Alta |
| F4 | Incorporar el análisis de capacidad ofertada por franja | Permitir el cálculo de índices de utilización y no solo de ocupación media | Media |
| F5 | Desarrollar un modelo predictivo de demanda por ruta y franja | Pasar del análisis descriptivo al predictivo, viable una vez resueltas F1 y F2 | Media |
| F6 | Implementar el registro de auditoría de las ejecuciones del ETL | Fortalecer la trazabilidad del proceso en un despliegue productivo | Media |
| F7 | Evaluar la incorporación de un identificador anonimizado del medio de pago | Habilitar la distinción entre abordajes y pasajeros únicos, resolviendo L3, previo análisis específico de protección de datos | Baja |
| F8 | Ampliar el módulo de IA con análisis comparativo entre rutas | Extender el alcance interpretativo del componente | Baja |

## 11.6. Reflexión final

El proyecto verifica una premisa que la práctica de la Inteligencia de Negocios suele enunciar y que aquí se constata de manera literal: el valor no reside en el dato sino en su organización. La totalidad de los hallazgos presentados en la sección 9 estaba contenida en el sistema transaccional del operador antes del inicio de este trabajo. La concentración del 90.72% del ingreso en cuatro rutas era ya un hecho de su operación; simplemente, nadie podía verla. Lo que la solución aporta no es información nueva, sino información **accesible**. Esa distinción, y no la sofisticación de la tecnología empleada, constituye el fundamento de la disciplina.

---
---
# 12. REFERENCIAS BIBLIOGRÁFICAS

Brown, T. B., Mann, B., Ryder, N., Subbiah, M., Kaplan, J., Dhariwal, P., Neelakantan, A., Shyam, P., Sastry, G., Askell, A., Agarwal, S., Herbert-Voss, A., Krueger, G., Henighan, T., Child, R., Ramesh, A., Ziegler, D. M., Wu, J., Winter, C., ... Amodei, D. (2020). Language models are few-shot learners. En H. Larochelle, M. Ranzato, R. Hadsell, M. F. Balcan y H. Lin (Eds.), *Advances in Neural Information Processing Systems* (Vol. 33, pp. 1877-1901). Curran Associates.

Congreso de la República del Perú. (2011, 3 de julio). *Ley N° 29733, Ley de Protección de Datos Personales*. Diario Oficial El Peruano. https://www.gob.pe/institucion/congreso-de-la-republica/normas-legales/243470-29733

Dwork, C. (2006). Differential privacy. En M. Bugliesi, B. Preneel, V. Sassone e I. Wegener (Eds.), *Automata, Languages and Programming. ICALP 2006. Lecture Notes in Computer Science* (Vol. 4052, pp. 1-12). Springer. https://doi.org/10.1007/11787006_1

Few, S. (2012). *Show me the numbers: Designing tables and graphs to enlighten* (2.ª ed.). Analytics Press.

Few, S. (2013). *Information dashboard design: Displaying data for at-a-glance monitoring* (2.ª ed.). Analytics Press.

Golfarelli, M. y Rizzi, S. (2009). *Data warehouse design: Modern principles and methodologies*. McGraw-Hill.

Inmon, W. H. (2005). *Building the data warehouse* (4.ª ed.). John Wiley & Sons.

Ji, Z., Lee, N., Frieske, R., Yu, T., Su, D., Xu, Y., Ishii, E., Bang, Y. J., Madotto, A. y Fung, P. (2023). Survey of hallucination in natural language generation. *ACM Computing Surveys, 55*(12), 1-38. https://doi.org/10.1145/3571730

Kimball, R. y Caserta, J. (2004). *The data warehouse ETL toolkit: Practical techniques for extracting, cleaning, conforming, and delivering data*. John Wiley & Sons.

Kimball, R. y Ross, M. (2013). *The data warehouse toolkit: The definitive guide to dimensional modeling* (3.ª ed.). John Wiley & Sons.

Microsoft. (2025). *Documentación de Power BI*. Microsoft Learn. https://learn.microsoft.com/es-es/power-bi/

Parmenter, D. (2015). *Key performance indicators: Developing, implementing, and using winning KPIs* (3.ª ed.). John Wiley & Sons.

Presidencia de la República del Perú. (2013, 22 de marzo). *Decreto Supremo N° 003-2013-JUS, Reglamento de la Ley N° 29733, Ley de Protección de Datos Personales*. Diario Oficial El Peruano. https://www.gob.pe/institucion/minjus/normas-legales/276657-003-2013-jus

Sharda, R., Delen, D. y Turban, E. (2020). *Analytics, data science, & artificial intelligence: Systems for decision support* (11.ª ed.). Pearson.

Sweeney, L. (2002). k-anonymity: A model for protecting privacy. *International Journal of Uncertainty, Fuzziness and Knowledge-Based Systems, 10*(5), 557-570. https://doi.org/10.1142/S0218488502001648

Tufte, E. R. (2001). *The visual display of quantitative information* (2.ª ed.). Graphics Press.

Vaswani, A., Shazeer, N., Parmar, N., Uszkoreit, J., Jones, L., Gomez, A. N., Kaiser, Ł. y Polosukhin, I. (2017). Attention is all you need. En I. Guyon, U. von Luxburg, S. Bengio, H. Wallach, R. Fergus, S. Vishwanathan y R. Garnett (Eds.), *Advances in Neural Information Processing Systems* (Vol. 30, pp. 5998-6008). Curran Associates.

Wickham, H. (2014). Tidy data. *Journal of Statistical Software, 59*(10), 1-23. https://doi.org/10.18637/jss.v059.i10

---
---

# ANEXOS

## ANEXO A. Diccionario de datos del Data Mart `transporte_dm`

Motor: MariaDB 11.4. Motor de almacenamiento: InnoDB. Juego de caracteres: utf8mb4. Intercalación: utf8mb4_general_ci.

### A.1. Tabla `dim_tiempo`

**Descripción:** dimensión temporal conformada. Calendario continuo del período, generado programáticamente. Marcada como tabla de fechas en el modelo semántico de Power BI. Filas: 391.

| Columna | Tipo | Nulo | Clave | Descripción |
|---|---|---|---|---|
| `id_tiempo` | INT | No | PK | Clave inteligente en formato AAAAMMDD |
| `fecha` | DATE | No | UK | Fecha del calendario |
| `anio` | SMALLINT | No | IDX | Año |
| `trimestre` | TINYINT | No | | Trimestre del año, valores 1 a 4 |
| `mes` | TINYINT | No | IDX | Número de mes, valores 1 a 12 |
| `mes_nombre` | VARCHAR(12) | No | | Nombre del mes en español |
| `anio_mes` | CHAR(7) | No | | Período en formato AAAA-MM, para ejes temporales |
| `semana_anio` | TINYINT | No | | Número de semana según norma ISO 8601, valores 1 a 53 |
| `dia` | TINYINT | No | | Día del mes, valores 1 a 31 |
| `dia_semana` | TINYINT | No | | Día de la semana, 1 igual a lunes, 7 igual a domingo |
| `dia_nombre` | VARCHAR(10) | No | | Nombre del día en español |
| `es_fin_semana` | TINYINT(1) | No | | Indicador booleano, 1 para sábado y domingo |

### A.2. Tabla `dim_ruta`

**Descripción:** dimensión conformada de rutas. Construida desde el catálogo `2op_ruta` del origen. Los códigos fueron sustituidos por identificadores genéricos conforme al procedimiento de anonimización. Filas: 22.

| Columna | Tipo | Nulo | Clave | Descripción |
|---|---|---|---|---|
| `id_ruta` | SMALLINT | No | PK | Clave natural heredada del catálogo del origen |
| `codigo_ruta` | VARCHAR(20) | No | IDX | Código anonimizado de la ruta, formato R-NN |
| `tipo_ruta` | VARCHAR(15) | No | | Clasificación derivada del servicio: Regular, Expreso, Auxiliar, Escolar u Otro |

### A.3. Tabla `dim_paradero`

**Descripción:** dimensión de paraderos. Construida por extracción de valores distintos sobre el campo de texto libre del origen. Nombres y zonas anonimizados. Filas: 170.

| Columna | Tipo | Nulo | Clave | Descripción |
|---|---|---|---|---|
| `id_paradero` | INT | No | PK | Clave subrogada autoincremental |
| `nombre_paradero` | VARCHAR(60) | No | UK | Identificador anonimizado del paradero, formato P-NNN |
| `zona` | VARCHAR(50) | No | IDX | Zona geográfica anonimizada. Valor por defecto "SIN ZONA" |

### A.4. Tabla `dim_sentido`

**Descripción:** dimensión conformada de sentido de circulación. Normaliza las seis codificaciones del origen a dos valores de negocio, conservando el código original para trazabilidad. Filas: 6.

| Columna | Tipo | Nulo | Clave | Descripción |
|---|---|---|---|---|
| `id_sentido` | TINYINT | No | PK | Clave subrogada autoincremental |
| `codigo_origen` | VARCHAR(10) | No | UK | Código tal como figura en el origen: NS, SN, IDA, VUELTA, EO, OE |
| `sentido` | VARCHAR(10) | No | | Valor normalizado de negocio: Ida, Vuelta u Otro |

### A.5. Tabla `dim_tipo_pasaje`

**Descripción:** dimensión derivada por reglas de negocio a partir del monto. No existe en el origen. Filas: 4.

| Columna | Tipo | Nulo | Clave | Descripción |
|---|---|---|---|---|
| `id_tipo_pasaje` | TINYINT | No | PK | Clave subrogada autoincremental |
| `tipo` | VARCHAR(15) | No | UK | Categoría: Adulto, Medio, Gratuito o Sin dato |
| `descripcion` | VARCHAR(80) | No | | Descripción de la regla de negocio que origina la categoría |

### A.6. Tabla `dim_franja_horaria`

**Descripción:** dimensión de franja horaria. Generada programáticamente. Las horas punta se marcaron a partir de la distribución empírica de la demanda. Filas: 24.

| Columna | Tipo | Nulo | Clave | Descripción |
|---|---|---|---|---|
| `id_franja_horaria` | TINYINT | No | PK | Clave inteligente igual a la hora del día, valores 0 a 23 |
| `hora_texto` | CHAR(5) | No | | Representación textual de la hora, formato HH:MM |
| `franja` | VARCHAR(20) | No | | Banda operativa: Madrugada, Punta Mañana, Valle, Punta Tarde o Noche |
| `es_hora_punta` | TINYINT(1) | No | | Indicador booleano, 1 para las franjas 6h a 8h y 17h a 19h |

### A.7. Tabla `fact_validacion_diaria`

**Descripción:** tabla de hechos principal. Grano: fecha por franja horaria por ruta por sentido por paradero por tipo de pasaje. Filas: 1,305,099.

| Columna | Tipo | Nulo | Clave | Descripción |
|---|---|---|---|---|
| `id_validacion_diaria` | BIGINT | No | PK | Clave subrogada autoincremental |
| `id_tiempo` | INT | No | FK, IDX | Referencia a `dim_tiempo` |
| `id_franja_horaria` | TINYINT | No | FK | Referencia a `dim_franja_horaria` |
| `id_ruta` | SMALLINT | No | FK, IDX | Referencia a `dim_ruta` |
| `id_sentido` | TINYINT | No | FK | Referencia a `dim_sentido` |
| `id_paradero` | INT | No | FK | Referencia a `dim_paradero` |
| `id_tipo_pasaje` | TINYINT | No | FK | Referencia a `dim_tipo_pasaje` |
| `cant_validaciones` | INT | No | | Medida aditiva. Conteo de validaciones del grupo |
| `monto_total` | DECIMAL(14,2) | No | | Medida aditiva. Suma de montos del grupo, en soles |

**Restricción de unicidad:** existe una clave única sobre el conjunto de las seis columnas del grano, que impide físicamente la duplicación de filas por reejecución del proceso.

### A.8. Tabla `fact_carrera`

**Descripción:** tabla de hechos secundaria. Grano: una fila por carrera ejecutada. Existe para el cálculo correcto del promedio de pasajeros por viaje, evitando el doble conteo. Filas: 310,678.

| Columna | Tipo | Nulo | Clave | Descripción |
|---|---|---|---|---|
| `id_carrera` | BIGINT | No | PK | Clave natural heredada del campo `id carrera` del origen |
| `id_tiempo` | INT | No | FK, IDX | Referencia a `dim_tiempo` |
| `id_ruta` | SMALLINT | No | FK, IDX | Referencia a `dim_ruta` |
| `id_sentido` | TINYINT | No | FK | Referencia a `dim_sentido` |
| `num_pasajeros` | INT | No | | Medida aditiva. Conteo de validaciones de la carrera |
| `monto_total` | DECIMAL(12,2) | No | | Medida aditiva. Suma de montos de la carrera, en soles |

### A.9. Tabla de staging `stg_tarifa_ref`

**Descripción:** tabla del área de staging. Materializa la tarifa de referencia por ruta y mes, insumo de la derivación del tipo de pasaje. Se conserva como artefacto de auditoría.

| Columna | Tipo | Nulo | Clave | Descripción |
|---|---|---|---|---|
| `id_ruta` | SMALLINT | No | PK (parte) | Ruta a la que aplica la tarifa |
| `anio_mes` | CHAR(7) | No | PK (parte) | Mes de vigencia, formato AAAA-MM |
| `tarifa_adulto` | DECIMAL(6,2) | No | | Monto modal observado en la ruta y el mes, interpretado como tarifa de adulto |

### A.10. Resumen de relaciones del modelo

| Tabla origen | Columna | Tabla destino | Columna | Cardinalidad |
|---|---|---|---|---|
| `fact_validacion_diaria` | `id_tiempo` | `dim_tiempo` | `id_tiempo` | Muchos a uno |
| `fact_validacion_diaria` | `id_franja_horaria` | `dim_franja_horaria` | `id_franja_horaria` | Muchos a uno |
| `fact_validacion_diaria` | `id_ruta` | `dim_ruta` | `id_ruta` | Muchos a uno |
| `fact_validacion_diaria` | `id_sentido` | `dim_sentido` | `id_sentido` | Muchos a uno |
| `fact_validacion_diaria` | `id_paradero` | `dim_paradero` | `id_paradero` | Muchos a uno |
| `fact_validacion_diaria` | `id_tipo_pasaje` | `dim_tipo_pasaje` | `id_tipo_pasaje` | Muchos a uno |
| `fact_carrera` | `id_tiempo` | `dim_tiempo` | `id_tiempo` | Muchos a uno |
| `fact_carrera` | `id_ruta` | `dim_ruta` | `id_ruta` | Muchos a uno |
| `fact_carrera` | `id_sentido` | `dim_sentido` | `id_sentido` | Muchos a uno |

---

## ANEXO B. Medidas DAX del modelo semántico

Todas las medidas residen en una tabla dedicada denominada `_Medidas`. Los indicadores requeridos por la guía del curso se identifican con la marca KPI.

### B.1. Indicadores principales

```dax
// KPI 1 - Total de validaciones
Total Validaciones = SUM ( fact_validacion_diaria[cant_validaciones] )

// KPI 2 - Ingreso, sensible al contexto de filtro de ruta y período
Ingreso Total = SUM ( fact_validacion_diaria[monto_total] )

Ingreso Total (fmt) = "S/ " & FORMAT ( [Ingreso Total], "#,0.00" )

// KPI 3 - Promedio de pasajeros por viaje. Calculado sobre fact_carrera
// para evitar el doble conteo descrito en la seccion 5.3.2
Num Carreras = COUNTROWS ( fact_carrera )

Pasajeros en Carreras = SUM ( fact_carrera[num_pasajeros] )

Promedio Pasajeros por Viaje =
    DIVIDE ( [Pasajeros en Carreras], [Num Carreras] )

// KPI 4 - Distribucion por tipo de pasaje.
// Se usa con dim_tipo_pasaje[tipo] en el eje del visual.
% Validaciones por Tipo =
    DIVIDE (
        [Total Validaciones],
        CALCULATE ( [Total Validaciones], ALL ( dim_tipo_pasaje ) )
    )
```

### B.2. Medidas de apoyo

```dax
// Ticket promedio. Cociente de agregados, nunca agregado de cocientes:
// la medida no es aditiva y debe calcularse en este orden.
Ticket Promedio =
    DIVIDE ( [Ingreso Total], [Total Validaciones] )

// Denominador restringido a los dias con registro efectivo, conforme
// a la limitacion de cobertura documentada en la seccion 6.6.
Dias con Registro =
    CALCULATE (
        DISTINCTCOUNT ( fact_validacion_diaria[id_tiempo] ),
        ALLSELECTED ( dim_tiempo )
    )

Validaciones por Dia con Registro =
    DIVIDE ( [Total Validaciones], [Dias con Registro] )

Ingreso por Carrera =
    DIVIDE ( SUM ( fact_carrera[monto_total] ), [Num Carreras] )

% Ingreso por Ruta =
    DIVIDE (
        [Ingreso Total],
        CALCULATE ( [Ingreso Total], ALL ( dim_ruta ) )
    )
```

### B.3. Medidas de segmentación de la demanda

```dax
Validaciones en Hora Punta =
    CALCULATE ( [Total Validaciones], dim_franja_horaria[es_hora_punta] = 1 )

% Validaciones en Hora Punta =
    DIVIDE (
        [Validaciones en Hora Punta],
        CALCULATE ( [Total Validaciones], ALL ( dim_franja_horaria ) )
    )

Validaciones Fin de Semana =
    CALCULATE ( [Total Validaciones], dim_tiempo[es_fin_semana] = 1 )

Validaciones Dia Laboral =
    CALCULATE ( [Total Validaciones], dim_tiempo[es_fin_semana] = 0 )

Ruta Top por Ingreso =
    CALCULATE (
        FIRSTNONBLANK (
            TOPN ( 1, VALUES ( dim_ruta[codigo_ruta] ), [Ingreso Total], DESC ),
            1
        ),
        ALLSELECTED ( dim_ruta )
    )

// Participacion acumulada de las cuatro primeras rutas.
// Materializa el indicador de riesgo de concentracion (hallazgo H1).
% Ingreso Top 4 Rutas =
    VAR TopRutas =
        TOPN ( 4, ALL ( dim_ruta ), [Ingreso Total], DESC )
    VAR IngresoTop =
        CALCULATE ( [Ingreso Total], TopRutas )
    RETURN
        DIVIDE ( IngresoTop, CALCULATE ( [Ingreso Total], ALL ( dim_ruta ) ) )
```

### B.4. Medidas de inteligencia de tiempo

> **Advertencia de uso.** Las medidas de esta subsección requieren que `dim_tiempo` esté marcada como tabla de fechas del modelo. Se documenta expresamente que, en atención a la cobertura temporal parcial descrita en la sección 6.6, **estas medidas no se emplearon para sustentar conclusión alguna del informe**. Se conservan en el modelo por completitud técnica y para su uso una vez implementada la carga incremental diaria propuesta en la línea de trabajo F1.

```dax
Validaciones YTD =
    TOTALYTD ( [Total Validaciones], dim_tiempo[fecha] )

Validaciones Mes Anterior =
    CALCULATE ( [Total Validaciones], DATEADD ( dim_tiempo[fecha], -1, MONTH ) )

Crecimiento Validaciones MoM % =
    VAR actual = [Total Validaciones]
    VAR previo = [Validaciones Mes Anterior]
    RETURN DIVIDE ( actual - previo, previo )

Ingreso Mes Anterior =
    CALCULATE ( [Ingreso Total], DATEADD ( dim_tiempo[fecha], -1, MONTH ) )

Crecimiento Ingreso MoM % =
    DIVIDE ( [Ingreso Total] - [Ingreso Mes Anterior], [Ingreso Mes Anterior] )
```

---

## ANEXO C. Código fuente del módulo de integración con IA

`[ANEXO C: insertar aquí el código fuente completo del módulo PHP de integración con la API de Google Gemini. Debe comprender: (1) el archivo de configuración que lee la credencial desde la variable de entorno; (2) la clase o funciones de acceso al extracto SQLite que calculan los indicadores del corte seleccionado; (3) la función de composición del contexto cuantitativo remitido al modelo; (4) la función de invocación de la API mediante cURL, con el manejo de errores descrito en la sección 8.4.3; (5) la vista que presenta el análisis junto a la tabla de indicadores. IMPORTANTE: verificar antes de insertar que ninguna credencial real figure en el código incluido en el informe.]`

`[FIGURA 19: Captura del archivo de configuración del módulo, mostrando la lectura de la credencial desde la variable de entorno]`

`[FIGURA 20: Captura de la ejecución en vivo del módulo, con el análisis generado por el modelo para un corte de ruta y período determinado]`

---

## ANEXO D. Estructura del repositorio del proyecto

| Ruta | Contenido |
|---|---|
| `docker-compose.yml` | Definición del entorno de contenedores: MariaDB y phpMyAdmin |
| `.env.example` | Plantilla de configuración versionada, sin valores reales de credenciales |
| `.gitignore` | Exclusiones del control de versiones, incluido el archivo `.env` |
| `db/init/` | Script de creación de las bases de datos |
| `db/datamart/01_schema_datamart.sql` | Definición de la estructura del Data Mart |
| `db/anonimizacion/01_anonimizar_datamart.sql` | Procedimiento de anonimización descrito en la sección 10.3 |
| `db/dumps/` | Volcados del origen y del Data Mart, no versionados |
| `etl/etl_datamart.py` | Proceso ETL completo, con modo de verificación |
| `etl/requirements.txt` | Declaración de dependencias de Python |
| `powerbi/Dashboard_Validaciones.pbix` | Archivo del dashboard |
| `powerbi/medidas_dax.txt` | Definición de las medidas del modelo semántico |
| `entrega/data/kpi_datamart.sqlite` | Extracto portátil del Data Mart |
| `entrega/etl/exportar_kpis_sqlite.py` | Script de generación del extracto |
| `entrega/informe/INFORME.md` | El presente documento |

---
---

## ANEXO E. Fundamentos conceptuales complementarios

Material de referencia del marco teórico de la sección 2, cuya aplicación concreta se documenta en las secciones 5 y 7.

### E.1. Operaciones OLAP sobre el cubo de datos

El modelo dimensional se conceptualiza como un cubo de datos multidimensional sobre el que se ejecutan operaciones analíticas estándar:

- ***Roll-up*** (agregación): asciende en una jerarquía, agregando el detalle. Ejemplo: de demanda diaria a demanda mensual.
- ***Drill-down*** (desagregación): desciende en una jerarquía, exponiendo el detalle. Ejemplo: de demanda mensual a demanda diaria y luego por franja horaria.
- ***Slice*** (rebanado): fija el valor de una dimensión, produciendo un subcubo. Ejemplo: analizar únicamente la ruta R-01.
- ***Dice*** (dado): fija rangos en varias dimensiones simultáneamente. Ejemplo: rutas R-01 y R-05, en horas punta, durante el segundo trimestre.
- ***Pivot*** (rotación): reorienta los ejes de presentación para observar la misma información desde otra perspectiva.

Estas operaciones no son abstracciones teóricas: constituyen exactamente las interacciones que el dashboard descrito en la sección 7 pone a disposición del usuario mediante segmentadores y jerarquías, según la correspondencia detallada en el Anexo I.

### E.2. Etapas del proceso ETL

**Extracción.** Obtención de los datos desde los sistemas fuente, minimizando el impacto sobre su operación. Puede ser completa, es decir, todo el histórico, o incremental, solo lo nuevo desde la última carga.

**Transformación.** Etapa de mayor densidad lógica. Comprende la *limpieza* o corrección de valores erróneos, tratamiento de nulos y eliminación de duplicados; la *estandarización* u homogenización de formatos, codificaciones y nomenclaturas; la *derivación* o creación de atributos calculados que no existen en el origen, aplicando reglas de negocio; la *integración* o unificación de datos de fuentes distintas bajo un modelo común; y la *agregación* o consolidación al grano definido para la tabla de hechos.

**Carga.** Inserción de los datos transformados en las estructuras destino, respetando el orden de dependencias, esto es, dimensiones antes que hechos por integridad referencial.

**Área de staging.** Zona intermedia de almacenamiento donde residen resultados parciales del proceso. Su función es desacoplar las etapas, permitir reprocesos sin volver a extraer del origen y materializar cálculos intermedios reutilizables.

### E.3. Correspondencia entre tipo de análisis y visualización

La selección del tipo de gráfico debe responder a la naturaleza de la comparación que se pretende comunicar (Few, 2012).

**Tabla E1. Correspondencia entre tipo de análisis y visualización recomendada**

| Tipo de análisis | Visualización recomendada | Fundamento perceptual |
|---|---|---|
| Comparación entre categorías | Barras horizontales o verticales | La longitud es el atributo visual de comparación más preciso |
| Evolución temporal | Línea | La pendiente comunica tasa de cambio de forma directa |
| Composición de un total | Barras apiladas al 100% o anillo con pocas categorías | Evita la comparación angular imprecisa de sectores |
| Distribución sobre un ciclo | Columnas ordenadas por el ciclo | Preserva el orden natural de la variable |
| Concentración o ranking | Barras ordenadas descendentemente | El orden facilita la lectura del ranking |
| Valor único destacado | Tarjeta numérica (*card*) | Elimina toda mediación gráfica innecesaria |

---

## ANEXO F. Interesados y catálogo de requerimientos

Detalle de respaldo de las secciones 3.3 y 3.5.

**Tabla F1. Matriz de interesados y sus necesidades de información**

| Interesado | Rol en la organización | Pregunta de negocio principal | Indicadores de interés | Frecuencia de consulta |
|---|---|---|---|---|
| Gerencia General | Dirección estratégica | ¿Es sostenible la estructura de ingresos de la operación? | Ingreso total, participación por ruta, evolución mensual | Mensual |
| Jefatura de Operaciones | Programación del servicio | ¿En qué franjas y rutas se requiere mayor frecuencia? | Validaciones por franja horaria, concentración en hora punta, promedio de pasajeros por viaje | Semanal |
| Jefatura de Flota | Asignación de unidades | ¿Cuántas unidades se requieren por ruta y período? | Carreras por ruta, promedio de pasajeros por viaje, validaciones por día | Semanal |
| Área de Planeamiento | Análisis y proyección | ¿Cómo evoluciona la demanda y qué patrones presenta? | Serie temporal de validaciones, patrón semanal, ranking de paraderos | Mensual |
| Área Comercial y Recaudación | Control de ingresos | ¿Cuál es el ticket promedio y la composición del pasaje? | Ticket promedio, distribución por tipo de pasaje, ingreso por ruta | Mensual |
| Área de Tecnología | Soporte de la solución | ¿Está el proceso de carga ejecutándose correctamente? | Conciliación de totales, volumetría cargada | Por ejecución |

**Tabla F2. Requerimientos funcionales**

| Código | Requerimiento | Criterio de aceptación |
|---|---|---|
| RF-01 | El Data Mart debe consolidar el histórico completo del período disponible | Rango de fechas cargado igual al rango del origen |
| RF-02 | La carga debe ser verificable por conciliación contra el origen | Diferencia entre total de origen y total del Data Mart igual a cero |
| RF-03 | El modelo debe permitir el análisis por ruta, sentido, paradero, franja horaria y tipo de pasaje | Todas las dimensiones presentes y relacionadas |
| RF-04 | El dashboard debe exponer los seis indicadores definidos | Indicadores visibles y reactivos a los filtros |
| RF-05 | El dashboard debe permitir filtrado por ruta y por período | Segmentadores funcionales |
| RF-06 | El dashboard debe ofrecer navegación jerárquica en la dimensión temporal | Jerarquía Año, Trimestre, Mes, Semana, Día operativa |
| RF-07 | El módulo de IA debe generar un análisis en lenguaje natural del corte seleccionado | Respuesta generada a partir de los indicadores reales del corte |
| RF-08 | El módulo de IA debe permitir seleccionar ruta y período | Controles de selección funcionales |
| RF-09 | La solución debe ser portátil y ejecutable sin instalación de motor de base de datos | Módulo operativo con extracto SQLite |

**Tabla F3. Requerimientos no funcionales**

| Código | Categoría | Requerimiento |
|---|---|---|
| RNF-01 | Rendimiento | El proceso ETL completo debe ejecutarse en un tiempo compatible con una ventana de carga nocturna |
| RNF-02 | Rendimiento | Las consultas del dashboard deben responder de forma interactiva |
| RNF-03 | Integridad | El modelo debe declarar restricciones de clave foránea entre hechos y dimensiones |
| RNF-04 | Reproducibilidad | El entorno debe ser reconstruible desde cero mediante contenedores y scripts versionados |
| RNF-05 | Seguridad | Ninguna credencial debe residir en el repositorio ni en código ejecutado en el cliente |
| RNF-06 | Privacidad | El Data Mart no debe contener atributos que identifiquen directa o indirectamente a personas naturales |
| RNF-07 | Portabilidad | La demostración debe ser ejecutable en un equipo distinto al de desarrollo |
| RNF-08 | Trazabilidad | Las transformaciones deben conservar el valor de origen cuando ello no comprometa la privacidad |

---

## ANEXO G. Estructura y perfilado de la fuente de datos

Detalle de respaldo de la sección 3.6.

**Tabla G1. Estructura de la tabla fuente `VALIDACIONES`**

| Columna | Tipo | Descripción | Tratamiento en el Data Mart |
|---|---|---|---|
| `id` | BIGINT | Identificador único de la validación | No se propaga (grano agregado) |
| `fecha` | DATE | Fecha de la validación | Alimenta `dim_tiempo` |
| `hora` | TIME | Hora de la validación | Alimenta `dim_franja_horaria` |
| `placa` | VARCHAR | Placa de la unidad | **Excluido** por minimización de datos |
| `id_conductor` | INT | Identificador del conductor | **Excluido** por minimización de datos |
| `servicio` | INT | Turno de servicio | No incorporado (fuera de alcance) |
| `sentido` | VARCHAR | Sentido del recorrido (NS, SN, IDA, VUELTA, EO, OE) | Normalizado en `dim_sentido` |
| `paradero` | VARCHAR | Nombre del paradero (texto libre) | Normalizado en `dim_paradero` |
| `orden` | INT | Posición de la parada en el recorrido | No incorporado |
| `zona` | VARCHAR | Zona del paradero (texto libre) | Atributo de `dim_paradero` |
| `id carrera` | BIGINT | Identificador de la carrera. **El nombre contiene un espacio** | Clave de `fact_carrera` |
| `monto` | DECIMAL | Monto cobrado | Medida y base de derivación del tipo de pasaje |
| `id_ruta` | TINYINT | Clave foránea al catálogo de rutas | Alimenta `dim_ruta` |

**Tabla G2. Cardinalidades de la fuente**

| Entidad | Cardinalidad |
|---|---|
| Rutas | 22 |
| Paraderos | 170 |
| Zonas (nomenclatura original) | 26 |
| Unidades de flota | 160 |
| Conductores | 335 |
| Carreras ejecutadas | 310,678 |

**Tabla G3. Resultados del perfilado de calidad**

| Dimensión de calidad | Atributo evaluado | Resultado | Acción adoptada |
|---|---|---|---|
| Completitud | `monto` | 0.56% de valores nulos | Clasificación como "Sin dato", sin descarte del registro |
| Completitud | `paradero`, `zona` | Valores vacíos en proporción marginal | Imputación con la etiqueta "SIN ZONA" |
| Completitud | `fecha`, `hora`, `id_ruta` | Sin nulos | Ninguna |
| Consistencia | `sentido` | Seis codificaciones distintas para dos conceptos | Normalización a Ida y Vuelta conservando el código de origen |
| Consistencia | `paradero` | Texto libre con variantes de escritura | Normalización y consolidación en dimensión |
| Unicidad | `id` | Clave primaria sin duplicados | Ninguna |
| Validez | `id_ruta` | Integridad referencial contra `2op_ruta` verificada | Ninguna |
| Validez | `monto` | Valores dentro del rango tarifario esperado | Ninguna |

---

## ANEXO H. Detalle técnico de la arquitectura y del proceso ETL

Detalle de respaldo de las secciones 4.1, 6.1, 6.3 y 6.7.

**Tabla H1. Capas de la arquitectura y sus componentes**

| Capa | Componente | Tecnología | Función |
|---|---|---|---|
| 1. Fuente | Sistema OLTP embarcado (copia) | MariaDB 11.4 sobre Docker | Aloja la tabla `VALIDACIONES` y el catálogo `2op_ruta` |
| 2. Integración | Proceso ETL | Python 3, pandas, SQLAlchemy, PyMySQL | Extrae, transforma, deriva, agrega y carga |
| 2b. Integración | Área de staging | Tabla `stg_tarifa_ref` en MariaDB | Materializa la tarifa de referencia por ruta y mes |
| 3. Almacenamiento | Data Mart `transporte_dm` | MariaDB 11.4, motor InnoDB | Modelo dimensional en constelación |
| 3b. Almacenamiento | Extracto portátil | SQLite | Copia de los agregados para el módulo de IA |
| 4. Servicio | Modelo semántico | Power BI Desktop, medidas DAX | Define indicadores, relaciones y jerarquías |
| 4b. Servicio | Módulo de interpretación | PHP 8, cURL | Compone el contexto cuantitativo y consume la API |
| 5. Consumo | Dashboard interactivo | Power BI Desktop y Power BI Service | Visualización analítica |
| 5b. Consumo | Página de análisis con IA | Navegador web | Presenta la lectura interpretada en lenguaje natural |
| Externo | Servicio de IA generativa | Google Gemini API, modelo `gemini-2.0-flash` | Genera la narrativa analítica |

**Tabla H2. Etapas del proceso ETL**

| N° | Etapa | Estrategia de ejecución | Salida |
|---|---|---|---|
| 1 | Conexión y verificación del origen | SQLAlchemy sobre PyMySQL | Sesión activa contra el motor |
| 2 | Construcción de `dim_tiempo` | Generación programática en pandas | 391 filas |
| 3 | Construcción de `dim_ruta` | Extracción del catálogo y derivación del tipo | 22 filas |
| 4 | Construcción de `dim_paradero` | Valores distintos y asignación de zona predominante | 170 filas |
| 5 | Construcción de `dim_sentido` | Valores distintos y normalización | 6 filas |
| 6 | Construcción de `dim_franja_horaria` | Generación programática con marcado de horas punta | 24 filas |
| 7 | Cálculo de tarifa de referencia | Consulta de agregación al motor | `stg_tarifa_ref` |
| 8 | Construcción de `dim_tipo_pasaje` | Carga del catálogo derivado | 4 filas |
| 9 | Carga de `fact_validacion_diaria` | Push-down SQL (INSERT ... SELECT ... GROUP BY) | 1,305,099 filas |
| 10 | Carga de `fact_carrera` | Push-down SQL (INSERT ... SELECT ... GROUP BY) | 310,678 filas |
| 11 | Conciliación y perfilado | Consultas de verificación | Reporte de control |

**Tabla H3. Catálogo de transformaciones del proceso ETL**

| Tipo de transformación | Atributo o entidad | Descripción de la regla |
|---|---|---|
| Generación | `dim_tiempo` | Calendario continuo con descomposición en año, trimestre, mes, semana ISO, día y día de la semana |
| Derivación | `dim_tiempo.es_fin_semana` | Marcado de sábados y domingos |
| Derivación | `dim_ruta.tipo_ruta` | Clasificación del servicio a partir del patrón del código de ruta |
| Normalización | `dim_sentido.sentido` | Reducción de seis codificaciones a Ida y Vuelta |
| Trazabilidad | `dim_sentido.codigo_origen` | Conservación del código original |
| Estandarización | `dim_paradero.nombre_paradero` | Limpieza y unificación del texto libre |
| Imputación | `dim_paradero.zona` | Sustitución de vacíos por "SIN ZONA" |
| Cálculo estadístico | `stg_tarifa_ref.tarifa_adulto` | Moda del monto por ruta y mes |
| Clasificación por reglas | `dim_tipo_pasaje` | Asignación del tipo según el monto y la tarifa de referencia |
| Agregación | `fact_validacion_diaria` | Conteo y suma agrupados por el grano de seis dimensiones |
| Agregación | `fact_carrera` | Conteo y suma agrupados por identificador de carrera |
| Sustitución de claves | Ambos hechos | Reemplazo de atributos descriptivos por claves de dimensión |

**Tabla H4. Controles de calidad ejecutados sobre el Data Mart**

| N° | Control | Resultado |
|---|---|---|
| C-01 | Conteo de validaciones origen contra destino | Diferencia cero |
| C-02 | Suma de montos origen contra destino | Coincidente |
| C-03 | Conteo de carreras origen contra `fact_carrera` | 310,678 coincidente |
| C-04 | Rango de fechas origen contra `dim_tiempo` | Coincidente |
| C-05 | Fechas distintas con hechos origen contra destino | 193 coincidente |
| C-06 | Cardinalidad de dimensiones contra valores distintos del origen | Coincidente en las seis dimensiones |
| C-07 | Ausencia de claves foráneas huérfanas | Sin violaciones |
| C-08 | Suma de participaciones por tipo de pasaje | 100.00% |
| C-09 | Verificación de la tarifa de referencia contra los valores tarifarios esperados | Consistente, con detección del ajuste de S/ 2.40 a S/ 2.45 |

---

## ANEXO I. Especificación del dashboard

Detalle de respaldo de las secciones 7.2 y 7.3.

**Tabla I1. Composición del dashboard por página**

| Página | N° | Visual | Tipo | Información que comunica | Justificación del tipo elegido |
|---|---|---|---|---|---|
| 1. Visión general | V-01 | Total de validaciones | Tarjeta | Volumen total de abordajes del corte | El valor único no requiere mediación gráfica |
| 1. Visión general | V-02 | Ingreso total | Tarjeta | Recaudación del corte | Idem |
| 1. Visión general | V-03 | Promedio de pasajeros por viaje | Tarjeta | Ocupación media por carrera | Idem |
| 1. Visión general | V-04 | Ticket promedio | Tarjeta | Recaudación media por abordaje | Idem |
| 1. Visión general | V-05 | Validaciones por franja horaria | Columnas ordenadas por hora | Perfil de demanda a lo largo del día | El orden natural del ciclo horario debe preservarse |
| 1. Visión general | V-06 | Distribución por tipo de pasaje | Barras horizontales con etiqueta de porcentaje | Composición de la demanda | Evita la comparación angular imprecisa del gráfico de sectores |
| 1. Visión general | V-07 | Ranking de rutas por ingreso | Barras horizontales descendentes | Contribución de cada ruta | El orden descendente comunica el ranking de forma directa |
| 1. Visión general | V-08 | Segmentador de ruta | Segmentador de lista | Filtrado por ruta | Cardinalidad baja (22 elementos) apta para lista |
| 1. Visión general | V-09 | Segmentador de período | Segmentador de rango de fechas | Filtrado temporal | Permite selección de intervalos arbitrarios |
| 1. Visión general | V-10 | Segmentador de tipo de pasaje | Segmentador de botones | Filtrado por tipo | Cardinalidad muy baja (4 elementos) apta para botones |
| 2. Ruta y paradero | V-11 | Matriz de ruta por franja horaria | Matriz con formato condicional | En qué franja concentra su demanda cada ruta | Máxima densidad informativa por unidad de pantalla |
| 2. Ruta y paradero | V-12 | Ranking de paraderos por validaciones | Barras horizontales descendentes con filtro de los primeros N | Paraderos de mayor afluencia | El orden facilita la lectura del ranking |
| 2. Ruta y paradero | V-13 | Distribución por zona | Barras horizontales | Agregación geográfica de la demanda | La orientación horizontal acomoda etiquetas de texto |
| 2. Ruta y paradero | V-14 | Comparación de sentido | Columnas agrupadas | Contraste entre Ida y Vuelta por ruta | Permite comparación pareada directa |
| 2. Ruta y paradero | V-15 | Tabla de detalle por ruta | Tabla | Cifras exactas de validaciones, ingreso, carreras y promedio | El tercer nivel de lectura requiere el valor exacto |
| 3. Análisis temporal | V-16 | Validaciones por día con registro | Línea sobre calendario continuo | Evolución diaria, con los períodos sin cobertura visibles como vacíos | La continuidad del eje evita sugerir una serie inexistente |
| 3. Análisis temporal | V-17 | Días con registro por mes | Columnas | Cuantifica la cobertura de la fuente por mes | Acompaña a los visuales temporales como control de lectura |
| 3. Análisis temporal | V-18 | Validaciones promedio por día con registro | Columnas | Comparación normalizada entre meses | Único visual autorizado para comparación intermensual |
| 3. Análisis temporal | V-19 | Demanda por día de la semana | Columnas ordenadas de lunes a domingo | Patrón semanal de la demanda dentro del alcance laborable | Preserva el orden natural del ciclo semanal |
| 3. Análisis temporal | V-20 | Comparación laboral contra fin de semana | Barras | Contraste entre ambos regímenes | Se conserva por completitud estructural; su lectura está limitada por la cobertura descrita en la sección 6.6.2 |

**Tabla I2. Correspondencia entre operaciones OLAP e interacciones del dashboard**

| Operación OLAP | Interacción disponible | Ejemplo de uso |
|---|---|---|
| *Roll-up* | Botón de ascenso en la jerarquía temporal | Pasar de la demanda diaria a la demanda trimestral |
| *Drill-down* | Botón de descenso o clic sobre un elemento de la jerarquía | Pasar de un mes a sus días, y de un día a sus franjas horarias |
| *Slice* | Segmentador de ruta | Analizar exclusivamente la ruta R-01 |
| *Dice* | Combinación de segmentadores | Rutas R-01 y R-05, en tipo de pasaje Adulto, durante el tercer trimestre |
| *Pivot* | Intercambio de campos en filas y columnas de la matriz | Observar franjas por ruta en lugar de rutas por franja |
| Filtrado cruzado | Clic sobre una barra de un visual | Seleccionar una ruta en el ranking y observar cómo se recalcula el perfil horario |

---

## ANEXO J. Evaluación de atributos bajo el criterio de minimización

Detalle de respaldo de la sección 10.2.

**Tabla J1. Evaluación de atributos de la fuente bajo el criterio de minimización**

| Atributo | ¿Identifica a una persona? | ¿Necesario para la finalidad? | Decisión |
|---|---|---|---|
| `fecha`, `hora` | No | Sí, esencial | Incorporado |
| `id_ruta` | No | Sí, esencial | Incorporado |
| `sentido` | No | Sí | Incorporado |
| `paradero`, `zona` | No | Sí | Incorporado |
| `monto` | No | Sí, esencial | Incorporado |
| `id carrera` | No | Sí, para el indicador de ocupación | Incorporado |
| `placa` | Sí, indirectamente | No | **Excluido** |
| `id_conductor` | Sí, directamente | No | **Excluido** |
| `servicio` | No, pero se asocia a turnos de personal | No, fuera de alcance | Excluido |
| `orden` | No | No aporta al análisis | Excluido |
| `id` | No | No, el grano es agregado | Excluido |

---

## ANEXO K. Desarrollo detallado de las limitaciones del estudio

Desarrollo completo de las limitaciones enunciadas en la sección 10.5.

### K.1. L1. el tipo de pasaje es derivado, no declarado

El sistema fuente no registra el tipo de pasajero, y la dimensión `dim_tipo_pasaje` se construye por inferencia a partir del monto aplicando las reglas de la sección 5.5. Se trata, por tanto, de una **clasificación inferida**, sujeta a error de clasificación: producirían asignaciones incorrectas un cobro erróneo del validador, un ajuste tarifario que el cálculo de la moda no capture con exactitud dentro del mes de transición, una tarifa promocional o diferenciada no contemplada, o un pago parcial por saldo insuficiente en el medio de pago. La magnitud del error no es cuantificable con la información disponible, por cuanto no existe una fuente independiente contra la cual contrastar la clasificación, si bien se estima reducida en atención a la nitidez de la estructura tarifaria observada y a la consistencia de la moda detectada por ruta y mes. La limitación afecta al indicador KPI 4 y, por derivación, al análisis del ticket promedio, pero no a los indicadores de demanda total, concentración horaria ni participación por ruta, que son independientes de esta clasificación.

### K.2. L2. escolar y universitario no son distinguibles

Los estudiantes de educación básica y de educación superior abonan la misma tarifa preferencial, y el monto, único atributo disponible, no permite separarlos. El estudio informa en consecuencia una categoría "Medio" que agrupa ambos segmentos, de modo que **no es posible dimensionar la demanda escolar de forma independiente de la universitaria**. Cualquier decisión que requiera esa distinción, por ejemplo el diseño de servicios ajustados al calendario escolar, no puede sustentarse en esta información. Conforme a lo expuesto en la sección 5.5.3, el indicador fue reformulado de "tipo de pasajero" a "tipo de pasaje", de modo que su denominación no afirme más de lo que los datos sostienen.

### K.3. L3. la validación mide abordajes, no personas únicas

Esta es la limitación conceptualmente más relevante del estudio. Una validación registra un **abordaje**, es decir, el acto de subir a una unidad, y no registra a una persona: un pasajero que realiza un trasbordo genera dos validaciones, y quien efectúa un viaje de ida y otro de retorno en el mismo día genera dos validaciones adicionales.

El indicador "total de validaciones" mide, por tanto, **volumen de abordajes** y no **número de pasajeros únicos**: la cifra de 11,737,931 validaciones no significa que once millones setecientas mil personas hayan utilizado el sistema. El término "demanda" debe entenderse en este estudio como **demanda de abordajes**, y cualquier lectura que interprete las cifras como número de usuarios distintos sería incorrecta. Del mismo modo, el indicador de pasajeros por viaje mide abordajes por carrera, magnitud correcta para dimensionar la ocupación de la unidad, que es su finalidad, pero que no debe leerse como número de personas distintas atendidas.

La corrección exigiría un identificador del medio de pago que permitiera vincular validaciones sucesivas de un mismo usuario. Tal identificador no está disponible en la fuente y, de estarlo, su incorporación habría requerido un análisis específico de protección de datos, por cuanto permitiría reconstruir patrones de desplazamiento individuales, información de sensibilidad considerablemente mayor que la tratada en este proyecto.

### K.4. L4. registros sin monto informado

El 0.56% de las validaciones, equivalente a 65,712 registros, carece de monto en el origen. Se clasificaron como "Sin dato" y se conservaron en el conteo de demanda conforme al fundamento expuesto en la sección 6.5, de modo que cuentan para la demanda pero no aportan ingreso. Existen dos escenarios posibles: que el abordaje efectivamente no generara cobro, en cuyo caso el tratamiento es correcto, o que el cobro se produjera y no quedara registrado, en cuyo caso el ingreso total se encuentra **subestimado** en una magnitud acotada superiormente por 65,712 multiplicado por la tarifa aplicable, es decir, en torno al 0.6% del ingreso total. La magnitud del efecto es inferior al margen de error aceptable para las decisiones que el estudio pretende informar, y se declara para que el usuario pueda ponderarla.

### K.5. L5. cobertura temporal parcial de la fuente

Esta limitación se identificó durante el control de calidad y se documenta técnicamente en la sección 6.6.1. Su formulación precisa es la siguiente: **la fuente cubre 193 de los 391 días del período, esto es, el 49.36% del calendario; el mes de diciembre de 2025 está ausente en su totalidad; y la cobertura mensual restante es irregular, oscilando entre un único día en marzo de 2025 y veintitrés días en abril y julio de 2025.**

El efecto es asimétrico y debe precisarse con exactitud. **No son sostenibles con esta fuente** las afirmaciones sobre **estacionalidad** de la demanda, pues con diciembre ausente y meses de cobertura mínima no es posible caracterizar el ciclo anual; las afirmaciones sobre **tendencia** o **evolución mensual**, dado que las diferencias entre totales mensuales miden cobertura del registro y no comportamiento del sistema; cualquier **comparación interanual**, imposible además por cuanto el período abarca trece meses y no dos ciclos completos; y cualquier afirmación sobre el efecto de eventos localizados en el tiempo, como festividades o períodos vacacionales. **Sí son sostenibles** la distribución por tipo de pasaje, la concentración horaria de la demanda, la participación de cada ruta en el ingreso, el ticket promedio y el promedio de pasajeros por viaje, todos ellos calculados sobre las 11,737,931 validaciones observadas.

El fundamento de esta distinción es que los indicadores estructurales son proporciones calculadas sobre el conjunto de días efectivamente registrados, y su validez depende de que la muestra de días observados sea suficientemente amplia y no sistemáticamente sesgada respecto de la estructura que se pretende caracterizar; con 193 días y casi doce millones de validaciones, la base de observación es amplia. Los indicadores de evolución temporal, en cambio, dependen de que la muestra sea temporalmente representativa, condición que la cobertura observada no satisface. Se advierte como reserva adicional que se desconoce el criterio con que se seleccionaron los días registrados, y de la posibilidad de un sesgo residual asociado se deja constancia expresa.

### K.6. L6. el alcance se restringe a la demanda laborable

Esta delimitación, documentada en la sección 6.6.2, es de naturaleza distinta de la anterior y de consecuencia más precisa. **La cobertura de la fuente corresponde a la operación de días laborables**: de lunes a viernes se registran entre 34 y 41 días con volúmenes del orden de los dos millones de validaciones en cada día de la semana, mientras que el sábado presenta ocho días de registro con únicamente 466 validaciones y el domingo está enteramente ausente.

En consecuencia, **el estudio caracteriza la demanda laborable y ninguna de sus conclusiones puede extenderse al régimen de fin de semana**. El perfil horario de doble pico, la composición por tipo de pasaje, el ticket promedio, la ocupación media por carrera y la participación de cada ruta en el ingreso son propiedades de la operación laborable. El comportamiento de sábado y domingo, que en sistemas de transporte urbano suele diferir de forma significativa tanto en volumen como en distribución horaria y composición tarifaria, no está representado en la fuente y no puede inferirse a partir de ella.

Se subraya que esta es una **delimitación del alcance y no un defecto del análisis**: los indicadores presentados son válidos y precisos dentro del universo que la fuente representa. La medida adoptada consiste en declarar explícitamente ese universo en la sección 9.1, en excluir del análisis toda comparación entre regímenes semanales, y en incorporar la ampliación de la cobertura al fin de semana como línea de trabajo futuro.

### K.7. L7 a L9

**Limitación 7: horizonte insuficiente para el análisis interanual.** Con independencia de la cobertura, el período abarca trece meses. Aun con cobertura diaria completa, tal horizonte sería insuficiente para caracterizar estacionalidad interanual, que exige al menos dos ciclos anuales completos para distinguir el patrón estacional de la variación coyuntural.

**Limitación 8: ausencia de datos de costo.** El estudio analiza demanda e ingreso y **no incorpora información de costos operativos**, por lo que no es posible determinar la rentabilidad de ruta alguna: una ruta de bajo ingreso puede ser rentable si su costo es proporcionalmente menor. Esta limitación acota expresamente el alcance de la recomendación R2 de la sección 9.5.3: el estudio identifica las rutas de menor aporte al ingreso, pero **no concluye que deban discontinuarse**.

**Limitación 9: efecto de la perturbación sobre las magnitudes absolutas.** Conforme a lo expuesto en la sección 10.3.2, las magnitudes absolutas presentadas no corresponden a la operación real del operador, mientras que los ratios, proporciones y órdenes sí se conservan exactamente. Toda cifra absoluta de este informe debe leerse como magnitud de escala representativa y no como cifra operativa del operador real.


---

## ANEXO L. Índice de tablas

### K.1. Tablas del cuerpo del informe

| N° | Título | Sección |
|---|---|---|
| 1 | Comparación entre sistemas OLTP y sistemas OLAP | 2.2 |
| 2 | Matriz de bus del Data Mart de validaciones | 3.2 |
| 3 | Indicadores clave de desempeño y decisiones asociadas | 3.4 |
| 4 | Medidas de las tablas de hechos del modelo | 5.2 |
| 5 | Resumen de las dimensiones del Data Mart | 5.4 |
| 6 | Reglas de derivación del tipo de pasaje | 5.5 |
| 7 | Evaluación comparativa de los esquemas dimensionales | 5.6 |
| 8 | Política de tratamiento de datos incompletos | 6.5 |
| 9 | Cobertura temporal de la fuente por mes | 6.6.1 |
| 10 | Cobertura de la fuente por día de la semana | 6.6.2 |
| 11 | Medidas de gestión de la credencial | 8.3 |
| 12 | Indicadores globales del período analizado | 9.2 |
| 13 | Distribución de la demanda y del ingreso por tipo de pasaje | 9.3 |
| 14 | Descomposición del ticket promedio por segmento tarifario | 9.3.1 |
| 15 | Ranking de rutas por ingreso generado | 9.5 |
| 16 | Síntesis de hallazgos, evidencia y acción asociada | 9.7 |
| 17 | Principios de la Ley N° 29733 aplicados al proyecto | 10.1 |
| 18 | Sustituciones aplicadas por supresión de identificadores | 10.3.1 |
| 19 | Efecto de la perturbación multiplicativa sobre los indicadores | 10.3.2 |
| 20 | Síntesis de limitaciones del estudio | 10.5.8 |
| 21 | Líneas de evolución de la solución | 11.5 |

### K.2. Tablas de los anexos

| N° | Título | Anexo |
|---|---|---|
| E1 | Correspondencia entre tipo de análisis y visualización recomendada | E |
| F1 | Matriz de interesados y sus necesidades de información | F |
| F2 | Requerimientos funcionales | F |
| F3 | Requerimientos no funcionales | F |
| G1 | Estructura de la tabla fuente `VALIDACIONES` | G |
| G2 | Cardinalidades de la fuente | G |
| G3 | Resultados del perfilado de calidad | G |
| H1 | Capas de la arquitectura y sus componentes | H |
| H2 | Etapas del proceso ETL | H |
| H3 | Catálogo de transformaciones del proceso ETL | H |
| H4 | Controles de calidad ejecutados sobre el Data Mart | H |
| I1 | Composición del dashboard por página | I |
| I2 | Correspondencia entre operaciones OLAP e interacciones del dashboard | I |
| J1 | Evaluación de atributos de la fuente bajo el criterio de minimización | J |

---

## ANEXO M. Índice de figuras

| N° | Descripción | Ubicación |
|---|---|---|
| 1 | Diagrama de arquitectura general de la solución | Sección 4.1 |
| 2 | Diagrama entidad-relación del modelo dimensional | Sección 5.3.2 |
| 3 | Diagrama de flujo del proceso ETL | Sección 6.1 |
| 4 | Días con registro por mes | Sección 6.6.1 |
| 5 | Salida por consola de la ejecución del ETL con la conciliación | Sección 6.7 |
| 6 | Vista de modelo de Power BI | Sección 7.1 |
| 7 | Página 1 del dashboard: visión general de la demanda | Sección 7.2 |
| 8 | Página 2 del dashboard: análisis por ruta y paradero | Sección 7.2 |
| 9 | Página 3 del dashboard: análisis temporal | Sección 7.2 |
| 10 | Informe publicado en Power BI Service | Sección 7.4 |
| 11 | Interfaz del módulo de IA | Sección 8.1 |
| 12 | Análisis generado por el modelo | Sección 8.1 |
| 13 | Diagrama de secuencia de la integración con la API | Sección 8.2 |
| 14 | Archivo `.env.example` y `.gitignore` | Sección 8.3 |
| 15 | Banda de tarjetas de indicadores globales | Sección 9.2 |
| 16 | Gráfico de distribución por tipo de pasaje | Sección 9.3 |
| 17 | Gráfico de validaciones por franja horaria | Sección 9.4 |
| 18 | Gráfico de ranking de rutas por ingreso | Sección 9.5 |
| 19 | Gráfico de validaciones promedio por día con registro | Sección 9.6 |
| 20 | Archivo de configuración del módulo de IA | Anexo C |
| 21 | Ejecución en vivo del módulo de IA | Anexo C |

---

*Fin del documento.*
