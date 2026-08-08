<?php

declare(strict_types=1);

require_once __DIR__ . '/../partials/bootstrap.php';

$pageTitle = 'User Management';
$users = $userService->getUsers([
    'search' => trim((string)($_GET['search'] ?? '')),
    'status' => trim((string)($_GET['status'] ?? ''))
]);

require_once __DIR__ . '/../../../layouts/header.php';
require_once __DIR__ . '/../../../layouts/sidebar.php';

?>

<main class="content">
    <?php require_once __DIR__ . '/../../../layouts/navbar.php'; ?>
    <section class="card">
        <h2>Users</h2>
        <p><a class="btn-primary" href="create.php">Create User</a></p>

        <form method="GET">
            <input name="search" placeholder="Search users" value="<?= e($_GET['search'] ?? '') ?>">
            <select name="status">
                <option value="">All statuses</option>
                <option value="Active" <?= ($_GET['status'] ?? '') === 'Active' ? 'selected' : '' ?>>Active</option>
                <option value="Inactive" <?= ($_GET['status'] ?? '') === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
            <button type="submit">Filter</button>
        </form>

        <table>
            <thead><tr><th>Employee ID</th><th>Name</th><th>Username</th><th>Role</th><th>Department</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= e($user['employee_id']) ?></td>
                    <td><?= e($user['first_name'] . ' ' . $user['last_name']) ?></td>
                    <td><?= e($user['username']) ?></td>
                    <td><?= e($user['role_name']) ?></td>
                    <td><?= e($user['department_name']) ?></td>
                    <td><?= e($user['status']) ?><?= !empty($user['locked_at']) ? ' / Locked' : '' ?></td>
                    <td>
                        <a href="view.php?id=<?= (int)$user['id'] ?>">View</a>
                        <a href="edit.php?id=<?= (int)$user['id'] ?>">Edit</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</main>

<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
