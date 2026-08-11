<?php

declare(strict_types=1);

$item ??= [];
$departments ??= [];
$movementType ??= 'receive';
$action ??= '#';
$buttonLabel ??= 'Save';
$pageTitleLabel ??= 'Stock Movement';
$selectedDepartmentId = (int)($movement['department_id'] ?? 0);
?>

<form method="post" action="<?= e($action) ?>" class="card form-card">
    <?= csrfField() ?>
    <input type="hidden" name="inventory_item_id" value="<?= (int)($item['id'] ?? 0) ?>">

    <div class="form-grid">
        <div class="form-group">
            <label>Item</label>
            <input type="text" value="<?= e((string)($item['item_code'] ?? '')) ?> — <?= e((string)($item['item_name'] ?? '')) ?>" readonly>
        </div>
        <div class="form-group">
            <label for="quantity">Quantity</label>
            <input type="number" id="quantity" name="quantity" min="0.01" step="0.01" required value="<?= e((string)($movement['quantity'] ?? '')) ?>">
        </div>
        <?php if ($movementType !== 'receive'): ?>
            <div class="form-group">
                <label for="department_id"><?= e($movementType === 'issue' ? 'Destination Department' : 'Department') ?></label>
                <select id="department_id" name="department_id" required>
                    <option value="">Select</option>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?= (int)$department['id'] ?>" <?= $selectedDepartmentId === (int)$department['id'] ? 'selected' : '' ?>>
                            <?= e((string)$department['department_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        <?php if ($movementType === 'adjust'): ?>
            <div class="form-group">
                <label for="adjustment_mode">Adjustment Mode</label>
                <select id="adjustment_mode" name="adjustment_mode" required>
                    <option value="Increase" <?= (string)($movement['adjustment_mode'] ?? 'Increase') === 'Increase' ? 'selected' : '' ?>>Increase</option>
                    <option value="Decrease" <?= (string)($movement['adjustment_mode'] ?? '') === 'Decrease' ? 'selected' : '' ?>>Decrease</option>
                </select>
            </div>
        <?php endif; ?>
        <div class="form-group">
            <label for="reference">Reference</label>
            <input type="text" id="reference" name="reference" maxlength="255" value="<?= e((string)($movement['reference'] ?? '')) ?>">
        </div>
    </div>

    <div class="form-group">
        <label for="remarks">Remarks</label>
        <textarea id="remarks" name="remarks" rows="4"><?= e((string)($movement['remarks'] ?? '')) ?></textarea>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn-primary"><?= e($buttonLabel) ?></button>
        <a class="btn-secondary" href="<?= e(storeBackToView((int)($item['id'] ?? 0))) ?>">Cancel</a>
    </div>
</form>

