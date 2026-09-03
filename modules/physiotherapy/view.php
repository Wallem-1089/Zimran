<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$recordId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
if (!$recordId) {
    header('Location: index.php');
    exit;
}

if (!$physiotherapyTablesReady) {
    http_response_code(503);
    exit('Physiotherapy tables are not available yet. Apply Migration 028 to enable this section.');
}

$record = $physiotherapyService->getRecordById($recordId, $currentUser);
if (!$record) {
    http_response_code(404);
    exit('Physiotherapy record not found.');
}

$visit = physiotherapyRequireVisit($visitService, (int)$record['visit_id']);
$patient = $patientService->getPatientById((int)$record['patient_id']);
if (!$patient) {
    http_response_code(404);
    exit('Patient not found.');
}

$latestSession = $physiotherapyService->getResult($recordId, $currentUser);
$sessions = $physiotherapyService->listSessions($recordId, $currentUser);
$canEdit = $permissionService->canEditPhysiotherapy($visit, $currentUser);
$canManageSessions = $permissionService->canManagePhysiotherapySessions($visit, $currentUser);
$canComplete = $permissionService->canCompletePhysiotherapy($visit, $currentUser);
$isClosed = in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true);

$pageTitle = 'Physiotherapy Record';
$moduleStylesheet = '/modules/visits/assets/visits.css';

