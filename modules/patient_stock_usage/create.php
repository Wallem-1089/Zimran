<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if (!$patientStockUsageTablesReady) {
    http_response_code(503);
    exit('Patient Stock Usage tables are not available yet. Apply Migration 053 to enable this section.');
}

$visitId = filter_input(INPUT_GET, 'visit', FILTER_VALIDATE_INT) ?: 0;
$visit = $visitService->getVisitById($visitId);
if (!$visit) {
    http_response_code(404);
    exit('Encounter not found.');
}
if (!$permissionService->canViewEncounter($visit, $currentUser)) {
    http_response_code(403);
    exit('Encounter access denied.');
}
if (!$permissionService->canRecordPatientStockUsage($currentUser)) {
    http_response_code(403);
    exit('You do not have permission to record patient stock usage.');
}
if (!$permissionService->isAdministrator($currentUser)
    && in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true)
) {
    http_response_code(403);
    exit('Completed or cancelled encounters cannot accept new stock usage.');
}

$patient = $patientService->getPatientById((int)$visit['patient_id']);
if (!$patient || (int)($patient['is_deleted'] ?? 0) === 1) {
    http_response_code(404);
    exit('Patient not found.');
}

$defaultDepartmentId = patientStockUsageDefaultDepartment($visit, $currentUser);
$selectedDepartmentId = (int)($_GET['department'] ?? $defaultDepartmentId);
if (!$permissionService->isAdministrator($currentUser) && $selectedDepartmentId !== $defaultDepartmentId) {
    $selectedDepartmentId = $defaultDepartmentId;
}
$departments = patientStockUsageDepartments($pdo);
$availableStock = $patientStockUsageService->listAvailableDepartmentStock($selectedDepartmentId, $currentUser);
$old = $_SESSION['old_patient_stock_usage'] ?? [];
unset($_SESSION['old_patient_stock_usage']);

$pageTitle = 'Record Patient Stock Usage';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div>
            <h1>Record Patient Stock Usage</h1>
            <p><?= e((string)($visit['visit_number'] ?? ('Encounter #' . (int)$visit['id']))) ?> — <?= e((string)($patient['first_name'] . ' ' . $patient['last_name'])) ?></p>
        </div>
        <div class="form-actions">
            <a class="btn-secondary" href="<?= e(patientStockUsageBackToWorkspace((int)$visit['id'])) ?>">Workspace</a>
            <a class="btn-secondary" href="history.php?visit=<?= (int)$visit['id'] ?>">Usage History</a>
        </div>
    </div>

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

    <form method="get" action="create.php" class="card">
        <input type="hidden" name="visit" value="<?= (int)$visit['id'] ?>">
        <div class="form-grid">
            <div class="form-group">
                <label for="department_filter">Stock Source Department</label>
                <select id="department_filter" name="department" onchange="this.form.submit()">
                    <?php foreach ($departments as $department): ?>
                        <option value="<?= (int)$department['id'] ?>" <?= $selectedDepartmentId === (int)$department['id'] ? 'selected' : '' ?>>
                            <?= e((string)$department['department_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <noscript><button class="btn-secondary" type="submit">Load Stock</button></noscript>
    </form>

    <form method="post" action="save.php" class="card">
        <?= csrfField() ?>
        <input type="hidden" name="visit_id" value="<?= (int)$visit['id'] ?>">
        <input type="hidden" name="patient_id" value="<?= (int)$visit['patient_id'] ?>">
        <input type="hidden" name="department_id" value="<?= (int)$selectedDepartmentId ?>">

        <div class="form-grid">
            <div class="form-group">
                <label for="inventory_item_id">Inventory Item</label>
                <select id="inventory_item_id" name="inventory_item_id" required>
                    <option value="">Select item from department stock</option>
                    <?php foreach ($availableStock as $stock): ?>
                        <option value="<?= (int)$stock['inventory_item_id'] ?>" <?= (int)($old['inventory_item_id'] ?? 0) === (int)$stock['inventory_item_id'] ? 'selected' : '' ?>>
                            <?= e((string)$stock['item_name']) ?>
                            — available <?= e((string)$stock['quantity']) ?> <?= e((string)($stock['unit'] ?? '')) ?>
                            <?= !empty($stock['billable_item_id']) ? ' / billable' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($availableStock === []): ?>
                    <small class="text-muted">No stock is available in the selected department.</small>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="quantity">Quantity Used</label>
                <input id="quantity" name="quantity" type="number" step="0.01" min="0.01" required value="<?= e((string)($old['quantity'] ?? '')) ?>">
            </div>

            <div class="form-group full-width">
                <label for="usage_reason">Usage Reason</label>
                <textarea id="usage_reason" name="usage_reason" rows="4" placeholder="e.g. IV cannulation, dressing care, procedure consumables"><?= e((string)($old['usage_reason'] ?? '')) ?></textarea>
            </div>

            <div class="form-group full-width">
                <label>
                    <input type="checkbox" name="request_billing" value="1" <?= !empty($old['request_billing']) ? 'checked' : '' ?>>
                    Create Billing Request for Accounts review
                </label>
                <small class="text-muted">This does not create a patient charge. Accounts still reviews and posts the official charge.</small>
            </div>
        </div>

        <div class="form-actions">
            <button class="btn-primary" type="submit" <?= $availableStock === [] ? 'disabled' : '' ?>>Record Stock Used</button>
            <a class="btn-secondary" href="<?= e(patientStockUsageBackToWorkspace((int)$visit['id'])) ?>">Cancel</a>
        </div>
    </form>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
