<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$visitId = filter_input(INPUT_GET, 'visit', FILTER_VALIDATE_INT) ?: 0;
$requestedSource = radiologyRequestSourceLabel((string)($_GET['source'] ?? ''));

if (!$visitId) {
    header('Location: index.php');
    exit;
}

$visit = radiologyRequireVisit($visitService, $visitId);
$isDirectRadiologyEncounter = in_array((string)($visit['department_name'] ?? ''), ['Radiology', 'X-Ray'], true);
$requestSourceNote = '';
if ($requestedSource === 'Direct' && !$isDirectRadiologyEncounter) {
    $requestSource = 'Clinical';
    $requestSourceNote = 'Direct is only for active encounters currently in Radiology/X-Ray. This request is being recorded as Clinical.';
} elseif (in_array($requestedSource, ['Clinical', 'Direct'], true)) {
    $requestSource = $requestedSource;
} else {
    $requestSource = $isDirectRadiologyEncounter ? 'Direct' : 'Clinical';
}
radiologyRequireAccess($permissionService, $visit, $currentUser, $requestSource);

if (!$radiologyTablesReady) {
    http_response_code(503);
    exit('Radiology tables are not available yet. Apply Migration 027 to enable this section.');
}

$patient = $patientService->getPatientById((int)$visit['patient_id']);
if (!$patient) {
    http_response_code(404);
    exit('Patient not found.');
}

$radiologyRequest = $_SESSION['old_radiology_request'] ?? [
    'request_source' => $requestSource,
    'priority' => 'Routine',
    'study_requested' => '',
    'clinical_indication' => ''
];
$radiologyRequest['request_source'] = $requestSource;
unset($_SESSION['old_radiology_request']);

$existingRequests = $radiologyService->listByVisit($visitId, $currentUser);
$pageTitle = 'Create Radiology Request';
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
            <h1>Create Radiology Request</h1>
            <p><?= e((string)($visit['visit_number'] ?? ('Encounter #' . $visitId))) ?></p>
        </div>
        <div class="form-actions">
            <a class="btn-secondary" href="<?= e(radiologyBackToWorkspace($visitId)) ?>">Workspace</a>
            <?php if ($permissionService->canViewRadiologyWorklist($currentUser)): ?>
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

    <div class="card">
        <h3>Existing Requests for This Encounter</h3>
        <?php if ($existingRequests === []): ?>
            <p class="text-muted">No radiology requests recorded for this encounter.</p>
        <?php else: ?>
            <ul class="clean-list">
                <?php foreach ($existingRequests as $request): ?>
                    <li>
                        <a href="view.php?id=<?= (int)$request['id'] ?>">#<?= (int)$request['id'] ?></a>
                        — <?= e((string)($request['study_requested'] ?? $request['tests_requested'] ?? '')) ?>
                        (<?= e((string)$request['status']) ?>)
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>

