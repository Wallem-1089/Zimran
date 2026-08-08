<?php

declare(strict_types=1);

require_once __DIR__ . '/../partials/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
requireCsrfToken();
$departmentId = (int)($_POST['department_id'] ?? 0);
$result = ($_POST['action'] ?? '') === 'activate'
    ? $departmentService->activateDepartment($departmentId, (int)$currentUser['id'])
    : $departmentService->deactivateDepartment($departmentId, (int)$currentUser['id']);
$_SESSION[$result['success'] ? 'success_message' : 'administration_errors'] = $result['success'] ? 'Department status updated.' : $result['errors'];
header('Location: view.php?id=' . $departmentId);
exit;
