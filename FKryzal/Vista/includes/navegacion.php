<aside class="sidebar">
            <a class="brand" href="index.php">
                <img src="assets/images/logo-oscuro.svg" alt="" class="brand-icon"> KryZal
            </a>
            <small>FITNESS CLUB</small>
            <nav>
                <?php foreach ($links as $key => $label): ?>
                <a class="<?= $page === $key || ($page === 'archive' && $key === 'manage') || ($page === 'messages' && $key === 'requests') || ($page === 'attendance' && $key === 'classes' && $me['role'] !== 'usuario') ? 'selected' : '' ?>" href="<?= h('index.php?page=' . $key) ?>"><?= h($label) ?>
                    <?php if ($alerts[$key] ?? 0): ?>
                    <span class="nav-alert" aria-label="<?= $alerts[$key] ?> avisos">
                        <span class="notification-dot" aria-hidden="true"></span>
                        <span class="nav-alert-count"><?= $alerts[$key] ?></span>
                    </span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </nav>
            <div class="sidebar-bottom">
                <?php if ($me['role'] === 'usuario'): ?>
                <a class="points-link" href="?page=rewards"
                    aria-label="<?= h($me['points']) ?> puntos disponibles. Ver recompensas">
                    <span>Saldo disponible</span>
                    <strong><?= h($me['points']) ?><small> puntos</small>
                    </strong>
                    <span class="points-link-action">Ver recompensas <span aria-hidden="true">→</span>
                    </span>
                </a>
                <?php endif; ?>
                <a class="sidebar-profile" href="?page=profile" aria-label="Editar mi perfil">
                    <strong><?= h($me['name']) ?></strong>
                    <small><?= h(role_label($me['role'])) ?></small>
                    <span>Editar perfil →</span>
                </a>
            </div>
        </aside>
