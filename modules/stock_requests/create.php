<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
stockRequestRequireReady($stockRequestTablesReady);

if (!$permissionService->canCreateStockRequest($currentUser)) {
    http_response_code(403);
    exit('Stock request creation denied.');
}

$items = stockRequestInventoryItems($pdo);
$itemsById = [];
foreach ($items as $item) {
    $itemsById[(int)$item['id']] = $item;
}
$drugItems = array_values(array_filter(
    $items,
    static fn (array $item): bool => in_array(strtolower(trim((string)($item['category'] ?? ''))), ['drug', 'medication'], true)
));
$consumableItems = array_values(array_filter(
    $items,
    static fn (array $item): bool => !in_array(strtolower(trim((string)($item['category'] ?? ''))), ['drug', 'medication'], true)
));
$departments = stockRequestDepartments($pdo);
$canChooseDepartment = $permissionService->isAdministrator($currentUser);
$currentDepartmentId = (int)($currentUser['active_department_id'] ?? $currentUser['department_id'] ?? 0);
$currentDepartmentName = (string)($currentUser['active_department_name'] ?? $currentUser['department_name'] ?? 'Your Department');
$old = $_SESSION['old_stock_request'] ?? [];
unset($_SESSION['old_stock_request']);
$enableWritingMode = $permissionService->canUseConsultationHandwriting($currentUser);
$oldDrugRows = [];
$oldConsumableRows = [];
foreach ((array)($old['inventory_item_id'] ?? []) as $index => $oldItemIdRaw) {
    $oldItemId = (int)$oldItemIdRaw;
    if ($oldItemId <= 0) {
        continue;
    }
    $oldRow = [
        'inventory_item_id' => $oldItemId,
        'quantity_requested' => (string)($old['quantity_requested'][$index] ?? ''),
        'notes' => (string)($old['notes'][$index] ?? ''),
    ];
    $oldItem = $itemsById[$oldItemId] ?? [];
    if (in_array(strtolower(trim((string)($oldItem['category'] ?? ''))), ['drug', 'medication'], true)) {
        $oldDrugRows[] = $oldRow;
    } else {
        $oldConsumableRows[] = $oldRow;
    }
}
for ($i = count($oldDrugRows); $i < 3; $i++) {
    $oldDrugRows[] = [];
}
for ($i = count($oldConsumableRows); $i < 3; $i++) {
    $oldConsumableRows[] = [];
}

$renderStockRequestRows = static function (array $rows, array $options): void {
    foreach ($rows as $row):
        $selectedItemId = (int)($row['inventory_item_id'] ?? 0);
        ?>
        <tr>
            <td>
                <select name="inventory_item_id[]">
                    <option value="">Select item</option>
                    <?php foreach ($options as $item): ?>
                        <option value="<?= (int)$item['id'] ?>" <?= $selectedItemId === (int)$item['id'] ? 'selected' : '' ?>>
                            <?= e((string)$item['item_code']) ?> - <?= e((string)$item['item_name']) ?> <?= e((string)($item['unit'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td><input name="quantity_requested[]" type="number" step="1" min="0" value="<?= e((string)($row['quantity_requested'] ?? '')) ?>"></td>
            <td><input name="notes[]" maxlength="1000" value="<?= e((string)($row['notes'] ?? '')) ?>"></td>
        </tr>
        <?php
    endforeach;
};

$pageTitle = 'New Stock Request';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <?php if (isset($_SESSION['validation_errors'])): ?><div class="alert-danger"><ul><?php foreach ((array)$_SESSION['validation_errors'] as $error): ?><li><?= e((string)$error) ?></li><?php endforeach; ?></ul></div><?php unset($_SESSION['validation_errors']); endif; ?>

    <div class="page-header">
        <div><h1>New Stock Request</h1><p>Request stock for your department. Drug requests go to Pharmacy; consumable/non-drug requests go to Store.</p></div>
        <div><a class="btn-secondary" href="index.php">Back</a></div>
    </div>

    <form class="card" method="post" action="save.php" <?= $enableWritingMode ? 'data-hms-handwriting-form="1"' : '' ?>>
        <?= csrfField() ?>
        <?php if ($canChooseDepartment): ?>
            <label>Requesting Department
                <select name="requesting_department_id" required>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?= (int)$department['id'] ?>" <?= (int)($old['requesting_department_id'] ?? 0) === (int)$department['id'] ? 'selected' : '' ?>>
                            <?= e((string)$department['department_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php else: ?>
            <input type="hidden" name="requesting_department_id" value="<?= (int)$currentDepartmentId ?>">
            <div class="form-group">
                <label>Requesting Department</label>
                <div class="readonly-field"><?= e($currentDepartmentName) ?></div>
                <p class="text-muted">Stock requests are fixed to your department.</p>
            </div>
        <?php endif; ?>
        <?php hmsRenderHandwritingToolbar($enableWritingMode, 'Stock Request Entry Mode'); ?>
        <?php hmsRenderHandwritingTextarea('reason', 'Reason / Notes', (string)($old['reason'] ?? ''), 4, false, $enableWritingMode, 2000); ?>

        <div class="card">
            <div class="section-header">
                <div>
                    <h3>Drug Stock Request</h3>
                    <p class="text-muted">These lines are routed to Pharmacy for issuing. Store only supplies drug stock into Pharmacy.</p>
                </div>
                <button class="btn-secondary btn-sm" type="button" data-add-stock-row="drug-stock-request-table">+ Add Drug Item</button>
            </div>
            <?php if ($drugItems === []): ?>
                <div class="empty-state">No active drug inventory items are available.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table" id="drug-stock-request-table">
                        <thead><tr><th>Drug Item</th><th>Quantity</th><th>Notes</th></tr></thead>
                        <tbody><?php $renderStockRequestRows($oldDrugRows, $drugItems); ?></tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="section-header">
                <div>
                    <h3>Consumable / Non-drug Stock Request</h3>
                    <p class="text-muted">These lines are routed to Store for issuing to the requesting department.</p>
                </div>
                <button class="btn-secondary btn-sm" type="button" data-add-stock-row="consumable-stock-request-table">+ Add Consumable Item</button>
            </div>
            <?php if ($consumableItems === []): ?>
                <div class="empty-state">No active consumable/non-drug inventory items are available.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table" id="consumable-stock-request-table">
                        <thead><tr><th>Consumable / Non-drug Item</th><th>Quantity</th><th>Notes</th></tr></thead>
                        <tbody><?php $renderStockRequestRows($oldConsumableRows, $consumableItems); ?></tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <div class="form-actions">
            <button class="btn-primary" type="submit">Submit Request</button>
            <a class="btn-secondary" href="index.php">Cancel</a>
        </div>
    </form>
    <?php hmsRenderHandwritingScript($enableWritingMode); ?>
    <script>
    document.querySelectorAll('[data-add-stock-row]').forEach((button) => {
        button.addEventListener('click', () => {
            const table = document.getElementById(button.getAttribute('data-add-stock-row'));
            const body = table ? table.querySelector('tbody') : null;
            const sourceRow = body ? body.querySelector('tr:last-child') : null;
            if (!body || !sourceRow) {
                return;
            }

            const row = sourceRow.cloneNode(true);
            row.querySelectorAll('select').forEach((select) => {
                select.selectedIndex = 0;
            });
            row.querySelectorAll('input').forEach((input) => {
                input.value = '';
            });
            body.appendChild(row);
        });
    });
    </script>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
