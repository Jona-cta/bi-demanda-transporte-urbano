<?php
/**
 * Configuracion del modulo de IA.
 *
 * La API Key se lee de un archivo .env que NO se versiona. Nunca se expone al
 * navegador: todas las llamadas a Gemini salen desde el servidor (analizar.php).
 * Si la clave viviera en el JavaScript del cliente, cualquiera podria leerla
 * desde el inspector del navegador.
 */

declare(strict_types=1);

/**
 * Lector minimo de .env (formato CLAVE=valor, # para comentarios).
 * No se usa una libreria externa a proposito: el proyecto debe correr en
 * cualquier maquina con solo descomprimir, sin composer ni dependencias.
 */
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

$RAIZ = dirname(__DIR__, 2);              // carpeta raiz del proyecto
$ENV  = cargar_env($RAIZ . '/.env');

/** Clave de la API de Google Gemini. Prioridad: .env, luego variable de entorno. */
define('GEMINI_API_KEY', $ENV['GEMINI_API_KEY'] ?? (getenv('GEMINI_API_KEY') ?: ''));

/** Modelo a usar. Configurable por si el docente prefiere otro. */
define('GEMINI_MODELO', $ENV['GEMINI_MODELO'] ?? 'gemini-2.0-flash');

/** Extracto del Data Mart (SQLite). Viaja con el proyecto. */
define('RUTA_SQLITE', $RAIZ . '/data/kpi_datamart.sqlite');

/** Nombre anonimizado de la organizacion, para los textos de la interfaz. */
define('NOMBRE_ORGANIZACION', 'Operador de Corredor Complementario');

// --- Acceso al modulo ------------------------------------------------------
// La contrasena no se almacena: solo su hash bcrypt, generado con
// password_hash(). Ni el repositorio ni este archivo contienen la clave.
define('APP_USUARIO',       $ENV['APP_USUARIO'] ?? 'gerente');
define('APP_PASSWORD_HASH', $ENV['APP_PASSWORD_HASH'] ?? '');

/** Credenciales de demostracion que se muestran en la pantalla de acceso.
 *  Se declaran en el .env para poder ocultarlas en un despliegue real. */
define('MOSTRAR_CREDENCIALES_DEMO', ($ENV['MOSTRAR_CREDENCIALES_DEMO'] ?? '1') === '1');
define('CLAVE_DEMO',                $ENV['CLAVE_DEMO'] ?? '');
