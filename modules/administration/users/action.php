<?php

declare(strict_types=1);

require_once __DIR__ . '/../partials/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

requireCsrfToken();

$userId = (int)($_POST['user_id'] ?? 0);
$action = trim((string)($_POST['action'] ?? ''));
$actorId = (int)$currentUser['id'];

$result = match ($action) {
    'activate' => $userService->activateUser($userId, $actorId),
    'deactivate' => $userService->deactivateUser($userId, $actorId),
    'lock' => $userService->lockUser($userId, $actorId, $_POST['reason'] ?? null),
    'unlock' => $userService->unlockUser($userId, $actorId),
    'force_password_change' => $userService->forcePasswordChange($userId, $actorId),
    default => ['success' => false, 'errors' => ['Invalid user action.']]
};

$_SESSION[$result['success'] ? 'success_message' : 'administration_errors'] =
    $result['success'] ? 'User action completed.' : $result['errors'];

header('Location: view.php?id=' . $userId);
exit;
