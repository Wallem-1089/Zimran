<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$requestId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
if (!$requestId) {
    header('Location: index.php');
    exit;
}

if (!$radiologyTablesReady) {
    http_response_code(503);
    exit('Radiology tables are not available yet. Apply Migration 027 to enable this section.');
}

$request = $radiologyService->getRequestById($requestId, $currentUser);
if (!$request) {
    http_response_code(404);
    exit('Radiology request not found.');
}

$visit = radiologyRequireVisit($visitService, (int)$request['visit_id']);
$result = $radiologyService->getResult($requestId, $currentUser);
$isClosed = in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true);
$enableWritingMode = $permissionService->canUseConsultationHandwriting($currentUser);

if ($isClosed && !$permissionService->isAdministrator($currentUser)) {
    header('Location: view.php?id=' . $requestId);
    exit;
}

if ($result === null) {
    $radiologyResult = [
        'radiology_request_id' => $requestId,
        'findings' => '',
        'impression' => '',
        'recommendation' => '',
    ];
    $action = 'report_save.php';
    $buttonLabel = 'Save Report';
} else {
    $radiologyResult = $result;
    $action = 'report_update.php';
    $buttonLabel = 'Update Report';
}

$patient = $patientService->getPatientById((int)$request['patient_id']);
if (!$patient) {
    http_response_code(404);
    exit('Patient not found.');
}

$pageTitle = 'Radiology Report';
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
            <h1>Radiology Report</h1>
            <p><?= e((string)($request['study_requested'] ?? $request['tests_requested'] ?? '')) ?></p>
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
        <input type="hidden" name="radiology_request_id" value="<?= (int)$requestId ?>">

        <div class="form-group">
            <label for="radiology_chart">Scanned X-Ray/Radiology Document <?= !empty($radiologyResult['chart_original_name']) ? '(optional replacement)' : '' ?></label>
            <input id="radiology_chart" name="radiology_chart" type="file" accept="application/pdf,image/jpeg,image/png">
            <small class="text-muted">Allowed: PDF, JPG, PNG. Maximum size: 10 MB.</small>
            <?php if (!empty($radiologyResult['chart_original_name'])): ?>
                <p>Current document: <a href="download_chart.php?id=<?= (int)$requestId ?>" target="_blank" rel="noopener"><?= e((string)$radiologyResult['chart_original_name']) ?></a></p>
            <?php endif; ?>
        </div>

        <?php hmsRenderHandwritingToolbar($enableWritingMode, 'Radiology Report Entry Mode'); ?>
        <?php hmsRenderHandwritingTextarea('findings', 'Findings', (string)($radiologyResult['findings'] ?? ''), 5, false, $enableWritingMode); ?>
        <?php hmsRenderHandwritingTextarea('impression', 'Impression', (string)($radiologyResult['impression'] ?? ''), 6, true, $enableWritingMode); ?>
        <?php hmsRenderHandwritingTextarea('recommendation', 'Recommendation', (string)($radiologyResult['recommendation'] ?? ''), 4, false, $enableWritingMode); ?>

        <div class="form-actions">
            <button type="submit" class="btn-primary"><?= e($buttonLabel) ?></button>
            <a class="btn-secondary" href="view.php?id=<?= (int)$requestId ?>">Cancel</a>
        </div>
    </form>
    <?php hmsRenderHandwritingScript($enableWritingMode); ?>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>

