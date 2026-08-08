<?php

declare(strict_types=1);

require_once __DIR__ . '/../partials/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: matrix.php'); exit; }
requireCsrfToken();
$roleId = (int)($_POST['role_id'] ?? 0);
$result = $permissionService->assignPermissions(
    $roleId,
    $_POST['permission_ids'] ?? [],
    (int)$currentUser['id']
);
$_SESSION[$result['success'] ? 'success_message' : 'administration_errors'] =
    $result['success'] ? 'Role permissions updated.' : $result['errors'];
header('Location: matrix.php?role_id=' . $roleId);
exit;
