<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$recordId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$record = $diabetesMonitoringService->getById($recordId, $currentUser);
if (!$record) {
    http_response_code(404);
    exit('DM Sheet entry not found.');
}

$visit = nursingRequireVisit($visitService, (int)$record['visit_id']);
nursingRequireAccess($permissionService, $visit, $currentUser);
$isClosedEncounter = in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true);
$canEdit = $permissionService->canEditNursing($visit, $currentUser)
    && (!$isClosedEncounter || $permissionService->isAdministrator($currentUser));

$pageTitle = 'DM Sheet Entry';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../../layouts/header.php';
require __DIR__ . '/../../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div>
            <h1>DM Sheet Entry</h1>
            <p><?= e((string)$record['recorded_at']) ?></p>
        </div>
        <div class="form-actions">
            <button class="btn-secondary" type="button" onclick="window.print()">Print DM Sheet Entry</button>
            <a class="btn-secondary" href="history.php?visit=<?= (int)$record['visit_id'] ?>">DM Sheet</a>
            <a class="btn-secondary" href="<?= e(dmSheetBackToWorkspace((int)$record['visit_id'])) ?>">Workspace</a>
            <?php if ($canEdit): ?>
                <a class="btn-primary" href="edit.php?id=<?= (int)$record['id'] ?>">Edit</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="summary-grid">
            <div class="summary-item"><span class="summary-label">Blood Glucose</span> <span class="summary-value"><?= e((string)$record['blood_glucose']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Meal Status</span> <span class="summary-value"><?= e((string)$record['meal_status']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Insulin Given</span> <span class="summary-value"><?= e((string)($record['insulin_given'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Recorded At</span> <span class="summary-value"><?= e((string)$record['recorded_at']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Recorded By</span> <span class="summary-value"><?= e((string)($record['recorded_by_name'] ?? '-')) ?></span></div>
        </div>

        <?php if (trim((string)($record['symptoms'] ?? '')) !== ''): ?>
            <h3>Symptoms</h3>
            <p><?= nl2br(e((string)$record['symptoms'])) ?></p>
        <?php endif; ?>

        <?php if (trim((string)($record['notes'] ?? '')) !== ''): ?>
            <h3>Notes</h3>
            <p><?= nl2br(e((string)$record['notes'])) ?></p>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../../../layouts/footer.php'; ?>
</div>
