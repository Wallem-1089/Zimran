<?php

declare(strict_types=1);

require_once __DIR__ . '/../partials/bootstrap.php';
$pageTitle = 'Role Permission Matrix';
$roles = $roleService->listRoles(false);
$permissions = $permissionService->listPermissions(false);
$selectedRoleId = (int)($_GET['role_id'] ?? ($roles[0]['id'] ?? 0));
$assigned = array_map('intval', array_column($permissionService->getRolePermissions($selectedRoleId), 'id'));
require_once __DIR__ . '/../../../layouts/header.php';
require_once __DIR__ . '/../../../layouts/sidebar.php';
?>
<main class="content">
    <?php require_once __DIR__ . '/../../../layouts/navbar.php'; ?>
    <section class="card">
        <h2>Role Permission Matrix</h2>
        <form method="GET">
            <label>Role
                <select name="role_id" onchange="this.form.submit()">
                    <?php foreach ($roles as $role): ?><option value="<?= (int)$role['id'] ?>" <?= (int)$role['id'] === $selectedRoleId ? 'selected' : '' ?>><?= e($role['role_name']) ?></option><?php endforeach; ?>
                </select>
            </label>
        </form>
        <form method="POST" action="matrix_save.php">
            <?= csrfField() ?>
            <input type="hidden" name="role_id" value="<?= $selectedRoleId ?>">
            <?php foreach ($permissions as $permission): ?>
                <label style="display:block">
                    <input type="checkbox" name="permission_ids[]" value="<?= (int)$permission['id'] ?>" <?= in_array((int)$permission['id'], $assigned, true) ? 'checked' : '' ?>>
                    <?= e($permission['module'] . ' - ' . $permission['permission_name']) ?>
                </label>
            <?php endforeach; ?>
            <button class="btn-primary" type="submit">Save Permissions</button>
        </form>
    </section>
</main>
<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
