<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$visitId = filter_input(INPUT_GET, 'visit', FILTER_VALIDATE_INT) ?: 0;
$visit = nursingRequireVisit($visitService, $visitId);
nursingRequireAccess($permissionService, $visit, $currentUser);

$assessment = $nursingService->getByVisit($visitId, $currentUser);

if ($assessment) {
    header('Location: view.php?id=' . (int)$assessment['id']);
    exit;
}

$canCreate = $permissionService->canCreateNursing($visit, $currentUser);
$isClosed = in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true);

if ($canCreate) {
    header('Location: create.php?visit=' . $visitId);
    exit;
}

$latestVitalSigns = $vitalSignsService ? $vitalSignsService->getLatestByVisit($visitId, $currentUser) : null;
$clinicalSafetySummary = nursingSafetySummary($clinicalSafetyService, (int)$visit['patient_id'], $currentUser, $visitId);
$problemSummary = $problemListService->getProblemSummary((int)$visit['patient_id'], $currentUser);
$medicalHistorySummary = $problemListService->getMedicalHistorySummary((int)$visit['patient_id'], $currentUser);

$pageTitle = 'Nursing Assessment';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div>
            <h1>Nursing Assessment</h1>
            <p><?= e((string)($visit['visit_number'] ?? ('Encounter #' . $visitId))) ?></p>
        </div>
        <div>
            <a class="btn-secondary" href="<?= e(nursingBackToWorkspace((int)$visit['id'])) ?>">Workspace</a>
            <a class="btn-secondary" href="<?= e(nursingBackToChart((int)$visit['patient_id'])) ?>">Patient Chart</a>
        </div>
    </div>

    <div class="card">
        <div class="summary-grid">
            <div class="summary-item"><span class="summary-label">Patient</span><span class="summary-value"><?= e((string)$visit['first_name']) ?> <?= e((string)$visit['last_name']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Hospital Number</span><span class="summary-value"><?= e((string)$visit['hospital_number']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Department</span><span class="summary-value"><?= e((string)($visit['department_name'] ?? '')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Encounter Status</span><span class="summary-value"><?= e((string)($visit['visit_status'] ?? 'Unknown')) ?></span></div>
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

    <div class="card">
        <div class="section-heading">
            <h3>Clinical Safety</h3>
            <a class="btn-secondary" href="<?= e(nursingSafetyLink((int)$visit['patient_id'], (int)$visit['id'])) ?>">View Safety</a>
        </div>
        <?php if (($clinicalSafetySummary['success'] ?? false) && !empty($clinicalSafetySummary['items'])): ?>
            <ul>
                <?php foreach (array_slice($clinicalSafetySummary['items'], 0, 3) as $item): ?>
                    <li><strong><?= e((string)($item['title'] ?? 'Safety item')) ?></strong><?= !empty($item['detail']) ? ': ' . e((string)$item['detail']) : '' ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="text-muted">No clinical safety alerts or allergies recorded.</p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>Clinical Context</h3>
        <div class="summary-grid">
            <div class="summary-item"><span class="summary-label">Problem List</span><span class="summary-value"><?= e((string)($problemSummary['summary'] ?? 'No active problems recorded.')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Medical History</span><span class="summary-value"><?= e((string)($medicalHistorySummary['summary'] ?? 'No structured medical history available.')) ?></span></div>
        </div>
    </div>

    <div class="card">
        <p class="text-muted">No nursing assessment recorded.</p>
        <?php if ($canCreate && !$isClosed): ?>
            <p><a class="btn-primary" href="create.php?visit=<?= (int)$visit['id'] ?>">Start Nursing Assessment</a></p>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
