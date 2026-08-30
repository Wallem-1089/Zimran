<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if (!$ecgTablesReady) {
    http_response_code(503);
    exit('ECG tables are not available yet. Apply Migration 058 to enable this section.');
}

$visitId = filter_input(INPUT_GET, 'visit', FILTER_VALIDATE_INT) ?: 0;
$patientId = filter_input(INPUT_GET, 'patient', FILTER_VALIDATE_INT) ?: 0;

if ($visitId > 0) {
    $visit = ecgRequireVisit($visitService, $visitId);
    $patientId = (int)$visit['patient_id'];
    $records = $ecgService->listByVisit($visitId, $currentUser);
} elseif ($patientId > 0) {
    $visit = null;
    $records = $ecgService->listByPatient($patientId, $currentUser);
} else {
    header('Location: index.php');
    exit;
}

$patient = $patientService->getPatientById($patientId);
if (!$patient) {
    http_response_code(404);
    exit('Patient not found.');
}

if (!$permissionService->canViewEcg($patientId, $currentUser)) {
    http_response_code(403);
    exit('You do not have permission to view ECG history.');
}

$pageTitle = 'ECG History';
$moduleStylesheet = '/modules/visits/assets/visits.css';

require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div>
            <h1>ECG History</h1>
            <p><?= e((string)($patient['first_name'] . ' ' . $patient['last_name'])) ?> · <?= e((string)$patient['hospital_number']) ?></p>
        </div>
        <div class="form-actions">
            <?php if ($visitId > 0): ?>
                <a class="btn-secondary" href="<?= e(ecgBackToWorkspace($visitId)) ?>">Workspace</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <?php if ($records === []): ?>
            <p class="text-muted">No ECG records found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Visit</th>
                            <th>Study</th>
                            <th>Source</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Chart</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $record): ?>
                            <tr>
                                <td><?= e((string)($record['created_at'] ?? '-')) ?></td>
                                <td><?= e((string)($record['visit_number'] ?? ('#' . (int)$record['visit_id']))) ?></td>
                                <td><?= e((string)($record['study_requested'] ?? 'ECG')) ?></td>
                                <td><?= e((string)($record['request_source'] ?? '-')) ?></td>
                                <td><?= e((string)($record['priority'] ?? '-')) ?></td>
                                <td><?= e((string)($record['status'] ?? '-')) ?></td>
                                <td><?= !empty($record['chart_stored_path']) ? 'Uploaded' : 'Pending' ?></td>
                                <td class="table-actions">
                                    <a href="view.php?id=<?= (int)$record['id'] ?>">View</a>
                                    <a href="../visits/workspace.php?id=<?= (int)$record['visit_id'] ?>&tab=ecg">Open Encounter</a>
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

