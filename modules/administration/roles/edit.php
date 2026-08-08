<?php

declare(strict_types=1);

require_once __DIR__ . '/../partials/bootstrap.php';
$roleId = (int)($_GET['id'] ?? 0);
$role = $roleService->getRole($roleId);
if (!$role) { http_response_code(404); exit('Role not found.'); }
$pageTitle = 'Edit Role';
require_once __DIR__ . '/../../../layouts/header.php';
require_once __DIR__ . '/../../../layouts/sidebar.php';
?>
<main class="content">
    <?php require_once __DIR__ . '/../../../layouts/navbar.php'; ?>
    <section class="card">
        <h2>Edit Role</h2>
        <form method="POST" action="update.php?id=<?= $roleId ?>">
            <?= csrfField() ?>
            <label>Role Name <input name="role_name" required value="<?= e($role['role_name']) ?>"></label>
            <label>Description <textarea name="description"><?= e((string)$role['description']) ?></textarea></label>
            <button class="btn-primary" type="submit">Update Role</button>
        </form>
    </section>
</main>
<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
