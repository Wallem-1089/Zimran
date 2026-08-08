<?php

declare(strict_types=1);

require_once __DIR__ . '/../partials/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../../dashboard/index.php'); exit; }
requireCsrfToken();
$departmentId = (int)($_POST['department_id'] ?? 0);
$userId = (int)$currentUser['id'];
$result = $userDepartmentService->switchDepartment($userId, $departmentId, $userId);
$_SESSION[$result['success'] ? 'success_message' : 'administration_errors'] = $result['success'] ? 'Active department switched.' : $result['errors'];
header('Location: ../../dashboard/index.php');
exit;
