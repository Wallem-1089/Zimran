<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if (!$theatreTablesReady) {
    http_response_code(503);
    exit('Theatre tables are not available yet. Apply Migration 029 to enable this section.');
}

$pageTitle = 'Theatre Worklist';
$moduleStylesheet = '/modules/visits/assets/visits.css';
$records = $theatreService->listWorklist($currentUser);

require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div>
            <h1>Theatre Worklist</h1>
            <p>Active theatre records and ongoing procedures.</p>
        </div>
    </div>
    <div class="card">
        <?php if ($records === []): ?>
            <p class="text-muted">No theatre records recorded.</p>
        <?php else: ?>
            <table class="summary-table">
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Hospital Number</th>
                        <th>Visit</th>
                        <th>Procedure</th>
                        <th>Surgeon</th>
                        <th>Status</th>
                        <th>Open Encounter</th>
                        <th>View</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $record): ?>
                        <tr>
                            <td><?= e((string)($record['patient_name'] ?? '-')) ?></td>
                            <td><?= e((string)($record['hospital_number'] ?? '-')) ?></td>
                            <td><?= e((string)($record['visit_number'] ?? '-')) ?></td>
                            <td><?= e((string)($record['procedure_name'] ?? '-')) ?></td>
                            <td><?= e((string)($record['surgeon_name'] ?? '-')) ?></td>
                            <td><?= e((string)($record['status'] ?? '-')) ?></td>
                            <td><a href="../visits/workspace.php?id=<?= (int)$record['visit_id'] ?>&tab=theatre">Open Encounter</a></td>
                            <td><a href="view.php?id=<?= (int)$record['id'] ?>">View</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>

