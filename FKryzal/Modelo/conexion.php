<?php
require_once __DIR__ . '/../app/core.php';
function conectarbd()
{
    global $pdo;
    if (!isset($pdo)) {
        $pdo = db();
    }
    return $pdo;
}
