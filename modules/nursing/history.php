<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$visitId = filter_input(INPUT_GET, 'visit', FILTER_VALIDATE_INT) ?: 0;
$patientId = filter_input(INPUT_GET, 'patient', FILTER_VALIDATE_INT) ?: 0;

$visit = null;
$patient = null;
$records = [];

if ($visitId > 0) {
    $visit = nursingRequireVisit($visitService, $visitId);
    nursingRequireAccess($permissionService, $visit, $currentUser);
    $records = $nursingService->listByVisit($visitId, $currentUser);
    $patientId = (int)$visit['patient_id'];
}

if ($patientId > 0 && $visit === null) {
    $patient = $patientService->getPatientById($patientId);
    if (!$patient) {
        http_response_code(404);
        exit('Patient not found.');
    }
    if (!$permissionService->canViewNursing($patientId, $currentUser)) {
        http_response_code(403);
        exit('Nursing access denied.');
    }
    $records = $nursingService->listByPatient($patientId, $currentUser);
}

$pageTitle = 'Nursing History';
$moduleStylesheet = '/modules/visits/assets/visits.css';

require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div>
            <h1>Nursing History</h1>
            <p><?= e((string)($visit['visit_number'] ?? ($patient['hospital_number'] ?? ''))) ?></p>
        </div>
        <?php if ($visit !== null && $permissionService->canCreateNursing($visit, $currentUser)): ?>
            <div><a class="btn-primary" href="create.php?visit=<?= (int)$visit['id'] ?>">Start Nursing Assessment</a></div>
        <?php endif; ?>
    </div>

    <div class="card">
        <?php if ($records === []): ?>
            <p class="text-muted">No nursing assessment recorded.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Encounter</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Nurse</th>
                        <th>Summary</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $record): ?>
                        <tr>
                            <td><?= e((string)($record['visit_number'] ?? ('#' . (int)$record['visit_id']))) ?></td>
                            <td><?= e((string)($record['created_at'] ?? '-')) ?></td>
                            <td><?= e((string)($record['status'] ?? '-')) ?></td>
                            <td><?= e((string)($record['nurse_name'] ?? '-')) ?></td>
                            <td><?= e((string)($record['summary'] ?? '-')) ?></td>
                            <td>
                                <a href="view.php?id=<?= (int)$record['id'] ?>">View</a>
                                <?php if ($visit !== null): ?>
                                    | <a href="../visits/workspace.php?id=<?= (int)$record['visit_id'] ?>&tab=nursing">Open Encounter</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
