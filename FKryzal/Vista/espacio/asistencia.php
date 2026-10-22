<?php
$c = query(
    'SELECT c.*,EXISTS(SELECT 1 FROM class_reviews r WHERE r.class_id=c.id) reviewed FROM classes c WHERE c.id=?',
    [(int) ($_GET['id'] ?? 0)],
)->fetch();
if (
    !$c ||
    $me['role'] === 'usuario' ||
    ($me['role'] === 'entrenador' && (int) $c['trainer_id'] !== (int) $me['id'])
) {
    http_response_code(403);
    echo '<div class="notice error">No tienes acceso a esta clase.</div>';
    return;
}
$rows = query(
    'SELECT u.id,u.name,u.email,b.attendance FROM bookings b JOIN users u ON u.id=b.user_id WHERE b.class_id=? ORDER BY u.name',
    [$c['id']],
)->fetchAll();
$future = strtotime($c['starts_at']) > time();
$closed = $c['reviewed'] || $c['cancelled'];
$conversation = null;
$student = null;
if (!empty($_GET['conversation'])) {
    try {
        $candidate = ticket_for_user((int) $_GET['conversation'], $me);
        if ((int) $candidate['class_id'] === (int) $c['id']) {
            $conversation = $candidate;
            $student = query("SELECT id,name FROM users WHERE id=? AND role='usuario'", [$candidate['requester_id']])->fetch() ?: null;
        }
    } catch (Throwable $e) {
        // Un enlace ajeno no añade contexto a la ficha de la clase.
    }
}
$focusStudent = $student ? (int) $student['id'] : 0;
$studentBooking = null;
foreach ($rows as $row) {
    if ((int) $row['id'] === $focusStudent) {
        $studentBooking = $row;
        break;
    }
}
?>

<nav class="class-backlinks" aria-label="Volver a la agenda o conversación">
<a class="back" href="?page=classes&tab=<?= $closed ? 'archived' : ($future ? 'upcoming' : 'pending') ?>">← Volver a Clases</a>
<?php if ($conversation): ?>
<a class="back conversation-back" href="?page=requests&id=<?= (int) $conversation['id'] ?>">← Volver a la conversación</a>
<?php endif; ?>
</nav>
<?php if ($student): ?>
<div class="notice student-context" role="status">
    <strong>Consulta de <?= h($student['name']) ?></strong>
    <span><?= !$studentBooking ? 'No figura entre los inscritos de esta clase.' : ($c['cancelled'] ? 'Clase cancelada. La asistencia no aplica.' : ($studentBooking['attendance'] === 'asistio' ? 'Asistencia registrada: asistió.' : ($studentBooking['attendance'] === 'no_asistio' ? 'Asistencia registrada: no asistió.' : ($future ? 'Inscrito en esta clase. Todavía no ha comenzado.' : 'Inscrito. Asistencia pendiente de registrar.')))) ?></span>
