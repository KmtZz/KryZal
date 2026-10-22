<?php
declare(strict_types=1);

function ranking_points(int $user): int
{
    return (int) query(
        'SELECT u.points+COALESCE((SELECT SUM(r.cost) FROM redemptions r WHERE r.user_id=u.id),0)+COALESCE((SELECT SUM(a.score_delta-a.balance_delta) FROM point_adjustments a WHERE a.user_id=u.id),0) FROM users u WHERE u.id=?',
        [$user],
    )->fetchColumn();
}
