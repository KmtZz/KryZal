<?php
declare(strict_types=1);

function csrf(): string
{
    $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    return '<input type="hidden" name="csrf" value="' . h($_SESSION['csrf']) . '">';
}

function validate_csrf(): void
{
    if (
        !hash_equals($_SESSION['csrf'] ?? '', (string) ($_POST['csrf'] ?? '')) ||
        empty($_SESSION['csrf'])
    ) {
        throw new RuntimeException('La sesión del formulario ha caducado. Recarga la página.');
    }
}

function password_hint(): string
{
    return 'Mínimo 10 caracteres, con mayúscula, minúscula, número y símbolo.';
}

function password_attributes(): string
{
    return 'minlength="10" maxlength="72" pattern="' .
        h('(?=.*\p{Lu})(?=.*\p{Ll})(?=.*[0-9])(?=.*[\p{P}\p{S}]).{10,72}') .
        '" title="' .
        h(password_hint()) .
        '" autocomplete="new-password"';
}

function password_ok(string $v): void
{
    $length = preg_match_all('/./us', $v);
    if (
        $length === false ||
        $length < 10 ||
        !preg_match('/\p{Lu}/u', $v) ||
        !preg_match('/\p{Ll}/u', $v) ||
        !preg_match('/[0-9]/', $v) ||
        !preg_match('/[\p{P}\p{S}]/u', $v)
    ) {
        throw new RuntimeException(password_hint());
    }
    if (strlen($v) > 72) {
        throw new RuntimeException('La contraseña es demasiado larga. Utiliza menos caracteres.');
    }
}

function require_role(array $roles): void
{
    global $me;
    if (!$me || !in_array($me['role'], $roles, true)) {
        throw new RuntimeException('No tienes permiso para esta operación.');
    }
}

function throttle(string $kind, string $key, int $limit): void
{
    $bucket = hash('sha256', $kind . '|' . $key);
    $n = query(
        'SELECT COUNT(*) FROM attempts WHERE bucket=? AND created_at>DATE_SUB(NOW(), INTERVAL 15 MINUTE)',
        [$bucket],
    )->fetchColumn();
    if ($n >= $limit) {
        throw new RuntimeException('Demasiados intentos. Espera 15 minutos.');
    }
    query('INSERT INTO attempts(bucket) VALUES(?)', [$bucket]);
}
