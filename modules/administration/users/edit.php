<?php

declare(strict_types=1);

require_once __DIR__ . '/../partials/bootstrap.php';

$userId = (int)($_GET['id'] ?? 0);
$user = $userService->getUserById($userId);

if (!$user) {
    http_response_code(404);
    exit('User not found.');
}

$pageTitle = 'Edit User';
$roles = $userService->getRoles();
$departments = $userService->getDepartments();
$formAction = 'update.php?id=' . $userId;
$isEdit = true;

require_once __DIR__ . '/../../../layouts/header.php';
require_once __DIR__ . '/../../../layouts/sidebar.php';

?>
<main class="content">
    <?php require_once __DIR__ . '/../../../layouts/navbar.php'; ?>
    <section class="card">
        <h2>Edit User</h2>
        <?php if (!empty($_SESSION['administration_errors'])): ?>
            <ul class="alert alert-danger">
                <?php foreach ($_SESSION['administration_errors'] as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
            </ul>
            <?php unset($_SESSION['administration_errors']); ?>
        <?php endif; ?>
        <?php require __DIR__ . '/../partials/user_form.php'; ?>
    </section>
</main>
<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
