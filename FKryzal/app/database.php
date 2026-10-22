<?php

function database_setup(PDO $connection, array $settings): void
{
    $name = $settings['db']['name'];
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
        throw new RuntimeException('Nombre de base no válido.');
    }
    $connection->exec(
        'CREATE DATABASE IF NOT EXISTS `' .
            $name .
            '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
    );
    $connection->exec('USE `' . $name . '`');
    foreach (explode(';', file_get_contents(__DIR__ . '/../sql/schema.sql')) as $sql) {
        if (trim($sql)) {
            $connection->exec($sql);
        }
    }
    $stmt = $connection->prepare(
        "INSERT IGNORE INTO app_state(setting,value) VALUES('installation',?)",
    );
    $stmt->execute([bin2hex(random_bytes(32))]);
}
function has_users(): bool
{
    try {
        $connection = db();
        return (int) $connection->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0;
    } catch (PDOException $e) {
        if (in_array((int) ($e->errorInfo[1] ?? 0), [1049, 1146], true)) {
            return false;
        }
        throw $e;
    }
}

function bind_installation(PDO $connection): void
{
    $value = $connection
        ->query("SELECT value FROM app_state WHERE setting='installation'")
        ->fetchColumn();
    if (!$value) {
        throw new RuntimeException(
            'La base del proyecto no está preparada. Ejecuta Instalar KryZal.',
        );
    }
    if (isset($_SESSION['uid']) && ($_SESSION['installation'] ?? null) !== $value) {
        unset($_SESSION['uid'], $_SESSION['av'], $_SESSION['id'], $_SESSION['rol']);
        session_regenerate_id(true);
    }
    $_SESSION['installation'] = $value;
}
