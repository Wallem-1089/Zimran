<?php

declare(strict_types=1);

require_once __DIR__ . '/../partials/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
requireCsrfToken();
$departmentId = (int)($_GET['id'] ?? 0);
$result = $departmentService->updateDepartment($departmentId, $_POST, (int)$currentUser['id']);
$_SESSION[$result['success'] ? 'success_message' : 'administration_errors'] = $result['success'] ? 'Department updated.' : $result['errors'];
header('Location: view.php?id=' . $departmentId);
exit;
