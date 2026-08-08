<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php'; requireCsrfToken();
$id = (int)($_POST['id'] ?? 0); $problem = $problemListService->getProblemById($id);
if (!$problem) { http_response_code(404); exit('Problem not found.'); }
if (!$permissionService->canManageProblemList((int)$problem['patient_id'], $currentUser)) { longitudinalAccessDenied($permissionService, $currentUser, (int)$problem['patient_id'], 'PROBLEM_LIST_ACCESS_DENIED'); }
$visitId = longitudinalVisitContext($pdo, $permissionService, $currentUser, (int)$problem['patient_id'], $_POST['visit_id'] ?? null); $_POST['visit_id'] = $visitId;
$result = $problemListService->updateProblem($id, $_POST, (int)($_POST['version'] ?? 0), (int)$currentUser['id'], longitudinalDepartmentId($currentUser));
longitudinalFlash($result, 'Problem updated.'); header('Location: ' . ($result['success'] ? 'view.php?id=' . $id : 'edit.php?id=' . $id) . longitudinalQuery($visitId)); exit;
