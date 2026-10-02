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
    "['drug', 'medication']",
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
    'isDrugStockCategory',
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

$stockRequestService = file_get_contents(__DIR__ . '/../services/StockRequestService.php');
assertPharmacyStoreWorkflow(is_string($stockRequestService), 'Unable to read stock request service.');
assertPharmacyStoreWorkflow(
    str_contains($stockRequestService, "scope_ii.category IN ('Drug', 'Medication')")
        && str_contains($stockRequestService, "scope_ii.category NOT IN ('Drug', 'Medication')")
        && str_contains($stockRequestService, "\$scope = in_array(\$scope, ['made', 'received'], true) ? \$scope : ''")
        && str_contains($stockRequestService, "\$scope === 'made'")
        && str_contains($stockRequestService, "\$scope === 'received'")
        && str_contains($stockRequestService, 'sr.requesting_department_id <> :stock_request_scope_department_id')
        && str_contains($stockRequestService, "['drug', 'medication']"),
    'Stock request service should route legacy Medication category as drug stock and support made/received queues.'
);

$stockRequestIndex = file_get_contents(__DIR__ . '/../modules/stock_requests/index.php');
assertPharmacyStoreWorkflow(is_string($stockRequestIndex), 'Unable to read stock request index page.');
assertPharmacyStoreWorkflow(
    str_contains($stockRequestIndex, '$stockRequestService->canIssueRequest')
        && str_contains($stockRequestIndex, 'Issue Stock')
        && !str_contains($stockRequestIndex, 'action="approve.php"')
        && str_contains($stockRequestIndex, 'Pharmacy handles drug stock requests')
        && str_contains($stockRequestIndex, 'Stock Requests Received')
        && str_contains($stockRequestIndex, 'Stock Requests Made')
        && str_contains($stockRequestIndex, 'index.php?scope=received')
        && str_contains($stockRequestIndex, 'index.php?scope=made'),
    'Stock requests page should expose Issue Stock and scoped Pharmacy received/made queues without a redundant Approve button.'
);

$stockRequestView = file_get_contents(__DIR__ . '/../modules/stock_requests/view.php');
assertPharmacyStoreWorkflow(is_string($stockRequestView), 'Unable to read stock request view page.');
assertPharmacyStoreWorkflow(
    str_contains($stockRequestView, 'Issue Stock')
        && !str_contains($stockRequestView, 'action="approve.php"')
        && !str_contains($stockRequestView, '$canApprove'),
    'Stock request detail page should use Issue Stock as the workflow action and omit Approve.'
);

$permissionService = file_get_contents(__DIR__ . '/../services/PermissionService.php');
assertPharmacyStoreWorkflow(is_string($permissionService), 'Unable to read Permission service.');
foreach ([
    "['Pharmacist']",
    "\$department === 'Pharmacy'",
    'canCreateBillableItems',
    'canIssueStock',
    "canIssueStockRequest(?array \$user = null)",
    "roleMatches(\$user, ['Store Officer', 'Pharmacist'])",
    "activeDepartmentName(\$user), ['Store', 'Pharmacy']",
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
        && str_contains($sidebar, 'Price Catalogue')
        && str_contains($sidebar, 'Stock Requests Received')
        && str_contains($sidebar, 'Stock Requests Made')
        && str_contains($sidebar, 'index.php?scope=received')
        && str_contains($sidebar, 'index.php?scope=made'),
    'Sidebar should expose Price Catalogue, inventory access, and split Pharmacy stock request queues.'
);

$pharmacyStockRequestPermissionMigration = file_get_contents(__DIR__ . '/../database/migrations/082_pharmacy_stock_request_permissions_up.sql');
assertPharmacyStoreWorkflow(
    is_string($pharmacyStockRequestPermissionMigration)
        && str_contains($pharmacyStockRequestPermissionMigration, "r.role_name = 'Pharmacist'")
        && str_contains($pharmacyStockRequestPermissionMigration, "'review_stock_request', 'issue_stock_request'"),
    'Migration 082 should repair Pharmacist stock request review/issue permissions.'
);

fwrite(STDOUT, 'PASS: Pharmacy/Store stock workflow static checks passed.' . PHP_EOL);
