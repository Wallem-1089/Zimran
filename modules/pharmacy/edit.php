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
    $_SESSION['error_message'] = 'Dispensed or cancelled prescriptions are view-only.';
    header('Location: view.php?id=' . $prescriptionId);
    exit;
}

if (!$permissionService->canEditPrescription($visit, $currentUser, (string)$prescription['prescription_source'])) {
    http_response_code(403);
    exit('Prescription edit is denied.');
}

$inventoryItems = pharmacyInventoryOptions($storeService, $permissionService, $currentUser);
foreach ($inventoryItems as &$inventoryItem) {
    $balance = $storeService->getDepartmentBalance((int)$inventoryItem['id'], pharmacyDepartmentId($pdo), $currentUser);
    $inventoryItem['pharmacy_stock_available'] = (float)($balance['quantity'] ?? 0);
}
unset($inventoryItem);

$pharmacyPrescription = $prescription;
$pharmacyPrescription['visit_id'] = (int)$prescription['visit_id'];
$pharmacyPrescription['patient_id'] = (int)$prescription['patient_id'];
$requestSource = (string)$prescription['prescription_source'];
$formAction = 'update.php';
$buttonLabel = 'Update Prescription';

$pageTitle = 'Edit Prescription';
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
            <h1>Edit Prescription</h1>
            <p><?= e((string)$prescription['visit_number']) ?></p>
        </div>
        <div>
            <a class="btn-secondary" href="view.php?id=<?= (int)$prescription['id'] ?>">Back</a>
        </div>
    </div>

    <?php require __DIR__ . '/_form.php'; ?>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
