<?php
/**
 * Consulta en lenguaje natural sobre el Data Mart (texto a SQL).
 *
 * Flujo:
 *   1. Se le entrega al modelo el esquema del extracto y la pregunta.
 *   2. El modelo devuelve UNA consulta SELECT.
 *   3. La consulta se VALIDA antes de ejecutarse (ver validar_sql).
 *   4. Se ejecuta sobre una conexion de solo lectura.
 *   5. El resultado vuelve al modelo, que redacta la respuesta.
 *
 * Por que la validacion no es opcional
 * ------------------------------------
 * El SQL que llega aqui lo escribio un modelo de lenguaje a partir de un texto
 * que tecleo el usuario. Es, por definicion, codigo de origen no confiable.
 * Ejecutarlo sin filtrar permitiria que una pregunta manipulada terminara
 * borrando o alterando datos. Por eso se aplican tres barreras independientes:
 *
 *   a) La conexion se abre en modo SOLO LECTURA a nivel de driver.
 *   b) Solo se admite una sentencia, y debe empezar por SELECT o WITH.
 *   c) Se rechaza cualquier palabra clave de escritura o de administracion.
 *
 * Cualquiera de las tres bastaria; estan las tres porque una defensa en capas
 * no depende de que ninguna sea perfecta.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/gemini.php';

/** Filas maximas que se devuelven al modelo, para no desbordar el contexto. */
const LIMITE_FILAS = 200;

/**
 * Descripcion del esquema que se le entrega al modelo.
 * Se mantiene aqui, y no se genera dinamicamente, para poder anotar cada tabla
 * con el significado de negocio que el modelo necesita para elegir bien.
 */
function esquema_para_modelo(): string
{
    return <<<'ESQUEMA'
Base de datos SQLite con el extracto del Data Mart de validaciones de un
operador de transporte publico urbano. Una VALIDACION es un abordaje: cada vez
que un pasajero sube a un bus y paga. Una CARRERA es un viaje completo de una
unidad en un sentido.

TABLAS (todas ya agregadas por mes; no existe el detalle diario):

kpi_mensual_ruta(anio_mes TEXT 'YYYY-MM', codigo_ruta TEXT, tipo_ruta TEXT,
                 validaciones INTEGER, ingreso REAL, dias_con_dato INTEGER)
    Demanda e ingreso por ruta y mes. dias_con_dato indica cuantos dias de ese
    mes tienen registro.

kpi_mensual_tipo_pasaje(anio_mes, codigo_ruta, tipo TEXT, validaciones, ingreso)
    tipo puede ser: 'Adulto', 'Medio', 'Gratuito', 'Sin dato'.

kpi_mensual_hora(anio_mes, codigo_ruta, hora INTEGER 0-23, hora_texto TEXT,
                 franja TEXT, es_hora_punta INTEGER 0/1, validaciones, ingreso)
    Demanda por hora del dia. es_hora_punta marca las franjas 6-8h y 17-19h.

kpi_mensual_paradero(anio_mes, codigo_ruta, paradero TEXT, zona TEXT,
                     validaciones, ingreso)
    Demanda por paradero. zona agrupa paraderos por sector.

kpi_mensual_dia_semana(anio_mes, codigo_ruta, dia_semana INTEGER 1=Lunes,
                       dia_nombre TEXT, es_fin_semana INTEGER 0/1,
                       validaciones, ingreso)

kpi_mensual_carrera(anio_mes, codigo_ruta, carreras INTEGER,
                    pasajeros INTEGER, ingreso REAL)
    Para el promedio de pasajeros por viaje: SUM(pasajeros)/SUM(carreras).

REGLAS DE NEGOCIO IMPORTANTES:
- Los codigos de ruta son 'R-01', 'R-02', ..., y tambien 'AUX R-01', 'ESCOLAR',
  'ESPECIAL-01', 'ND'. Los paraderos son 'P-001' a 'P-170'.
- El periodo disponible es 2025-02 a 2026-02. Diciembre de 2025 NO tiene datos.
- La cobertura es PARCIAL: solo 193 de 391 dias tienen registro, y casi todos
  son dias laborables. Por eso NUNCA se comparan totales mensuales directos:
  para comparar meses hay que dividir entre dias_con_dato.
- El ticket promedio es SUM(ingreso)/SUM(validaciones).
ESQUEMA;
}

