<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$visitId = filter_input(INPUT_GET, 'visit', FILTER_VALIDATE_INT) ?: 0;
$patientId = filter_input(INPUT_GET, 'patient', FILTER_VALIDATE_INT) ?: 0;

if (!$pharmacyTablesReady) {
    http_response_code(503);
    exit('Pharmacy tables are not available yet. Apply Migration 032 to enable this section.');
}

if ($visitId > 0) {
    $visit = pharmacyRequireVisit($visitService, $visitId);
    $patientId = (int)$visit['patient_id'];
    $records = $pharmacyService->listByVisit($visitId, $currentUser);
} elseif ($patientId > 0) {
    $visit = null;
    $records = $pharmacyService->listByPatient($patientId, $currentUser);
} else {
    header('Location: index.php');
    exit;
}

$patient = $patientService->getPatientById($patientId);
if (!$patient) {
    http_response_code(404);
    exit('Patient not found.');
}

if (!$permissionService->canViewPharmacy($patientId, $currentUser)) {
    http_response_code(403);
    exit('Pharmacy access denied.');
}

$pageTitle = 'Pharmacy History';
$moduleStylesheet = '/modules/visits/assets/visits.css';

require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div>
            <h1>Pharmacy History</h1>
            <p><?= e((string)$patient['first_name']) ?> <?= e((string)$patient['last_name']) ?> — <?= e((string)$patient['hospital_number']) ?></p>
        </div>
        <div class="form-actions">
            <?php if ($visitId > 0 && $records !== []): ?>
                <a class="btn-secondary" href="view.php?id=<?= (int)($records[0]['id'] ?? 0) ?>">Open Latest Prescription</a>
            <?php endif; ?>
            <?php if ($visitId > 0): ?>
                <a class="btn-secondary" href="<?= e(pharmacyBackToWorkspace($visitId)) ?>">Workspace</a>
            <?php endif; ?>
            <?php if ($permissionService->canViewPharmacyWorklist($currentUser)): ?>
                <a class="btn-secondary" href="index.php">Worklist</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <?php if ($records === []): ?>
            <p class="text-muted">No prescriptions found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Visit</th>
                            <th>Medication</th>
                            <th>Quantity</th>
                            <th>Source</th>
                            <th>Status</th>
                            <th>Prescriber</th>
                            <th>Dispensed By</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $record): ?>
                            <tr>
                                <td><?= e((string)($record['created_at'] ?? '-')) ?></td>
                                <td><?= e((string)($record['visit_number'] ?? '-')) ?></td>
                                <td><?= e((string)($record['medication_name'] ?? '-')) ?></td>
                                <td><?= e((string)($record['quantity'] ?? '-')) ?></td>
                                <td><?= e((string)($record['prescription_source'] ?? '-')) ?></td>
                                <td><?= e((string)($record['status'] ?? '-')) ?></td>
                                <td><?= e((string)($record['prescribed_by_name'] ?? $record['created_by_name'] ?? '-')) ?></td>
                                <td><?= e((string)($record['dispensed_by_name'] ?? '-')) ?></td>
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
