<?php

declare(strict_types=1);

require_once __DIR__ . '/../partials/bootstrap.php';
$pageTitle = 'Permission Matrix';
$roles = $roleService->listRoles(false);
$users = array_values(array_filter(
    $userService->getUsers(['status' => 'Active']),
    static fn (array $user): bool => strtolower((string)($user['username'] ?? '')) !== 'walter'
));
$permissions = $permissionService->listPermissions(false);
$mode = (string)($_GET['mode'] ?? 'role');
if (!in_array($mode, ['role', 'user'], true)) {
    $mode = 'role';
}
$selectedRoleId = (int)($_GET['role_id'] ?? ($roles[0]['id'] ?? 0));
$selectedUserId = (int)($_GET['user_id'] ?? ($users[0]['id'] ?? 0));
if (!in_array($selectedUserId, array_map(static fn (array $user): int => (int)$user['id'], $users), true)) {
    $selectedUserId = (int)($users[0]['id'] ?? 0);
}
$assigned = array_map('intval', array_column($permissionService->getRolePermissions($selectedRoleId), 'id'));
$userOverrides = [];
foreach ($permissionService->getUserPermissionOverrides($selectedUserId) as $override) {
    $userOverrides[(int)$override['id']] = (string)$override['effect'];
}
require_once __DIR__ . '/../../../layouts/header.php';
require_once __DIR__ . '/../../../layouts/sidebar.php';
?>
<style>
    .permission-mode-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: .65rem;
        margin: 0 0 1rem;
    }

    .permission-mode-tabs a {
        padding: .65rem 1rem;
        border: 1px solid #dbe3ef;
        border-radius: 999px;
        background: #fff;
        text-decoration: none;
        color: #334155;
        font-weight: 700;
    }

    .permission-mode-tabs a.active {
        border-color: #2563eb;
        background: #eff6ff;
        color: #1d4ed8;
    }

    .permission-matrix-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: .85rem;
        align-items: end;
        margin-bottom: 1rem;
    }

    .permission-matrix-form {
        margin-top: 1rem;
    }

    .permission-list {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: .65rem;
        margin: 1rem 0 1.25rem;
    }

    .permission-option {
        display: grid;
        grid-template-columns: 1.25rem minmax(0, 1fr);
        align-items: start;
        gap: .75rem;
        min-height: 2.65rem;
        padding: .75rem .85rem;
        border: 1px solid #dbe3ef;
        border-radius: 12px;
        background: #ffffff;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(15, 23, 42, .04);
    }

    .permission-user-option {
        grid-template-columns: minmax(0, 1fr);
        cursor: default;
    }

    .permission-effects {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
        margin-top: .55rem;
    }

    .permission-effect-choice {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .35rem .55rem;
        border: 1px solid #dbe3ef;
        border-radius: 999px;
        background: #f8fafc;
        color: #334155;
        font-size: .88rem;
        cursor: pointer;
    }

    .permission-effect-choice input {
        margin: 0;
        accent-color: #2563eb;
    }

    .permission-option:hover {
        border-color: #93b4e8;
        background: #f8fbff;
    }

    .permission-option input[type="checkbox"] {
        width: 1.05rem;
        height: 1.05rem;
        margin: .12rem 0 0;
        accent-color: #2563eb;
    }

    .permission-option-text {
        display: block;
        line-height: 1.35;
        color: #1f2937;
        overflow-wrap: anywhere;
    }

    @media (max-width: 640px) {
        .permission-list {
            grid-template-columns: 1fr;
        }
    }
