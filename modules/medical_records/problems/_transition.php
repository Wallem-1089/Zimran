<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php'; requireCsrfToken();
$id = (int)($_POST['id'] ?? 0); $problem = $problemListService->getProblemById($id);
if (!$problem) { http_response_code(404); exit('Problem not found.'); }
$patientId = (int)$problem['patient_id'];
$permissionMethod = in_array($problemOperation, ['verify','refute'], true) ? 'canVerifyProblemList' : 'canResolveProblemList';
if (!$permissionService->{$permissionMethod}($patientId, $currentUser)) { longitudinalAccessDenied($permissionService, $currentUser, $patientId, 'PROBLEM_LIST_ACCESS_DENIED'); }
$visitId = longitudinalVisitContext($pdo, $permissionService, $currentUser, $patientId, $_POST['visit_id'] ?? null);
$args = [$id, (string)($_POST['reason'] ?? ''), (int)$currentUser['id'], (int)($_POST['version'] ?? 0), $visitId];
$method = match ($problemOperation) { 'verify' => 'verifyProblem', 'refute' => 'refuteProblem', 'deactivate' => 'deactivateProblem', 'reactivate' => 'reactivateProblem', 'resolve' => 'resolveProblem', default => 'markProblemEnteredInError' };
if ($problemOperation === 'resolve') { $args[] = ($_POST['resolved_date'] ?? null); }
$args[] = longitudinalDepartmentId($currentUser);
$result = $problemListService->{$method}(...$args);
longitudinalFlash($result, $problemSuccessMessage);
header('Location: view.php?id=' . $id . longitudinalQuery($visitId)); exit;
