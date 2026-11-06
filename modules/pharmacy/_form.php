<?php

declare(strict_types=1);

if (!isset($pharmacyPrescription, $inventoryItems, $requestSource, $formAction, $buttonLabel)) {
    return;
}

$selectedInventoryItemIds = $pharmacyPrescription['inventory_item_ids']
    ?? ($pharmacyPrescription['inventory_item_id'] ?? null);
$requestSourceNote ??= 'Clinical prescriptions are linked to this encounter without transferring ownership.';
$showInventorySelection ??= true;
$enableWritingMode ??= isset($permissionService)
    && method_exists($permissionService, 'canUseConsultationHandwriting')
    && $permissionService->canUseConsultationHandwriting($currentUser ?? null);
?>

<form method="post" action="<?= e($formAction) ?>" class="card" <?= $enableWritingMode ? 'data-hms-handwriting-form="1"' : '' ?>>
    <?= csrfField() ?>
    <input type="hidden" name="visit_id" value="<?= (int)$pharmacyPrescription['visit_id'] ?>">
    <input type="hidden" name="patient_id" value="<?= (int)$pharmacyPrescription['patient_id'] ?>">
    <input type="hidden" name="prescription_source" value="<?= e($requestSource) ?>">
    <?php if (isset($pharmacyPrescription['id'])): ?>
        <input type="hidden" name="id" value="<?= (int)$pharmacyPrescription['id'] ?>">
    <?php endif; ?>

    <div class="form-group">
        <label>Prescription Source</label>
        <div class="readonly-field"><?= e($requestSource) ?></div>
        <p class="text-muted"><?= e($requestSourceNote) ?></p>
    </div>

    <?php
    $listValue = static function (mixed $value): array {
        if (is_array($value)) {
            return array_values($value);
        }
        return $value === null || $value === '' ? [] : [$value];
    };
    $inventoryIds = $listValue($pharmacyPrescription['inventory_item_ids'] ?? null);
    if ($inventoryIds === [] && (int)($pharmacyPrescription['inventory_item_id'] ?? 0) > 0) {
        $inventoryIds = [$pharmacyPrescription['inventory_item_id']];
    }
    $billableIds = $listValue($pharmacyPrescription['suggested_billable_item_ids'] ?? null);
    if ($billableIds === [] && (int)($pharmacyPrescription['suggested_billable_item_id'] ?? 0) > 0) {
        $billableIds = [$pharmacyPrescription['suggested_billable_item_id']];
    }
    $rowCount = max(
        1,
        count($inventoryIds),
        count($listValue($pharmacyPrescription['medication_name'] ?? null)),
        count($listValue($pharmacyPrescription['dosage'] ?? null)),
        count($listValue($pharmacyPrescription['frequency'] ?? null)),
        count($listValue($pharmacyPrescription['duration'] ?? null)),
        count($listValue($pharmacyPrescription['quantity'] ?? null)),
        count($listValue($pharmacyPrescription['instructions'] ?? null))
    );
    $medicationNames = $listValue($pharmacyPrescription['medication_name'] ?? null);
    $dosages = $listValue($pharmacyPrescription['dosage'] ?? null);
    $frequencies = $listValue($pharmacyPrescription['frequency'] ?? null);
    $durations = $listValue($pharmacyPrescription['duration'] ?? null);
    $quantities = $listValue($pharmacyPrescription['quantity'] ?? null);
    $instructions = $listValue($pharmacyPrescription['instructions'] ?? null);
    $inventoryOptions = '<option value="">Select an item or leave blank for free text</option>';
    foreach ($inventoryItems as $item) {
        $itemId = (int)($item['id'] ?? 0);
        $itemText = trim((string)($item['item_code'] ?? '') . ' - ' . (string)($item['item_name'] ?? ''));
        $price = isset($item['unit_price']) ? ' — ₦' . number_format((float)$item['unit_price'], 2) : '';
        $stock = isset($item['pharmacy_stock_available']) ? ' — Stock: ' . number_format((float)$item['pharmacy_stock_available'], 0) : '';
        $linkedBillableId = (int)($item['billable_item_id'] ?? 0);
        $inventoryOptions .= '<option value="' . $itemId . '" data-billable-item-id="' . $linkedBillableId . '" data-item-name="' . e((string)($item['item_name'] ?? '')) . '">' . e($itemText . $price . $stock) . '</option>';
    }
    $billableOptions = '<option value="">Select billable item</option>';
    foreach (($billableItemOptions ?? []) as $item) {
        $itemId = (int)($item['id'] ?? 0);
        $itemText = trim((string)($item['item_code'] ?? '') . ' - ' . (string)($item['item_name'] ?? ''));
        $billableOptions .= '<option value="' . $itemId . '" data-item-name="' . e((string)($item['item_name'] ?? '')) . '">' . e($itemText . ' (₦' . number_format((float)($item['unit_price'] ?? 0), 2) . ')') . '</option>';
    }
    $templateId = 'pharmacy-medication-row-' . bin2hex(random_bytes(3));
    $billableTemplateId = 'pharmacy-billable-row-' . bin2hex(random_bytes(3));
    ?>

    <?php hmsRenderHandwritingToolbar($enableWritingMode, 'Prescription Entry Mode'); ?>
    <?php if ($requestSource === 'Clinical'): ?>
        <div class="card" data-pharmacy-billable-picker style="margin-bottom:1rem;">
            <h3>Billable Items for This Prescription Request</h3>
            <div data-pharmacy-billable-rows>
                <?php for ($billableIndex = 0; $billableIndex < max(1, count($billableIds)); $billableIndex++): ?>
                    <div class="pharmacy-billable-row" data-pharmacy-billable-row style="display:flex;gap:.5rem;align-items:center;margin-bottom:.5rem;">
                        <input type="search" data-billable-search placeholder="Search billable item code or name" autocomplete="off" style="flex:1;min-width:0;">
                        <select name="suggested_billable_item_ids[]" data-billable-select required style="flex:2;min-width:0;">
                            <?= str_replace('value="' . (int)($billableIds[$billableIndex] ?? 0) . '"', 'value="' . (int)($billableIds[$billableIndex] ?? 0) . '" selected', $billableOptions) ?>
                        </select>
                        <button type="button" class="btn-secondary" data-remove-pharmacy-billable title="Remove billable item" style="border-radius:999px;width:2rem;height:2rem;padding:0;<?= $billableIndex === 0 ? 'visibility:hidden;' : '' ?>">&times;</button>
                    </div>
                <?php endfor; ?>
            </div>
            <button type="button" class="btn-primary" data-add-pharmacy-billable title="Add another billable item" style="border-radius:999px;width:2.75rem;height:2.75rem;padding:0;font-size:1.5rem;line-height:1;">+</button>
            <small class="form-help">These items are billed by Accounts and remain attached to this single prescription request.</small>
            <template id="<?= e($billableTemplateId) ?>">
                <div class="pharmacy-billable-row" data-pharmacy-billable-row style="display:flex;gap:.5rem;align-items:center;margin-bottom:.5rem;">
                    <input type="search" data-billable-search placeholder="Search billable item code or name" autocomplete="off" style="flex:1;min-width:0;">
                    <select name="suggested_billable_item_ids[]" data-billable-select required style="flex:2;min-width:0;"><?= $billableOptions ?></select>
                    <button type="button" class="btn-secondary" data-remove-pharmacy-billable title="Remove billable item" style="border-radius:999px;width:2rem;height:2rem;padding:0;">&times;</button>
                </div>
            </template>
        </div>
    <?php endif; ?>
    <div class="pharmacy-medication-picker" data-pharmacy-medication-picker>
        <div data-pharmacy-medication-rows>
            <?php for ($index = 0; $index < $rowCount; $index++): ?>
                <div class="card pharmacy-medication-row" data-pharmacy-medication-row style="margin-bottom:1rem;">
                    <div class="form-grid">
                        <?php if ($showInventorySelection): ?><div class="form-group">
                            <label>Medication / Inventory Item</label>
                                <div style="display:flex;gap:.5rem;align-items:center"><input type="search" data-medication-search placeholder="Search item code or name" autocomplete="off" aria-label="Search medication or inventory item" style="flex:1;min-width:0"><button type="button" class="btn-secondary" data-medication-search-button style="white-space:nowrap">Search</button></div>
                            <select name="inventory_item_ids[]" data-medication-select style="width:100%;margin-top:.25rem;">
                                <?= str_replace('value="' . (int)($inventoryIds[$index] ?? 0) . '"', 'value="' . (int)($inventoryIds[$index] ?? 0) . '" selected', $inventoryOptions) ?>
                            </select>
                            <small class="form-help" data-medication-empty hidden>No matching medication or inventory items.</small>
                        </div><?php endif; ?>
                        <div class="form-group">
                            <label>Medication Name Snapshot</label>
                            <input type="text" name="medication_name[]" value="<?= e((string)($medicationNames[$index] ?? '')) ?>" maxlength="255">
                            <small class="form-help">Optional. The selected billable item supplies the requested drug/service name.</small>
                        </div>
                        <div class="form-group"><label>Dosage</label><input type="text" name="dosage[]" value="<?= e((string)($dosages[$index] ?? '')) ?>" placeholder="e.g. 500 mg"></div>
                        <div class="form-group"><label>Frequency</label><input type="text" name="frequency[]" value="<?= e((string)($frequencies[$index] ?? '')) ?>" placeholder="e.g. Twice daily"></div>
                        <div class="form-group"><label>Duration</label><input type="text" name="duration[]" value="<?= e((string)($durations[$index] ?? '')) ?>" placeholder="e.g. 5 days"></div>
                        <div class="form-group"><label>Quantity <span class="required">*</span></label><input type="number" name="quantity[]" min="1" step="1" value="<?= e((string)($quantities[$index] ?? '')) ?>" required></div>
                        <div class="form-group"><label>Instructions</label><textarea name="instructions[]" rows="3"><?= e((string)($instructions[$index] ?? '')) ?></textarea></div>
                    </div>
                    <button type="button" class="btn-secondary" data-remove-pharmacy-medication title="Remove medication" style="border-radius:999px;width:2rem;height:2rem;padding:0;<?= $index === 0 ? 'visibility:hidden;' : '' ?>">&times;</button>
                </div>
            <?php endfor; ?>
        </div>
        <button type="button" class="btn-primary" data-add-pharmacy-medication title="Add another medication" style="border-radius:999px;width:2.75rem;height:2.75rem;padding:0;font-size:1.5rem;line-height:1;">+</button>
        <small class="form-help"><?= $showInventorySelection ? 'Add one row for each medication. Each row has its own dosage, frequency, duration, quantity, instructions, and billable item.' : 'Add one row for each medication request. Pharmacy will select the available inventory item when dispensing.' ?></small>
        <template id="<?= e($templateId) ?>">
            <div class="card pharmacy-medication-row" data-pharmacy-medication-row style="margin-bottom:1rem;">
                <div class="form-grid">
                    <?php if ($showInventorySelection): ?><div class="form-group"><label>Medication / Inventory Item</label><div style="display:flex;gap:.5rem;align-items:center"><input type="search" data-medication-search placeholder="Search item code or name" autocomplete="off" style="flex:1;min-width:0"><button type="button" class="btn-secondary" data-medication-search-button style="white-space:nowrap">Search</button></div><select name="inventory_item_ids[]" data-medication-select style="width:100%;margin-top:.25rem;"><?= $inventoryOptions ?></select><small class="form-help" data-medication-empty hidden>No matching medication or inventory items.</small></div><?php endif; ?>
                    <div class="form-group"><label>Medication Name Snapshot</label><input type="text" name="medication_name[]" maxlength="255"><small class="form-help">Optional. The selected billable item supplies the requested drug/service name.</small></div>
                    <div class="form-group"><label>Dosage</label><input type="text" name="dosage[]" placeholder="e.g. 500 mg"></div>
                    <div class="form-group"><label>Frequency</label><input type="text" name="frequency[]" placeholder="e.g. Twice daily"></div>
                    <div class="form-group"><label>Duration</label><input type="text" name="duration[]" placeholder="e.g. 5 days"></div>
                    <div class="form-group"><label>Quantity <span class="required">*</span></label><input type="number" name="quantity[]" min="1" step="1" required></div>
                    <div class="form-group"><label>Instructions</label><textarea name="instructions[]" rows="3"></textarea></div>
                </div>
                <button type="button" class="btn-secondary" data-remove-pharmacy-medication title="Remove medication" style="border-radius:999px;width:2rem;height:2rem;padding:0;">&times;</button>
            </div>
        </template>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn-primary"><?= e($buttonLabel) ?></button>
        <a class="btn-secondary" href="<?= e(pharmacyBackToWorkspace((int)$pharmacyPrescription['visit_id'])) ?>">Workspace</a>
    </div>
