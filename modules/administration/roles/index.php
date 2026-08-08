<?php

declare(strict_types=1);

require_once __DIR__ . '/../partials/bootstrap.php';

$pageTitle = 'Role Management';
$roles = $roleService->searchRoles(trim((string)($_GET['search'] ?? '')));

require_once __DIR__ . '/../../../layouts/header.php';
require_once __DIR__ . '/../../../layouts/sidebar.php';

?>
<main class="content">
    <?php require_once __DIR__ . '/../../../layouts/navbar.php'; ?>
    <section class="card">
        <h2>Roles</h2>
        <p><a class="btn-primary" href="create.php">Create Role</a></p>
        <form method="GET"><input name="search" placeholder="Search roles" value="<?= e($_GET['search'] ?? '') ?>"><button type="submit">Search</button></form>
        <table>
            <thead><tr><th>Name</th><th>Description</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($roles as $role): ?>
                <tr>
                    <td><?= e($role['role_name']) ?></td>
                    <td><?= e((string)$role['description']) ?></td>
                    <td><?= !empty($role['is_active']) ? 'Active' : 'Inactive' ?></td>
                    <td><a href="view.php?id=<?= (int)$role['id'] ?>">View</a> <a href="edit.php?id=<?= (int)$role['id'] ?>">Edit</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</main>
<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
