<?php

declare(strict_types=1);

require_once __DIR__ . '/../partials/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
requireCsrfToken();
$roleId = (int)($_GET['id'] ?? 0);
$result = $roleService->updateRole(
    $roleId,
    (string)($_POST['role_name'] ?? ''),
    $_POST['description'] ?? null,
    (int)$currentUser['id']
);
$_SESSION[$result['success'] ? 'success_message' : 'administration_errors'] =
    $result['success'] ? 'Role updated successfully.' : $result['errors'];
header('Location: view.php?id=' . $roleId);
exit;
