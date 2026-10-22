<?php

switch ($action) {
    case 'login':
        $email = strtolower(trim($_POST['email'] ?? ''));
        throttle('login-ip', $_SERVER['REMOTE_ADDR'] ?? '', 30);
        throttle('login-email', $email, 10);
        $u = query('SELECT * FROM users WHERE email=?', [$email])->fetch();
        if (!$u || !password_verify($_POST['password'] ?? '', $u['password']) || !$u['active']) {
            throw new RuntimeException('Email o contraseña incorrectos, o cuenta inactiva.');
        }
        setcookie('kryzal_email', isset($_POST['remember_user']) ? $email : '', [
            'expires' => isset($_POST['remember_user']) ? time() + 90 * 86400 : time() - 3600,
            'path' => '/FKryzal/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_regenerate_id(true);
        $_SESSION['uid'] = $u['id'];
        $_SESSION['av'] = $u['auth_version'];
        go($u['force_password'] ? 'profile' : 'dashboard');
    case 'logout':
        $_SESSION = [];
        session_destroy();
        go('home');
    case 'register':
        throttle('register', $_SERVER['REMOTE_ADDR'] ?? '', 5);
        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $pw = $_POST['password'] ?? '';
        if (!$name || strlen($name) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Revisa el nombre y el email.');
        }
        password_ok($pw);
        if ($pw !== ($_POST['confirm'] ?? '')) {
            throw new RuntimeException('Las contraseñas no coinciden.');
        }
        query('INSERT INTO users(name,email,password) VALUES(?,?,?)', [
            $name,
            $email,
            password_hash($pw, PASSWORD_DEFAULT),
        ]);
        flash('Cuenta creada. Inicia sesión.');
        go('login');
    case 'forgot':
        $email = strtolower(trim($_POST['email'] ?? ''));
        throttle('reset-ip', $_SERVER['REMOTE_ADDR'] ?? '', 10);
        throttle('reset-email', $email, 3);
        $u = query('SELECT * FROM users WHERE email=? AND active=1', [$email])->fetch();
        if ($u) {
            $token = bin2hex(random_bytes(32));
            $pdo->beginTransaction();
            query('UPDATE resets SET used_at=NOW() WHERE user_id=? AND used_at IS NULL', [
                $u['id'],
            ]);
            query(
                'INSERT INTO resets(user_id,token_hash,expires_at) VALUES(?,?,DATE_ADD(NOW(),INTERVAL 30 MINUTE))',
                [$u['id'], hash('sha256', $token)],
            );
            $pdo->commit();
            try {
                mail_reset(
                    $u,
                    rtrim($config['base_url'], '/') . '/index.php?page=reset&token=' . $token,
                );
            } catch (Throwable $e) {
                query('UPDATE resets SET used_at=NOW() WHERE token_hash=?', [
                    hash('sha256', $token),
                ]);
                error_log('KryZal SMTP: ' . $e->getMessage());
            }
        }
        flash(
            'Si existe una cuenta activa con ese email, se ha preparado un enlace de recuperación.',
        );
        go('forgot');
    case 'reset':
        $token = $_POST['token'] ?? '';
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            throw new RuntimeException('Enlace no válido.');
        }
        $pw = $_POST['password'] ?? '';
        password_ok($pw);
        if ($pw !== ($_POST['confirm'] ?? '')) {
            throw new RuntimeException('Las contraseñas no coinciden.');
        }
        $pdo->beginTransaction();
        $r = query(
            'SELECT * FROM resets WHERE token_hash=? AND used_at IS NULL AND expires_at>NOW() FOR UPDATE',
            [hash('sha256', $token)],
        )->fetch();
        if (!$r) {
            throw new RuntimeException('El enlace ha caducado o ya se utilizó.');
        }
        query(
            'UPDATE users SET password=?,force_password=0,auth_version=auth_version+1 WHERE id=?',
            [password_hash($pw, PASSWORD_DEFAULT), $r['user_id']],
        );
        query('UPDATE resets SET used_at=NOW() WHERE user_id=? AND used_at IS NULL', [
            $r['user_id'],
        ]);
        $pdo->commit();
        flash('Contraseña actualizada. Inicia sesión.');
        go('login');
}
