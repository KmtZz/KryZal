<?php
require_once __DIR__ . '/usuario.php';
require_once __DIR__ . '/conexion.php';
class UsuarioDAO //Aqui iran todas las funciones relacionadas con usuarios, ya sea comprobacion o creacion de usuarios
{
    public function nuevo($email)
    {
        //Funcion para actualizar que un usuario cambio la contraseña
        try {
            $con = conectarbd();
            $sentencia = 'UPDATE users SET force_password = 0 WHERE email = ?';
            if ($resultado = $con->prepare($sentencia)) {
                if ($resultado->bindParam(1, $email) && $resultado->execute()) {
                    return true;
                }
            }
        } catch (Exception $e) {
            echo 'Error ' . $e->getMessage() . '</label>';
            return null;
        }
    }
    public function cambiarcontra($email, $contrasena)
    {
        //Funcion para cambiar la contraseña de un nuevo usuario
        password_ok($contrasena);
        $contrasena = password_hash($contrasena, PASSWORD_DEFAULT);
        try {
            $con = conectarbd();
            $sentencia =
                'UPDATE users SET password = ?, auth_version=auth_version+1 WHERE email = ?';
            if ($resultado = $con->prepare($sentencia)) {
                if (
                    $resultado->bindParam(1, $contrasena) &&
                    $resultado->bindParam(2, $email) &&
                    $resultado->execute()
                ) {
                    return true;
                }
            }
        } catch (Exception $e) {
            echo 'Error ' . $e->getMessage() . '</label>';
            return null;
        }
    }
    public function crearUsuario($nombre, $email, $contrasena, $rol, $nuevo)
    {
        //Funcion para crear usuario nuevo
        password_ok($contrasena);
        $contrasena = password_hash($contrasena, PASSWORD_DEFAULT);
        $nuevo = $nuevo === 'si' ? 1 : 0;
        try {
            $con = conectarbd();
            $cont = [];
            $sentencia =
                'INSERT INTO users (name, email, password, role, force_password) VALUES (?, ?, ?, ?,?)';
            if ($resultado = $con->prepare($sentencia)) {
                if (
                    $resultado->bindParam(1, $nombre) &&
                    $resultado->bindParam(2, $email) &&
                    $resultado->bindParam(3, $contrasena) &&
                    $resultado->bindParam(4, $rol) &&
                    $resultado->bindParam(5, $nuevo) &&
                    $resultado->execute()
                ) {
                    return true;
                }
            }
        } catch (Exception $e) {
            echo 'Error ' . $e->getMessage() . '</label>';
            return null;
        }
    }
    public function comprobar($email)
    {
        //Funcion de login para comprobar el email o el id (no recuerdo si al final lo use en alguna parte lo del id)
        try {
            $con = conectarbd();
            $sentencia = 'SELECT * FROM usuarios WHERE email = ? OR CAST(id AS CHAR) = ?';
            if ($resultado = $con->prepare($sentencia)) {
                if (
                    $resultado->bindParam(1, $email) &&
                    $resultado->bindParam(2, $email) &&
                    $resultado->execute()
                ) {
                    if ($datos = $resultado->fetch(PDO::FETCH_ASSOC)) {
                        if (
                            $usu = new usuario(
                                $datos['id'],
                                $datos['nombre'],
                                $datos['email'],
                                $datos['contrasena'],
                                $datos['rol'],
                                $datos['puntos'],
                                $datos['estado'],
                                $datos['nuevo'],
                            )
                        ) {
                            return $usu;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            echo 'Error ' . $e->getMessage() . '</label>';
            return null;
        }
    }
    public function mostrarUsuarios($pagina = 1, $usuariosPorPagina = 10)
    {
        //Paginacion para mostrar al admin los usuarios y datos necesarios
        try {
            $con = conectarbd();

            $offset = ($pagina - 1) * $usuariosPorPagina;

            $totalQuery = 'SELECT COUNT(*) as total FROM usuarios';
            $totalStmt = $con->prepare($totalQuery);
            $totalStmt->execute();
            $totalUsuarios = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];

            $totalPaginas = ceil($totalUsuarios / $usuariosPorPagina);

            $sentencia = 'SELECT * FROM usuarios ORDER BY rol LIMIT ? OFFSET ?';
            $stmt = $con->prepare($sentencia);
            $stmt->bindParam(1, $usuariosPorPagina, PDO::PARAM_INT);
            $stmt->bindParam(2, $offset, PDO::PARAM_INT);
            $stmt->execute();

            $cont = [];

            while ($allusu = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $usus = new usuario(
                    $allusu['id'],
                    $allusu['nombre'],
                    $allusu['email'],
                    $allusu['contrasena'],
                    $allusu['rol'],
                    $allusu['puntos'],
                    $allusu['estado'],
                    $allusu['nuevo'],
                );
                $cont[] = $usus;
            }
            echo "<div class='container my-4' >";
            echo "<div class='table-responsive' >";
            echo "<table class='table table-striped table-bordered table-hover bg-light'>";
            echo "<thead class='table-dark'>
                    <tr>
                        <th>Id</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Puntos</th>
                        <th>Estado</th>
                    </tr>
                  </thead>";
            echo '<tbody>';

            foreach ($cont as $usu) {
                echo "<tr>
                        <td>{$usu->getId()}</td>
                        <td>{$usu->getNombre()}</td>
                        <td>{$usu->getEmail()}</td>
                        <td>{$usu->getRol()}</td>
                        <td>{$usu->getPuntos()}</td>
                        <td>{$usu->getEstado()}</td>
                    </tr>";
            }

            echo '</tbody>';
            echo '</table>';
            if (isset($_SESSION['mensajefiltro'])) {
                echo "<div id='mensajefiltro' class='alert alert-secondary text-center mt-3 shadow rounded p-3 w-50 mx-auto'>
                        <strong>⚠️ Aviso:</strong> " .
                    $_SESSION['mensajefiltro'] .
                    "
                      </div>";
                unset($_SESSION['mensajefiltro']);
            }
            echo " 
    
            <script>
                setTimeout(function() {
                    var mensaje = document.getElementById('mensajefiltro');
                    if (mensaje) {
                        mensaje.style.display = 'none';
                    }
                }, 5000); 
            </script>";
            echo '</div>';
            echo '</div>';

            echo '<nav>';
            echo "<ul class='pagination justify-content-center'>";

            $disableFirst = $pagina == 1 ? 'disabled' : '';
            echo "<li class='page-item $disableFirst'><a class='page-link' href='?page=1'>&laquo;&laquo;</a></li>";

            $disablePrev = $pagina == 1 ? 'disabled' : '';
            echo "<li class='page-item $disablePrev'><a class='page-link' href='?page=" .
                ($pagina - 1) .
                "'>&lt;</a></li>";

            echo "<li class='page-item disabled'><span class='page-link'>$pagina</span></li>";

            $disableNext = $pagina == $totalPaginas ? 'disabled' : '';
            echo "<li class='page-item $disableNext'><a class='page-link' href='?page=" .
                ($pagina + 1) .
                "'>&gt;</a></li>";

            $disableLast = $pagina == $totalPaginas ? 'disabled' : '';
            echo "<li class='page-item $disableLast'><a class='page-link' href='?page=$totalPaginas'>&raquo;&raquo;</a></li>";

            echo '</ul>';
            echo '</nav>';
        } catch (Exception $e) {
            echo 'Error ' . $e->getMessage();
            return null;
        }
    }
    public function mostrarusuariosfiltro($valor, $filtro)
    {
        //Muestra a los usuarios con el filtro intrdudcido
        if (!in_array($filtro, ['nombre', 'id', 'email', 'rol', 'estado'], true)) {
            return null;
        }
        try {
            $con = conectarbd();
            $sentencia = "SELECT * FROM usuarios WHERE $filtro = ?";
            if ($resultado = $con->prepare($sentencia)) {
                if ($resultado->bindParam(1, $valor) && $resultado->execute()) {
                    while ($allusu = $resultado->fetch(PDO::FETCH_ASSOC)) {
                        $usus = new usuario(
                            $allusu['id'],
                            $allusu['nombre'],
                            $allusu['email'],
                            $allusu['contrasena'],
                            $allusu['rol'],
                            $allusu['puntos'],
                            $allusu['estado'],
                            $allusu['nuevo'],
                        );
                        $cont[] = $usus;
                    }
                }

                if (!empty($cont)) {
                    usort($cont, function ($a, $b) {
                        return strcmp($a->getRol(), $b->getRol());
                    });
                    echo "<div class='container my-4'>";
                    echo "<div class='table-responsive'>";
                    echo "<table class='table table-striped table-bordered table-hover bg-light'>";
                    echo "<thead class='table-dark'>
                            <tr>
                                <th>Id</th>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Rol</th>
                                <th>Puntos</th>
                                <th>Estado</th>
                            </tr>
                          </thead>";
                    echo '<tbody>';

                    foreach ($cont as $usu) {
                        echo "<tr>
                                <td>{$usu->getId()}</td>
                                <td>{$usu->getNombre()}</td>
                                <td>{$usu->getEmail()}</td>
                                <td>{$usu->getrol()}</td>
                                <td>{$usu->getPuntos()}</td>
                                <td>{$usu->getEstado()}</td>
                            </tr>";
                    }

                    echo '</tbody>';
                    echo '</table>';
                    echo '</div>';

                    echo "<div class='d-flex justify-content-center mt-3'>";
                    echo "<a href='mostrar.php?restablecer' class='btn btn-dark'>Restablecer filtro</a>";
                    echo '</div>';
                    echo '</div>';
                } else {
                    $_SESSION['mensajefiltro'] = 'No hay usuarios que coincidan con el filtro';
                    header('location:/FKryzal/Vista/admin/mostrar.php');
                }
            }
        } catch (Exception $e) {
            echo 'Error ' . $e->getMessage() . '</label>';
            return null;
        }
    }
    public function mostrarfiltro($filtro, $accion)
    {
        if (!in_array($accion, ['nombre', 'id', 'email', 'rol', 'estado'], true)) {
            return null;
        }
        $usuarios = new usuarioDAO();

        switch ($accion) {
            case 'nombre':
                $var = 'nombre';

                break;
            case 'id':
                $var = 'id';

                break;
            case 'email':
                $var = 'email';
                break;

            case 'rol':
                $var = 'rol';
                break;

            case 'estado':
                $var = 'estado';
                break;
        }
        $filtrar = $usuarios->mostrarusuariosfiltro($filtro, $var);
    }
    public function modificarUsuarios($pagina = 1, $usuariosPorPagina = 10)
    {
        //Muestra usuarios y permite modificarlos
        try {
            $con = conectarbd();

            $offset = ($pagina - 1) * $usuariosPorPagina;

            $totalQuery = 'SELECT COUNT(*) as total FROM usuarios';
            $totalStmt = $con->prepare($totalQuery);
            $totalStmt->execute();
            $totalUsuarios = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];

            $totalPaginas = ceil($totalUsuarios / $usuariosPorPagina);

            $sentencia = 'SELECT * FROM usuarios ORDER BY rol LIMIT ? OFFSET ?';
            $stmt = $con->prepare($sentencia);
            $stmt->bindParam(1, $usuariosPorPagina, PDO::PARAM_INT);
            $stmt->bindParam(2, $offset, PDO::PARAM_INT);
            $stmt->execute();

            $cont = [];

            while ($allusu = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $usus = new usuario(
                    $allusu['id'],
                    $allusu['nombre'],
                    $allusu['email'],
                    $allusu['contrasena'],
                    $allusu['rol'],
                    $allusu['puntos'],
                    $allusu['estado'],
                    $allusu['nuevo'],
                );
                $cont[] = $usus;
            }

            echo "<div class='container my-4'>";
            echo "<div class='table-responsive'>";

            echo "<table class='table table-striped table-bordered table-hover bg-light'>";
            echo "<thead class='table-dark'>
                    <tr>
                        <th>Id</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Puntos</th>
                        <th>Estado</th>
                        <th  colspan='2' class='text-center'>Modificar</th>
                    </tr>
                  </thead>";
            echo '<tbody>';

            foreach ($cont as $usu) {
                echo "<tr>
                        <td>{$usu->getId()}</td>
                        <td>{$usu->getNombre()}</td>
                        <td>{$usu->getEmail()}</td>
                        <td>{$usu->getRol()}</td>
                        <td>{$usu->getPuntos()}</td>
                        <td>{$usu->getEstado()}</td>
                   <td class='text-center'>
    <form action='/FKryzal/Controlador/gestionusu.php' method='post'>
        <input type='hidden' name='idusu' value='{$usu->getId()}'>
        <button type='submit' name='editar' class='btn btn-outline-primary btn-sm'>Editar</button>
    </form>
</td>
<td class='text-center'>
    <form action='/FKryzal/Controlador/gestionusu.php' method='post'>
        <input type='hidden' name='idusu' value='{$usu->getId()}'>
        <button type='submit' name='borrar' class='btn btn-outline-danger btn-sm'>Borrar</button>
    </form>
</td>
                    </tr>";
            }

            echo '</tbody>';
            echo '</table>';
            if (isset($_SESSION['mensajefiltro'])) {
                echo "<div id='mensajefiltro' class='alert alert-secondary text-center mt-3 shadow rounded p-3 w-50 mx-auto'>
                        <strong>⚠️ Aviso:</strong> " .
                    $_SESSION['mensajefiltro'] .
                    "
                      </div>";
                unset($_SESSION['mensajefiltro']);
            }
            echo "   
    
    <script>
        setTimeout(function() {
            var mensaje = document.getElementById('mensajefiltro');
            if (mensaje) {
                mensaje.style.display = 'none';
            }
        }, 5000); 
    </script>";
            echo '</div>';
            echo '</div>';

            echo '<nav>';
            echo "<ul class='pagination justify-content-center'>";

            $disableFirst = $pagina == 1 ? 'disabled' : '';
            echo "<li class='page-item $disableFirst'><a class='page-link' href='?page=1'>&laquo;&laquo;</a></li>";

            $disablePrev = $pagina == 1 ? 'disabled' : '';
            echo "<li class='page-item $disablePrev'><a class='page-link' href='?page=" .
                ($pagina - 1) .
                "'>&lt;</a></li>";

            echo "<li class='page-item disabled'><span class='page-link'>$pagina</span></li>";

            $disableNext = $pagina == $totalPaginas ? 'disabled' : '';
            echo "<li class='page-item $disableNext'><a class='page-link' href='?page=" .
                ($pagina + 1) .
                "'>&gt;</a></li>";

            $disableLast = $pagina == $totalPaginas ? 'disabled' : '';
            echo "<li class='page-item $disableLast'><a class='page-link' href='?page=$totalPaginas'>&raquo;&raquo;</a></li>";

            echo '</ul>';
            echo '</nav>';
        } catch (Exception $e) {
            echo 'Error ' . $e->getMessage();
            return null;
        }
    }
    public function mostrarusuariosfiltro2($valor, $filtro2)
    {
        //Para filtrar un usuario y modificarlo
        if (!in_array($filtro2, ['nombre', 'id', 'email', 'rol', 'estado'], true)) {
            return null;
        }
        try {
            $con = conectarbd();
            $sentencia = "SELECT * FROM usuarios WHERE $filtro2 = ?";
            if ($resultado = $con->prepare($sentencia)) {
                if ($resultado->bindParam(1, $valor) && $resultado->execute()) {
                    while ($allusu = $resultado->fetch(PDO::FETCH_ASSOC)) {
                        $usus = new usuario(
                            $allusu['id'],
                            $allusu['nombre'],
                            $allusu['email'],
                            $allusu['contrasena'],
                            $allusu['rol'],
                            $allusu['puntos'],
                            $allusu['estado'],
                            $allusu['nuevo'],
                        );
                        $cont[] = $usus;
                    }
                }

                if (!empty($cont)) {
                    usort($cont, function ($a, $b) {
                        return strcmp($a->getRol(), $b->getRol());
                    });
                    echo "<div class='container my-4'>";
                    echo "<div class='table-responsive'>";
                    echo "<table class='table table-striped table-bordered table-hover bg-light'>";
                    echo "<thead class='table-dark'>
                            <tr>
                                <th>Id</th>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Rol</th>
                                <th>Puntos</th>
                                <th>Estado</th>
                                <th colspan='2'class='text-center'>Modificar</th>
                            </tr>
                          </thead>";
                    echo '<tbody>';

                    foreach ($cont as $usu) {
                        echo "<tr>
                                <td>{$usu->getId()}</td>
                                <td>{$usu->getNombre()}</td>
                                <td>{$usu->getEmail()}</td>
                                <td>{$usu->getrol()}</td>
                                <td>{$usu->getPuntos()}</td>
                                <td>{$usu->getEstado()}</td>
                                   <td class='text-center'>
                                    <form action='/FKryzal/Controlador/gestionusu.php' method='post' class='d-inline'>
                                        <input type='hidden' name='idusu' value='{$usu->getId()}'>
                                        <button type='submit' name='editar' class='btn btn-outline-primary btn-sm'>Editar</button>
                                    </form>
                                </td>
                                <td class='text-center'>
                                    <form action='/FKryzal/Controlador/gestionusu.php' method='post' class='d-inline'>
                                        <input type='hidden' name='idusu' value='{$usu->getId()}'>
                                        <button type='submit' name='borrar' class='btn btn-outline-danger btn-sm'>Borrar</button>
                                    </form>
                                </td>
                             
                            </tr>";
                    }

                    echo '</tbody>';
                    echo '</table>';
                    echo '</div>';
                    echo '</div>';

                    echo "<div class='d-flex justify-content-center mt-3'>";
                    echo "<a href='gestionar.php?restablecer' class='btn btn-dark '>Restablecer filtro</a>";
                    echo '</div>';
                } else {
                    $_SESSION['mensajefiltro'] = 'No hay usuarios que coincidan con el filtro';
                    header('location:/FKryzal/Vista/admin/gestionar.php');
                }
            }
        } catch (Exception $e) {
            echo 'Error ' . $e->getMessage() . '</label>';
            return null;
        }
    }
    public function editarusuario($nombre, $email, $rol, $puntos, $estado, $id)
    {
        //Despues de la funcion de gestionar es la que metera en la BD los datos nuevos
        if (
            ($_SESSION['rol'] ?? '') !== 'administrador' ||
            !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            !in_array($rol, ['usuario', 'entrenador', 'administrador'], true)
        ) {
            return false;
        }
        if (
            (int) $id === (int) ($_SESSION['id'] ?? 0) &&
            ($estado !== 'activo' || $rol !== 'administrador')
        ) {
            return false;
        }
        $estado = $estado === 'activo' ? 1 : 0;

        try {
            $con = conectarbd();
            $sentencia =
                'UPDATE users SET name = ?, email = ?, role = ?, points = ?, active = ?, auth_version=auth_version+1 WHERE id = ?';
            if ($resultado = $con->prepare($sentencia)) {
                if (
                    $resultado->bindParam(1, $nombre) &&
                    $resultado->bindParam(2, $email) &&
                    $resultado->bindParam(3, $rol) &&
                    $resultado->bindParam(4, $puntos) &&
                    $resultado->bindParam(5, $estado) &&
                    $resultado->bindParam(6, $id) &&
                    $resultado->execute()
                ) {
                    return true;
                }
            }
        } catch (Exception $e) {
            echo 'Error ' . $e->getMessage() . '</label>';
            return null;
        }
    }

    public function borrarusuario($email)
    {
        //borrara al usuario de la BD
        if (($_SESSION['rol'] ?? '') !== 'administrador') {
            return false;
        }
        try {
            $con = conectarbd();
            $sentencia = "UPDATE users SET active=0,auth_version=auth_version+1 WHERE email=? AND role<>'administrador';
";
            if ($resultado = $con->prepare($sentencia)) {
                if ($resultado->bindParam(1, $email) && $resultado->execute()) {
                    return true;
                }
            }
        } catch (Exception $e) {
            echo 'Error ' . $e->getMessage() . '</label>';
            return null;
        }
    }
    public function clasificacion()
    {
        //muestra una clasificacion de los usuarios con mas puntos
        try {
            $con = conectarbd();

            $registrosPorPagina = 10;

            ensure_features();
            $sentencia =
                "SELECT u.id,u.nombre,u.email,u.contrasena,u.rol,u.puntos+COALESCE((SELECT SUM(r.cost) FROM redemptions r WHERE r.user_id=u.id),0) puntos,u.estado,u.nuevo FROM usuarios u WHERE rol='usuario' AND estado='activo' ORDER BY puntos DESC,nombre,id";
            $resultado = $con->query($sentencia);

            $cont = [];
            while ($allusu = $resultado->fetch(PDO::FETCH_ASSOC)) {
                $usus = new usuario(
                    $allusu['id'],
                    $allusu['nombre'],
                    $allusu['email'],
                    $allusu['contrasena'],
                    $allusu['rol'],
                    $allusu['puntos'],
                    $allusu['estado'],
                    $allusu['nuevo'],
                );
                $cont[] = $usus;
            }

            $pagina = isset($_GET['page']) ? (int) $_GET['page'] : 1;

            $totalUsuarios = count($cont);
            $totalPaginas = ceil($totalUsuarios / $registrosPorPagina);

            if ($pagina > $totalPaginas) {
                $pagina = $totalPaginas;
            }
            if ($pagina < 1) {
                $pagina = 1;
            }

            $inicio = ($pagina - 1) * $registrosPorPagina;
            $usuariosPagina = array_slice($cont, $inicio, $registrosPorPagina);

            $puestoUsuario = null;
            $puntosUsuario = null;

            foreach ($cont as $index => $usu) {
                if ($_SESSION['id'] == $usu->getId()) {
                    $puestoUsuario = $index + 1;
                    $puntosUsuario = $usu->getPuntos();
                    break;
                }
            }

            echo '';

            echo "<div style='margin-top:2vh' class='user-rank-info mb-3'>";
            echo '<div class="alert puntos text-secondary text-center p-3" role="alert">
                <strong><u>Tu clasificación es: <span class="text-dark">' .
                $puestoUsuario .
                '</span> y tienes: <span class="text-dark">' .
                $puntosUsuario .
                '</span> puntos</strong></u>.
              </div>';
            echo '</div>';

            echo "<div class='table-responsive' style='max-width: 500px; margin: auto;'>";
            echo "<table class='table table-bordered table-sm text-center'>";
            echo "<thead class='table-dark'><tr><th>Nombre</th><th>Puntos</th><th>Puesto</th></tr></thead>";
            echo '<tbody>';

            $var = $inicio + 1;
            foreach ($usuariosPagina as $usu) {
                $color =
                    $_SESSION['id'] == $usu->getId() ? 'gradient-custom-lightblue' : 'table-light';
                $color = $var == 1 ? 'gradient-custom' : $color;
                $color = $var == 2 ? 'gradient-custom2' : $color;
                $color = $var == 3 ? 'gradient-custom3' : $color;

                echo "<tr><td class='$color'>" .
                    $usu->getNombre() .
                    '</td><td>' .
                    $usu->getPuntos() .
                    "</td><td>$var</td></tr>";
                $var++;
            }

            echo '</tbody>';
            echo '</table>';
            echo '</div>';

            echo '<nav>';
            echo "<ul class='pagination justify-content-center'>";

            $disableFirst = $pagina == 1 ? 'disabled' : '';
            echo "<li class='page-item $disableFirst'><a class='page-link' href='?page=1'>&laquo;&laquo;</a></li>";

            $disablePrev = $pagina == 1 ? 'disabled' : '';
            echo "<li class='page-item $disablePrev'><a class='page-link' href='?page=" .
                ($pagina - 1) .
                "'>&lt;</a></li>";

            echo "<li class='page-item disabled'><span class='page-link'>$pagina</span></li>";

            $disableNext = $pagina == $totalPaginas ? 'disabled' : '';
            echo "<li class='page-item $disableNext'><a class='page-link' href='?page=" .
                ($pagina + 1) .
                "'>&gt;</a></li>";

            $disableLast = $pagina == $totalPaginas ? 'disabled' : '';
            echo "<li class='page-item $disableLast'><a class='page-link' href='?page=$totalPaginas'>&raquo;&raquo;</a></li>";

            echo '</ul>';
            echo '</nav>';
        } catch (Exception $e) {
            error_log('KryZal DAO: ' . $e->getMessage());
            return null;
        }
    }

    public function crearusuariosprueba()
    {
        //Funcion para pushear 50 usuarios para probar la pagina de ranking y presentar el proyecto
        try {
            $con = conectarbd();
            $sentencia =
                'INSERT INTO users (name, email, password, role, force_password) VALUES (?, ?, ?, ?, ?)';

            $var = 0;
            do {
                $nombre = "Prueba$var";
                $email = "prueba$var@example.com";
                $contrasena = password_hash('KryzalDemo!7', PASSWORD_DEFAULT);
                $rol = 'usuario';
                $nuevo = 1;

                $resultado = $con->prepare($sentencia);
                if ($resultado) {
                    $resultado->bindParam(1, $nombre);
                    $resultado->bindParam(2, $email);
                    $resultado->bindParam(3, $contrasena);
                    $resultado->bindParam(4, $rol);
                    $resultado->bindParam(5, $nuevo);
                    $resultado->execute();
                } else {
                    echo 'Error al preparar la consulta.';
                    return null;
                }

                $var++;
            } while ($var < 50);

            return true;
        } catch (Exception $e) {
            error_log('KryZal DAO: ' . $e->getMessage());
            return null;
        }
    }

    public function controladorusuarios()
    {
        //Compruebo que existe el primer usuario pusheado de la funcion anterior y depende si existe o no te permite lanzar la funcion de antes
        try {
            $con = conectarbd();
            $sentencia = "SELECT * FROM usuarios WHERE email = 'prueba0@example.com'";
            if ($resultado = $con->prepare($sentencia)) {
                if ($resultado->execute()) {
                    if ($datos = $resultado->fetch(PDO::FETCH_ASSOC)) {
                        return $datos;
                    }
                }
            }
        } catch (Exception $e) {
            return null;
        }
    }
    function generarRanking()
    {
        //Genera las recomensas dependiendo del ranking
        $ranking = [
            ['premio' => 'Descuento del 50% en tienda', 'puntos' => 500],
            ['premio' => 'Producto gratis', 'puntos' => 1000],
            ['premio' => 'Descuento del 20% en próximo pedido', 'puntos' => 300],
            ['premio' => 'Envio gratis', 'puntos' => 200],
            ['premio' => 'Descuento del 10% en tienda', 'puntos' => 150],
        ];

        usort($ranking, function ($a, $b) {
            return $b['puntos'] - $a['puntos'];
        });

        echo '

            <div class="container">
                <h1 class="text-center mb-4">Ranking de Premios y Descuentos</h1>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Premio/Descuento</th>
                            <th>Puntos Requeridos</th>
                        </tr>
                    </thead>
                    <tbody>';

        $posicion = 1;
        foreach ($ranking as $item) {
            echo '<tr>
                    <td>' .
                $posicion++ .
                '</td>
                    <td>' .
                $item['premio'] .
                '</td>
                    <td>' .
                $item['puntos'] .
                '</td>
                  </tr>';
        }

        echo '  </tbody>
                </table>
            </div>
        
        ';
    }
}
