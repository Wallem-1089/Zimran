<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
stockRequestRequireReady($stockRequestTablesReady);
stockRequestRequireView($permissionService, $currentUser);

$activeDepartmentId = (int)(
    $currentUser['active_department_id']
    ?? $_SESSION['active_department_id']
    ?? $currentUser['department_id']
    ?? 0
);
$activeDepartmentName = (string)(
    $currentUser['active_department_name']
    ?? $_SESSION['active_department_name']
    ?? $currentUser['department_name']
    ?? 'Department'
);

$selectedDepartmentId = $activeDepartmentId;
$departments = stockRequestDepartments($pdo);

if ($permissionService->isAdministrator($currentUser)) {
    $requestedDepartmentId = filter_input(INPUT_GET, 'department', FILTER_VALIDATE_INT) ?: 0;
    if ($requestedDepartmentId > 0) {
        $selectedDepartmentId = $requestedDepartmentId;
    }
}

if ($selectedDepartmentId <= 0) {
    http_response_code(400);
    exit('No active department is available for this account.');
}

$selectedDepartmentName = $activeDepartmentName;
foreach ($departments as $department) {
    if ((int)$department['id'] === $selectedDepartmentId) {
        $selectedDepartmentName = (string)$department['department_name'];
        break;
    }
}

$viewMode = (string)($_GET['view'] ?? 'summary');
if (!in_array($viewMode, ['summary', 'stock', 'ledger'], true)) {
    $viewMode = 'summary';
}

$departmentQuery = $permissionService->isAdministrator($currentUser)
    ? '&department=' . $selectedDepartmentId
    : '';
$summaryUrl = 'my_department_stock.php' . ($departmentQuery !== '' ? '?' . ltrim($departmentQuery, '&') : '');
$stockUrl = 'my_department_stock.php?view=stock' . $departmentQuery;
$ledgerUrl = 'my_department_stock.php?view=ledger' . $departmentQuery;

$balances = $storeService->listDepartmentStock($selectedDepartmentId, $currentUser);
$ledgerLimit = $viewMode === 'ledger' ? 200 : 75;
$ledger = $storeService->listDepartmentLedger($selectedDepartmentId, $currentUser, $ledgerLimit);

$receivedRows = array_values(array_filter(
    $ledger,
    static fn (array $row): bool => (string)($row['transaction_type'] ?? '') === 'Issue'
        && (int)($row['to_department_id'] ?? 0) === $selectedDepartmentId
));
$consumedRows = array_values(array_filter(
    $ledger,
    static fn (array $row): bool => (string)($row['transaction_type'] ?? '') === 'Consumption'
        && (int)($row['from_department_id'] ?? 0) === $selectedDepartmentId
));

$balanceRows = array_slice($balances, 0, 50);
$recentRowLimit = $viewMode === 'summary' ? 20 : 50;
$receivedRows = array_slice($receivedRows, 0, $recentRowLimit);
$consumedRows = array_slice($consumedRows, 0, $recentRowLimit);

