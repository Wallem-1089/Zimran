<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
$rawAlert = clinicalSafetyAlertForUser(
    $clinicalSafetyService,
    (int)($_GET['id'] ?? 0),
    $currentUser
);
if (!$permissionService->canManageClinicalAlerts((int)$rawAlert['patient_id'], $currentUser)) { clinicalSafetyAccessDenied($permissionService, $currentUser, (int)$rawAlert['patient_id']); }
$canViewConfidential = $permissionService->canViewConfidentialAlerts((int)$rawAlert['patient_id'], $currentUser);
if ((string)$rawAlert['confidentiality_level'] !== 'Standard' && !$canViewConfidential) { clinicalSafetyAccessDenied($permissionService, $currentUser, (int)$rawAlert['patient_id']); }
$patient = $patientService->getPatientById((int)$rawAlert['patient_id']); $alert = $rawAlert;
$visitId = clinicalSafetyVisitContext($pdo, $permissionService, $currentUser, (int)$rawAlert['patient_id'], $_GET['visit'] ?? null);
$alertAction = 'alert_update.php'; $alertSubmitLabel = 'Update Alert'; $pageTitle = 'Update Clinical Alert'; $moduleStylesheet = '/modules/medical_records/assets/medical_records.css';
require __DIR__ . '/../../../layouts/header.php'; require __DIR__ . '/../../../layouts/sidebar.php';
?>
<div class="main-container"><?php require __DIR__ . '/../../../layouts/navbar.php'; ?><main class="content"><h1>Update Clinical Alert</h1><?php require __DIR__ . '/alert_form.php'; ?></main><?php require __DIR__ . '/../../../layouts/footer.php'; ?></div>
