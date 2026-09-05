<?php

declare(strict_types=1);

$pageTitle = 'Department Worklist';
$moduleStylesheet = '/modules/visits/assets/visits.css';

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../services/VisitService.php';
require_once __DIR__ . '/../../services/PermissionService.php';
require_once __DIR__ . '/../../services/BillingService.php';

$currentUser = $currentUser ?? ($_SESSION['user'] ?? null);
$departmentId = (int)(
    $currentUser['active_department_id']
    ?? $_SESSION['active_department_id']
    ?? $currentUser['department_id']
    ?? 0
);
$departmentName = (string)(
    $currentUser['active_department_name']
    ?? $_SESSION['active_department_name']
    ?? $currentUser['department_name']
    ?? 'Department'
);

$permissionService = new PermissionService($pdo);
$visitService = new VisitService($pdo);
$billingService = new BillingService($pdo);
$canViewAllDepartmentWorklists = $permissionService->canViewAllDepartmentWorklists($currentUser);
$availableDepartments = $canViewAllDepartmentWorklists ? $visitService->getDepartments() : [];
$requestedDepartmentId = filter_input(INPUT_GET, 'department_id', FILTER_VALIDATE_INT) ?: 0;

if ($canViewAllDepartmentWorklists && $requestedDepartmentId > 0) {
    foreach ($availableDepartments as $department) {
        if ((int)$department['id'] === $requestedDepartmentId) {
            $departmentId = $requestedDepartmentId;
            $departmentName = (string)$department['department_name'];
            break;
        }
    }
}
$canActOnSelectedDepartment = $permissionService->isAdministrator($currentUser)
    || $permissionService->canAccessDepartment($departmentId, $currentUser);

if (
    !$currentUser
    || !$permissionService->hasPermission('view_encounter', $currentUser)
    || (
        !$canViewAllDepartmentWorklists
        && !$permissionService->canAccessDepartment($departmentId, $currentUser)
    )
) {
    http_response_code(403);
    exit('You are not allowed to view this department worklist.');
}

$rows = $visitService->listDepartmentWorklist($departmentId);
$isAccountsDepartment = strcasecmp($departmentName, 'Accounts') === 0;
$billingRequestsReady = false;
$pendingBillingRequests = [];

if ($isAccountsDepartment && $permissionService->canViewBillingRequests($currentUser)) {
    try {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*)
             FROM information_schema.tables
             WHERE table_schema = DATABASE()
               AND table_name = :table'
        );
        $stmt->execute([':table' => 'billing_requests']);
        $billingRequestsReady = (int)$stmt->fetchColumn() > 0;

        if ($billingRequestsReady) {
            $pendingBillingRequests = $billingService->listBillingRequests(
                ['status' => 'Pending'],
                $currentUser
            );
        }
    } catch (Throwable) {
        $billingRequestsReady = false;
        $pendingBillingRequests = [];
    }
}

require_once __DIR__ . '/../../layouts/header.php';
require_once __DIR__ . '/../../layouts/sidebar.php';
?>
<style>
    .department-worklist-table {
        min-width: 980px;
        border-collapse: separate;
        border-spacing: 0;
    }

    .department-worklist-table th,
    .department-worklist-table td {
        padding: .85rem 1rem;
        vertical-align: middle;
    }

    .department-worklist-table th {
        white-space: nowrap;
        letter-spacing: .01em;
    }

    .department-worklist-table .table-actions {
        display: flex;
        flex-wrap: wrap;
        gap: .65rem;
        align-items: center;
        min-width: 190px;
    }

    .department-worklist-table .table-actions a,
    .department-worklist-table .table-actions button {
        white-space: nowrap;
        text-decoration: none;
    }

    .department-worklist-table th:last-child,
    .department-worklist-table td:last-child {
        min-width: 210px;
    }
</style>

<div class="main-container">
<?php require_once __DIR__ . '/../../layouts/navbar.php'; ?>

