<?php

switch ($action) {
    case 'photo':
        require_role(['administrador', 'entrenador', 'usuario']);
        ensure_photos();
        if (isset($_POST['remove_photo'])) {
            query('DELETE FROM profile_photos WHERE user_id=?', [$me['id']]);
            flash('Foto eliminada.');
            go('profile');
        }
        $file = $_FILES['photo'] ?? null;
        if (
            !$file ||
            $file['error'] !== UPLOAD_ERR_OK ||
            $file['size'] > 2 * 1024 * 1024 ||
            !is_uploaded_file($file['tmp_name'])
        ) {
            throw new RuntimeException('Sube una imagen JPG, PNG o WebP de hasta 2 MB.');
        }
        $info = @getimagesize($file['tmp_name']);
        $mime = $info['mime'] ?? '';
        if (
            !$info ||
            !in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true) ||
            $info[0] > 6000 ||
            $info[1] > 6000
        ) {
            throw new RuntimeException(
                'La imagen debe ser JPG, PNG o WebP y no superar 6000 píxeles por lado.',
            );
        }
        $image = file_get_contents($file['tmp_name']);
        query(
            'INSERT INTO profile_photos(user_id,mime,image) VALUES(?,?,?) ON DUPLICATE KEY UPDATE mime=VALUES(mime),image=VALUES(image),updated_at=CURRENT_TIMESTAMP',
            [$me['id'], $mime, $image],
        );
        flash('Foto de perfil actualizada.');
        go('profile');
    case 'profile':
        require_role(['administrador', 'entrenador', 'usuario']);
        $name = trim($_POST['name'] ?? '');
        if (!$name || strlen($name) > 100) {
            throw new RuntimeException('Nombre no válido.');
        }
        if ($me['role'] === 'entrenador') {
            $specialty = trim($_POST['specialty'] ?? '');
            if (!in_array($specialty, specialties(), true)) {
                throw new RuntimeException('Selecciona tu especialidad.');
            }
            query('UPDATE users SET name=?,specialty=? WHERE id=?', [$name, $specialty, $me['id']]);
        } else {
            query('UPDATE users SET name=? WHERE id=?', [$name, $me['id']]);
        }
        flash('Perfil actualizado.');
        go('profile');
    case 'password':
        require_role(['administrador', 'entrenador', 'usuario']);
        if (!password_verify($_POST['current'] ?? '', $me['password'])) {
            throw new RuntimeException('La contraseña actual no es correcta.');
        }
        $pw = $_POST['password'] ?? '';
        password_ok($pw);
        if ($pw !== ($_POST['confirm'] ?? '')) {
            throw new RuntimeException('Las contraseñas no coinciden.');
        }
        $pdo->beginTransaction();
        query(
            'UPDATE users SET password=?,force_password=0,auth_version=auth_version+1 WHERE id=?',
            [password_hash($pw, PASSWORD_DEFAULT), $me['id']],
        );
        query('UPDATE resets SET used_at=NOW() WHERE user_id=?', [$me['id']]);
        $pdo->commit();
        $_SESSION['av'] = $me['auth_version'] + 1;
        session_regenerate_id(true);
        flash('Contraseña actualizada.');
        go('profile');
}
