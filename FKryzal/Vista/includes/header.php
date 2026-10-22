<?php
require_once __DIR__ . '/../../app/legacy.php';
$script = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '');
$roles = str_contains($script, '/admin/')
    ? ['administrador']
    : (str_contains($script, '/entrenador/')
        ? ['entrenador']
        : ['usuario']);
legacy_require($roles);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        validate_csrf();
    } catch (Throwable $e) {
        http_response_code(403);
        exit(h($e->getMessage()));
    };
}
ob_start(function ($html) {
    $html = preg_replace('~<style>.*?</style>~s', '', $html);
    return preg_replace_callback('~<form\b[^>]*>~i', fn($m) => $m[0] . csrf(), $html);
});
?>
<!doctype html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>KryZal · Mi gimnasio</title>
        <link rel="icon" href="/FKryzal/assets/images/ico.png?v=2">
        <link rel="stylesheet" href="/FKryzal/assets/style.css">
        <link rel="stylesheet" href="/FKryzal/assets/legacy.css">
        <script src="/FKryzal/assets/app.js" defer></script>
    </head>
    <body class="legacy">
        <header class="legacy-header">
            <a class="brand" href="/FKryzal/index.php?page=home">
                <img class="brand-icon" src="/FKryzal/assets/images/logo-claro.svg" alt=""> KryZal
            </a>
            <nav aria-label="Mi gimnasio">
                <a href="/FKryzal/index.php?page=dashboard">Mi espacio</a>
                <a href="/FKryzal/index.php?page=profile">Mi perfil</a>
                <?php if ($me['role'] === 'usuario'): ?>
                <a href="/FKryzal/index.php?page=classes">Clases en tarjetas</a>
                <a href="/FKryzal/Vista/cliente/calendario.php">Calendario</a>
                <a href="/FKryzal/Vista/cliente/misclases.php">Mis clases</a>
                <a href="/FKryzal/Vista/cliente/clasificacion.php">Clasificación</a>
                <a href="/FKryzal/Vista/cliente/recompensas.php">Recompensas</a>
                <?php elseif ($me['role'] === 'entrenador'): ?>
                <a href="/FKryzal/index.php?page=manage">Gestionar clases</a>
                <a href="/FKryzal/Vista/entrenador/especialidad.php?nuevo">Especialidad</a>
                <?php else: ?>
                <a href="/FKryzal/index.php?page=users">Gestionar usuarios</a>
                <?php endif; ?>
            </nav>
            <form class="legacy-logout" method="post" action="/FKryzal/index.php?page=login">
                <?= csrf() ?>
                <input type="hidden" name="action" value="logout">
                <button class="logout-button">Cerrar sesión</button>
            </form>
        </header>
        <main class="legacy-main">
