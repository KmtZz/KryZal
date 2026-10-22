<?php

function field(
    string $label,
    string $name,
    string $type = 'text',
    string $value = '',
    string $extra = '',
): void {
    echo '<label>' .
        h($label) .
        '<input type="' .
        h($type) .
        '" name="' .
        h($name) .
        '" value="' .
        h($value) .
        '" ' .
        $extra .
        '>' .
        ($type === 'password' && $name === 'password' && str_contains($extra, 'new-password')
            ? '<span class="password-hint">' . h(password_hint()) . '</span>'
            : '') .
        '</label>';
}
function startform(string $action): void
{
    echo '<form method="post">' .
        csrf() .
        '<input type="hidden" name="action" value="' .
        h($action) .
        '">';
}
