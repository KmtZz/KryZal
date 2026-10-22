<?php
declare(strict_types=1);

function current_user(): ?array
{
    if (!isset($_SESSION['uid'])) {
        return null;
    }
    $u = query('SELECT * FROM users WHERE id=?', [$_SESSION['uid']])->fetch();
    if (!$u || !$u['active'] || (int) $u['auth_version'] !== (int) ($_SESSION['av'] ?? -1)) {
        unset($_SESSION['uid'], $_SESSION['av'], $_SESSION['id'], $_SESSION['rol']);
        return null;
    }
    $_SESSION['id'] = $u['id'];
    $_SESSION['rol'] = $u['role'];
    return $u;
}
