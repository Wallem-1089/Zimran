<?php

declare(strict_types=1);

require_once __DIR__ . '/../partials/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

requireCsrfToken();

$userId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$result = $userService->updateUser(
    $userId,
    $_POST,
    (int)$currentUser['id']
);

if (!$result['success']) {
    $_SESSION['administration_errors'] = $result['errors'];
    header('Location: edit.php?id=' . $userId);
    exit;
}

$_SESSION['success_message'] = 'User updated successfully.';
header('Location: view.php?id=' . $userId);
exit;
