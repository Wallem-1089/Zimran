<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php'; requireCsrfToken();
$patientId = (int)($_POST['patient_id'] ?? 0);
if (!$permissionService->canManageProblemList($patientId, $currentUser)) { longitudinalAccessDenied($permissionService, $currentUser, $patientId, 'PROBLEM_LIST_ACCESS_DENIED'); }
$visitId = longitudinalVisitContext($pdo, $permissionService, $currentUser, $patientId, $_POST['visit_id'] ?? null);
$_POST['source_visit_id'] = $visitId;
$result = $problemListService->addProblem($_POST, (int)$currentUser['id'], longitudinalDepartmentId($currentUser));
longitudinalFlash($result, 'Problem added.');
header('Location: ' . ($result['success'] ? 'view.php?id=' . (int)$result['problem_id'] : 'create.php?patient=' . $patientId) . longitudinalQuery($visitId)); exit;
