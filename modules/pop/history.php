<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$visitId = filter_input(INPUT_GET, 'visit', FILTER_VALIDATE_INT) ?: 0;
$patientId = filter_input(INPUT_GET, 'patient', FILTER_VALIDATE_INT) ?: 0;

if (!$popTablesReady) {
    http_response_code(503);
    exit('POP tables are not available yet. Apply Migration 059 to enable this section.');
}

if ($visitId > 0) {
    $visit = popRequireVisit($visitService, $visitId);
    $patientId = (int)$visit['patient_id'];
    $requests = $popService->listByVisit($visitId, $currentUser);
} elseif ($patientId > 0) {
    $requests = $popService->listByPatient($patientId, $currentUser);
} else {
    header('Location: index.php');
    exit;
}

$patient = $patientService->getPatientById($patientId);
if (!$patient) {
    http_response_code(404);
    exit('Patient not found.');
}

$pageTitle = 'POP History';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div>
            <h1>POP History</h1>
            <p><?= e((string)($patient['first_name'] . ' ' . $patient['last_name'])) ?> · <?= e((string)$patient['hospital_number']) ?></p>
        </div>
        <div class="form-actions">
            <button type="button" class="btn-secondary" onclick="window.print()">Print</button>
            <?php if ($visitId > 0): ?>
                <a class="btn-secondary" href="<?= e(popBackToWorkspace($visitId)) ?>">Workspace</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <?php if ($requests === []): ?>
            <p class="text-muted">No POP history found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Visit</th>
                            <th>Procedure</th>
                            <th>Body Part</th>
                            <th>Status</th>
                            <th>Performed By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td><?= e((string)($request['created_at'] ?? '-')) ?></td>
                                <td><?= e((string)($request['visit_number'] ?? '-')) ?></td>
                                <td><?= e((string)($request['procedure_requested'] ?? 'POP / Casting')) ?></td>
                                <td><?= e((string)($request['body_part'] ?? '-')) ?></td>
                                <td><?= e((string)$request['status']) ?></td>
                                <td><?= e((string)($request['performed_by_name'] ?? '-')) ?></td>
                                <td class="actions-cell">
                                    <a href="view.php?id=<?= (int)$request['id'] ?>">View</a>
                                    <a href="../visits/workspace.php?id=<?= (int)$request['visit_id'] ?>&tab=pop">Open Encounter</a>
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
