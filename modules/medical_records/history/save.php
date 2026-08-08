<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php'; requireCsrfToken(); $patientId = (int)($_POST['patient_id'] ?? 0);
if (!$permissionService->canManageStructuredMedicalHistory($patientId, $currentUser)) { longitudinalAccessDenied($permissionService, $currentUser, $patientId, 'MEDICAL_HISTORY_ACCESS_DENIED'); }
$visitId = longitudinalVisitContext($pdo, $permissionService, $currentUser, $patientId, $_POST['visit_id'] ?? null); $_POST['source_visit_id'] = $visitId;
$result = $problemListService->addHistoryEntry($_POST, (int)$currentUser['id'], longitudinalDepartmentId($currentUser)); longitudinalFlash($result, 'Medical history added.');
header('Location: ' . ($result['success'] ? 'view.php?id=' . (int)$result['history_entry_id'] : 'create.php?patient=' . $patientId) . longitudinalQuery($visitId)); exit;
