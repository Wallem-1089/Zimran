<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
stockRequestRequireReady($stockRequestTablesReady);
stockRequestRequireView($permissionService, $currentUser);

$status = trim((string)($_GET['status'] ?? ''));
$scope = trim((string)($_GET['scope'] ?? ''));
$scope = in_array($scope, ['made', 'received'], true) ? $scope : '';
$requests = $stockRequestService->listRequests(['status' => $status, 'scope' => $scope], $currentUser);
$activeDepartmentName = (string)(
    $currentUser['active_department_name']
    ?? $_SESSION['active_department_name']
    ?? $currentUser['department_name']
    ?? ''
);
$isPharmacyStockRequestView = strcasecmp($activeDepartmentName, 'Pharmacy') === 0;
$stockRequestHeading = 'Stock Requests';
$stockRequestDescription = 'Departments request stock here. Pharmacy handles drug stock requests; Store handles consumable/non-drug stock requests.';
if ($isPharmacyStockRequestView && $scope === 'received') {
    $stockRequestHeading = 'Stock Requests Received';
    $stockRequestDescription = 'Drug stock requests from other departments for Pharmacy issuing.';
} elseif ($isPharmacyStockRequestView && $scope === 'made') {
    $stockRequestHeading = 'Stock Requests Made';
    $stockRequestDescription = 'Stock requests made by Pharmacy to Store.';
}

$pageTitle = $stockRequestHeading;
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <?php if (isset($_SESSION['success_message'])): ?><div class="alert-success"><?= e((string)$_SESSION['success_message']) ?></div><?php unset($_SESSION['success_message']); endif; ?>
    <?php if (isset($_SESSION['validation_errors'])): ?><div class="alert-danger"><ul><?php foreach ((array)$_SESSION['validation_errors'] as $error): ?><li><?= e((string)$error) ?></li><?php endforeach; ?></ul></div><?php unset($_SESSION['validation_errors']); endif; ?>

    <div class="page-header">
        <div>
            <h1><?= e($stockRequestHeading) ?></h1>
            <p><?= e($stockRequestDescription) ?></p>
        </div>
        <div class="form-actions">
            <?php if ($isPharmacyStockRequestView): ?>
                <a class="<?= $scope === 'received' ? 'btn-primary' : 'btn-secondary' ?>" href="index.php?scope=received">Stock Requests Received</a>
                <a class="<?= $scope === 'made' ? 'btn-primary' : 'btn-secondary' ?>" href="index.php?scope=made">Stock Requests Made</a>
            <?php endif; ?>
            <a class="btn-secondary" href="my_department_stock.php">My Department Stock</a>
            <?php if ($permissionService->canCreateStockRequest($currentUser)): ?>
                <a class="btn-primary" href="create.php">New Stock Request</a>
            <?php endif; ?>
        </div>
    </div>

    <form class="card" method="get">
        <?php if ($scope !== ''): ?>
            <input type="hidden" name="scope" value="<?= e($scope) ?>">
        <?php endif; ?>
        <div class="form-grid">
            <label>Status
                <select name="status">
                    <option value="">All</option>
                    <?php foreach (['Pending','Approved','Partially Issued','Issued','Cancelled'] as $option): ?>
                        <option value="<?= e($option) ?>" <?= $status === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <div class="form-actions">
            <button class="btn-primary" type="submit">Filter</button>
            <a class="btn-secondary" href="index.php<?= $scope !== '' ? '?scope=' . e($scope) : '' ?>">Reset</a>
        </div>
    </form>

    <div class="card">
        <?php if ($requests === []): ?>
            <div class="empty-state">No stock requests found.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Department</th>
                            <th>Requested By</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Reviewed By</th>
                            <th class="no-print">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($requests as $request): ?>
                        <?php
                            $requestDetail = $stockRequestService->getRequestById((int)$request['id'], $currentUser);
                            $canIssue = $requestDetail !== null
                                && $stockRequestService->canIssueRequest($requestDetail, $currentUser);
                        ?>
                        <tr>
                            <td>#<?= (int)$request['id'] ?></td>
                            <td><?= e((string)$request['requesting_department_name']) ?></td>
                            <td><?= e((string)($request['requested_by_name'] ?? '-')) ?></td>
                            <td><?= e((string)$request['status']) ?></td>
                            <td><?= e((string)($request['created_at'] ?? '-')) ?></td>
                            <td><?= e((string)($request['reviewed_by_name'] ?? '-')) ?></td>
                            <td class="no-print">
                                <div class="form-actions">
                                    <a class="btn-secondary btn-sm" href="view.php?id=<?= (int)$request['id'] ?>">View</a>
                                    <?php if ($canIssue): ?>
                                        <a class="btn-primary btn-sm" href="issue.php?id=<?= (int)$request['id'] ?>">Issue Stock</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
