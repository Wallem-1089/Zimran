<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
$identifier = $identifierService->getIdentifierById((int)($_GET['id'] ?? 0));
if (!$identifier) { http_response_code(404); exit('Identifier not found.'); }
$patient = $patientService->getPatientById((int)$identifier['patient_id']);
if (!$permissionService->canManagePatientIdentifiers((int)$identifier['patient_id'], $identifier['identifier_type'], $currentUser)) { http_response_code(403); exit('Access denied.'); }
$settings = new SettingsService($pdo); $identifierTypes = $settings->getArray('mpi.enabled_identifier_types', []); $pageTitle = 'Edit Patient Identifier'; require __DIR__ . '/form.php';
