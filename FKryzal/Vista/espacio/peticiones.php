<?php
$id = (int) ($_GET['id'] ?? 0);
$new = isset($_GET['new']);
$admin = $me['role'] === 'administrador';
require __DIR__ . '/../includes/mensajes_origen.php';
if ($id):

    $t = ticket_for_user($id, $me);
    $messages = query(
        'SELECT m.*,u.name,u.email,u.role FROM ticket_messages m JOIN users u ON u.id=m.author_id WHERE m.ticket_id=? ORDER BY m.id',
        [$id],
    )->fetchAll();
    $returnBox = (int) $t['requester_id'] === (int) $me['id'] ? 'sent' : 'received';
    $from = query('SELECT name,email,role FROM users WHERE id=?', [$t['requester_id']])->fetch();
    $to = $t['recipient_id']
        ? query('SELECT name,email,role FROM users WHERE id=?', [$t['recipient_id']])->fetch()
        : ['name' => 'Administración', 'email' => ''];
    $class = $t['class_id']
        ? query('SELECT id,name,starts_at,trainer_id,cancelled FROM classes WHERE id=?', [$t['class_id']])->fetch()
        : null;
    $classUrl = null;
    if ($class) {
        if ($admin || ($me['role'] === 'entrenador' && (int) $class['trainer_id'] === (int) $me['id'])) {
            $classUrl = '?page=attendance&id=' . (int) $class['id'] . '&conversation=' . $id;
            if ($from['role'] === 'usuario') {
                $classUrl .= '#student-' . (int) $t['requester_id'];
            }
        } elseif ($me['role'] === 'usuario' && query('SELECT id FROM bookings WHERE class_id=? AND user_id=?', [$class['id'], $me['id']])->fetch()) {
            $classUrl = '?page=myclasses&class_id=' . (int) $class['id'] . '&state=' .
                ($class['cancelled'] || strtotime($class['starts_at']) <= time() ? 'archived' : 'upcoming');
        }
    }
?>

<a class="back" href="?page=requests&box=<?= $returnBox ?><?= $t['status'] === 'cerrada' ? '&state=cerrada' : '' ?>">← Volver a mensajes</a>
<section class="panel">
    <div class="section-head">
        <h2><?= h($t['subject']) ?></h2>
        <span class="pill <?= $t['status'] === 'cerrada' ? 'reward-active' : '' ?>"><?= h(ucfirst($t['status'])) ?></span>
    </div>
    <p>
        <span class="role-label role-<?= h($from['role']) ?>"><?= h(role_label($from['role'])) ?></span> <?= h($from['name']) ?> ·
        <?= h($from['email']) ?> <span aria-hidden="true">→</span> <?= h($to['name']) ?>
    </p>
    <?php if ($class): ?>
    <?php if ($classUrl): ?>
    <a class="ticket-context ticket-class-link" href="<?= h($classUrl) ?>">
        <span>Consulta desde <strong><?= h($class['name']) ?></strong> · <?= date('d/m/Y H:i', strtotime($class['starts_at'])) ?></span>
        <span class="ticket-class-action"><?= $me['role'] === 'usuario' ? 'Ver mi clase' : 'Abrir clase' ?> <span aria-hidden="true">→</span></span>
    </a>
    <?php else: ?>
    <p class="ticket-context">Consulta desde <?= h($class['name']) ?> · <?= date('d/m/Y H:i', strtotime($class['starts_at'])) ?></p>
    <?php endif; ?>
    <?php endif; ?>
    <p class="hint">Los mensajes se consultan dentro de KryZal. No se envían como correos externos.</p>
</section>
<section class="ticket-conversation" aria-label="Conversación">
    <?php foreach ($messages as $m): ?>
    <article class="panel ticket-message <?= (int) $m['author_id'] === (int) $me['id'] ? 'own-message' : '' ?>">
        <div class="section-head">
            <strong><?= h($m['name']) ?> <span class="role-label role-<?= h($m['role']) ?>"><?= h(role_label($m['role'])) ?>
                </span>
            </strong>
            <time><?= date('d/m/Y H:i', strtotime($m['created_at'])) ?></time>
        </div>
        <small><?= h($m['email']) ?></small>
        <pre><?= h($m['body']) ?></pre>
    </article>
    <?php endforeach; ?>
