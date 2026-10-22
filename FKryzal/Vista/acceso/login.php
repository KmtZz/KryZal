<span class="eyebrow">BIENVENIDO A TU CLUB</span>
<h1>Vuelve a tu ritmo.</h1>
<p>Organiza tus clases y sigue tu actividad.</p>
<?php
startform('login');
field(
    'Email',
    'email',
    'email',
    trim(
        (string) ($_POST['email'] ??
            (filter_var($_COOKIE['kryzal_email'] ?? '', FILTER_VALIDATE_EMAIL)
                ? $_COOKIE['kryzal_email']
                : '')),
    ),
    'required autocomplete="username"',
);
field('Contraseña', 'password', 'password', '', 'required autocomplete="current-password"');
?>
<label class="check remember-user">
    <input type="checkbox" name="remember_user" value="1" <?= ($_SERVER['REQUEST_METHOD'] === 'POST' ? isset($_POST['remember_user']) : !empty($_COOKIE['kryzal_email'])) ? 'checked' : '' ?>> Recordar usuario
</label>
<p class="hint remember-hint">Guarda solo tu correo en este dispositivo.</p>
<button>Entrar</button>
</form>
<div class="auth-links">
    <a href="?page=forgot">¿Olvidaste tu contraseña?</a>
    <a href="?page=register">Crear cuenta</a>
</div>
