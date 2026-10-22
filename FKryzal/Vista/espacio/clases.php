<?php
$staff = $me['role'] !== 'usuario';
$personal = $page === 'myclasses' && !$staff;
if (!$staff && in_array($page, ['manage', 'archive'], true)) {
    http_response_code(403);
    echo '<div class="notice error">No tienes permiso.</div>';
    return;
}
$view = ($_GET['view'] ?? '') === 'calendar' ? 'calendar' : 'cards';
$pg = max(1, (int) ($_GET['p'] ?? 1));
$q = substr(trim((string) ($_GET['q'] ?? '')), 0, 150);
$trainerArea = $me['role'] === 'entrenador' && $page === 'classes';
$scope = $trainerArea
    ? (($_GET['scope'] ?? '') === 'all' ? 'all' : 'mine')
    : (in_array($page, ['myclasses', 'manage'], true) ? 'mine' : 'all');
$classWorkspace = ($trainerArea && $scope === 'mine') ||
    ($me['role'] === 'administrador' && in_array($page, ['classes', 'manage', 'archive'], true));
$today = ($_GET['day'] ?? '') === 'today';
$selectedClass = $personal ? max(0, (int) ($_GET['class_id'] ?? 0)) : 0;
$tab = $_GET['tab'] ?? match ($_GET['timing'] ?? '') {
    'pending' => 'pending', 'upcoming' => 'upcoming', default => 'active',
};
if (!in_array($tab, ['active', 'pending', 'upcoming', 'archived'], true)) {
    $tab = 'active';
}
if ($page === 'archive') {
    $tab = 'archived';
}
$timing = in_array($tab, ['pending', 'upcoming'], true) ? $tab : 'all';
$archived = $personal && ($_GET['state'] ?? '') === 'archived';
$classArchive = $archived || ($classWorkspace && $tab === 'archived');
$classParams = [
    'page' => $page, 'view' => $view, 'q' => $q, 'scope' => $scope,
    'month' => $_GET['month'] ?? date('Y-m'), 'state' => $_GET['state'] ?? '',
    'day' => $today ? 'today' : '',
];
if ($classWorkspace || $trainerArea) {
    $classParams['tab'] = $tab;
}
if ($selectedClass) {
    $classParams['class_id'] = $selectedClass;
}
$classUrl = static function (array $changes = []) use ($classParams, $me, $classWorkspace): string {
    $params = array_replace($classParams, $changes);
    if ($classWorkspace && $me['role'] === 'administrador') {
        $params['page'] = 'classes';
    }
    return '?' . http_build_query($params);
};
$where = '1=1';
$args = [];
if ($classWorkspace) {
    if ($me['role'] === 'entrenador') {
        $where = 'c.trainer_id=?';
        $args[] = $me['id'];
    }
    $where .= $tab === 'archived'
        ? ' AND (c.cancelled=1 OR EXISTS(SELECT 1 FROM class_reviews r WHERE r.class_id=c.id))'
        : ' AND c.cancelled=0 AND NOT EXISTS(SELECT 1 FROM class_reviews r WHERE r.class_id=c.id)';
    if ($tab === 'pending' || $tab === 'upcoming') {
        $where .= $tab === 'pending' ? ' AND c.starts_at<=NOW()' : ' AND c.starts_at>NOW()';
    }
} elseif ($personal) {
    $where = 'EXISTS(SELECT 1 FROM bookings bx WHERE bx.class_id=c.id AND bx.user_id=?)';
    $args[] = $me['id'];
    $where .= $archived
        ? ' AND (c.starts_at<=NOW() OR c.cancelled=1)'
        : ' AND c.starts_at>NOW() AND c.cancelled=0';
} elseif ($page === 'classes') {
    // La agenda general muestra próximas sesiones, sin registros privados ajenos.
    $where .= ' AND c.starts_at>NOW() AND c.cancelled=0';
}
if ($selectedClass) {
    $where .= ' AND c.id=?';
    $args[] = $selectedClass;
}
if ($today) {
    $where .= ' AND c.starts_at>=? AND c.starts_at<?';
    $args[] = date('Y-m-d') . ' 00:00:00';
    $args[] = date('Y-m-d', strtotime('tomorrow')) . ' 00:00:00';
}
if ($q !== '') {
    $where .=
        $classWorkspace && $me['role'] === 'entrenador'
            ? ' AND c.name COLLATE utf8mb4_unicode_ci LIKE ?'
            : ' AND (c.name COLLATE utf8mb4_unicode_ci LIKE ? OR u.name COLLATE utf8mb4_unicode_ci LIKE ?)';
    $args[] = '%' . $q . '%';
    if (!($classWorkspace && $me['role'] === 'entrenador')) {
        $args[] = '%' . $q . '%';
    }
}
$pinSelect = $personal
    ? 'EXISTS(SELECT 1 FROM booking_pins pin JOIN bookings bp ON bp.id=pin.booking_id WHERE bp.class_id=c.id AND bp.user_id=' .
        (int) $me['id'] .
        ')'
    : '0';
