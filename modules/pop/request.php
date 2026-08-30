<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$visitId = filter_input(INPUT_GET, 'visit', FILTER_VALIDATE_INT) ?: 0;
$requestedSource = popRequestSourceLabel((string)($_GET['source'] ?? ''));

if (!$visitId) {
    header('Location: index.php');
    exit;
}

$visit = popRequireVisit($visitService, $visitId);
$requestSource = in_array($requestedSource, ['Clinical', 'Direct'], true)
    ? $requestedSource
    : ((string)($visit['department_name'] ?? '') === 'POP' ? 'Direct' : 'Clinical');
popRequireCreateAccess($permissionService, $visit, $currentUser, $requestSource);

if (!$popTablesReady) {
    http_response_code(503);
    exit('POP tables are not available yet. Apply Migration 059 to enable this section.');
}

$patient = $patientService->getPatientById((int)$visit['patient_id']);
if (!$patient) {
    http_response_code(404);
    exit('Patient not found.');
}

$popRequest = $_SESSION['old_pop_request'] ?? [
    'request_source' => $requestSource,
    'priority' => 'Routine',
    'procedure_requested' => 'POP / Casting',
    'clinical_indication' => ''
];
unset($_SESSION['old_pop_request']);

$pageTitle = 'Create POP Request';
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
            <h1>Create POP Request</h1>
            <p><?= e((string)($visit['visit_number'] ?? ('Encounter #' . $visitId))) ?></p>
        </div>
        <div class="form-actions">
            <a class="btn-secondary" href="<?= e(popBackToWorkspace($visitId)) ?>">Workspace</a>
            <?php if ($permissionService->canViewPopWorklist($currentUser)): ?>
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
