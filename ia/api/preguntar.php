<?php
/**
 * Endpoint de la consulta en lenguaje natural.
 *
 * Recibe una pregunta, la traduce a SQL, la ejecuta sobre el extracto en modo
 * solo lectura y devuelve la respuesta redactada junto con la consulta que se
 * ejecuto. Devolver el SQL es deliberado: permite al usuario comprobar de donde
 * sale la cifra, en lugar de tener que confiar en el modelo.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

// Dos llamadas al modelo por pregunta (generar SQL y redactar la respuesta).
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

    // El corte activo en la pagina acota las preguntas consultivas: si el
    // usuario esta viendo la ruta R-01, "que deberia hacer" se responde sobre
    // R-01 y no sobre el total.
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
