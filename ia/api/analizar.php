<?php
/**
 * Endpoint del analisis ejecutivo.
 *
 * Consulta el extracto, arma el contexto cuantitativo y lo entrega a Gemini.
 * Devuelve siempre JSON, tambien en los errores.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

// La cadena de modelos de reserva puede encadenar varias llamadas.
set_time_limit(420);

require_once __DIR__ . '/../lib/kpis.php';
require_once __DIR__ . '/../lib/gemini.php';
require_once __DIR__ . '/../lib/auth.php';

// Se protege el endpoint, no solo la pagina: de lo contrario podria
// invocarse directamente y consumir la cuota de la clave.
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

    $analisis = analizar_con_gemini(construir_prompt($datos));

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
