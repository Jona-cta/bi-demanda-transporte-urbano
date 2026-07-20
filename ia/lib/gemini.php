<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

const GEMINI_ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';

/**
 * Modelos de reserva, en orden de preferencia.
 *
 * La capa gratuita no asigna la misma cuota a todos los modelos ni en todos los
 * proyectos: una clave puede recibir "limit: 0" en uno y funcionar en otro.
 */
const GEMINI_MODELOS_RESERVA = [
    'gemini-flash-latest',
    'gemini-2.0-flash',
    'gemini-2.0-flash-lite',
    'gemini-flash-lite-latest',
];

/**
 * Convierte el corte de KPIs en el texto que recibe el modelo.
 *
 * Se le entrega el dato ya agregado: el modelo interpreta, no calcula.
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
 * Recorre los modelos de reserva si el elegido no responde.
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

    $candidatos = array_values(array_unique(
        array_merge([GEMINI_MODELO], GEMINI_MODELOS_RESERVA)
    ));

    $ultimo_error = null;

    foreach ($candidatos as $modelo) {
        // Dos intentos por modelo: un 503 suele resolverse en segundos.
        for ($intento = 1; $intento <= 2; $intento++) {
            try {
                $texto = llamar_modelo($modelo, $prompt);
                $GLOBALS['gemini_modelo_usado'] = $modelo;
                return $texto;
            } catch (ModeloNoDisponible $e) {
                $ultimo_error = $e->getMessage();
                if ($e->reintentable && $intento === 1) {
                    sleep(2);
                    continue;
                }
                break;
            }
        }
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
 * El modelo no atendio la peticion.
 *
 * $reintentable distingue la saturacion pasajera (conviene reintentar) de la
 * falta de cuota o de existencia del modelo (hay que cambiar de modelo).
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
 * @throws ModeloNoDisponible si conviene reintentar o cambiar de modelo.
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
            'temperature' => 0.3,
            // Holgado: los modelos flash razonan antes de responder y esos
            // tokens se descuentan del mismo limite.
            'maxOutputTokens' => 8192,
        ],
    ], JSON_UNESCAPED_UNICODE);

    // El PHP portatil no hereda el almacen de certificados de Windows.
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
            // En cabecera y no en la URL: no queda en logs ni en proxys.
            'x-goog-api-key: ' . GEMINI_API_KEY,
        ],
        CURLOPT_TIMEOUT        => 90,
        CURLOPT_CONNECTTIMEOUT => 15,
    ]);

    $respuesta = curl_exec($ch);
    $http      = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err       = curl_error($ch);
    $errno     = curl_errno($ch);
    curl_close($ch);

    if ($respuesta === false) {
        if ($errno === CURLE_OPERATION_TIMEDOUT) {
            throw new ModeloNoDisponible(
                "[$modelo] La respuesta tardo mas de 90 segundos.", false);
        }
        throw new RuntimeException('No se pudo conectar con la API de Gemini: ' . $err);
    }

    $json = json_decode($respuesta, true);

    if ($http !== 200) {
        $detalle = $json['error']['message'] ?? substr((string) $respuesta, 0, 300);

        if (($http === 400 || $http === 403) && stripos($detalle, 'api key') !== false) {
            throw new RuntimeException('La API Key no es valida. Revisa GEMINI_API_KEY en el archivo .env');
        }

        // Servicio saturado: se reintenta con el mismo modelo.
        if (in_array($http, [500, 502, 503, 504], true)) {
            throw new ModeloNoDisponible("[$modelo] $detalle", true);
        }

        // Modelo inexistente (404) o sin cuota asignada (429): se cambia de modelo.
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
