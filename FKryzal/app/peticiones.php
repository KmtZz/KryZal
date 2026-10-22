<?php
function ensure_tickets(): void
{
    ensure_contacts();
    query(
        "CREATE TABLE IF NOT EXISTS tickets(id INT PRIMARY KEY AUTO_INCREMENT,requester_id INT NOT NULL,recipient_id INT NULL,class_id INT NULL,subject VARCHAR(150) NOT NULL,status ENUM('abierta','cerrada') NOT NULL DEFAULT 'abierta',request_key CHAR(64) NOT NULL UNIQUE,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at DATETIME NOT NULL,FOREIGN KEY(requester_id) REFERENCES users(id),FOREIGN KEY(recipient_id) REFERENCES users(id),FOREIGN KEY(class_id) REFERENCES classes(id),INDEX(recipient_id,status,updated_at),INDEX(requester_id,updated_at)) ENGINE=InnoDB",
    );
    query(
        'CREATE TABLE IF NOT EXISTS ticket_messages(id INT PRIMARY KEY AUTO_INCREMENT,ticket_id INT NOT NULL,author_id INT NOT NULL,body TEXT NOT NULL,request_key CHAR(64) NOT NULL UNIQUE,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(ticket_id) REFERENCES tickets(id),FOREIGN KEY(author_id) REFERENCES users(id),INDEX(ticket_id,id)) ENGINE=InnoDB',
    );
    query(
        'CREATE TABLE IF NOT EXISTS ticket_reads(ticket_id INT NOT NULL,user_id INT NOT NULL,last_message_id INT NOT NULL DEFAULT 0,PRIMARY KEY(ticket_id,user_id),FOREIGN KEY(ticket_id) REFERENCES tickets(id),FOREIGN KEY(user_id) REFERENCES users(id)) ENGINE=InnoDB',
    );
    query(
        'CREATE TABLE IF NOT EXISTS contact_reads(contact_id INT NOT NULL,admin_id INT NOT NULL,PRIMARY KEY(contact_id,admin_id),FOREIGN KEY(contact_id) REFERENCES contact_messages(id),FOREIGN KEY(admin_id) REFERENCES users(id)) ENGINE=InnoDB',
    );
}
function ticket_scope(array $user, string $alias = 't'): array
{
    if ($user['role'] === 'administrador') {
        return [
            "($alias.recipient_id IS NULL OR $alias.recipient_id=? OR $alias.requester_id=?)",
            [$user['id'], $user['id']],
        ];
    }
    return ["($alias.requester_id=? OR $alias.recipient_id=?)", [$user['id'], $user['id']]];
}
function ticket_for_user(int $id, array $user, bool $lock = false): array
{
    [$where, $args] = ticket_scope($user);
    $t = query(
        'SELECT t.* FROM tickets t WHERE t.id=? AND ' . $where . ($lock ? ' FOR UPDATE' : ''),
        array_merge([$id], $args),
    )->fetch();
    if (!$t) {
        throw new RuntimeException('Petición no encontrada o sin permiso de acceso.');
    }
    return $t;
}
function unread_tickets(array $user): int
{
    [$where, $args] = ticket_scope($user);
    return (int) query(
        'SELECT COUNT(*) FROM ticket_messages m JOIN tickets t ON t.id=m.ticket_id LEFT JOIN ticket_reads r ON r.ticket_id=t.id AND r.user_id=? WHERE ' .
            $where .
            ' AND m.author_id<>? AND m.id>COALESCE(r.last_message_id,0)',
        array_merge([$user['id']], $args, [$user['id']]),
    )->fetchColumn();
}
function ticket_folder_unread(array $user, string $state): array
{
    [$where, $args] = ticket_scope($user);
    $row = query(
        'SELECT COALESCE(SUM(t.requester_id=?),0) sent,COALESCE(SUM(t.requester_id<>?),0) received ' .
        'FROM ticket_messages m JOIN tickets t ON t.id=m.ticket_id ' .
        'LEFT JOIN ticket_reads r ON r.ticket_id=t.id AND r.user_id=? WHERE ' . $where .
        ' AND t.status=? AND m.author_id<>? AND m.id>COALESCE(r.last_message_id,0)',
        array_merge([$user['id'], $user['id'], $user['id']], $args, [$state, $user['id']]),
    )->fetch();
    $sent = (int) $row['sent'];
    $received = (int) $row['received'];
    return ['sent' => $sent, 'received' => $received, 'all' => $sent + $received];
}

