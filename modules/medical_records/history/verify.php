<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php'; requireCsrfToken(); $id = (int)($_POST['id'] ?? 0); $entry = $problemListService->getHistoryEntryById($id);
if (!$entry) { http_response_code(404); exit('Medical history not found.'); }
if (!$permissionService->canVerifyStructuredMedicalHistory((int)$entry['patient_id'], $currentUser)) { longitudinalAccessDenied($permissionService, $currentUser, (int)$entry['patient_id'], 'MEDICAL_HISTORY_ACCESS_DENIED'); }
$visitId = longitudinalVisitContext($pdo, $permissionService, $currentUser, (int)$entry['patient_id'], $_POST['visit_id'] ?? null);
$result = $problemListService->verifyHistoryEntry($id, (string)($_POST['reason'] ?? ''), (int)$currentUser['id'], (int)($_POST['version'] ?? 0), $visitId, longitudinalDepartmentId($currentUser)); longitudinalFlash($result, 'Medical history verified.'); header('Location: view.php?id=' . $id . longitudinalQuery($visitId)); exit;
