<?php

declare(strict_types=1);

require_once __DIR__ . '/../partials/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: create.php');
    exit;
}

requireCsrfToken();

$result = $userService->createUser($_POST, (int)$currentUser['id']);

if (!$result['success']) {
    $_SESSION['administration_errors'] = $result['errors'];
    header('Location: create.php');
    exit;
}

$_SESSION['success_message'] = 'User created successfully.';
header('Location: view.php?id=' . (int)$result['user_id']);
exit;
