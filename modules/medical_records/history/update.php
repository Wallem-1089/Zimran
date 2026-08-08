<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php'; requireCsrfToken(); $id = (int)($_POST['id'] ?? 0); $entry = $problemListService->getHistoryEntryById($id);
if (!$entry) { http_response_code(404); exit('Medical history not found.'); }
if (!$permissionService->canManageStructuredMedicalHistory((int)$entry['patient_id'], $currentUser)) { longitudinalAccessDenied($permissionService, $currentUser, (int)$entry['patient_id'], 'MEDICAL_HISTORY_ACCESS_DENIED'); }
$visitId = longitudinalVisitContext($pdo, $permissionService, $currentUser, (int)$entry['patient_id'], $_POST['visit_id'] ?? null); $_POST['visit_id'] = $visitId;
$result = $problemListService->updateHistoryEntry($id, $_POST, (int)($_POST['version'] ?? 0), (int)$currentUser['id'], longitudinalDepartmentId($currentUser)); longitudinalFlash($result, 'Medical history updated.');
header('Location: ' . ($result['success'] ? 'view.php?id=' . $id : 'edit.php?id=' . $id) . longitudinalQuery($visitId)); exit;
