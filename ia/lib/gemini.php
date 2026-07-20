<?php
/**
 * Cliente de la API de Google Gemini.
 *
 * Toda llamada sale del SERVIDOR. La API Key nunca se envia al navegador.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

const GEMINI_ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';

/**
 * Modelos de reserva.
 *
 * La capa gratuita no asigna la misma cuota a todos los modelos ni en todos los
 * proyectos: una clave nueva puede recibir "limit: 0" en un modelo concreto y
 * funcionar sin problema en otro. Para que la demostracion no dependa de como
 * quedo aprovisionada una clave en particular, si el modelo configurado no esta
 * disponible se reintenta con estos, en orden.
 */
const GEMINI_MODELOS_RESERVA = [
    'gemini-flash-latest',
    'gemini-2.0-flash',
    'gemini-2.0-flash-lite',
    'gemini-flash-lite-latest',
];

/**
 * Convierte el corte de KPIs en el texto que se le manda al modelo.
 *
 * Se le entrega el dato YA AGREGADO, no filas crudas: el modelo interpreta,
 * no calcula. Asi las cifras del analisis son siempre las mismas que las del
 * dashboard, y no dependen de que el modelo sume bien.
 */
function construir_prompt(array $datos): string
{
    $k = $datos['kpis'];
    $f = $datos['filtro'];

    $ruta    = $f['ruta'] === 'TODAS' ? 'todas las rutas' : 'la ruta ' . $f['ruta'];
    $periodo = $f['periodo'] === 'TODOS'
        ? 'todo el periodo disponible (feb-2025 a feb-2026)'
        : 'el periodo ' . $f['periodo'];

    $t = "CONTEXTO\n";
    $t .= "Eres un analista de Inteligencia de Negocios de un operador de transporte publico ";
    $t .= "urbano en el Peru (corredor complementario). Analizas datos de validaciones ";
    $t .= "(cada validacion es un abordaje registrado por el sistema embarcado del bus).\n\n";

    $t .= "CORTE ANALIZADO: $ruta, $periodo.\n\n";

    $t .= "INDICADORES\n";
    $t .= "- Total de validaciones: " . number_format($k['total_validaciones']) . "\n";
    $t .= "- Ingreso total: S/ " . number_format($k['ingreso_total'], 2) . "\n";
    $t .= "- Carreras (viajes) realizadas: " . number_format($k['num_carreras']) . "\n";
    $t .= "- Promedio de pasajeros por viaje: " . $k['promedio_pasajeros_viaje'] . "\n";
    $t .= "- Ticket promedio por validacion: S/ " . number_format($k['ticket_promedio'], 2) . "\n";
    $t .= "- Demanda en hora punta: " . $k['pct_hora_punta'] . "% del total\n";
    $t .= "- Dias con registro en el corte: " . number_format($k['dias_con_dato']) . "\n";
    $t .= "- Validaciones promedio por dia con registro: " . number_format($k['validaciones_por_dia']) . "\n\n";

    $t .= "ADVERTENCIA SOBRE LA COBERTURA (leer antes de analizar)\n";
    $t .= "La fuente NO registra todos los dias del calendario: solo 193 de 391 dias del ";
    $t .= "periodo tienen datos, y diciembre de 2025 esta ausente por completo. Por lo tanto ";
    $t .= "los totales mensuales NO son comparables entre si: un mes con pocos dias de ";
    $t .= "registro parece de baja demanda cuando en realidad solo esta menos cubierto. ";
    $t .= "Para comparar meses usa SIEMPRE las validaciones promedio por dia con registro. ";
    $t .= "No interpretes como caida o crecimiento de demanda lo que es diferencia de cobertura, ";
    $t .= "y no hagas afirmaciones sobre estacionalidad.\n\n";

    $t .= "DISTRIBUCION POR TIPO DE PASAJE\n";
    $total = max(1, $k['total_validaciones']);
    foreach ($datos['tipo_pasaje'] as $tp) {
        $pct = round(100 * (int) $tp['validaciones'] / $total, 2);
        $t .= "- {$tp['tipo']}: " . number_format((int) $tp['validaciones']) . " validaciones ($pct%), ";
        $t .= "S/ " . number_format((float) $tp['ingreso'], 2) . "\n";
    }

    $t .= "\nDEMANDA POR HORA DEL DIA\n";
    foreach ($datos['por_hora'] as $h) {
        $marca = ((int) $h['es_hora_punta'] === 1) ? ' [HORA PUNTA]' : '';
        $t .= "- {$h['hora_texto']}: " . number_format((int) $h['validaciones']) . $marca . "\n";
    }

    if ($datos['paraderos']) {
        $t .= "\nPARADEROS CON MAYOR DEMANDA\n";
        foreach ($datos['paraderos'] as $p) {
            $t .= "- {$p['paradero']} ({$p['zona']}): " . number_format((int) $p['validaciones']) . "\n";
        }
    }

    if ($datos['dias']) {
        $t .= "\nDEMANDA POR DIA DE LA SEMANA\n";
        foreach ($datos['dias'] as $d) {
            $t .= "- {$d['dia_nombre']}: " . number_format((int) $d['validaciones']) . "\n";
        }
    }

    if ($datos['ranking']) {
        $t .= "\nRANKING DE RUTAS POR INGRESO\n";
        foreach ($datos['ranking'] as $r) {
            $pct = $k['ingreso_total'] > 0
                ? round(100 * (float) $r['ingreso'] / $k['ingreso_total'], 2) : 0;
            $t .= "- {$r['codigo_ruta']} ({$r['tipo_ruta']}): S/ "
                . number_format((float) $r['ingreso'], 2) . " ($pct% del ingreso), "
                . number_format((int) $r['validaciones']) . " validaciones\n";
        }
    }

    if ($datos['evolucion']) {
        $t .= "\nEVOLUCION MENSUAL (normalizada por cobertura)\n";
        foreach ($datos['evolucion'] as $e) {
            $t .= "- {$e['anio_mes']}: " . number_format((int) $e['validaciones']) . " validaciones en "
                . (int) $e['dias_con_dato'] . " dias con registro"
                . " (promedio " . number_format((int) $e['validaciones_por_dia']) . " por dia), S/ "
                . number_format((float) $e['ingreso'], 2) . "\n";
        }
    }

    $t .= "\nTAREA\n";
    $t .= "Redacta un analisis ejecutivo en espanol, dirigido a la gerencia de operaciones. ";
    $t .= "Usa exactamente esta estructura, con estos encabezados:\n\n";
    $t .= "## Lectura general\n";
    $t .= "Dos o tres frases sobre que muestra el corte en conjunto.\n\n";
    $t .= "## Patrones detectados\n";
    $t .= "Tres a cinco hallazgos concretos. Cada uno DEBE citar la cifra que lo sustenta.\n\n";
    $t .= "## Recomendaciones operativas\n";
    $t .= "Tres acciones concretas y accionables (frecuencias, asignacion de flota, ";
    $t .= "gestion de paraderos o de la mezcla tarifaria). Cada una justificada con el dato.\n\n";
    $t .= "## Alertas\n";
    $t .= "Riesgos o anomalias que la gerencia deberia vigilar.\n\n";
    $t .= "REGLAS\n";
    $t .= "- Usa unicamente las cifras entregadas arriba. No inventes datos ni estimes lo que no esta.\n";
    $t .= "- No uses guiones largos. Si necesitas un separador, usa el guion normal.\n";
    $t .= "- Se concreto y profesional. Nada de relleno ni de repetir la consigna.\n";
    $t .= "- Los codigos de ruta y paradero estan anonimizados; usalos tal cual aparecen.\n";

    return $t;
}

