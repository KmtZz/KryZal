<?php
declare(strict_types=1);

function mail_reset(array $u, string $link): void
{
    global $config;
    $m = $config['mail'];
    $body =
        'Hola ' .
        $u['name'] .
        ",\n\nPuedes cambiar tu contraseña desde este enlace, válido durante 30 minutos:\n" .
        $link .
        "\n\nSi no lo solicitaste, ignora este mensaje.";
    if ($m['mode'] === 'local') {
        query('INSERT INTO local_mail(user_id,subject,body) VALUES(?,?,?)', [
            $u['id'],
            'Recuperar contraseña de KryZal',
            $body,
        ]);
        return;
    }
    require_once __DIR__ . '/../lib/Exception.php';
    require_once __DIR__ . '/../lib/PHPMailer.php';
    require_once __DIR__ . '/../lib/SMTP.php';
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $m['host'];
    $mail->Port = (int) $m['port'];
    $mail->SMTPAuth = true;
    $mail->Username = $m['user'];
    $mail->Password = $m['password'];
    $mail->SMTPSecure = $m['encryption'];
    $mail->Timeout = 20;
    $mail->CharSet = 'UTF-8';
    $mail->setFrom($m['from'], $m['name']);
    $mail->addAddress($u['email'], $u['name']);
    $mail->Subject = 'Recuperar contraseña de KryZal';
    $mail->Body = $body;
    $mail->send();
}

function mail_contact(string $name, string $email, string $body): void
{
    global $config;
    $m = $config['mail'];
    require_once __DIR__ . '/../lib/Exception.php';
    require_once __DIR__ . '/../lib/PHPMailer.php';
    require_once __DIR__ . '/../lib/SMTP.php';
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $m['host'];
    $mail->Port = (int) $m['port'];
    $mail->SMTPAuth = true;
    $mail->Username = $m['user'];
    $mail->Password = $m['password'];
    $mail->SMTPSecure = $m['encryption'];
    $mail->Timeout = 20;
    $mail->CharSet = 'UTF-8';
    $mail->setFrom($m['from'], $m['name']);
    $mail->addAddress($m['from']);
    $mail->addReplyTo($email, $name);
    $mail->Subject = 'Contacto KryZal: ' . $name;
    $mail->Body = $name . ' <' . $email . ">\n\n" . $body;
    $mail->send();
}

function send_contact_reply(int $id): void
{
    global $config;
    $m = $config['mail'];
    if ($m['mode'] !== 'smtp') {
        throw new RuntimeException('Configura SMTP antes de enviar por correo.');
    }
    $claim = query(
        "UPDATE contact_replies SET delivery='enviando' WHERE id=? AND delivery IN ('local','fallido')",
        [$id],
    );
    if ($claim->rowCount() !== 1) {
        throw new RuntimeException('La respuesta ya está enviada o se está enviando.');
    }
    $r = query(
        'SELECT r.body,c.name,c.email,c.body original FROM contact_replies r JOIN contact_messages c ON c.id=r.contact_id WHERE r.id=?',
        [$id],
    )->fetch();
    try {
        require_once __DIR__ . '/../lib/Exception.php';
        require_once __DIR__ . '/../lib/PHPMailer.php';
        require_once __DIR__ . '/../lib/SMTP.php';
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $m['host'];
        $mail->Port = (int) $m['port'];
        $mail->SMTPAuth = true;
        $mail->Username = $m['user'];
        $mail->Password = $m['password'];
        $mail->SMTPSecure = $m['encryption'];
        $mail->Timeout = 20;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($m['from'], $m['name']);
        $mail->addAddress($r['email'], $r['name']);
        $mail->Subject = 'Respuesta a tu consulta · KryZal';
        $mail->Body = $r['body'] . "\n\nConsulta original:\n" . $r['original'];
        $mail->send();
        query("UPDATE contact_replies SET delivery='enviado',sent_at=NOW() WHERE id=?", [$id]);
    } catch (Throwable $e) {
        query("UPDATE contact_replies SET delivery='fallido' WHERE id=?", [$id]);
        error_log('KryZal SMTP: ' . $e->getMessage());
    }
}
