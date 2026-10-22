<?php
if ($me) {
    ensure_photos();
    $alerts = workspace_alerts($me);
    $links = ['profile' => 'Mi perfil', 'dashboard' => 'Resumen', 'classes' => 'Clases'];
    if ($me['role'] === 'usuario') {
        $links['myclasses'] = 'Mis clases';
        $links['ranking'] = 'Clasificación';
        $links['rewards'] = 'Recompensas';
        $links['myrewards'] = 'Mis recompensas';
    } else {
        $alerts['classes'] = $alerts['manage'];
    }
    if ($me['role'] === 'administrador') {
        $links['users'] = 'Usuarios';
        $links['rewardcheck'] = 'Validar recompensas';
        $links['requests'] = 'Mensajes';
        $alerts['requests'] += $alerts['messages'];
        if ($config['mail']['mode'] === 'local' && in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
            $links['mail'] = 'Recuperación de cuentas';
        }
        $links['settings'] = 'Configuración';
    } else {
        $links['requests'] = 'Mensajes';
    }
}

$views = [
    'login' => 'acceso/login.php',
    'register' => 'acceso/registro.php',
    'forgot' => 'acceso/recuperar.php',
    'reset' => 'acceso/restablecer.php',
    'dashboard' => 'espacio/resumen.php',
    'classes' => 'espacio/clases.php',
    'myclasses' => 'espacio/clases.php',
    'manage' => 'espacio/clases.php',
    'archive' => 'espacio/clases.php',
    'attendance' => 'espacio/asistencia.php',
    'profile' => 'espacio/perfil.php',
    'rewards' => 'espacio/recompensas.php',
    'myrewards' => 'espacio/recompensas.php',
    'rewardcheck' => 'espacio/recompensas.php',
    'requests' => 'espacio/peticiones.php',
    'ranking' => 'espacio/clasificacion.php',
];

if ($me && $me['role'] === 'administrador') {
    $views['messages'] = 'espacio/mensajes.php';
    $views['users'] = 'espacio/usuarios.php';
    $views['settings'] = 'espacio/configuracion.php';
    if (
        $config['mail']['mode'] === 'local' &&
        in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)
    ) {
        $views['mail'] = 'espacio/buzon.php';
    }
}

$title = $page === 'profile' && $me && $me['force_password']
    ? 'Primer acceso'
    : ($page === 'archive' ? 'Clases archivadas' : ($page === 'messages' ? 'Mensajes' : ($links[$page] ?? 'Asistencia')));
?>
<!doctype html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>KryZal Fitness</title>
        <link rel="icon" href="assets/images/ico.png?v=2">
        <link rel="stylesheet" href="assets/style.css">
        <link rel="stylesheet" href="assets/workspace.css">
        <script src="assets/app.js" defer></script>
    </head>
    <body class="<?= $me ? '' : 'auth' ?>" data-page="<?= h($page) ?>">
        <?php if ($me): ?>
            <?php require __DIR__ . '/includes/navegacion.php'; ?>
        <?php endif; ?>

        <main class="<?= $me ? 'main' : 'auth-card' ?>">
            <?php if ($me): ?>
                <div class="workspace-topbar">
                    <header class="workspace-header">
                        <div>
                            <span class="eyebrow">TU ESPACIO, TU RITMO</span>
                            <h1><?= h($title) ?></h1>
                        </div>
                        <div class="header-actions">
                            <a class="header-home" href="index.php">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="m3 10 9-7 9 7M5 9v11h5v-6h4v6h5V9" />
                                </svg>Página principal
                            </a>
                            <?php startform('logout'); ?>
                                <button class="logout-button">Cerrar sesión</button>
                            </form>
                        </div>
                    </header>
                </div>
            <?php else: ?>
                <a class="brand" href="index.php">
                    <img src="assets/images/logo-oscuro.svg" alt="" class="brand-icon"> KryZal
                </a>
            <?php endif; ?>

            <?php require __DIR__ . '/includes/aviso.php'; ?>
            <?php if ($error): $bookingError = in_array($_POST['action'] ?? '', ['book', 'unbook', 'pin_booking'], true); ?>
                <div class="notice error<?= $bookingError ? ' dismissible-notice' : '' ?>" <?= $bookingError ? 'data-toast' : '' ?> role="alert">
                    <span>
                        <?= h($error) ?>
                        <?php if ($conflictingClassId): ?>
                        <a class="notice-reservation-link" href="?page=myclasses&amp;class_id=<?= $conflictingClassId ?>">Ver mi reserva</a>
                        <?php endif; ?>
                    </span>
                    <?php if ($bookingError): ?>
                    <button type="button" data-toast-close aria-label="Cerrar aviso">×</button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($views[$page])): ?>
                <?php require __DIR__ . '/' . $views[$page]; ?>
            <?php else: ?>
                <div class="empty">Esta sección no está disponible.</div>
            <?php endif; ?>
        </main>

        <dialog class="confirm-dialog" id="confirm-dialog" aria-labelledby="confirm-title">
            <h2 id="confirm-title">Confirmar acción</h2>
            <p data-confirm-message></p>
            <div class="dialog-actions">
                <button type="button" class="secondary" data-confirm-cancel>Volver</button>
                <button type="button" data-confirm-accept>Confirmar</button>
            </div>
        </dialog>
    </body>
</html>