/**
 * Llama a Gemini y devuelve el texto generado.
 *
 * @throws RuntimeException con un mensaje entendible para el usuario final.
 */
function analizar_con_gemini(string $prompt): string
{
    if (GEMINI_API_KEY === '') {
        throw new RuntimeException(
            'No hay API Key configurada. Copia .env.example a .env y coloca tu clave '
            . 'de Google Gemini en GEMINI_API_KEY. Se obtiene gratis en https://ai.google.dev'
        );
    }

    // El modelo configurado primero; luego los de reserva, sin repetir.
    $candidatos = array_values(array_unique(
        array_merge([GEMINI_MODELO], GEMINI_MODELOS_RESERVA)
    ));

    $ultimo_error = null;

    foreach ($candidatos as $modelo) {
        // Hasta dos intentos por modelo antes de pasar al siguiente. Un 503
        // ("high demand") suele resolverse en segundos, asi que conviene
        // insistir un poco con el modelo elegido antes de degradar a otro.
        for ($intento = 1; $intento <= 2; $intento++) {
            try {
                $texto = llamar_modelo($modelo, $prompt);
                $GLOBALS['gemini_modelo_usado'] = $modelo;
                return $texto;
            } catch (ModeloNoDisponible $e) {
                $ultimo_error = $e->getMessage();
                if ($e->reintentable && $intento === 1) {
                    sleep(2);      // saturacion pasajera: se reintenta igual
                    continue;
                }
                break;             // sin cuota o inexistente: cambiar de modelo
            }
        }
        // Cualquier otro error (clave invalida, sin red) se propaga tal cual:
        // reintentar con otro modelo no lo resolveria.
    }

    throw new RuntimeException(
        'Ningun modelo de Gemini quedo disponible con esta API Key. '
        . 'Ultimo detalle: ' . $ultimo_error
    );
}

/** Devuelve el modelo que efectivamente respondio. */
function gemini_modelo_usado(): string
{
    return $GLOBALS['gemini_modelo_usado'] ?? GEMINI_MODELO;
}

/**
 * El modelo no atendio la peticion: conviene reintentar o probar con otro.
 *
 * $reintentable distingue las dos causas:
 *   true  -> saturacion pasajera del servicio (503, 500). Vale la pena
 *            reintentar con el mismo modelo tras una breve espera.
 *   false -> el modelo no existe (404) o no tiene cuota asignada (429).
 *            Insistir no sirve: hay que cambiar de modelo.
 */
