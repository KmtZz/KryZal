<?php if ($notice): $type = $notice[1] ?? 'success'; ?>

<?php if ($type === 'success'): ?>

<div class="flash-toast" data-toast role="status" aria-live="polite" aria-atomic="true">
    <span class="toast-icon" aria-hidden="true">✓</span>
    <span class="toast-message"><?= h($notice[0]) ?></span>
    <button type="button" class="toast-close" data-toast-close aria-label="Cerrar aviso">×</button>
</div>

<?php else: ?>
<div class="notice <?= h($type) ?>" role="alert"><?= h($notice[0]) ?></div>
<?php endif; ?>

<?php endif; ?>