$total = (int) query(
    'SELECT COUNT(*) FROM classes c JOIN users u ON u.id=c.trainer_id WHERE ' . $where,
    $args,
)->fetchColumn();
$pages = max(1, (int) ceil($total / 24));
$pg = min($pg, $pages);
$classes = query(
    'SELECT c.*,u.name trainer,u.specialty,' .
        $pinSelect .
        ' pinned,COALESCE((SELECT MAX(e.created_at) FROM class_events e WHERE e.class_id=c.id AND e.event IN (\'reviewed\',\'cancelled\',\'empty_cancelled\')),(SELECT r.reviewed_at FROM class_reviews r WHERE r.class_id=c.id),c.starts_at) archived_at,(SELECT COUNT(*) FROM bookings b WHERE b.class_id=c.id) booked,(SELECT COUNT(*) FROM bookings b WHERE b.class_id=c.id AND b.user_id=' .
        (int) $me['id'] .
        ') mine,EXISTS(SELECT 1 FROM class_reviews r WHERE r.class_id=c.id) reviewed,(SELECT b.attendance FROM bookings b WHERE b.class_id=c.id AND b.user_id=' .
        (int) $me['id'] .
        ') attendance FROM classes c JOIN users u ON u.id=c.trainer_id WHERE ' .
        $where .
        ' ORDER BY ' .
        ($personal ? 'pinned DESC,' : '') .
        ($classWorkspace && $tab === 'active' ? '(c.starts_at<=NOW()) DESC,' : '') .
        ' ' .
        ($classArchive
            ? 'archived_at DESC,c.id DESC'
            : 'c.starts_at ASC,c.id ASC') .
        ' LIMIT 24 OFFSET ' .
        ($pg - 1) * 24,
    $args,
)->fetchAll();
?>

