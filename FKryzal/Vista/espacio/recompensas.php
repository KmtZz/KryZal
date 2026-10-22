<?php
if ($page === 'rewardcheck') {
    require __DIR__ . '/recompensas_admin.php';
    return;
}
if ($me['role'] !== 'usuario') {
    http_response_code(403);
    echo '<div class="notice error">Esta sección es para socios.</div>';
    return;
}
if ($page === 'rewards'):

    $_SESSION['reward_tokens'] ??= [];
    if (count($_SESSION['reward_tokens']) > 100) {
        $_SESSION['reward_tokens'] = array_slice($_SESSION['reward_tokens'], -50, null, true);
    }
?>

<section class="panel">
    <h2>Canjea tus puntos</h2>
    <p>Tienes <strong><?= h($me['points']) ?> puntos disponibles</strong>. El coste se descuenta de este saldo al
        canjear y recibes un código de un solo uso válido durante 30 días. Tu puntuación para la clasificación
        se mantiene.</p>
    <p>Presenta el código al gimnasio para recibir tu premio. Si caduca, los puntos no se devuelven.</p>
    <a href="?page=myrewards">Ver mis recompensas →</a>
</section>
<section class="cards reward-catalog">

    <?php foreach (reward_catalog() as $id => [$title, $cost]):
    
    $available = $me['points'] >= $cost;
    $key = bin2hex(random_bytes(32));
    $_SESSION['reward_tokens'][$key] = time();
    ?>
    <article class="panel reward-card">
        <span class="pill"><?= $cost ?> puntos</span>
        <h2><?= h($title) ?></h2>
        <p><?= $available ? 'Disponible para canjear.' : h($cost - $me['points']) . ' puntos para desbloquearla.' ?></p>
        <?php startform('redeem'); ?>
        <input type="hidden" name="reward_id" value="<?= $id ?>">
        <input type="hidden" name="request_key" value="<?= $key ?>">
        <button <?= $available ? '' : 'disabled' ?>
            data-confirm="¿Canjear <?= h($title) ?> por <?= $cost ?> puntos? El código tendrá 30 días de validez.">
            <?= $available ? 'Canjear recompensa' : 'Puntos insuficientes' ?></button>
        </form>
    </article>
    <?php endforeach; ?>
</section>

<?php
else:

    $filter = $_GET['state'] ?? 'active';
    $clauses = [
        'active' => 'used_at IS NULL AND expires_at>NOW()',
        'used' => 'used_at IS NOT NULL',
        'expired' => 'used_at IS NULL AND expires_at<=NOW()',
    ];
    if (!isset($clauses[$filter])) {
        $filter = 'active';
    }
    $pg = max(1, (int) ($_GET['p'] ?? 1));
    $where = 'user_id=? AND ' . $clauses[$filter];
    $total = (int) query('SELECT COUNT(*) FROM redemptions WHERE ' . $where, [
        $me['id'],
    ])->fetchColumn();
    $pages = max(1, (int) ceil($total / 12));
    $pg = min($pg, $pages);
    $rows = query(
        'SELECT * FROM redemptions WHERE ' .
            $where .
            ' ORDER BY id DESC LIMIT 12 OFFSET ' .
            ($pg - 1) * 12,
        [$me['id']],
    )->fetchAll();
?>

<section class="panel">
    <h2>Tus recompensas</h2>
    <p>Consulta los códigos pendientes de utilizar y tu historial. Cada código se utiliza una sola vez.</p>
    <a href="?page=rewards">Volver al catálogo →</a>
</section>
<div class="class-view-switch">
    <?php foreach (['active' => 'Activas', 'used' => 'Utilizadas', 'expired' => 'Caducadas'] as $state => $label): ?>
    <a class="<?= $filter === $state ? 'selected' : '' ?>" href="?page=myrewards&state=<?= $state ?>"><?= $label ?></a>
    <?php endforeach; ?>
</div>
<?php pagination($pg, $pages, ['page' => 'myrewards', 'state' => $filter]); ?>
<section class="cards">
    <?php foreach ($rows as $reward): ?>
    <article class="panel reward-card">
        <span class="pill <?= $filter === 'active' ? 'reward-active' : '' ?>"><?= h(['active' => 'Activa', 'used' => 'Utilizada', 'expired' => 'Caducada'][$filter]) ?></span>
        <h2><?= h($reward['title']) ?></h2>
        <p><?= h($reward['cost']) ?> puntos · canjeada el <?= date('d/m/Y', strtotime($reward['created_at'])) ?></p>
        <?php if ($filter === 'active'): ?>
        <div class="reward-code code-copy">
            <strong><?= h($reward['code']) ?></strong>
            <button type="button" class="copy-code" data-copy-code="<?= h($reward['code']) ?>"
                aria-label="Copiar código">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <rect x="8" y="8" width="12" height="13" rx="2" />
                    <path d="M16 8V4a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h4" />
                </svg>
                <span>Copiar</span>
            </button>
        </div>
        <span class="copy-feedback" role="status"></span>
        <p>Válida hasta <?= date('d/m/Y H:i', strtotime($reward['expires_at'])) ?></p>
        <small>Presenta este código al gimnasio.</small>
        <?php elseif ($filter === 'used'): ?>
        <p>Utilizada el <?= date('d/m/Y H:i', strtotime($reward['used_at'])) ?></p>
        <?php else: ?>
        <p>Caducó el <?= date('d/m/Y H:i', strtotime($reward['expires_at'])) ?></p>
        <?php endif; ?>
    </article>
    <?php endforeach; ?>
</section>
<?php if (!$rows): ?>
<div class="empty">No tienes recompensas <?= h(strtolower(['active' => 'activas', 'used' => 'utilizadas', 'expired' => 'caducadas'][$filter])) ?>.</div>
<?php endif; ?>

<?php pagination($pg, $pages, ['page' => 'myrewards', 'state' => $filter,]); ?>

<?php endif; ?>
