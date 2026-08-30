<?php

declare(strict_types=1);

require_once __DIR__ . '/../partials/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: matrix.php'); exit; }
requireCsrfToken();
$mode = (string)($_POST['mode'] ?? 'role');

if ($mode === 'user') {
    $userId = (int)($_POST['user_id'] ?? 0);
    $targetUser = $userService->getUserById($userId);
    if (strtolower((string)($targetUser['username'] ?? '')) === 'walter') {
        $_SESSION['administration_errors'] = ['The protected Walter administrator account cannot be modified from user permission overrides.'];
        header('Location: matrix.php?mode=user');
        exit;
    }

    $result = $permissionService->assignUserPermissionOverrides(
        $userId,
        $_POST['permission_effects'] ?? [],
        (int)$currentUser['id']
    );
    $_SESSION[$result['success'] ? 'success_message' : 'administration_errors'] =
        $result['success'] ? 'User permission overrides updated.' : $result['errors'];
    header('Location: matrix.php?mode=user&user_id=' . $userId);
    exit;
}

$roleId = (int)($_POST['role_id'] ?? 0);
$result = $permissionService->assignPermissions(
    $roleId,
    $_POST['permission_ids'] ?? [],
    (int)$currentUser['id']
);
$_SESSION[$result['success'] ? 'success_message' : 'administration_errors'] =
    $result['success'] ? 'Role permissions updated.' : $result['errors'];
header('Location: matrix.php?mode=role&role_id=' . $roleId);
exit;
