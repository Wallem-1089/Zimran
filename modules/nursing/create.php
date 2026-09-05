<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$visitId = filter_input(INPUT_GET, 'visit', FILTER_VALIDATE_INT) ?: 0;
$visit = nursingRequireVisit($visitService, $visitId);
nursingRequireAccess($permissionService, $visit, $currentUser);

if (!$permissionService->canCreateNursing($visit, $currentUser)) {
    http_response_code(403);
    exit('Nursing creation is denied.');
}

$existingAssessment = $nursingService->getByVisit($visitId, $currentUser);
if ($existingAssessment) {
    header('Location: view.php?id=' . (int)$existingAssessment['id']);
    exit;
}

$pageTitle = 'Start Nursing Assessment';
$moduleStylesheet = '/modules/visits/assets/visits.css';
$nursingAssessment = $_SESSION['old_nursing_assessment'] ?? ['visit_id' => $visitId, 'patient_id' => (int)$visit['patient_id']];
unset($_SESSION['old_nursing_assessment']);
$nursingConfiguredFields = $configurableFormService->listFields('nursing_assessment', true);
$nursingConfiguredValues = $_SESSION['old_configured_fields'] ?? [];
unset($_SESSION['old_configured_fields']);
$latestVitalSigns = $vitalSignsService ? $vitalSignsService->getLatestByVisit($visitId, $currentUser) : null;
$clinicalSafetySummary = nursingSafetySummary($clinicalSafetyService, (int)$visit['patient_id'], $currentUser, $visitId);
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
            <h1>Start Nursing Assessment</h1>
            <p><?= e((string)($visit['visit_number'] ?? ('Encounter #' . $visitId))) ?></p>
        </div>
        <div>
            <a class="btn-secondary" href="<?= e(nursingBackToWorkspace((int)$visit['id'])) ?>">Workspace</a>
            <a class="btn-secondary" href="<?= e(nursingBackToChart((int)$visit['patient_id'])) ?>">Patient Chart</a>
        </div>
    </div>

    <div class="card">
        <div class="summary-grid">
            <div class="summary-item"><span class="summary-label">Patient</span> <span class="summary-value"><?= e((string)$visit['first_name']) ?> <?= e((string)$visit['last_name']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Hospital Number</span> <span class="summary-value"><?= e((string)$visit['hospital_number']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Department</span> <span class="summary-value"><?= e((string)($visit['department_name'] ?? '')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Encounter Status</span> <span class="summary-value"><?= e((string)($visit['visit_status'] ?? 'Unknown')) ?></span></div>
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

    <?php $action = 'save.php'; $buttonLabel = 'Save Nursing Assessment'; require __DIR__ . '/_form.php'; ?>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