</form>

<script>
(function () {
    var root = document.querySelector('[data-pharmacy-medication-picker]');
    if (!root) return;
    var billableRoot = document.querySelector('[data-pharmacy-billable-picker]');
    var rows = root.querySelector('[data-pharmacy-medication-rows]');
    var template = root.querySelector('template');
    var add = root.querySelector('[data-add-pharmacy-medication]');

    function filter(select, input, empty, chooseFirst) {
        if (!select || !input) return;
        var term = (input.value || '').trim().toLowerCase();
        var matches = 0;
        var firstMatch = null;
        Array.prototype.forEach.call(select.options, function (option, index) {
            if (index === 0) {
                option.hidden = false;
                option.disabled = false;
                return;
            }
            var match = term === '' || option.textContent.toLowerCase().indexOf(term) !== -1;
            option.hidden = !match;
            option.disabled = !match;
            if (match) {
                matches++;
                if (!firstMatch) firstMatch = option;
            }
        });
        if (empty) empty.hidden = term === '' || matches > 0;
        if (chooseFirst && firstMatch) {
            select.value = firstMatch.value;
            select.dispatchEvent(new Event('change', {bubbles: true}));
        }
    }

    function filterRow(row) {
        filter(row.querySelector('[data-medication-select]'), row.querySelector('[data-medication-search]'), row.querySelector('[data-medication-empty]'), false);
    }

    function filterAll() {
        Array.prototype.forEach.call(rows.querySelectorAll('[data-pharmacy-medication-row]'), filterRow);
    }

    if (add && rows && template) {
        add.addEventListener('click', function () {
            rows.appendChild(template.content.cloneNode(true));
        });
    }

    root.addEventListener('input', function (event) {
        if (event.target.matches('[data-medication-search]')) {
            var medicationRow = event.target.closest('[data-pharmacy-medication-row]');
            if (medicationRow) filterRow(medicationRow);
        }
    });

    root.addEventListener('click', function (event) {
        var medicationButton = event.target.closest('[data-medication-search-button]');
        var row = medicationButton && medicationButton.closest('[data-pharmacy-medication-row]');
        if (!row && !medicationButton) return;
        if (medicationButton) {
            filter(row.querySelector('[data-medication-select]'), row.querySelector('[data-medication-search]'), row.querySelector('[data-medication-empty]'), true);
        }
    });

    if (billableRoot) {
        var billableRows = billableRoot.querySelector('[data-pharmacy-billable-rows]');
        var billableTemplate = billableRoot.querySelector('template');
        var addBillable = billableRoot.querySelector('[data-add-pharmacy-billable]');
        var filterBillableRow = function (row, chooseFirst) {
            filter(row.querySelector('[data-billable-select]'), row.querySelector('[data-billable-search]'), null, chooseFirst);
        };
        if (addBillable && billableRows && billableTemplate) {
            addBillable.addEventListener('click', function () {
                billableRows.appendChild(billableTemplate.content.cloneNode(true));
            });
        }
        billableRoot.addEventListener('input', function (event) {
            if (event.target.matches('[data-billable-search]')) {
                var billableRow = event.target.closest('[data-pharmacy-billable-row]');
                if (billableRow) filterBillableRow(billableRow, false);
            }
        });
        billableRoot.addEventListener('click', function (event) {
            var searchButton = event.target.closest('[data-billable-search-button]');
            var removeButton = event.target.closest('[data-remove-pharmacy-billable]');
            if (searchButton) filterBillableRow(searchButton.closest('[data-pharmacy-billable-row]'), true);
            if (removeButton && billableRows.querySelectorAll('[data-pharmacy-billable-row]').length > 1) removeButton.closest('[data-pharmacy-billable-row]').remove();
        });
        billableRoot.addEventListener('change', function (event) {
            if (!event.target.matches('[data-billable-select]')) return;
            var selected = event.target.options[event.target.selectedIndex];
            var medicationRows = rows.querySelectorAll('[data-pharmacy-medication-row]');
            if (medicationRows.length !== 1) return;
            var nameInput = medicationRows[0].querySelector('input[name="medication_name[]"]');
            var itemName = selected && selected.getAttribute('data-item-name');
            if (nameInput && itemName) nameInput.value = itemName;
        });
    }

    root.addEventListener('change', function (event) {
        if (!event.target.matches('[data-medication-select]')) return;
        var row = event.target.closest('[data-pharmacy-medication-row]');
        var option = event.target.options[event.target.selectedIndex];
        var nameInput = row && row.querySelector('input[name="medication_name[]"]');
        if (nameInput && option && option.value && !nameInput.value.trim()) {
            nameInput.value = option.getAttribute('data-item-name') || option.textContent.split(' — ')[0].trim();
        }
        var linkedBillableId = option && option.getAttribute('data-billable-item-id');
    });

    root.addEventListener('click', function (event) {
        var button = event.target.closest('[data-remove-pharmacy-medication]');
        if (!button) return;
        var row = button.closest('[data-pharmacy-medication-row]');
        if (row && rows.querySelectorAll('[data-pharmacy-medication-row]').length > 1) row.remove();
    });

    filterAll();
}());
</script>

<?php hmsRenderHandwritingScript($enableWritingMode); ?>
