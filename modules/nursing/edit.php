<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$assessmentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$assessment = $nursingService->getById($assessmentId, $currentUser);
if (!$assessment) {
    http_response_code(404);
    exit('Nursing assessment not found.');
}

$visit = nursingRequireVisit($visitService, (int)$assessment['visit_id']);
nursingRequireAccess($permissionService, $visit, $currentUser);

if (!$permissionService->canEditNursing($visit, $currentUser) || (string)$assessment['status'] !== 'Draft') {
    http_response_code(403);
    exit('This nursing assessment cannot be edited.');
}

$pageTitle = 'Edit Nursing Assessment';
$moduleStylesheet = '/modules/visits/assets/visits.css';
$nursingAssessment = $assessment;
$latestVitalSigns = $vitalSignsService ? $vitalSignsService->getLatestByVisit((int)$visit['id'], $currentUser) : null;
$clinicalSafetySummary = nursingSafetySummary($clinicalSafetyService, (int)$visit['patient_id'], $currentUser, (int)$visit['id']);
$problemSummary = $problemListService->getProblemSummary((int)$visit['patient_id'], $currentUser);
$medicalHistorySummary = $problemListService->getMedicalHistorySummary((int)$visit['patient_id'], $currentUser);

require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div>
            <h1>Edit Nursing Assessment</h1>
            <p><?= e((string)($visit['visit_number'] ?? ('Encounter #' . (int)$visit['id']))) ?></p>
        </div>
        <div>
            <a class="btn-secondary" href="<?= e(nursingBackToWorkspace((int)$visit['id'])) ?>">Workspace</a>
            <a class="btn-secondary" href="<?= e(nursingBackToChart((int)$visit['patient_id'])) ?>">Patient Chart</a>
        </div>
    </div>

    <?php if ($latestVitalSigns !== null): ?>
        <div class="card">
            <h3>Latest Vital Signs</h3>
            <?php $latest = $latestVitalSigns; require __DIR__ . '/../vital_signs/partials/record_card.php'; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="section-heading">
            <h3>Clinical Safety</h3>
            <a class="btn-secondary" href="<?= e(nursingSafetyLink((int)$visit['patient_id'], (int)$visit['id'])) ?>">View Safety</a>
        </div>
        <?php if (($clinicalSafetySummary['success'] ?? false) && !empty($clinicalSafetySummary['items'])): ?>
            <ul>
                <?php foreach (array_slice($clinicalSafetySummary['items'], 0, 3) as $item): ?>
                    <li><strong><?= e((string)($item['title'] ?? 'Safety item')) ?><?= !empty($item['detail']) ? ':' : '' ?></strong> <?= !empty($item['detail']) ? e((string)$item['detail']) : '' ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="text-muted">No clinical safety alerts or allergies recorded.</p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>Clinical Context</h3>
        <div class="summary-grid">
            <div class="summary-item"><span class="summary-label">Problem List</span> <span class="summary-value"><?= e((string)($problemSummary['summary'] ?? 'No active problems recorded.')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Medical History</span> <span class="summary-value"><?= e((string)($medicalHistorySummary['summary'] ?? 'No structured medical history available.')) ?></span></div>
        </div>
    </div>

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
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert-danger"><?= nl2br(e((string)$_SESSION['error_message'])) ?></div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <?php $action = 'update.php'; $buttonLabel = 'Update Nursing Assessment'; require __DIR__ . '/_form.php'; ?>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
