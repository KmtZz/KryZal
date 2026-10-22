<?php
require_once __DIR__ . '/../app/legacy.php';
legacy_require(['usuario']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../Vista/cliente/calendario.php');
    exit();
}
try {
    validate_csrf();
} catch (Throwable $e) {
    http_response_code(403);
    exit(h($e->getMessage()));
}
require_once '../Modelo/entrenadorDAO.php';
$entrenador = new entrenadorDAO();
if (isset($_POST['formclases'])) {
    if (isset($_POST['apuntarse'])) {
        if (isset($_POST['sistir'])) {
            if (empty($_POST['sistir'])) {
                $_SESSION['mensajeusuclas'] =
                    "<span class='text-danger'>Debes seleccionar una clase para inscribirte</span>";
                header('location:/FKryzal/Vista/cliente/calendario.php');
            }
            $claseid = $_POST['sistir'];
            $usuid = $_SESSION['id'];

            $inscipciones = $entrenador->inscripciones();
            foreach ($inscipciones as $inscripcion) {
                if ($inscripcion['clase_id'] == $claseid && $inscripcion['usuario_id'] == $usuid) {
                    $claseexiste = true; //compruebo si ya esta inscrito a esa clase
                }
            }
            if (isset($claseexiste)) {
                $_SESSION['mensajeusuclas'] =
                    "<span class='text-danger'>Ya estás inscrito en esta clase</span>";
                header('location:/FKryzal/Vista/cliente/calendario.php');
            } else {
                if ($entrenador->inscribir($claseid, $usuid) == true) {
                    $_SESSION['mensajeusuclas'] =
                        "<span class='text-success'>Has sido inscrito en la clase</span>";

                    header('location:/FKryzal/Vista/cliente/calendario.php');
                } else {
                    $_SESSION['mensajeusuclas'] =
                        "<span class='text-danger'>No se ha podido inscribir en la clase</span>";
                    header('location:/FKryzal/Vista/cliente/calendario.php');
                }
            }
        } else {
            $_SESSION['mensajeusuclas'] =
                "<span class='text-danger'>Debes seleccionar una clase para inscribirte</span>";
            header('location:/FKryzal/Vista/cliente/calendario.php');
        }
    }
    if (isset($_POST['desapuntarse'])) {

        if (isset($_POST['dsistir'])) {
            if (empty($_POST['dsistir'])) {
                $_SESSION['mensajeusuclas'] =
                    "<span class='text-danger'>Debes seleccionar una clase para desinscribir</span>";
                header('location:/FKryzal/Vista/cliente/misclases.php');
            }
            if ($entrenador->desapuntarse($_POST['dsistir'])) {
                $_SESSION['mensajeusuclas'] =
                    "<span class='text-success'>Has sido desapuntado de la clase</span>";
                header('location:/FKryzal/Vista/cliente/misclases.php');
            } else {
                $_SESSION['mensajeusuclas'] =
                    "<span class='text-danger'>No se ha podido desapuntar de la clase</span>";
                header('location:/FKryzal/Vista/cliente/misclases.php');
            }
        } else {
            $_SESSION['mensajeusuclas'] =
                "<span class='text-danger'>Debes seleccionar una clase para desinscribirte</span>";
            header('location:/FKryzal/Vista/cliente/misclases.php');
        }
    }
} else {
    header('location:/FKryzal/Vista/cliente/calendario.php');
}
