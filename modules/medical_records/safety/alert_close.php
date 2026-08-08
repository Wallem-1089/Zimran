<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php'; requireCsrfToken();
$id = (int)($_POST['id'] ?? 0); $alert = clinicalSafetyAlertForUser($clinicalSafetyService, $id, $currentUser, false);
if (!$permissionService->canManageClinicalAlerts((int)$alert['patient_id'], $currentUser)) { clinicalSafetyAccessDenied($permissionService, $currentUser, (int)$alert['patient_id']); }
if ((string)$alert['confidentiality_level'] !== 'Standard'
    && !$permissionService->canViewConfidentialAlerts((int)$alert['patient_id'], $currentUser)
) { clinicalSafetyAccessDenied($permissionService, $currentUser, (int)$alert['patient_id']); }
$visitId = clinicalSafetyVisitContext($pdo, $permissionService, $currentUser, (int)$alert['patient_id'], $_POST['visit_id'] ?? null);
$result = $clinicalSafetyService->closeAlert($id, (string)($_POST['reason'] ?? ''), (int)$currentUser['id'], (int)($_POST['version'] ?? 0), $visitId);
$_SESSION[$result['success'] ? 'success_message' : 'validation_errors'] = $result['success'] ? 'Clinical alert closed.' : $result['errors'];
header('Location: alert_view.php?id=' . $id . clinicalSafetyQuery($visitId)); exit;
