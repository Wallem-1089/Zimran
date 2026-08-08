<?php

declare(strict_types=1);

require_once __DIR__ . '/../partials/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

requireCsrfToken();

$userId = (int)($_POST['user_id'] ?? 0);
$password = (string)($_POST['password'] ?? '');
$confirmation = (string)($_POST['password_confirmation'] ?? '');

if ($password !== $confirmation) {
    $_SESSION['administration_errors'] = ['Passwords do not match.'];
    header('Location: reset_password.php?id=' . $userId);
    exit;
}

$result = $userService->resetPassword(
    $userId,
    $password,
    (int)$currentUser['id']
);

$_SESSION[$result['success'] ? 'success_message' : 'administration_errors'] =
    $result['success'] ? 'Password reset successfully.' : $result['errors'];

header('Location: view.php?id=' . $userId);
exit;
