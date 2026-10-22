<?php
include '../includes/header.php';
require_once '../../Modelo/usuarioDAO.php';
if (isset($_SESSION['nuevo'])) {
    if ($_SESSION['nuevo'] == 'si') {
        header('Location: ../login.php?nuevo');
    }
}
?>

<div class="content">
    <?php if ($_SESSION['rol'] == 'usuario' && $_SESSION['estado'] == 'activo') {
        $usuarios = new usuarioDAO();
        $ranking = $usuarios->generarRanking(); //No quise tener nada elavorado asi que solo muestra las posibilidades de gastar los puntos
        echo $ranking;
    } else {
        if ($_SESSION['estado'] == 'inactivo') {
            $_SESSION['mensajelogin'] =
                '⚠️ Aviso: Su cuenta esta inactiva, por favor, contacta con el soporte para solventar el problema.';
            header('Location: /FKryzal/Vista/login.php');
        } else {
            $_SESSION['mensajelogin'] = '⚠️ Aviso: No tiene permisos para acceder a esta página.';
            header('Location: /FKryzal/Vista/login.php');
        }
    } ?>
</div> <?php include '../includes/footer.php';
