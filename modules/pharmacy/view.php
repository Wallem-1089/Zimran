<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$prescriptionId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
if (!$prescriptionId) {
    header('Location: index.php');
    exit;
}

if (!$pharmacyTablesReady) {
    http_response_code(503);
    exit('Pharmacy tables are not available yet. Apply Migration 032 to enable this section.');
}

$prescription = $pharmacyService->getPrescriptionById($prescriptionId, $currentUser);
if (!$prescription) {
    http_response_code(404);
    exit('Prescription not found.');
}

$visit = pharmacyRequireVisit($visitService, (int)$prescription['visit_id']);
$patient = $patientService->getPatientById((int)$prescription['patient_id']);
if (!$patient) {
    http_response_code(404);
    exit('Patient not found.');
}

$dispensing = $pharmacyService->getDispensingByPrescription($prescriptionId, $currentUser);
$canEdit = (string)$prescription['status'] === 'Prescribed'
    && $permissionService->canEditPrescription($visit, $currentUser, (string)$prescription['prescription_source']);
$canDispense = (string)$prescription['status'] === 'Prescribed'
    && $permissionService->canDispensePrescription($visit, $currentUser);
$canCancel = (string)$prescription['status'] === 'Prescribed'
    && $permissionService->canEditPrescription($visit, $currentUser, (string)$prescription['prescription_source']);
$canRecordDrugChart = !in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true)
    && $permissionService->canCreateNursing($visit, $currentUser);

$pageTitle = 'Prescription';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <?php if (isset($_SESSION['validation_errors'])): ?>
        <div class="alert-danger">
            <strong>Please correct the following:</strong>
            <ul>
                <?php foreach ((array)$_SESSION['validation_errors'] as $error): ?>
                    <li><?= e((string)$error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php unset($_SESSION['validation_errors']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert-success"><?= e((string)$_SESSION['success_message']) ?></div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <div class="page-header">
        <div>
            <h1>Prescription #<?= (int)$prescription['id'] ?></h1>
            <p><?= e((string)($prescription['visit_number'] ?? ('Encounter #' . (int)$prescription['visit_id']))) ?></p>
        </div>
        <div class="form-actions">
            <button class="btn-secondary" type="button" onclick="window.print()">Print Prescription</button>
            <?php if ($permissionService->canViewPharmacyWorklist($currentUser)): ?>
                <a class="btn-secondary" href="index.php">Worklist</a>
            <?php endif; ?>
            <a class="btn-secondary" href="<?= e(pharmacyBackToWorkspace((int)$prescription['visit_id'])) ?>">Workspace</a>
            <a class="btn-secondary" href="<?= e(pharmacyBackToConsultation((int)$prescription['visit_id'])) ?>">Consultation</a>
            <?php if (!in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true) && $permissionService->canCreateBillingRequest($currentUser)): ?>
                <a class="btn-secondary" href="../billing/request_create.php?visit=<?= (int)$prescription['visit_id'] ?>&source_module=Pharmacy&source_record_id=<?= (int)$prescription['id'] ?>&description=<?= urlencode('Pharmacy: ' . (string)($prescription['medication_name'] ?? '') . ' x ' . (string)($prescription['quantity'] ?? '')) ?>">Request Billing</a>
            <?php endif; ?>
            <?php if ($canRecordDrugChart): ?>
                <a class="btn-primary" href="../nursing/drug_chart/create.php?visit=<?= (int)$prescription['visit_id'] ?>&prescription=<?= (int)$prescription['id'] ?>">Record in Drug Chart</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="summary-grid">
            <div class="summary-item"><span class="summary-label">Patient</span> <span class="summary-value"><?= e((string)($prescription['patient_name'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Hospital Number</span> <span class="summary-value"><?= e((string)($prescription['hospital_number'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Medication</span> <span class="summary-value"><?= e((string)$prescription['medication_name']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Quantity</span> <span class="summary-value"><?= e((string)$prescription['quantity']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Source</span> <span class="summary-value"><?= e((string)$prescription['prescription_source']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Status</span> <span class="summary-value"><?= e((string)$prescription['status']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Prescriber</span> <span class="summary-value"><?= e((string)($prescription['prescribed_by_name'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Requested</span> <span class="summary-value"><?= e((string)($prescription['created_at'] ?? '-')) ?></span></div>
        </div>
    </div>

    <div class="card">
        <h3>Prescription Details</h3>
        <table class="summary-table">
            <tbody>
                <tr><th>Medication Snapshot</th><td><?= e((string)$prescription['medication_name']) ?></td></tr>
                <tr><th>Inventory Item</th><td><?= e((string)($prescription['inventory_item_name'] ?? '-')) ?></td></tr>
                <tr><th>Price</th><td><?= $prescription['unit_price'] !== null ? '₦' . number_format((float)$prescription['unit_price'], 2) : '—' ?></td></tr>
                <tr><th>Stock Available</th><td><?= number_format((float)($prescription['pharmacy_stock_available'] ?? 0), 2) ?></td></tr>
                <tr><th>Dosage</th><td><?= nl2br(e((string)($prescription['dosage'] ?? '-'))) ?></td></tr>
                <tr><th>Frequency</th><td><?= nl2br(e((string)($prescription['frequency'] ?? '-'))) ?></td></tr>
                <tr><th>Duration</th><td><?= nl2br(e((string)($prescription['duration'] ?? '-'))) ?></td></tr>
                <tr><th>Instructions</th><td><?= nl2br(e((string)($prescription['instructions'] ?? '-'))) ?></td></tr>
            </tbody>
        </table>
    </div>

    <div class="card">
        <div class="form-actions">
            <?php if ($canEdit): ?>
                <a class="btn-secondary" href="edit.php?id=<?= (int)$prescription['id'] ?>">Edit</a>
            <?php endif; ?>
            <?php if ($canDispense): ?>
                <a class="btn-primary" href="dispense.php?id=<?= (int)$prescription['id'] ?>">Dispense</a>
            <?php endif; ?>
            <?php if ($canRecordDrugChart): ?>
                <a class="btn-secondary" href="../nursing/drug_chart/create.php?visit=<?= (int)$prescription['visit_id'] ?>&prescription=<?= (int)$prescription['id'] ?>">Record Administration</a>
            <?php endif; ?>
            <?php if ($canCancel): ?>
                <form method="post" action="cancel_save.php" onsubmit="return confirm('Cancel this prescription?');">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int)$prescription['id'] ?>">
                    <button type="submit" class="btn-danger">Cancel</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <h3>Dispensing</h3>
        <?php if (!$dispensing): ?>
            <p class="text-muted">No dispensing record exists.</p>
        <?php else: ?>
            <div class="summary-grid">
                <div class="summary-item"><span class="summary-label">Quantity Dispensed</span> <span class="summary-value"><?= e((string)$dispensing['quantity_dispensed']) ?></span></div>
                <div class="summary-item"><span class="summary-label">Dispensed By</span> <span class="summary-value"><?= e((string)($dispensing['dispensed_by_name'] ?? '-')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Dispensed At</span> <span class="summary-value"><?= e((string)($dispensing['created_at'] ?? '-')) ?></span></div>
            </div>
            <?php if (trim((string)($dispensing['dispensing_notes'] ?? '')) !== ''): ?>
                <h4>Dispensing Notes</h4>
                <p><?= nl2br(e((string)$dispensing['dispensing_notes'])) ?></p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
