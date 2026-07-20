<?php
declare(strict_types=1);

/** Lector minimo de .env (CLAVE=valor, # para comentarios). */
function cargar_env(string $ruta): array
{
    if (!is_readable($ruta)) {
        return [];
    }
    $vars = [];
    foreach (file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
        $linea = trim($linea);
        if ($linea === '' || str_starts_with($linea, '#')) {
            continue;
        }
        $partes = explode('=', $linea, 2);
        if (count($partes) !== 2) {
            continue;
        }
        $clave = trim($partes[0]);
        $valor = trim($partes[1]);
        // Quita comillas envolventes si las hubiera.
        if (strlen($valor) >= 2
            && ($valor[0] === '"' || $valor[0] === "'")
            && $valor[strlen($valor) - 1] === $valor[0]) {
            $valor = substr($valor, 1, -1);
        }
        $vars[$clave] = $valor;
    }
    return $vars;
}

$RAIZ = dirname(__DIR__, 2);
$ENV  = cargar_env($RAIZ . '/.env');

/** Clave de la API de Google Gemini. */
define('GEMINI_API_KEY', $ENV['GEMINI_API_KEY'] ?? (getenv('GEMINI_API_KEY') ?: ''));

/** Modelo a usar. */
define('GEMINI_MODELO', $ENV['GEMINI_MODELO'] ?? 'gemini-flash-latest');

/** Extracto del Data Mart. Viaja con el proyecto. */
define('RUTA_SQLITE', $RAIZ . '/data/kpi_datamart.sqlite');

/** Nombre anonimizado de la organizacion. */
define('NOMBRE_ORGANIZACION', 'Operador de Corredor Complementario');

/** Acceso al modulo. Se almacena el hash bcrypt, nunca la contrasena. */
define('APP_USUARIO',       $ENV['APP_USUARIO'] ?? 'gerente');
define('APP_PASSWORD_HASH', $ENV['APP_PASSWORD_HASH'] ?? '');

/** Credenciales de demostracion mostradas en la pantalla de acceso. */
define('MOSTRAR_CREDENCIALES_DEMO', ($ENV['MOSTRAR_CREDENCIALES_DEMO'] ?? '1') === '1');
define('CLAVE_DEMO',                $ENV['CLAVE_DEMO'] ?? '');
