<?php
/**
 * Autenticacion del modulo.
 *
 * Decisiones de diseno, por si hay que sustentarlas:
 *
 * 1. La contrasena NO se guarda. Se guarda su hash (password_hash con bcrypt)
 *    en el archivo .env, que no se versiona. Aunque alguien lea el repositorio
 *    o el archivo de configuracion, no obtiene la contrasena.
 * 2. La comparacion se hace con password_verify, que ademas es resistente a
 *    ataques de temporizacion.
 * 3. Al iniciar sesion se regenera el identificador de sesion, para evitar
 *    fijacion de sesion (session fixation).
 * 4. Hay un retardo tras cada intento fallido, que encarece la prueba masiva de
 *    contrasenas sin molestar al usuario legitimo.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function iniciar_sesion_segura(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_set_cookie_params([
        'httponly' => true,   // la cookie no es accesible desde JavaScript
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

/**
 * Valida las credenciales contra las definidas en el .env.
 * Devuelve true si son correctas y deja la sesion iniciada.
 */
function intentar_login(string $usuario, string $clave): bool
{
    iniciar_sesion_segura();

    $usuario_ok = APP_USUARIO;
    $hash_ok    = APP_PASSWORD_HASH;

    // hash_equals compara en tiempo constante: no revela por temporizacion
    // si el nombre de usuario existe.
    $usuario_valido = hash_equals($usuario_ok, $usuario);
    $clave_valida   = $hash_ok !== '' && password_verify($clave, $hash_ok);

    if ($usuario_valido && $clave_valida) {
        session_regenerate_id(true);       // evita fijacion de sesion
        $_SESSION['usuario'] = $usuario_ok;
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
 * Exige sesion iniciada. Si no la hay, redirige al login (paginas) o
 * devuelve 401 en JSON (peticiones de la API).
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
