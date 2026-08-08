<?php

declare(strict_types=1);

require_once __DIR__ . '/../partials/bootstrap.php';
$userId = (int)($_GET['id'] ?? 0);
$user = $userService->getUserById($userId);
if (!$user) { http_response_code(404); exit('User not found.'); }
$memberships = $userDepartmentService->listUserDepartments($userId);
$departments = $departmentService->listDepartments(false);
$assignedIds = array_map('intval', array_column($memberships, 'department_id'));
$pageTitle = 'User Departments';
require_once __DIR__ . '/../../../layouts/header.php';
require_once __DIR__ . '/../../../layouts/sidebar.php';
?>
<main class="content">
    <?php require_once __DIR__ . '/../../../layouts/navbar.php'; ?>
    <section class="card">
        <h2>Departments: <?= e($user['first_name'] . ' ' . $user['last_name']) ?></h2>
        <h3>Current assignments</h3>
        <table><thead><tr><th>Department</th><th>Primary</th><th>Status</th><th>Actions</th></tr></thead><tbody>
        <?php foreach ($memberships as $membership): ?>
            <tr>
                <td><?= e($membership['department_name']) ?></td>
                <td><?= !empty($membership['is_primary']) ? 'Yes' : 'No' ?></td>
                <td><?= !empty($membership['is_active']) ? 'Active' : 'Inactive' ?></td>
                <td>
                    <?php if (empty($membership['is_primary']) && !empty($membership['is_active'])): ?>
                        <form method="POST" action="department_action.php" style="display:inline">
                            <?= csrfField() ?><input type="hidden" name="user_id" value="<?= $userId ?>"><input type="hidden" name="department_id" value="<?= (int)$membership['department_id'] ?>">
                            <button name="action" value="primary">Set Primary</button>
                            <button name="action" value="remove">Remove</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?></tbody></table>

        <h3>Assign department</h3>
        <form method="POST" action="department_action.php">
            <?= csrfField() ?><input type="hidden" name="user_id" value="<?= $userId ?>">
            <select name="department_id" required>
                <option value="">Select department</option>
                <?php foreach ($departments as $department): ?>
                    <?php if (!in_array((int)$department['id'], $assignedIds, true)): ?>
                        <option value="<?= (int)$department['id'] ?>"><?= e($department['department_name']) ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
            <label><input type="checkbox" name="primary" value="1"> Make primary</label>
            <button class="btn-primary" name="action" value="assign">Assign</button>
        </form>
    </section>
</main>
<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
