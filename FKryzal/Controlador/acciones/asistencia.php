<?php

switch ($action) {
    case 'reopen_class':
        require_role(['entrenador', 'administrador']);
        $id = (int) ($_POST['class_id'] ?? 0);
        if (
            !isset($_POST['review_event']) ||
            !preg_match('/^[0-9]+$/D', (string) $_POST['review_event'])
        ) {
            throw new RuntimeException('Recarga el registro antes de desarchivar.');
        }
        reopen_class($id, $me, (int) $_POST['review_event']);
        flash(
            'Clase desarchivada. Los puntos anteriores se han revertido. Completa de nuevo la asistencia.',
        );
        go('attendance&id=' . $id);
    case 'attendance':
        require_role(['entrenador', 'administrador']);
        $id = (int) ($_POST['class_id'] ?? 0);
        require_once __DIR__ . '/../../Modelo/entrenadorDAO.php';
        $entrenador = new entrenadorDAO();
        if (!$entrenador->actualizarasis($_POST['attendance'] ?? [], $id)) {
            throw new RuntimeException(
                'Marca la asistencia de todos los inscritos. Solo puedes completar tus clases iniciadas y no archivadas.',
            );
        }
        flash('Asistencia completada. La clase se ha archivado.');
        go('archive');
}
