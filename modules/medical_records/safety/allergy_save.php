<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
requireCsrfToken();
$patientId = (int)($_POST['patient_id'] ?? 0);
if (!$permissionService->canRecordAllergies($patientId, $currentUser)) { clinicalSafetyAccessDenied($permissionService, $currentUser, $patientId); }
$visitId = clinicalSafetyVisitContext($pdo, $permissionService, $currentUser, $patientId, $_POST['visit_id'] ?? null);
$_POST['source_visit_id'] = $visitId;
$result = $clinicalSafetyService->recordAllergy($_POST, (int)$currentUser['id']);
$_SESSION[$result['success'] ? 'success_message' : 'validation_errors'] = $result['success'] ? 'Allergy recorded.' : $result['errors'];
$contextQuery = clinicalSafetyQuery($visitId);
header('Location: ' . ($result['success'] ? 'allergy_view.php?id=' . (int)$result['allergy_id'] . $contextQuery : 'allergy_create.php?patient=' . $patientId . $contextQuery));
exit;