/**
 * Valida que el SQL generado sea una consulta de solo lectura.
 *
 * @throws RuntimeException si la sentencia no es admisible.
 */
function validar_sql(string $sql): string
{
    $sql = trim($sql);

    // Se retira el punto y coma final, si lo hay.
    $sql = rtrim($sql, "; \t\n\r");

    if ($sql === '') {
        throw new RuntimeException('El modelo no devolvio ninguna consulta.');
    }

    // Una sola sentencia: un punto y coma en el medio indicaria dos.
    if (str_contains($sql, ';')) {
        throw new RuntimeException(
            'Solo se admite una consulta por pregunta.');
    }

    // Debe ser una lectura.
    if (!preg_match('/^\s*(SELECT|WITH)\b/i', $sql)) {
        throw new RuntimeException(
            'Solo se permiten consultas de lectura (SELECT).');
    }

    // Palabras clave que no tienen cabida en una consulta de lectura.
    $prohibidas = [
        'INSERT', 'UPDATE', 'DELETE', 'DROP', 'CREATE', 'ALTER', 'REPLACE',
        'TRUNCATE', 'ATTACH', 'DETACH', 'PRAGMA', 'VACUUM', 'REINDEX',
        'GRANT', 'REVOKE',
    ];
    foreach ($prohibidas as $palabra) {
        if (preg_match('/\b' . $palabra . '\b/i', $sql)) {
            throw new RuntimeException(
                "La consulta contiene una operacion no permitida ($palabra).");
        }
    }

    // Techo de filas, por si el modelo lo omitio.
    if (!preg_match('/\bLIMIT\s+\d+/i', $sql)) {
        $sql .= ' LIMIT ' . LIMITE_FILAS;
    }

    return $sql;
}

