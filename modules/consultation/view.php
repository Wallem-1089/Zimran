<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$consultationId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$consultation = $consultationService->getById($consultationId);
if (!$consultation) {
    http_response_code(404);
    exit('Consultation not found.');
}

$visit = consultationRequireVisit($visitService, (int)$consultation['visit_id']);
consultationRequireAccess($permissionService, $visit, $currentUser);

$canEdit = (string)$consultation['status'] === 'Draft'
    && $permissionService->canEditConsultation($visit, $currentUser);
$canComplete = (string)$consultation['status'] === 'Draft'
    && $permissionService->canCompleteConsultation($visit, $currentUser);
$latestVitalSigns = $vitalSignsService
    ? $vitalSignsService->getLatestByVisit((int)$visit['id'], $currentUser)
    : null;
$latestLaboratoryRequests = $laboratoryService
    ? $laboratoryService->listByVisit((int)$visit['id'], $currentUser)
    : [];

$pageTitle = 'Consultation';
$moduleStylesheet = '/modules/visits/assets/visits.css';
$fields = [
    'presenting_complaint' => 'Presenting Complaint',
    'history_of_presenting_complaint' => 'History of Presenting Complaint',
    'examination_findings' => 'Examination Findings',
    'assessment' => 'Assessment',
    'diagnosis' => 'Diagnosis',
    'treatment_plan' => 'Treatment Plan',
    'advice' => 'Advice',
    'follow_up' => 'Follow Up',
    'referral_notes' => 'Referral Notes'
];
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert-success"><?= e($_SESSION['success_message']) ?></div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['validation_errors'])): ?>
        <div class="alert-danger"><?= e(implode(' ', (array)$_SESSION['validation_errors'])) ?></div>
        <?php unset($_SESSION['validation_errors']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert-danger"><?= nl2br(e((string)$_SESSION['error_message'])) ?></div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <div class="page-header">
        <div>
            <h1>Consultation</h1>
            <p><?= e((string)$consultation['visit_number']) ?> | <?= e((string)$consultation['status']) ?></p>
        </div>
        <div>
            <a class="btn-secondary" href="<?= e(consultationBackToWorkspace((int)$consultation['visit_id'])) ?>">Workspace</a>
            <?php if ($canEdit): ?>
                <a class="btn-secondary" href="edit.php?id=<?= (int)$consultation['id'] ?>">Edit</a>
            <?php endif; ?>
            <?php if ($canComplete): ?>
                <form method="post" action="complete.php" style="display:inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int)$consultation['id'] ?>">
                    <button class="btn-primary" type="submit">Complete</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="summary-grid">
            <div class="summary-item"><span class="summary-label">Clinical Doctor</span><span class="summary-value"><?= e((string)$consultation['doctor_name']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Department</span><span class="summary-value"><?= e((string)$consultation['department_name']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Created By</span><span class="summary-value"><?= e((string)$consultation['created_by_name']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Completed</span><span class="summary-value"><?= e((string)($consultation['completed_at'] ?? 'Not completed')) ?></span></div>
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
        <div class="card-header">
            <div>
                <h3>Laboratory Requests</h3>
                <p>Encounter laboratory requests and results.</p>
            </div>
            <?php if ($permissionService->canCreateLaboratoryRequest($visit, $currentUser, 'Clinical')): ?>
                <a class="btn-primary" href="../laboratory/create.php?visit=<?= (int)$visit['id'] ?>&source=Clinical">Request Laboratory Test</a>
            <?php endif; ?>
        </div>
        <?php if ($latestLaboratoryRequests === []): ?>
            <p class="text-muted">No laboratory requests recorded.</p>
        <?php else: ?>
            <ul class="clean-list">
                <?php foreach (array_slice($latestLaboratoryRequests, 0, 5) as $request): ?>
                    <li>
                        <a href="../laboratory/view.php?id=<?= (int)$request['id'] ?>">#<?= (int)$request['id'] ?></a>
                        — <?= e((string)$request['tests_requested']) ?>
                        (<?= e((string)$request['status']) ?>)
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php if ($latestLaboratoryResult !== null && trim((string)($latestLaboratoryResult['result'] ?? '')) !== ''): ?>
                <div class="summary-grid">
                    <div class="summary-item"><span class="summary-label">Sample Taken</span><span class="summary-value"><?= e((string)($latestLaboratoryResult['sample_taken'] ?? '-')) ?></span></div>
                    <div class="summary-item"><span class="summary-label">Findings</span><span class="summary-value"><?= e((string)($latestLaboratoryResult['findings'] ?? '-')) ?></span></div>
                    <div class="summary-item"><span class="summary-label">Result</span><span class="summary-value"><?= e((string)$latestLaboratoryResult['result']) ?></span></div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <?php foreach ($fields as $field => $label): ?>
        <div class="card">
            <h3><?= e($label) ?></h3>
            <p><?= nl2br(e((string)($consultation[$field] ?? ''))) ?></p>
        </div>
    <?php endforeach; ?>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
