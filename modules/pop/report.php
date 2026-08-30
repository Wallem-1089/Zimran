<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$requestId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
if (!$requestId) {
    header('Location: index.php');
    exit;
}

if (!$POPTablesReady) {
    http_response_code(503);
    exit('POP tables are not available yet. Apply Migration 058 to enable this section.');
}

$request = $POPService->getRequestById($requestId, $currentUser);
if (!$request) {
    http_response_code(404);
    exit('POP request not found.');
}

$visit = POPRequireVisit($visitService, (int)$request['visit_id']);
$report = $POPService->getReport($requestId, $currentUser);
$hasReport = $report !== null && !empty($report['report_id']);
$isClosed = in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true);
$isRequestClosed = in_array((string)($request['status'] ?? ''), ['Completed', 'Cancelled'], true);

if (($isClosed && !$permissionService->isAdministrator($currentUser)) || $isRequestClosed) {
    header('Location: view.php?id=' . $requestId);
    exit;
}

if ($hasReport && !$permissionService->canEditPOPReport($visit, $currentUser)) {
    http_response_code(403);
    exit('You cannot edit this POP report.');
}
if (!$hasReport && !$permissionService->canUploadPOPChart($visit, $currentUser)) {
    http_response_code(403);
    exit('You cannot upload this POP chart.');
}

$patient = $patientService->getPatientById((int)$request['patient_id']);
if (!$patient) {
    http_response_code(404);
    exit('Patient not found.');
}

$action = $hasReport ? 'report_update.php' : 'report_save.php';
$buttonLabel = $hasReport ? 'Update POP Chart/Notes' : 'Save POP Chart/Notes';

$pageTitle = 'POP Chart and Notes';
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
            <h1>POP Chart and Notes</h1>
            <p><?= e((string)($request['study_requested'] ?? 'POP')) ?></p>
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

    <form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" class="card">
        <?= csrfField() ?>
        <input type="hidden" name="POP_request_id" value="<?= (int)$requestId ?>">

        <div class="form-group">
            <label for="POP_chart">Scanned POP Chart <?= $hasReport ? '(optional replacement)' : '' ?></label>
            <input id="POP_chart" name="POP_chart" type="file" accept="application/pdf,image/jpeg,image/png" <?= $hasReport ? '' : 'required' ?>>
            <small class="text-muted">Allowed: PDF, JPG, PNG. Maximum size: 10 MB.</small>
            <?php if ($hasReport && !empty($report['chart_original_name'])): ?>
                <p>Current chart: <a href="download_chart.php?id=<?= (int)$requestId ?>" target="_blank" rel="noopener"><?= e((string)$report['chart_original_name']) ?></a></p>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="notes">POP Notes</label>
            <textarea id="notes" name="notes" rows="7"><?= e((string)($report['notes'] ?? '')) ?></textarea>
        </div>

        <div class="form-group">
            <label for="remarks">Remarks</label>
            <textarea id="remarks" name="remarks" rows="5"><?= e((string)($report['remarks'] ?? '')) ?></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary"><?= e($buttonLabel) ?></button>
            <a class="btn-secondary" href="view.php?id=<?= (int)$requestId ?>">Cancel</a>
        </div>
    </form>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>

