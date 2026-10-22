<?php
ensure_contact_replies();
require __DIR__ . '/../includes/mensajes_origen.php';
$id = (int) ($_GET['id'] ?? 0);
if ($id):

    $contact = query('SELECT * FROM contact_messages WHERE id=?', [$id])->fetch();
    if (!$contact) {
        http_response_code(404);
        echo '<div class="empty">Consulta no encontrada.</div>';
        return;
    }
    query('INSERT IGNORE INTO contact_reads(contact_id,admin_id) VALUES(?,?)', [$id, $me['id']]);
    $replies = query(
        'SELECT r.*,u.name admin FROM contact_replies r JOIN users u ON u.id=r.admin_id WHERE r.contact_id=? ORDER BY r.id',
        [$id],
    )->fetchAll();
?>

<a class="back" href="?page=messages">← Volver a consultas</a>
<section class="panel contact-thread">
    <h2><?= h($contact['name']) ?></h2>
    <p><?= h($contact['email']) ?> · <?= date('d/m/Y H:i', strtotime($contact['created_at'])) ?></p>
    <pre><?= h($contact['body']) ?></pre>
</section>

<?php foreach ($replies as $reply): ?>
<article class="panel contact-reply">
    <div class="section-head">
        <h2>Respuesta de <?= h($reply['admin']) ?></h2>
        <span class="pill <?= $reply['delivery'] === 'enviado' ? 'reward-active' : '' ?>"><?= h(['local' => 'Guardada localmente', 'enviando' => 'Envío en curso', 'enviado' => 'Enviada por correo', 'fallido' => 'Envío fallido',][$reply['delivery']]) ?></span>
    </div>
    <p><?= date('d/m/Y H:i', strtotime($reply['created_at'])) ?></p>
    <pre><?= h($reply['body']) ?></pre>
    <?php if ($reply['delivery'] === 'local'): ?>
    <p class="hint">Esta respuesta no se ha enviado al destinatario.</p>
    <?php elseif ($reply['delivery'] === 'fallido'): ?>
    <p class="notice error">No se pudo enviar. Revisa Configuración antes de intentarlo de nuevo.</p>
    <?php elseif ($reply['delivery'] === 'enviado'): ?>
    <p class="hint">Enviada el <?= date('d/m/Y H:i', strtotime($reply['sent_at'])) ?></p>
    <?php endif; ?>

    <?php if ($config['mail']['mode'] === 'smtp' && in_array($reply['delivery'], ['local', 'fallido'], true)): startform('contact_send'); ?>
    <input type="hidden" name="reply_id" value="<?= h($reply['id']) ?>">
    <button data-confirm="¿Enviar esta respuesta a <?= h($contact['email']) ?>?">Enviar por correo</button>
    </form>
    <?php endif; ?>
</article>
<?php endforeach; ?>

<section class="panel">
    <h2>Responder a la consulta</h2>
    <p>Destinatario: <?= h($contact['email']) ?></p>
    <?php if ($config['mail']['mode'] === 'local'): ?>
    <div class="notice">Modo local: la respuesta quedará guardada aquí sin enviarse. Configura SMTP para
        enviar correos.</div>
    <?php endif; ?>

    <?php startform('contact_reply'); ?>
    <input type="hidden" name="contact_id" value="<?= $id ?>">
    <input type="hidden" name="request_key" value="<?= h(($_POST['action'] ?? '') === 'contact_reply' ? $_POST['request_key'] ?? '' : bin2hex(random_bytes(32))) ?>">
    <label>Respuesta
        <textarea name="body" rows="6" required minlength="10"
            maxlength="10000"><?= h($_POST['body'] ?? '') ?></textarea>
    </label>
    <button data-confirm="<?= $config['mail']['mode'] === 'local' ? '¿Guardar la respuesta localmente, sin enviar correo?' : '¿Enviar la respuesta por correo a ' . h($contact['email']) . '?' ?>"><?= $config['mail']['mode'] === 'local' ? 'Guardar respuesta local' : 'Enviar respuesta' ?></button>
    </form>
</section>

<?php
else:

    $q = substr(trim((string) ($_GET['q'] ?? '')), 0, 150);
    $state = $_GET['state'] ?? 'all';
    $origin = $_GET['origin'] ?? 'all';
    $unreadOnly = ($_GET['unread'] ?? '') === '1';
    $where = '1=1';
    $args = [];
    if ($q !== '') {
        $where .=
            ' AND (c.name COLLATE utf8mb4_unicode_ci LIKE ? OR c.email COLLATE utf8mb4_unicode_ci LIKE ? OR c.body COLLATE utf8mb4_unicode_ci LIKE ?)';
        $args = array_fill(0, 3, '%' . $q . '%');
    }
    if ($unreadOnly) {
        $where .= ' AND NOT EXISTS(SELECT 1 FROM contact_reads cr WHERE cr.contact_id=c.id AND cr.admin_id=?)';
        $args[] = $me['id'];
    }
    if ($origin === 'registered') {
        $where .= ' AND EXISTS(SELECT 1 FROM users u WHERE u.email=c.email)';
    } elseif ($origin === 'visitor') {
        $where .= ' AND NOT EXISTS(SELECT 1 FROM users u WHERE u.email=c.email)';
    } else {
        $origin = 'all';
    }
    $answered =
        "EXISTS(SELECT 1 FROM contact_replies r WHERE r.contact_id=c.id AND r.delivery='enviado')";
    if ($state === 'pending') {
        $where .= ' AND NOT ' . $answered;
    } elseif ($state === 'answered') {
        $where .= ' AND ' . $answered;
    } else {
        $state = 'all';
    }
    $total = (int) query(
        'SELECT COUNT(*) FROM contact_messages c WHERE ' . $where,
        $args,
    )->fetchColumn();
    $pages = max(1, (int) ceil($total / 12));
    $pg = min(max(1, (int) ($_GET['p'] ?? 1)), $pages);
    $rows = query(
        'SELECT c.*,NOT EXISTS(SELECT 1 FROM contact_reads cr WHERE cr.contact_id=c.id AND cr.admin_id=' . (int) $me['id'] . ') unread,' .
            $answered .
            ' answered,(SELECT COUNT(*) FROM contact_replies r WHERE r.contact_id=c.id) replies FROM contact_messages c WHERE ' .
            $where .
            ' ORDER BY c.id DESC LIMIT 12 OFFSET ' .
            ($pg - 1) * 12,
        $args,
    )->fetchAll();
