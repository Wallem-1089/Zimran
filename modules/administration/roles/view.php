<?php

declare(strict_types=1);

require_once __DIR__ . '/../partials/bootstrap.php';
$roleId = (int)($_GET['id'] ?? 0);
$role = $roleService->getRole($roleId);
if (!$role) { http_response_code(404); exit('Role not found.'); }
$pageTitle = 'View Role';
$permissions = $permissionService->getRolePermissions($roleId);
$success = $_SESSION['success_message'] ?? null;
unset($_SESSION['success_message']);
require_once __DIR__ . '/../../../layouts/header.php';
require_once __DIR__ . '/../../../layouts/sidebar.php';
?>
<main class="content">
    <?php require_once __DIR__ . '/../../../layouts/navbar.php'; ?>
    <section class="card">
        <h2><?= e($role['role_name']) ?></h2>
        <?php if ($success): ?><p class="alert alert-success"><?= e($success) ?></p><?php endif; ?>
        <p><?= e((string)$role['description']) ?></p>
        <p>Status: <?= !empty($role['is_active']) ? 'Active' : 'Inactive' ?></p>
        <p><a class="btn-primary" href="edit.php?id=<?= $roleId ?>">Edit</a> <a href="../permissions/matrix.php?role_id=<?= $roleId ?>">Permission Matrix</a></p>
        <form method="POST" action="action.php">
            <?= csrfField() ?>
            <input type="hidden" name="role_id" value="<?= $roleId ?>">
            <button name="action" value="<?= !empty($role['is_active']) ? 'deactivate' : 'activate' ?>">
                <?= !empty($role['is_active']) ? 'Deactivate' : 'Activate' ?>
            </button>
        </form>
        <h3>Assigned Permissions</h3>
        <ul><?php foreach ($permissions as $permission): ?><li><?= e($permission['permission_name']) ?></li><?php endforeach; ?></ul>
    </section>
</main>
<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
