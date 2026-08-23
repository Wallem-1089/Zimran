<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$visitId = filter_input(INPUT_GET, 'visit', FILTER_VALIDATE_INT) ?: 0;
$requestedSource = physiotherapyRequestSourceLabel((string)($_GET['source'] ?? ''));

if (!$visitId) {
    header('Location: index.php');
    exit;
}

$visit = physiotherapyRequireVisit($visitService, $visitId);
$recordSource = in_array($requestedSource, ['Clinical', 'Direct'], true)
    ? $requestedSource
    : (in_array((string)($visit['department_name'] ?? ''), ['Physiotherapy', 'Physio', 'Rehabilitation'], true) ? 'Direct' : 'Clinical');
physiotherapyRequireAccess($permissionService, $visit, $currentUser, $recordSource);

if (!$physiotherapyTablesReady) {
    http_response_code(503);
    exit('Physiotherapy tables are not available yet. Apply Migration 028 to enable this section.');
}

$patient = $patientService->getPatientById((int)$visit['patient_id']);
if (!$patient) {
    http_response_code(404);
    exit('Patient not found.');
}

$physiotherapyRecord = $_SESSION['old_physiotherapy_request'] ?? [
    'record_source' => $recordSource,
    'referral_reason' => '',
    'presenting_problem' => '',
    'assessment' => '',
    'functional_limitations' => '',
    'treatment_plan' => '',
    'goals' => '',
    'precautions' => '',
];
unset($_SESSION['old_physiotherapy_request']);

$existingRecords = $physiotherapyService->listByVisit($visitId, $currentUser);
$pageTitle = 'Create Physiotherapy Record';
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
            <h1>Create Physiotherapy Record</h1>
            <p><?= e((string)($visit['visit_number'] ?? ('Encounter #' . $visitId))) ?></p>
        </div>
        <div class="form-actions">
            <a class="btn-secondary" href="<?= e(physiotherapyBackToWorkspace($visitId)) ?>">Workspace</a>
            <?php if ($permissionService->canViewPhysiotherapyWorklist($currentUser)): ?>
                <a class="btn-secondary" href="index.php">Worklist</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="summary-grid">
        <div class="summary-item"><span class="summary-label">Patient</span> <span class="summary-value"><?= e((string)($patient['first_name'] . ' ' . $patient['last_name'])) ?></span></div>
        <div class="summary-item"><span class="summary-label">Hospital Number</span> <span class="summary-value"><?= e((string)$patient['hospital_number']) ?></span></div>
        <div class="summary-item"><span class="summary-label">Record Source</span> <span class="summary-value"><?= e($recordSource) ?></span></div>
        <div class="summary-item"><span class="summary-label">Encounter Status</span> <span class="summary-value"><?= e((string)($visit['visit_status'] ?? '-')) ?></span></div>
    </div>

    <?php require __DIR__ . '/_form.php'; ?>

    <div class="card">
        <h3>Existing Records for This Encounter</h3>
        <?php if ($existingRecords === []): ?>
            <p class="text-muted">No physiotherapy records recorded for this encounter.</p>
        <?php else: ?>
            <ul class="clean-list">
                <?php foreach ($existingRecords as $record): ?>
                    <li>
                        <a href="view.php?id=<?= (int)$record['id'] ?>">#<?= (int)$record['id'] ?></a>
                        — <?= e((string)($record['summary'] ?? $record['presenting_problem'] ?? '')) ?>
                        (<?= e((string)$record['status']) ?>)
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