<main class="content">
    <div class="page-header">
        <div>
            <h1>Department Worklist</h1>
            <p><?= e($departmentName) ?> encounters awaiting receive or active queue action.</p>
        </div>
    </div>

    <?php if ($canViewAllDepartmentWorklists): ?>
        <form method="get" class="card compact-filter">
            <div class="form-grid">
                <div class="form-group">
                    <label for="department_id">View Department</label>
                    <select id="department_id" name="department_id">
                        <?php foreach ($availableDepartments as $department): ?>
                            <option value="<?= (int)$department['id'] ?>" <?= (int)$department['id'] === $departmentId ? 'selected' : '' ?>>
                                <?= e((string)$department['department_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-actions">
                <button class="btn-secondary" type="submit">Open Worklist</button>
            </div>
        </form>
    <?php endif; ?>

    <?php if ($isAccountsDepartment && $permissionService->canViewBillingRequests($currentUser)): ?>
        <section class="card">
            <div class="card-header">
                <div>
                    <h2>Pending Billing Requests</h2>
                    <p>Department recommendations that need Accounts review. These do not transfer encounter ownership.</p>
                </div>
                <div class="form-actions">
                    <a class="btn-secondary" href="../billing/billing_requests.php">Open Billing Requests</a>
                </div>
            </div>

            <?php if (!$billingRequestsReady): ?>
                <div class="empty-state">Billing request tables are not available yet. Apply Migration 044 to enable this section.</div>
            <?php elseif ($pendingBillingRequests === []): ?>
                <div class="empty-state">No pending billing requests.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="summary-table department-worklist-table">
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Hospital No.</th>
                                <th>Visit No.</th>
                                <th>Requesting Department</th>
                                <th>Description</th>
                                <th>Suggested Item</th>
                                <th>Qty</th>
                                <th>Requested By</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingBillingRequests as $request): ?>
                                <tr>
                                    <td><?= e((string)($request['patient_name'] ?? 'Unnamed Patient')) ?></td>
                                    <td><?= e((string)($request['hospital_number'] ?? '—')) ?></td>
                                    <td><?= e((string)($request['visit_number'] ?? ('#' . (int)($request['visit_id'] ?? 0)))) ?></td>
                                    <td><?= e((string)($request['department_name'] ?? '—')) ?></td>
                                    <td><?= e((string)($request['description'] ?? '—')) ?></td>
                                    <td><?= e((string)($request['suggested_item_name'] ?? '—')) ?></td>
                                    <td><?= e((string)($request['display_quantity'] ?? '1')) ?></td>
                                    <td><?= e((string)($request['requested_by_name'] ?? '—')) ?></td>
                                    <td class="table-actions">
                                        <a class="btn-primary btn-sm" href="../billing/request_review.php?id=<?= (int)$request['id'] ?>">Create Charge</a>
                                        <a class="btn-secondary btn-sm" href="workspace.php?id=<?= (int)$request['visit_id'] ?>&tab=billing">Open Encounter</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <section class="card">
        <?php if ($rows === []): ?>
            <div class="empty-state">
                No encounters are currently waiting for this department.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="summary-table department-worklist-table">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Hospital No.</th>
                            <th>Visit No.</th>
                            <th>Encounter Status</th>
                            <th>Queue Status</th>
                            <th>Department State</th>
                            <th>Details</th>
                            <th>Position</th>
                            <th>Queued At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <?php
                                $visitId = (int)($row['visit_id'] ?? 0);
                                $awaitingReceive = !empty($row['can_receive']);
                                $worklistStatus = (string)($row['worklist_status'] ?? '');
                                $requestAttentionStatuses = [
                                    'Laboratory Request',
                                    'Radiology Request',
                                    'ECG Request',
                                    'POP Request',
                                    'Physiotherapy Record',
                                    'Prescription',
                                    'Theatre Record',
                                ];
                                $isRequestAttention = !$awaitingReceive && in_array($worklistStatus, $requestAttentionStatuses, true);
                                $patientName = trim(
                                    (string)($row['first_name'] ?? '')
                                    . ' '
                                    . (string)($row['last_name'] ?? '')
                                );
                            ?>
                            <tr>
                                <td><?= e($patientName !== '' ? $patientName : 'Unnamed Patient') ?></td>
                                <td><?= e((string)($row['hospital_number'] ?? '—')) ?></td>
                                <td><?= e((string)($row['visit_number'] ?? ('#' . $visitId))) ?></td>
                                <td><?= e((string)($row['visit_status'] ?? '—')) ?></td>
                                <td><?= e((string)($row['queue_status'] ?? 'Waiting')) ?></td>
                                <td>
                                    <?php if ($awaitingReceive): ?>
                                        <span class="status-badge status-warning">Awaiting Receive</span>
                                    <?php elseif ($isRequestAttention): ?>
                                        <span class="status-badge status-warning">Request / Attention</span>
                                    <?php else: ?>
                                        <span class="status-badge status-success">Received</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e((string)($row['remarks'] ?? '—')) ?></td>
                                <td><?= ($row['position'] ?? null) !== null ? (int)$row['position'] : '—' ?></td>
                                <td><?= !empty($row['queued_at']) ? e(date('d M Y h:i A', strtotime((string)$row['queued_at']))) : '—' ?></td>
                                <td class="table-actions">
                                    <?php if ($awaitingReceive && $canActOnSelectedDepartment): ?>
                                        <a class="btn-primary btn-sm" href="receive.php?visit=<?= $visitId ?>">Receive</a>
                                    <?php endif; ?>
                                    <a class="btn-secondary btn-sm" href="workspace.php?id=<?= $visitId ?>">Open Encounter</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
</div>