?>

<section class="collection-controls contact-controls">
    <nav class="collection-tabs" aria-label="Estado de las consultas">
        <?php foreach (['all' => 'Todas', 'pending' => 'Pendientes de envío', 'answered' => 'Respondidas por correo'] as $key => $label): ?>
        <a class="<?= $state === $key ? 'selected' : '' ?>" <?= $state === $key ? 'aria-current="page"' : '' ?> href="?<?= h(http_build_query(['page' => 'messages', 'state' => $key, 'q' => $q, 'origin' => $origin, 'unread' => $unreadOnly ? '1' : '0'])) ?>"><?= $label ?></a>
        <?php endforeach; ?>
    </nav>
    <form method="get" class="collection-toolbar">
        <input type="hidden" name="page" value="messages">
        <input type="hidden" name="state" value="<?= h($state) ?>">
        <div class="collection-search">
            <label class="visually-hidden" for="contact-search">Buscar nombre, correo o mensaje</label>
            <input id="contact-search" type="search" name="q" value="<?= h($q) ?>" maxlength="150" placeholder="Buscar nombre, correo o mensaje…">
            <button type="submit" aria-label="Buscar consultas" title="Buscar"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 4 4"/></svg></button>
        </div>
        <label class="compact-select"><span class="visually-hidden">Correo del remitente</span><select name="origin" data-auto-submit>
            <option value="all">Cualquier correo</option>
            <option value="visitor" <?= $origin === 'visitor' ? 'selected' : '' ?>>Sin cuenta registrada</option>
            <option value="registered" <?= $origin === 'registered' ? 'selected' : '' ?>>Con cuenta registrada</option>
        </select></label>
        <label class="compact-select"><span class="visually-hidden">Lectura</span><select name="unread" data-auto-submit>
            <option value="0">Leídas y sin leer</option>
            <option value="1" <?= $unreadOnly ? 'selected' : '' ?>>Solo sin leer</option>
        </select></label>
        <?php if ($q !== '' || $origin !== 'all' || $state !== 'all' || $unreadOnly): ?>
        <a class="toolbar-reset" href="?page=messages">Limpiar</a>
        <?php endif; ?>
    </form>
    <p class="contact-origin-note">Enviadas desde Contacto. La coincidencia del correo con una cuenta no verifica la identidad del remitente.</p>
</section>
<?php if ($total): ?>
<div class="results-toolbar"><p><?= $total ?> <?= $total === 1 ? 'consulta' : 'consultas' ?></p>
<?php pagination($pg, $pages, ['page' => 'messages', 'q' => $q, 'state' => $state, 'origin' => $origin, 'unread' => $unreadOnly ? '1' : '0']); ?>
</div>
<?php endif; ?>

<?php foreach ($rows as $message): ?>
<article class="panel contact-preview">
    <div class="section-head">
        <h2><?= h($message['name']) ?><?php if ($message['unread']): ?> <span class="unread-count">Sin leer</span><?php endif; ?></h2>
        <span class="pill <?= $message['answered'] ? 'reward-active' : '' ?>"><?= $message['answered'] ? 'Respondida por correo' : ($message['replies'] ? 'Respuesta guardada' : 'Sin responder') ?></span>
    </div>
    <p><?= h($message['email']) ?> · <?= date('d/m/Y H:i', strtotime($message['created_at'])) ?></p>
    <p class="contact-excerpt"><?= h($message['body']) ?></p>
    <a class="button secondary" href="?page=messages&id=<?= h($message['id']) ?>">Abrir consulta</a>
</article>
<?php endforeach; ?>

<?php if (!$rows): ?>
<section class="panel ticket-empty" role="status">
    <h2><?= $q !== '' || $origin !== 'all' || $state !== 'all' || $unreadOnly ? 'No encontramos consultas' : 'Todavía no hay consultas del formulario web' ?></h2>
    <p><?= $q !== '' || $origin !== 'all' || $state !== 'all' || $unreadOnly ? 'Prueba otra búsqueda o limpia los filtros.' : 'Los mensajes enviados desde Contacto aparecerán aquí.' ?></p>
</section>
<?php endif; ?>

<?php pagination($pg, $pages, ['page' => 'messages', 'q' => $q, 'state' => $state, 'origin' => $origin, 'unread' => $unreadOnly ? '1' : '0']); ?>

<?php endif; ?>
