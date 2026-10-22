<?php ensure_photos(); $photo = query('SELECT updated_at FROM profile_photos WHERE user_id=?', [$me['id']])->fetch(); ?>
<div class="profile-head">
    <?php if (!$me['force_password']): ?>
    <button type="button" class="avatar-edit" data-photo-open aria-label="Cambiar foto de perfil">
        <?php avatar($me); ?>
        <span class="avatar-edit-overlay" aria-hidden="true">
            <svg viewBox="0 0 24 24">
                <path d="m15 5 4 4M4 20l4-1L20 7a2.8 2.8 0 0 0-4-4L4 15z" />
            </svg>
        </span>
        <span class="avatar-edit-mark" aria-hidden="true">
            <svg viewBox="0 0 16 16">
                <path d="M8 3v10M3 8h10" />
            </svg>
        </span>
    </button>
    <?php else:avatar($me);endif; ?>
    <div>
        <h2><?= h($me['name']) ?></h2>
        <p><?= h(ucfirst($me['role'])) ?>
            <?php if ($me['role'] === 'usuario'): ?>
            · <?= h($me['points']) ?> puntos disponibles
            <?php endif; ?>
        </p>
        <?php if (!$me['force_password']): ?>
        <button class="photo-edit-link" type="button" data-photo-open>Cambiar foto</button>
        <?php endif; ?>
    </div>
</div>
<?php if ($me['role'] === 'entrenador' && !trim($me['specialty'])): ?>
<div class="notice">Completa tu especialidad para empezar a gestionar tus clases.</div>
<?php endif; ?>

<?php if ($me['force_password']): ?>
<div class="notice">Cambia tu contraseña temporal para continuar. La nueva debe cumplir los requisitos
    indicados.</div>
<?php endif; ?>

<?php if (!$me['force_password']): require __DIR__ . '/foto.php'; endif; ?>
<div class="<?= $me['force_password'] ? 'password-first' : 'two-columns' ?>">
    <?php if (!$me['force_password']): ?>
    <section class="panel">
        <h2>Datos personales</h2>
        <?php
        startform('profile');
        field('Nombre', 'name', 'text', $me['name'], 'required maxlength="100"');
        field('Email', 'email', 'email', $me['email'], 'disabled');
        if (
            $me['role'] === 'entrenador'
        ): ?>
        <label>Especialidad<select name="specialty" required>
                <option value="">Selecciona tu especialidad</option>
                <?php foreach (specialties() as $specialty): ?>
                <option value="<?= h($specialty) ?>" <?= $me['specialty'] === $specialty ? 'selected' : '' ?>><?= h($specialty) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <?php endif; ?>
        <p class="hint">El email identifica tu cuenta y no se modifica desde este formulario.</p>
        <button>Guardar perfil</button>
        </form>
    </section>
    <?php endif; ?>
    <section class="panel">
        <h2>Cambiar contraseña</h2>
        <?php startform('password'); ?>
        <input type="hidden" name="username" autocomplete="username" value="<?= h($me['email']) ?>">
        <?php
        field('Contraseña actual', 'current', 'password', '', 'required autocomplete="current-password"');
        field('Nueva contraseña', 'password', 'password', '', 'required ' . password_attributes());
        field('Repite la contraseña', 'confirm', 'password', '', 'required autocomplete="new-password"');
        ?>
        <button>Actualizar contraseña</button>
        </form>
    </section>
</div>
