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

$storeService = file_get_contents(__DIR__ . '/../services/StoreService.php');
assertPharmacyStoreWorkflow(is_string($storeService), 'Unable to read Store service.');
foreach ([
    'Store can only issue stock to Pharmacy',
    'Pharmacy can only issue stock onward to another requesting department',
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