</style>
<main class="content">
    <?php require_once __DIR__ . '/../../../layouts/navbar.php'; ?>
    <section class="card">
        <h2>Permission Matrix</h2>
        <p class="text-muted">Role permissions are the defaults. User overrides let one account inherit, allow, or deny a permission without changing everyone else in the same role or department.</p>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert-success"><?= e((string)$_SESSION['success_message']) ?></div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['administration_errors'])): ?>
            <div class="alert-danger">
                <strong>Please correct the following:</strong>
                <ul>
                    <?php foreach ((array)$_SESSION['administration_errors'] as $error): ?>
                        <li><?= e((string)$error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php unset($_SESSION['administration_errors']); ?>
        <?php endif; ?>

        <div class="permission-mode-tabs">
            <a class="<?= $mode === 'role' ? 'active' : '' ?>" href="matrix.php?mode=role&role_id=<?= (int)$selectedRoleId ?>">Role Permissions</a>
            <a class="<?= $mode === 'user' ? 'active' : '' ?>" href="matrix.php?mode=user&user_id=<?= (int)$selectedUserId ?>">User Overrides</a>
        </div>

        <?php if ($mode === 'user'): ?>
            <form method="GET" class="permission-matrix-toolbar">
                <input type="hidden" name="mode" value="user">
                <label>User Account
                    <select name="user_id" onchange="this.form.submit()">
                        <?php foreach ($users as $user): ?>
                            <?php
                                $label = trim((string)($user['first_name'] ?? '') . ' ' . (string)($user['last_name'] ?? ''));
                                $label = ($label !== '' ? $label : (string)$user['username'])
                                    . ' — ' . (string)($user['username'] ?? '')
                                    . ' / ' . (string)($user['role_name'] ?? '');
                            ?>
                            <option value="<?= (int)$user['id'] ?>" <?= (int)$user['id'] === $selectedUserId ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </form>
            <form method="POST" action="matrix_save.php" class="permission-matrix-form">
                <?= csrfField() ?>
                <input type="hidden" name="mode" value="user">
                <input type="hidden" name="user_id" value="<?= (int)$selectedUserId ?>">
                <div class="permission-list">
                    <?php foreach ($permissions as $permission): ?>
                        <?php $permissionId = (int)$permission['id']; $effect = $userOverrides[$permissionId] ?? 'Inherit'; ?>
                        <div class="permission-option permission-user-option">
                            <span class="permission-option-text"><?= e($permission['module'] . ' - ' . $permission['permission_name']) ?></span>
                            <div class="permission-effects">
                                <?php foreach (['Inherit', 'Allow', 'Deny'] as $choice): ?>
                                    <label class="permission-effect-choice">
                                        <input type="radio" name="permission_effects[<?= $permissionId ?>]" value="<?= e($choice) ?>" <?= $effect === $choice ? 'checked' : '' ?>>
                                        <?= e($choice) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button class="btn-primary" type="submit">Save User Overrides</button>
            </form>
        <?php else: ?>
            <form method="GET" class="permission-matrix-toolbar">
                <input type="hidden" name="mode" value="role">
                <label>Role
                    <select name="role_id" onchange="this.form.submit()">
                        <?php foreach ($roles as $role): ?><option value="<?= (int)$role['id'] ?>" <?= (int)$role['id'] === $selectedRoleId ? 'selected' : '' ?>><?= e($role['role_name']) ?></option><?php endforeach; ?>
                    </select>
                </label>
            </form>
            <form method="POST" action="matrix_save.php" class="permission-matrix-form">
                <?= csrfField() ?>
                <input type="hidden" name="mode" value="role">
                <input type="hidden" name="role_id" value="<?= $selectedRoleId ?>">
                <div class="permission-list">
                    <?php foreach ($permissions as $permission): ?>
                        <label class="permission-option">
                            <input type="checkbox" name="permission_ids[]" value="<?= (int)$permission['id'] ?>" <?= in_array((int)$permission['id'], $assigned, true) ? 'checked' : '' ?>>
                            <span class="permission-option-text"><?= e($permission['module'] . ' - ' . $permission['permission_name']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <button class="btn-primary" type="submit">Save Role Permissions</button>
            </form>
        <?php endif; ?>
    </section>
</main>
<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
