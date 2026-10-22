<?php if ($me['role'] === 'usuario'):

$future = query(
    'SELECT COUNT(*) FROM bookings b JOIN classes c ON c.id=b.class_id WHERE b.user_id=? AND c.starts_at>NOW() AND c.cancelled=0',
    [$me['id']],
)->fetchColumn();
$attended = query("SELECT COUNT(*) FROM bookings WHERE user_id=? AND attendance='asistio'", [
    $me['id'],
])->fetchColumn();
?>

<section class="hero">
    <span class="pill">KRYZAL FITNESS</span>
    <h2><?= h($me['name']) ?></h2>
    <p>Encuentra tu próxima clase y reserva un espacio para ti.</p>
    <a class="button" href="?page=classes">Explorar clases →</a>
</section>
<section class="stats">
    <article>
        <span>Tus puntos disponibles</span>
        <strong>
            <a class="stat-points" href="?page=rewards"
                aria-label="<?= h($me['points']) ?> puntos disponibles. Ver recompensas"><?= h($me['points']) ?></a>
        </strong>
        <small>Canjéalos desde Recompensas</small>
        <small class="score-hint">
            <a href="?page=ranking">Clasificación: <?= h(ranking_points((int) $me['id'])) ?> pts</a>
        </small>
    </article>
    <article>
        <span>Próximas reservas</span>
        <strong><?= h($future) ?></strong>
        <small>Clases pendientes</small>
    </article>
    <article>
        <span>Asistencias</span>
        <strong><?= h($attended) ?></strong>
        <small>Registradas por el entrenador</small>
    </article>
</section>
<div class="panel">
    <h2>Tu actividad, en un lugar</h2>
    <p>Consulta tus reservas, tu perfil y tus recompensas.</p>
    <a href="?page=myclasses">Ver mis clases →</a>
</div>

<?php
else:

    $where = $me['role'] === 'entrenador' ? 'c.trainer_id=?' : '1=1';
    $args = $me['role'] === 'entrenador' ? [$me['id']] : [];
    $pending = query(
        'SELECT COUNT(*) FROM classes c WHERE ' .
            $where .
            ' AND c.cancelled=0 AND c.starts_at<=NOW() AND NOT EXISTS(SELECT 1 FROM class_reviews r WHERE r.class_id=c.id)',
        $args,
    )->fetchColumn();
    $future = query(
        'SELECT COUNT(*) FROM classes c WHERE ' .
            $where .
            ' AND c.cancelled=0 AND c.starts_at>NOW()',
        $args,
    )->fetchColumn();
?>

<section class="hero">
    <span class="pill"><?= h(strtoupper($me['role'])) ?></span>
    <h2><?= h($me['name']) ?></h2>
    <p><?= $me['role'] === 'administrador' ? 'Revisa las sesiones del club, las consultas y la actividad de los socios.' : 'Consulta tus próximas clases y completa la asistencia pendiente.' ?></p>
    <a class="button" href="?page=classes">Gestionar clases →</a>
</section>
<section class="stats">
    <article>
        <span>Próximas clases</span>
        <strong><?= h($future) ?></strong>
        <small>Sesiones programadas</small>
    </article>
    <article>
        <span>Asistencia pendiente</span>
        <strong><?= h($pending) ?></strong>
        <small>Clases iniciadas por completar</small>
    </article>
    <article>
        <span>Tu perfil</span>
        <strong class="small-stat"><?= h($me['role'] === 'entrenador' ? $me['specialty'] : 'Administración') ?></strong>
        <small>
            <a href="?page=profile">Gestionar mi perfil</a>
        </small>
    </article>
</section>
<?php if ($pending && $me['role'] === 'entrenador'): ?>
<div class="notice warning">Tienes <?= h($pending) ?> <?= $pending == 1 ? 'clase con asistencia pendiente' : 'clases con asistencia pendiente' ?>. <a href="?page=classes&tab=pending">Completar
        asistencia →</a>
</div>
<?php endif; ?>

<?php endif; ?>

<?php
$todayCount =
    $me['role'] === 'usuario'
        ? $alerts['myclasses']
        : ($me['role'] === 'entrenador'
            ? $alerts['today']
            : 0);
if (
    $todayCount
): ?>
<section class="notice today-notice">
    <span class="notification-dot" aria-hidden="true"></span>
    <div>
        <strong>Hoy tienes <?= $todayCount ?> <?= $todayCount === 1 ? 'clase' : 'clases' ?> <?= $me['role'] === 'usuario' ? 'reservada' . ($todayCount === 1 ? '' : 's') : 'por impartir' ?></strong>
        <p>Consulta el horario y los detalles en tu agenda.</p>
    </div>
    <a class="button secondary" href="?page=<?= $me['role'] === 'usuario' ? 'myclasses' : 'classes' ?>&day=today">Ver clases de hoy</a>
</section>
<?php endif; ?>

<?php $unread = unread_tickets($me) + ($me['role'] === 'administrador' ? unread_contacts((int) $me['id']) : 0); ?>
<section class="panel inbox-summary">
    <div>
        <span class="eyebrow">COMUNICACIÓN</span>
        <h2><?= $unread ?> <?= $unread === 1 ? 'mensaje sin leer' : 'mensajes sin leer' ?></h2>
        <p><?= $me['role'] === 'administrador' ? 'Consulta las peticiones del equipo y los socios, y los mensajes de visitantes.' : 'Consulta las respuestas y peticiones recibidas en KryZal.' ?></p>
    </div>
    <div class="inline-actions">
        <a class="button secondary" href="?page=requests">Mensajes internos</a>
        <?php if ($me['role'] === 'administrador'): ?>
        <a class="button secondary" href="?page=messages">Formulario web</a>
        <?php endif; ?>
    </div>
</section>