</section>
<section class="panel">
    <?php if ($t['status'] === 'abierta'): ?>
    <h2>Responder</h2>
    <?php startform('ticket_reply'); ?>
    <input type="hidden" name="ticket_id" value="<?= $id ?>">
    <input type="hidden" name="request_key" value="<?= bin2hex(random_bytes(32)) ?>">
    <label>Mensaje
        <textarea name="body" rows="5" required minlength="2" maxlength="10000"><?= h(($_POST['action'] ?? '') === 'ticket_reply' ? $_POST['body'] ?? '' : '') ?></textarea>
    </label>
    <button>Enviar respuesta</button>
    </form>
    <div class="ticket-status-action">
        <?php startform('ticket_status'); ?>
        <input type="hidden" name="ticket_id" value="<?= $id ?>">
        <input type="hidden" name="status" value="cerrada">
        <button class="secondary"
            data-confirm="¿Cerrar esta conversación? Podrás reabrirla si necesitas continuar.">Cerrar conversación
        </button>
        </form>
    </div>
    <?php else: ?>
    <h2>Conversación cerrada</h2>
    <p>La conversación se conserva. Puedes reabrirla para enviar otra respuesta.</p>
    <?php startform('ticket_status'); ?>
    <input type="hidden" name="ticket_id" value="<?= $id ?>">
    <input type="hidden" name="status" value="abierta">
    <button class="secondary">Reabrir conversación</button>
    </form>
    <?php endif; ?>
</section>

<?php
elseif ($new):

    $classId = (int) ($_GET['class_id'] ?? 0);
    $class = $classId
        ? query(
            'SELECT c.*,u.name trainer FROM classes c JOIN users u ON u.id=c.trainer_id WHERE c.id=? AND u.active=1',
            [$classId],
        )->fetch()
        : null;
    if (
        $classId &&
        (!$class ||
            ($me['role'] === 'usuario' &&
                !query('SELECT id FROM bookings WHERE class_id=? AND user_id=?', [
                    $classId,
                    $me['id'],
                ])->fetch()))
    ) {
        echo '<div class="empty">Selecciona una de tus clases para contactar con su entrenador.</div>';
        return;
    }
?>

<a class="back" href="?page=requests">← Volver a mensajes</a>
<section class="panel ticket-compose">
    <h2>Nueva conversación</h2>
    <?php if ($class): ?>
    <p>Destinatario: <strong><?= h($class['trainer']) ?></strong>
    </p>
    <?php endif; ?>

    <?php if ($class): ?>
    <p class="ticket-context"><?= h($class['name']) ?> · <?= date('d/m/Y H:i', strtotime($class['starts_at'])) ?></p>
    <?php endif; ?>
    <p class="hint">Recibirás la respuesta en Mensajes, dentro de Enviadas. El destinatario podrá ver tu nombre y correo.
    </p>
    <?php startform('ticket_create'); ?>
    <input type="hidden" name="request_key" value="<?= h(($_POST['action'] ?? '') === 'ticket_create' ? $_POST['request_key'] ?? '' : bin2hex(random_bytes(32))) ?>">
    <input type="hidden" name="class_id" value="<?= $class ? $classId : 0 ?>">
    <?php if (!$class):
    
    $recipientId = (int) ($_POST['recipient_id'] ?? ($_GET['recipient_id'] ?? 0));
    $people = $admin
        ? query(
            'SELECT id,name,role,specialty FROM users WHERE active=1 AND id<>? ORDER BY role,name',
            [$me['id']],
        )->fetchAll()
        : query(
            "SELECT id,name,role,specialty FROM users WHERE active=1 AND role='entrenador' AND id<>? ORDER BY name",
            [$me['id']],
        )->fetchAll();
    if (!in_array($recipientId, array_map('intval', array_column($people, 'id')), true)) {
        $recipientId = 0;
    }
    ?>
    <div class="recipient-search" data-recipient-picker>
        <label>Buscar destinatario
            <input type="search" data-recipient-search placeholder="<?= $admin ? 'Nombre, rol o especialidad' : 'Nombre o especialidad del entrenador' ?>" autocomplete="off">
        </label>
        <label>Destinatario<select name="recipient_id" data-recipient-select>
                <option value="0" <?= $recipientId === 0 ? 'selected' : '' ?>>Administración</option>
                <?php foreach ($people as $person): ?>
                <option value="<?= $person['id'] ?>" <?= (int) $person['id'] === $recipientId ? 'selected' : '' ?> data-search="<?= h($person['name'] . ' ' . $person['specialty'] . ' ' . $person['role']) ?>">
                    <?= h($person['name'] . ' · ' . ($person['role'] === 'entrenador' ? $person['specialty'] : ($person['role'] === 'usuario' ? 'Socio' : 'Administrador'))) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <p class="hint">Por defecto se dirige a Administración. Para otra persona, búscala y selecciónala en
            Destinatario.</p>
        <p class="recipient-empty" data-recipient-empty hidden>No hay personas que coincidan con la búsqueda.
            Puedes contactar con Administración.</p>
    </div>
    <?php else: ?>
    <p class="hint">
        <a href="?page=requests&new=1">Elegir otro destinatario sin asociar una clase</a>
    </p>
    <?php endif; ?>

    <?php field(
    'Asunto',
    'subject',
    'text',
    (string) ($_POST['subject'] ?? ($_GET['subject'] ?? '')),
    'required minlength="3" maxlength="150"',
    ); ?>
    <label>Mensaje
        <textarea name="body" rows="6" required minlength="10"
            maxlength="10000"><?= h($_POST['body'] ?? '') ?></textarea>
    </label>
    <button>Enviar mensaje</button>
    </form>
