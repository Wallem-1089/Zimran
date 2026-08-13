<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if (!$pharmacyTablesReady) {
    http_response_code(503);
    exit('Pharmacy tables are not available yet. Apply Migration 032 to enable this section.');
}

$status = trim((string)($_GET['status'] ?? 'Prescribed'));
$worklist = $pharmacyService->listWorklist($currentUser, ['status' => $status]);
$summaryRows = $pharmacyService->listWorklist($currentUser, []);
$statusCounts = array_count_values(array_map(static fn (array $row): string => (string)($row['status'] ?? 'Unknown'), $summaryRows));

$pageTitle = 'Pharmacy Worklist';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div>
            <h1>Pharmacy Worklist</h1>
            <p>Clinical and direct prescriptions awaiting dispensing.</p>
        </div>
        <div class="form-actions">
            <a class="btn-secondary" href="index.php">All</a>
            <a class="btn-secondary" href="index.php?status=Prescribed">Prescribed</a>
            <a class="btn-secondary" href="index.php?status=Dispensed">Dispensed</a>
            <a class="btn-secondary" href="index.php?status=Cancelled">Cancelled</a>
        </div>
    </div>

    <div class="summary-grid">
        <div class="summary-item"><span class="summary-label">Waiting</span> <span class="summary-value"><?= (int)($statusCounts['Prescribed'] ?? 0) ?></span></div>
        <div class="summary-item"><span class="summary-label">Dispensed</span> <span class="summary-value"><?= (int)($statusCounts['Dispensed'] ?? 0) ?></span></div>
        <div class="summary-item"><span class="summary-label">Cancelled</span> <span class="summary-value"><?= (int)($statusCounts['Cancelled'] ?? 0) ?></span></div>
    </div>

    <div class="card">
        <?php if ($worklist === []): ?>
            <p class="text-muted">No prescriptions found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Hospital Number</th>
                            <th>Visit Number</th>
                            <th>Medication</th>
                            <th>Quantity</th>
                            <th>Source</th>
                            <th>Prescriber</th>
                            <th>Requested</th>
                            <th>Status</th>
                            <th>Pharmacy Stock Available</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($worklist as $row): ?>
                            <tr>
                                <td><?= e((string)($row['patient_name'] ?? 'Unknown')) ?></td>
                                <td><?= e((string)($row['hospital_number'] ?? '-')) ?></td>
                                <td><?= e((string)($row['visit_number'] ?? '-')) ?></td>
                                <td><?= e((string)($row['medication_name'] ?? '-')) ?></td>
                                <td><?= e((string)($row['quantity'] ?? '-')) ?></td>
                                <td><?= e((string)($row['prescription_source'] ?? '-')) ?></td>
                                <td><?= e((string)($row['prescribed_by_name'] ?? '-')) ?></td>
                                <td><?= e((string)($row['created_at'] ?? '-')) ?></td>
                                <td><?= e((string)($row['status'] ?? '-')) ?></td>
                                <td><?= e(number_format((float)($row['pharmacy_stock_available'] ?? 0), 2)) ?></td>
                                <td>
                                    <a class="btn-secondary btn-sm" href="view.php?id=<?= (int)$row['id'] ?>">View</a>
                                    <a class="btn-secondary btn-sm" href="../visits/workspace.php?id=<?= (int)$row['visit_id'] ?>&tab=pharmacy">Open Encounter</a>
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
