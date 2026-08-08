<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
$document = documentForUser($medicalDocumentService, (int)($_GET['id'] ?? 0), $currentUser);
if (!$permissionService->canReplaceMedicalDocuments((int)$document['patient_id'], $currentUser)) {
    documentAccessDenied($permissionService, $currentUser, (int)$document['patient_id']);
}
$patient = $patientService->getPatientById((int)$document['patient_id']);
$visitId = documentVisitContext($pdo, $permissionService, $currentUser, (int)$document['patient_id'], $_GET['visit'] ?? $document['visit_id'] ?? null);
$documentAction = 'replace_save.php';
$submitLabel = 'Upload Replacement';
$pageTitle = 'Replace Medical Document';
$moduleStylesheet = '/modules/medical_records/assets/medical_records.css';
require __DIR__ . '/../../../layouts/header.php';
require __DIR__ . '/../../../layouts/sidebar.php';
?>
<div class="main-container"><?php require __DIR__ . '/../../../layouts/navbar.php'; ?><main class="content"><h1>Replace <?= e($document['title']) ?></h1><?php require __DIR__ . '/form.php'; ?></main><?php require __DIR__ . '/../../../layouts/footer.php'; ?></div>