class ModeloNoDisponible extends RuntimeException
{
    public bool $reintentable;

    public function __construct(string $mensaje, bool $reintentable = false)
    {
        parent::__construct($mensaje);
        $this->reintentable = $reintentable;
    }
}

/**
 * Ejecuta la llamada contra un modelo concreto.
 *
 * @throws ModeloNoDisponible si el modelo no existe o no tiene cuota asignada.
 * @throws RuntimeException   para errores que no se arreglan cambiando de modelo.
 */
function llamar_modelo(string $modelo, string $prompt): string
{
    $url = sprintf(GEMINI_ENDPOINT, $modelo);

    $cuerpo = json_encode([
        'contents' => [[
            'parts' => [['text' => $prompt]],
        ]],
        'generationConfig' => [
            // Temperatura baja: es un analisis cuantitativo, no un ejercicio creativo.
            'temperature'     => 0.3,
            // Holgado a proposito. Los modelos flash actuales razonan antes de
            // responder y esos tokens de razonamiento se descuentan del mismo
            // limite: con 2048 la respuesta se cortaba a media frase. No se
            // desactiva el razonamiento con thinkingConfig porque los modelos
            // mas antiguos de la lista de reserva no aceptan ese campo y
            // rechazarian la peticion.
            'maxOutputTokens' => 8192,
        ],
    ], JSON_UNESCAPED_UNICODE);

    // Certificados raiz: el PHP portatil no hereda el almacen de Windows, asi
    // que sin esto la conexion falla con "unable to get local issuer
    // certificate". La ruta se calcula aqui, en absoluto, porque php.ini solo
    // admite rutas relativas al directorio de trabajo y el proyecto tiene que
    // funcionar desde cualquier carpeta.
    // Nunca se desactiva la verificacion: hacerlo dejaria la API Key expuesta
    // a un intermediario que interceptara la conexion.
    // __DIR__ es ia/lib; dos niveles arriba es la raiz del proyecto.
    $cacert = dirname(__DIR__, 2) . '/php/cacert.pem';

    $ch = curl_init($url);
    if (is_readable($cacert)) {
        curl_setopt($ch, CURLOPT_CAINFO, $cacert);
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $cuerpo,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            // La clave va en cabecera, no en la URL: asi no queda registrada en
            // logs de servidor ni en el historial de proxys.
            'x-goog-api-key: ' . GEMINI_API_KEY,
        ],
        // 90 s: los modelos con razonamiento pueden tardar bastante cuando el
        // servicio esta cargado. Con 60 s se cortaban respuestas que si iban a
        // llegar.
        CURLOPT_TIMEOUT        => 90,
        CURLOPT_CONNECTTIMEOUT => 15,
    ]);

    $respuesta = curl_exec($ch);
    $http      = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err       = curl_error($ch);
    $errno     = curl_errno($ch);
    curl_close($ch);

    if ($respuesta === false) {
        // Si se agoto el tiempo, insistir con el MISMO modelo no ayuda: lo mas
        // probable es que vuelva a tardar igual. Se pasa al siguiente, que
        // incluye variantes "lite" bastante mas rapidas.
        if ($errno === CURLE_OPERATION_TIMEDOUT) {
            throw new ModeloNoDisponible(
                "[$modelo] La respuesta tardo mas de 90 segundos.", false);
        }
        throw new RuntimeException('No se pudo conectar con la API de Gemini: ' . $err);
    }

    $json = json_decode($respuesta, true);

    if ($http !== 200) {
        $detalle = $json['error']['message'] ?? substr((string) $respuesta, 0, 300);

        // Clave invalida: cambiar de modelo no ayuda, se corta aqui.
        if (($http === 400 || $http === 403) && stripos($detalle, 'api key') !== false) {
            throw new RuntimeException('La API Key no es valida. Revisa GEMINI_API_KEY en el archivo .env');
        }

        // 503 y 500: el servicio esta saturado ("high demand") o fallo de forma
        // pasajera. Se reintenta con el mismo modelo antes de degradar a otro.
        if (in_array($http, [500, 502, 503, 504], true)) {
            throw new ModeloNoDisponible("[$modelo] $detalle", true);
        }

        // 404: el modelo no existe para esta version de la API.
        // 429: la capa gratuita no le asigno cuota a este modelo en este
        //      proyecto (aparece como "limit: 0"). En ambos casos insistir con
        //      el mismo modelo no sirve: se pasa al siguiente.
        if ($http === 404 || $http === 429) {
            throw new ModeloNoDisponible("[$modelo] $detalle", false);
        }

        throw new RuntimeException("La API respondio con codigo $http: $detalle");
    }

    $texto = $json['candidates'][0]['content']['parts'][0]['text'] ?? '';
    if (trim($texto) === '') {
        throw new RuntimeException('La API respondio sin contenido. Reintenta en unos segundos.');
    }

    return trim($texto);
}
