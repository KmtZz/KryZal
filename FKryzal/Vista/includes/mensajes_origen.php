<?php if ($me['role'] === 'administrador'): ?>
<nav class="message-sources" aria-label="Origen de los mensajes">
    <a class="<?= $page === 'requests' ? 'selected' : '' ?>" <?= $page === 'requests' ? 'aria-current="page"' : '' ?> href="?page=requests">Internos<?php if ($internalUnread = unread_tickets($me)): ?> <span class="unread-count"><?= $internalUnread ?></span><?php endif; ?></a>
    <a class="<?= $page === 'messages' ? 'selected' : '' ?>" <?= $page === 'messages' ? 'aria-current="page"' : '' ?> href="?page=messages">Formulario web<?php if ($publicUnread = unread_contacts((int) $me['id'])): ?> <span class="unread-count"><?= $publicUnread ?></span><?php endif; ?></a>
</nav>
<?php endif; ?>
