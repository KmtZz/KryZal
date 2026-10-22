<?php
require __DIR__ . '/app/core.php';
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('Configuración inicial solo desde localhost.');
}
$error = '';
$step = 'conexión';
try {
    if (has_users()) {
        header('Location: index.php?page=home');
        exit();
    }
} catch (Throwable $e) {
    $error = 'No se puede conectar a MySQL. Comprueba WampServer y app/config.php.';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        validate_csrf();
        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        if (
            !$name ||
            strlen($name) > 100 ||
            strlen($email) > 190 ||
            !filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {
            throw new RuntimeException('Introduce un nombre y email válidos.');
        }
        password_ok($password);
        $step = 'preparación de tablas';
        $pdo = db(true);
        database_setup($pdo, $config);
        require_once __DIR__ . '/app/legacy.php';
        legacy_prepare();
        $lock = 'kryzal-init-' . substr(hash('sha256', $config['db']['name']), 0, 40);
        $step = 'bloqueo inicial';
        if ((int) query('SELECT GET_LOCK(?,10)', [$lock])->fetchColumn() !== 1) {
            throw new RuntimeException(
                'Hay otra configuración inicial en curso. Vuelve a intentarlo.',
            );
        }
        try {
            if ((int) query('SELECT COUNT(*) FROM users')->fetchColumn() > 0) {
                header('Location: index.php?page=home');
                exit();
            }
            $step = 'creación del administrador';
            $pdo->beginTransaction();
            query('INSERT INTO users(name,email,password,role) VALUES(?,?,?,?)', [
                $name,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                'administrador',
            ]);
            if (isset($_POST['demo'])) {
                set_time_limit(120);
                require_once __DIR__ . '/app/demo.php';
                demo_data((int) $pdo->lastInsertId());
            }
            $pdo->commit();
            $_SESSION = [];
            session_regenerate_id(true);
            flash('Instalación completada. Ya puedes iniciar sesión.');
            header('Location: index.php?page=home');
        } finally {
            query('SELECT RELEASE_LOCK(?)', [$lock]);
        }
        exit();
    } catch (Throwable $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($e instanceof PDOException) {
            $code = (int) ($e->errorInfo[1] ?? 0);
            $detail = date(DATE_ATOM) . ' | ' . $step . ' | ' . $e->getMessage() . PHP_EOL;
            if (
                file_put_contents(
                    __DIR__ . '/app/install_error.log',
                    $detail,
                    FILE_APPEND | LOCK_EX,
                ) === false
            ) {
                error_log($detail);
            }
            $error =
                'No se pudo completar la configuración (MySQL ' .
                $code .
                ', paso: ' .
                $step .
                '). El detalle está en app/install_error.log.';
        } else {
            $error = $e->getMessage();
        }
    };
}
?>
<!doctype html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Empezar · KryZal</title>
        <link rel="icon" href="assets/images/ico.png?v=2">
        <link rel="stylesheet" href="assets/style.css">
    </head>
    <body class="auth">
        <main class="auth-card">
            <div class="brand">
                <img src="assets/images/logo-oscuro.svg" alt="" class="brand-icon"> KryZal
            </div>
            <h1>Bienvenido a KryZal</h1>
            <p>Crea la cuenta de administrador para empezar a utilizar el gimnasio.</p>
            <?php if ($error): ?>
            <div class="notice error" role="alert"><?= h($error) ?></div>
            <?php endif; ?>
            <form method="post"><?= csrf() ?><label>Nombre
                    <input name="name" value="<?= h($_POST['name'] ?? '') ?>" required maxlength="100"
                        autocomplete="name">
                </label>
                <label>Email
                    <input type="email" name="email" value="<?= h($_POST['email'] ?? '') ?>" required maxlength="190"
                        autocomplete="email">
                </label>
                <label>Contraseña
                    <input type="password" name="password" required <?= password_attributes() ?>>
                    <span class="password-hint"><?= h(password_hint()) ?></span>
                </label>
                <label class="check">
                    <input type="checkbox" name="demo" <?= $_SERVER['REQUEST_METHOD'] !== 'POST' || isset($_POST['demo']) ? 'checked' : '' ?>> Cargar demostración completa
                </label>
                <p class="hint">Incluye 150 socios, 8 entrenadores, 2 administradores adicionales, varios
                    meses de clases, reservas, recompensas y consultas. Todos los correos y estados de envío
                    son de demostración; no se envían mensajes externos. Puedes dejarla desmarcada para
                    empezar solo con tu administrador.</p>
                <button>Crear administrador y empezar</button>
            </form>
        </main>
    </body>
</html>
