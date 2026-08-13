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
if ((string)$prescription['status'] !== 'Prescribed') {
    $_SESSION['error_message'] = 'Completed or cancelled prescriptions are view-only.';
    header('Location: view.php?id=' . $prescriptionId);
    exit;
}

if (!$permissionService->canDispensePrescription($visit, $currentUser)) {
    http_response_code(403);
    exit('Prescription dispensing is denied.');
}

$patient = $patientService->getPatientById((int)$prescription['patient_id']);
if (!$patient) {
    http_response_code(404);
    exit('Patient not found.');
}

$stock = null;
if ((int)($prescription['inventory_item_id'] ?? 0) > 0) {
    $stock = $storeService->getDepartmentBalance((int)$prescription['inventory_item_id'], pharmacyDepartmentId($pdo), $currentUser);
}

$canViewClinicalSafety = $permissionService->canViewClinicalSafety((int)$visit['patient_id'], $currentUser);
$allergies = $canViewClinicalSafety
    ? $clinicalSafetyService->getPatientAllergies((int)$visit['patient_id'])
    : [];
$alerts = $canViewClinicalSafety
    ? $clinicalSafetyService->getPatientAlertsForUser((int)$visit['patient_id'], $currentUser)
    : [];

$pageTitle = 'Dispense Prescription';
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

    <div class="page-header">
        <div>
            <h1>Dispense Prescription</h1>
            <p><?= e((string)$prescription['visit_number']) ?></p>
        </div>
        <div>
            <a class="btn-secondary" href="view.php?id=<?= (int)$prescription['id'] ?>">Back</a>
        </div>
    </div>

    <?php if ($canViewClinicalSafety): ?>
        <div class="card">
            <h3>Clinical Safety</h3>
            <div class="summary-grid">
                <div class="summary-item"><span class="summary-label">Allergies</span> <span class="summary-value"><?= e((string)count($allergies)) ?></span></div>
                <div class="summary-item"><span class="summary-label">Alerts</span> <span class="summary-value"><?= e((string)count($alerts)) ?></span></div>
            </div>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="summary-grid">
            <div class="summary-item"><span class="summary-label">Medication</span> <span class="summary-value"><?= e((string)$prescription['medication_name']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Stock Available</span> <span class="summary-value"><?= number_format((float)($stock['quantity'] ?? 0), 2) ?></span></div>
            <div class="summary-item"><span class="summary-label">Quantity</span> <span class="summary-value"><?= e((string)$prescription['quantity']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Source</span> <span class="summary-value"><?= e((string)$prescription['prescription_source']) ?></span></div>
        </div>
    </div>

    <form method="post" action="dispense_save.php" class="card">
        <?= csrfField() ?>
        <input type="hidden" name="id" value="<?= (int)$prescription['id'] ?>">

        <div class="form-group">
            <label>Medication</label>
            <p class="text-muted"><?= e((string)$prescription['medication_name']) ?></p>
        </div>

        <div class="form-group">
            <label for="quantity_dispensed">Quantity to Dispense</label>
            <input
                type="number"
                id="quantity_dispensed"
                name="quantity_dispensed"
                min="0.01"
                step="0.01"
                value="<?= e((string)$prescription['quantity']) ?>"
                required>
        </div>

        <div class="form-group">
            <label for="dispensing_notes">Dispensing Notes</label>
            <textarea id="dispensing_notes" name="dispensing_notes" rows="4"></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">Dispense Prescription</button>
            <a class="btn-secondary" href="view.php?id=<?= (int)$prescription['id'] ?>">Cancel</a>
        </div>
    </form>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
