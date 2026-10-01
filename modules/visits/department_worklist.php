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
require_once __DIR__ . '/../../services/PatientRegistrationBillingService.php';

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
$registrationBillingService = new PatientRegistrationBillingService($pdo);
$canViewAllDepartmentWorklists = $permissionService->canViewAllDepartmentWorklists($currentUser);
$availableDepartments = $canViewAllDepartmentWorklists ? $visitService->getDepartments() : [];
$requestedDepartmentId = filter_input(INPUT_GET, 'department_id', FILTER_VALIDATE_INT) ?: 0;
$requestedDepartmentName = trim((string)($_GET['department'] ?? ''));
$selectedViaEmergencyPermission = false;

if ($requestedDepartmentId <= 0
    && strcasecmp($requestedDepartmentName, 'Emergency') === 0
    && $permissionService->canViewEmergencyWorklist($currentUser)
) {
    $stmt = $pdo->prepare("
        SELECT id, department_name
        FROM departments
        WHERE department_name = 'Emergency'
          AND is_active = 1
        LIMIT 1
    ");
    $stmt->execute();
    $requestedDepartment = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    if ($requestedDepartment) {
        $requestedDepartmentId = (int)$requestedDepartment['id'];
        $departmentId = $requestedDepartmentId;
        $departmentName = (string)$requestedDepartment['department_name'];
        $selectedViaEmergencyPermission = true;
    }
}

if ($requestedDepartmentId > 0) {
    $requestedDepartment = null;
    if ($canViewAllDepartmentWorklists) {
        foreach ($availableDepartments as $department) {
            if ((int)$department['id'] === $requestedDepartmentId) {
                $requestedDepartment = $department;
                break;
            }
        }
    } elseif ($permissionService->canViewEmergencyWorklist($currentUser)
    ) {
        $stmt = $pdo->prepare("
            SELECT id, department_name
            FROM departments
            WHERE id = :id
              AND department_name = 'Emergency'
              AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute([':id' => $requestedDepartmentId]);
        $requestedDepartment = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    if ($requestedDepartment) {
        $departmentId = $requestedDepartmentId;
        $departmentName = (string)$requestedDepartment['department_name'];
        $selectedViaEmergencyPermission = strcasecmp($departmentName, 'Emergency') === 0
            && $permissionService->canViewEmergencyWorklist($currentUser);
    }
}
$canActOnSelectedDepartment = $permissionService->isAdministrator($currentUser)
    || $selectedViaEmergencyPermission
    || $permissionService->canAccessDepartment($departmentId, $currentUser);

if (
    !$currentUser
    || (
        !$selectedViaEmergencyPermission
        && !$permissionService->hasPermission('view_encounter', $currentUser)
    )
    || (
        !$canViewAllDepartmentWorklists
        && !$selectedViaEmergencyPermission
        && !$permissionService->canAccessDepartment($departmentId, $currentUser)
    )
) {
    http_response_code(403);
    exit('You are not allowed to view this department worklist.');
}

$rows = $visitService->listDepartmentWorklist($departmentId);
$isAccountsDepartment = strcasecmp($departmentName, 'Accounts') === 0;
$billingRequestsReady = false;
$registrationRequestsReady = false;
$pendingBillingRequests = [];
$pendingRegistrationRequests = [];
$canReviewBillingRequests = $permissionService->canReviewBillingRequest($currentUser)
    || $permissionService->canRecordPayment($currentUser);

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
                ['statuses' => ['Pending', 'Charged']],
                $currentUser
            );
        }

        $stmt->execute([':table' => 'patient_registration_billing_requests']);
        $registrationRequestsReady = (int)$stmt->fetchColumn() > 0;

        if ($registrationRequestsReady) {
            $pendingRegistrationRequests = $registrationBillingService->listRequests(
                ['status' => 'Pending']
            );
        }
    } catch (Throwable) {
        $billingRequestsReady = false;
        $registrationRequestsReady = false;
        $pendingBillingRequests = [];
        $pendingRegistrationRequests = [];
    }
}

