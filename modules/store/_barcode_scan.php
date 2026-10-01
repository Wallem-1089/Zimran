<?php

declare(strict_types=1);

$barcodeScanId ??= 'store_barcode_scan';
$barcodeScanLabel ??= 'Scan Barcode / SKU';
$barcodeScanHelp ??= 'Scan with a keyboard-style barcode scanner, then press Enter.';
$barcodeScanTargetSelect ??= '';
$barcodeScanRedirect ??= '';
$barcodeScanDepartmentInput ??= '';
$barcodeScanRequireStock ??= false;
$barcodeScanEndpoint ??= 'barcode_lookup.php';
?>
<div class="card compact-filter store-barcode-scan"
     data-store-barcode-scan="1"
     data-lookup-endpoint="<?= e($barcodeScanEndpoint) ?>"
     data-target-select="<?= e($barcodeScanTargetSelect) ?>"
     data-redirect-template="<?= e($barcodeScanRedirect) ?>"
     data-department-input="<?= e($barcodeScanDepartmentInput) ?>"
     data-require-stock="<?= $barcodeScanRequireStock ? '1' : '0' ?>">
    <div class="form-grid">
        <div class="form-group">
            <label for="<?= e($barcodeScanId) ?>"><?= e($barcodeScanLabel) ?></label>
            <input id="<?= e($barcodeScanId) ?>" type="text" inputmode="none" autocomplete="off" placeholder="Scan or type barcode">
            <small class="text-muted"><?= e($barcodeScanHelp) ?></small>
        </div>
        <div class="form-group">
            <label>&nbsp;</label>
            <button type="button" class="btn-secondary" data-store-barcode-button="1">Lookup</button>
            <small class="text-muted" data-store-barcode-status="1"></small>
        </div>
    </div>
</div>

<script>
(function () {
    function installScanner(container) {
        const input = container.querySelector('input');
        const button = container.querySelector('[data-store-barcode-button]');
        const status = container.querySelector('[data-store-barcode-status]');
        const targetSelectId = container.getAttribute('data-target-select') || '';
        const redirectTemplate = container.getAttribute('data-redirect-template') || '';
        const departmentInputId = container.getAttribute('data-department-input') || '';
        const requireStock = container.getAttribute('data-require-stock') === '1';
        const endpoint = container.getAttribute('data-lookup-endpoint') || 'barcode_lookup.php';

        async function lookup() {
            const barcode = input.value.trim();
            if (!barcode) {
                status.textContent = 'Enter or scan a barcode first.';
                return;
            }

            status.textContent = 'Looking up barcode...';
            const params = new URLSearchParams({ barcode: barcode });
            if (requireStock) {
                params.set('require_stock', '1');
            }
            if (departmentInputId) {
                const departmentInput = document.getElementById(departmentInputId);
                if (departmentInput && departmentInput.value) {
                    params.set('department_id', departmentInput.value);
                }
            }

            try {
                const response = await fetch(endpoint + '?' + params.toString(), {
                    headers: { 'Accept': 'application/json' }
                });
                const payload = await response.json();
                if (!payload.success || !payload.item) {
                    status.textContent = (payload.errors || ['No matching item found.']).join(' ');
                    return;
                }

                const item = payload.item;
                if (targetSelectId) {
                    const select = document.getElementById(targetSelectId);
                    if (!select) {
                        status.textContent = 'Item found, but the target field is missing.';
                        return;
                    }
                    const option = Array.from(select.options).find((candidate) => candidate.value === String(item.id));
                    if (!option) {
                        status.textContent = item.item_code + ' found, but it is not available in this list.';
                        return;
                    }
                    select.value = String(item.id);
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    status.textContent = item.item_code + ' — ' + item.item_name + ' selected.';
                    input.value = '';
                    return;
                }

                if (redirectTemplate) {
                    window.location.href = redirectTemplate.replace('__ITEM_ID__', encodeURIComponent(String(item.id)));
                    return;
                }

                status.textContent = item.item_code + ' — ' + item.item_name;
            } catch (error) {
                status.textContent = 'Unable to lookup barcode.';
            }
        }

        button.addEventListener('click', lookup);
        input.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                lookup();
            }
        });
    }

    document.querySelectorAll('[data-store-barcode-scan]').forEach(installScanner);
})();
</script>
