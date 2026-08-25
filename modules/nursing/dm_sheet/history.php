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
    $records = $diabetesMonitoringService->listByVisit($visitId, $currentUser);
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
        exit('DM Sheet access denied.');
    }
    $records = $diabetesMonitoringService->listByPatient($patientId, $currentUser);
}

$pageTitle = 'DM Sheet';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../../layouts/header.php';
require __DIR__ . '/../../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div>
            <h1>DM Sheet</h1>
            <p><?= e((string)($visit['visit_number'] ?? ($patient['hospital_number'] ?? ''))) ?></p>
        </div>
        <div class="form-actions">
            <button class="btn-secondary" type="button" onclick="window.print()">Print DM Sheet</button>
            <?php if ($visit !== null): ?>
                <a class="btn-secondary" href="<?= e(dmSheetBackToWorkspace((int)$visit['id'])) ?>">Workspace</a>
                <?php if ($permissionService->canCreateNursing($visit, $currentUser)): ?>
                    <a class="btn-primary" href="create.php?visit=<?= (int)$visit['id'] ?>">New DM Entry</a>
                <?php endif; ?>
            <?php else: ?>
                <a class="btn-secondary" href="<?= e(dmSheetBackToChart((int)$patientId)) ?>">Patient Chart</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <?php if ($records === []): ?>
            <p class="text-muted">No DM Sheet entries found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Recorded At</th>
                            <th>Encounter</th>
                            <th>Blood Glucose</th>
                            <th>Meal Status</th>
                            <th>Insulin Given</th>
                            <th>Recorded By</th>
                            <th class="no-print">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $record): ?>
                            <tr>
                                <td><?= e((string)$record['recorded_at']) ?></td>
                                <td><?= e((string)($record['visit_number'] ?? ('#' . (int)$record['visit_id']))) ?></td>
                                <td><?= e((string)$record['blood_glucose']) ?></td>
                                <td><?= e((string)$record['meal_status']) ?></td>
                                <td><?= e((string)($record['insulin_given'] ?? '-')) ?></td>
                                <td><?= e((string)($record['recorded_by_name'] ?? '-')) ?></td>
                                <td class="no-print">
                                    <a class="btn-secondary btn-sm" href="view.php?id=<?= (int)$record['id'] ?>">View</a>
                                    <?php if ($visit !== null): ?>
                                        <a class="btn-secondary btn-sm" href="../../visits/workspace.php?id=<?= (int)$record['visit_id'] ?>&tab=nursing">Open Encounter</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../../../layouts/footer.php'; ?>
</div>
