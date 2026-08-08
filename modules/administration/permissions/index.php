<?php

declare(strict_types=1);

require_once __DIR__ . '/../partials/bootstrap.php';
$pageTitle = 'Permission Management';
$permissions = $permissionService->listPermissions();
require_once __DIR__ . '/../../../layouts/header.php';
require_once __DIR__ . '/../../../layouts/sidebar.php';
?>
<main class="content">
    <?php require_once __DIR__ . '/../../../layouts/navbar.php'; ?>
    <section class="card">
        <h2>Permissions</h2>
        <p><a class="btn-primary" href="create.php">Create Permission</a></p>
        <p><a href="matrix.php">Role Permission Matrix</a></p>
        <table>
            <thead><tr><th>Key</th><th>Name</th><th>Module</th><th>Status</th><th>Action</th></tr></thead>
            <tbody><?php foreach ($permissions as $permission): ?>
                <tr>
                    <td><?= e($permission['permission_key']) ?></td>
                    <td><?= e($permission['permission_name']) ?></td>
                    <td><?= e($permission['module']) ?></td>
                    <td><?= !empty($permission['is_active']) ? 'Active' : 'Inactive' ?></td>
                    <td><a href="edit.php?id=<?= (int)$permission['id'] ?>">Edit</a></td>
                </tr>
            <?php endforeach; ?></tbody>
        </table>
    </section>
</main>
<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
