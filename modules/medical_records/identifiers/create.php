<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
$patientId = filter_input(INPUT_GET, 'patient', FILTER_VALIDATE_INT);
$patient = $patientId ? $patientService->getPatientById($patientId) : null;
if (!$patient) { http_response_code(404); exit('Patient not found.'); }
if (!$permissionService->canManagePatientIdentifiers($patientId, null, $currentUser)) {
    http_response_code(403); exit('You do not have permission to manage identifiers.');
}
$settings = new SettingsService($pdo);
$identifierTypes = $settings->getArray('mpi.enabled_identifier_types', []);
$pageTitle = 'Add Patient Identifier';
require __DIR__ . '/form.php';
