<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/auth.php';

cerrar_sesion();
header('Location: login.php');
exit;
