<?php

declare(strict_types=1);

$pageTitle = 'Department Worklist';
$moduleStylesheet = '/modules/visits/assets/visits.css';

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../services/VisitService.php';
require_once __DIR__ . '/../../services/PermissionService.php';

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

if (
    !$currentUser
    || !$permissionService->hasPermission('view_encounter', $currentUser)
    || (
        !$permissionService->isAdministrator($currentUser)
        && !$permissionService->canAccessDepartment($departmentId, $currentUser)
    )
) {
    http_response_code(403);
    exit('You are not allowed to view this department worklist.');
}

$rows = $visitService->listDepartmentWorklist($departmentId);

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
        gap: .5rem;
        align-items: center;
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
                                    <?php else: ?>
                                        <span class="status-badge status-success">Received</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= ($row['position'] ?? null) !== null ? (int)$row['position'] : '—' ?></td>
                                <td><?= !empty($row['queued_at']) ? e(date('d M Y h:i A', strtotime((string)$row['queued_at']))) : '—' ?></td>
                                <td class="table-actions">
                                    <?php if ($awaitingReceive): ?>
                                        <a class="btn-primary" href="receive.php?visit=<?= $visitId ?>">Receive</a>
                                    <?php endif; ?>
                                    <a class="btn-secondary" href="workspace.php?id=<?= $visitId ?>">Open Encounter</a>
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