<?php if ($selectedClass): ?>
<a class="back" href="?page=myclasses&state=<?= $archived ? 'archived' : 'upcoming' ?>">← Ver todas mis clases</a>
<?php endif; ?>
<?php if (!$selectedClass): ?>
<section class="class-controls collection-controls">
    <?php if ($classWorkspace): ?>
    <nav class="collection-tabs" aria-label="Tareas de clases">
        <?php foreach (['active' => 'Por gestionar', 'pending' => 'Completar asistencia', 'upcoming' => 'Gestionar alumnos', 'archived' => 'Archivadas'] as $key => $label): ?>
        <a class="<?= $tab === $key ? 'selected' : '' ?>" <?= $tab === $key ? 'aria-current="page"' : '' ?> href="<?= h($classUrl(['tab' => $key, 'p' => 1])) ?>"><?= $label ?></a>
        <?php endforeach; ?>
    </nav>
    <?php elseif ($personal): ?>
    <nav class="collection-tabs" aria-label="Reservas">
        <a class="<?= !$archived ? 'selected' : '' ?>" href="<?= h($classUrl(['state' => 'upcoming', 'p' => 1])) ?>">Próximas</a>
        <a class="<?= $archived ? 'selected' : '' ?>" href="<?= h($classUrl(['state' => 'archived', 'p' => 1])) ?>">Archivadas</a>
    </nav>
    <?php endif; ?>
    <form class="collection-toolbar" method="get">
        <input type="hidden" name="page" value="<?= h($page) ?>">
        <input type="hidden" name="view" value="<?= h($view) ?>">
        <input type="hidden" name="month" value="<?= h($classParams['month']) ?>">
        <input type="hidden" name="state" value="<?= h($classParams['state']) ?>">
        <?php if ($classWorkspace || $trainerArea): ?><input type="hidden" name="tab" value="<?= $tab ?>"><?php endif; ?>
        <div class="collection-search">
            <label class="visually-hidden" for="class-search">Buscar clase<?= $classWorkspace && $me['role'] === 'entrenador' ? '' : ' o entrenador' ?></label>
            <input id="class-search" type="search" name="q" value="<?= h($q) ?>" maxlength="150" placeholder="<?= $classWorkspace && $me['role'] === 'entrenador' ? 'Buscar clase…' : 'Buscar clase o entrenador…' ?>">
            <button type="submit" aria-label="Buscar clases" title="Buscar"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 4 4"/></svg></button>
        </div>
        <?php if ($trainerArea || ($page === 'classes' && !$staff)): ?>
        <label class="compact-select"><span class="visually-hidden">Agenda</span><select name="scope" data-auto-submit>
            <option value="<?= $staff ? 'mine' : 'all' ?>" <?= $scope === ($staff ? 'mine' : 'all') ? 'selected' : '' ?>><?= $staff ? 'Mis clases' : 'Todas las clases' ?></option>
            <option value="<?= $staff ? 'all' : 'mine' ?>" <?= $scope === ($staff ? 'all' : 'mine') ? 'selected' : '' ?>><?= $staff ? 'Todo el gimnasio' : 'Mis reservas' ?></option>
        </select></label>
        <?php else: ?><input type="hidden" name="scope" value="<?= $scope ?>"><?php endif; ?>
        <label class="compact-select"><span class="visually-hidden">Día</span><select name="day" data-auto-submit>
            <option value="">Cualquier día</option><option value="today" <?= $today ? 'selected' : '' ?>>Hoy</option>
        </select></label>
        <?php if ($q !== '' || $today): ?><a class="toolbar-reset" href="<?= h($classUrl(['q' => '', 'day' => '', 'p' => 1])) ?>">Limpiar</a><?php endif; ?>
        <nav class="view-toggle" aria-label="Vista de clases">
            <a class="<?= $view === 'cards' ? 'selected' : '' ?>" href="<?= h($classUrl(['view' => 'cards', 'p' => 1])) ?>" aria-label="Ver tarjetas" title="Tarjetas"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z"/></svg></a>
            <a class="<?= $view === 'calendar' ? 'selected' : '' ?>" href="<?= h($classUrl(['view' => 'calendar', 'p' => 1])) ?>" aria-label="Ver calendario" title="Calendario"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4M17 3v4M3 11h18"/></svg></a>
        </nav>
    </form>
    <?php if ($classWorkspace && $tab !== 'archived'): ?>
    <details class="class-create" <?= $error && ($_POST['action'] ?? '') === 'create_class' ? 'open' : '' ?>>
        <summary>Nueva clase
        </summary>
        <?php startform('create_class'); ?>
        <div class="form-grid">
            <?php
            field('Nombre', 'name', 'text', '', 'required maxlength="100"');
            field(
                'Fecha y hora',
                'starts_at',
                'datetime-local',
                '',
                'required data-future-date min="' . date('Y-m-d\TH:i', time() + 60) . '"',
            );
            field('Plazas', 'capacity', 'number', '12', 'required min="1" max="200"');
            if (
                $me['role'] === 'administrador'
            ): ?>
            <label>Entrenador<select name="trainer" required>
                    <option value="">Selecciona un entrenador</option>
                    <?php foreach (query("SELECT id,name FROM users WHERE role='entrenador' AND active=1")->fetchAll() as $t): ?>
                    <option value="<?= h($t['id']) ?>"><?= h($t['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <?php endif; ?>
        </div>
        <button>Crear clase</button>
        </form>
    </details>
    <?php endif; ?>
</section>
<?php endif; ?>
<?php if ($view === 'calendar'): require __DIR__ . '/../calendario.php'; else: ?>

<?php if ($total): ?>
<div class="results-toolbar">
    <p><?= $total ?> <?= $total === 1 ? 'clase' : 'clases' ?><?php if ($pages > 1): ?> · página <?= $pg ?> de <?= $pages ?><?php endif; ?></p>

<?php pagination($pg, $pages, $classParams); ?>
</div>
<?php endif; ?>
<section class="cards class-cards">

    <?php foreach ($classes as $c):
    
    $owner =
        $me['role'] === 'administrador' ||
        ($me['role'] === 'entrenador' && (int) $c['trainer_id'] === (int) $me['id']);
    $past = strtotime($c['starts_at']) <= time();
    [$appearance, $status] = class_state($c, $me, $personal);
    ?>

    <article class="class-card <?= h($appearance) ?><?= $personal && !empty($c['pinned']) ? ' pinned-class' : '' ?>" data-filter>
        <?php if ($me['role'] === 'entrenador' && $owner && $past && !$c['cancelled'] && !$c['reviewed']): ?>
        <span class="review-pin" title="Asistencia por completar" aria-label="Asistencia por completar">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="m15 3 6 6-4 1-3 4-1 4-3-3-5 5 4-6-3-3 4-1 4-3z" />
            </svg>
        </span>
        <?php endif; ?>

        <?php if (!$personal || $status !== ''): ?>
        <div class="card-top">
            <?php if ($status !== ''): ?>
            <span class="pill"><?= h($status) ?></span>
            <?php endif; ?>

            <?php if (!$personal && !$staff): ?>
            <span class="capacity-status">
                <?php if (!$past && !$c['cancelled'] && $c['booked'] >= $c['capacity']): ?>
                <span class="pill full-badge">Sin plazas</span>
                <?php elseif (!$past && !$c['cancelled']): ?>
                <?= h(max(0, $c['capacity'] - $c['booked'])) ?> <?= $c['capacity'] - $c['booked'] === 1 ? 'plaza' : 'plazas' ?>
                <?php endif; ?>
            </span>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($personal): ?>
        <div class="personal-class-heading">
            <h2><?= h($c['name']) ?></h2>
            <?php booking_pin_button($c); ?>
        </div>
        <?php else: ?>
        <h2><?= h($c['name']) ?></h2>
        <?php endif; ?>

        <?php trainer_button($c); ?>
        <div class="class-meta">
            <strong><?= date('d/m/Y · H:i', strtotime($c['starts_at'])) ?></strong>
            <?php if (!$personal): ?>
            <span><?= h($c['booked']) ?> / <?= h($c['capacity']) ?> inscritos</span>
            <?php endif; ?>
        </div>

        <?php if (($classArchive) && ($c['cancelled'] || $c['reviewed'])): ?>
        <p class="archive-date"><?= $c['cancelled'] ? 'Cancelada' : 'Archivada' ?> · <?= date('d/m/Y H:i', strtotime($c['archived_at'])) ?></p>
        <?php endif; ?>

        <?php if (!$staff && $c['mine'] && ($c['cancelled'] || $c['reviewed'] || $past)): ?>
        <p class="point-effect <?= $c['cancelled'] ? 'neutral' : ($c['reviewed'] && $c['attendance'] === 'asistio' ? 'positive' : ($c['reviewed'] && $c['attendance'] === 'no_asistio' ? 'negative' : 'neutral')) ?>">
            <?php if ($c['cancelled']): ?>
            Clase cancelada · 0 puntos
            <?php elseif ($c['reviewed']): ?>
            <?= $c['attendance'] === 'asistio' ? 'Asistió · +10 puntos' : ($c['attendance'] === 'no_asistio' ? 'No asistió · −10 puntos' : 'Sin puntos registrados') ?>
            <?php elseif ($past): ?>
            Asistencia pendiente
            <?php endif; ?>
        </p>
        <?php endif; ?>
        <div class="class-actions">
            <?php if (!$staff && !$c['cancelled'] && !$past): startform($c['mine'] ? 'unbook' : 'book'); ?>
            <input type="hidden" name="class_id" value="<?= h($c['id']) ?>">
            <button class="<?= $c['mine'] ? 'secondary' : '' ?>" <?= (!$c['mine'] && $c['booked'] >= $c['capacity']) || ($c['mine'] && !cancellation_open($c)) ? 'disabled' : '' ?> <?= $c['mine'] ? 'data-confirm="¿Cancelar tu reserva? La plaza volverá a estar disponible."' : (!cancellation_open($c) ? 'data-confirm="La clase empieza en menos de 2 horas y no podrás cancelar la reserva desde la web. ¿Reservar plaza?"' : '') ?>>
                <?= $c['mine'] ? (cancellation_open($c) ? 'Cancelar reserva' : 'Cancelación cerrada') : ($c['booked'] >= $c['capacity'] ? 'Aforo completo' : 'Reservar plaza') ?></button>
            <?php if ($c['mine'] && !cancellation_open($c)): ?>
            <small class="hint">Quedan menos de 2 horas para empezar.</small>
            <?php endif; ?>
            </form>

            <?php elseif ($staff && $owner): ?>
            <a class="button secondary" href="?page=attendance&id=<?= h($c['id']) ?>"><?= $c['reviewed'] || $c['cancelled'] ? 'Ver registro' : ($past ? ($me['role'] === 'administrador' ? 'Gestionar asistencia' : 'Completar asistencia') : 'Gestionar alumnos') ?>
            </a>

            <?php require __DIR__ . '/../includes/cambiar_entrenador.php'; ?>

            <?php if (!$past && !$c['cancelled']): startform('cancel_class'); ?>
            <input type="hidden" name="class_id" value="<?= h($c['id']) ?>">
            <button class="text-button"
                data-confirm="¿Cancelar esta clase? Los inscritos podrán verla como cancelada.">Cancelar clase
            </button>
            </form>
            <?php endif; ?>

            <?php endif; ?>
        </div>
    </article>

    <?php endforeach; ?>
</section>

<?php if (!$classes):
    if ($q !== '' || $today) {
        $emptyTitle = 'No hay clases que coincidan';
        $emptyText = 'Prueba otra búsqueda o cambia el día seleccionado.';
    } elseif ($classArchive) {
        $emptyTitle = 'Todavía no hay clases archivadas';
        $emptyText = 'Las sesiones completadas y canceladas aparecerán aquí.';
    } elseif ($classWorkspace && $tab === 'pending') {
        $emptyTitle = 'No hay asistencias pendientes';
        $emptyText = 'Las clases que comiencen con inscritos aparecerán aquí para completar su asistencia.';
    } elseif ($personal) {
        $emptyTitle = 'Todavía no tienes reservas';
        $emptyText = 'Explora Clases para encontrar una sesión y reservar tu plaza.';
    } else {
        $emptyTitle = 'No hay clases en esta vista';
        $emptyText = $classWorkspace ? 'Puedes crear una sesión desde Nueva clase.' : 'Las próximas sesiones aparecerán aquí cuando estén programadas.';
    }
?>
<section class="panel collection-empty" role="status">
    <h2><?= h($emptyTitle) ?></h2><p><?= h($emptyText) ?></p>
</section>
<?php endif; ?>

<?php pagination($pg, $pages, $classParams); ?>

<?php endif; ?>

<?php require __DIR__ . '/entrenador_tarjeta.php'; ?>
