<dialog class="photo-dialog" id="photo-dialog" aria-labelledby="photo-title"
    data-photo-auto="<?= isset($_GET['photo']) || ($_POST['action'] ?? '') === 'photo' ? 'yes' : 'no' ?>">
    <div class="photo-dialog-head">
        <h2 id="photo-title">Tu foto de perfil</h2>
        <button type="button" class="photo-close" data-photo-close aria-label="Cerrar">×</button>
    </div>
    <p>Elige una imagen. Así se verá en tu perfil y en la clasificación.</p>

    <?php if ($error && ($_POST['action'] ?? '') === 'photo'): ?>
    <p class="notice error" role="alert"><?= h($error) ?></p>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" data-photo-form><?= csrf() ?>
        <input type="hidden" name="action" value="photo">
        <div class="photo-preview" data-photo-preview>
            <?php avatar($me); ?>
        </div>
        <label class="photo-picker">Elegir imagen
            <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" required
                data-photo-input>
        </label>
        <p class="hint">JPG, PNG o WebP. Máximo 2 MB y 6000 píxeles por lado.</p>
        <p class="photo-feedback" data-photo-feedback role="status" aria-live="polite"></p>
        <div class="dialog-actions">
            <button type="button" class="secondary" data-photo-close>Cancelar</button>
            <button type="submit" data-photo-save disabled>Guardar foto</button>
        </div>
    </form>

    <?php if ($photo): startform('photo'); ?>
    <button class="text-button photo-remove" name="remove_photo" value="1">Eliminar foto actual</button>
    </form>
    <?php endif; ?>

</dialog>
