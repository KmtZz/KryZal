<h1>Tu próximo paso.</h1>
<p>Crea tu cuenta de socio.</p>
<?php
startform('register');
field('Nombre', 'name', 'text', '', 'required maxlength="100" autocomplete="name"');
field('Email', 'email', 'email', '', 'required autocomplete="email"');
field('Contraseña', 'password', 'password', '', 'required ' . password_attributes());
field('Repite la contraseña', 'confirm', 'password', '', 'required autocomplete="new-password"');
?>
<button>Crear cuenta</button>
</form>
<a class="back" href="?page=login">Volver al acceso</a>
