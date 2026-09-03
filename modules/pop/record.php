<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$requestId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
if (!$requestId) {
    header('Location: index.php');
    exit;
}
if (!$popTablesReady) {
    http_response_code(503);
    exit('POP tables are not available yet. Apply Migration 059 to enable this section.');
}

$request = $popService->getRequestById($requestId, $currentUser);
if (!$request) {
    http_response_code(404);
    exit('POP request not found.');
}
$visit = popRequireVisit($visitService, (int)$request['visit_id']);
$record = $popService->getRecord($requestId, $currentUser);
$hasRecord = $record !== null && !empty($record['record_id']);
$isClosed = in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true);
$isRequestClosed = in_array((string)($request['status'] ?? ''), ['Completed', 'Cancelled'], true);
$enableWritingMode = $permissionService->canUseConsultationHandwriting($currentUser);

if (($isClosed && !$permissionService->isAdministrator($currentUser)) || $isRequestClosed) {
    header('Location: view.php?id=' . $requestId);
    exit;
}
if ($hasRecord && !$permissionService->canEditPopRecord($visit, $currentUser)) {
    http_response_code(403);
    exit('You cannot edit this POP record.');
}
if (!$hasRecord && !$permissionService->canRecordPopProcedure($visit, $currentUser)) {
    http_response_code(403);
    exit('You cannot record this POP procedure.');
}

$patient = $patientService->getPatientById((int)$request['patient_id']);
if (!$patient) {
    http_response_code(404);
    exit('Patient not found.');
}

$action = $hasRecord ? 'record_update.php' : 'record_save.php';
$buttonLabel = $hasRecord ? 'Update POP Record' : 'Save POP Record';

$pageTitle = 'POP Procedure Record';
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
            <ul><?php foreach ((array)$_SESSION['validation_errors'] as $error): ?><li><?= e((string)$error) ?></li><?php endforeach; ?></ul>
        </div>
        <?php unset($_SESSION['validation_errors']); ?>
    <?php endif; ?>

    <div class="page-header">
        <div>
            <h1>POP Procedure Record</h1>
            <p><?= e((string)($request['procedure_requested'] ?? 'POP / Casting')) ?></p>
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

    <form method="post" action="<?= e($action) ?>" class="card" <?= $enableWritingMode ? 'data-hms-handwriting-form="1"' : '' ?>>
        <?= csrfField() ?>
        <input type="hidden" name="pop_request_id" value="<?= (int)$requestId ?>">

        <div class="form-grid">
            <div class="form-group">
                <label for="cast_type">Cast Type</label>
                <input id="cast_type" name="cast_type" type="text" maxlength="255" value="<?= e((string)($record['cast_type'] ?? '')) ?>">
            </div>
            <div class="form-group">
                <label for="body_part">Body Part</label>
                <input id="body_part" name="body_part" type="text" maxlength="255" value="<?= e((string)($record['body_part'] ?? '')) ?>">
            </div>
        </div>

        <?php hmsRenderHandwritingToolbar($enableWritingMode, 'POP Procedure Entry Mode'); ?>
        <?php hmsRenderHandwritingTextarea('procedure_notes', 'Procedure Notes', (string)($record['procedure_notes'] ?? ''), 7, true, $enableWritingMode); ?>
        <?php hmsRenderHandwritingTextarea('materials_used', 'Materials Used', (string)($record['materials_used'] ?? ''), 4, false, $enableWritingMode); ?>
        <?php hmsRenderHandwritingTextarea('aftercare_instructions', 'Aftercare Instructions', (string)($record['aftercare_instructions'] ?? ''), 4, false, $enableWritingMode); ?>
        <?php hmsRenderHandwritingTextarea('remarks', 'Remarks', (string)($record['remarks'] ?? ''), 4, false, $enableWritingMode); ?>

        <div class="form-actions">
            <button type="submit" class="btn-primary"><?= e($buttonLabel) ?></button>
            <a class="btn-secondary" href="view.php?id=<?= (int)$requestId ?>">Cancel</a>
        </div>
    </form>
    <?php hmsRenderHandwritingScript($enableWritingMode); ?>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
