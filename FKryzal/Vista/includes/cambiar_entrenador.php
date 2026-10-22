<?php
if ($me['role'] === 'administrador' && !$c['cancelled'] && empty($c['reviewed']) && strtotime($c['starts_at']) > time()):
    $assignableTrainers ??= query("SELECT id,name FROM users WHERE role='entrenador' AND active=1 ORDER BY name")->fetchAll();
?>
<details class="trainer-change">
    <summary>Cambiar entrenador</summary>
    <?php startform('assign_trainer'); ?>
    <input type="hidden" name="class_id" value="<?= (int) $c['id'] ?>">
    <input type="hidden" name="return_page" value="<?= h($page) ?>">
    <?php foreach (['q', 'view', 'day', 'timing', 'month', 'p', 'tab', 'scope'] as $returnKey): ?>
    <?php if (isset($_GET[$returnKey]) && is_string($_GET[$returnKey])): ?>
    <input type="hidden" name="return_<?= $returnKey ?>" value="<?= h($_GET[$returnKey]) ?>">
    <?php endif; ?>
    <?php endforeach; ?>
    <label>Entrenador<select name="trainer" required>
        <?php foreach ($assignableTrainers as $trainer): ?>
        <option value="<?= (int) $trainer['id'] ?>" <?= (int) $trainer['id'] === (int) $c['trainer_id'] ? 'selected' : '' ?>><?= h($trainer['name']) ?></option>
        <?php endforeach; ?>
    </select></label>
    <button class="secondary">Guardar entrenador</button>
    </form>
</details>
<?php endif; ?>
