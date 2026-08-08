<?php

declare(strict_types=1);

require_once __DIR__ . '/../partials/bootstrap.php';

$pageTitle = 'Administration';
$users = $userService->getUsers();
$departments = $departmentService->listDepartments();
$activeDepartments = count(array_filter($departments, static fn (array $department): bool => !empty($department['is_active'])));

require_once __DIR__ . '/../../../layouts/header.php';
require_once __DIR__ . '/../../../layouts/sidebar.php';

?>

<main class="content">
    <?php require_once __DIR__ . '/../../../layouts/navbar.php'; ?>

    <section class="card">
        <h2>Administration</h2>
        <p>Manage users and security operations.</p>
        <p><a class="btn-primary" href="../../../dashboard/admin.php">Operational Dashboard</a></p>
        <p><a class="btn-primary" href="../users/index.php">Manage Users</a></p>
        <p><a class="btn-primary" href="../departments/index.php">Manage Departments</a></p>
        <p><a class="btn-primary" href="../security/dashboard.php">Security Dashboard</a></p>
        <p><a class="btn-primary" href="../settings/index.php">System Settings</a></p>
        <p>Total departments: <?= count($departments) ?> | Active: <?= $activeDepartments ?> | Inactive: <?= count($departments) - $activeDepartments ?></p>

        <h3>Department Summary</h3>
        <table>
            <thead><tr><th>Department</th><th>Users</th><th>Active Encounters</th><th>Queue</th><th>Status</th></tr></thead>
            <tbody><?php foreach ($departments as $department): ?>
                <tr>
                    <td><?= e($department['department_name']) ?></td>
                    <td><?= (int)$department['active_users'] ?> active / <?= (int)$department['inactive_users'] ?> inactive</td>
                    <td><?= (int)$department['active_encounters'] ?></td>
                    <td><?= !empty($department['queue_enabled']) ? 'Enabled' : 'Disabled' ?></td>
                    <td><?= !empty($department['is_active']) ? 'Active' : 'Inactive' ?></td>
                </tr>
            <?php endforeach; ?></tbody>
        </table>
    </section>
</main>

<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
