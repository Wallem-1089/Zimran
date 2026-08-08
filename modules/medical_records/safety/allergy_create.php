<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
$patientId = filter_input(INPUT_GET, 'patient', FILTER_VALIDATE_INT);
$patient = $patientId ? $patientService->getPatientById($patientId) : null;
if (!$patient) { http_response_code(404); exit('Patient not found.'); }
if (!$permissionService->canRecordAllergies((int)$patientId, $currentUser)) { clinicalSafetyAccessDenied($permissionService, $currentUser, (int)$patientId); }
$visitId = clinicalSafetyVisitContext($pdo, $permissionService, $currentUser, (int)$patientId, $_GET['visit'] ?? null);
$allergyAction = 'allergy_save.php';
$allergySubmitLabel = 'Record Allergy';
$pageTitle = 'Record Allergy';
$moduleStylesheet = '/modules/medical_records/assets/medical_records.css';
require __DIR__ . '/../../../layouts/header.php'; require __DIR__ . '/../../../layouts/sidebar.php';
?>
<div class="main-container"><?php require __DIR__ . '/../../../layouts/navbar.php'; ?><main class="content"><h1>Record Allergy</h1><p><?= e($patient['hospital_number'] . ' — ' . $patient['first_name'] . ' ' . $patient['last_name']) ?></p><?php require __DIR__ . '/allergy_form.php'; ?></main><?php require __DIR__ . '/../../../layouts/footer.php'; ?></div>
