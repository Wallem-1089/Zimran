<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$requestId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
if (!$requestId) {
    header('Location: index.php');
    exit;
}

if (!$laboratoryTablesReady) {
    http_response_code(503);
    exit('Laboratory tables are not available yet. Apply Migration 025 to enable this section.');
}

$request = $laboratoryService->getRequestById($requestId, $currentUser);
if (!$request) {
    http_response_code(404);
    exit('Laboratory request not found.');
}

$visit = laboratoryRequireVisit($visitService, (int)$request['visit_id']);
$result = $laboratoryService->getResult($requestId, $currentUser);
$isClosed = in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true);

if ($isClosed && !$permissionService->isAdministrator($currentUser)) {
    header('Location: view.php?id=' . $requestId);
    exit;
}

if ($result === null) {
    $laboratoryResult = [
        'laboratory_request_id' => $requestId,
        'sample_taken' => '',
        'findings' => '',
        'result' => '',
        'interpretation' => '',
    ];
    $action = 'result_save.php';
    $buttonLabel = 'Save Result';
} else {
    $laboratoryResult = $result;
    $action = 'result_save.php';
    $buttonLabel = 'Update Result';
}

$patient = $patientService->getPatientById((int)$request['patient_id']);
if (!$patient) {
    http_response_code(404);
    exit('Patient not found.');
}

$pageTitle = 'Laboratory Result';
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
            <h1>Laboratory Result</h1>
            <p><?= e((string)$request['tests_requested']) ?></p>
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

    <form method="post" action="<?= e($action) ?>" class="card">
        <?= csrfField() ?>
        <input type="hidden" name="laboratory_request_id" value="<?= (int)$requestId ?>">

        <div class="form-group">
            <label for="sample_taken">Sample Taken</label>
            <textarea id="sample_taken" name="sample_taken" rows="3"><?= e((string)($laboratoryResult['sample_taken'] ?? '')) ?></textarea>
        </div>

        <div class="form-group">
            <label for="findings">Findings</label>
            <textarea id="findings" name="findings" rows="5"><?= e((string)($laboratoryResult['findings'] ?? '')) ?></textarea>
        </div>

        <div class="form-group">
            <label for="result">Result</label>
            <textarea id="result" name="result" rows="8" required><?= e((string)($laboratoryResult['result'] ?? '')) ?></textarea>
        </div>

        <div class="form-group">
            <label for="interpretation">Interpretation</label>
            <textarea id="interpretation" name="interpretation" rows="4"><?= e((string)($laboratoryResult['interpretation'] ?? '')) ?></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary"><?= e($buttonLabel) ?></button>
            <a class="btn-secondary" href="view.php?id=<?= (int)$requestId ?>">Cancel</a>
        </div>
    </form>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
