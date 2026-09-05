<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if (!$ecgTablesReady) {
    http_response_code(503);
    exit('ECG tables are not available yet. Apply Migration 058 to enable this section.');
}

if (!$permissionService->canViewEcgWorklist($currentUser)) {
    http_response_code(403);
    exit('You do not have permission to view the ECG worklist.');
}

$status = (string)($_GET['status'] ?? '');
$requests = $ecgService->listWorklist($currentUser, ['status' => $status]);

$pageTitle = 'ECG Worklist';
$moduleStylesheet = '/modules/visits/assets/visits.css';

require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div>
            <h1>ECG Worklist</h1>
            <p>Clinical and direct ECG requests awaiting ECG department action.</p>
        </div>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert-success"><?= e((string)$_SESSION['success_message']) ?></div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <form method="get" class="card filters-inline">
        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status">
                <?php foreach (['' => 'Active', 'All' => 'All', 'Requested' => 'Requested', 'In Progress' => 'In Progress', 'Completed' => 'Completed', 'Cancelled' => 'Cancelled'] as $value => $label): ?>
                    <option value="<?= e((string)$value) ?>" <?= $status === (string)$value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn-secondary">Filter</button>
    </form>

    <div class="card">
        <?php if ($requests === []): ?>
            <p class="text-muted">No ECG requests found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Hospital No.</th>
                            <th>Visit No.</th>
                            <th>Study</th>
                            <th>Source</th>
                            <th>Priority</th>
                            <th>Requested By</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td><?= e((string)($request['patient_name'] ?? '-')) ?></td>
                                <td><?= e((string)($request['hospital_number'] ?? '-')) ?></td>
                                <td><?= e((string)($request['visit_number'] ?? '-')) ?></td>
                                <td><?= e((string)($request['study_requested'] ?? 'ECG')) ?></td>
                                <td><?= e((string)($request['request_source'] ?? '-')) ?></td>
                                <td><?php $priority = (string)($request['priority'] ?? '-'); ?><?php if ($priority === 'Urgent'): ?><span class="status-badge status-warning priority-badge">Urgent</span><?php else: ?><?= e($priority) ?><?php endif; ?></td>
                                <td><?= e((string)($request['requested_by_name'] ?? '-')) ?></td>
                                <td><?= e((string)($request['created_at'] ?? '-')) ?></td>
                                <td><?= e((string)($request['status'] ?? '-')) ?></td>
                                <td class="table-actions">
                                    <a href="../visits/workspace.php?id=<?= (int)$request['visit_id'] ?>&tab=ecg">Open Encounter</a>
                                    <a href="view.php?id=<?= (int)$request['id'] ?>">View/Process</a>
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
