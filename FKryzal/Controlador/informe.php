<?php
require_once __DIR__ . '/../app/legacy.php';
legacy_require(['administrador', 'entrenador']);
$id = (int) ($_GET['id'] ?? 0);
$class = query(
    'SELECT c.*,r.reviewed_at FROM classes c LEFT JOIN class_reviews r ON r.class_id=c.id WHERE c.id=?',
    [$id],
)->fetch();
if (!$class || ($me['role'] === 'entrenador' && (int) $class['trainer_id'] !== (int) $me['id'])) {
    http_response_code(403);
    exit('No tienes permiso para descargar este informe.');
}
$rows = query(
    'SELECT u.name,u.email,b.attendance FROM bookings b JOIN users u ON u.id=b.user_id WHERE b.class_id=? ORDER BY u.name',
    [$id],
)->fetchAll();
$archived = !$class['cancelled'] && $class['reviewed_at'];
$filename = ($archived ? 'informe' : 'inscritos') . '-clase-' . $id . '.txt';
header('Content-Type: text/plain; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store');
$clean = fn($value) => str_replace(["\t", "\r", "\n"], ' ', (string) $value);
echo 'KryZal · ' .
    $clean($class['name']) .
    "\nFecha de la clase: " .
    date('d/m/Y H:i', strtotime($class['starts_at'])) .
    "\n";
if ($class['cancelled']) {
    echo "Estado: clase cancelada y archivada.\nLa cancelación no aplica puntos.\n";
} elseif ($archived) {
    echo 'Estado: la clase se archivó el ' .
        date('d/m/Y H:i', strtotime($class['reviewed_at'])) .
        ".\nAsistencia completada.\n";
} else {
    echo "Estado: pendiente de completar la asistencia y archivar.\n" .
        (strtotime($class['starts_at']) > time() ? "La clase todavía no ha comenzado.\n" : '') .
        "Listado de alumnos inscritos. No es un informe de asistencia definitivo.\n";
}
echo "\nTotal de inscritos: " .
    count($rows) .
    "\n\nNombre\tEmail" .
    ($archived ? "\tAsistencia\tPuntos" : '') .
    "\n";
foreach ($rows as $row) {
    echo $clean($row['name']) . "\t" . $clean($row['email']);
    if ($archived) {
        echo "\t" .
            ($row['attendance'] === 'asistio'
                ? 'Asistió'
                : ($row['attendance'] === 'no_asistio'
                    ? 'No asistió'
                    : 'Sin registrar')) .
            "\t" .
            ($row['attendance'] === 'asistio'
                ? '+10'
                : ($row['attendance'] === 'no_asistio'
                    ? '-10'
                    : '0'));
    }
    echo "\n";
}
if (!$rows) {
    echo "No hay alumnos inscritos.\n";
}
