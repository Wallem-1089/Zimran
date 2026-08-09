<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$visitId = filter_input(INPUT_GET, 'visit', FILTER_VALIDATE_INT) ?: 0;
$patientId = filter_input(INPUT_GET, 'patient', FILTER_VALIDATE_INT) ?: 0;

if ($visitId > 0) {
    $visit = laboratoryRequireVisit($visitService, $visitId);
    $patientId = (int)$visit['patient_id'];
    $records = $laboratoryService->listByVisit($visitId, $currentUser);
} elseif ($patientId > 0) {
    $visit = null;
    $records = $laboratoryService->listByPatient($patientId, $currentUser);
} else {
    header('Location: index.php');
    exit;
}

$patient = $patientService->getPatientById($patientId);
if (!$patient) {
    http_response_code(404);
    exit('Patient not found.');
}

$pageTitle = 'Laboratory History';
$moduleStylesheet = '/modules/visits/assets/visits.css';

require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div>
            <h1>Laboratory History</h1>
            <p><?= e((string)($patient['first_name'] . ' ' . $patient['last_name'])) ?> | <?= e((string)$patient['hospital_number']) ?></p>
        </div>
        <div class="form-actions">
            <?php if ($visitId > 0 && $records !== []): ?>
                <a class="btn-secondary" href="view.php?id=<?= (int)($records[0]['id'] ?? 0) ?>">Open Latest Request</a>
            <?php endif; ?>
            <?php if ($visitId > 0): ?>
                <a class="btn-secondary" href="<?= e(laboratoryBackToWorkspace($visitId)) ?>">Workspace</a>
            <?php else: ?>
                <a class="btn-secondary" href="../medical_records/chart.php?patient=<?= (int)$patientId ?>&tab=laboratory">Patient Chart</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <?php if ($records === []): ?>
            <p class="text-muted">No laboratory requests found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tests Requested</th>
                            <th>Source</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Requested By</th>
                            <th>Created</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $record): ?>
                            <tr>
                                <td><?= (int)$record['id'] ?></td>
                                <td><?= e((string)$record['tests_requested']) ?></td>
                                <td><?= e((string)$record['request_source']) ?></td>
                                <td><?= e((string)$record['priority']) ?></td>
                                <td><?= e((string)$record['status']) ?></td>
                                <td><?= e((string)($record['requested_by_name'] ?? '-')) ?></td>
                                <td><?= e((string)($record['created_at'] ?? '-')) ?></td>
                                <td><a class="btn-secondary btn-sm" href="view.php?id=<?= (int)$record['id'] ?>">View</a></td>
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