/** Conexion de SOLO LECTURA al extracto. */
function conectar_solo_lectura(): PDO
{
    if (!is_readable(RUTA_SQLITE)) {
        throw new RuntimeException('No se encuentra el extracto de datos.');
    }
    // El modo ro se declara en el propio DSN: aunque una consulta lograra
    // burlar la validacion, el driver rechazaria cualquier escritura.
    $pdo = new PDO('sqlite:' . RUTA_SQLITE, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA query_only = ON');
    return $pdo;
}

/** Pide al modelo la consulta SQL correspondiente a la pregunta. */
function generar_sql(string $pregunta): string
{
    $prompt  = "Eres un analista de datos. Traduce la pregunta del usuario a UNA "
             . "consulta SQL de SQLite sobre este esquema.\n\n";
    $prompt .= esquema_para_modelo();
    $prompt .= "\n\nPREGUNTA DEL USUARIO:\n" . $pregunta;
    $prompt .= "\n\nREGLAS DE RESPUESTA\n";
    $prompt .= "- Devuelve UNICAMENTE la consulta SQL. Sin explicacion, sin comentarios, "
             . "sin bloques de codigo markdown.\n";
    $prompt .= "- Una sola sentencia SELECT. Nunca INSERT, UPDATE, DELETE ni DDL.\n";
    $prompt .= "- Incluye siempre LIMIT (maximo " . LIMITE_FILAS . ").\n";
    $prompt .= "- Usa alias legibles en las columnas del resultado.\n";
    $prompt .= "- Si la pregunta compara meses, divide entre dias_con_dato.\n";
    $prompt .= "- Si la pregunta pide una RECOMENDACION, un diagnostico o una opinion "
             . "sobre que hacer (por ejemplo: que deberia hacer para mejorar los "
             . "ingresos, como reduzco la congestion, conviene reforzar esta ruta), "
             . "no intentes traducirla a SQL: devuelve exactamente CONSULTIVA\n";
    $prompt .= "- Si la pregunta pide un dato que este esquema no contiene (conductores, "
             . "unidades, combustible, costos), devuelve exactamente: NO_RESPONDIBLE\n";

    $sql = analizar_con_gemini($prompt);

    // El modelo suele envolver el SQL en un bloque de codigo pese a la instruccion.
    $sql = preg_replace('/^```(?:sql)?\s*|\s*```$/im', '', trim($sql));

    return trim($sql);
}

/** Pide al modelo que redacte la respuesta a partir de los datos obtenidos. */
function redactar_respuesta(string $pregunta, string $sql, array $filas): string
{
    $prompt  = "Eres un analista de Inteligencia de Negocios de un operador de "
             . "transporte publico urbano peruano. Respondes a la gerencia.\n\n";
    $prompt .= "PREGUNTA: " . $pregunta . "\n\n";
    $prompt .= "CONSULTA EJECUTADA:\n" . $sql . "\n\n";
    $prompt .= "RESULTADO (" . count($filas) . " fila(s)):\n";

    if (!$filas) {
        $prompt .= "(sin filas)\n";
    } else {
        $prompt .= implode(" | ", array_keys($filas[0])) . "\n";
        foreach (array_slice($filas, 0, 60) as $f) {
            $prompt .= implode(" | ", array_map(
                fn($v) => is_numeric($v) ? (string) $v : (string) $v, $f)) . "\n";
        }
    }

    $prompt .= "\nREDACTA LA RESPUESTA\n";
    $prompt .= "- Responde la pregunta de forma directa, en dos a cinco frases.\n";
    $prompt .= "- Cita SIEMPRE las cifras del resultado. No inventes ninguna.\n";
    // El modelo tiende a poner el simbolo de moneda a cualquier cifra grande,
    // incluidos los conteos. Se distingue explicitamente.
    $prompt .= "- Usa el simbolo S/ SOLO para importes de dinero (ingreso, "
             . "recaudacion, ticket promedio). Las validaciones, pasajeros, "
             . "carreras y dias son CONTEOS: van con separador de miles y sin "
             . "simbolo de moneda.\n";
    $prompt .= "- Si el resultado esta vacio, dilo con claridad y sugiere como reformular.\n";
    $prompt .= "- No uses guiones largos, solo el guion normal.\n";
    $prompt .= "- Si la respuesta depende de comparar meses, recuerda que la cobertura "
             . "de la fuente es parcial y advierte de ello en una frase.\n";

    return analizar_con_gemini($prompt);
}

/**
 * Responde una pregunta de tipo consultivo: la que pide una recomendacion o un
 * diagnostico en lugar de una cifra.
 *
 * No se traduce a SQL porque no hay consulta que devuelva un consejo. En su
 * lugar se le entrega al modelo el mismo contexto cuantitativo que alimenta el
 * analisis ejecutivo, y se le exige que toda afirmacion se apoye en una cifra.
 * De ese modo la recomendacion queda anclada a los datos y no a la intuicion
 * del modelo.
 */
function responder_consultiva(string $pregunta, string $ruta, string $periodo): array
{
    require_once __DIR__ . '/kpis.php';

    $pdo   = conectar_sqlite();
    $datos = obtener_kpis($pdo, $ruta, $periodo);
    $k     = $datos['kpis'];

    $ctx  = "INDICADORES DEL CORTE ANALIZADO";
    $ctx .= ($ruta === 'TODAS' ? " (todas las rutas)" : " (ruta $ruta)");
    $ctx .= ($periodo === 'TODOS' ? ", periodo completo feb-2025 a feb-2026" : ", periodo $periodo");
    $ctx .= "\n";
    $ctx .= "- Validaciones: " . number_format($k['total_validaciones']) . "\n";
    $ctx .= "- Ingreso: S/ " . number_format($k['ingreso_total'], 2) . "\n";
    $ctx .= "- Carreras: " . number_format($k['num_carreras']) . "\n";
    $ctx .= "- Pasajeros por viaje: " . $k['promedio_pasajeros_viaje'] . "\n";
    $ctx .= "- Ticket promedio: S/ " . number_format($k['ticket_promedio'], 2) . "\n";
    $ctx .= "- Demanda en hora punta: " . $k['pct_hora_punta'] . "%\n";
    $ctx .= "- Dias con registro: " . number_format($k['dias_con_dato']) . "\n\n";

    $ctx .= "DISTRIBUCION POR TIPO DE PASAJE\n";
    foreach ($datos['tipo_pasaje'] as $tp) {
        $ctx .= "- {$tp['tipo']}: " . number_format((int) $tp['validaciones'])
              . " validaciones, S/ " . number_format((float) $tp['ingreso'], 2) . "\n";
    }

    $ctx .= "\nDEMANDA POR HORA\n";
    foreach ($datos['por_hora'] as $h) {
        $ctx .= "- {$h['hora_texto']}: " . number_format((int) $h['validaciones'])
              . (((int) $h['es_hora_punta'] === 1) ? " [PUNTA]" : "") . "\n";
    }

    if ($datos['paraderos']) {
        $ctx .= "\nPARADEROS DE MAYOR DEMANDA\n";
        foreach ($datos['paraderos'] as $p) {
            $ctx .= "- {$p['paradero']} ({$p['zona']}): "
                  . number_format((int) $p['validaciones']) . "\n";
        }
    }
    if ($datos['ranking']) {
        $ctx .= "\nRANKING DE RUTAS POR INGRESO\n";
        foreach ($datos['ranking'] as $r) {
            $ctx .= "- {$r['codigo_ruta']}: S/ " . number_format((float) $r['ingreso'], 2)
                  . ", " . number_format((int) $r['validaciones']) . " validaciones\n";
        }
    }

    $prompt  = "Eres un analista de Inteligencia de Negocios de un operador de "
             . "transporte publico urbano peruano. La gerencia te consulta.\n\n";
    $prompt .= "CONSULTA: " . $pregunta . "\n\n";
    $prompt .= $ctx . "\n";
    $prompt .= "RESPONDE ASI\n";
    $prompt .= "- Da una recomendacion concreta y accionable, no generalidades.\n";
    $prompt .= "- CADA afirmacion debe apoyarse en una cifra de las de arriba. Citala.\n";
    $prompt .= "- Entre dos y cuatro puntos, cada uno con su justificacion cuantitativa.\n";
    $prompt .= "- Si los datos disponibles no bastan para sustentar la recomendacion, "
             . "dilo con claridad y senala que dato haria falta.\n";
    $prompt .= "- Usa S/ solo para importes de dinero; las validaciones y carreras "
             . "son conteos.\n";
    $prompt .= "- No uses guiones largos, solo el guion normal.\n";
    $prompt .= "- La cobertura de la fuente es parcial (193 de 391 dias, casi todos "
             . "laborables). No hagas afirmaciones sobre fines de semana ni sobre "
             . "estacionalidad.\n";

    return [
        'pregunta'  => $pregunta,
        'modo'      => 'consultiva',
        'sql'       => null,
        'filas'     => [],
        'n_filas'   => 0,
        'respuesta' => analizar_con_gemini($prompt),
    ];
}

/**
 * Responde una pregunta en lenguaje natural.
 *
 * Decide sola el camino: si la pregunta pide un DATO se traduce a SQL; si pide
 * una RECOMENDACION se responde con el contexto cuantitativo completo.
 *
 * @return array{pregunta:string, modo:string, sql:?string, filas:array, respuesta:string}
 */
function responder_pregunta(string $pregunta, string $ruta = 'TODAS',
                            string $periodo = 'TODOS'): array
{
    $pregunta = trim($pregunta);
    if ($pregunta === '') {
        throw new RuntimeException('Escribe una pregunta.');
    }
    if (mb_strlen($pregunta) > 400) {
        throw new RuntimeException('La pregunta es demasiado larga.');
    }

    $sql = generar_sql($pregunta);

    if (stripos($sql, 'CONSULTIVA') !== false) {
        return responder_consultiva($pregunta, $ruta, $periodo);
    }

    if (stripos($sql, 'NO_RESPONDIBLE') !== false) {
        throw new RuntimeException(
            'Esa pregunta no puede responderse con los datos disponibles. '
            . 'El extracto contiene demanda e ingreso por ruta, paradero, hora, '
            . 'dia de la semana y tipo de pasaje, entre febrero de 2025 y '
            . 'febrero de 2026.');
    }

    $sql = validar_sql($sql);

    $pdo = conectar_solo_lectura();
    try {
        $filas = $pdo->query($sql)->fetchAll();
    } catch (PDOException $e) {
        throw new RuntimeException(
            'La consulta generada no pudo ejecutarse. Prueba a reformular la '
            . 'pregunta de forma mas concreta.');
    }

    return [
        'pregunta'  => $pregunta,
        'modo'      => 'dato',
        'sql'       => $sql,
        'filas'     => array_slice($filas, 0, 20),
        'n_filas'   => count($filas),
        'respuesta' => redactar_respuesta($pregunta, $sql, $filas),
    ];
}
