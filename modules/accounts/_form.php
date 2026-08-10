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
        <div class="form-group">
            <label for="item_code">Item Code</label>
            <input type="text" id="item_code" name="item_code" maxlength="30" required value="<?= e((string)($item['item_code'] ?? '')) ?>">
        </div>
        <div class="form-group">
            <label for="item_name">Item Name</label>
            <input type="text" id="item_name" name="item_name" maxlength="255" required value="<?= e((string)($item['item_name'] ?? '')) ?>">
        </div>
        <div class="form-group">
            <label for="item_type">Item Type</label>
            <select id="item_type" name="item_type" required>
                <?php foreach (['Service', 'Product'] as $type): ?>
                    <option value="<?= e($type) ?>" <?= (string)($item['item_type'] ?? 'Service') === $type ? 'selected' : '' ?>><?= e($type) ?></option>
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
        </div>
        <div class="form-group">
            <label for="unit">Unit</label>
            <input type="text" id="unit" name="unit" maxlength="50" value="<?= e((string)($item['unit'] ?? '')) ?>">
        </div>
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
