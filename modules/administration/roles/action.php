<?php

declare(strict_types=1);

require_once __DIR__ . '/../partials/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
requireCsrfToken();
$roleId = (int)($_POST['role_id'] ?? 0);
$result = ($_POST['action'] ?? '') === 'activate'
    ? $roleService->activateRole($roleId, (int)$currentUser['id'])
    : $roleService->deactivateRole($roleId, (int)$currentUser['id']);
$_SESSION[$result['success'] ? 'success_message' : 'administration_errors'] =
    $result['success'] ? 'Role status updated.' : $result['errors'];
header('Location: view.php?id=' . $roleId);
exit;
