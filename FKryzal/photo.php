<?php
require __DIR__ . '/app/core.php';
try {
    $pdo = db();
    bind_installation($pdo);
    $me = current_user();
    if (!$me) {
        http_response_code(403);
        exit();
    }
    ensure_photos();
    $id = (int) ($_GET['id'] ?? $me['id']);
    if (
        $id !== (int) $me['id'] &&
        !query('SELECT id FROM users WHERE id=? AND active=1', [$id])->fetch()
    ) {
        http_response_code(403);
        exit();
    }
    $photo = query('SELECT mime,image FROM profile_photos WHERE user_id=?', [$id])->fetch();
    if (!$photo) {
        http_response_code(404);
        exit();
    }
    header('Content-Type: ' . $photo['mime']);
    header('Cache-Control: private, no-store');
    header('X-Content-Type-Options: nosniff');
    echo $photo['image'];
} catch (Throwable $e) {
    http_response_code(503);
}
