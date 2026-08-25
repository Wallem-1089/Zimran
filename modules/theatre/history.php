<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$patientId = filter_input(INPUT_GET, 'patient', FILTER_VALIDATE_INT) ?: 0;
$visitId = filter_input(INPUT_GET, 'visit', FILTER_VALIDATE_INT) ?: 0;

if (!$permissionService->hasPermission('view_theatre', $currentUser) && !$permissionService->isAdministrator($currentUser)) {
    http_response_code(403);
    exit('Theatre access denied.');
}

$records = [];
if ($patientId > 0) {
    $records = $theatreService->listByPatient($patientId, null);
} elseif ($visitId > 0) {
    $record = $theatreService->getByVisit($visitId, $currentUser);
    $records = $record ? [$record] : [];
}

$pageTitle = 'Theatre History';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div>
            <h1>Theatre History</h1>
            <p>Longitudinal theatre records for the patient or encounter.</p>
        </div>
        <div class="form-actions">
            <button class="btn-secondary" type="button" onclick="window.print()">Print Theatre History</button>
        </div>
    </div>
    <div class="card">
        <?php if ($records === []): ?>
            <p class="text-muted">No theatre history available.</p>
        <?php else: ?>
            <table class="summary-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Visit</th>
                        <th>Procedure</th>
                        <th>Surgeon</th>
                        <th>Status</th>
                        <th>View</th>
                        <th>Open Encounter</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $record): ?>
                        <tr>
                            <td><?= e((string)($record['created_at'] ?? '-')) ?></td>
                            <td><?= e((string)($record['visit_number'] ?? '-')) ?></td>
                            <td><?= e((string)($record['procedure_name'] ?? '-')) ?></td>
                            <td><?= e((string)($record['surgeon_name'] ?? '-')) ?></td>
                            <td><?= e((string)($record['status'] ?? '-')) ?></td>
                            <td><a href="view.php?id=<?= (int)$record['id'] ?>">View</a></td>
                            <td><a href="../visits/workspace.php?id=<?= (int)$record['visit_id'] ?>&tab=theatre">Open Encounter</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
