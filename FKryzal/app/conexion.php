<?php
declare(strict_types=1);

function db(bool $server = false): PDO
{
    global $config;
    $d = $config['db'];
    $connection = new PDO(
        'mysql:host=' .
            $d['host'] .
            ';port=' .
            $d['port'] .
            ($server ? '' : ';dbname=' . $d['name']) .
            ';charset=utf8mb4',
        $d['user'],
        $d['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ],
    );
    $connection->exec("SET time_zone='" . date('P') . "'");
    return $connection;
}

function query(string $sql, array $args = []): PDOStatement
{
    global $pdo;
    $s = $pdo->prepare($sql);
    $s->execute($args);
    return $s;
}

