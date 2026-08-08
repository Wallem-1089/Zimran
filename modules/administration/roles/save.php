<?php

declare(strict_types=1);

require_once __DIR__ . '/../partials/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
requireCsrfToken();
$result = $roleService->createRole(
    (string)($_POST['role_name'] ?? ''),
    $_POST['description'] ?? null,
    (int)$currentUser['id']
);
$_SESSION[$result['success'] ? 'success_message' : 'administration_errors'] =
    $result['success'] ? 'Role created successfully.' : $result['errors'];
header('Location: ' . ($result['success'] ? 'view.php?id=' . (int)$result['role_id'] : 'create.php'));
exit;
