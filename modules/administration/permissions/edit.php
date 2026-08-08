<?php

declare(strict_types=1);

require_once __DIR__ . '/../partials/bootstrap.php';
$permissionId = (int)($_GET['id'] ?? 0);
$permissions = $permissionService->listPermissions();
$permission = null;
foreach ($permissions as $candidate) {
    if ((int)$candidate['id'] === $permissionId) { $permission = $candidate; break; }
}
if (!$permission) { http_response_code(404); exit('Permission not found.'); }
$pageTitle = 'Edit Permission';
require_once __DIR__ . '/../../../layouts/header.php';
require_once __DIR__ . '/../../../layouts/sidebar.php';
?>
<main class="content">
    <?php require_once __DIR__ . '/../../../layouts/navbar.php'; ?>
    <section class="card">
        <h2>Edit Permission</h2>
        <form method="POST" action="update.php?id=<?= $permissionId ?>">
            <?= csrfField() ?>
            <label>Permission Key <input name="permission_key" required value="<?= e($permission['permission_key']) ?>"></label>
            <label>Permission Name <input name="permission_name" required value="<?= e($permission['permission_name']) ?>"></label>
            <label>Module <input name="module" required value="<?= e($permission['module']) ?>"></label>
            <label>Description <textarea name="description"><?= e((string)$permission['description']) ?></textarea></label>
            <button class="btn-primary" type="submit">Update Permission</button>
        </form>
    </section>
</main>
<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
