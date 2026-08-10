<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$status = (string)($_GET['status'] ?? '');
$worklist = $physiotherapyService->listWorklist($currentUser, ['status' => $status]);

$pageTitle = 'Physiotherapy Worklist';
$moduleStylesheet = '/modules/visits/assets/visits.css';

require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div>
            <h1>Physiotherapy Worklist</h1>
            <p>Clinical and direct physiotherapy records awaiting processing.</p>
        </div>
        <div class="form-actions">
            <a class="btn-secondary" href="index.php">All</a>
            <a class="btn-secondary" href="index.php?status=Active">Active</a>
            <a class="btn-secondary" href="index.php?status=Completed">Completed</a>
            <a class="btn-secondary" href="index.php?status=Cancelled">Cancelled</a>
        </div>
    </div>

    <div class="card">
        <?php if ($worklist === []): ?>
            <p class="text-muted">No physiotherapy records found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Hospital Number</th>
                            <th>Visit Number</th>
                            <th>Presenting Problem</th>
                            <th>Source</th>
                            <th>Status</th>
                            <th>Physiotherapist</th>
                            <th>Date</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($worklist as $row): ?>
                            <tr>
                                <td><?= e((string)($row['patient_name'] ?? 'Unknown')) ?></td>
                                <td><?= e((string)($row['hospital_number'] ?? '-')) ?></td>
                                <td><?= e((string)($row['visit_number'] ?? '-')) ?></td>
                                <td><?= e((string)($row['presenting_problem'] ?? $row['summary'] ?? '-')) ?></td>
                                <td><?= e((string)($row['record_source'] ?? '-')) ?></td>
                                <td><?= e((string)($row['status'] ?? '-')) ?></td>
                                <td><?= e((string)($row['physiotherapist_name'] ?? '-')) ?></td>
                                <td><?= e((string)($row['created_at'] ?? '-')) ?></td>
                                <td>
                                    <a class="btn-secondary btn-sm" href="view.php?id=<?= (int)$row['id'] ?>">View</a>
                                    <a class="btn-secondary btn-sm" href="../visits/workspace.php?id=<?= (int)$row['visit_id'] ?>&tab=physiotherapy">Open Encounter</a>
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
