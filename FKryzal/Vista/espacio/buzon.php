<?php
$q = substr(trim((string) ($_GET['q'] ?? '')), 0, 150);
$args = [];
$where = '1=1';
if ($q !== '') {
    $where .= ' AND (u.name LIKE ? OR u.email LIKE ?)';
    $args = ['%' . $q . '%', '%' . $q . '%'];
}
$total = (int) query(
    'SELECT COUNT(*) FROM local_mail m JOIN users u ON u.id=m.user_id WHERE ' . $where,
    $args,
)->fetchColumn();
$pages = max(1, (int) ceil($total / 12));
$pg = min(max(1, (int) ($_GET['p'] ?? 1)), $pages);
$rows = query(
    'SELECT m.*,u.name,u.email,u.active FROM local_mail m JOIN users u ON u.id=m.user_id WHERE ' .
        $where .
        ' ORDER BY m.id DESC LIMIT 12 OFFSET ' .
        ($pg - 1) * 12,
    $args,
)->fetchAll();
?>

<section class="panel">
    <h2>Recuperación de cuentas</h2>
    <p>Mensajes guardados en modo local. No se han enviado por email.</p>
    <details class="inline-help">
        <summary>Cómo usar los enlaces</summary>
        <p>Generar otro enlace anula los anteriores y crea uno válido durante 30 minutos. Abre el enlace en una ventana privada o sin sesión iniciada.</p>
        <p>Para enviar correos reales, configura SMTP. Las consultas y respuestas están en Mensajes.</p>
    </details>
    <form method="get" class="filter-form">
        <input type="hidden" name="page" value="mail">
        <label>Buscar persona
            <input type="search" name="q" value="<?= h($q) ?>" maxlength="150"
                placeholder="Nombre o correo">
        </label>
        <button>Buscar</button>
        <a class="button secondary" href="?page=mail">Limpiar</a>
    </form>
</section>
<p><?= $total ?> <?= $total === 1 ? 'mensaje' : 'mensajes' ?> · página <?= $pg ?> de <?= $pages ?></p>

<?php pagination($pg, $pages, ['page' => 'mail', 'q' => $q]); ?>

<?php foreach ($rows as $m):

preg_match('~https?://\S+~', $m['body'], $match);
$link = $match[0] ?? '';
parse_str((string) parse_url($link, PHP_URL_QUERY), $params);
$token = $params['token'] ?? '';
$reset = preg_match('/^[a-f0-9]{64}$/', $token)
    ? query('SELECT expires_at,used_at FROM resets WHERE user_id=? AND token_hash=?', [
        $m['user_id'],
        hash('sha256', $token),
    ])->fetch()
    : false;
$valid = $reset && !$reset['used_at'] && strtotime($reset['expires_at']) > time();
?>
<article class="panel local-message">
    <div class="section-head">
        <h2><?= h($m['name']) ?></h2>
        <span class="pill <?= $valid ? 'reward-active' : '' ?>"><?= $valid ? 'Enlace vigente' : ($reset && $reset['used_at'] ? 'Utilizado o sustituido' : 'Enlace caducado') ?></span>
    </div>
    <p><?= h($m['email']) ?> · <?= date('d/m/Y H:i', strtotime($m['created_at'])) ?></p>
    <details>
        <summary>Ver mensaje de recuperación</summary>
        <pre><?= h($m['body']) ?></pre>
    </details>
    <p class="copy-feedback" role="status"></p>
    <div class="inline-actions">
        <?php if ($valid): ?>
        <button type="button" class="secondary" data-copy-text="<?= h(rtrim($config['base_url'], '/') . '/index.php?page=reset&token=' . $token) ?>">Copiar enlace</button>
        <?php endif; ?>

        <?php if ($m['active']): startform('mail_renew'); ?>
        <input type="hidden" name="user_id" value="<?= $m['user_id'] ?>">
        <button class="secondary"
            data-confirm="¿Generar un enlace nuevo para <?= h($m['email']) ?>? Los anteriores dejarán de funcionar. No se enviará por email en modo local.">
            Generar nuevo enlace</button>
        </form>
        <?php endif; ?>
    </div>
</article>
<?php endforeach; ?>

<?php if (!$rows): ?>
<div class="empty">No hay correos de recuperación con estos filtros.</div>
<?php endif; ?>

<?php pagination($pg, $pages, ['page' => 'mail', 'q' => $q]); ?>
