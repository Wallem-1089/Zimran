<?php

declare(strict_types=1);

function assertPharmacyStoreWorkflow(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$storeForm = file_get_contents(__DIR__ . '/../modules/store/_form.php');
assertPharmacyStoreWorkflow(is_string($storeForm), 'Unable to read Store inventory form.');
assertPharmacyStoreWorkflow(
    str_contains($storeForm, "'Drug', 'Consumable'")
        && str_contains($storeForm, '<select id="category" name="category" required>'),
    'Stock category should be a Drug/Consumable dropdown.'
);

$storeIndex = file_get_contents(__DIR__ . '/../modules/store/index.php');
assertPharmacyStoreWorkflow(is_string($storeIndex), 'Unable to read Store inventory index.');
assertPharmacyStoreWorkflow(
    str_contains($storeIndex, "'Drug', 'Consumable'")
        && str_contains($storeIndex, '<select id="category" name="category">'),
    'Inventory category filter should use the Drug/Consumable dropdown.'
);

$stockRequestBootstrap = file_get_contents(__DIR__ . '/../modules/stock_requests/_bootstrap.php');
assertPharmacyStoreWorkflow(is_string($stockRequestBootstrap), 'Unable to read stock request bootstrap.');
assertPharmacyStoreWorkflow(
    str_contains($stockRequestBootstrap, 'item_code, item_name, unit, category'),
    'Stock request inventory options should expose item category for request routing.'
);

$stockRequestCreate = file_get_contents(__DIR__ . '/../modules/stock_requests/create.php');
assertPharmacyStoreWorkflow(is_string($stockRequestCreate), 'Unable to read stock request create page.');
foreach ([
    'Drug Stock Request',
    'Consumable / Non-drug Stock Request',
    'Drug requests go to Pharmacy',
    'consumable/non-drug requests go to Store',
    'data-add-stock-row="drug-stock-request-table"',
    'data-add-stock-row="consumable-stock-request-table"',
    'Store only supplies drug stock into Pharmacy',
] as $needle) {
    assertPharmacyStoreWorkflow(
        str_contains($stockRequestCreate, $needle),
        'Stock request create page is missing split routing UI: ' . $needle
    );
}

$storeService = file_get_contents(__DIR__ . '/../services/StoreService.php');
assertPharmacyStoreWorkflow(is_string($storeService), 'Unable to read Store service.');
foreach ([
    'Store can only issue drug stock to Pharmacy',
    'Pharmacy can only issue drug stock onward to another requesting department',
    'Consumable stock should be issued directly by Store to the requesting department',
    'getPharmacyDepartmentId',
    'Category must be Drug or Consumable',
    'strcasecmp($activeDepartmentName, \'Pharmacy\')',
    'listStockLedger',
] as $needle) {
    assertPharmacyStoreWorkflow(
        str_contains($storeService, $needle),
        'Store service is missing workflow rule: ' . $needle
    );
}

$permissionService = file_get_contents(__DIR__ . '/../services/PermissionService.php');
assertPharmacyStoreWorkflow(is_string($permissionService), 'Unable to read Permission service.');
foreach ([
    "['Pharmacist']",
    "\$department === 'Pharmacy'",
    'canCreateBillableItems',
    'canIssueStock',
    'canViewStockLedger',
] as $needle) {
    assertPharmacyStoreWorkflow(
        str_contains($permissionService, $needle),
        'Permission service is missing Pharmacy ownership support: ' . $needle
    );
}

$storeLedgerPage = file_get_contents(__DIR__ . '/../modules/store/ledger.php');
assertPharmacyStoreWorkflow(is_string($storeLedgerPage), 'Unable to read Store ledger page.');
assertPharmacyStoreWorkflow(
    str_contains($storeLedgerPage, '$showFullHistory')
        && str_contains($storeLedgerPage, 'Show Recent 200')
        && str_contains($storeLedgerPage, 'Full History')
        && str_contains($storeLedgerPage, '$showFullHistory ? 0 : 200'),
    'Store ledger should show full matching history by default with a recent-history toggle.'
);

$departmentStockPage = file_get_contents(__DIR__ . '/../modules/stock_requests/my_department_stock.php');
assertPharmacyStoreWorkflow(is_string($departmentStockPage), 'Unable to read department stock page.');
assertPharmacyStoreWorkflow(
    str_contains($departmentStockPage, '$ledgerLimit = $viewMode === \'ledger\' ? 0 : 75')
        && str_contains($departmentStockPage, 'All <?= count($ledger) ?> stock movement'),
    'Department stock ledger view should show the full department movement history.'
);

$accountsForm = file_get_contents(__DIR__ . '/../modules/accounts/_form.php');
assertPharmacyStoreWorkflow(is_string($accountsForm), 'Unable to read price catalogue form.');
assertPharmacyStoreWorkflow(
    str_contains($accountsForm, 'Pharmacy owns stock item pricing'),
    'Price catalogue form should explain Pharmacy-owned pricing.'
);

$sidebar = file_get_contents(__DIR__ . '/../layouts/sidebar.php');
assertPharmacyStoreWorkflow(is_string($sidebar), 'Unable to read sidebar.');
assertPharmacyStoreWorkflow(
    str_contains($sidebar, "['Accountant', 'Accounts', 'Pharmacist']")
        && str_contains($sidebar, "['Store Officer', 'Pharmacist']")
        && str_contains($sidebar, 'Price Catalogue'),
    'Sidebar should expose Price Catalogue and inventory access to Pharmacy.'
);

fwrite(STDOUT, 'PASS: Pharmacy/Store stock workflow static checks passed.' . PHP_EOL);
