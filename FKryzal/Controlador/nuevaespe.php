<?php
require_once __DIR__ . '/../app/legacy.php';
legacy_require(['entrenador']);
try {
    validate_csrf();
    require_once __DIR__ . '/../Modelo/entrenadorDAO.php';
    $dao = new entrenadorDAO();
    if (!$dao->crearentrenador($_SESSION['id'], $_POST['especialidad'] ?? '')) {
        throw new RuntimeException('Especialidad no válida.');
    }
    header('Location: ../Vista/entrenador/especialidad.php?nuevo');
    exit();
} catch (Throwable $e) {
    http_response_code(400);
    exit(h($e->getMessage()));
}
