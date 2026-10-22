<?php

switch ($action) {
    case 'user_create':
        require_role(['administrador']);
        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $pw = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? '';
        $specialty = trim($_POST['specialty'] ?? '');
        if (
            !$name ||
            strlen($name) > 100 ||
            !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            strlen($email) > 190 ||
            !in_array($role, ['usuario', 'entrenador', 'administrador'], true)
        ) {
            throw new RuntimeException('Revisa los datos de la nueva cuenta.');
        }
        password_ok($pw);
        if ($role === 'entrenador' && !in_array($specialty, specialties(), true)) {
            throw new RuntimeException('Selecciona la especialidad del entrenador.');
        }
        query(
            'INSERT INTO users(name,email,password,role,specialty,force_password) VALUES(?,?,?,?,?,1)',
            [
                $name,
                $email,
                password_hash($pw, PASSWORD_DEFAULT),
                $role,
                $role === 'entrenador' ? $specialty : '',
            ],
        );
        flash('Cuenta creada. Cambiará su contraseña temporal en el primer acceso.');
        go('users');
    case 'user_points':
        require_role(['administrador']);
        $values = [];
        foreach (['balance', 'score', 'previous_balance', 'previous_score'] as $key) {
            $value = filter_var($_POST[$key] ?? null, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => -1000000, 'max_range' => 1000000],
            ]);
            if ($value === false) {
                throw new RuntimeException(
                    'Los puntos deben ser números enteros entre −1000000 y 1000000.',
                );
            }
            $values[$key] = $value;
        }
        require_once __DIR__ . '/../../Modelo/puntosDAO.php';
        $points = new puntosDAO($pdo);
        $points->modificar(
            (int) ($_POST['id'] ?? 0),
            (int) $me['id'],
            $values['balance'],
            $values['score'],
            $values['previous_balance'],
            $values['previous_score'],
        );
        flash('Puntos actualizados.');
        go(
            'users&' .
                http_build_query([
                    'q' => $_GET['q'] ?? '',
                    'role' => $_GET['role'] ?? '',
                    'sort' => in_array(
                        $_GET['sort'] ?? '',
                        ['name_asc', 'name_desc', 'joined_desc', 'joined_asc'],
                        true,
                    )
                        ? $_GET['sort']
                        : 'name_asc',
                    'p' => max(1, (int) ($_GET['p'] ?? 1)),
                ]),
        );
    case 'user_password':
        require_role(['administrador']);
        $id = (int) ($_POST['id'] ?? 0);
        $pw = $_POST['password'] ?? '';
        password_ok($pw);
        if ($id === (int) $me['id']) {
            throw new RuntimeException('Cambia tu propia contraseña desde Mi perfil.');
        }
        $pdo->beginTransaction();
        $target = query('SELECT id FROM users WHERE id=? FOR UPDATE', [$id])->fetch();
        if (!$target) {
            throw new RuntimeException('Cuenta no encontrada.');
        }
        query(
            'UPDATE users SET password=?,force_password=1,auth_version=auth_version+1 WHERE id=?',
            [password_hash($pw, PASSWORD_DEFAULT), $id],
        );
        query('UPDATE resets SET used_at=NOW() WHERE user_id=? AND used_at IS NULL', [$id]);
        $pdo->commit();
        flash('Contraseña temporal guardada. El usuario deberá cambiarla al entrar.');
        go(
            'users&' .
                http_build_query([
                    'q' => $_GET['q'] ?? '',
                    'role' => $_GET['role'] ?? '',
                    'sort' => in_array(
                        $_GET['sort'] ?? '',
                        ['name_asc', 'name_desc', 'joined_desc', 'joined_asc'],
                        true,
                    )
                        ? $_GET['sort']
                        : 'name_asc',
                    'p' => max(1, (int) ($_GET['p'] ?? 1)),
                ]),
        );
    case 'user_save':
        require_role(['administrador']);
        $id = (int) ($_POST['id'] ?? 0);
        $role = $_POST['role'] ?? '';
        $active = isset($_POST['active']) ? 1 : 0;
        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        if (
            !$name ||
            strlen($name) > 100 ||
            strlen($email) > 190 ||
            !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            !in_array($role, ['administrador', 'entrenador', 'usuario'], true)
        ) {
            throw new RuntimeException('Datos no válidos.');
        }
        if ($id === $me['id'] && (!$active || $role !== 'administrador')) {
            throw new RuntimeException(
                'No puedes desactivar ni quitar permisos a tu propia cuenta.',
            );
        }
        $pdo->beginTransaction();
        $target = query('SELECT * FROM users WHERE id=? FOR UPDATE', [$id])->fetch();
        if (!$target) {
            throw new RuntimeException('Cuenta no encontrada.');
        }
        if (query('SELECT id FROM users WHERE email=? AND id<>?', [$email, $id])->fetch()) {
            throw new RuntimeException('Ya existe una cuenta con ese email.');
        }
        if (
            $target['role'] === 'administrador' &&
            $target['active'] &&
            (!$active || $role !== 'administrador')
        ) {
            query(
                "SELECT id FROM users WHERE role='administrador' AND active=1 FOR UPDATE",
            )->fetchAll();
            if (
                query(
                    "SELECT COUNT(*) FROM users WHERE role='administrador' AND active=1",
                )->fetchColumn() <= 1
            ) {
                throw new RuntimeException('Debe quedar un administrador activo.');
            }
        }
        if (
            $role !== $target['role'] &&
            query('SELECT COUNT(*) FROM classes WHERE trainer_id=?', [$id])->fetchColumn()
        ) {
            throw new RuntimeException('Esta cuenta tiene clases asignadas; conserva su rol.');
        }
        $specialty = trim($_POST['specialty'] ?? '');
        if ($role === 'entrenador' && !in_array($specialty, specialties(), true)) {
            throw new RuntimeException('Selecciona la especialidad del entrenador.');
        }
        query(
            'UPDATE users SET name=?,email=?,role=?,active=?,specialty=?,auth_version=auth_version+1 WHERE id=?',
            [$name, $email, $role, $active, $role === 'entrenador' ? $specialty : '', $id],
        );
        if ($email !== $target['email']) {
            query('UPDATE resets SET used_at=NOW() WHERE user_id=? AND used_at IS NULL', [$id]);
        }
        $pdo->commit();
        if ($id === $me['id']) {
            $_SESSION['av'] = $me['auth_version'] + 1;
        }
        flash('Usuario actualizado.');
        go(
            'users&' .
                http_build_query([
                    'q' => $_GET['q'] ?? '',
                    'role' => $_GET['role'] ?? '',
                    'sort' => $_GET['sort'] ?? 'name_asc',
                    'p' => max(1, (int) ($_GET['p'] ?? 1)),
                ]),
        );
}
