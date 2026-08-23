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
<style>
    .permission-matrix-toolbar {
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
        <h2>Role Permission Matrix</h2>
        <form method="GET" class="permission-matrix-toolbar">
            <label>Role
                <select name="role_id" onchange="this.form.submit()">
                    <?php foreach ($roles as $role): ?><option value="<?= (int)$role['id'] ?>" <?= (int)$role['id'] === $selectedRoleId ? 'selected' : '' ?>><?= e($role['role_name']) ?></option><?php endforeach; ?>
                </select>
            </label>
        </form>
        <form method="POST" action="matrix_save.php" class="permission-matrix-form">
            <?= csrfField() ?>
            <input type="hidden" name="role_id" value="<?= $selectedRoleId ?>">
            <div class="permission-list">
                <?php foreach ($permissions as $permission): ?>
                    <label class="permission-option">
                        <input type="checkbox" name="permission_ids[]" value="<?= (int)$permission['id'] ?>" <?= in_array((int)$permission['id'], $assigned, true) ? 'checked' : '' ?>>
                        <span class="permission-option-text"><?= e($permission['module'] . ' - ' . $permission['permission_name']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <button class="btn-primary" type="submit">Save Permissions</button>
        </form>
    </section>
</main>
<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
