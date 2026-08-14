<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

requireCsrfToken();

$visitId = (int)($_POST['visit_id'] ?? 0);
$visit = consultationRequireVisit($visitService, $visitId);

if (!$permissionService->canCreateConsultation($visit, $currentUser)) {
    http_response_code(403);
    exit('Consultation creation is denied.');
}

$fields = [
    'presenting_complaint' => 'Presenting Complaint',
    'history_of_presenting_complaint' => 'History of Presenting Complaint',
    'examination_findings' => 'Examination Findings',
    'assessment' => 'Assessment',
    'diagnosis' => 'Diagnosis',
    'treatment_plan' => 'Treatment Plan',
    'advice' => 'Advice',
    'follow_up' => 'Follow Up',
    'referral_notes' => 'Referral Notes',
];
$latestVitalSigns = $vitalSignsService
    ? $vitalSignsService->getLatestByVisit($visitId, $currentUser)
    : null;

$pageTitle = 'Review Consultation';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div>
            <h1>Review Consultation</h1>
            <p><?= e($visit['visit_number'] ?? ('Encounter #' . $visitId)) ?></p>
        </div>
    </div>

    <div class="card">
        <div class="summary-grid">
            <div class="summary-item"><span class="summary-label">Patient</span> <span class="summary-value"><?= e((string)$visit['first_name']) ?> <?= e((string)$visit['last_name']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Hospital Number</span> <span class="summary-value"><?= e((string)$visit['hospital_number']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Department</span> <span class="summary-value"><?= e((string)($visit['department_name'] ?? '')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Clinical Doctor</span> <span class="summary-value"><?= e((string)($visit['doctor_name'] ?? 'Not Assigned')) ?></span></div>
        </div>
    </div>

    <?php if ($latestVitalSigns !== null): ?>
        <div class="card">
            <h3>Latest Vital Signs</h3>
            <?php $latest = $latestVitalSigns; require __DIR__ . '/../vital_signs/partials/record_card.php'; ?>
        </div>
    <?php else: ?>
        <div class="card"><h3>Latest Vital Signs</h3><p>No vital signs recorded.</p></div>
    <?php endif; ?>

    <?php foreach ($fields as $field => $label): ?>
        <div class="card">
            <h3><?= e($label) ?></h3>
            <?php consultationRenderNarrative((string)($_POST[$field] ?? '')); ?>
        </div>
    <?php endforeach; ?>

    <form method="post" action="save.php" class="card">
        <?= csrfField() ?>
        <input type="hidden" name="review_confirmed" value="1">
        <?php foreach (array_merge(['visit_id'], array_keys($fields)) as $field): ?>
            <input type="hidden" name="<?= e($field) ?>" value="<?= e((string)($_POST[$field] ?? '')) ?>">
        <?php endforeach; ?>
        <div class="form-actions">
            <button type="submit" class="btn-primary">Confirm & Save Draft</button>
            <a class="btn-secondary" href="create.php?visit=<?= (int)$visitId ?>">Back & Edit</a>
        </div>
    </form>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
