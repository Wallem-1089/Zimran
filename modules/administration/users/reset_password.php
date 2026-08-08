<?php

declare(strict_types=1);

require_once __DIR__ . '/../partials/bootstrap.php';

$userId = (int)($_GET['id'] ?? 0);
$user = $userService->getUserById($userId);

if (!$user) {
    http_response_code(404);
    exit('User not found.');
}

$pageTitle = 'Reset User Password';

require_once __DIR__ . '/../../../layouts/header.php';
require_once __DIR__ . '/../../../layouts/sidebar.php';

?>
<main class="content">
    <?php require_once __DIR__ . '/../../../layouts/navbar.php'; ?>
    <section class="card">
        <h2>Reset Password</h2>
        <p><?= e($user['first_name'] . ' ' . $user['last_name']) ?></p>
        <form method="POST" action="reset_password_save.php">
            <?= csrfField() ?>
            <input type="hidden" name="user_id" value="<?= $userId ?>">
            <label>New Password
                <input type="password" name="password" minlength="8" required>
            </label>
            <label>Confirm Password
                <input type="password" name="password_confirmation" minlength="8" required>
            </label>
            <button class="btn-primary" type="submit">Reset Password</button>
        </form>
    </section>
</main>
<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
