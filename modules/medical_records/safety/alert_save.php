<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php'; requireCsrfToken();
$patientId = (int)($_POST['patient_id'] ?? 0);
if (!$permissionService->canManageClinicalAlerts($patientId, $currentUser)) { clinicalSafetyAccessDenied($permissionService, $currentUser, $patientId); }
if ((string)($_POST['confidentiality_level'] ?? 'Standard') !== 'Standard'
    && !$permissionService->canViewConfidentialAlerts($patientId, $currentUser)
) { clinicalSafetyAccessDenied($permissionService, $currentUser, $patientId); }
$visitId = clinicalSafetyVisitContext($pdo, $permissionService, $currentUser, $patientId, $_POST['visit_id'] ?? null);
$_POST['visit_id'] = $visitId;
$result = $clinicalSafetyService->createAlert($_POST, (int)$currentUser['id']);
$_SESSION[$result['success'] ? 'success_message' : 'validation_errors'] = $result['success'] ? 'Clinical alert created.' : $result['errors'];
$contextQuery = clinicalSafetyQuery($visitId);
header('Location: ' . ($result['success'] ? 'alert_view.php?id=' . (int)$result['alert_id'] . $contextQuery : 'alert_create.php?patient=' . $patientId . $contextQuery)); exit;