require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <?php if (isset($_SESSION['validation_errors'])): ?>
        <div class="alert-danger">
            <strong>Please correct the following:</strong>
            <ul>
                <?php foreach ((array)$_SESSION['validation_errors'] as $error): ?>
                    <li><?= e((string)$error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php unset($_SESSION['validation_errors']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert-success"><?= e((string)$_SESSION['success_message']) ?></div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <div class="page-header">
        <div>
            <h1>Physiotherapy Record #<?= (int)$record['id'] ?></h1>
            <p><?= e((string)($visit['visit_number'] ?? ('Encounter #' . (int)$record['visit_id']))) ?></p>
        </div>
        <div class="form-actions">
            <?php if ($permissionService->canViewPhysiotherapyWorklist($currentUser)): ?>
                <a class="btn-secondary" href="index.php">Worklist</a>
            <?php endif; ?>
            <a class="btn-secondary" href="<?= e(physiotherapyBackToWorkspace((int)$record['visit_id'])) ?>">Workspace</a>
            <a class="btn-secondary" href="history.php?visit=<?= (int)$record['visit_id'] ?>">History</a>
            <?php if (!$isClosed && $permissionService->canCreateBillingRequest($currentUser)): ?>
                <a class="btn-secondary" href="../billing/request_create.php?visit=<?= (int)$record['visit_id'] ?>&source_module=Physiotherapy&source_record_id=<?= (int)$record['id'] ?>&description=<?= urlencode('Physiotherapy: ' . (string)($record['presenting_problem'] ?? '')) ?>">Request Billing</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="summary-grid">
            <div class="summary-item"><span class="summary-label">Patient</span> <span class="summary-value"><?= e((string)($patient['first_name'] . ' ' . $patient['last_name'])) ?></span></div>
            <div class="summary-item"><span class="summary-label">Hospital Number</span> <span class="summary-value"><?= e((string)($record['hospital_number'] ?? $patient['hospital_number'])) ?></span></div>
            <div class="summary-item"><span class="summary-label">Source</span> <span class="summary-value"><?= e((string)$record['record_source']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Status</span> <span class="summary-value"><?= e((string)$record['status']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Physiotherapist</span> <span class="summary-value"><?= e((string)($record['physiotherapist_name'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Created By</span> <span class="summary-value"><?= e((string)($record['created_by_name'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Created At</span> <span class="summary-value"><?= e((string)($record['created_at'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Sessions</span> <span class="summary-value"><?= e((string)($record['session_count'] ?? 0)) ?></span></div>
        </div>
    </div>

    <div class="card">
        <h3>Referral Reason</h3>
        <p><?php trim((string)($record['referral_reason'] ?? '')) === '' ? print '<span class="text-muted">No referral reason recorded.</span>' : hmsRenderNarrative((string)$record['referral_reason']); ?></p>
    </div>

    <div class="card">
        <h3>Presenting Problem</h3>
        <p><?php hmsRenderNarrative((string)($record['presenting_problem'] ?? '')); ?></p>
    </div>

    <div class="card">
        <h3>Assessment</h3>
        <p><?php hmsRenderNarrative((string)($record['assessment'] ?? '')); ?></p>
    </div>

    <div class="card">
        <h3>Treatment Plan</h3>
        <p><?php hmsRenderNarrative((string)($record['treatment_plan'] ?? '')); ?></p>
    </div>

    <?php if (trim((string)($record['functional_limitations'] ?? '')) !== ''): ?>
        <div class="card">
            <h3>Functional Limitations</h3>
            <p><?php hmsRenderNarrative((string)$record['functional_limitations']); ?></p>
        </div>
    <?php endif; ?>

    <?php if (trim((string)($record['goals'] ?? '')) !== ''): ?>
        <div class="card">
            <h3>Goals</h3>
            <p><?php hmsRenderNarrative((string)$record['goals']); ?></p>
        </div>
    <?php endif; ?>

    <?php if (trim((string)($record['precautions'] ?? '')) !== ''): ?>
        <div class="card">
            <h3>Precautions</h3>
            <p><?php hmsRenderNarrative((string)$record['precautions']); ?></p>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="section-heading">
            <h3>Sessions</h3>
            <div class="form-actions">
                <?php if (!$isClosed && $canManageSessions && (string)$record['status'] === 'Active'): ?>
                    <a class="btn-primary" href="report.php?record=<?= (int)$record['id'] ?>">Add Session</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($sessions === []): ?>
            <p class="text-muted">No physiotherapy sessions recorded.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Treatment Given</th>
                            <th>Patient Response</th>
                            <th>Progress Notes</th>
                            <th>Next Plan</th>
                            <th>Recorded By</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessions as $session): ?>
                            <tr>
                                <td><?= e((string)($session['session_date'] ?? '-')) ?></td>
                                <td><?php hmsRenderNarrative((string)($session['treatment_given'] ?? '-')); ?></td>
                                <td><?php hmsRenderNarrative((string)($session['patient_response'] ?? '-')); ?></td>
                                <td><?php hmsRenderNarrative((string)($session['progress_notes'] ?? '-')); ?></td>
                                <td><?php hmsRenderNarrative((string)($session['next_plan'] ?? '-')); ?></td>
                                <td><?= e((string)($session['recorded_by_name'] ?? '-')) ?></td>
                                <td>
                                    <?php if (!$isClosed && $canManageSessions && (string)$record['status'] === 'Active'): ?>
                                        <a class="btn-secondary btn-sm" href="report.php?session=<?= (int)$session['id'] ?>">Edit</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>Latest Session</h3>
        <?php if (!$latestSession || trim((string)($latestSession['treatment_given'] ?? '')) === ''): ?>
            <p class="text-muted">No physiotherapy session recorded.</p>
        <?php else: ?>
            <div class="summary-grid">
                <div class="summary-item"><span class="summary-label">Date</span> <span class="summary-value"><?= e((string)($latestSession['session_date'] ?? '-')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Treatment Given</span> <span class="summary-value"><?php hmsRenderNarrative((string)($latestSession['treatment_given'] ?? '-')); ?></span></div>
                <div class="summary-item"><span class="summary-label">Patient Response</span> <span class="summary-value"><?php hmsRenderNarrative((string)($latestSession['patient_response'] ?? '-')); ?></span></div>
                <div class="summary-item"><span class="summary-label">Recorded By</span> <span class="summary-value"><?= e((string)($latestSession['recorded_by_name'] ?? '-')) ?></span></div>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="form-actions">
            <?php if (!$isClosed && $canEdit && (string)$record['status'] === 'Active'): ?>
                <a class="btn-secondary" href="edit.php?id=<?= (int)$record['id'] ?>">Edit Record</a>
            <?php endif; ?>
            <?php if (!$isClosed && $canComplete && (string)$record['status'] === 'Active'): ?>
                <form method="post" action="complete.php" onsubmit="return confirm('Complete this physiotherapy record?');">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int)$record['id'] ?>">
                    <button type="submit" class="btn-primary">Complete</button>
                </form>
            <?php endif; ?>
            <?php if (!$isClosed && $canEdit && (string)$record['status'] === 'Active'): ?>
                <form method="post" action="cancel.php" onsubmit="return confirm('Cancel this physiotherapy record?');">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int)$record['id'] ?>">
                    <button type="submit" class="btn-danger">Cancel</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