require_once __DIR__ . '/../../layouts/header.php';
require_once __DIR__ . '/../../layouts/sidebar.php';
?>
<style>
    .department-worklist-card {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }

    .department-worklist-card .card-header {
        gap: 1rem;
        align-items: flex-start;
    }

    .department-worklist-card .card-header .form-actions {
        justify-content: flex-end;
        gap: .6rem;
    }

    .department-worklist-section-title {
        margin: .25rem 0 -.25rem;
        color: #172033;
        font-size: 1.05rem;
        line-height: 1.25;
    }

    .department-table-wrap {
        margin-top: 0;
        overflow-x: auto;
    }

    .department-worklist-table {
        width: 100%;
        min-width: 1180px;
        border-collapse: separate;
        border-spacing: 0;
        table-layout: auto;
    }

    .department-worklist-table th,
    .department-worklist-table td {
        padding: .75rem .85rem;
        vertical-align: top;
        line-height: 1.35;
        overflow-wrap: anywhere;
        word-break: normal;
    }

    .department-worklist-table th {
        white-space: nowrap;
        letter-spacing: .01em;
    }

    .department-worklist-table .status-badge {
        max-width: 100%;
        white-space: normal;
        overflow-wrap: anywhere;
        text-align: center;
    }

    .department-worklist-table .worklist-status-cell,
    .department-worklist-table .worklist-details-cell {
        min-width: 130px;
    }

    .department-worklist-table .table-actions {
        display: flex;
        flex-direction: column;
        gap: .5rem;
        align-items: stretch;
        min-width: 0;
        white-space: normal;
    }

    .department-worklist-table .table-actions a,
    .department-worklist-table .table-actions button {
        justify-content: center;
        white-space: nowrap;
        text-decoration: none;
    }

    .department-worklist-table th:last-child,
    .department-worklist-table td:last-child {
        text-align: left;
        min-width: 0;
    }

    .department-worklist-table .inline-form {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: .45rem;
        align-items: stretch;
        margin: 0;
        white-space: normal;
    }

    .department-worklist-table .inline-form input[type="text"],
    .department-worklist-table .inline-form button {
        width: 100%;
        min-width: 0;
        margin: 0;
    }

    .registration-billing-table th:nth-child(1),
    .registration-billing-table td:nth-child(1) {
        width: 18%;
    }

    .registration-billing-table th:nth-child(2),
    .registration-billing-table td:nth-child(2) {
        width: 12%;
    }

    .registration-billing-table th:nth-child(3),
    .registration-billing-table td:nth-child(3),
    .registration-billing-table th:nth-child(4),
    .registration-billing-table td:nth-child(4) {
        width: 13%;
    }

    .registration-billing-table th:nth-child(5),
    .registration-billing-table td:nth-child(5) {
        width: 10%;
    }

    .registration-billing-table th:nth-child(6),
    .registration-billing-table td:nth-child(6) {
        width: 13%;
    }

    .registration-billing-table th:nth-child(7),
    .registration-billing-table td:nth-child(7) {
        width: 13%;
    }

    .registration-billing-table th:nth-child(8),
    .registration-billing-table td:nth-child(8) {
        width: 18%;
    }

    .clinical-billing-table th:nth-child(1),
    .clinical-billing-table td:nth-child(1),
    .encounter-worklist-table th:nth-child(1),
    .encounter-worklist-table td:nth-child(1) {
        width: 17%;
    }

    .clinical-billing-table th:nth-child(5),
    .clinical-billing-table td:nth-child(5),
    .clinical-billing-table th:nth-child(6),
    .clinical-billing-table td:nth-child(6),
    .encounter-worklist-table th:nth-child(7),
    .encounter-worklist-table td:nth-child(7) {
        width: 16%;
    }

    .clinical-billing-table th:nth-child(9),
    .clinical-billing-table td:nth-child(9),
    .encounter-worklist-table th:nth-child(10),
    .encounter-worklist-table td:nth-child(10) {
        width: 15%;
    }

    .encounter-worklist-table th:nth-child(4),
    .encounter-worklist-table td:nth-child(4),
    .encounter-worklist-table th:nth-child(5),
    .encounter-worklist-table td:nth-child(5),
    .encounter-worklist-table th:nth-child(6),
    .encounter-worklist-table td:nth-child(6) {
        width: 12%;
    }

    .encounter-worklist-table th:nth-child(7),
    .encounter-worklist-table td:nth-child(7) {
        width: 18%;
    }

    @media (max-width: 900px) {
        .department-worklist-card .card-header {
            align-items: stretch;
        }

        .department-worklist-card .card-header .form-actions {
            justify-content: stretch;
        }

        .department-worklist-card .card-header .form-actions a {
            flex: 1 1 180px;
            text-align: center;
        }
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
        <section class="card department-worklist-card">
            <div class="card-header">
                <div>
                    <h2>Pending Billing Requests</h2>
                    <p>Registration payments and department recommendations that need Accounts clearance.</p>
                </div>
                <div class="form-actions">
                    <a class="btn-secondary" href="../billing/registration_requests.php">Open Registration Requests</a>
                    <a class="btn-secondary" href="../billing/billing_requests.php">Open Billing Requests</a>
                </div>
            </div>

            <?php if (!$billingRequestsReady && !$registrationRequestsReady): ?>
                <div class="empty-state">Billing request tables are not available yet. Apply the billing migrations to enable this section.</div>
            <?php endif; ?>

            <?php if ($registrationRequestsReady && $pendingRegistrationRequests !== []): ?>
                <h3 class="department-worklist-section-title">Patient Registration Billing</h3>
                <div class="table-responsive department-table-wrap">
                    <table class="summary-table department-worklist-table registration-billing-table">
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Hospital No.</th>
                                <th>Billing Type</th>
                                <th>Registration Type</th>
                                <th>Amount</th>
                                <th>Requested By</th>
                                <th>Requested At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingRegistrationRequests as $request): ?>
                                <tr>
                                    <td><?= e((string)($request['patient_name'] ?? 'Unnamed Patient')) ?></td>
                                    <td><?= e((string)($request['hospital_number'] ?? 'Pending')) ?></td>
                                    <td><?= e((string)($request['billing_type'] ?? 'Registration')) ?></td>
                                    <td><?= e((string)($request['registration_type'] ?? 'Renewal')) ?></td>
                                    <td>₦<?= e((string)($request['display_amount'] ?? number_format((float)($request['amount'] ?? 0), 2))) ?></td>
                                    <td><?= e((string)($request['requested_by_name'] ?? '—')) ?></td>
                                    <td><?= !empty($request['requested_at']) ? e(date('d M Y h:i A', strtotime((string)$request['requested_at']))) : '—' ?></td>
                                    <td class="table-actions">
                                        <?php if ($canReviewBillingRequests): ?>
                                            <form method="post" action="../billing/registration_request_pay.php" class="inline-form">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="request_id" value="<?= (int)$request['id'] ?>">
                                                <input type="text" name="notes" placeholder="Payment ref / notes">
                                                <button class="btn-primary btn-sm" type="submit">Mark Paid</button>
                                            </form>
                                        <?php endif; ?>
                                        <a class="btn-secondary btn-sm" href="../billing/registration_requests.php">Open Requests</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if ($billingRequestsReady && $pendingBillingRequests !== []): ?>
                <h3 class="department-worklist-section-title">Clinical Request Billing</h3>
                <div class="table-responsive department-table-wrap">
                    <table class="summary-table department-worklist-table clinical-billing-table">
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
                                        <a class="btn-primary btn-sm" href="../billing/request_review.php?id=<?= (int)$request['id'] ?>">
                                            <?= (string)($request['status'] ?? '') === 'Charged' ? 'Review Payment' : 'Create Charge' ?>
                                        </a>
                                        <a class="btn-secondary btn-sm" href="workspace.php?id=<?= (int)$request['visit_id'] ?>&tab=billing">Open Encounter</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif (($billingRequestsReady || $registrationRequestsReady) && $pendingRegistrationRequests === []): ?>
                <div class="empty-state">No pending billing or registration requests.</div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <section class="card">
        <?php if ($rows === []): ?>
            <div class="empty-state">
                No encounters are currently waiting for this department.
            </div>
        <?php else: ?>
            <div class="table-responsive department-table-wrap">
                <table class="summary-table department-worklist-table encounter-worklist-table">
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
                                    'Plaster Request',
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
                                <td class="worklist-status-cell"><?= e((string)($row['visit_status'] ?? '—')) ?></td>
                                <td class="worklist-status-cell"><?= e((string)($row['queue_status'] ?? 'Waiting')) ?></td>
                                <td class="worklist-status-cell">
                                    <?php if ($awaitingReceive): ?>
                                        <span class="status-badge status-warning">Awaiting Receive</span>
                                    <?php elseif ($isRequestAttention): ?>
                                        <span class="status-badge status-warning">Request / Attention</span>
                                    <?php else: ?>
                                        <span class="status-badge status-success">Received</span>
                                    <?php endif; ?>
                                </td>
                                <td class="worklist-details-cell"><?= e((string)($row['remarks'] ?? '—')) ?></td>
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
