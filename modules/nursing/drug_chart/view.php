<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$recordId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$record = $medicationAdministrationService->getById($recordId, $currentUser);
if (!$record) {
    http_response_code(404);
    exit('Drug chart entry not found.');
}

$visit = nursingRequireVisit($visitService, (int)$record['visit_id']);
nursingRequireAccess($permissionService, $visit, $currentUser);
$isClosedEncounter = in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true);
$canEdit = $permissionService->canEditNursing($visit, $currentUser)
    && (!$isClosedEncounter || $permissionService->isAdministrator($currentUser));

$pageTitle = 'Drug Chart Entry';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../../layouts/header.php';
require __DIR__ . '/../../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div>
            <h1>Drug Chart Entry</h1>
            <p><?= e((string)$record['medication_name']) ?></p>
        </div>
        <div class="form-actions">
            <button class="btn-secondary" type="button" onclick="window.print()">Print Drug Chart Entry</button>
            <a class="btn-secondary" href="history.php?visit=<?= (int)$record['visit_id'] ?>">Drug Chart</a>
            <a class="btn-secondary" href="<?= e(drugChartBackToWorkspace((int)$record['visit_id'])) ?>">Workspace</a>
            <?php if ($canEdit): ?>
                <a class="btn-primary" href="edit.php?id=<?= (int)$record['id'] ?>">Edit</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="summary-grid">
            <div class="summary-item"><span class="summary-label">Medication</span> <span class="summary-value"><?= e((string)$record['medication_name']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Dose Given</span> <span class="summary-value"><?= e((string)$record['dose_given']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Route</span> <span class="summary-value"><?= e((string)($record['route'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Status</span> <span class="summary-value"><?= e((string)$record['administration_status']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Time</span> <span class="summary-value"><?= e((string)$record['scheduled_time']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Administered By</span> <span class="summary-value"><?= e((string)($record['administered_by_name'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Prescription Status</span> <span class="summary-value"><?= e((string)($record['prescription_status'] ?? 'Not linked')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Prescribed Dose</span> <span class="summary-value"><?= e((string)($record['prescribed_dosage'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Prescribed Frequency</span> <span class="summary-value"><?= e((string)($record['prescribed_frequency'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Prescribed Duration</span> <span class="summary-value"><?= e((string)($record['prescribed_duration'] ?? '-')) ?></span></div>
        </div>

        <?php if (!empty($record['prescription_id'])): ?>
            <p class="text-muted">
                Linked to Pharmacy Prescription
                <a href="../../pharmacy/view.php?id=<?= (int)$record['prescription_id'] ?>">#<?= (int)$record['prescription_id'] ?></a>.
            </p>
        <?php endif; ?>

        <?php if (trim((string)($record['prescribed_instructions'] ?? '')) !== ''): ?>
            <h3>Prescription Instructions</h3>
            <p><?php hmsRenderNarrative((string)$record['prescribed_instructions']); ?></p>
        <?php endif; ?>

        <?php if (trim((string)($record['notes'] ?? '')) !== ''): ?>
            <h3>Notes</h3>
            <p><?php hmsRenderNarrative((string)$record['notes']); ?></p>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../../../layouts/footer.php'; ?>
</div>
