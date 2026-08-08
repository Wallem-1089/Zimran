<?php

declare(strict_types=1);

require_once __DIR__ . '/../partials/bootstrap.php';
$departmentId = (int)($_GET['id'] ?? 0);
$department = $departmentService->getDepartment($departmentId);
if (!$department) { http_response_code(404); exit('Department not found.'); }
$pageTitle = 'View Department';
$success = $_SESSION['success_message'] ?? null;
unset($_SESSION['success_message']);
require_once __DIR__ . '/../../../layouts/header.php';
require_once __DIR__ . '/../../../layouts/sidebar.php';
?>
<main class="content">
    <?php require_once __DIR__ . '/../../../layouts/navbar.php'; ?>
    <section class="card">
        <h2><?= e($department['department_name']) ?></h2>
        <?php if ($success): ?><p class="alert alert-success"><?= e($success) ?></p><?php endif; ?>
        <p>Code: <?= e($department['department_code']) ?></p>
        <p>Type: <?= e($department['department_type']) ?></p>
        <p>Location: <?= e((string)$department['location']) ?></p>
        <p>Contact: <?= e((string)$department['contact_extension']) ?></p>
        <p>Active users: <?= (int)$department['active_users'] ?> | Inactive users: <?= (int)$department['inactive_users'] ?></p>
        <p>Active encounters: <?= (int)$department['active_encounters'] ?></p>
        <p>Queue: <?= !empty($department['queue_enabled']) ? 'Enabled' : 'Disabled' ?></p>
        <p><a class="btn-primary" href="edit.php?id=<?= $departmentId ?>">Edit</a></p>
        <form method="POST" action="action.php">
            <?= csrfField() ?><input type="hidden" name="department_id" value="<?= $departmentId ?>">
            <button name="action" value="<?= !empty($department['is_active']) ? 'deactivate' : 'activate' ?>"><?= !empty($department['is_active']) ? 'Deactivate' : 'Activate' ?></button>
        </form>
    </section>
</main>
<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
