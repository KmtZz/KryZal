<?php
require_once __DIR__ . '/usuario.php';
require_once __DIR__ . '/conexion.php';
class entrenadorDAO // Clase para realizar operaciones de entrenador y lo que tenga que ver con las clases
{
    public function obtenerclases()
    {
        // Obtiene las clases de todos los entrenadores
        try {
            $con = conectarbd();
            $cont = [];
            $sentencia = 'SELECT * FROM clases';
            if ($resultado = $con->prepare($sentencia)) {
                if ($resultado->execute()) {
                    while ($clases = $resultado->fetch(PDO::FETCH_ASSOC)) {
                        $cont[] = $clases;
                    }
                    return $cont;
                }
            }
        } catch (Exception $e) {
            echo 'Error ' . $e->getMessage() . '</label>';
            return null;
        }
    }
    public function miplanning($clases, $mes, $bool)
    {
        // Obtiene un planning QUE DEPENDE de quien llame y como  a la funcion
        echo '';
        $anio = max(1970, min(2100, (int) ($_POST['anio'] ?? date('Y'))));
        $mes = (int) $mes;
        if ($mes < 1) {
            $mes = 12;
            $anio--;
        } elseif ($mes > 12) {
            $mes = 1;
            $anio++;
        }
        switch ($mes) {
            case 1:
                $mes2 = 'Enero';
                break;
            case 2:
                $mes2 = 'Febrero';
                break;
            case 3:
                $mes2 = 'Marzo';
                break;
            case 4:
                $mes2 = 'Abril';
                break;
            case 5:
                $mes2 = 'Mayo';
                break;
            case 6:
                $mes2 = 'Junio';
                break;
            case 7:
                $mes2 = 'Julio';
                break;
            case 8:
                $mes2 = 'Agosto';
                break;
            case 9:
                $mes2 = 'Septiembre';
                break;
            case 10:
                $mes2 = 'Octubre';
                break;
            case 11:
                $mes2 = 'Noviembre';
                break;
            case 12:
                $mes2 = 'Diciembre';
                break;
        }
        $hoy = date('Y-m-d');
        $hora = date('H:i:s');
        $primerDiaMes = date('N', strtotime("$anio-$mes-01"));
        $totalDiasMes = date('t', strtotime("$anio-$mes-01"));

        $clasesPorFecha = [];
        foreach ($clases as $clase) {
            $clasesPorFecha[$clase['fecha']][] = $clase;
        }
        echo '   ';

        $html = '<div class="table-responsive">';
        if ($_SESSION['rol'] === 'usuario') {
            $html .=
                '<form action="/FKryzal/Controlador/usuarioclases.php" method="post">' .
                csrf() .
                '<input type="hidden" name="formclases" value="formclases">';
        }
        $html .=
            '<table class="table calendar-table table-bordered text-center" style="border-collapse: collapse; width: 100%; text-align: center;">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= "<th   class='gradient-customccc' colspan='7'><h1>$mes2 $anio</h1></th>";

        $html .= '</tr>';

        $html .= '<tr >';
        $html .= '<th style="color: white;">Lunes</th>';
        $html .= '<th style="color: white;">Martes</th>';
        $html .= '<th style="color: white;">Miércoles</th>';
        $html .= '<th style="color: white;">Jueves</th>';
        $html .= '<th style="color: white;">Viernes</th>';
        $html .= '<th style="color: white;">Sábado</th>';
        $html .= '<th style="color: white;">Domingo</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        $html .= '<tr>';

        for ($i = 1; $i < $primerDiaMes; $i++) {
            $html .= '<td style="border: 1px solid black" ></td>';
        }

        for ($dia = 1; $dia <= $totalDiasMes; $dia++) {
            $fechaActual =
                "$anio-" .
                str_pad($mes, 2, '0', STR_PAD_LEFT) .
                '-' .
                str_pad($dia, 2, '0', STR_PAD_LEFT);
            $contenidoCelda = "<u><strong>$dia</strong></u>";
            if (isset($clasesPorFecha[$fechaActual])) {
                $contenidoCelda .= "<hr style='border: 1px solid #000; width: 100%;'>";

                foreach ($clasesPorFecha[$fechaActual] as $clase) {
                    $word = $clase['nombre'];
                    $word = str_pad($word, 6, $word);

                    $asciiValues = array_map('ord', str_split($word));

                    $r = 0;
                    $g = 0;
                    $b = 0;
                    foreach ($asciiValues as $index => $value) {
                        if ($index % 3 === 0) {
                            $r += $value;
                        } elseif ($index % 3 === 1) {
                            $g += $value;
                        } else {
                            $b += $value;
                        }
                    }

                    $base = 128;
                    $r = ($r % 128) + $base;
                    $g = ($g % 128) + $base;
                    $b = ($b % 128) + $base;
                    $backgroundColor = sprintf(
                        'linear-gradient(60deg,rgb(250, 250, 250) 80%%, #%02X%02X%02X 20%%)',
                        $r,
                        $g,
                        $b,
                    );
                    $textColor = sprintf('#%02X%02X%02X', 255 - $r, 255 - $g, 255 - $b);

                    if (strtotime($clase['fecha'] . ' ' . $clase['hora']) > time()) {
                        $textColor = sprintf(
                            '#%02X%02X%02X',
                            min(255, 255 - $r),
                            min(255, 255 - $g),
                            min(255, 255 - $b),
                        );
                    }

                    $contenidoCelda .=
                        '<div style="font-size: 0.9em;font-weight: bold; background:' .
                        $backgroundColor .
                        '; color: black; padding: 5px; margin-top: 5px; border: gray solid 1px">';
                    $horaClase = htmlspecialchars($clase['hora']);
                    $horaFormateada = date('H:i', strtotime($horaClase));

                    $contenidoCelda .= $horaFormateada . ' - ' . htmlspecialchars($clase['nombre']);
                    if ($clase['plazas'] == '0') {
                    } else {
                    }
                    if ($_SESSION['rol'] == 'usuario' && $clase['plazas'] != '0') {
                        //aqui hago controles para que me muestre lo que quiero
                        $contenidoCelda .=
                            '<br> (' . htmlspecialchars($clase['plazas']) . ' plazas)   ';
                        if (
                            $bool == 'no' &&
                            strtotime($clase['fecha'] . ' ' . $clase['hora']) > time()
                        ) {
                            $contenidoCelda .= "<input type='radio'  name='dsistir' value='$clase[id]'> ";
                        } elseif (
                            $bool == 'si' &&
                            strtotime($clase['fecha'] . ' ' . $clase['hora']) > time()
                        ) {
                            $contenidoCelda .= "<input type='radio'  name='sistir' value='$clase[id]'>";
                        } elseif ($hoy > $clase['fecha']) {
                            $contenidoCelda .= '<br><span style="color: red;">' . ' (Realizada)';
                        } elseif ($hoy == $clase['fecha'] && date('H:i:s') >= $clase['hora']) {
                            $contenidoCelda .= '<br><span style="color: red;">' . ' (Realizada)';
                        }
                    } elseif ($_SESSION['rol'] == 'usuario' && $clase['plazas'] == '0') {
                        $contenidoCelda .= '<br> <span style="color: red;">' . ' (Lleno)   ';
                        if ($bool == 'no') {
                            if ($hoy > $clase['fecha']) {
                                $contenidoCelda .=
                                    '<br><span style="color: red;">' . ' (Realizada)';
                            } elseif ($hoy == $clase['fecha'] && date('H:i:s') >= $clase['hora']) {
                                $contenidoCelda .=
                                    '<br><span style="color: red;">' . ' (Realizada)';
                            } else {
                                $contenidoCelda .= "<input type='radio'  name='dsistir' value='$clase[id]' > ";
                            }
                        } elseif ($bool == 'si') {
                            if ($hoy > $clase['fecha']) {
                                $contenidoCelda .=
                                    '<br><span style="color: red;">' . ' (Realizada)';
                            } elseif ($hoy == $clase['fecha'] && date('H:i:s') >= $clase['hora']) {
                                $contenidoCelda .=
                                    '<br><span style="color: red;">' . ' (Realizada)';
                            } else {
                            }
                        }
                    } elseif ($_SESSION['rol'] != 'usuario' && $bool == 'misclases') {
                        $contenidoCelda .=
                            ' <br> (' . htmlspecialchars($clase['plazas']) . ' plazas)';

                        if ($clase['plazas'] == '0') {
                            $contenidoCelda .= '<br> <span style="color: red;">' . ' (Lleno)';
                        }
                        if ($hoy > $clase['fecha']) {
                            $contenidoCelda .= '<br><span style="color: red;">' . ' (Realizada)';
                        } elseif ($hoy == $clase['fecha'] && date('H:i:s') >= $clase['hora']) {
                            $contenidoCelda .= '<br><span style="color: red;">' . ' (Realizada)';
                        }

                        $contenidoCelda .= "<input type='radio'  name='radioclase' value='$clase[id]'> ";
                    }
                    $contenidoCelda .= '</div>';
                }
            }
            $html .= "<td style='border: 2px solid black;'>" . $contenidoCelda . '</td>';

            if (($dia + $primerDiaMes - 1) % 7 == 0) {
                $html .= '</tr>';
                if ($dia != $totalDiasMes) {
                    $html .= '<tr>';
                }
            }
        }

        while (($totalDiasMes + $primerDiaMes - 1) % 7 != 0) {
            $html .= '<td style="border: 1px solid black"></td>';
            $totalDiasMes++;
        }

        $html .= '</tr>';
        $html .= '</tbody>';
        $html .= '<tfoot>';
        if (isset($_SESSION['mensajeusuclas'])) {
            $html .= '<tr><td colspan="7">' . $_SESSION['mensajeusuclas'] . '</td></tr>';
            unset($_SESSION['mensajeusuclas']);
        }
        if ($_SESSION['rol'] === 'usuario') {
            $html .=
                '<tr><td colspan="7"><button type="submit" name="' .
                ($bool === 'no' ? 'desapuntarse' : 'apuntarse') .
                '" value="1">' .
                ($bool === 'no' ? 'Cancelar reserva' : 'Reservar clase seleccionada') .
                '</button></td></tr>';
        }
        $html .= '</tfoot></table>';
        if ($_SESSION['rol'] === 'usuario') {
            $html .= '</form>';
        }
        $html .= '</div><div class="calendar-pagination">';
        foreach ([-1 => '← Mes anterior', 1 => 'Mes siguiente →'] as $delta => $label) {
            $html .=
                '<form method="post">' .
                csrf() .
                '<input type="hidden" name="mes" value="' .
                ($mes + $delta) .
                '"><input type="hidden" name="anio" value="' .
                $anio .
                '"><button class="secondary">' .
                $label .
                '</button></form>';
        }
        $html .= '</div>';

        return $html;
    }
    public function desapuntarse($clase)
    {
        $con = conectarbd();
        try {
            $con->beginTransaction();
            $stmt = $con->prepare('SELECT * FROM classes WHERE id=? FOR UPDATE');
            $stmt->execute([(int) $clase]);
            $c = $stmt->fetch(PDO::FETCH_ASSOC);
            if (
                ($_SESSION['rol'] ?? '') !== 'usuario' ||
                !$c ||
                $c['cancelled'] ||
                strtotime($c['starts_at']) - time() < 7200
            ) {
                throw new RuntimeException('Reserva fuera del plazo de cancelación.');
            }
            $stmt = $con->prepare(
                'DELETE FROM bookings WHERE class_id=? AND user_id=? AND attendance IS NULL',
            );
            $stmt->execute([(int) $clase, (int) $_SESSION['id']]);
            $changed = $stmt->rowCount() > 0;
            $con->commit();
            return $changed;
        } catch (Throwable $e) {
            if ($con->inTransaction()) {
                $con->rollBack();
            }
            return false;
        }
    }

