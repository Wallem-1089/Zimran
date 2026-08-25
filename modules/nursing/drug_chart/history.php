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
    $records = $medicationAdministrationService->listByVisit($visitId, $currentUser);
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
        exit('Drug Chart access denied.');
    }
    $records = $medicationAdministrationService->listByPatient($patientId, $currentUser);
}

$pageTitle = 'Drug Chart';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../../layouts/header.php';
require __DIR__ . '/../../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div>
            <h1>Drug Chart / MAR</h1>
            <p><?= e((string)($visit['visit_number'] ?? ($patient['hospital_number'] ?? ''))) ?></p>
        </div>
        <div class="form-actions">
            <button class="btn-secondary" type="button" onclick="window.print()">Print Drug Chart</button>
            <?php if ($visit !== null): ?>
                <a class="btn-secondary" href="<?= e(drugChartBackToWorkspace((int)$visit['id'])) ?>">Workspace</a>
                <?php if ($permissionService->canCreateNursing($visit, $currentUser)): ?>
                    <a class="btn-primary" href="create.php?visit=<?= (int)$visit['id'] ?>">New Drug Chart Entry</a>
                <?php endif; ?>
            <?php else: ?>
                <a class="btn-secondary" href="<?= e(drugChartBackToChart((int)$patientId)) ?>">Patient Chart</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <?php if ($records === []): ?>
            <p class="text-muted">No drug chart entries found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Encounter</th>
                            <th>Medication</th>
                            <th>Dose</th>
                            <th>Route</th>
                            <th>Status</th>
                            <th>Administered By</th>
                            <th class="no-print">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $record): ?>
                            <tr>
                                <td><?= e((string)$record['scheduled_time']) ?></td>
                                <td><?= e((string)($record['visit_number'] ?? ('#' . (int)$record['visit_id']))) ?></td>
                                <td><?= e((string)$record['medication_name']) ?></td>
                                <td><?= e((string)$record['dose_given']) ?></td>
                                <td><?= e((string)($record['route'] ?? '-')) ?></td>
                                <td><?= e((string)$record['administration_status']) ?></td>
                                <td><?= e((string)($record['administered_by_name'] ?? '-')) ?></td>
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
