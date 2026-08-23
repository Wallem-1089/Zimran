<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$visitId = filter_input(INPUT_GET, 'visit', FILTER_VALIDATE_INT) ?: 0;
$patientId = filter_input(INPUT_GET, 'patient', FILTER_VALIDATE_INT) ?: 0;

if ($visitId > 0) {
    $visit = physiotherapyRequireVisit($visitService, $visitId);
    $records = $physiotherapyService->listByVisit($visitId, $currentUser);
    $patientId = (int)$visit['patient_id'];
} elseif ($patientId > 0) {
    $records = $physiotherapyService->listByPatient($patientId, $currentUser);
} else {
    header('Location: index.php');
    exit;
}

$patient = $patientService->getPatientById($patientId);
if (!$patient) {
    http_response_code(404);
    exit('Patient not found.');
}

$pageTitle = 'Physiotherapy History';
$moduleStylesheet = '/modules/visits/assets/visits.css';

require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div>
            <h1>Physiotherapy History</h1>
            <p><?= e((string)$patient['first_name']) ?> <?= e((string)$patient['last_name']) ?> — <?= e((string)$patient['hospital_number']) ?></p>
        </div>
        <div class="form-actions">
            <?php if ($permissionService->canViewPhysiotherapyWorklist($currentUser)): ?>
                <a class="btn-secondary" href="index.php">Worklist</a>
            <?php endif; ?>
            <?php if ($visitId > 0): ?>
                <a class="btn-secondary" href="<?= e(physiotherapyBackToWorkspace($visitId)) ?>">Workspace</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <?php if ($records === []): ?>
            <p class="text-muted">No physiotherapy records found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Visit</th>
                            <th>Source</th>
                            <th>Presenting Problem</th>
                            <th>Status</th>
                            <th>Sessions</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $record): ?>
                            <tr>
                                <td><?= e((string)($record['created_at'] ?? '-')) ?></td>
                                <td><?= e((string)($record['visit_number'] ?? '-')) ?></td>
                                <td><?= e((string)($record['record_source'] ?? '-')) ?></td>
                                <td><?= e((string)($record['presenting_problem'] ?? $record['summary'] ?? '-')) ?></td>
                                <td><?= e((string)($record['status'] ?? '-')) ?></td>
                                <td><?= e((string)($record['session_count'] ?? 0)) ?></td>
                                <td>
                                    <a class="btn-secondary btn-sm" href="view.php?id=<?= (int)$record['id'] ?>">View</a>
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
