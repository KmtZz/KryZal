<?php
preg_match('/^.{0,100}/us', $_GET['q'] ?? '', $match);
$q = trim($match[0] ?? '');
$role = $_GET['role'] ?? '';
$where = '1=1';
$args = [];
if ($q !== '') {
    $like = '%' . str_replace(['=', '%', '_'], ['==', '=%', '=_'], $q) . '%';
    $where .=
        " AND (name COLLATE utf8mb4_unicode_ci LIKE ? ESCAPE '=' OR email COLLATE utf8mb4_unicode_ci LIKE ? ESCAPE '=')";
    $args = [$like, $like];
}
if (in_array($role, ['usuario', 'entrenador', 'administrador'], true)) {
    $where .= ' AND role=?';
    $args[] = $role;
} else {
    $role = '';
}
$sortOptions = [
    'name_asc' => ['Nombre: A–Z', 'name ASC, id ASC'],
    'name_desc' => ['Nombre: Z–A', 'name DESC, id DESC'],
    'joined_desc' => ['Inscripción: más recientes', 'created_at DESC, id DESC'],
    'joined_asc' => ['Inscripción: más antiguos', 'created_at ASC, id ASC'],
];
$sort = $_GET['sort'] ?? 'name_asc';
if (!in_array($sort, array_keys($sortOptions), true)) {
    $sort = 'name_asc';
}
$filtersActive = $sort !== 'name_asc';
$userFilterUrl = static fn(string $targetRole): string => '?' . h(http_build_query([
    'page' => 'users', 'role' => $targetRole, 'q' => $q, 'sort' => $sort,
]));
$total = (int) query('SELECT COUNT(*) FROM users WHERE ' . $where, $args)->fetchColumn();
$pages = max(1, (int) ceil($total / 12));
$pg = min(max(1, (int) ($_GET['p'] ?? 1)), $pages);
$rows = query(
    'SELECT * FROM users WHERE ' . $where . ' ORDER BY ' . $sortOptions[$sort][1] . ' LIMIT 12 OFFSET ' . ($pg - 1) * 12,
    $args,
)->fetchAll();
$params = http_build_query(['page' => 'users', 'q' => $q, 'role' => $role, 'sort' => $sort]);
?>

<section class="panel user-toolbar">
    <nav class="quick-filters user-role-filters" aria-label="Filtrar usuarios por rol">
        <?php foreach (['' => 'Todos', 'usuario' => 'Socios', 'entrenador' => 'Entrenadores', 'administrador' => 'Administradores'] as $key => $label): ?>
        <a class="<?= $role === $key ? 'selected' : '' ?>" <?= $role === $key ? 'aria-current="true"' : '' ?> href="<?= $userFilterUrl($key) ?>"><?= $label ?></a>
        <?php endforeach; ?>
    </nav>
    <form method="get" class="collection-toolbar user-search-toolbar">
        <input type="hidden" name="page" value="users">
        <input type="hidden" name="role" value="<?= h($role) ?>">
        <div class="collection-search">
            <label class="visually-hidden" for="user-search">Buscar usuarios por nombre o correo</label>
            <input id="user-search" type="search" name="q" value="<?= h($q) ?>" maxlength="100" placeholder="Buscar por nombre o correo…">
            <button type="submit" aria-label="Buscar usuarios" title="Buscar"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 4 4"/></svg></button>
        </div>
        <label class="compact-select user-sort"><span class="visually-hidden">Ordenar usuarios</span><select name="sort" data-auto-submit>
            <?php foreach ($sortOptions as $key => [$label, $order]): ?>
            <option value="<?= $key ?>" <?= $sort === $key ? 'selected' : '' ?>><?= h($label) ?></option>
            <?php endforeach; ?>
        </select></label>
        <?php if ($q !== '' || $role !== '' || $filtersActive): ?>
        <a class="toolbar-reset" href="?page=users">Restablecer</a>
        <?php endif; ?>
    </form>
