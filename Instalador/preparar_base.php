<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit();
}
require_once __DIR__ . '/../FKryzal/app/core.php';
try {
    $options = getopt('', ['config:', 'output:', 'check']);
    $fresh = $config;
    if (isset($options['config'])) {
        if (!is_file($options['config'])) {
            throw new RuntimeException('No se encuentra la configuración de conexión.');
        }
        $connection = require $options['config'];
        if (!is_array($connection) || !isset($connection['db'])) {
            throw new RuntimeException('Configuración de conexión no válida.');
        }
        $config['db'] = $connection['db'];
    }
    if (!in_array($config['db']['host'], ['localhost', '127.0.0.1', '::1'], true)) {
        throw new RuntimeException('El instalador solo reinicia una base local de Wamp.');
    }
    $name = $config['db']['name'];
    if (!preg_match('/^kryzal[a-zA-Z0-9_]*$/i', $name)) {
        throw new RuntimeException(
            'La base del proyecto debe empezar por kryzal. Revisa app/config.php.',
        );
    }
    $pdo = db(true);
    $pdo->query('SELECT 1');
    if (isset($options['check'])) {
        echo "Conexión disponible.\n";
        exit(0);
    }
    if (!isset($options['output'])) {
        throw new RuntimeException('Falta el destino de la configuración.');
    }
    $schema = file_get_contents(__DIR__ . '/../FKryzal/sql/schema.sql');
    if (!$schema) {
        throw new RuntimeException('Falta el esquema de la base.');
    }
    $pdo->exec('DROP DATABASE IF EXISTS `' . $name . '`');
    database_setup($pdo, $config);
    require_once __DIR__ . '/../FKryzal/app/legacy.php';
    legacy_prepare();
    ensure_contacts();
    $fresh['db'] = $config['db'];
    $encoded = "<?php\nreturn " . var_export($fresh, true) . ";\n";
    if (file_put_contents($options['output'], $encoded, LOCK_EX) === false) {
        throw new RuntimeException('No se pudo guardar la conexión del proyecto nuevo.');
    }
    echo 'Base ' . $name . " reiniciada y preparada, sin usuarios.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'No se pudo preparar la base: ' . $e->getMessage() . "\n");
    exit(1);
}