function unread_contacts(int $admin): int
{
    return (int) query(
        'SELECT COUNT(*) FROM contact_messages c WHERE NOT EXISTS(SELECT 1 FROM contact_reads r WHERE r.contact_id=c.id AND r.admin_id=?)',
        [$admin],
    )->fetchColumn();
}
function pagination(int $current, int $pages, array $params): void
{
    if ($pages < 2) {
        return;
    }
    echo '<nav class="pagination" aria-label="Páginas de resultados">';
    $link = static function (int $number, string $label) use ($params, $current): void {
        echo '<a ' .
            ($number === $current ? 'class="selected" aria-current="page" ' : '') .
            'href="?' .
            h(http_build_query(array_merge($params, ['p' => $number]))) .
            '">' .
            h($label) .
            '</a>';
    };
    if ($current > 1) {
        $link($current - 1, 'Anterior');
    }
    $visible = array_unique(
        array_merge([1], range(max(1, $current - 2), min($pages, $current + 2)), [$pages]),
    );
    sort($visible);
    $previous = 0;
    foreach ($visible as $n) {
        if ($previous && $n > $previous + 1) {
            echo '<span class="pagination-ellipsis" aria-hidden="true">…</span>';
        }
        $link($n, (string) $n);
        $previous = $n;
    }
    if ($current < $pages) {
        $link($current + 1, 'Siguiente');
    }
    echo '</nav>';
}
function cancellation_open(array $class): bool
{
    return !$class['cancelled'] && strtotime($class['starts_at']) - time() >= 7200;
}

function trainer_button(array $class): void
{
    global $me;
    $contact = (int) $class['trainer_id'] !== (int) $me['id'];
    echo '<button type="button" class="trainer-identity" data-trainer-open data-booked="' .
        ($me['role'] === 'usuario' && $class['mine'] ? '1' : '0') .
        '" data-trainer-id="' .
        (int) $class['trainer_id'] .
        '" data-class-id="' .
        (int) $class['id'] .
        '" data-trainer-name="' .
        h($class['trainer']) .
        '" data-specialty="' .
        h($class['specialty']) .
        '" data-class-name="' .
        h($class['name']) .
        '" data-class-time="' .
        h(date('d/m/Y H:i', strtotime($class['starts_at']))) .
        '" data-contact="' .
        ($contact ? '1' : '0') .
        '">';
    avatar(['id' => $class['trainer_id'], 'name' => $class['trainer']], 'avatar-small');
    echo '<span>' . h($class['trainer']) . '</span></button>';
}

function booking_pin_button(array $class): void
{
    $pinned = !empty($class['pinned']);
    $label = ($pinned ? 'Desfijar clase: ' : 'Fijar clase: ') . $class['name'];
    startform('pin_booking');
    echo '<input type="hidden" name="class_id" value="' .
        (int) $class['id'] .
        '"><input type="hidden" name="pinned" value="' .
        ($pinned ? '0' : '1') .
        '">';
    echo '<button type="submit" class="booking-pin' .
        ($pinned ? ' is-active' : '') .
        '" aria-pressed="' .
        ($pinned ? 'true' : 'false') .
        '" aria-label="' .
        h($label) .
        '" title="' .
        h($label) .
        '"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 3 6 6-4 1-3 4-1 4-3-3-5 5 4-6-3-3 4-1 4-3z"/></svg><span>' .
        ($pinned ? 'Fijada' : 'Fijar') .
        '</span></button></form>';
}

function class_state(array $class, array $user, bool $personal = false): array
{
    if ($class['cancelled']) {
        return ['cancelled-class', 'Cancelada'];
    }
    if ($class['reviewed']) {
        if ($user['role'] === 'usuario' && $class['mine']) {
            if ($class['attendance'] === 'asistio') {
                return ['attended', 'Asistió'];
            }
            if ($class['attendance'] === 'no_asistio') {
                return ['absent', 'No asistió'];
            }
        }
        return ['completed', 'Asistencia completada'];
    }
    if (strtotime($class['starts_at']) <= time()) {
        return [
            $user['role'] === 'entrenador' && (int) $class['trainer_id'] === (int) $user['id']
                ? 'needs-review'
                : 'awaiting-review',
            'Asistencia pendiente',
        ];
    }
    if ($personal && $user['role'] === 'usuario' && $class['mine']) {
        return ['', ''];
    }
    if ($class['booked'] >= $class['capacity']) {
        return ['full-class', $class['mine'] ? 'Reservada por ti' : 'Aforo completo'];
    }
    return [
        $class['mine'] ? 'reserved' : '',
        $class['mine'] ? 'Reservada por ti' : 'Próxima clase',
    ];
}

