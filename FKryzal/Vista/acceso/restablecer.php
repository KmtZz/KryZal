<h1>Nueva contraseña.</h1>
<?php startform('reset'); ?>
<input type="hidden" name="token" value="<?= h($_GET['token'] ?? ($_POST['token'] ?? '')) ?>">
<?php
field('Contraseña', 'password', 'password', '', 'required ' . password_attributes());
field('Repite la contraseña', 'confirm', 'password', '', 'required');
?>
<button>Guardar contraseña</button>
</form>
<a class="back" href="?page=login">Volver al acceso</a>
