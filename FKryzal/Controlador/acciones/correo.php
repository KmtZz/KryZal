<?php

switch ($action) {
    case 'mail_renew':
        require_role(['administrador']);
        if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
            throw new RuntimeException('Gestiona la recuperación desde localhost.');
        }
        $u = query('SELECT * FROM users WHERE id=? AND active=1', [
            (int) ($_POST['user_id'] ?? 0),
        ])->fetch();
        if (!$u) {
            throw new RuntimeException('Cuenta activa no encontrada.');
        }
        throttle('admin-reset', (string) $u['id'], 3);
        $token = bin2hex(random_bytes(32));
        $pdo->beginTransaction();
        query('SELECT id FROM users WHERE id=? FOR UPDATE', [$u['id']])->fetch();
        query('UPDATE resets SET used_at=NOW() WHERE user_id=? AND used_at IS NULL', [$u['id']]);
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
            query('UPDATE resets SET used_at=NOW() WHERE token_hash=?', [hash('sha256', $token)]);
            throw new RuntimeException('No se pudo enviar. Revisa SMTP y genera otro enlace.');
        }
        flash(
            $config['mail']['mode'] === 'local'
                ? 'Nuevo enlace guardado en Recuperación de cuentas. El anterior ha quedado anulado.'
                : 'Nuevo enlace enviado por correo. El anterior ha quedado anulado.',
        );
        go(
            $config['mail']['mode'] === 'local'
                ? 'mail&q=' . urlencode($u['email'])
                : 'users&q=' . urlencode($u['email']),
        );
    case 'contact_reply':
        require_role(['administrador']);
        ensure_contact_replies();
        $id = (int) ($_POST['contact_id'] ?? 0);
        $body = trim($_POST['body'] ?? '');
        $key = $_POST['request_key'] ?? '';
        $contact = query('SELECT * FROM contact_messages WHERE id=?', [$id])->fetch();
        if (
            !$contact ||
            strlen($body) < 10 ||
            strlen($body) > 10000 ||
            !preg_match('/^[a-f0-9]{64}$/', $key)
        ) {
            throw new RuntimeException('Revisa la consulta y la respuesta (10–10000 caracteres).');
        }
        $existing = query('SELECT id FROM contact_replies WHERE request_key=?', [$key])->fetch();
        if (!$existing) {
            query(
                'INSERT INTO contact_replies(contact_id,admin_id,body,request_key) VALUES(?,?,?,?)',
                [$id, $me['id'], $body, $key],
            );
            $reply = (int) $pdo->lastInsertId();
            if ($config['mail']['mode'] === 'smtp') {
                send_contact_reply($reply);
            }
        }
        flash(
            $config['mail']['mode'] === 'local'
                ? 'Respuesta guardada localmente. No se ha enviado ningún correo.'
                : 'Respuesta procesada. Consulta su estado de envío.',
        );
        go('messages&id=' . $id);
    case 'contact_send':
        require_role(['administrador']);
        ensure_contact_replies();
        if ($config['mail']['mode'] !== 'smtp') {
            throw new RuntimeException('Configura SMTP antes de enviar por correo.');
        }
        $reply = (int) ($_POST['reply_id'] ?? 0);
        $record = query('SELECT contact_id FROM contact_replies WHERE id=?', [$reply])->fetch();
        if (!$record) {
            throw new RuntimeException('Respuesta no encontrada.');
        }
        send_contact_reply($reply);
        flash('Respuesta procesada. Consulta su estado de envío.');
        go('messages&id=' . $record['contact_id']);
    case 'mail_settings':
        require_role(['administrador']);
        if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
            throw new RuntimeException('Configura el correo desde localhost.');
        }
        $mode = $_POST['mode'] ?? 'local';
        if (!in_array($mode, ['local', 'smtp'], true)) {
            throw new RuntimeException('Modo no válido.');
        }
        $url = rtrim(trim($_POST['base_url'] ?? ''), '/');
        if (
            !filter_var($url, FILTER_VALIDATE_URL) ||
            !in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)
        ) {
            throw new RuntimeException('URL no válida.');
        }
        $port = (int) ($_POST['port'] ?? 587);
        $encryption = $_POST['encryption'] ?? 'tls';
        if ($port < 1 || $port > 65535 || !in_array($encryption, ['tls', 'ssl'], true)) {
            throw new RuntimeException('Puerto o cifrado no válido.');
        }
        $from = trim($_POST['from'] ?? '');
        $host = trim($_POST['host'] ?? '');
        $user = trim($_POST['smtp_user'] ?? '');
        $pw = $_POST['smtp_password'] ?? '';
        if ($mode === 'smtp' && (!$host || !$user || !filter_var($from, FILTER_VALIDATE_EMAIL))) {
            throw new RuntimeException('Completa servidor, usuario y remitente SMTP.');
        }
        $config['base_url'] = $url;
        $config['mail'] = [
            'mode' => $mode,
            'host' => $host,
            'port' => $port,
            'user' => $user,
            'password' => $pw !== '' ? $pw : $config['mail']['password'],
            'encryption' => $encryption,
            'from' => $from,
            'name' => 'KryZal Fitness',
        ];
        if ($mode === 'smtp' && $config['mail']['password'] === '') {
            throw new RuntimeException('Introduce una contraseña de aplicación SMTP.');
        }
        if (
            file_put_contents(
                __DIR__ . '/../../app/config.php',
                "<?php\nreturn " . var_export($config, true) . ";\n",
                LOCK_EX,
            ) === false
        ) {
            throw new RuntimeException('No se pudo guardar la configuración.');
        }
        flash('Configuración guardada.');
        go('settings');
}
