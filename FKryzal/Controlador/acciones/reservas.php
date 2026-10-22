<?php

switch ($action) {
    case 'pin_booking':
        require_role(['usuario']);
        if ($page !== 'myclasses' || !in_array($_POST['pinned'] ?? '', ['0', '1'], true)) {
            throw new RuntimeException('La clase solo se puede fijar desde Mis clases.');
        }
        $id = (int) ($_POST['class_id'] ?? 0);
        $pinned = $_POST['pinned'] === '1';
        $pdo->beginTransaction();
        $booking = query('SELECT id FROM bookings WHERE class_id=? AND user_id=? FOR UPDATE', [
            $id,
            $me['id'],
        ])->fetch();
        if (!$booking) {
            throw new RuntimeException('La reserva no existe o no pertenece a tu cuenta.');
        }
        if ($pinned) {
            query(
                'INSERT INTO booking_pins(booking_id) VALUES(?) ON DUPLICATE KEY UPDATE booking_id=VALUES(booking_id)',
                [$booking['id']],
            );
        } else {
            query('DELETE FROM booking_pins WHERE booking_id=?', [$booking['id']]);
        }
        $pdo->commit();
        flash($pinned ? 'Clase fijada.' : 'Clase desfijada.');
        $return = class_return();
        if ($pinned) {
            $return = preg_replace('/&p=\d+/', '&p=1', $return);
        }
        go($return);
    case 'book':
        require_role(['usuario']);
        $id = (int) ($_POST['class_id'] ?? 0);
        $pdo->beginTransaction();
        $c = query('SELECT * FROM classes WHERE id=? FOR UPDATE', [$id])->fetch();
        if (!$c || $c['cancelled'] || strtotime($c['starts_at']) <= time()) {
            throw new RuntimeException('Esta clase no está disponible.');
        }
        query('SELECT id FROM users WHERE id=? FOR UPDATE', [$me['id']])->fetch();
        $conflict = query(
            'SELECT b.class_id FROM bookings b JOIN classes c ON c.id=b.class_id WHERE b.user_id=? AND c.starts_at=? AND c.cancelled=0 ORDER BY b.id LIMIT 1',
            [$me['id'], $c['starts_at']],
        )->fetch();
        if ($conflict) {
            $conflictingClassId = (int) $conflict['class_id'];
            throw new RuntimeException('Ya tienes una reserva para esa fecha y hora.');
        }
        if (
            query('SELECT COUNT(*) FROM bookings WHERE class_id=?', [$id])->fetchColumn() >=
            $c['capacity']
        ) {
            throw new RuntimeException('No quedan plazas.');
        }
        query('INSERT INTO bookings(user_id,class_id) VALUES(?,?)', [$me['id'], $id]);
        $pdo->commit();
        flash('Inscripción confirmada.');
        go(class_return());
    case 'unbook':
        require_role(['usuario']);
        $id = (int) ($_POST['class_id'] ?? 0);
        $pdo->beginTransaction();
        $c = query('SELECT * FROM classes WHERE id=? FOR UPDATE', [$id])->fetch();
        if (!$c || !cancellation_open($c)) {
            throw new RuntimeException(
                'La reserva solo se puede cancelar con al menos 2 horas de antelación.',
            );
        }
        query('DELETE FROM bookings WHERE class_id=? AND user_id=? AND attendance IS NULL', [
            $id,
            $me['id'],
        ]);
        $pdo->commit();
        flash('Inscripción cancelada.');
        go(class_return());
}
