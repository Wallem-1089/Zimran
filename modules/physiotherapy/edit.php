<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$recordId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
if (!$recordId) {
    header('Location: index.php');
    exit;
}

if (!$physiotherapyTablesReady) {
    http_response_code(503);
    exit('Physiotherapy tables are not available yet. Apply Migration 028 to enable this section.');
}

$physiotherapyRecord = $physiotherapyService->getRecordById($recordId, $currentUser);
if (!$physiotherapyRecord) {
    http_response_code(404);
    exit('Physiotherapy record not found.');
}

$visit = physiotherapyRequireVisit($visitService, (int)$physiotherapyRecord['visit_id']);
$patient = $patientService->getPatientById((int)$physiotherapyRecord['patient_id']);
if (!$patient) {
    http_response_code(404);
    exit('Patient not found.');
}

if (!$permissionService->canEditPhysiotherapy($visit, $currentUser)) {
    http_response_code(403);
    exit('You cannot edit this physiotherapy record.');
}

if (in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true)
    && !$permissionService->isAdministrator($currentUser)) {
    http_response_code(403);
    exit('Completed or cancelled encounters are read-only.');
}

$physiotherapyConfiguredFields = $configurableFormService->listFields('physiotherapy_record', true);
$physiotherapyConfiguredValues = $configurableFormService->getResponseValueMap('physiotherapy_record', 'Physiotherapy Record', $recordId);
if (isset($_SESSION['old_configured_fields']) && is_array($_SESSION['old_configured_fields'])) {
    $physiotherapyConfiguredValues = $_SESSION['old_configured_fields'];
    unset($_SESSION['old_configured_fields']);
}

$pageTitle = 'Edit Physiotherapy Record';
$moduleStylesheet = '/modules/visits/assets/visits.css';

require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div>
            <h1>Edit Physiotherapy Record</h1>
            <p><?= e((string)$visit['visit_number']) ?></p>
        </div>
        <div class="form-actions">
            <a class="btn-secondary" href="view.php?id=<?= (int)$recordId ?>">Back</a>
        </div>
    </div>

    <?php require __DIR__ . '/_form.php'; ?>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
