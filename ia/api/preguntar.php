<?php
/**
 * Endpoint de la consulta en lenguaje natural.
 *
 * Devuelve la respuesta redactada y, cuando hubo consulta, el SQL ejecutado.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

// Hasta dos llamadas al modelo por pregunta.
set_time_limit(420);

require_once __DIR__ . '/../lib/consulta_ia.php';
require_once __DIR__ . '/../lib/auth.php';

exigir_sesion(true);

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit(json_encode(['ok' => false, 'error' => 'Metodo no permitido.']));
    }

    $pregunta = (string) ($_POST['pregunta'] ?? '');

    // El corte activo acota las preguntas de recomendacion.
    $ruta    = (string) ($_POST['ruta'] ?? 'TODAS');
    $periodo = (string) ($_POST['periodo'] ?? 'TODOS');

    $r = responder_pregunta($pregunta, $ruta, $periodo);

    echo json_encode([
        'ok'        => true,
        'modo'      => $r['modo'],
        'respuesta' => $r['respuesta'],
        'sql'       => $r['sql'],
        'filas'     => $r['filas'],
        'n_filas'   => $r['n_filas'],
        'modelo'    => gemini_modelo_usado(),
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()],
                     JSON_UNESCAPED_UNICODE);
}
