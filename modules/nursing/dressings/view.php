<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$recordId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$record = $dressingRecordService->getById($recordId, $currentUser);
if (!$record) {
    http_response_code(404);
    exit('Dressing record not found.');
}

$visit = nursingRequireVisit($visitService, (int)$record['visit_id']);
nursingRequireAccess($permissionService, $visit, $currentUser);
$canEdit = $permissionService->canEditNursing($visit, $currentUser)
    && (
        !in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true)
        || $permissionService->isAdministrator($currentUser)
    );
$isClosed = in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true);
$canRequestBilling = !$isClosed && $permissionService->canCreateBillingRequest($currentUser);
$dressingConfiguredDisplayValues = $configurableFormService->getResponseValues('dressing_record', 'Dressing Record', (int)$record['id']);

$pageTitle = 'Dressing Record';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../../layouts/header.php';
require __DIR__ . '/../../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../../layouts/navbar.php'; ?>
<main class="content">
    <?php if (isset($_SESSION['success_message'])): ?><div class="alert-success"><?= e((string)$_SESSION['success_message']) ?></div><?php unset($_SESSION['success_message']); endif; ?>
    <div class="page-header">
        <div>
            <h1>Dressing Record</h1>
            <p><?= e((string)($record['visit_number'] ?? ('Encounter #' . (int)$record['visit_id']))) ?></p>
        </div>
        <div class="form-actions">
            <button class="btn-secondary" type="button" onclick="window.print()">Print Dressing Record</button>
            <a class="btn-secondary" href="history.php?visit=<?= (int)$record['visit_id'] ?>">Dressing Book</a>
            <a class="btn-secondary" href="<?= e(dressingBackToWorkspace((int)$record['visit_id'])) ?>">Workspace</a>
            <?php if ($canRequestBilling): ?>
                <a class="btn-secondary" href="../../billing/request_create.php?visit=<?= (int)$record['visit_id'] ?>&source_module=Dressing&source_record_id=<?= (int)$record['id'] ?>&description=<?= urlencode('Dressing: ' . (string)($record['wound_site'] ?? 'Dressing care')) ?>">Request Billing</a>
            <?php endif; ?>
            <?php if ($canEdit): ?><a class="btn-primary" href="edit.php?id=<?= (int)$record['id'] ?>">Edit</a><?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="summary-grid">
            <div class="summary-item"><span class="summary-label">Patient</span> <span class="summary-value"><?= e((string)($record['first_name'] ?? '')) ?> <?= e((string)($record['last_name'] ?? '')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Hospital Number</span> <span class="summary-value"><?= e((string)($record['hospital_number'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Wound Site</span> <span class="summary-value"><?= e((string)$record['wound_site']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Next Dressing Date</span> <span class="summary-value"><?= e((string)($record['next_dressing_date'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Recorded By</span> <span class="summary-value"><?= e((string)($record['recorded_by_name'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Recorded At</span> <span class="summary-value"><?= e((string)($record['created_at'] ?? '-')) ?></span></div>
        </div>
    </div>

    <?php foreach ([
        'wound_condition' => 'Wound Condition',
        'dressing_done' => 'Dressing Done',
        'supplies_used' => 'Supplies Used',
    ] as $field => $label): ?>
        <div class="card">
            <h3><?= e($label) ?></h3>
            <p><?php hmsRenderNarrative((string)($record[$field] ?? '-')); ?></p>
        </div>
    <?php endforeach; ?>
    <?php hmsRenderConfiguredValues($dressingConfiguredDisplayValues); ?>
</main>
<?php require __DIR__ . '/../../../layouts/footer.php'; ?>
</div>
