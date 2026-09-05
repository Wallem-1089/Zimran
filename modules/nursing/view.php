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
$canEdit = $permissionService->canEditNursing($visit, $currentUser);
$canComplete = $permissionService->canCompleteNursing($visit, $currentUser)
    && (string)($assessment['status'] ?? '') === 'Draft';
$latestVitalSigns = $vitalSignsService ? $vitalSignsService->getLatestByVisit((int)$visit['id'], $currentUser) : null;
$clinicalSafetySummary = nursingSafetySummary($clinicalSafetyService, (int)$visit['patient_id'], $currentUser, (int)$visit['id']);
$nursingConfiguredDisplayValues = $configurableFormService->getResponseValues('nursing_assessment', 'Nursing Assessment', (int)$assessment['id']);

$pageTitle = 'Nursing Assessment';
$moduleStylesheet = '/modules/visits/assets/visits.css';

require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert-success"><?= e((string)$_SESSION['success_message']) ?></div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    <div class="page-header">
        <div>
            <h1>Nursing Assessment</h1>
            <p><?= e((string)($visit['visit_number'] ?? ('Encounter #' . (int)$visit['id']))) ?> | <?= e((string)($assessment['status'] ?? 'Draft')) ?></p>
        </div>
        <div>
            <a class="btn-secondary" href="<?= e(nursingBackToWorkspace((int)$visit['id'])) ?>">Workspace</a>
            <a class="btn-secondary" href="<?= e(nursingBackToChart((int)$visit['patient_id'])) ?>">Patient Chart</a>
            <?php if (!in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true) && $permissionService->canCreateBillingRequest($currentUser)): ?>
                <a class="btn-secondary" href="../billing/request_create.php?visit=<?= (int)$visit['id'] ?>&source_module=Nursing&source_record_id=<?= (int)$assessment['id'] ?>&description=<?= urlencode('Nursing: assessment/support services for encounter ' . (string)($visit['visit_number'] ?? ('#' . (int)$visit['id']))) ?>">Request Billing</a>
            <?php endif; ?>
            <?php if ($canEdit && (string)$assessment['status'] === 'Draft'): ?>
                <a class="btn-primary" href="edit.php?id=<?= (int)$assessment['id'] ?>">Edit</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="summary-grid">
            <div class="summary-item"><span class="summary-label">Nurse</span> <span class="summary-value"><?= e((string)($assessment['nurse_name'] ?? 'Unknown')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Recorded By</span> <span class="summary-value"><?= e((string)($assessment['created_by_name'] ?? 'Unknown')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Recorded At</span> <span class="summary-value"><?= e((string)($assessment['created_at'] ?? 'Unknown')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Completed At</span> <span class="summary-value"><?= e((string)($assessment['completed_at'] ?? 'Not completed')) ?></span></div>
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
        <?php $latest = $assessment; require __DIR__ . '/partials/record_card.php'; ?>
    </div>

    <?php if ($canComplete): ?>
        <form method="post" action="complete.php" class="card">
            <?= csrfField() ?>
            <input type="hidden" name="id" value="<?= (int)$assessment['id'] ?>">
            <div class="form-actions">
                <button type="submit" class="btn-primary">Complete Assessment</button>
            </div>
        </form>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
