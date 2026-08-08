<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../services/UserDepartmentService.php';

$userDepartmentService = new UserDepartmentService($pdo);
$userId = (int)$currentUser['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    $result = $userDepartmentService->switchDepartment(
        $userId,
        (int)($_POST['department_id'] ?? 0),
        $userId
    );
    $_SESSION[$result['success'] ? 'success_message' : 'administration_errors'] =
        $result['success'] ? 'Active department switched.' : $result['errors'];
    header('Location: department_switch.php');
    exit;
}

$memberships = $userDepartmentService->listUserDepartments($userId);
$pageTitle = 'Switch Department';

require_once __DIR__ . '/../../layouts/header.php';
require_once __DIR__ . '/../../layouts/sidebar.php';

?>
<main class="content">
    <?php require_once __DIR__ . '/../../layouts/navbar.php'; ?>
    <section class="card">
        <h2>Switch Active Department</h2>
        <?php if (!empty($_SESSION['success_message'])): ?><p class="alert alert-success"><?= e($_SESSION['success_message']) ?></p><?php unset($_SESSION['success_message']); endif; ?>
        <?php if (!empty($_SESSION['administration_errors'])): ?><ul class="alert alert-danger"><?php foreach ($_SESSION['administration_errors'] as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul><?php unset($_SESSION['administration_errors']); endif; ?>
        <form method="POST">
            <?= csrfField() ?>
            <select name="department_id" required>
                <?php foreach ($memberships as $membership): ?>
                    <?php if (!empty($membership['is_active']) && !empty($membership['department_is_active'])): ?>
                        <option value="<?= (int)$membership['department_id'] ?>" <?= !empty($membership['is_primary']) ? 'selected' : '' ?>><?= e($membership['department_name']) ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
            <button class="btn-primary" type="submit">Switch Department</button>
        </form>
    </section>
</main>
<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
