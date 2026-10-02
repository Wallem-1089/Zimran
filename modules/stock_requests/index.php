<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
stockRequestRequireReady($stockRequestTablesReady);
stockRequestRequireView($permissionService, $currentUser);

$status = trim((string)($_GET['status'] ?? ''));
$requests = $stockRequestService->listRequests(['status' => $status], $currentUser);

$pageTitle = 'Stock Requests';
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
            <h1>Stock Requests</h1>
            <p>Departments request stock here. Pharmacy handles drug stock requests; Store handles consumable/non-drug stock requests.</p>
        </div>
        <div class="form-actions">
            <a class="btn-secondary" href="my_department_stock.php">My Department Stock</a>
            <?php if ($permissionService->canCreateStockRequest($currentUser)): ?>
                <a class="btn-primary" href="create.php">New Stock Request</a>
            <?php endif; ?>
        </div>
    </div>

    <form class="card" method="get">
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
            <a class="btn-secondary" href="index.php">Reset</a>
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
                            $canApprove = (string)$request['status'] === 'Pending'
                                && $permissionService->canReviewStockRequest($currentUser);
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
                                    <?php if ($canApprove): ?>
                                        <form method="post" action="approve.php" style="display:inline">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="id" value="<?= (int)$request['id'] ?>">
                                            <button class="btn-secondary btn-sm" type="submit">Approve</button>
                                        </form>
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
