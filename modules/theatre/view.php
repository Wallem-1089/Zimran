<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$recordId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$theatre = $theatreService->getById($recordId, $currentUser);

if (!$theatre) {
    http_response_code(404);
    exit('Theatre record not found.');
}

$visit = theatreRequireVisit($visitService, (int)$theatre['visit_id']);
theatreRequireAccess($permissionService, $visit, $currentUser);

$canEdit = (string)$theatre['status'] === 'Draft'
    && $permissionService->canEditTheatre($visit, $currentUser);
$canComplete = (string)$theatre['status'] === 'Draft'
    && $permissionService->canCompleteTheatre($visit, $currentUser);
$latestVitalSigns = $vitalSignsService
    ? $vitalSignsService->getLatestByVisit((int)$visit['id'], $currentUser)
    : null;

$pageTitle = 'Theatre Record';
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
            <h1>Theatre Record</h1>
            <p><?= e((string)$theatre['visit_number']) ?> | <?= e((string)$theatre['status']) ?></p>
        </div>
        <div class="form-actions no-print">
            <button class="btn-secondary" type="button" onclick="window.print()">Print Operation Note</button>
            <a class="btn-secondary" href="<?= e(theatreBackToWorkspace((int)$theatre['visit_id'])) ?>">Workspace</a>
            <a class="btn-secondary" href="history.php?patient=<?= (int)$theatre['patient_id'] ?>">History</a>
            <?php if (!in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true) && $permissionService->canCreateBillingRequest($currentUser)): ?>
                <a class="btn-secondary" href="../billing/request_create.php?visit=<?= (int)$theatre['visit_id'] ?>&source_module=Theatre&source_record_id=<?= (int)$theatre['id'] ?>&description=<?= urlencode('Theatre: ' . (string)($theatre['procedure_name'] ?? '')) ?>">Request Billing</a>
            <?php endif; ?>
            <?php if ($canEdit): ?>
                <a class="btn-secondary" href="edit.php?id=<?= (int)$theatre['id'] ?>">Edit</a>
            <?php endif; ?>
            <?php if ($canComplete): ?>
                <form method="post" action="complete.php" style="display:inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int)$theatre['id'] ?>">
                    <button class="btn-primary" type="submit">Complete</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <div class="card">
        <div class="summary-grid">
            <div class="summary-item"><span class="summary-label">Procedure</span> <span class="summary-value"><?= e((string)$theatre['procedure_name']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Surgeon</span> <span class="summary-value"><?= e((string)($theatre['surgeon_name'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Department</span> <span class="summary-value"><?= e((string)($theatre['department_name'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Created By</span> <span class="summary-value"><?= e((string)($theatre['created_by_name'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Completed By</span> <span class="summary-value"><?= e((string)($theatre['completed_by_name'] ?? 'Not completed')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Completed At</span> <span class="summary-value"><?= e((string)($theatre['completed_at'] ?? 'Not completed')) ?></span></div>
        </div>
    </div>
    <?php if ($latestVitalSigns !== null): ?>
        <div class="card">
            <h3>Latest Vital Signs</h3>
            <?php $latest = $latestVitalSigns; require __DIR__ . '/../vital_signs/partials/record_card.php'; ?>
        </div>
    <?php endif; ?>
    <?php foreach ([
        'indication' => 'Indication',
        'preoperative_notes' => 'Preoperative Notes',
        'procedure_details' => 'Procedure Details',
        'findings' => 'Findings',
        'complications' => 'Complications',
        'postoperative_notes' => 'Postoperative Notes',
        'postoperative_plan' => 'Postoperative Plan',
        'anaesthesia_notes' => 'Anaesthesia Notes',
    ] as $field => $label): ?>
        <div class="card">
            <h3><?= e($label) ?></h3>
            <p><?= nl2br(e((string)($theatre[$field] ?? ''))) ?></p>
        </div>
    <?php endforeach; ?>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
