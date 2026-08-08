<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$visitId = filter_input(INPUT_GET, 'visit', FILTER_VALIDATE_INT) ?: 0;
$patientId = filter_input(INPUT_GET, 'patient', FILTER_VALIDATE_INT) ?: 0;

$visit = null;
$patient = null;
$records = [];

if ($visitId > 0) {
    $visit = vitalSignsRequireVisit($visitService, $visitId);
    vitalSignsRequireAccess($permissionService, $visit, $currentUser);
    $records = $vitalSignsService->listByVisit($visitId, $currentUser);
    $patientId = (int)$visit['patient_id'];
}

if ($patientId > 0 && $visit === null) {
    $patient = $patientService->getPatientById($patientId);
    if (!$patient) {
        http_response_code(404);
        exit('Patient not found.');
    }
    if (!$permissionService->canViewVitalSigns($patientId, $currentUser)) {
        http_response_code(403);
        exit('Vital signs access denied.');
    }
    $records = $vitalSignsService->listByPatient($patientId, $currentUser);
}

$pageTitle = 'Vital Signs History';
$moduleStylesheet = '/modules/visits/assets/visits.css';

require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div>
            <h1>Vital Signs History</h1>
            <p><?= e($visit['visit_number'] ?? ($patient['hospital_number'] ?? '')) ?></p>
        </div>
        <?php if ($visit !== null && $permissionService->canCreateVitalSigns($visit, $currentUser)): ?>
            <div><a class="btn-primary" href="create.php?visit=<?= (int)$visit['id'] ?>">Record New</a></div>
        <?php endif; ?>
    </div>

    <div class="card">
        <?php if ($records === []): ?>
            <p class="text-muted">No vital signs recorded.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Temperature</th>
                        <th>Pulse</th>
                        <th>BP</th>
                        <th>Oxygen</th>
                        <th>BMI</th>
                        <th>Recorder</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $record): ?>
                        <tr>
                            <td><?= e((string)$record['created_at']) ?></td>
                            <td><?= e((string)($record['temperature'] ?? '-')) ?></td>
                            <td><?= e((string)($record['pulse'] ?? '-')) ?></td>
                            <td><?= e((string)($record['blood_pressure'] ?? '-')) ?></td>
                            <td><?= e((string)($record['oxygen_saturation'] ?? '-')) ?></td>
                            <td><?= e((string)($record['bmi'] ?? '-')) ?></td>
                            <td><?= e((string)($record['recorded_by_name'] ?? '-')) ?></td>
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
