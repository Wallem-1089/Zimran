<?php

declare(strict_types=1);

if (!isset($pharmacyPrescription, $inventoryItems, $requestSource, $formAction, $buttonLabel)) {
    return;
}

$selectedInventoryItemId = (int)($pharmacyPrescription['inventory_item_id'] ?? 0);
?>

<form method="post" action="<?= e($formAction) ?>" class="card">
    <?= csrfField() ?>
    <input type="hidden" name="visit_id" value="<?= (int)$pharmacyPrescription['visit_id'] ?>">
    <input type="hidden" name="patient_id" value="<?= (int)$pharmacyPrescription['patient_id'] ?>">
    <input type="hidden" name="prescription_source" value="<?= e($requestSource) ?>">
    <?php if (isset($pharmacyPrescription['id'])): ?>
        <input type="hidden" name="id" value="<?= (int)$pharmacyPrescription['id'] ?>">
    <?php endif; ?>

    <div class="form-group">
        <label for="inventory_item_id">Medication / Inventory Item</label>
        <select id="inventory_item_id" name="inventory_item_id">
            <option value="">Select an item or leave blank for free text</option>
            <?php foreach ($inventoryItems as $item): ?>
                <?php
                    $unitPrice = $item['unit_price'] ?? null;
                    $priceLabel = $unitPrice !== null ? ' — ₦' . number_format((float)$unitPrice, 2) : '';
                    $stockLabel = isset($item['pharmacy_stock_available']) ? ' — Stock: ' . number_format((float)$item['pharmacy_stock_available'], 2) : '';
                ?>
                <option value="<?= (int)$item['id'] ?>"<?= (int)$item['id'] === $selectedInventoryItemId ? ' selected' : '' ?>>
                    <?= e((string)$item['item_name']) ?><?= e($priceLabel . $stockLabel) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label for="medication_name">Medication Name Snapshot</label>
        <input
            type="text"
            id="medication_name"
            name="medication_name"
            value="<?= e((string)($pharmacyPrescription['medication_name'] ?? '')) ?>"
            required>
    </div>

    <div class="form-group">
        <label for="dosage">Dosage</label>
        <textarea id="dosage" name="dosage" rows="2"><?= e((string)($pharmacyPrescription['dosage'] ?? '')) ?></textarea>
    </div>

    <div class="form-group">
        <label for="frequency">Frequency</label>
        <textarea id="frequency" name="frequency" rows="2"><?= e((string)($pharmacyPrescription['frequency'] ?? '')) ?></textarea>
    </div>

    <div class="form-group">
        <label for="duration">Duration</label>
        <textarea id="duration" name="duration" rows="2"><?= e((string)($pharmacyPrescription['duration'] ?? '')) ?></textarea>
    </div>

    <div class="form-group">
        <label for="quantity">Quantity</label>
        <input
            type="number"
            id="quantity"
            name="quantity"
            min="0.01"
            step="0.01"
            value="<?= e((string)($pharmacyPrescription['quantity'] ?? '')) ?>"
            required>
    </div>

    <div class="form-group">
        <label for="instructions">Instructions</label>
        <textarea id="instructions" name="instructions" rows="4"><?= e((string)($pharmacyPrescription['instructions'] ?? '')) ?></textarea>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn-primary"><?= e($buttonLabel) ?></button>
        <a class="btn-secondary" href="<?= e(pharmacyBackToWorkspace((int)$pharmacyPrescription['visit_id'])) ?>">Workspace</a>
    </div>
</form>
