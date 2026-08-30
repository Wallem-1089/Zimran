<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$visitId = filter_input(INPUT_GET, 'visit', FILTER_VALIDATE_INT) ?: 0;
$requestedSource = ecgRequestSourceLabel((string)($_GET['source'] ?? ''));

if (!$visitId) {
    header('Location: index.php');
    exit;
}

$visit = ecgRequireVisit($visitService, $visitId);
$requestSource = in_array($requestedSource, ['Clinical', 'Direct'], true)
    ? $requestedSource
    : ((string)($visit['department_name'] ?? '') === 'ECG' ? 'Direct' : 'Clinical');
ecgRequireCreateAccess($permissionService, $visit, $currentUser, $requestSource);

if (!$ecgTablesReady) {
    http_response_code(503);
    exit('ECG tables are not available yet. Apply Migration 058 to enable this section.');
}

$patient = $patientService->getPatientById((int)$visit['patient_id']);
if (!$patient) {
    http_response_code(404);
    exit('Patient not found.');
}

$ecgRequest = $_SESSION['old_ecg_request'] ?? [
    'request_source' => $requestSource,
    'priority' => 'Routine',
    'study_requested' => 'ECG',
    'clinical_indication' => ''
];
unset($_SESSION['old_ecg_request']);

$pageTitle = 'Create ECG Request';
$moduleStylesheet = '/modules/visits/assets/visits.css';

require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <?php if (isset($_SESSION['validation_errors'])): ?>
        <div class="alert-danger">
            <strong>Please correct the following:</strong>
            <ul>
                <?php foreach ((array)$_SESSION['validation_errors'] as $error): ?>
                    <li><?= e((string)$error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php unset($_SESSION['validation_errors']); ?>
    <?php endif; ?>

    <div class="page-header">
        <div>
            <h1>Create ECG Request</h1>
            <p><?= e((string)($visit['visit_number'] ?? ('Encounter #' . $visitId))) ?></p>
        </div>
        <div class="form-actions">
            <a class="btn-secondary" href="<?= e(ecgBackToWorkspace($visitId)) ?>">Workspace</a>
            <?php if ($permissionService->canViewEcgWorklist($currentUser)): ?>
                <a class="btn-secondary" href="index.php">Worklist</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="summary-grid">
        <div class="summary-item"><span class="summary-label">Patient</span> <span class="summary-value"><?= e((string)($patient['first_name'] . ' ' . $patient['last_name'])) ?></span></div>
        <div class="summary-item"><span class="summary-label">Hospital Number</span> <span class="summary-value"><?= e((string)$patient['hospital_number']) ?></span></div>
        <div class="summary-item"><span class="summary-label">Request Source</span> <span class="summary-value"><?= e($requestSource) ?></span></div>
        <div class="summary-item"><span class="summary-label">Encounter Status</span> <span class="summary-value"><?= e((string)($visit['visit_status'] ?? '-')) ?></span></div>
    </div>

    <?php require __DIR__ . '/_form.php'; ?>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>

