<?php
if ($me['role'] !== 'administrador') {
    http_response_code(403);
    echo '<div class="notice error">No tienes permiso.</div>';
    return;
}
$history = ($_GET['view'] ?? '') === 'history';
?>

<div class="class-view-switch">
    <a class="<?= !$history ? 'selected' : '' ?>" href="?page=rewardcheck">Validar código</a>
    <a class="<?= $history ? 'selected' : '' ?>" href="?page=rewardcheck&view=history">Historial de códigos</a>
</div>

<?php if (!$history):

$code = strtoupper(trim((string) ($_GET['code'] ?? '')));
$reward =
    $code !== ''
        ? query(
            'SELECT r.*,u.name,u.email,a.name validator FROM redemptions r JOIN users u ON u.id=r.user_id LEFT JOIN users a ON a.id=r.used_by WHERE r.code=?',
            [$code],
        )->fetch()
        : null;
?>

<section class="panel">
    <h2>Validar recompensa</h2>
    <p>Consulta el código del socio. Márcalo como utilizado cuando entregues el premio.</p>
    <form method="get">
        <input type="hidden" name="page" value="rewardcheck">
        <?php field('Código de recompensa', 'code', 'text', $code, 'required maxlength="24"'); ?>
        <button>Consultar código</button>
    </form>
</section>

<?php if ($code !== '' && !$reward): ?>
<div class="notice error">No existe una recompensa con ese código.</div>
<?php elseif ($reward): $active = !$reward['used_at'] && strtotime($reward['expires_at']) > time(); ?>
<section class="panel reward-validation">
    <h2><?= h($reward['title']) ?></h2>
    <p><?= h($reward['name']) ?> · <?= h($reward['email']) ?></p>
    <p>Canjeada el <?= date('d/m/Y H:i', strtotime($reward['created_at'])) ?> · <?= h($reward['cost']) ?> puntos</p>
    <p>Caduca el <?= date('d/m/Y H:i', strtotime($reward['expires_at'])) ?></p>
    <div class="reward-status">
        <span class="pill <?= $active ? 'reward-active' : '' ?>"><?= $reward['used_at'] ? 'Utilizada' : ($active ? 'Activa' : 'Caducada') ?></span>
    </div>
    <?php if ($reward['used_at']): ?>
    <p>Utilizada el <?= date('d/m/Y H:i', strtotime($reward['used_at'])) ?> · Validada por <?= h($reward['validator'] ?? 'Administración') ?></p>
    <?php endif; ?>

    <?php if ($active): startform('use_reward'); ?>
    <input type="hidden" name="code" value="<?= h($reward['code']) ?>">
    <button data-confirm="¿Entregar esta recompensa y marcar el código como utilizado?">Marcar como utilizada
    </button>
    </form>
    <?php endif; ?>
    <a class="back" href="?page=rewardcheck&view=history">Ver historial de códigos →</a>
</section>
<?php endif; ?>

<?php
else:

    $q = substr(trim((string) ($_GET['q'] ?? '')), 0, 150);
    $state = $_GET['state'] ?? 'archived';
    $type = (int) ($_GET['type'] ?? 0);
    $clauses = [
        'all' => '1=1',
        'active' => 'r.used_at IS NULL AND r.expires_at>NOW()',
        'used' => 'r.used_at IS NOT NULL',
        'expired' => 'r.used_at IS NULL AND r.expires_at<=NOW()',
        'archived' => '(r.used_at IS NOT NULL OR r.expires_at<=NOW())',
    ];
    if (!isset($clauses[$state])) {
        $state = 'archived';
    }
    $where = $clauses[$state];
    $args = [];
    if ($q !== '') {
        $where .=
            ' AND (u.name COLLATE utf8mb4_unicode_ci LIKE ? OR u.email COLLATE utf8mb4_unicode_ci LIKE ? OR r.title COLLATE utf8mb4_unicode_ci LIKE ? OR r.code COLLATE utf8mb4_unicode_ci LIKE ?)';
        $args = array_fill(0, 4, '%' . $q . '%');
    }
    if ($type > 0) {
        $where .= ' AND r.reward_id=?';
        $args[] = $type;
    }
    $total = (int) query(
        'SELECT COUNT(*) FROM redemptions r JOIN users u ON u.id=r.user_id WHERE ' . $where,
        $args,
    )->fetchColumn();
    $pages = max(1, (int) ceil($total / 12));
    $pg = min(max(1, (int) ($_GET['p'] ?? 1)), $pages);
    $rows = query(
        'SELECT r.*,u.name,u.email,a.name validator FROM redemptions r JOIN users u ON u.id=r.user_id LEFT JOIN users a ON a.id=r.used_by WHERE ' .
            $where .
            ' ORDER BY r.id DESC LIMIT 12 OFFSET ' .
            ($pg - 1) * 12,
        $args,
    )->fetchAll();
    $params = http_build_query([
        'page' => 'rewardcheck',
        'view' => 'history',
        'q' => $q,
        'state' => $state,
        'type' => $type,
    ]);