</section>
<details class="panel compact-disclosure">
    <summary>Crear nueva cuenta</summary>
    <p>El usuario cambiará su contraseña temporal en el primer acceso. Si es entrenador, selecciona su
        especialidad.</p>
    <?php startform('user_create'); ?>
    <div class="form-grid">
        <?php
        field('Nombre', 'name', 'text', '', 'required maxlength="100"');
        field('Email', 'email', 'email', '', 'required maxlength="190"');
        field('Contraseña temporal', 'password', 'password', '', 'required ' . password_attributes());
        ?>
        <label>Rol<select name="role" data-role-select>
                <?php foreach (['usuario', 'entrenador', 'administrador'] as $r): ?>
                <option value="<?= $r ?>"><?= h(role_label($r)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label data-specialty-field>Especialidad<select name="specialty">
                <option value="">Selecciona especialidad</option>
                <?php foreach (specialties() as $specialty): ?>
                <option><?= h($specialty) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>
    <button>Crear cuenta</button>
    </form>
</details>
<div class="results-toolbar">
    <p><?= $total ?> resultados<?php if ($pages > 1): ?> · página <?= $pg ?> de <?= $pages ?><?php endif; ?></p>

<?php pagination($pg, $pages, ['page' => 'users', 'q' => $q, 'role' => $role, 'sort' => $sort]); ?>
</div>
<section class="user-list">
    <?php foreach ($rows as $u): ?>
    <details class="panel account-card">
        <summary class="account-summary">
            <?php avatar($u, 'avatar-small'); ?>
            <span class="account-identity"><strong><?= h($u['name']) ?></strong><small><?= h($u['email']) ?></small></span>
            <span class="account-date">Alta <?= date('d/m/Y', strtotime($u['created_at'])) ?></span>
            <span class="role-label role-<?= h($u['role']) ?>"><?= h(role_label($u['role'])) ?></span>
            <?php if (!$u['active']): ?><span class="pill">Inactiva</span><?php endif; ?>
            <span class="account-edit-label">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m16 3 5 5-12 12-6 1 1-6Z M13 6l5 5"/></svg>
                <span class="account-edit-closed">Editar</span><span class="account-edit-open">Cerrar</span>
                <svg class="account-chevron" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
            </span>
        </summary>
        <div class="account-body">
        <?php startform('user_save'); ?>
        <input type="hidden" name="id" value="<?= h($u['id']) ?>">
        <?php
        field('Nombre', 'name', 'text', $u['name'], 'required maxlength="100"');
        field('Email', 'email', 'email', $u['email'], 'required maxlength="190" autocomplete="off"');
        ?>
        <label>Rol<select name="role" data-role-select>
                <?php foreach (['usuario', 'entrenador', 'administrador'] as $r): ?>
                <option value="<?= $r ?>" <?= $u['role'] === $r ? 'selected' : '' ?>><?= h(role_label($r)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label data-specialty-field>Especialidad<select name="specialty">
                <option value="">Selecciona especialidad</option>
                <?php foreach (specialties() as $specialty): ?>
                <option value="<?= h($specialty) ?>" <?= $u['specialty'] === $specialty ? 'selected' : '' ?>><?= h($specialty) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="check">
            <input type="checkbox" name="active" <?= $u['active'] ? 'checked' : '' ?>> Cuenta activa
        </label>
        <button class="secondary">Guardar</button>
        </form>

        <?php if ($u['role'] === 'usuario'): $score = ranking_points((int) $u['id']); ?>
        <details class="points-editor">
            <summary>Modificar puntos</summary>
            <p>El saldo sirve para canjear recompensas. La puntuación determina la clasificación. Puedes
                modificar cada valor por separado.</p>
            <?php startform('user_points'); ?>
            <input type="hidden" name="id" value="<?= h($u['id']) ?>">
            <input type="hidden" name="previous_balance" value="<?= h($u['points']) ?>">
            <input type="hidden" name="previous_score" value="<?= h($score) ?>">
            <?php
            field(
                'Saldo para recompensas',
                'balance',
                'number',
                (string) $u['points'],
                'required step="1" min="-1000000" max="1000000"',
            );
            field(
                'Puntuación de clasificación',
                'score',
                'number',
                (string) $score,
                'required step="1" min="-1000000" max="1000000"',
            );
            ?>
            <button class="secondary" data-confirm="¿Guardar los nuevos puntos de <?= h($u['name']) ?>?">
                Guardar puntos</button>
            </form>
        </details>
        <?php endif; ?>

        <?php if ((int) $u['id'] !== (int) $me['id']): ?>
        <details class="account-reset">
            <summary>Restablecer contraseña</summary>
            <p>Define una contraseña temporal. Se cerrarán sus sesiones y tendrá que cambiarla al entrar. La
                cuenta conserva su estado de activación.</p>
            <?php startform('user_password'); ?>
            <input type="hidden" name="id" value="<?= h($u['id']) ?>">
            <?php field('Nueva contraseña temporal', 'password', 'password', '', 'required ' . password_attributes()); ?>
            <button class="secondary"
                data-confirm="¿Restablecer la contraseña de <?= h($u['name']) ?> y cerrar sus sesiones?">
                Restablecer contraseña</button>
            </form>
        </details>
        <?php endif; ?>
        </div>
    </details>
    <?php endforeach; ?>
</section>

<?php if (!$rows): ?>
<section class="panel collection-empty" role="status"><h2>No encontramos usuarios</h2><p>Prueba otro nombre o correo, o cambia el rol seleccionado.</p></section>
<?php endif; ?>

<?php pagination($pg, $pages, ['page' => 'users', 'q' => $q, 'role' => $role, 'sort' => $sort]); ?>
