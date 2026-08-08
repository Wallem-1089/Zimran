<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php'; requireCsrfToken();
$id = (int)($_POST['id'] ?? 0); $alert = clinicalSafetyAlertForUser($clinicalSafetyService, $id, $currentUser, false);
if (!$permissionService->canManageClinicalAlerts((int)$alert['patient_id'], $currentUser)) { clinicalSafetyAccessDenied($permissionService, $currentUser, (int)$alert['patient_id']); }
$canViewConfidential = $permissionService->canViewConfidentialAlerts((int)$alert['patient_id'], $currentUser);
if (((string)$alert['confidentiality_level'] !== 'Standard'
    || (string)($_POST['confidentiality_level'] ?? 'Standard') !== 'Standard')
    && !$canViewConfidential
) { clinicalSafetyAccessDenied($permissionService, $currentUser, (int)$alert['patient_id']); }
$visitId = clinicalSafetyVisitContext($pdo, $permissionService, $currentUser, (int)$alert['patient_id'], $_POST['visit_id'] ?? null);
$_POST['event_visit_id'] = $visitId;
$result = $clinicalSafetyService->updateAlert($id, $_POST, (int)($_POST['version'] ?? 0), (int)$currentUser['id']);
$_SESSION[$result['success'] ? 'success_message' : 'validation_errors'] = $result['success'] ? 'Clinical alert updated.' : $result['errors'];
$contextQuery = clinicalSafetyQuery($visitId);
header('Location: ' . ($result['success'] ? 'alert_view.php?id=' . $id . $contextQuery : 'alert_edit.php?id=' . $id . $contextQuery)); exit;
