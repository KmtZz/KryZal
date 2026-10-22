<?php
$month = $_GET['month'] ?? date('Y-m');
$first = DateTimeImmutable::createFromFormat('!Y-m', $month);
if (
    !$first ||
    $first->format('Y-m') !== $month ||
    (int) $first->format('Y') < 2000 ||
    (int) $first->format('Y') > 2100
) {
    $first = new DateTimeImmutable('first day of this month midnight');
}
$end = $first->modify('+1 month');
$months = [
    'enero',
    'febrero',
    'marzo',
    'abril',
    'mayo',
    'junio',
    'julio',
    'agosto',
    'septiembre',
    'octubre',
    'noviembre',
    'diciembre',
];
$events = query(
    'SELECT c.*,u.name trainer,u.specialty,' .
        ($pinSelect ?? '0') .
        ' pinned,(SELECT b.attendance FROM bookings b WHERE b.class_id=c.id AND b.user_id=' .
        (int) $me['id'] .
        ') attendance,EXISTS(SELECT 1 FROM class_reviews r WHERE r.class_id=c.id) reviewed,(SELECT COUNT(*) FROM bookings b WHERE b.class_id=c.id) booked,(SELECT COUNT(*) FROM bookings b WHERE b.class_id=c.id AND b.user_id=' .
        (int) $me['id'] .
        ') mine FROM classes c JOIN users u ON u.id=c.trainer_id WHERE ' .
        $where .
        ' AND c.starts_at>=? AND c.starts_at<? ORDER BY c.starts_at',
    array_merge($args, [$first->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')]),
)->fetchAll();
$days = [];
foreach ($events as $c) {
    $days[(int) date('j', strtotime($c['starts_at']))][] = $c;
}
?>

<section class="calendar-panel">
    <div class="calendar-toolbar">
        <div>
            <span class="eyebrow"><?= $page === 'myclasses' ? 'TU AGENDA PERSONAL' : 'AGENDA DEL GIMNASIO' ?></span>
            <h2><?= h(ucfirst($months[(int) $first->format('n') - 1]) . ' ' . $first->format('Y')) ?></h2>
        </div>
        <div class="calendar-controls">
            <a class="button secondary" href="<?= h($classUrl(['month' => $first->modify('-1 month')->format('Y-m'), 'day' => ''])) ?>" aria-label="Mes anterior">←</a>
            <a class="button secondary" href="<?= h($classUrl(['month' => date('Y-m')])) ?>">Hoy</a>
            <a class="button secondary" href="<?= h($classUrl(['month' => $end->format('Y-m'), 'day' => ''])) ?>" aria-label="Mes siguiente">→</a>
        </div>
    </div>
    <div class="section-head">
        <p><?= count($events) ?> <?= count($events) === 1 ? ($page === 'myclasses' ? 'reserva' : 'sesión') : ($page === 'myclasses' ? 'reservas' : 'sesiones') ?> este mes</p>
    </div>
    <p class="calendar-swipe">Desliza el calendario hacia los lados para ver toda la semana.</p>
    <div class="calendar-scroll">
        <div class="calendar-grid">

            <?php foreach (['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $d): ?>
            <div class="calendar-weekday"><?= $d ?></div>
            <?php endforeach; ?>

            <?php for ($i = 1; $i < (int) $first->format('N'); $i++): ?>
            <div class="calendar-day outside"></div>
            <?php endfor; ?>

            <?php for ($day = 1; $day <= (int) $first->format('t'); $day++):
            $date = $first->setDate(
                (int) $first->format('Y'),
                (int) $first->format('m'),
                $day,
            ); ?>
            <div class="calendar-day <?= $date->format('Y-m-d') === date('Y-m-d') ? 'today' : '' ?>">
                <time datetime="<?= $date->format('Y-m-d') ?>"><?= $day ?></time>
                <?php foreach ($days[$day] ?? [] as $c): [$appearance, $status] = class_state($c, $me, $personal ?? false); ?>
                <article class="calendar-event <?= h($appearance) ?>" data-filter>
                    <div class="calendar-event-heading">
                        <strong><?= date('H:i', strtotime($c['starts_at'])) ?> · <?= h($c['name']) ?></strong>
                        <?php if ($personal ?? false) {
                        booking_pin_button($c);
                        } ?>
                    </div>
                    <?php trainer_button($c); ?>

                    <?php if ($status !== ''): ?>
                    <small><?= h($status) ?></small>
                    <?php endif; ?>

                    <?php if (
                        $me['role'] === 'usuario' &&
                        !$c['cancelled'] &&
                        strtotime($c['starts_at']) > time()
                    ):
                    startform($c['mine'] ? 'unbook' : 'book'); ?>
                    <input type="hidden" name="class_id" value="<?= h($c['id']) ?>">
                    <button class="<?= $c['mine'] ? 'secondary' : '' ?>" <?= (!$c['mine'] && $c['booked'] >= $c['capacity']) || ($c['mine'] && !cancellation_open($c)) ? 'disabled' : '' ?> <?= $c['mine'] ? 'data-confirm="¿Cancelar tu reserva? La plaza volverá a estar disponible."' : (!cancellation_open($c) ? 'data-confirm="La clase empieza en menos de 2 horas y no podrás cancelar la reserva desde la web. ¿Reservar plaza?"' : '') ?>>
                        <?= $c['mine'] ? (cancellation_open($c) ? 'Cancelar reserva' : 'Cancelación cerrada') : ($c['booked'] >= $c['capacity'] ? 'Aforo completo' : 'Reservar plaza') ?></button>
                    </form>
                    <?php elseif ($me['role'] === 'administrador' || ($me['role'] === 'entrenador' && (int) $c['trainer_id'] === (int) $me['id'])): ?>
                    <a class="button secondary" href="?page=attendance&id=<?= h($c['id']) ?>">
                        <?= $c['reviewed'] || $c['cancelled'] ? 'Ver registro' : (strtotime($c['starts_at']) <= time() ? ($me['role'] === 'administrador' ? 'Gestionar asistencia' : 'Completar asistencia') : 'Gestionar alumnos') ?></a>
                    <?php endif; ?>
                </article>
                <?php endforeach; ?>
            </div>
            <?php endfor; ?>

            <?php for ($i = ((int) $first->format('N') - 1 + (int) $first->format('t')) % 7; $i > 0 && $i < 7; $i++): ?>
            <div class="calendar-day outside"></div>
            <?php endfor; ?>

        </div>
    </div>
    <?php if (!$events): ?>
    <div class="empty"><?= $page === 'myclasses' ? 'No tienes reservas este mes. Puedes buscar sesiones en Clases.' : 'No hay próximas sesiones disponibles este mes. Prueba con otro mes.' ?></div>
    <?php endif; ?>
</section>
