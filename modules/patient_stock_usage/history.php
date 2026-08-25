<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if (!$patientStockUsageTablesReady) {
    http_response_code(503);
    exit('Patient Stock Usage tables are not available yet. Apply Migration 053 to enable this section.');
}

$visitId = filter_input(INPUT_GET, 'visit', FILTER_VALIDATE_INT) ?: 0;
$patientId = filter_input(INPUT_GET, 'patient', FILTER_VALIDATE_INT) ?: 0;

$visit = null;
$patient = null;
$records = [];

if ($visitId > 0) {
    $visit = $visitService->getVisitById($visitId);
    if (!$visit || !$permissionService->canViewEncounter($visit, $currentUser)) {
        http_response_code(403);
        exit('Encounter access denied.');
    }
    $patientId = (int)$visit['patient_id'];
    $patient = $patientService->getPatientById($patientId);
    $records = $patientStockUsageService->listByVisit($visitId, $currentUser);
} elseif ($patientId > 0) {
    if (!$permissionService->canViewPatientStockUsage($currentUser)) {
        http_response_code(403);
        exit('Patient stock usage access denied.');
    }
    $patient = $patientService->getPatientById($patientId);
    if (!$patient) {
        http_response_code(404);
        exit('Patient not found.');
    }
    $records = $patientStockUsageService->listByPatient($patientId, $currentUser);
} else {
    header('Location: ../patients/search.php');
    exit;
}

$pageTitle = 'Patient Stock Usage History';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div>
            <h1>Patient Stock Usage</h1>
            <p><?= e((string)($visit['visit_number'] ?? ($patient['hospital_number'] ?? ''))) ?></p>
        </div>
        <div class="form-actions">
            <button class="btn-secondary" type="button" onclick="window.print()">Print</button>
            <?php if ($visit !== null): ?>
                <a class="btn-secondary" href="<?= e(patientStockUsageBackToWorkspace((int)$visit['id'])) ?>">Workspace</a>
                <?php if ($permissionService->canRecordPatientStockUsage($currentUser)
                    && !in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true)
                ): ?>
                    <a class="btn-primary" href="create.php?visit=<?= (int)$visit['id'] ?>">Record Stock Used</a>
                <?php endif; ?>
            <?php else: ?>
                <a class="btn-secondary" href="../medical_records/chart.php?patient=<?= (int)$patientId ?>">Patient Chart</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <?php if ($records === []): ?>
            <p class="text-muted">No patient stock usage records found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Encounter</th>
                            <th>Department</th>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th>Recorded By</th>
                            <th>Billing</th>
                            <th class="no-print">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $record): ?>
                            <tr>
                                <td><?= e((string)$record['created_at']) ?></td>
                                <td><?= e((string)($record['visit_number'] ?? ('#' . (int)$record['visit_id']))) ?></td>
                                <td><?= e((string)($record['department_name'] ?? '-')) ?></td>
                                <td><?= e((string)($record['item_name'] ?? '-')) ?></td>
                                <td><?= e((string)$record['quantity']) ?> <?= e((string)($record['unit'] ?? '')) ?></td>
                                <td><?= e((string)($record['recorded_by_name'] ?? '-')) ?></td>
                                <td><?= !empty($record['billing_request_id']) ? '#' . (int)$record['billing_request_id'] . ' ' . e((string)($record['billing_request_status'] ?? '')) : 'Not requested' ?></td>
                                <td class="no-print">
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
