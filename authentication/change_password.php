<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Bootstrap
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../services/SessionService.php';

/*
|--------------------------------------------------------------------------
| Require Login
|--------------------------------------------------------------------------
*/

$sessionService = new SessionService();

$sessionService->requireLogin();

/*
|--------------------------------------------------------------------------
| Page Setup
|--------------------------------------------------------------------------
*/

$pageTitle = 'Change Password';

$errors = $_SESSION['password_errors'] ?? [];
unset($_SESSION['password_errors']);

$success = $_SESSION['success_message'] ?? null;
unset($_SESSION['success_message']);

$user = $sessionService->user();

require_once __DIR__ . '/../layouts/header.php';
$branding = appBranding($GLOBALS['pdo'] ?? null);

?>

<div class="login-container">

    <div class="login-card">

        <h2>Change Password</h2>

        <p>

            Hello
            <strong><?= e($user['first_name']) ?></strong>,

            you must change your password before you can continue
            using <?= e($branding['full_name']) ?>.

        </p>

        <?php if (!empty($errors)): ?>

            <div class="alert alert-danger">

                <ul>

                    <?php foreach ($errors as $error): ?>

                        <li><?= e($error) ?></li>

                    <?php endforeach; ?>

                </ul>

            </div>

        <?php endif; ?>

        <?php if ($success): ?>

            <div class="alert alert-success">

                <?= e($success) ?>

            </div>

        <?php endif; ?>

        <form
            method="POST"
            action="process_password_change.php"
            autocomplete="off">

            <?= csrfField() ?>

            <div class="form-group">

                <label for="current_password">

                    Current Password

                </label>

                <input
                    id="current_password"
                    type="password"
                    name="current_password"
                    required>

            </div>

            <div class="form-group">

                <label for="new_password">

                    New Password

                </label>

                <input
                    id="new_password"
                    type="password"
                    name="new_password"
                    minlength="8"
                    required>

            </div>

            <div class="form-group">

                <label for="confirm_password">

                    Confirm New Password

                </label>

                <input
                    id="confirm_password"
                    type="password"
                    name="confirm_password"
                    minlength="8"
                    required>

            </div>

            <div class="form-actions">

                <button
                    type="submit"
                    class="btn-primary">

                    Change Password

                </button>

            </div>

        </form>

    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
