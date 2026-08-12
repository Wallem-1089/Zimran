<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$status = (string)($_GET['status'] ?? '');
$worklist = $radiologyService->listWorklist($currentUser, ['status' => $status]);
$summaryRows = $radiologyService->listWorklist($currentUser, []);
$statusCounts = array_count_values(array_map(static fn (array $row): string => (string)($row['status'] ?? 'Unknown'), $summaryRows));

$pageTitle = 'Radiology Worklist';
$moduleStylesheet = '/modules/visits/assets/visits.css';

require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div>
            <h1>Radiology Worklist</h1>
            <p>Clinical and direct radiology requests awaiting processing.</p>
        </div>
        <div class="form-actions">
            <a class="btn-secondary" href="index.php">All</a>
            <a class="btn-secondary" href="index.php?status=Requested">Requested</a>
            <a class="btn-secondary" href="index.php?status=In%20Progress">In Progress</a>
            <a class="btn-secondary" href="index.php?status=Completed">Completed</a>
        </div>
    </div>

    <div class="summary-grid">
        <div class="summary-item"><span class="summary-label">Requested</span><span class="summary-value"><?= (int)($statusCounts['Requested'] ?? 0) ?></span></div>
        <div class="summary-item"><span class="summary-label">In Progress</span><span class="summary-value"><?= (int)($statusCounts['In Progress'] ?? 0) ?></span></div>
        <div class="summary-item"><span class="summary-label">Completed</span><span class="summary-value"><?= (int)($statusCounts['Completed'] ?? 0) ?></span></div>
    </div>

    <div class="card">
        <?php if ($worklist === []): ?>
            <p class="text-muted">No radiology requests found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Hospital Number</th>
                            <th>Visit Number</th>
                            <th>Tests Requested</th>
                            <th>Source</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Requested By</th>
                            <th>Requested</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($worklist as $row): ?>
                            <tr>
                                <td><?= e((string)($row['patient_name'] ?? 'Unknown')) ?></td>
                                <td><?= e((string)($row['hospital_number'] ?? '-')) ?></td>
                                <td><?= e((string)($row['visit_number'] ?? '-')) ?></td>
                                <td><?= e((string)($row['study_requested'] ?? $row['tests_requested'] ?? '-')) ?></td>
                                <td><?= e((string)($row['request_source'] ?? '-')) ?></td>
                                <td><?= e((string)($row['priority'] ?? '-')) ?></td>
                                <td><?= e((string)($row['status'] ?? '-')) ?></td>
                                <td><?= e((string)($row['requested_by_name'] ?? '-')) ?></td>
                                <td><?= e((string)($row['created_at'] ?? '-')) ?></td>
                                <td>
                                    <a class="btn-secondary btn-sm" href="view.php?id=<?= (int)$row['id'] ?>">View</a>
                                    <a class="btn-secondary btn-sm" href="../visits/workspace.php?id=<?= (int)$row['visit_id'] ?>&tab=radiology">Open Encounter</a>
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

