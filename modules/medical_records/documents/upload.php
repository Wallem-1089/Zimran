<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
$patientId = filter_input(INPUT_GET, 'patient', FILTER_VALIDATE_INT);
$patient = $patientId ? $patientService->getPatientById((int)$patientId) : null;
if (!$patient) {
    http_response_code(404);
    exit('Patient not found.');
}
if (!$permissionService->canUploadMedicalDocuments((int)$patientId, null, $currentUser)) {
    documentAccessDenied($permissionService, $currentUser, (int)$patientId);
}
$visitId = documentVisitContext($pdo, $permissionService, $currentUser, (int)$patientId, $_GET['visit'] ?? null);
$pageTitle = 'Upload Medical Document';
$moduleStylesheet = '/modules/medical_records/assets/medical_records.css';
$documentAction = 'save.php';
$submitLabel = 'Upload Document';
require __DIR__ . '/../../../layouts/header.php';
require __DIR__ . '/../../../layouts/sidebar.php';
?>
<div class="main-container"><?php require __DIR__ . '/../../../layouts/navbar.php'; ?>
<main class="content">
    <h1>Upload Medical Document</h1>
    <p><?= e($patient['hospital_number'] . ' - ' . $patient['first_name'] . ' ' . $patient['last_name']) ?></p>
    <?php require __DIR__ . '/form.php'; ?>
</main><?php require __DIR__ . '/../../../layouts/footer.php'; ?></div>
