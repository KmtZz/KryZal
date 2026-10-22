<?php
$page = $_GET['page'] ?? ($me ? 'dashboard' : 'login');
$public = ['login', 'register', 'forgot', 'reset'];
if (!$me && !in_array($page, $public, true)) {
    go('login');
}
if ($me && $me['force_password'] && $page !== 'profile' && ($_POST['action'] ?? '') !== 'logout') {
    go('profile');
}
if (
    $me &&
    in_array($page, ['login', 'register', 'forgot', 'reset'], true) &&
    ($_POST['action'] ?? '') !== 'logout'
) {
    go('dashboard');
}
if (
    $me &&
    ((in_array($page, ['users', 'rewardcheck', 'settings', 'messages', 'mail'], true) &&
        $me['role'] !== 'administrador') ||
        (in_array($page, ['manage', 'archive', 'attendance'], true) && $me['role'] === 'usuario') ||
        (in_array($page, ['ranking', 'rewards', 'myrewards'], true) && $me['role'] !== 'usuario'))
) {
    http_response_code(403);
    exit('No tienes permiso para esta sección.');
}
if ($page === 'mail' && ($config['mail']['mode'] !== 'local' || !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true))) {
    http_response_code(403);
    exit('Esta sección solo está disponible localmente en modo local.');
}
if ($page === 'calendar') {
    go('classes&view=calendar');
}
ensure_features();
cancel_empty_classes();
if ($me) {
    $me = current_user();
}
if ($me) {
    ensure_tickets();
    if ($page === 'requests' && !empty($_GET['id'])) {
        try {
            $selected = ticket_for_user((int) $_GET['id'], $me);
            $last = (int) query(
                'SELECT COALESCE(MAX(id),0) FROM ticket_messages WHERE ticket_id=?',
                [$selected['id']],
            )->fetchColumn();
            query(
                'INSERT INTO ticket_reads(ticket_id,user_id,last_message_id) VALUES(?,?,?) ON DUPLICATE KEY UPDATE last_message_id=GREATEST(last_message_id,VALUES(last_message_id))',
                [$selected['id'], $me['id'], $last],
            );
        } catch (Throwable $e) {
            http_response_code(404);
            exit('Petición no encontrada o sin permiso de acceso.');
        }
    }
    if (
        $page === 'messages' &&
        !empty($_GET['id']) &&
        query('SELECT id FROM contact_messages WHERE id=?', [(int) $_GET['id']])->fetch()
    ) {
        query('INSERT IGNORE INTO contact_reads(contact_id,admin_id) VALUES(?,?)', [
            (int) $_GET['id'],
            $me['id'],
        ]);
    }
}
if (
    $me &&
    $me['role'] === 'entrenador' &&
    !trim($me['specialty']) &&
    $page !== 'profile' &&
    ($_POST['action'] ?? '') !== 'logout'
) {
    go('profile');
}
if ($page === 'specialty') {
    go('profile');
}
// Conserva los enlaces anteriores de ambos roles del equipo.
if ($me && $me['role'] !== 'usuario' && in_array($page, ['manage', 'myclasses', 'archive'], true)) {
    $params = [];
    foreach (['view', 'month', 'q', 'day', 'scope', 'tab'] as $key) {
        if (isset($_GET[$key]) && is_string($_GET[$key])) {
            $params[$key] = $_GET[$key];
        }
    }
    $params['scope'] = $page === 'archive' ? 'mine' : ($params['scope'] ?? 'mine');
    $params['tab'] = $page === 'archive' ? 'archived' : ($params['tab'] ?? match ($_GET['timing'] ?? '') {
        'pending' => 'pending', 'upcoming' => 'upcoming', default => 'active',
    });
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        go('classes&' . http_build_query($params));
    }
    $_GET = array_merge($_GET, $params);
    $page = 'classes';
}
if ($me && $me['role'] !== 'entrenador' && in_array($page, ['classes', 'myclasses', 'manage'], true)) {
    $target = $page;
    $scope =
        ($_GET['scope'] ?? '') === 'mine'
            ? 'mine'
            : (($_GET['scope'] ?? '') === 'all'
                ? 'all'
                : '');
    if ($page === 'myclasses' && $me['role'] !== 'usuario') {
        $target = 'manage';
    }
    if ($scope === 'mine' && $page === 'classes' && $me['role'] !== 'administrador') {
        $target = $me['role'] === 'usuario' ? 'myclasses' : 'manage';
    }
    if (
        $scope === 'all' &&
        ($page === 'myclasses' || ($page === 'manage' && $me['role'] === 'entrenador'))
    ) {
        $target = 'classes';
    }
    if ($target !== $page) {
        $params = [];
        foreach (['view', 'month', 'q', 'day', 'timing'] as $key) {
            if (isset($_GET[$key]) && is_string($_GET[$key])) {
                $params[$key] = $_GET[$key];
            }
        }
        go($target . ($params ? '&' . http_build_query($params) : ''));
    }
}
$error = '';
$conflictingClassId = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        validate_csrf();
        $action = $_POST['action'] ?? '';
        if ($me && $me['force_password'] && !in_array($action, ['password', 'logout'], true)) {
            throw new RuntimeException('Cambia tu contraseña antes de continuar.');
        }
        $handlers = [
            'login' => 'acceso',
            'logout' => 'acceso',
            'register' => 'acceso',
            'forgot' => 'acceso',
            'reset' => 'acceso',
            'photo' => 'perfil',
            'profile' => 'perfil',
            'password' => 'perfil',
            'pin_booking' => 'reservas',
            'book' => 'reservas',
            'unbook' => 'reservas',
            'create_class' => 'clases',
            'assign_trainer' => 'clases',
            'cancel_class' => 'clases',
            'remove_student' => 'clases',
            'reopen_class' => 'asistencia',
            'attendance' => 'asistencia',
            'redeem' => 'recompensas',
            'use_reward' => 'recompensas',
            'ticket_create' => 'peticiones',
            'ticket_reply' => 'peticiones',
            'ticket_status' => 'peticiones',
            'mail_renew' => 'correo',
            'contact_reply' => 'correo',
            'contact_send' => 'correo',
            'mail_settings' => 'correo',
            'user_create' => 'usuarios',
            'user_points' => 'usuarios',
            'user_password' => 'usuarios',
            'user_save' => 'usuarios',
        ];

        if (!isset($handlers[$action])) {
            throw new RuntimeException('Operación no válida.');
        }

        require __DIR__ . '/acciones/' . $handlers[$action] . '.php';
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error =
            $e instanceof PDOException
                ? 'No se pudo guardar. Comprueba que no exista un email, inscripción u horario duplicado.'
                : $e->getMessage();
    }
}
$notice = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
