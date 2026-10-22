<?php

switch ($action) {
    case 'ticket_create':
        require_role(['administrador', 'entrenador', 'usuario']);
        throttle('ticket-create', (string) $me['id'], 10);
        $subject = trim($_POST['subject'] ?? '');
        $body = trim($_POST['body'] ?? '');
        $key = $_POST['request_key'] ?? '';
        $class = (int) ($_POST['class_id'] ?? 0);
        $recipient = (int) ($_POST['recipient_id'] ?? 0);
        if (
            strlen($subject) < 3 ||
            strlen($subject) > 150 ||
            strlen($body) < 10 ||
            strlen($body) > 10000 ||
            !preg_match('/^[a-f0-9]{64}$/', $key)
        ) {
            throw new RuntimeException(
                'Escribe un asunto de 3 a 150 caracteres y un mensaje de 10 a 10000.',
            );
        }
        $pdo->beginTransaction();
        if ($class) {
            $c = query('SELECT * FROM classes WHERE id=?', [$class])->fetch();
            if (!$c) {
                throw new RuntimeException('Clase no encontrada.');
            }
            if (
                $me['role'] === 'usuario' &&
                !query('SELECT id FROM bookings WHERE class_id=? AND user_id=?', [
                    $class,
                    $me['id'],
                ])->fetch()
            ) {
                throw new RuntimeException(
                    'Contacta con el entrenador desde una clase en la que estés inscrito.',
                );
            }
            $recipient = (int) $c['trainer_id'];
        }
        if ($recipient) {
            $target = query('SELECT id,role FROM users WHERE id=? AND active=1', [
                $recipient,
            ])->fetch();
            if (!$target || $recipient === (int) $me['id']) {
                throw new RuntimeException('Destinatario no válido.');
            }
            if ($me['role'] !== 'administrador' && !$class && $target['role'] !== 'entrenador') {
                throw new RuntimeException('Selecciona Administración o un entrenador activo.');
            }
        }
        $existing = query('SELECT id,requester_id FROM tickets WHERE request_key=?', [
            $key,
        ])->fetch();
        if ($existing) {
            if ((int) $existing['requester_id'] !== (int) $me['id']) {
                throw new RuntimeException('Formulario no válido.');
            }
            $id = (int) $existing['id'];
        } else {
            query(
                'INSERT INTO tickets(requester_id,recipient_id,class_id,subject,request_key,updated_at) VALUES(?,?,?,?,?,NOW())',
                [$me['id'], $recipient ?: null, $class ?: null, $subject, $key],
            );
            $id = (int) $pdo->lastInsertId();
            query(
                'INSERT INTO ticket_messages(ticket_id,author_id,body,request_key) VALUES(?,?,?,?)',
                [$id, $me['id'], $body, $key],
            );
        }
        $pdo->commit();
        flash('Mensaje enviado. Puedes seguir la conversación aquí.');
        go('requests&id=' . $id);
    case 'ticket_reply':
        require_role(['administrador', 'entrenador', 'usuario']);
        $id = (int) ($_POST['ticket_id'] ?? 0);
        $body = trim($_POST['body'] ?? '');
        $key = $_POST['request_key'] ?? '';
        if (strlen($body) < 2 || strlen($body) > 10000 || !preg_match('/^[a-f0-9]{64}$/', $key)) {
            throw new RuntimeException('Escribe un mensaje de 2 a 10000 caracteres.');
        }
        $pdo->beginTransaction();
        $t = ticket_for_user($id, $me, true);
        if ($t['status'] !== 'abierta') {
            throw new RuntimeException('Reabre la petición antes de responder.');
        }
        $existing = query('SELECT ticket_id,author_id FROM ticket_messages WHERE request_key=?', [
            $key,
        ])->fetch();
        if (
            $existing &&
            ((int) $existing['ticket_id'] !== $id ||
                (int) $existing['author_id'] !== (int) $me['id'])
        ) {
            throw new RuntimeException('Formulario no válido.');
        }
        if (!$existing) {
            query(
                'INSERT INTO ticket_messages(ticket_id,author_id,body,request_key) VALUES(?,?,?,?)',
                [$id, $me['id'], $body, $key],
            );
            query('UPDATE tickets SET updated_at=NOW() WHERE id=?', [$id]);
        }
        $pdo->commit();
        flash('Respuesta enviada.');
        go('requests&id=' . $id);
    case 'ticket_status':
        require_role(['administrador', 'entrenador', 'usuario']);
        $id = (int) ($_POST['ticket_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        if (!in_array($status, ['abierta', 'cerrada'], true)) {
            throw new RuntimeException('Estado no válido.');
        }
        $pdo->beginTransaction();
        ticket_for_user($id, $me, true);
        query('UPDATE tickets SET status=?,updated_at=NOW() WHERE id=?', [$status, $id]);
        $pdo->commit();
        flash($status === 'cerrada' ? 'Conversación cerrada.' : 'Conversación reabierta.');
        go('requests&id=' . $id);
}
