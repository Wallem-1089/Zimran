<?php

declare(strict_types=1);

require_once __DIR__ . '/../partials/bootstrap.php';
$pageTitle = 'Department Management';
$departments = $departmentService->searchDepartments(trim((string)($_GET['search'] ?? '')));
require_once __DIR__ . '/../../../layouts/header.php';
require_once __DIR__ . '/../../../layouts/sidebar.php';
?>
<main class="content">
    <?php require_once __DIR__ . '/../../../layouts/navbar.php'; ?>
    <section class="card">
        <h2>Departments</h2>
        <p><a class="btn-primary" href="create.php">Create Department</a></p>
        <form method="GET"><input name="search" placeholder="Search departments" value="<?= e($_GET['search'] ?? '') ?>"><button type="submit">Search</button></form>
        <table>
            <thead><tr><th>Name</th><th>Code</th><th>Type</th><th>Users</th><th>Encounters</th><th>Queue</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody><?php foreach ($departments as $department): ?>
                <tr>
                    <td><?= e($department['department_name']) ?></td>
                    <td><?= e($department['department_code']) ?></td>
                    <td><?= e($department['department_type']) ?></td>
                    <td><?= (int)$department['active_users'] ?> active / <?= (int)$department['inactive_users'] ?> inactive</td>
                    <td><?= (int)$department['active_encounters'] ?></td>
                    <td><?= !empty($department['queue_enabled']) ? 'Enabled' : 'Disabled' ?></td>
                    <td><?= !empty($department['is_active']) ? 'Active' : 'Inactive' ?></td>
                    <td><a href="view.php?id=<?= (int)$department['id'] ?>">View</a> <a href="edit.php?id=<?= (int)$department['id'] ?>">Edit</a></td>
                </tr>
            <?php endforeach; ?></tbody>
        </table>
    </section>
</main>
<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