</div>
<?php endif; ?>
<section class="panel <?= $c['reviewed'] ? 'completed' : '' ?>">
    <h2><?= h($c['name']) ?></h2>
    <p><?= date('d/m/Y · H:i', strtotime($c['starts_at'])) ?> · <?= count($rows) ?> inscritos</p>
    <p>
        <a href="Controlador/informe.php?id=<?= h($c['id']) ?>"><?= $c['reviewed'] && !$c['cancelled'] ? 'Descargar informe de clase archivada (.txt)' : 'Descargar listado de inscritos (.txt)' ?></a>
    </p>

    <?php require __DIR__ . '/../includes/cambiar_entrenador.php'; ?>

    <?php if ($future && !$closed): ?>
    <div class="notice">La clase todavía no ha comenzado. Puedes consultar y retirar alumnos; la asistencia se
        habilitará cuando comience.</div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Socio</th>
                    <th>Email</th>
                    <th>Inscripción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                <tr id="student-<?= (int) $r['id'] ?>" class="<?= (int) $r['id'] === $focusStudent ? 'student-focus' : '' ?>">
                    <td><?= h($r['name']) ?></td>
                    <td><?= h($r['email']) ?></td>
                    <td>
                        <?php startform('remove_student'); ?>
                        <input type="hidden" name="class_id" value="<?= h($c['id']) ?>">
                        <input type="hidden" name="student_id" value="<?= h($r['id']) ?>">
                        <button class="secondary"
                            data-confirm="¿Retirar a <?= h($r['name']) ?> de esta clase?">Retirar alumno
                        </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php elseif ($closed): ?>
    <div class="notice <?= $c['reviewed'] ? 'success' : '' ?>"><?= $c['cancelled'] ? 'Clase cancelada. No se han aplicado puntos por la cancelación.' : 'Asistencia completada. La clase está archivada.' ?></div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Socio</th>
                    <th>Email</th>
                    <th>Asistencia</th>
                    <th>Puntos</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                <tr id="student-<?= (int) $r['id'] ?>" class="<?= (int) $r['id'] === $focusStudent ? 'student-focus' : '' ?>">
                    <td><?= h($r['name']) ?></td>
                    <td><?= h($r['email']) ?></td>
                    <td>
                        <span class="attendance-status <?= $c['cancelled'] ? 'cancelled' : ($r['attendance'] === 'asistio' ? 'attended' : 'absent') ?>"><?= $c['cancelled'] ? 'No aplica' : ($r['attendance'] === 'asistio' ? 'Asistió' : ($r['attendance'] === 'no_asistio' ? 'No asistió' : 'Sin registrar')) ?></span>
                    </td>
                    <td>
                        <span class="point-effect <?= $c['cancelled'] ? 'neutral' : ($r['attendance'] === 'asistio' ? 'positive' : 'negative') ?>"><?= $c['cancelled'] ? '0' : ($r['attendance'] === 'asistio' ? '+10' : ($r['attendance'] === 'no_asistio' ? '−10' : '0')) ?> puntos</span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php else: ?>
    <div class="notice warning">Completa la asistencia de todos los alumnos para archivar esta clase y aplicar
        sus puntos.</div>
    <?php startform('attendance'); ?>
    <input type="hidden" name="class_id" value="<?= h($c['id']) ?>">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Socio</th>
                    <th>Email</th>
                    <th>Asistencia</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                <tr id="student-<?= (int) $r['id'] ?>" class="<?= (int) $r['id'] === $focusStudent ? 'student-focus' : '' ?>">
                    <td><?= h($r['name']) ?></td>
                    <td><?= h($r['email']) ?></td>
                    <td>
                        <select name="attendance[<?= h($r['id']) ?>]" required>
                            <option value="">Selecciona asistencia</option>
                            <option value="asistio" <?= $r['attendance'] === 'asistio' ? 'selected' : '' ?>>Asistió</option>
                            <option value="no_asistio" <?= $r['attendance'] === 'no_asistio' ? 'selected' : '' ?>>No asistió</option>
                        </select>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <button
        data-confirm="¿Completar la asistencia y archivar esta clase? Se aplicarán los puntos a los alumnos.">
        Guardar y archivar</button>
    </form>
    <?php endif; ?>

    <?php if (!$rows): ?>
    <p><?= $c['cancelled'] ? 'La clase se canceló sin alumnos inscritos.' : ($c['reviewed'] ? 'Clase archivada sin alumnos inscritos.' : 'No hay alumnos inscritos.') ?></p>
    <?php endif; ?>
</section>

<?php if ($c['reviewed'] && !$c['cancelled']): ?>
<section class="panel reopen-panel">
    <h2>Corregir asistencia</h2>
    <p>Desarchivar devuelve la clase a pendientes y revierte los puntos de su asistencia. Después tendrás que
        revisar a todos los alumnos y volver a guardar.</p>
    <?php startform('reopen_class'); ?>
    <input type="hidden" name="review_event" value="<?= (int) query('SELECT COALESCE(MAX(id),0) FROM class_events WHERE class_id=?', [$c['id']])->fetchColumn() ?>">
    <input type="hidden" name="class_id" value="<?= h($c['id']) ?>">
    <button class="secondary"
        data-confirm="¿Desarchivar esta clase? Se revertirán los puntos de todos los inscritos. La asistencia quedará pendiente hasta que vuelvas a guardarla.">
        Desarchivar clase</button>
    </form>
</section>
<?php endif; ?>

<?php
$events = query(
    'SELECT e.event,e.created_at,u.name FROM class_events e LEFT JOIN users u ON u.id=e.actor_id WHERE e.class_id=? ORDER BY e.id DESC LIMIT 10',
    [$c['id']],
)->fetchAll();
if (
    $events
): ?>
<section class="panel class-history">
    <h2>Historial de la clase</h2>
    <ul>
        <?php foreach ($events as $event): ?>
        <li>
            <strong><?= h(['reviewed' => 'Asistencia completada y clase archivada', 'cancelled' => 'Clase cancelada', 'empty_cancelled' => 'Cancelada automáticamente sin inscritos', 'reopened' => 'Clase desarchivada y puntos revertidos',][$event['event']] ?? 'Cambio de estado') ?></strong>
            <span><?= date('d/m/Y H:i', strtotime($event['created_at'])) ?> · <?= h($event['name'] ?? 'Sistema') ?></span>
        </li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>
