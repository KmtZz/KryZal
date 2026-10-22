<?php
require_once __DIR__ . '/core.php';
function legacy_prepare(): void
{
    global $pdo;
    $column = query(
        "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='specialty'",
    )->fetchColumn();
    if (!$column) {
        query("ALTER TABLE users ADD COLUMN specialty VARCHAR(100) NOT NULL DEFAULT ''");
    }
    if (
        query(
            "SELECT COUNT(*) FROM information_schema.VIEWS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('usuarios','clases','inscripciones','entrenadores')",
        )->fetchColumn() == 4
    ) {
        return;
    }
    query(
        "CREATE OR REPLACE VIEW usuarios AS SELECT id,name nombre,email,password contrasena,role rol,points puntos,CASE WHEN active=1 THEN 'activo' ELSE 'inactivo' END estado,CASE WHEN force_password=1 THEN 'si' ELSE 'no' END nuevo FROM users",
    );
    query(
        'CREATE OR REPLACE VIEW clases AS SELECT c.id,c.name nombre,DATE(c.starts_at) fecha,TIME(c.starts_at) hora,c.trainer_id entrenador_id,GREATEST(0,c.capacity-(SELECT COUNT(*) FROM bookings b WHERE b.class_id=c.id)) plazas FROM classes c WHERE c.cancelled=0',
    );
    query(
        "CREATE OR REPLACE VIEW inscripciones AS SELECT id,user_id usuario_id,class_id clase_id,'aceptada' estado,attendance asistencia FROM bookings",
    );
    query(
        "CREATE OR REPLACE VIEW entrenadores AS SELECT id,id usuario_id,specialty especialidad FROM users WHERE role='entrenador'",
    );
}
function legacy_require(array $roles): void
{
    global $pdo, $me;
    if (!has_users()) {
        header('Location: /FKryzal/install.php');
        exit();
    }
    $pdo = db();
    bind_installation($pdo);
    $me = isset($_SESSION['uid'])
        ? query('SELECT * FROM users WHERE id=?', [$_SESSION['uid']])->fetch()
        : null;
    if (!$me || !$me['active'] || $me['auth_version'] !== ($_SESSION['av'] ?? -1)) {
        $_SESSION = [];
        header('Location: /FKryzal/index.php?page=login');
        exit();
    }
    if (!in_array($me['role'], $roles, true)) {
        http_response_code(403);
        exit('No tienes permiso para esta sección.');
    }
    if ($me['force_password']) {
        header('Location: /FKryzal/index.php?page=profile');
        exit();
    }
    legacy_prepare();
    $_SESSION['id'] = $me['id'];
    $_SESSION['rol'] = $me['role'];
    $_SESSION['nombre'] = $me['name'];
    $_SESSION['email'] = $me['email'];
    $_SESSION['estado'] = 'activo';
    $_SESSION['nuevo'] = 'no';
}
