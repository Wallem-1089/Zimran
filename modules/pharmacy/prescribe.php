<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$visitId = filter_input(INPUT_GET, 'visit', FILTER_VALIDATE_INT) ?: 0;
$requestSource = pharmacyRequestSourceLabel((string)($_GET['source'] ?? 'Clinical'));

if (!$visitId) {
    header('Location: index.php');
    exit;
}

$visit = pharmacyRequireVisit($visitService, $visitId);
$patient = $patientService->getPatientById((int)$visit['patient_id']);
if (!$patient) {
    http_response_code(404);
    exit('Patient not found.');
}

if (!$pharmacyTablesReady) {
    http_response_code(503);
    exit('Pharmacy tables are not available yet. Apply Migration 032 to enable this section.');
}

if (!$permissionService->canCreatePrescription($visit, $currentUser, $requestSource)) {
    http_response_code(403);
    exit('You do not have permission to create this prescription.');
}

$inventoryItems = pharmacyInventoryOptions($storeService, $permissionService, $currentUser);
foreach ($inventoryItems as &$inventoryItem) {
    $balance = $storeService->getDepartmentBalance((int)$inventoryItem['id'], pharmacyDepartmentId($pdo), $currentUser);
    $inventoryItem['pharmacy_stock_available'] = (float)($balance['quantity'] ?? 0);
}
unset($inventoryItem);

$pharmacyPrescription = $_SESSION['old_pharmacy_prescription'] ?? [
    'visit_id' => $visitId,
    'patient_id' => (int)$visit['patient_id'],
    'prescription_source' => $requestSource,
    'inventory_item_id' => null,
    'medication_name' => '',
    'dosage' => '',
    'frequency' => '',
    'duration' => '',
    'quantity' => '',
    'instructions' => '',
];
unset($_SESSION['old_pharmacy_prescription']);

$canViewClinicalSafety = $permissionService->canViewClinicalSafety((int)$visit['patient_id'], $currentUser);
$allergies = $canViewClinicalSafety
    ? $clinicalSafetyService->getPatientAllergies((int)$visit['patient_id'])
    : [];
$alerts = $canViewClinicalSafety
    ? $clinicalSafetyService->getPatientAlertsForUser((int)$visit['patient_id'], $currentUser)
    : [];

$existingPrescriptions = $pharmacyService->listByVisit($visitId, $currentUser);

$pageTitle = 'Create Prescription';
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
            <h1>Create Prescription</h1>
            <p><?= e((string)($visit['visit_number'] ?? ('Encounter #' . $visitId))) ?></p>
        </div>
        <div class="form-actions">
            <a class="btn-secondary" href="<?= e(pharmacyBackToWorkspace($visitId)) ?>">Workspace</a>
            <a class="btn-secondary" href="index.php">Worklist</a>
        </div>
    </div>

    <div class="summary-grid card">
        <div class="summary-item"><span class="summary-label">Patient</span><span class="summary-value"><?= e((string)($patient['first_name'] . ' ' . $patient['last_name'])) ?></span></div>
        <div class="summary-item"><span class="summary-label">Hospital Number</span><span class="summary-value"><?= e((string)$patient['hospital_number']) ?></span></div>
        <div class="summary-item"><span class="summary-label">Request Source</span><span class="summary-value"><?= e($requestSource) ?></span></div>
        <div class="summary-item"><span class="summary-label">Encounter Status</span><span class="summary-value"><?= e((string)($visit['visit_status'] ?? '-')) ?></span></div>
    </div>

    <?php if ($canViewClinicalSafety): ?>
        <div class="card">
            <h3>Clinical Safety</h3>
            <div class="summary-grid">
                <div class="summary-item">
                    <span class="summary-label">Allergies</span>
                    <span class="summary-value"><?= e((string)count($allergies)) ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Alerts</span>
                    <span class="summary-value"><?= e((string)count($alerts)) ?></span>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php $pharmacyPrescription = $pharmacyPrescription; $formAction = 'save.php'; $buttonLabel = 'Save Prescription'; require __DIR__ . '/_form.php'; ?>

    <div class="card">
        <h3>Existing Prescriptions for This Encounter</h3>
        <?php if ($existingPrescriptions === []): ?>
            <p class="text-muted">No prescriptions recorded for this encounter.</p>
        <?php else: ?>
            <ul class="clean-list">
                <?php foreach ($existingPrescriptions as $prescription): ?>
                    <li>
                        <a href="view.php?id=<?= (int)$prescription['id'] ?>">#<?= (int)$prescription['id'] ?></a>
                        — <?= e((string)$prescription['medication_name']) ?>
                        (<?= e((string)$prescription['status']) ?>)
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
