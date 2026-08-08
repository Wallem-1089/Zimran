<?php

declare(strict_types=1);

require_once __DIR__ . '/../partials/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
requireCsrfToken();
$permissionId = (int)($_GET['id'] ?? 0);
$result = $permissionService->updatePermission(
    $permissionId,
    (string)($_POST['permission_key'] ?? ''),
    (string)($_POST['permission_name'] ?? ''),
    (string)($_POST['module'] ?? ''),
    $_POST['description'] ?? null,
    (int)$currentUser['id']
);
$_SESSION[$result['success'] ? 'success_message' : 'administration_errors'] =
    $result['success'] ? 'Permission updated successfully.' : $result['errors'];
header('Location: index.php');
exit;
