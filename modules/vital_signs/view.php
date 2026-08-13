<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$vitalSignsId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$vitalSigns = $vitalSignsService->getById($vitalSignsId);
if (!$vitalSigns) {
    http_response_code(404);
    exit('Vital signs record not found.');
}

$visit = vitalSignsRequireVisit($visitService, (int)$vitalSigns['visit_id']);
vitalSignsRequireAccess($permissionService, $visit, $currentUser);
$canEdit = $permissionService->canEditVitalSigns($visit, $currentUser);

$pageTitle = 'Vital Signs';
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
            <h1>Vital Signs</h1>
            <p><?= e((string)$visit['visit_number']) ?></p>
        </div>
        <div>
            <a class="btn-secondary" href="<?= e(vitalSignsBackToWorkspace((int)$visit['id'])) ?>">Workspace</a>
            <a class="btn-secondary" href="<?= e(vitalSignsBackToChart((int)$visit['patient_id'])) ?>">Patient Chart</a>
            <?php if ($canEdit): ?>
                <a class="btn-primary" href="edit.php?id=<?= (int)$vitalSigns['id'] ?>">Edit</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="summary-grid">
            <div class="summary-item"><span class="summary-label">Recorded By</span> <span class="summary-value"><?= e((string)($vitalSigns['recorded_by_name'] ?? 'Unknown')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Recorded At</span> <span class="summary-value"><?= e((string)($vitalSigns['created_at'] ?? 'Unknown')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Department</span> <span class="summary-value"><?= e((string)($vitalSigns['department_name'] ?? '')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Visit Status</span> <span class="summary-value"><?= e((string)($visit['visit_status'] ?? 'Unknown')) ?></span></div>
        </div>
    </div>

    <div class="card">
        <?php $latest = $vitalSigns; require __DIR__ . '/partials/record_card.php'; ?>
    </div>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
