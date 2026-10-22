<?php
if ($me['role'] !== 'usuario') {
    http_response_code(403);
    echo '<div class="notice error">Esta sección es para socios.</div>';
    return;
}
ensure_photos();
$score =
    'u.points+COALESCE((SELECT SUM(r.cost) FROM redemptions r WHERE r.user_id=u.id),0)+COALESCE((SELECT SUM(a.score_delta-a.balance_delta) FROM point_adjustments a WHERE a.user_id=u.id),0)';
$ranking =
    'SELECT u.id,u.name,' .
    $score .
    ' score,RANK() OVER(ORDER BY ' .
    $score .
    ' DESC) position,ROW_NUMBER() OVER(ORDER BY ' .
    $score .
    ' DESC,u.name,u.id) listing_order FROM users u WHERE u.role=\'usuario\' AND u.active=1';
$own = query('SELECT position,listing_order FROM (' . $ranking . ') ranked WHERE id=?', [
    $me['id'],
])->fetch();
$position = (int) $own['position'];
$total = (int) query("SELECT COUNT(*) FROM users WHERE role='usuario' AND active=1")->fetchColumn();
$pages = max(1, (int) ceil($total / 25));
$pg = isset($_GET['mine'])
    ? (int) ceil((int) $own['listing_order'] / 25)
    : max(1, (int) ($_GET['p'] ?? 1));
$pg = min($pages, $pg);
$rows = query(
    'SELECT * FROM (' .
        $ranking .
        ') ranked ORDER BY listing_order LIMIT 25 OFFSET ' .
        ($pg - 1) * 25,
)->fetchAll();
?>

<section class="panel">
    <div class="section-head">
        <h2>Clasificación del club</h2>
        <a class="button secondary" href="?page=ranking&mine=1#mi-posicion">Ir a mi posición ·
            #<?= $position ?></a>
    </div>
    <p>La posición depende de tu puntuación por actividad: +10 por asistencia y −10 por ausencia. Canjear
        premios no modifica esta puntuación. Los empates comparten posición. Si crees que hay un error, <a
            href="?page=requests&new=1&subject=Consulta%20sobre%20mi%20puntuaci%C3%B3n">contacta con
            Administración</a>.</p>
    <div class="score-summary">
        <div>
            <span>Puntuación para clasificación</span>
            <strong><?= number_format(ranking_points((int) $me['id']), 0, ',', '.') ?> puntos</strong>
        </div>
        <div>
            <span>Saldo para canjear</span>
            <strong>
                <a class="stat-points" href="?page=rewards"><?= h($me['points']) ?> puntos</a>
            </strong>
            <a href="?page=rewards">Ver recompensas →</a>
        </div>
        <div>
            <span>Mis recompensas</span>
            <?php $activeRewards = (int) query(
            'SELECT COUNT(*) FROM redemptions WHERE user_id=? AND used_at IS NULL AND expires_at>NOW()',
            [$me['id']],
            )->fetchColumn(); ?>
            <strong><?= $activeRewards ?> <?= $activeRewards === 1 ? 'activa' : 'activas' ?></strong>
            <a href="?page=myrewards">Ver mis códigos →</a>
        </div>
    </div>
    <p><?= $total ?> socios · página <?= $pg ?> de <?= $pages ?></p>
    <?php pagination($pg, $pages, ['page' => 'ranking']); ?>
    <div class="ranking-head" aria-hidden="true">
        <span>Posición</span>
        <span>Socio</span>
        <span>Puntos</span>
    </div>
    <div class="ranking">
        <?php foreach ($rows as $r): ?>
        <div <?= (int) $r['id'] === (int) $me['id'] ? 'id="mi-posicion" class="my-rank"' : '' ?>>
            <span class="rank-number<?= $r['position'] <= 3 ? ' rank-' . $r['position'] : '' ?>"><?= $r['position'] ?></span>
            <?php avatar($r, 'avatar-small'); ?>
            <strong><?= h($r['name']) ?><?= (int) $r['id'] === (int) $me['id'] ? ' (tú)' : '' ?></strong>
            <span class="rank-points">
                <strong><?= number_format((int) $r['score'], 0, ',', '.') ?></strong>
                <small>pts</small>
            </span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php pagination($pg, $pages, ['page' => 'ranking']); ?>
</section>
