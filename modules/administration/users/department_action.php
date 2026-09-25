<?php

declare(strict_types=1);

require_once __DIR__ . '/../partials/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
requireCsrfToken();
$userId = (int)($_POST['user_id'] ?? 0);
$departmentId = (int)($_POST['department_id'] ?? 0);
$actorId = (int)$currentUser['id'];
$action = (string)($_POST['action'] ?? '');
$targetUser = $userService->getUserById($userId);
if (!$targetUser) { http_response_code(404); exit('User not found.'); }
administrationGuardSuperAdministratorUser($targetUser, $currentUser, $permissionService);
$result = match ($action) {
    'assign' => $userDepartmentService->assignDepartment($userId, $departmentId, $actorId, !empty($_POST['primary'])),
    'remove' => $userDepartmentService->removeDepartment($userId, $departmentId, $actorId),
    'primary' => $userDepartmentService->setPrimaryDepartment($userId, $departmentId, $actorId),
    default => ['success' => false, 'errors' => ['Invalid department action.']]
};
$_SESSION[$result['success'] ? 'success_message' : 'administration_errors'] = $result['success'] ? 'Department assignment updated.' : $result['errors'];
header('Location: departments.php?id=' . $userId);
exit;
