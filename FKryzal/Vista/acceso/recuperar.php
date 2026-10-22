<h1>Recupera tu acceso.</h1>
<p><?= $config['mail']['mode'] === 'local' ? 'Generaremos un enlace válido durante 30 minutos.' : 'Te enviaremos un enlace válido durante 30 minutos.' ?></p>
<?php if ($config['mail']['mode'] === 'local'): ?>
<div class="notice">Modo local: los mensajes se guardan en «Recuperación de cuentas» del administrador; no se envían
    emails reales.</div>
<?php endif; ?>

<?php startform('forgot'); field('Email', 'email', 'email', '', 'required'); ?>
<button>Solicitar enlace</button>
</form>
<a class="back" href="?page=login">Volver al acceso</a>
