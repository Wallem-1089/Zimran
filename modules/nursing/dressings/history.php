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
    $records = $dressingRecordService->listByVisit($visitId, $currentUser);
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
        exit('Dressing Book access denied.');
    }
    $records = $dressingRecordService->listByPatient($patientId, $currentUser);
}

$canRequestBilling = $visit !== null
    && !in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true)
    && $permissionService->canCreateBillingRequest($currentUser);

$pageTitle = 'Dressing Book';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../../layouts/header.php';
require __DIR__ . '/../../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div>
            <h1>Dressing Book</h1>
            <p><?= e((string)($visit['visit_number'] ?? ($patient['hospital_number'] ?? ''))) ?></p>
        </div>
        <div class="form-actions">
            <button class="btn-secondary" type="button" onclick="window.print()">Print Dressing Book</button>
            <?php if ($visit !== null): ?>
                <a class="btn-secondary" href="<?= e(dressingBackToWorkspace((int)$visit['id'])) ?>">Workspace</a>
                <?php if ($permissionService->canCreateNursing($visit, $currentUser)): ?>
                    <a class="btn-primary" href="create.php?visit=<?= (int)$visit['id'] ?>">New Dressing Record</a>
                <?php endif; ?>
            <?php else: ?>
                <a class="btn-secondary" href="<?= e(dressingBackToChart((int)$patientId)) ?>">Patient Chart</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <?php if ($records === []): ?>
            <p class="text-muted">No dressing records found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Encounter</th>
                            <th>Wound Site</th>
                            <th>Condition / Dressing</th>
                            <th>Next Dressing</th>
                            <th>Recorded By</th>
                            <th class="no-print">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $record): ?>
                            <tr>
                                <td><?= e((string)($record['created_at'] ?? '-')) ?></td>
                                <td><?= e((string)($record['visit_number'] ?? ('#' . (int)$record['visit_id']))) ?></td>
                                <td><?= e((string)$record['wound_site']) ?></td>
                                <td><?= e((string)($record['summary'] ?? '-')) ?></td>
                                <td><?= e((string)($record['next_dressing_date'] ?? '-')) ?></td>
                                <td><?= e((string)($record['recorded_by_name'] ?? '-')) ?></td>
                                <td class="no-print">
                                    <a class="btn-secondary btn-sm" href="view.php?id=<?= (int)$record['id'] ?>">View</a>
                                    <?php if ($canRequestBilling): ?>
                                        <a class="btn-secondary btn-sm" href="../../billing/request_create.php?visit=<?= (int)$record['visit_id'] ?>&source_module=Dressing&source_record_id=<?= (int)$record['id'] ?>&description=<?= urlencode('Dressing: ' . (string)($record['wound_site'] ?? 'Dressing care')) ?>">Request Billing</a>
                                    <?php endif; ?>
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
