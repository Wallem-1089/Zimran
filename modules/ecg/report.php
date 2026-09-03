<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$requestId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
if (!$requestId) {
    header('Location: index.php');
    exit;
}

if (!$ecgTablesReady) {
    http_response_code(503);
    exit('ECG tables are not available yet. Apply Migration 058 to enable this section.');
}

$request = $ecgService->getRequestById($requestId, $currentUser);
if (!$request) {
    http_response_code(404);
    exit('ECG request not found.');
}

$visit = ecgRequireVisit($visitService, (int)$request['visit_id']);
$report = $ecgService->getReport($requestId, $currentUser);
$hasReport = $report !== null && !empty($report['report_id']);
$isClosed = in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true);
$isRequestClosed = in_array((string)($request['status'] ?? ''), ['Completed', 'Cancelled'], true);
$enableWritingMode = $permissionService->canUseConsultationHandwriting($currentUser);

if (($isClosed && !$permissionService->isAdministrator($currentUser)) || $isRequestClosed) {
    header('Location: view.php?id=' . $requestId);
    exit;
}

if ($hasReport && !$permissionService->canEditEcgReport($visit, $currentUser)) {
    http_response_code(403);
    exit('You cannot edit this ECG report.');
}
if (!$hasReport && !$permissionService->canUploadEcgChart($visit, $currentUser)) {
    http_response_code(403);
    exit('You cannot upload this ECG chart.');
}

$patient = $patientService->getPatientById((int)$request['patient_id']);
if (!$patient) {
    http_response_code(404);
    exit('Patient not found.');
}

$action = $hasReport ? 'report_update.php' : 'report_save.php';
$buttonLabel = $hasReport ? 'Update ECG Chart/Notes' : 'Save ECG Chart/Notes';

$pageTitle = 'ECG Chart and Notes';
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
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert-success"><?= e((string)$_SESSION['success_message']) ?></div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <div class="page-header">
        <div>
            <h1>ECG Chart and Notes</h1>
            <p><?= e((string)($request['study_requested'] ?? 'ECG')) ?></p>
        </div>
        <div class="form-actions">
            <a class="btn-secondary" href="view.php?id=<?= (int)$requestId ?>">Back</a>
        </div>
    </div>

    <div class="card">
        <div class="summary-grid">
            <div class="summary-item"><span class="summary-label">Patient</span> <span class="summary-value"><?= e((string)($patient['first_name'] . ' ' . $patient['last_name'])) ?></span></div>
            <div class="summary-item"><span class="summary-label">Hospital Number</span> <span class="summary-value"><?= e((string)$patient['hospital_number']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Visit Number</span> <span class="summary-value"><?= e((string)($request['visit_number'] ?? ('#' . (int)$request['visit_id']))) ?></span></div>
            <div class="summary-item"><span class="summary-label">Status</span> <span class="summary-value"><?= e((string)$request['status']) ?></span></div>
        </div>
    </div>

    <form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" class="card" <?= $enableWritingMode ? 'data-hms-handwriting-form="1"' : '' ?>>
        <?= csrfField() ?>
        <input type="hidden" name="ecg_request_id" value="<?= (int)$requestId ?>">

        <div class="form-group">
            <label for="ecg_chart">Scanned ECG Chart <?= $hasReport ? '(optional replacement)' : '' ?></label>
            <input id="ecg_chart" name="ecg_chart" type="file" accept="application/pdf,image/jpeg,image/png" <?= $hasReport ? '' : 'required' ?>>
            <small class="text-muted">Allowed: PDF, JPG, PNG. Maximum size: 10 MB.</small>
            <?php if ($hasReport && !empty($report['chart_original_name'])): ?>
                <p>Current chart: <a href="download_chart.php?id=<?= (int)$requestId ?>" target="_blank" rel="noopener"><?= e((string)$report['chart_original_name']) ?></a></p>
            <?php endif; ?>
        </div>

        <?php hmsRenderHandwritingToolbar($enableWritingMode, 'ECG Notes Entry Mode'); ?>
        <?php hmsRenderHandwritingTextarea('notes', 'ECG Notes', (string)($report['notes'] ?? ''), 7, false, $enableWritingMode); ?>
        <?php hmsRenderHandwritingTextarea('remarks', 'Remarks', (string)($report['remarks'] ?? ''), 5, false, $enableWritingMode); ?>

        <div class="form-actions">
            <button type="submit" class="btn-primary"><?= e($buttonLabel) ?></button>
            <a class="btn-secondary" href="view.php?id=<?= (int)$requestId ?>">Cancel</a>
        </div>
    </form>
    <?php hmsRenderHandwritingScript($enableWritingMode); ?>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