</section>

<?php
else:

    [$where, $args] = ticket_scope($me);
    $state = ($_GET['state'] ?? 'abierta') === 'cerrada' ? 'cerrada' : 'abierta';
    $box = $_GET['box'] ?? ($me['role'] === 'usuario' ? 'all' : 'received');
    if (!in_array($box, ['all', 'received', 'sent'], true)) {
        $box = 'all';
    }
    if ($box === 'sent') {
        $where .= ' AND t.requester_id=?';
        $args[] = $me['id'];
    } elseif ($box === 'received') {
        // La bandeja compartida llega a cada administrador salvo a quien la inició.
        // El ámbito de acceso anterior conserva la privacidad de las demás.
        $where .= ' AND t.requester_id<>?';
        $args[] = $me['id'];
    }
    $where .= ' AND t.status=?';
    $args[] = $state;
    $q = substr(trim((string) ($_GET['q'] ?? '')), 0, 150);
    $origin = $_GET['origin'] ?? 'all';
    if (in_array($origin, ['usuario', 'entrenador', 'administrador'], true)) {
        // El rol corresponde a la otra persona de la conversación.
        $where .= " AND (CASE WHEN t.requester_id=? THEN COALESCE(v.role,'administrador') ELSE u.role END)=?";
        $args[] = $me['id'];
        $args[] = $origin;
    } else {
        $origin = 'all';
    }
    $unreadOnly = ($_GET['unread'] ?? '') === '1';
    if ($unreadOnly) {
        $where .= ' AND EXISTS(SELECT 1 FROM ticket_messages um LEFT JOIN ticket_reads ur ON ur.ticket_id=t.id AND ur.user_id=? WHERE um.ticket_id=t.id AND um.author_id<>? AND um.id>COALESCE(ur.last_message_id,0))';
        $args[] = $me['id'];
        $args[] = $me['id'];
    }
    $folderUnread = ticket_folder_unread($me, $state);
    $listParams = ['page' => 'requests', 'box' => $box, 'state' => $state, 'origin' => $origin, 'q' => $q, 'unread' => $unreadOnly ? '1' : '0'];
    $listUrl = static fn(array $changes = []): string => '?' . h(http_build_query(array_replace($listParams, $changes)));
    if ($q !== '') {
        $where .=
            ' AND (t.subject LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR COALESCE(v.name,\'Administración\') LIKE ? OR EXISTS(SELECT 1 FROM ticket_messages m WHERE m.ticket_id=t.id AND m.body LIKE ?))';
        $args = array_merge($args, array_fill(0, 5, '%' . $q . '%'));
    }
    $join =
        ' FROM tickets t JOIN users u ON u.id=t.requester_id LEFT JOIN users v ON v.id=t.recipient_id WHERE ' .
        $where;
    $total = (int) query('SELECT COUNT(*)' . $join, $args)->fetchColumn();
    $pages = max(1, (int) ceil($total / 12));
    $pg = min(max(1, (int) ($_GET['p'] ?? 1)), $pages);
    $rows = query(
        'SELECT t.*,u.name requester,u.role requester_role,v.name recipient,(SELECT COUNT(*) FROM ticket_messages m LEFT JOIN ticket_reads r ON r.ticket_id=m.ticket_id AND r.user_id=' .
            (int) $me['id'] .
            ' WHERE m.ticket_id=t.id AND m.author_id<>' .
            (int) $me['id'] .
            ' AND m.id>COALESCE(r.last_message_id,0)) unread' .
            $join .
            ' ORDER BY t.updated_at DESC,t.id DESC LIMIT 12 OFFSET ' .
            ($pg - 1) * 12,
        $args,
    )->fetchAll();
?>

