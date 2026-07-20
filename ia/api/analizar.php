<?php
/**
 * Endpoint del analisis con IA.
 *
 * Recibe ruta y periodo, consulta el extracto del Data Mart, arma el contexto
 * cuantitativo y se lo entrega a Gemini para que lo interprete.
 *
 * Devuelve siempre JSON, tambien en los errores, para que la interfaz pueda
 * mostrar un mensaje entendible en vez de una pagina de error de PHP.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

// El modulo puede recorrer varios modelos antes de obtener respuesta, y cada
// llamada admite hasta 90 s. Sin este margen PHP cortaria la peticion antes de
// que la cadena de respaldo termine de intentarlo.
set_time_limit(420);

require_once __DIR__ . '/../lib/kpis.php';
require_once __DIR__ . '/../lib/gemini.php';
require_once __DIR__ . '/../lib/auth.php';

// El endpoint tambien se protege, no solo la pagina: de lo contrario se podria
// consumir la API (y la cuota de la clave) llamandolo directamente.
exigir_sesion(true);

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit(json_encode(['ok' => false, 'error' => 'Metodo no permitido.']));
    }

    $ruta    = trim((string) ($_POST['ruta'] ?? 'TODAS'));
    $periodo = trim((string) ($_POST['periodo'] ?? 'TODOS'));

    $pdo = conectar_sqlite();

    // Validacion en servidor: no se confia en el desplegable del navegador.
    // Se comprueba que los valores existan realmente en el extracto.
    $rutas_validas = array_column(listar_rutas($pdo), 'codigo_ruta');
    $rutas_validas[] = 'TODAS';
    if (!in_array($ruta, $rutas_validas, true)) {
        http_response_code(400);
        exit(json_encode(['ok' => false, 'error' => 'La ruta indicada no existe en el conjunto de datos.']));
    }

    $periodos_validos = listar_periodos($pdo);
    $periodos_validos[] = 'TODOS';
    if (!in_array($periodo, $periodos_validos, true)) {
        http_response_code(400);
        exit(json_encode(['ok' => false, 'error' => 'El periodo indicado no existe en el conjunto de datos.']));
    }

    $datos = obtener_kpis($pdo, $ruta, $periodo);

    if ($datos['kpis']['total_validaciones'] === 0) {
        exit(json_encode([
            'ok'    => false,
            'error' => 'No hay datos para esa combinacion de ruta y periodo. Prueba con otro corte.',
        ], JSON_UNESCAPED_UNICODE));
    }

    $prompt   = construir_prompt($datos);
    $analisis = analizar_con_gemini($prompt);

    echo json_encode([
        'ok'       => true,
        'kpis'     => $datos['kpis'],
        'analisis' => $analisis,
        'modelo'   => gemini_modelo_usado(),
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
