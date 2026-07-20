<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function iniciar_sesion_segura(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_set_cookie_params([
        'httponly' => true,   // no accesible desde JavaScript
        'samesite' => 'Lax',  // no viaja en peticiones de terceros
    ]);
    session_start();
}

function usuario_actual(): ?string
{
    iniciar_sesion_segura();
    return $_SESSION['usuario'] ?? null;
}

function esta_autenticado(): bool
{
    return usuario_actual() !== null;
}

/** Valida las credenciales y deja la sesion iniciada si son correctas. */
function intentar_login(string $usuario, string $clave): bool
{
    iniciar_sesion_segura();

    // hash_equals compara en tiempo constante: no revela por temporizacion
    // si el usuario existe.
    $usuario_valido = hash_equals(APP_USUARIO, $usuario);
    $clave_valida   = APP_PASSWORD_HASH !== ''
                      && password_verify($clave, APP_PASSWORD_HASH);

    if ($usuario_valido && $clave_valida) {
        session_regenerate_id(true);   // evita fijacion de sesion
        $_SESSION['usuario'] = APP_USUARIO;
        $_SESSION['inicio']  = time();
        return true;
    }

    // Retardo ante credenciales incorrectas: encarece el ensayo masivo.
    usleep(400000);
    return false;
}

function cerrar_sesion(): void
{
    iniciar_sesion_segura();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/**
 * Exige sesion iniciada.
 *
 * Redirige al login en las paginas; devuelve 401 en JSON para las peticiones
 * de la API.
 */
function exigir_sesion(bool $es_api = false): void
{
    if (esta_autenticado()) {
        return;
    }
    if ($es_api) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        exit(json_encode([
            'ok'    => false,
            'error' => 'La sesion expiro. Vuelve a iniciar sesion.',
        ], JSON_UNESCAPED_UNICODE));
    }
    header('Location: login.php');
    exit;
}
