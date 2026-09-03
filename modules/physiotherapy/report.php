<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$recordId = filter_input(INPUT_GET, 'record', FILTER_VALIDATE_INT) ?: 0;
$sessionId = filter_input(INPUT_GET, 'session', FILTER_VALIDATE_INT) ?: 0;

if (!$recordId && !$sessionId) {
    header('Location: index.php');
    exit;
}

if (!$physiotherapyTablesReady) {
    http_response_code(503);
    exit('Physiotherapy tables are not available yet. Apply Migration 028 to enable this section.');
}

if ($sessionId > 0) {
    $physiotherapySession = $physiotherapyService->getSessionById($sessionId, $currentUser);
    if (!$physiotherapySession) {
        http_response_code(404);
        exit('Physiotherapy session not found.');
    }

    $recordId = (int)$physiotherapySession['physiotherapy_record_id'];
    $action = 'report_update.php';
    $buttonLabel = 'Update Session';
    $physiotherapySession = $physiotherapySession;
} else {
    $physiotherapySession = [
        'physiotherapy_record_id' => $recordId,
        'session_date' => date('Y-m-d H:i:s'),
        'treatment_given' => '',
        'patient_response' => '',
        'progress_notes' => '',
        'next_plan' => '',
    ];
    $action = 'report_save.php';
    $buttonLabel = 'Save Session';
}

$record = $physiotherapyService->getRecordById($recordId, $currentUser);
if (!$record) {
    http_response_code(404);
    exit('Physiotherapy record not found.');
}

$visit = physiotherapyRequireVisit($visitService, (int)$record['visit_id']);
$patient = $patientService->getPatientById((int)$record['patient_id']);
if (!$patient) {
    http_response_code(404);
    exit('Patient not found.');
}

if (!$permissionService->canManagePhysiotherapySessions($visit, $currentUser)) {
    http_response_code(403);
    exit('You cannot manage physiotherapy sessions.');
}

if (in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true)
    && !$permissionService->isAdministrator($currentUser)) {
    http_response_code(403);
    exit('Completed or cancelled encounters are read-only.');
}
$enableWritingMode = $permissionService->canUseConsultationHandwriting($currentUser);

$pageTitle = 'Physiotherapy Session';
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
            <h1><?= $sessionId > 0 ? 'Edit Physiotherapy Session' : 'Add Physiotherapy Session' ?></h1>
            <p><?= e((string)($visit['visit_number'] ?? ('Encounter #' . $recordId))) ?></p>
        </div>
        <div class="form-actions">
            <a class="btn-secondary" href="view.php?id=<?= (int)$recordId ?>">Back</a>
        </div>
    </div>

    <div class="card">
        <div class="summary-grid">
            <div class="summary-item"><span class="summary-label">Patient</span> <span class="summary-value"><?= e((string)($patient['first_name'] . ' ' . $patient['last_name'])) ?></span></div>
            <div class="summary-item"><span class="summary-label">Hospital Number</span> <span class="summary-value"><?= e((string)$patient['hospital_number']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Record Source</span> <span class="summary-value"><?= e((string)$record['record_source']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Record Status</span> <span class="summary-value"><?= e((string)$record['status']) ?></span></div>
        </div>
    </div>

    <form method="post" action="<?= e($action) ?>" class="card" <?= $enableWritingMode ? 'data-hms-handwriting-form="1"' : '' ?>>
        <?= csrfField() ?>
        <input type="hidden" name="physiotherapy_record_id" value="<?= (int)$recordId ?>">
        <input type="hidden" name="session_id" value="<?= (int)($physiotherapySession['id'] ?? 0) ?>">

        <div class="form-group">
            <label for="session_date">Session Date</label>
            <input type="datetime-local" id="session_date" name="session_date" value="<?= e(date('Y-m-d\TH:i', strtotime((string)($physiotherapySession['session_date'] ?? 'now')))) ?>" required>
        </div>

        <?php hmsRenderHandwritingToolbar($enableWritingMode, 'Physiotherapy Session Entry Mode'); ?>
        <?php hmsRenderHandwritingTextarea('treatment_given', 'Treatment Given', (string)($physiotherapySession['treatment_given'] ?? ''), 5, true, $enableWritingMode); ?>
        <?php hmsRenderHandwritingTextarea('patient_response', 'Patient Response', (string)($physiotherapySession['patient_response'] ?? ''), 4, false, $enableWritingMode); ?>
        <?php hmsRenderHandwritingTextarea('progress_notes', 'Progress Notes', (string)($physiotherapySession['progress_notes'] ?? ''), 4, false, $enableWritingMode); ?>
        <?php hmsRenderHandwritingTextarea('next_plan', 'Next Plan', (string)($physiotherapySession['next_plan'] ?? ''), 4, false, $enableWritingMode); ?>

        <div class="form-actions">
            <button type="submit" class="btn-primary"><?= e($buttonLabel) ?></button>
            <a class="btn-secondary" href="view.php?id=<?= (int)$recordId ?>">Cancel</a>
        </div>
    </form>
    <?php hmsRenderHandwritingScript($enableWritingMode); ?>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
