<?php
declare(strict_types=1);
$config = require __DIR__ . '/config.php';
date_default_timezone_set('Europe/Madrid');
if (PHP_SAPI !== 'cli') {
    session_name('KRYZAL2');
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    ini_set('session.use_strict_mode', '1');
    session_start();
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');
}

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/seguridad.php';
require_once __DIR__ . '/correo.php';
require_once __DIR__ . '/sesion.php';
require_once __DIR__ . '/interfaz.php';
require_once __DIR__ . '/esquema.php';
require_once __DIR__ . '/catalogos.php';
require_once __DIR__ . '/puntuacion.php';
require_once __DIR__ . '/clases.php';
require_once __DIR__ . '/peticiones.php';
require_once __DIR__ . '/formularios.php';
