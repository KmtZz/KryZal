<?php include '../includes/header.php'; require_once '../../Modelo/entrenadorDAO.php'; ?>
<div class="content">
    <?php if (
        $_SESSION['rol'] == 'entrenador' &&
        $_SESSION['estado'] == 'activo'
    ) {
        $entrenador = new entrenadorDAO();
        if (isset($_POST['mes'])) {
            $mes = $_POST['mes'];
        } else {
            $mes = date('n');
        }
        $clases = $entrenador->obtenerclases(); //Obetenemos las clases de todos los entrenadores en las que podremos ver los alumbnos metidos
        $planning = $entrenador->miplanning($clases, $mes, $bool = 'nada');
        echo $planning;
    } else {
        if ($_SESSION['estado'] == 'inactivo') {
            $_SESSION['mensajelogin'] =
                'Su cuenta esta inactiva, por favor, contacta con el soporte para solventar el problema.';
            header('Location: /FKryzal/Vista/login.php');
        } else {
            $_SESSION['mensajelogin'] = '⚠️ Aviso: No tiene permisos para acceder a esta página.';
            header('Location: /FKryzal/Vista/login.php');
        }
    } ?>

</div>
<?php include '../includes/footer.php'; ?>