<section class="panel message-controls">
    <div class="section-head">
        <div>
            <h2><?= $admin ? 'Conversaciones internas' : 'Tus conversaciones' ?></h2>
            <p><?= $admin ? 'Consultas dirigidas a Administración y conversaciones en las que participas.' : 'Separa las consultas que recibes de las que has iniciado.' ?></p>
        </div>
        <a class="button" href="?page=requests&new=1">Nueva conversación</a>
    </div>
    <nav class="message-folders" aria-label="Bandeja de conversaciones">
        <?php foreach (['received' => 'Recibidas', 'sent' => 'Enviadas', 'all' => 'Todas'] as $key => $label): ?>
        <a class="<?= $box === $key ? 'selected' : '' ?>" <?= $box === $key ? 'aria-current="page"' : '' ?> href="<?= $listUrl(['box' => $key]) ?>"><?= $label ?><?php if ($folderUnread[$key]): ?> <span class="unread-count" aria-label="<?= $folderUnread[$key] ?> mensajes sin leer"><?= $folderUnread[$key] ?></span><?php endif; ?></a>
        <?php endforeach; ?>
    </nav>
    <div class="message-filter-row">
        <nav class="quick-filters" aria-label="Filtrar por participante">
            <?php foreach (['all' => 'Todos', 'usuario' => 'Socios', 'entrenador' => 'Entrenadores', 'administrador' => 'Administración'] as $key => $label): ?>
            <a class="<?= $origin === $key ? 'selected' : '' ?>" <?= $origin === $key ? 'aria-current="true"' : '' ?> href="<?= $listUrl(['origin' => $key]) ?>"><?= $label ?></a>
            <?php endforeach; ?>
        </nav>
    </div>
    <form method="get" class="filter-form message-search">
        <input type="hidden" name="page" value="requests">
        <input type="hidden" name="box" value="<?= $box ?>">
        <input type="hidden" name="origin" value="<?= $origin ?>">
        <label>Buscar conversación<input type="search" name="q" value="<?= h($q) ?>" placeholder="Asunto, persona o mensaje" maxlength="150"></label>
        <label>Estado<select name="state"><option value="abierta" <?= $state === 'abierta' ? 'selected' : '' ?>>Abiertas</option><option value="cerrada" <?= $state === 'cerrada' ? 'selected' : '' ?>>Cerradas</option></select></label>
        <label class="message-unread"><input type="checkbox" name="unread" value="1" <?= $unreadOnly ? 'checked' : '' ?>> Solo sin leer</label>
        <button>Buscar</button>
        <?php if ($q !== '' || $origin !== 'all' || $unreadOnly): ?>
        <a class="button secondary" href="<?= $listUrl(['q' => '', 'origin' => 'all', 'unread' => '0']) ?>">Limpiar</a>
        <?php endif; ?>
    </form>
</section>

<?php if ($rows): ?>
<div class="results-toolbar"><p><?= $total ?> <?= $total === 1 ? 'conversación' : 'conversaciones' ?> · página <?= $pg ?> de <?= $pages ?></p>
<?php pagination($pg, $pages, $listParams); ?></div>
<section class="ticket-list" aria-label="Conversaciones">
    <?php foreach ($rows as $t): ?>
    <article class="panel ticket-row">
        <div class="section-head">
            <span class="hint"><?= (int) $t['requester_id'] === (int) $me['id'] ? 'Enviada' : 'Recibida' ?> · <?= h(ucfirst($t['status'])) ?></span>
            <?php if ($t['unread']): ?>
            <span class="unread-count"><?= $t['unread'] ?> sin leer</span>
            <?php endif; ?>
        </div>
        <h2><?= h($t['subject']) ?></h2>
        <p class="ticket-origin">
            <span class="role-label role-<?= h($t['requester_role']) ?>"><?= h(role_label($t['requester_role'])) ?></span> <?= h($t['requester']) ?> →
            <?= h($t['recipient'] ?? 'Administración') ?>
        </p>
        <p class="hint">Última actividad: <?= date('d/m/Y H:i', strtotime($t['updated_at'])) ?></p>
        <a class="button secondary" href="?page=requests&id=<?= $t['id'] ?>">Abrir conversación</a>
    </article>
    <?php endforeach; ?>
</section>
<?php pagination($pg, $pages, $listParams); ?>

<?php else:$filtered = $q !== '' || $origin !== 'all' || $unreadOnly; ?>

<section class="panel ticket-empty" role="status">
    <span class="ticket-empty-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24">
            <path d="M20 11.5a8 8 0 0 1-8 8H6l-4 3V11.5a9 9 0 0 1 18 0Z" />
            <path d="M7 10h8M7 14h5" />
        </svg>
    </span>
    <h2><?= $filtered ? 'No encontramos conversaciones' : ($state === 'cerrada' ? 'No hay conversaciones cerradas en esta bandeja' : 'No hay conversaciones abiertas en esta bandeja') ?></h2>
    <p><?= $filtered ? 'Prueba con otra búsqueda o quita los filtros.' : ($state === 'cerrada' ? 'Las conversaciones que cierres aparecerán aquí.' : ($box === 'received' ? 'Aquí aparecerán las consultas dirigidas a ti' . ($admin ? ' o a Administración.' : '.') : 'Inicia una conversación con Nueva conversación.')) ?></p>
    <?php if ($filtered): ?>
    <a class="button secondary" href="<?= $listUrl(['q' => '', 'origin' => 'all', 'unread' => '0']) ?>">Limpiar búsqueda</a>
    <?php endif; ?>
</section>

<?php endif; ?>

<?php endif; ?>
