<?php
declare(strict_types=1);

function h($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function go(string $page = 'dashboard'): never
{
    header('Location: index.php?page=' . $page);
    exit();
}

function flash(string $text, string $type = 'success'): void
{
    $_SESSION['flash'] = [$text, $type];
}

function avatar(array $user, string $size = ''): void
{
    $photo = query('SELECT updated_at FROM profile_photos WHERE user_id=?', [$user['id']])->fetch();
    preg_match('/^./us', $user['name'], $initial);
    echo '<span class="avatar ' .
        h($size) .
        '" data-avatar-initial="' .
        h(strtoupper($initial[0] ?? '')) .
        '">';
    if ($photo) {
        echo '<img src="photo.php?id=' .
            (int) $user['id'] .
            '&v=' .
            h(urlencode($photo['updated_at'])) .
            '" alt="Foto de ' .
            h($user['name']) .
            '" loading="lazy">';
    } else {
        echo h(strtoupper($initial[0] ?? ''));
    }
    echo '</span>';
}
