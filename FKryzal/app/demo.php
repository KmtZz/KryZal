<?php
function demo_data(int $owner): void
{
    global $pdo, $config;
    $hash = password_hash('KryzalDemo!7', PASSWORD_DEFAULT);
    $now = new DateTimeImmutable();
    $today = $now->setTime(0, 0);
    $addUser = static function (
        string $name,
        string $email,
        string $role,
        string $specialty = '',
    ) use ($hash): ?int {
        if (query('SELECT id FROM users WHERE email=?', [$email])->fetch()) {
            return null;
        }
        query('INSERT INTO users(name,email,password,role,specialty) VALUES(?,?,?,?,?)', [
            $name,
            $email,
            $hash,
            $role,
            $specialty,
        ]);
        global $pdo;
        return (int) $pdo->lastInsertId();
    };
    $admins = [$owner];
    foreach (
        [
            ['Irati Mendizabal', 'admin.irati@example.com'],
            ['Aitor Etxebarria', 'admin.aitor@example.com'],
        ]
        as $u
    ) {
        $id = $addUser($u[0], $u[1], 'administrador');
        if ($id) {
            $admins[] = $id;
        }
    }
    $trainers = [];
    $trainerUsers = [
        ['María López', 'maria.lopez@example.com', 'Entrenamiento funcional'],
        ['Ander García', 'ander.garcia@example.com', 'Fuerza'],
        ['Nerea Martín', 'nerea.martin@example.com', 'Yoga'],
        ['Jon Ruiz', 'jon.ruiz@example.com', 'Spinning'],
        ['Leire Alonso', 'leire.alonso@example.com', 'Pilates'],
        ['Unai Pérez', 'unai.perez@example.com', 'HIIT'],
        ['Ane Fernández', 'ane.fernandez@example.com', 'Movilidad articular'],
        ['Iker Santos', 'iker.santos@example.com', 'Crossfit'],
    ];
    foreach ($trainerUsers as $u) {
        $id = $addUser($u[0], $u[1], 'entrenador', $u[2]);
        if ($id) {
            $trainers[] = $id;
        }
    }
    $members = [];
    foreach (
        [
            ['Luis Fernández', 'luis.fernandez@example.com'],
            ['Carlos Sánchez', 'carlos.sanchez@example.com'],
        ]
        as $u
    ) {
        $id = $addUser($u[0], $u[1], 'usuario');
        if ($id) {
            $members[] = $id;
        }
    }
    $names = [
        'Ainhoa',
        'Beñat',
        'Clara',
        'David',
        'Elena',
        'Gorka',
        'Haizea',
        'Isabel',
        'Javier',
        'Laura',
        'Mikel',
        'Naiara',
        'Óscar',
        'Paula',
        'Raúl',
        'Sara',
    ];
    $surnames = [
        'Agirre',
        'Álvarez',
        'Arana',
        'Castro',
        'Díaz',
        'Echevarría',
        'Gómez',
        'Hernández',
        'Iglesias',
        'Lasa',
    ];
    for ($i = 0; $i < 148; $i++) {
        $id = $addUser(
            $names[$i % count($names)] . ' ' . $surnames[intdiv($i, count($names))],
            sprintf('socio%03d@example.com', $i + 1),
            'usuario',
        );
        if ($id) {
            $members[] = $id;
        }
    }
    foreach ($members as $id) {
        $joined = $today
            ->modify('-' . random_int(91, 365) . ' days')
            ->setTime(random_int(8, 19), random_int(0, 59));
        query('UPDATE users SET created_at=? WHERE id=?', [$joined->format('Y-m-d H:i:s'), $id]);
        $points = random_int(30, 220) * 10;
        query('UPDATE users SET points=? WHERE id=?', [$points, $id]);
        query(
            'INSERT INTO point_adjustments(user_id,admin_id,balance_delta,score_delta,created_at) VALUES(?,?,?,?,?)',
            [$id, $owner, $points, $points, $today->modify('-90 days')->format('Y-m-d H:i:s')],
        );
    }
    $reserved = [];
    $occupied = [];
    $insertClass = static function (
        string $name,
        DateTimeImmutable $date,
        int $trainer,
        int $capacity,
        bool $cancelled = false,
    ) use (&$occupied): ?int {
        $key = $trainer . '|' . $date->format('Y-m-d H:i:s');
        if (isset($occupied[$key])) {
            return null;
        }
        $occupied[$key] = true;
        query(
            'INSERT INTO classes(name,starts_at,trainer_id,capacity,cancelled) VALUES(?,?,?,?,?)',
            [$name, $date->format('Y-m-d H:i:s'), $trainer, $capacity, $cancelled ? 1 : 0],
        );
        global $pdo;
        $id = (int) $pdo->lastInsertId();
        if ($cancelled) {
            query(
                'INSERT INTO class_events(class_id,actor_id,event,details,created_at) VALUES(?,NULL,?,?,?)',
                [
                    $id,
                    'cancelled',
                    'Cancelación de demostración',
                    date('Y-m-d H:i:s', min($date->getTimestamp() - 86400, time() - 3600)),
                ],
            );
        }
        return $id;
    };
    if ($trainers && $members) {
        foreach (
            [
                ['Entrenamiento funcional', 0, 18],
                ['Movilidad y fuerza', 1, 19],
                ['Circuito de resistencia', 2, 18],
            ]
            as $c
        ) {
            $id = $insertClass(
                $c[0],
                $today->modify('+' . $c[1] . ' days')->setTime($c[2], 0),
                $trainers[0],
                12,
            );
            $pick = array_slice($members, 1);
            shuffle($pick);
            array_unshift($pick, $members[0]);
            foreach (array_slice($pick, 0, random_int(8, 12)) as $member) {
                query('INSERT INTO bookings(user_id,class_id,created_at) VALUES(?,?,?)', [
                    $member,
                    $id,
                    $now->format('Y-m-d H:i:s'),
                ]);
                $reserved[
                    $member .
                        '|' .
                        $today
                            ->modify('+' . $c[1] . ' days')
                            ->setTime($c[2], 0)
                            ->format('Y-m-d H:i:s')
                ] = true;
            }
        }
    }
    $titles = [
        'Entrenamiento funcional',
        'Fuerza y técnica',
        'Yoga suave',
        'Spinning',
        'Pilates',
        'HIIT',
        'Movilidad articular',
        'Crossfit',
    ];
    if ($trainers && $members) {
        for ($day = -42; $day <= 120; $day++) {
            $date = $today->modify(($day >= 0 ? '+' : '') . $day . ' days');
            if ($day !== 0 && $date->format('N') === '7') {
                continue;
            }
            foreach ([7, 12, 18] as $slot => $hour) {
                for ($parallel = 0; $parallel < 2; $parallel++) {
                    $index = (abs($day) + $slot * 2 + $parallel) % count($trainers);
                    $starts = $date->setTime($hour, 0);
                    $capacity = [10, 12, 16, 20][random_int(0, 3)];
                    $cancelled = random_int(1, 20) === 1;
                    $id = $insertClass(
                        $titles[$index % count($titles)],
                        $starts,
                        $trainers[$index],
                        $capacity,
                        $cancelled,
                    );
                    if (!$id) {
                        continue;
                    }
                    $past = $starts < $now;
                    $reviewed = $past && !$cancelled && random_int(1, 10) !== 1;
                    $count = random_int(0, 9) === 0 ? 0 : random_int(4, $capacity);
                    if (random_int(1, 8) === 1) {
                        $count = $capacity;
                    }
                    $pick = array_values(
                        array_filter(
                            $members,
                            static fn($member) => $cancelled ||
                                !isset($reserved[$member . '|' . $starts->format('Y-m-d H:i:s')]),
                        ),
                    );
                    shuffle($pick);
                    $bookingDate = $starts->modify('-' . random_int(1, 10) . ' days');
                    if ($bookingDate > $now) {
                        $bookingDate = $now;
                    }
                    foreach (array_slice($pick, 0, $count) as $member) {
                        if (!$cancelled) {
                            $reserved[$member . '|' . $starts->format('Y-m-d H:i:s')] = true;
                        }
                        $attendance = $reviewed
                            ? (random_int(1, 10) > 1
                                ? 'asistio'
                                : 'no_asistio')
                            : null;
                        query(
                            'INSERT INTO bookings(user_id,class_id,attendance,created_at) VALUES(?,?,?,?)',
                            [$member, $id, $attendance, $bookingDate->format('Y-m-d H:i:s')],
                        );
                        if ($attendance) {
                            query('UPDATE users SET points=points+? WHERE id=?', [
                                $attendance === 'asistio' ? 10 : -10,
                                $member,
                            ]);
                        }
                    }
                    if ($reviewed) {
                        query(
                            'INSERT INTO class_reviews(class_id,reviewed_by,reviewed_at) VALUES(?,?,?)',
                            [
                                $id,
                                $trainers[$index],
                                $starts->modify('+1 hour')->format('Y-m-d H:i:s'),
                            ],
                        );
                    }
                }
            }
        }
    }
    if ($members) {
        $member = $members[0];
        $absence = query(
            'SELECT b.id,b.attendance FROM bookings b JOIN class_reviews r ON r.class_id=b.class_id WHERE b.user_id=? ORDER BY r.reviewed_at DESC LIMIT 1',
            [$member],
        )->fetch();
        if ($absence && $absence['attendance'] !== 'no_asistio') {
            query("UPDATE bookings SET attendance='no_asistio' WHERE id=?", [$absence['id']]);
            query('UPDATE users SET points=points-20 WHERE id=?', [$member]);
        }
    }
    $holders = $members;
    shuffle($holders);
    $catalog = reward_catalog();
    foreach ($holders as $i => $member) {
        $balance = (int) query('SELECT points FROM users WHERE id=?', [$member])->fetchColumn();
        $eligible = array_keys(array_filter($catalog, static fn($r) => $r[1] <= $balance));
        if (!$eligible) {
            continue;
        }
        $reward = $eligible[random_int(0, count($eligible) - 1)];
        [$title, $cost] = $catalog[$reward];
        $state = $i % 3;
        $created = $now->modify(
            '-' . ($state === 2 ? random_int(35, 60) : random_int(1, 20)) . ' days',
        );
        $expires = $created->modify('+30 days');
        $used = $state === 1 ? $created->modify('+1 day')->format('Y-m-d H:i:s') : null;
        $admin = $state === 1 ? $admins[$i % count($admins)] : null;
        query(
            'INSERT INTO redemptions(user_id,reward_id,title,cost,code,request_key,expires_at,used_at,used_by,created_at) VALUES(?,?,?,?,?,?,?,?,?,?)',
            [
                $member,
                $reward,
                $title,
                $cost,
                strtoupper(bin2hex(random_bytes(12))),
                bin2hex(random_bytes(32)),
                $expires->format('Y-m-d H:i:s'),
                $used,
                $admin,
                $created->format('Y-m-d H:i:s'),
            ],
        );
        query('UPDATE users SET points=points-? WHERE id=?', [$cost, $member]);
    }
    $questions = [
        '¿Qué horarios tenéis para las clases de fuerza?',
        'Me gustaría conocer las tarifas y los servicios del gimnasio.',
        '¿Se puede reservar una sesión de entrenamiento personal?',
        '¿Hay plazas disponibles para pilates por las tardes?',
        'Quiero información sobre las clases de spinning.',
        '¿Cómo puedo cambiar una reserva de clase?',
    ];
    for ($i = 0; $i < 48; $i++) {
        $name = $names[$i % count($names)] . ' ' . $surnames[intdiv($i, count($names))];
        $created = $now->modify('-' . random_int(1, 30) . ' days');
        query(
            'INSERT INTO contact_messages(name,email,body,delivery,created_at) VALUES(?,?,?,?,?)',
            [
                $name,
                sprintf('consulta%03d@example.com', $i + 1),
                'Consulta de demostración. ' . $questions[$i % count($questions)],
                $i % 2 ? 'local' : 'enviado',
                $created->format('Y-m-d H:i:s'),
            ],
        );
        $id = (int) $pdo->lastInsertId();
        if ($i % 4 !== 0) {
            $state = ['local', 'enviado', 'fallido'][$i % 3];
            $replyDate = $created->modify('+2 hours');
            query(
                'INSERT INTO contact_replies(contact_id,admin_id,body,request_key,delivery,created_at,sent_at) VALUES(?,?,?,?,?,?,?)',
                [
                    $id,
                    $admins[$i % count($admins)],
                    'Respuesta de demostración. Gracias por contactar con KryZal. Puedes consultar los horarios y reservar desde Clases. Para información sobre tarifas, estaremos encantados de atenderte en recepción.',
                    bin2hex(random_bytes(32)),
                    $state,
                    $replyDate->format('Y-m-d H:i:s'),
                    $state === 'enviado' ? $replyDate->format('Y-m-d H:i:s') : null,
                ],
            );
        }
    }
    foreach (array_slice($members, 0, 24) as $i => $id) {
        $u = query('SELECT name,email FROM users WHERE id=?', [$id])->fetch();
        $token = bin2hex(random_bytes(32));
        $fresh = $i < 8;
        $created = $fresh ? $now : $now->modify('-' . ($i - 7) . ' days');
        $expires = $created->modify('+30 minutes');
        query('INSERT INTO resets(user_id,token_hash,expires_at) VALUES(?,?,?)', [
            $id,
            hash('sha256', $token),
            $expires->format('Y-m-d H:i:s'),
        ]);
        $link = rtrim($config['base_url'], '/') . '/index.php?page=reset&token=' . $token;
        $body =
            'Hola ' .
            $u['name'] .
            ".\n\nMensaje de demostración guardado localmente.\nPuedes establecer una nueva contraseña desde este enlace:\n" .
            $link .
            "\n\nEl enlace caduca a los 30 minutos. No se ha enviado ningún correo externo.";
        query('INSERT INTO local_mail(user_id,subject,body,created_at) VALUES(?,?,?,?)', [
            $id,
            'Demostración · Recuperar contraseña de KryZal',
            $body,
            $created->format('Y-m-d H:i:s'),
        ]);
    }
    $subjects = [
        'Duda sobre mi reserva',
        'Consulta de puntuación',
        'Horario del próximo mes',
        'Material para la clase',
        'Revisión de asistencia',
        'Cambio de actividad',
    ];
    foreach (array_slice($members, 0, 36) as $i => $member) {
        $class = query(
            'SELECT c.id,c.trainer_id,c.name,c.starts_at FROM bookings b JOIN classes c ON c.id=b.class_id WHERE b.user_id=? ORDER BY c.starts_at DESC LIMIT 1',
            [$member],
        )->fetch();
        $trainer = $i % 2 === 0 && $class ? (int) $class['trainer_id'] : null;
        $created = $now->modify('-' . ($i + 1) . ' hours');
        $closed = $i % 4 === 0;
        query(
            'INSERT INTO tickets(requester_id,recipient_id,class_id,subject,status,request_key,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?)',
            [
                $member,
                $trainer,
                $trainer ? $class['id'] : null,
                $subjects[$i % count($subjects)],
                $closed ? 'cerrada' : 'abierta',
                bin2hex(random_bytes(32)),
                $created->format('Y-m-d H:i:s'),
                $created->modify('+20 minutes')->format('Y-m-d H:i:s'),
            ],
        );
        $ticket = (int) $pdo->lastInsertId();
        query(
            'INSERT INTO ticket_messages(ticket_id,author_id,body,request_key,created_at) VALUES(?,?,?,?,?)',
            [
                $ticket,
                $member,
                'Hola, tengo una consulta sobre ' .
                ($trainer ? 'la clase ' . $class['name'] : 'mi cuenta del gimnasio') .
                '. ¿Podéis ayudarme a revisarla?',
                bin2hex(random_bytes(32)),
                $created->format('Y-m-d H:i:s'),
            ],
        );
        if ($i % 3 !== 0) {
            query(
                'INSERT INTO ticket_messages(ticket_id,author_id,body,request_key,created_at) VALUES(?,?,?,?,?)',
                [
                    $ticket,
                    $trainer ?? $admins[$i % count($admins)],
                    'Hola, hemos revisado tu consulta. Puedes comprobar la información en tu espacio y escribirnos aquí si necesitas más ayuda.',
                    bin2hex(random_bytes(32)),
                    $created->modify('+20 minutes')->format('Y-m-d H:i:s'),
                ],
            );
        }
    }
    foreach ($trainers as $i => $trainer) {
        $created = $now->modify('-' . ($i + 2) . ' hours');
        query(
            'INSERT INTO tickets(requester_id,subject,request_key,created_at,updated_at) VALUES(?,?,?,?,?)',
            [
                $trainer,
                'Consulta de planificación semanal',
                bin2hex(random_bytes(32)),
                $created->format('Y-m-d H:i:s'),
                $created->format('Y-m-d H:i:s'),
            ],
        );
        $ticket = (int) $pdo->lastInsertId();
        query(
            'INSERT INTO ticket_messages(ticket_id,author_id,body,request_key,created_at) VALUES(?,?,?,?,?)',
            [
                $ticket,
                $trainer,
                'Quería confirmar la organización de mis clases y la disponibilidad de material para la próxima semana.',
                bin2hex(random_bytes(32)),
                $created->format('Y-m-d H:i:s'),
            ],
        );
    }
}
