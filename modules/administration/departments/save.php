<?php

declare(strict_types=1);

require_once __DIR__ . '/../partials/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
requireCsrfToken();
$result = $departmentService->createDepartment($_POST, (int)$currentUser['id']);
$_SESSION[$result['success'] ? 'success_message' : 'administration_errors'] = $result['success'] ? 'Department created.' : $result['errors'];
header('Location: ' . ($result['success'] ? 'view.php?id=' . (int)$result['department_id'] : 'create.php'));
exit;
