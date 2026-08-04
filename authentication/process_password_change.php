<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Bootstrap
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

require_once __DIR__ . '/../services/UserService.php';
require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../services/SessionService.php';
require_once __DIR__ . '/../services/AuditService.php';

/*
|--------------------------------------------------------------------------
| Services
|--------------------------------------------------------------------------
*/

$userService = new UserService($pdo);
$authService = new AuthService($pdo);
$sessionService = new SessionService();
$auditService = new AuditService($pdo);

/*
|--------------------------------------------------------------------------
| Require Login
|--------------------------------------------------------------------------
*/

$sessionService->requireLogin();

/*
|--------------------------------------------------------------------------
| Allow POST Only
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header('Location: change_password.php');
    exit;

}

if (!verifyCsrfToken()) {

    $auditService->log(
        isset($_SESSION['user']['id'])
            ? (int)$_SESSION['user']['id']
            : null,
        null,
        'Security',
        'INVALID_CSRF',
        'Password change request failed CSRF validation.'
    );

    $_SESSION['error_message'] =
        'Security validation failed. Please try again.';

    header('Location: change_password.php');

    exit;

}

/*
|--------------------------------------------------------------------------
| Current User
|--------------------------------------------------------------------------
*/

$user = $sessionService->user();

if (!$user) {

    header('Location: login.php');
    exit;

}

$userId = (int)$user['id'];

/*
|--------------------------------------------------------------------------
| Reload User From Database
|--------------------------------------------------------------------------
*/

$dbUser = $userService->findById($userId);

if (!$dbUser) {

    $sessionService->logout();

    header('Location: login.php');
    exit;

}

/*
|--------------------------------------------------------------------------
| Retrieve Form Data
|--------------------------------------------------------------------------
*/

$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

$errors = [];

/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/

if ($currentPassword === '') {

    $errors[] = 'Current password is required.';

}

if ($newPassword === '') {

    $errors[] = 'New password is required.';

}

if (strlen($newPassword) < 8) {

    $errors[] = 'New password must contain at least 8 characters.';

}

if ($newPassword !== $confirmPassword) {

    $errors[] = 'Password confirmation does not match.';

}

/*
|--------------------------------------------------------------------------
| Verify Current Password
|--------------------------------------------------------------------------
*/

if (
    empty($errors) &&
    !$authService->verifyPassword(
        $currentPassword,
        $dbUser['password']
    )
) {

    $errors[] = 'Current password is incorrect.';

}

/*
|--------------------------------------------------------------------------
| Prevent Reusing Same Password
|--------------------------------------------------------------------------
*/

if (
    empty($errors) &&
    $authService->verifyPassword(
        $newPassword,
        $dbUser['password']
    )
) {

    $errors[] =
        'Your new password must be different from the current password.';

}

/*
|--------------------------------------------------------------------------
| Validation Failed
|--------------------------------------------------------------------------
*/

if (!empty($errors)) {

    $_SESSION['password_errors'] = $errors;

    header('Location: change_password.php');

    exit;

}

/*
|--------------------------------------------------------------------------
| Update Password
|--------------------------------------------------------------------------
*/

$hashedPassword = $authService->hashPassword($newPassword);

$userService->updatePassword(
    $userId,
    $hashedPassword
);

/*
|--------------------------------------------------------------------------
| Audit Log
|--------------------------------------------------------------------------
*/

$auditService->updated(
    $userId,
    null,
    'Authentication',
    'Password changed successfully.'
);

/*
|--------------------------------------------------------------------------
| Success
|--------------------------------------------------------------------------
*/

$_SESSION['success_message'] =
    'Password changed successfully.';

header('Location: ../dashboard/index.php');

exit;
