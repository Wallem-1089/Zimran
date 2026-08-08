<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
$allergy = $clinicalSafetyService->getAllergyById((int)($_GET['id'] ?? 0));
if (!$allergy) { http_response_code(404); exit('Allergy not found.'); }
$patient = $patientService->getPatientById((int)$allergy['patient_id']);
if (!$permissionService->canUpdateAllergies((int)$allergy['patient_id'], $currentUser)) { clinicalSafetyAccessDenied($permissionService, $currentUser, (int)$allergy['patient_id']); }
$visitId = clinicalSafetyVisitContext($pdo, $permissionService, $currentUser, (int)$allergy['patient_id'], $_GET['visit'] ?? null);
$allergyAction = 'allergy_update.php';
$allergySubmitLabel = 'Update Allergy';
$pageTitle = 'Update Allergy';
$moduleStylesheet = '/modules/medical_records/assets/medical_records.css';
require __DIR__ . '/../../../layouts/header.php'; require __DIR__ . '/../../../layouts/sidebar.php';
?>
<div class="main-container"><?php require __DIR__ . '/../../../layouts/navbar.php'; ?><main class="content"><h1>Update Allergy</h1><?php require __DIR__ . '/allergy_form.php'; ?></main><?php require __DIR__ . '/../../../layouts/footer.php'; ?></div>
