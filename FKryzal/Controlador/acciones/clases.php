<?php

switch ($action) {
    case 'create_class':
        require_role(['entrenador', 'administrador']);
        $trainer = $me['role'] === 'entrenador' ? $me['id'] : (int) ($_POST['trainer'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $_POST['starts_at'] ?? '');
        $cap = (int) ($_POST['capacity'] ?? 0);
        if (
            !$name ||
            strlen($name) > 100 ||
            !$date ||
            $date->format('Y-m-d\TH:i') !== ($_POST['starts_at'] ?? '') ||
            $date->getTimestamp() <= time() ||
            $cap < 1 ||
            $cap > 200
        ) {
            throw new RuntimeException('Revisa nombre, fecha futura y plazas (1–200).');
        }
        if (
            !query("SELECT id FROM users WHERE id=? AND role='entrenador' AND active=1", [
                $trainer,
            ])->fetch()
        ) {
            throw new RuntimeException('Selecciona un entrenador activo.');
        }
        query('INSERT INTO classes(name,starts_at,trainer_id,capacity) VALUES(?,?,?,?)', [
            $name,
            $date->format('Y-m-d H:i:s'),
            $trainer,
            $cap,
        ]);
        flash('Clase creada.');
        go('manage');
    case 'assign_trainer':
        require_role(['administrador']);
        $id = (int) ($_POST['class_id'] ?? 0);
        $trainer = (int) ($_POST['trainer'] ?? 0);
        if (
            !query(
                'SELECT c.id FROM classes c WHERE c.id=? AND c.cancelled=0 AND c.starts_at>NOW() AND NOT EXISTS(SELECT 1 FROM class_reviews r WHERE r.class_id=c.id)',
                [$id],
            )->fetch()
        ) {
            throw new RuntimeException('Solo puedes reasignar una clase futura y abierta.');
        }
        if (
            !query("SELECT id FROM users WHERE id=? AND role='entrenador' AND active=1", [
                $trainer,
            ])->fetch()
        ) {
            throw new RuntimeException('Selecciona un entrenador activo.');
        }
        query('UPDATE classes SET trainer_id=? WHERE id=?', [$trainer, $id]);
        flash('Entrenador actualizado. Las reservas se conservan.');
        $returnPage = $_POST['return_page'] ?? 'manage';
        if ($returnPage === 'attendance') {
            go('attendance&id=' . $id);
        }
        $returnPage = $returnPage === 'classes' ? 'classes' : 'manage';
        $returnParams = [];
        foreach (['q', 'view', 'day', 'timing', 'month', 'p', 'tab', 'scope'] as $key) {
            $value = $_POST['return_' . $key] ?? null;
            if (is_string($value)) {
                $returnParams[$key] = substr($value, 0, 150);
            }
        }
        go($returnPage . ($returnParams ? '&' . http_build_query($returnParams) : ''));
    case 'cancel_class':
        require_role(['entrenador', 'administrador']);
        $id = (int) ($_POST['class_id'] ?? 0);
        $pdo->beginTransaction();
        $c = query('SELECT * FROM classes WHERE id=? FOR UPDATE', [$id])->fetch();
        if (
            !$c ||
            $c['cancelled'] ||
            ($me['role'] === 'entrenador' && $c['trainer_id'] !== $me['id']) ||
            strtotime($c['starts_at']) <= time()
        ) {
            throw new RuntimeException('No puedes cancelar esta clase.');
        }
        query('UPDATE classes SET cancelled=1 WHERE id=?', [$id]);
        class_event($id, (int) $me['id'], 'cancelled', 'Cancelación manual');
        $pdo->commit();
        flash('Clase cancelada. Las inscripciones se conservan sin penalización.');
        go('manage');
    case 'remove_student':
        require_role(['entrenador', 'administrador']);
        $id = (int) ($_POST['class_id'] ?? 0);
        $student = (int) ($_POST['student_id'] ?? 0);
        $pdo->beginTransaction();
        $c = query('SELECT * FROM classes WHERE id=? FOR UPDATE', [$id])->fetch();
        if (
            !$c ||
            $c['cancelled'] ||
            strtotime($c['starts_at']) <= time() ||
            ($me['role'] === 'entrenador' && (int) $c['trainer_id'] !== (int) $me['id'])
        ) {
            throw new RuntimeException('Solo puedes retirar alumnos de tus próximas clases.');
        }
        query('DELETE FROM bookings WHERE class_id=? AND user_id=? AND attendance IS NULL', [
            $id,
            $student,
        ]);
        $pdo->commit();
        flash('Alumno retirado de la clase.');
        go('attendance&id=' . $id);
}
