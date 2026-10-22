<?php ob_start(); include '../includes/header.php'; require_once '../../Modelo/usuarioDAO.php'; ?>
<div class="content">
    <?php
    if ($_SESSION['rol'] == 'administrador' && $_SESSION['estado'] == 'activo') {
        $usuarios = new usuarioDAO();
    ?>

    <div class="d-flex justify-content-end">
        <div class="dropdown">
            <button class="btn  dropdown-toggle" type="button" id="dropdownMenuButton"
                data-bs-toggle="dropdown" aria-expanded="false">
                Mostrar Filtro
            </button>
            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                <li>
                    <form action="" method="post" class="px-3 py-2">
                        <label for="filtro">Filtro:</label>
                        <input type="text" name="filtro" required class="form-control mb-2">

                        <label for="accion2">Seleccionar Filtro:</label>
                        <select name="accion2" class="form-control mb-2">
                            <option value="nombre">Nombre</option>
                            <option value="id">Id</option>
                            <option value="email">Email</option>
                            <option value="rol">Rol</option>
                            <option value="estado">Estado</option>
                        </select>

                        <div class="d-flex justify-content-center">
                            <button type="submit" class="btn btn-outline-secondary btn-sm mt-2">Filtrar
                            </button>
                        </div>
                    </form>
                </li>
            </ul>
        </div>
    </div>

    <?php
    if (isset($_POST['filtro'])) {
        $filtro = $_POST['filtro'];
        $usuarios->mostrarfiltro($_POST['filtro'], $_POST['accion2']);
    } else {
        $pagina = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $usuarios->mostrarusuarios($pagina);
    }
    if (isset($_GET['restablecer'])) {
        unset($_POST['filtro']);
    }
    ?>

</div>
<?php
} else {
    if ($_SESSION['estado'] == 'inactivo') {
        $_SESSION['mensajelogin'] =
            '⚠️ Aviso: Su cuenta esta inactiva, por favor, contacta con el soporte para solventar el problema.';
        header('Location: /FKryzal/Vista/login.php');
    } else {
        $_SESSION['mensajelogin'] = '⚠️ Aviso: No tiene permisos para acceder a esta página.';
        header('Location: /FKryzal/Vista/login.php');
    }
}
include '../includes/footer.php';

?>
