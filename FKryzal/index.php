<?php
require __DIR__ . '/app/core.php';
try {
    $ready = has_users();
} catch (Throwable $e) {
    http_response_code(503);
    exit('No se puede conectar a MySQL. Revisa WampServer y app/config.php.');
}
if (!$ready) {
    header('Location: install.php');
    exit();
}
try {
    $pdo = db();
    bind_installation($pdo);
    $me = current_user();
} catch (Throwable $e) {
    http_response_code(503);
    exit('No se puede conectar a MySQL. Revisa WampServer y app/config.php.');
}
if (in_array($_GET['page'] ?? 'home', ['home', 'about', 'contact'], true)) {
    ensure_features();
    cancel_empty_classes();
    require __DIR__ . '/Vista/publica.php';
    exit();
}
require __DIR__ . '/Controlador/cuenta.php';
require __DIR__ . '/Vista/espacio.php';
