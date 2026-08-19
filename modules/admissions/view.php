<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
admissionRequireReady($admissionTablesReady);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$visitId = filter_input(INPUT_GET, 'visit', FILTER_VALIDATE_INT) ?: 0;
$admission = $id > 0
    ? $admissionService->getAdmissionById($id, $currentUser)
    : $admissionService->getByVisit($visitId, $currentUser);

if (!$admission) {
    http_response_code(404);
    exit('Admission not found.');
}

$visit = $visitService->getVisitById((int)$admission['visit_id']);
$movements = $admissionService->listMovements((int)$admission['id']);
$activeAdmission = in_array((string)$admission['status'], ['Admitted','Transferred'], true);
$canTransfer = $activeAdmission && $permissionService->canTransferAdmission($visit, $currentUser);
$canDischarge = $activeAdmission && $permissionService->canDischargeAdmission($visit, $currentUser);

$pageTitle = 'Admission';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <?php if (isset($_SESSION['success_message'])): ?><div class="alert-success"><?= e((string)$_SESSION['success_message']) ?></div><?php unset($_SESSION['success_message']); endif; ?>
    <?php if (isset($_SESSION['validation_errors'])): ?><div class="alert-danger"><?= e(implode(' ', (array)$_SESSION['validation_errors'])) ?></div><?php unset($_SESSION['validation_errors']); endif; ?>
    <div class="page-header">
        <div><h1>Admission</h1><p><?= e((string)$admission['patient_name']) ?> · <?= e((string)$admission['visit_number']) ?></p></div>
        <div class="form-actions">
            <a class="btn-secondary" href="<?= e(admissionBackToWorkspace((int)$admission['visit_id'])) ?>">Workspace</a>
            <?php if ($canTransfer): ?><a class="btn-secondary" href="transfer.php?id=<?= (int)$admission['id'] ?>">Transfer Bed/Ward</a><?php endif; ?>
            <?php if ($canDischarge): ?><a class="btn-primary" href="discharge.php?id=<?= (int)$admission['id'] ?>">Discharge</a><?php endif; ?>
            <?php if ($canDischarge): ?><a class="btn-secondary" href="cancel.php?id=<?= (int)$admission['id'] ?>">Cancel Admission</a><?php endif; ?>
        </div>
    </div>
    <div class="card">
        <div class="summary-grid">
            <div class="summary-item"><span class="summary-label">Ward</span> <span class="summary-value"><?= e((string)$admission['ward_name']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Bed</span> <span class="summary-value"><?= e((string)$admission['bed_label']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Type</span> <span class="summary-value"><?= e((string)$admission['admission_type']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Status</span> <span class="summary-value"><?= e((string)$admission['status']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Admitted By</span> <span class="summary-value"><?= e((string)($admission['admitted_by_name'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Admitted At</span> <span class="summary-value"><?= e((string)$admission['admitted_at']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Discharged By</span> <span class="summary-value"><?= e((string)($admission['discharged_by_name'] ?? 'Not discharged')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Discharged At</span> <span class="summary-value"><?= e((string)($admission['discharged_at'] ?? 'Not discharged')) ?></span></div>
        </div>
    </div>
    <div class="card"><h3>Admission Diagnosis</h3><p><?= nl2br(e((string)($admission['admission_diagnosis'] ?? ''))) ?></p></div>
    <div class="card"><h3>Admission Notes</h3><p><?= nl2br(e((string)($admission['admission_notes'] ?? ''))) ?></p></div>
    <?php if (!empty($admission['discharge_notes'])): ?><div class="card"><h3>Discharge Notes</h3><p><?= nl2br(e((string)$admission['discharge_notes'])) ?></p></div><?php endif; ?>
    <div class="card">
        <h3>Movement History</h3>
        <?php if ($movements === []): ?><div class="empty-state">No admission movements recorded.</div><?php else: ?>
            <table class="data-table"><thead><tr><th>Type</th><th>From</th><th>To</th><th>Reason</th><th>By</th><th>Date</th></tr></thead><tbody>
                <?php foreach ($movements as $move): ?>
                    <tr>
                        <td><?= e((string)$move['movement_type']) ?></td>
                        <td><?= e(trim((string)($move['from_ward_name'] ?? '') . ' ' . (string)($move['from_bed_label'] ?? '')) ?: '-') ?></td>
                        <td><?= e(trim((string)($move['to_ward_name'] ?? '') . ' ' . (string)($move['to_bed_label'] ?? '')) ?: '-') ?></td>
                        <td><?= e((string)($move['reason'] ?? '-')) ?></td>
                        <td><?= e((string)($move['performed_by_name'] ?? '-')) ?></td>
                        <td><?= e((string)$move['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody></table>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