function role_label(string $role): string
{
    return ['usuario' => 'Socio', 'entrenador' => 'Entrenador', 'administrador' => 'Administrador'][
        $role
    ] ?? 'Cuenta';
}
function class_event(int $id, ?int $actor, string $event, string $details): void
{
    query('INSERT INTO class_events(class_id,actor_id,event,details) VALUES(?,?,?,?)', [
        $id,
        $actor,
        $event,
        $details,
    ]);
}
function cancel_empty_classes(): void
{
    global $pdo;
    $ids = query(
        'SELECT c.id FROM classes c WHERE c.cancelled=0 AND c.starts_at<=NOW() AND NOT EXISTS(SELECT 1 FROM bookings b WHERE b.class_id=c.id) AND NOT EXISTS(SELECT 1 FROM class_reviews r WHERE r.class_id=c.id)',
    )->fetchAll(PDO::FETCH_COLUMN);
    foreach ($ids as $id) {
        try {
            $pdo->beginTransaction();
            $c = query('SELECT * FROM classes WHERE id=? FOR UPDATE', [$id])->fetch();
            if (
                $c &&
                !$c['cancelled'] &&
                strtotime($c['starts_at']) <= time() &&
                !query('SELECT class_id FROM class_reviews WHERE class_id=?', [$id])->fetch() &&
                !query('SELECT id FROM bookings WHERE class_id=? LIMIT 1', [$id])->fetch()
            ) {
                query('UPDATE classes SET cancelled=1 WHERE id=?', [$id]);
                class_event(
                    (int) $id,
                    null,
                    'empty_cancelled',
                    'Cancelación automática al empezar sin inscritos. Sin puntos.',
                );
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
function reopen_class(int $id, array $user, int $expectedEvent): void
{
    global $pdo;
    try {
        $pdo->beginTransaction();
        $c = query('SELECT * FROM classes WHERE id=? FOR UPDATE', [$id])->fetch();
        if (
            !$c ||
            $c['cancelled'] ||
            !in_array($user['role'], ['administrador', 'entrenador'], true) ||
            ($user['role'] === 'entrenador' && (int) $c['trainer_id'] !== (int) $user['id'])
        ) {
            throw new RuntimeException('No puedes desarchivar esta clase.');
        }
        $review = query('SELECT * FROM class_reviews WHERE class_id=?', [$id])->fetch();
        $event = (int) query('SELECT COALESCE(MAX(id),0) FROM class_events WHERE class_id=?', [
            $id,
        ])->fetchColumn();
        if (!$review || $event !== $expectedEvent) {
            throw new RuntimeException(
                'El registro de la clase ha cambiado. Recarga la página antes de continuar.',
            );
        }
        $rows = query('SELECT * FROM bookings WHERE class_id=? ORDER BY user_id FOR UPDATE', [
            $id,
        ])->fetchAll();
        foreach ($rows as $r) {
            $delta =
                $r['attendance'] === 'asistio' ? -10 : ($r['attendance'] === 'no_asistio' ? 10 : 0);
            if ($delta) {
                query('UPDATE users SET points=points+? WHERE id=?', [$delta, $r['user_id']]);
            }
        }
        class_event(
            $id,
            (int) $user['id'],
            'reopened',
            json_encode(
                ['review' => $review, 'bookings' => $rows],
                JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
            ),
        );
        query('UPDATE bookings SET attendance=NULL WHERE class_id=?', [$id]);
        query('DELETE FROM class_reviews WHERE class_id=?', [$id]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}
function workspace_alerts(array $user): array
{
    $id = (int) $user['id'];
    $staff = $user['role'] !== 'usuario';
    $scope = $staff
        ? ($user['role'] === 'entrenador'
            ? 'c.trainer_id=?'
            : '1=1')
        : 'EXISTS(SELECT 1 FROM bookings b WHERE b.class_id=c.id AND b.user_id=?)';
    $args = $user['role'] === 'administrador' ? [] : [$id];
    $today = (int) query(
        'SELECT COUNT(*) FROM classes c WHERE ' .
            $scope .
            ' AND c.cancelled=0 AND c.starts_at>=NOW() AND c.starts_at<CURDATE()+INTERVAL 1 DAY AND NOT EXISTS(SELECT 1 FROM class_reviews r WHERE r.class_id=c.id)',
        $args,
    )->fetchColumn();
    $pending =
        $user['role'] === 'entrenador'
            ? (int) query(
                'SELECT COUNT(*) FROM classes c WHERE ' .
                    $scope .
                    ' AND c.cancelled=0 AND c.starts_at<=NOW() AND NOT EXISTS(SELECT 1 FROM class_reviews r WHERE r.class_id=c.id)',
                $args,
            )->fetchColumn()
            : 0;
    $tickets = unread_tickets($user);
    $contacts = $user['role'] === 'administrador' ? unread_contacts($id) : 0;
    return [
        'requests' => $tickets,
        'messages' => $contacts,
        'manage' => $pending + ($user['role'] === 'entrenador' ? $today : 0),
        'myclasses' => $staff ? 0 : $today,
        'today' => $today,
        'dashboard' =>
            $tickets + $contacts + $pending + ($user['role'] === 'administrador' ? 0 : $today),
    ];
}
