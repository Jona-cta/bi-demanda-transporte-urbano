<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/auth.php';

// Si ya hay sesion, no tiene sentido volver a pedir credenciales.
if (esta_autenticado()) {
    header('Location: index.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim((string) ($_POST['usuario'] ?? ''));
    $clave   = (string) ($_POST['clave'] ?? '');

    // Validacion en servidor: no se confia en el "required" del formulario.
    if ($usuario === '' || $clave === '') {
        $error = 'Ingresa usuario y contrasena.';
    } elseif (APP_PASSWORD_HASH === '') {
        $error = 'El modulo no tiene credenciales configuradas. '
               . 'Copia .env.example a .env antes de iniciar sesion.';
    } elseif (intentar_login($usuario, $clave)) {
        header('Location: index.php');
        exit;
    } else {
        // Mensaje deliberadamente generico: no se revela si lo que fallo fue
        // el usuario o la contrasena.
        $error = 'Usuario o contrasena incorrectos.';
    }
}

function h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Acceso - Analisis Inteligente de Demanda</title>
<link rel="stylesheet" href="assets/estilo.css">
</head>
<body class="pantalla-acceso">

<main class="acceso">
  <div class="acceso-marca">
    <h1>Analisis Inteligente de Demanda</h1>
    <p><?= h(NOMBRE_ORGANIZACION) ?></p>
  </div>

  <form class="acceso-caja" method="post" id="form-acceso">
    <h2>Iniciar sesion</h2>

    <?php if ($error): ?>
      <div class="aviso aviso-error"><?= h($error) ?></div>
    <?php endif; ?>

    <div class="campo">
      <label for="usuario">Usuario</label>
      <input type="text" id="usuario" name="usuario" required autofocus
             autocomplete="username" value="<?= h($_POST['usuario'] ?? '') ?>">
    </div>

    <div class="campo">
      <label for="clave">Contrasena</label>
      <input type="password" id="clave" name="clave" required
             autocomplete="current-password">
    </div>

    <button type="submit" class="btn btn-primario btn-ancho" id="btn-entrar">
      Entrar
    </button>

    <?php if (MOSTRAR_CREDENCIALES_DEMO && CLAVE_DEMO !== ''): ?>
      <div class="credenciales-demo">
        <strong>Acceso de demostracion</strong>
        Usuario: <code><?= h(APP_USUARIO) ?></code>
        &nbsp;·&nbsp;
        Contrasena: <code><?= h(CLAVE_DEMO) ?></code>
      </div>
    <?php endif; ?>
  </form>

  <p class="acceso-pie">
    Proyecto Final - Inteligencia de Negocios (100000I62N) - UTP, Ciclo 1 2026
  </p>
</main>

<script>
// Candado del boton: evita el doble envio del formulario.
document.getElementById('form-acceso').addEventListener('submit', function () {
  const b = document.getElementById('btn-entrar');
  b.disabled = true;
  b.textContent = 'Entrando...';
});
</script>

</body>
</html>
