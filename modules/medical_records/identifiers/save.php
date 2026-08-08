<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../index.php'); exit; }
requireCsrfToken();
$patientId = (int)($_POST['patient_id'] ?? 0);
if (!$permissionService->canManagePatientIdentifiers($patientId, (string)($_POST['identifier_type'] ?? ''), $currentUser)) {
    http_response_code(403); exit('You do not have permission to manage this identifier.');
}
$result = $identifierService->addIdentifier($_POST, (int)$currentUser['id']);
$_SESSION[$result['success'] ? 'success_message' : 'validation_errors'] = $result['success'] ? 'Identifier added.' : $result['errors'];
header('Location: ' . ($result['success'] ? '../chart.php?patient=' . $patientId . '&tab=identifiers' : 'create.php?patient=' . $patientId));
exit;