?>

<section class="panel">
    <h2>Historial de códigos</h2>
    <p>Consulta quién canjeó cada recompensa, su estado y quién validó la entrega.</p>
    <form method="get" class="reward-filters">
        <input type="hidden" name="page" value="rewardcheck">
        <input type="hidden" name="view" value="history">
        <label>Socio, correo, premio o código
            <input name="q" value="<?= h($q) ?>" maxlength="150">
        </label>
        <label>Tipo de recompensa<select name="type">
                <option value="0">Todos los premios</option>
                <?php foreach (query('SELECT DISTINCT reward_id,title FROM redemptions ORDER BY title')->fetchAll() as $t): ?>
                <option value="<?= h($t['reward_id']) ?>" <?= $type === (int) $t['reward_id'] ? 'selected' : '' ?>><?= h($t['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Estado<select name="state">
                <?php foreach (
                [
                    'archived' => 'Archivadas',
                    'all' => 'Todos',
                    'active' => 'Activas',
                    'used' => 'Utilizadas',
                    'expired' => 'Caducadas',
                ]
                as $key => $label
                ): ?>
                <option value="<?= $key ?>" <?= $state === $key ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button>Buscar</button>
        <a class="button secondary" href="?page=rewardcheck&view=history">Limpiar</a>
    </form>
</section>
<p><?= $total ?> <?= $total === 1 ? 'código' : 'códigos' ?> · página <?= $pg ?> de <?= $pages ?></p>
<?php pagination($pg, $pages, ['page' => 'rewardcheck', 'view' => 'history', 'q' => $q, 'state' => $state, 'type' => $type,]); ?>
<section class="cards reward-history">
    <?php foreach ($rows as $r): $used = (bool) $r['used_at']; $active = !$used && strtotime($r['expires_at']) > time(); ?>
    <article class="panel">
        <span class="pill <?= $active ? 'reward-active' : '' ?>"><?= $used ? 'Utilizada' : ($active ? 'Activa' : 'Caducada') ?></span>
        <h2><?= h($r['title']) ?></h2>
        <p>
            <strong><?= h($r['name']) ?></strong>
            <br><?= h($r['email']) ?>
        </p>
        <strong class="reward-code"><?= h($r['code']) ?></strong>
        <p>Canjeada el <?= date('d/m/Y H:i', strtotime($r['created_at'])) ?> · <?= h($r['cost']) ?> puntos</p>
        <p><?= $used ? 'Utilizada el ' . date('d/m/Y H:i', strtotime($r['used_at'])) : 'Caduca el ' . date('d/m/Y H:i', strtotime($r['expires_at'])) ?></p>
        <?php if ($used): ?>
        <p>Validada por <?= h($r['validator'] ?? 'Administración') ?></p>
        <?php endif; ?>
        <a class="button secondary" href="?page=rewardcheck&code=<?= h($r['code']) ?>">Ver código</a>
    </article>
    <?php endforeach; ?>
</section>
<?php if (!$rows): ?>
<div class="empty">No hay códigos con estos filtros.</div>
<?php endif; ?>

<?php pagination($pg, $pages, ['page' => 'rewardcheck', 'view' => 'history', 'q' => $q, 'state' => $state, 'type' => $type]); ?>

<?php endif; ?>
