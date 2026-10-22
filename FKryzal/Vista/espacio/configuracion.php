<div class="panel">
    <h2>Correo y recuperación</h2>
    <p>En modo local, los enlaces de recuperación quedan en Recuperación de cuentas y no se envían emails. Para enviarlos
        por correo, selecciona SMTP y configura tu cuenta.</p>
    <?php startform('mail_settings'); ?>
    <label>Modo<select name="mode">
            <option value="local" <?= $config['mail']['mode'] === 'local' ? 'selected' : '' ?>>Local (sin envío)</option>
            <option value="smtp" <?= $config['mail']['mode'] === 'smtp' ? 'selected' : '' ?>>Correo SMTP</option>
        </select>
    </label>
    <?php field('URL de la aplicación', 'base_url', 'url', $config['base_url'], 'required'); ?>
    <div class="form-grid">
        <?php
        field('Servidor SMTP', 'host', 'text', $config['mail']['host']);
        field('Puerto', 'port', 'number', (string) $config['mail']['port'], 'min="1" max="65535"');
        ?>
        <label>Cifrado<select name="encryption">
                <option value="tls" <?= $config['mail']['encryption'] === 'tls' ? 'selected' : '' ?>>STARTTLS</option>
                <option value="ssl" <?= $config['mail']['encryption'] === 'ssl' ? 'selected' : '' ?>>TLS implícito</option>
            </select>
        </label>
        <?php
        field('Usuario SMTP', 'smtp_user', 'text', $config['mail']['user']);
        field('Email remitente', 'from', 'email', $config['mail']['from']);
        field('Contraseña de aplicación', 'smtp_password', 'password', '', 'autocomplete="new-password"');
        ?>
    </div>
    <p class="hint">Deja la contraseña vacía para conservar la configurada. Para Gmail: smtp.gmail.com, puerto
        587 y STARTTLS.</p>
    <button>Guardar configuración</button>
    </form>
</div>