    public function inscripciones()
    {
        //funcion para mostrar las inscripciones de todas las clases y usuarios
        try {
            $con = conectarbd();
            $cont = [];
            $sentencia = 'SELECT * FROM inscripciones';
            if ($resultado = $con->prepare($sentencia)) {
                if ($resultado->execute()) {
                    while ($clases = $resultado->fetch(PDO::FETCH_ASSOC)) {
                        $cont[] = $clases;
                    }
                    return $cont;
                }
            }
        } catch (Exception $e) {
            echo 'Error ' . $e->getMessage() . '</label>';
            return null;
        }
    }
    public function inscribir($clase, $id)
    {
        $con = conectarbd();
        try {
            if (
                (int) $id !== (int) ($_SESSION['id'] ?? 0) ||
                ($_SESSION['rol'] ?? '') !== 'usuario'
            ) {
                return false;
            }
            $con->beginTransaction();
            $stmt = $con->prepare('SELECT * FROM classes WHERE id=? FOR UPDATE');
            $stmt->execute([(int) $clase]);
            $c = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$c || $c['cancelled'] || strtotime($c['starts_at']) <= time()) {
                throw new RuntimeException('Clase no disponible.');
            }
            $stmt = $con->prepare('SELECT id FROM users WHERE id=? FOR UPDATE');
            $stmt->execute([(int) $id]);
            $stmt->fetch();
            $stmt = $con->prepare(
                'SELECT b.id FROM bookings b JOIN classes c ON c.id=b.class_id WHERE b.user_id=? AND c.starts_at=? AND c.cancelled=0',
            );
            $stmt->execute([(int) $id, $c['starts_at']]);
            if ($stmt->fetch()) {
                throw new RuntimeException('Ya tienes una reserva para esa fecha y hora.');
            }
            $stmt = $con->prepare('SELECT COUNT(*) FROM bookings WHERE class_id=?');
            $stmt->execute([(int) $clase]);
            if ($stmt->fetchColumn() >= $c['capacity']) {
                throw new RuntimeException('No quedan plazas.');
            }
            $stmt = $con->prepare('INSERT INTO bookings(user_id,class_id) VALUES(?,?)');
            $stmt->execute([(int) $id, (int) $clase]);
            $con->commit();
            return true;
        } catch (Throwable $e) {
            if ($con->inTransaction()) {
                $con->rollBack();
            }
            return false;
        }
    }

    public function misclases($usuarioid)
    {
        //funcion para mostrar las clases en las que el usuario esta inscrito para luego que el entrenador pueda fichar la asistencia y el sistema de puntos
        try {
            $con = conectarbd();
            $cont = [];
            $sentencia = "
            SELECT 
                c.* 
            FROM 
                clases c
            INNER JOIN 
                inscripciones i ON c.id = i.clase_id
            WHERE 
                i.usuario_id = ?
        ";
            if ($resultado = $con->prepare($sentencia)) {
                if ($resultado->bindParam(1, $usuarioid) && $resultado->execute()) {
                    while ($clases = $resultado->fetch(PDO::FETCH_ASSOC)) {
                        $cont[] = $clases;
                    }
                    if (!empty($cont)) {
                        return $cont;
                    } else {
                        return null;
                    }
                }
            }
        } catch (Exception $e) {
            echo 'Error ' . $e->getMessage() . '</label>';
            return null;
        }
    }
    public function comprobarespe($usuario_id)
    {
        //Funcion para comprobar si el usuario es nuevo y requiere especialidad
        try {
            $con = conectarbd();
            $cont = [];
            $sentencia = 'SELECT especialidad FROM entrenadores WHERE usuario_id = ?';
            if ($resultado = $con->prepare($sentencia)) {
                if ($resultado->bindParam(1, $usuario_id) && $resultado->execute()) {
                    if ($allusu = $resultado->fetch(PDO::FETCH_ASSOC)) {
                        return $allusu['especialidad'];
                    }
                }
            }
        } catch (Exception $e) {
            echo 'Error ' . $e->getMessage() . '</label>';
            return null;
        }
    }
    public function crearentrenador($usuario_id, $especialidad)
    {
        if (
            (int) $usuario_id !== (int) ($_SESSION['id'] ?? 0) ||
            ($_SESSION['rol'] ?? '') !== 'entrenador' ||
            !trim($especialidad) ||
            strlen($especialidad) > 100
        ) {
            return false;
        }
        $con = conectarbd();
        $stmt = $con->prepare("UPDATE users SET specialty=? WHERE id=? AND role='entrenador'");
        return $stmt->execute([trim($especialidad), (int) $usuario_id]);
    }

    public function subirclase($id, $fecha, $hora, $nombreclase, $plazas)
    {
        //Funcion para subir una clase como entrenador
        if (
            ($_SESSION['rol'] ?? '') !== 'entrenador' ||
            (int) $id !== (int) ($_SESSION['id'] ?? 0) ||
            (int) $plazas < 1 ||
            (int) $plazas > 200 ||
            strtotime($fecha . ' ' . $hora) <= time()
        ) {
            return false;
        }
        $fecha = $fecha . ' ' . $hora;

        try {
            $con = conectarbd();
            $cont = [];
            $sentencia =
                'INSERT INTO classes (name, starts_at, trainer_id, capacity) VALUES (?, ?, ?, ?)';
            if ($resultado = $con->prepare($sentencia)) {
                if (
                    $resultado->bindParam(1, $nombreclase) &&
                    $resultado->bindParam(2, $fecha) &&
                    $resultado->bindParam(3, $id) &&
                    $resultado->bindParam(4, $plazas) &&
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
    public function entrenaclases($id)
    {
        //Funcion para ver las clases que ha creado un entrenador
        try {
            $con = conectarbd();
            $cont = [];
            $sentencia = "
            SELECT 
                * 
            FROM 
                clases 
         
            WHERE 
            entrenador_id = ?
        ";
            if ($resultado = $con->prepare($sentencia)) {
                if ($resultado->bindParam(1, $id) && $resultado->execute()) {
                    while ($clases = $resultado->fetch(PDO::FETCH_ASSOC)) {
                        $cont[] = $clases;
                    }
                    if (!empty($cont)) {
                        return $cont;
                    } else {
                        return null;
                    }
                }
            }
        } catch (Exception $e) {
            echo 'Error ' . $e->getMessage() . '</label>';
            return null;
        }
    }
    public function alumnosclase($id)
    {
        //Funcion para ver los alumnos de una clase
        try {
            $con = conectarbd();
            $cont = [];
            $sentencia = "
       SELECT 
    usuarios.nombre,
        usuarios.id,

    inscripciones.asistencia
FROM 
    usuarios
INNER JOIN 
    inscripciones 
ON 
    usuarios.id = inscripciones.usuario_id
WHERE 
    inscripciones.clase_id = ?";
            if ($resultado = $con->prepare($sentencia)) {
                if ($resultado->bindParam(1, $id) && $resultado->execute()) {
                    while ($alumno = $resultado->fetch(PDO::FETCH_ASSOC)) {
                        $alumnos[] = $alumno;
                    }
                    if (!empty($alumnos)) {
                        return $alumnos;
                    } else {
                        return null;
                    }
                }
            }
        } catch (Exception $e) {
            echo 'Error ' . $e->getMessage() . '</label>';
            return null;
        }
    }
    public function tablaalumnos($cabe, $datos, $fecha, $hora, $idclase, $entrena)
    {
        //Funcion para crear la tabla de alumnos de una clase, que recogeere en txt y en html para diferentes cosas
        $hoy = date('Y-m-d');
        $horahoy = date('H:i:s');

        $txt = '';
        $txt .= 'Clase: ' . $cabe . "\n";
        $txt .= "Apuntados:\n";
        $txt .= "Nombre\tAsistencia\n";

        foreach ($datos as $alumno) {
            $txt .= htmlspecialchars($alumno['nombre']) . "\t";

            if ($fecha > $hoy || ($fecha == $hoy && $hora < $horahoy)) {
                $txt .= "Por confirmar\n";
            } else {
                if ($alumno['asistencia'] === null) {
                    if ($entrena == $_SESSION['id']) {
                        $txt .= "Seleccionar asistencia\n";
                    } else {
                        $txt .= "Asistencia aún no registrada por el entrenador
\n";
                    }
                } else {
                    if ($alumno['asistencia'] == 'asistio') {
                        $txt .= "Asistió\n";
                    } else {
                        $txt .= "No asistió\n";
                    }
                }
            }
        }

        $txt .= "\n";
        $_SESSION['txt'] = $txt;
        $html = '';
        $html .= '<div class="container mt-4">';
        $html .= '<table class="table table-bordered table-striped table-hover text-center">';
        $html .= '<thead>';
        $html .= '<tr class="gradient-custom">';
        $html .=
            '<th colspan="2" class="gradient-custom"><h1 class="text-white">' .
            $cabe .
            '</h1></th>';
        $html .= '</tr>';
        $html .= '<tr class="gradient-custom">';
        $html .= '<th colspan="2" class="text-white">APUNTADOS:</th>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<th class="text-white">Nombre</th>';
        $html .= '<th class="text-white">Asistencia</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        $html .= '<form action="/FKryzal/Controlador/gestionclases.php" method="post">';

        foreach ($datos as $alumno) {
            $html .= '<input type="hidden" name="claseid" value="' . $idclase . '">';
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($alumno['nombre']) . '</td>';

            if ($fecha > $hoy || ($fecha == $hoy && $hora < $horahoy)) {
                $html .= '';

                $html .= '<td class="gradient-muted">Por confirmar</td>';
            } else {
                if ($alumno['asistencia'] === null) {
                    if ($entrena == $_SESSION['id']) {
                        $html .= '<td>';
                        $html .=
                            '<select name="asistencia[' .
                            $alumno['id'] .
                            ']" class="form-select" required>';
                        $html .= '<option value="asistio">Asistió</option>';
                        $html .= '<option value="no_asistio">No asistió</option>';
                        $html .= '</select>';
                        $html .= '</td>';
                    } else {
                        $html .= '<td class="gradient-muted">Asistencia aún no registrada por el entrenador
</td>';
                    }
                } else {
                    $html .= '';

                    if ($alumno['asistencia'] == 'asistio') {
                        $html .= '<td class="gradient-success">ASISTIÓ</td>';
                    } else {
                        $html .= '<td class="gradient-danger">NO ASISTIÓ</td>';
                    }
                }
            }
            $html .= '</tr>';
        }

        if ($fecha > date('Y-m-d')) {
            $html .=
                '<tr><td colspan="2"><a href="/FKryzal/Vista/entrenador/gestionclases.php" class="btn btn-dark w-100" role="button" name="atras">Volver Atrás</a></td></tr>';
        } elseif (in_array(null, array_column($datos, 'asistencia'))) {
            if ($entrena == $_SESSION['id']) {
                $html .=
                    '<tr><td class="gradient-custom" colspan="2"><button type="submit" class="btn btn-dark w-100" name="guardar">Guardar</button></td></tr>';
                $html .=
                    '<tr><td colspan="2"><a href="/FKryzal/Vista/entrenador/gestionclases.php" class="btn btn-dark" role="button" name="atras">Volver Atrás</a></td></tr>';
            }
        } else {
            $html .=
                '<tr><td colspan="2"><button type="submit" class="btn btn-dark w-100" name="imprimir">Imprimir</button></td></tr>';
            $html .=
                '<tr><td colspan="2"><a href="/FKryzal/Vista/entrenador/gestionclases.php" class="btn btn-dark" role="button" name="atras">Volver Atrás</a></td></tr>';
        }

        $html .= '</form>';
        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }

    public function actualizarasis($asis, $clase)
    {
        $con = conectarbd();
        try {
            if (!in_array($_SESSION['rol'] ?? '', ['entrenador', 'administrador'], true)) {
                return false;
            }
            $con->beginTransaction();
            $stmt = $con->prepare('SELECT * FROM classes WHERE id=? FOR UPDATE');
            $stmt->execute([(int) $clase]);
            $c = $stmt->fetch(PDO::FETCH_ASSOC);
            if (
                !$c ||
                $c['cancelled'] ||
                strtotime($c['starts_at']) > time() ||
                ($_SESSION['rol'] === 'entrenador' &&
                    (int) $c['trainer_id'] !== (int) $_SESSION['id'])
            ) {
                throw new RuntimeException('Clase no disponible.');
            }
            $stmt = $con->prepare('SELECT class_id FROM class_reviews WHERE class_id=?');
            $stmt->execute([(int) $clase]);
            if ($stmt->fetch()) {
                throw new RuntimeException('Clase archivada.');
            }
            $stmt = $con->prepare('SELECT * FROM bookings WHERE class_id=? FOR UPDATE');
            $stmt->execute([(int) $clase]);
            $inscritos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($inscritos as $booking) {
                $id = (int) $booking['user_id'];
                $estado = $asis[$id] ?? '';
                if (!in_array($estado, ['asistio', 'no_asistio'], true)) {
                    throw new RuntimeException('Completa todos los inscritos.');
                }
                $anterior =
                    $booking['attendance'] === 'asistio'
                        ? 10
                        : ($booking['attendance'] === 'no_asistio'
                            ? -10
                            : 0);
                $delta = ($estado === 'asistio' ? 10 : -10) - $anterior;
                $stmt = $con->prepare('UPDATE bookings SET attendance=? WHERE id=?');
                $stmt->execute([$estado, $booking['id']]);
                if ($delta) {
                    $stmt = $con->prepare('UPDATE users SET points=points+? WHERE id=?');
                    $stmt->execute([$delta, $id]);
                }
            }
            $stmt = $con->prepare('INSERT INTO class_reviews(class_id,reviewed_by) VALUES(?,?)');
            $stmt->execute([(int) $clase, (int) $_SESSION['id']]);
            class_event((int) $clase, (int) $_SESSION['id'], 'reviewed', 'Asistencia completada');
            $con->commit();
            return true;
        } catch (Throwable $e) {
            if ($con->inTransaction()) {
                $con->rollBack();
            }
            return false;
        }
    }
    public function clase($claseid)
    {
        //Muestra la información de la clase seleccionada (me daba error cogerlo de la funcion de miplanning y recurri a esto)
        try {
            $con = conectarbd();
            $cont = [];
            $sentencia = "
            SELECT 
                * 
            FROM 
                clases 
          
            WHERE 
                id= ?
        ";
            if ($resultado = $con->prepare($sentencia)) {
                if ($resultado->bindParam(1, $claseid) && $resultado->execute()) {
                    if ($clases = $resultado->fetch(PDO::FETCH_ASSOC)) {
                        return $clases;
                    } else {
                        return null;
                    }
                }
            }
        } catch (Exception $e) {
            echo 'Error ' . $e->getMessage() . '</label>';
            return null;
        }
    }
}