$pageTitle = 'My Department Stock';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div>
            <h1>My Department Stock</h1>
            <p><?= e($selectedDepartmentName) ?> stock balances, received items, and patient usage history.</p>
        </div>
        <div class="form-actions">
            <a class="btn-secondary" href="index.php">Stock Requests</a>
            <?php if ($viewMode === 'summary'): ?>
                <a class="btn-secondary" href="<?= e($stockUrl) ?>">View Full Department Stock</a>
                <a class="btn-secondary" href="<?= e($ledgerUrl) ?>">View Stock Ledger</a>
            <?php else: ?>
                <a class="btn-secondary" href="<?= e($summaryUrl) ?>">View Summary</a>
            <?php endif; ?>
            <?php if ($permissionService->canCreateStockRequest($currentUser)): ?>
                <a class="btn-primary" href="create.php">Request Stock</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($permissionService->isAdministrator($currentUser)): ?>
        <form method="get" class="card">
            <div class="form-grid">
                <div class="form-group">
                    <label for="department">Department</label>
                    <select id="department" name="department">
                        <?php foreach ($departments as $department): ?>
                            <option value="<?= (int)$department['id'] ?>" <?= $selectedDepartmentId === (int)$department['id'] ? 'selected' : '' ?>>
                                <?= e((string)$department['department_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-actions">
                <button class="btn-primary" type="submit">View Department</button>
            </div>
        </form>
    <?php endif; ?>

    <section class="card">
        <div class="card-header">
            <div>
                <h2>Current Department Balance</h2>
                <p>Current quantity held by this department. Balances are maintained from stock ledger movements.</p>
                <?php if (count($balances) > count($balanceRows)): ?>
                    <p class="text-muted">Showing first <?= count($balanceRows) ?> of <?= count($balances) ?> balances.</p>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($balanceRows === []): ?>
            <div class="empty-state">No stock balance is recorded for this department.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Item Code</th>
                            <th>Item</th>
                            <th>Category</th>
                            <th>Quantity</th>
                            <th>Last Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($balanceRows as $balance): ?>
                            <tr>
                                <td><?= e((string)$balance['item_code']) ?></td>
                                <td><?= e((string)$balance['item_name']) ?></td>
                                <td><?= e((string)$balance['category']) ?></td>
                                <td><?= e((string)$balance['quantity']) ?> <?= e((string)($balance['unit'] ?? '')) ?></td>
                                <td><?= e((string)($balance['updated_at'] ?? '-')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="card">
        <div class="card-header">
            <div>
                <h2>Recently Received From Store</h2>
                <p>Latest <?= (int)$recentRowLimit ?> Store issue transactions where this department received stock.</p>
            </div>
        </div>

        <?php if ($receivedRows === []): ?>
            <div class="empty-state">No recent Store issues received by this department.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Item</th>
                            <th>Quantity Received</th>
                            <th>Issued By</th>
                            <th>Reference</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($receivedRows as $row): ?>
                            <tr>
                                <td><?= e((string)($row['created_at'] ?? '-')) ?></td>
                                <td><?= e((string)($row['item_name'] ?? '-')) ?></td>
                                <td><?= e((string)$row['quantity']) ?> <?= e((string)($row['unit'] ?? '')) ?></td>
                                <td><?= e((string)($row['performed_by_name'] ?? '-')) ?></td>
                                <td><?= e((string)($row['reference'] ?? '-')) ?></td>
                                <td><?= e((string)($row['remarks'] ?? '-')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="card">
        <div class="card-header">
            <div>
                <h2>Recently Used On Patients</h2>
                <p>Latest <?= (int)$recentRowLimit ?> consumption transactions recorded when department stock was used for patient care.</p>
            </div>
        </div>

        <?php if ($consumedRows === []): ?>
            <div class="empty-state">No patient stock usage has been recorded for this department.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Item</th>
                            <th>Quantity Used</th>
                            <th>Recorded By</th>
                            <th>Reference</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($consumedRows as $row): ?>
                            <tr>
                                <td><?= e((string)($row['created_at'] ?? '-')) ?></td>
                                <td><?= e((string)($row['item_name'] ?? '-')) ?></td>
                                <td><?= e((string)$row['quantity']) ?> <?= e((string)($row['unit'] ?? '')) ?></td>
                                <td><?= e((string)($row['performed_by_name'] ?? '-')) ?></td>
                                <td><?= e((string)($row['reference'] ?? '-')) ?></td>
                                <td><?= e((string)($row['remarks'] ?? '-')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($viewMode === 'ledger'): ?>
        <section class="card">
            <div class="card-header">
                <div>
                    <h2>Stock Ledger</h2>
                    <p>Latest <?= count($ledger) ?> stock movements for <?= e($selectedDepartmentName) ?>. Use the Store ledger for wider investigation.</p>
                </div>
            </div>

            <?php if ($ledger === []): ?>
                <div class="empty-state">No stock ledger activity is recorded for this department.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Item</th>
                                <th>Quantity</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Performed By</th>
                                <th>Reference</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ledger as $row): ?>
                                <tr>
                                    <td><?= e((string)($row['created_at'] ?? '-')) ?></td>
                                    <td><?= e((string)($row['transaction_type'] ?? '-')) ?></td>
                                    <td><?= e((string)($row['item_name'] ?? '-')) ?></td>
                                    <td><?= e((string)($row['quantity'] ?? '-')) ?> <?= e((string)($row['unit'] ?? '')) ?></td>
                                    <td><?= e((string)($row['from_department_name'] ?? '-')) ?></td>
                                    <td><?= e((string)($row['to_department_name'] ?? '-')) ?></td>
                                    <td><?= e((string)($row['performed_by_name'] ?? '-')) ?></td>
                                    <td><?= e((string)($row['reference'] ?? '-')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
