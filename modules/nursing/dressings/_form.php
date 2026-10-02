<?php

declare(strict_types=1);

$dressingRecord ??= [];
$action ??= 'save.php';
$buttonLabel ??= 'Save Dressing Record';
$dressingConfiguredFields ??= [];
$dressingConfiguredValues ??= [];
$showDressingStockUsage ??= false;
$dressingAvailableStock ??= [];
$dressingStockDepartmentId ??= 0;
$enableWritingMode ??= isset($permissionService)
    && method_exists($permissionService, 'canUseConsultationHandwriting')
    && $permissionService->canUseConsultationHandwriting($currentUser ?? null);
$stockItemRows = [];
foreach ((array)($dressingRecord['stock_inventory_item_id'] ?? []) as $index => $itemId) {
    $stockItemRows[] = [
        'stock_inventory_item_id' => (int)$itemId,
        'stock_quantity' => (string)($dressingRecord['stock_quantity'][$index] ?? ''),
        'stock_usage_reason' => (string)($dressingRecord['stock_usage_reason'][$index] ?? ''),
    ];
}
for ($i = count($stockItemRows); $i < 2; $i++) {
    $stockItemRows[] = [];
}
?>

<form method="post" action="<?= e($action) ?>" class="card" <?= $enableWritingMode ? 'data-hms-handwriting-form="1"' : '' ?>>
    <?= csrfField() ?>
    <input type="hidden" name="visit_id" value="<?= (int)$visit['id'] ?>">
    <input type="hidden" name="patient_id" value="<?= (int)$visit['patient_id'] ?>">
    <?php if (!empty($dressingRecord['id'])): ?>
        <input type="hidden" name="id" value="<?= (int)$dressingRecord['id'] ?>">
    <?php endif; ?>

    <div class="form-grid">
        <div class="form-group">
            <label for="wound_site">Wound Site</label>
            <input id="wound_site" name="wound_site" required maxlength="255" value="<?= e((string)($dressingRecord['wound_site'] ?? '')) ?>">
        </div>

        <div class="form-group">
            <label for="next_dressing_date">Next Dressing Date</label>
            <input id="next_dressing_date" name="next_dressing_date" type="date" value="<?= e((string)($dressingRecord['next_dressing_date'] ?? '')) ?>">
        </div>

        <?php hmsRenderHandwritingToolbar($enableWritingMode, 'Dressing Record Entry Mode'); ?>
        <?php hmsRenderHandwritingTextarea('wound_condition', 'Wound Condition', (string)($dressingRecord['wound_condition'] ?? ''), 4, false, $enableWritingMode); ?>
        <?php hmsRenderHandwritingTextarea('dressing_done', 'Dressing Done', (string)($dressingRecord['dressing_done'] ?? ''), 4, false, $enableWritingMode); ?>
        <?php hmsRenderHandwritingTextarea('supplies_used', 'Supplies Used', (string)($dressingRecord['supplies_used'] ?? ''), 3, false, $enableWritingMode); ?>
    </div>

    <?php if ($showDressingStockUsage): ?>
        <div class="card">
            <div class="section-header">
                <div>
                    <h3>Stock Used for Dressing</h3>
                    <p class="text-muted">Selected items will be recorded as Patient Stock Usage and deducted from this department's stock.</p>
                </div>
                <button class="btn-secondary btn-sm" type="button" data-add-dressing-stock-row>Add Stock Item</button>
            </div>
            <input type="hidden" name="stock_department_id" value="<?= (int)$dressingStockDepartmentId ?>">
            <?php if ($dressingAvailableStock === []): ?>
                <p class="text-muted">No stock is currently available in your department to attach to this dressing record.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table" id="dressing-stock-table">
                        <thead>
                            <tr>
                                <th>Inventory Item</th>
                                <th>Quantity Used</th>
                                <th>Usage Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stockItemRows as $row): ?>
                                <tr>
                                    <td>
                                        <select name="stock_inventory_item_id[]">
                                            <option value="">Select stock item</option>
                                            <?php foreach ($dressingAvailableStock as $stock): ?>
                                                <option value="<?= (int)$stock['inventory_item_id'] ?>" <?= (int)($row['stock_inventory_item_id'] ?? 0) === (int)$stock['inventory_item_id'] ? 'selected' : '' ?>>
                                                    <?= e((string)$stock['item_name']) ?> — available <?= e((string)$stock['quantity']) ?> <?= e((string)($stock['unit'] ?? '')) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td><input name="stock_quantity[]" type="number" step="1" min="1" value="<?= e((string)($row['stock_quantity'] ?? '')) ?>"></td>
                                    <td><input name="stock_usage_reason[]" maxlength="1000" value="<?= e((string)($row['stock_usage_reason'] ?? '')) ?>" placeholder="Optional note"></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <label>
                    <input type="checkbox" name="stock_request_billing" value="1" <?= !empty($dressingRecord['stock_request_billing']) ? 'checked' : '' ?>>
                    Create billing request for selected stock used
                </label>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php hmsRenderConfiguredFields($dressingConfiguredFields, $dressingConfiguredValues); ?>

    <div class="form-actions">
        <button type="submit" class="btn-primary"><?= e($buttonLabel) ?></button>
        <a class="btn-secondary" href="<?= e(dressingBackToWorkspace((int)$visit['id'])) ?>">Cancel</a>
    </div>
</form>
<?php hmsRenderHandwritingScript($enableWritingMode); ?>
<?php if ($showDressingStockUsage && $dressingAvailableStock !== []): ?>
    <script>
    document.querySelectorAll('[data-add-dressing-stock-row]').forEach((button) => {
        button.addEventListener('click', () => {
            const table = document.getElementById('dressing-stock-table');
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
                input.checked = false;
            });
            body.appendChild(row);
        });
    });
    </script>
<?php endif; ?>
