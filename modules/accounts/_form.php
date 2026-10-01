<?php

declare(strict_types=1);

$item ??= [];
$departments ??= [];
$action ??= 'save.php';
$buttonLabel ??= 'Save Item';
$selectedDepartmentId = (int)($item['department_id'] ?? 0);
?>

<form method="post" action="<?= e($action) ?>" class="card">
    <?= csrfField() ?>
    <?php if (!empty($item['id'])): ?>
        <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
    <?php endif; ?>

    <div class="form-grid">
        <?php if (!empty($item['id'])): ?>
            <div class="form-group">
                <label for="item_code">Item Code</label>
                <input type="text" id="item_code" name="item_code" maxlength="30" required value="<?= e((string)($item['item_code'] ?? '')) ?>">
            </div>
        <?php else: ?>
            <div class="form-group">
                <label>Item Code</label>
                <div class="summary-item">
                    <span class="summary-label">Auto-generated</span>
                    <span class="summary-value">Assigned when saved</span>
                </div>
                <small class="form-help">The system will generate the next unused item code for the selected department and item type.</small>
            </div>
        <?php endif; ?>
        <div class="form-group">
            <label for="item_name">Item Name</label>
            <input type="text" id="item_name" name="item_name" maxlength="255" required value="<?= e((string)($item['item_name'] ?? '')) ?>">
        </div>
        <div class="form-group">
            <label for="item_type">Item Type</label>
            <select id="item_type" name="item_type" required>
                <?php $currentItemType = (string)($item['item_type'] ?? 'Service'); ?>
                <?php $currentItemType = $currentItemType === 'Product' ? 'Consumable' : $currentItemType; ?>
                <?php foreach (['Drug', 'Consumable', 'Service'] as $type): ?>
                    <option value="<?= e($type) ?>" <?= $currentItemType === $type ? 'selected' : '' ?>><?= e($type) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="department_id">Department</label>
            <select id="department_id" name="department_id">
                <option value="">—</option>
                <?php foreach ($departments as $department): ?>
                    <option value="<?= (int)$department['id'] ?>" <?= $selectedDepartmentId === (int)$department['id'] ? 'selected' : '' ?>>
                        <?= e((string)$department['department_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="unit_price">Unit Price</label>
            <input type="number" id="unit_price" name="unit_price" min="0" step="0.01" required value="<?= e((string)($item['unit_price'] ?? '0.00')) ?>">
            <small class="form-help">Pharmacy owns stock item pricing. Stock quantities are managed through Store / Pharmacy inventory movement.</small>
        </div>
        <input type="hidden" name="unit" value="<?= e((string)($item['unit'] ?? '')) ?>">
    </div>

    <div class="form-group">
        <label for="description">Description</label>
        <textarea id="description" name="description" rows="4"><?= e((string)($item['description'] ?? '')) ?></textarea>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn-primary"><?= e($buttonLabel) ?></button>
        <a class="btn-secondary" href="<?= e(accountsBackToIndex()) ?>">Cancel</a>
    </div>
</form>
