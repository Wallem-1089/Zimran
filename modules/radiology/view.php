<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$requestId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
if (!$requestId) {
    header('Location: index.php');
    exit;
}

if (!$radiologyTablesReady) {
    http_response_code(503);
    exit('Radiology tables are not available yet. Apply Migration 027 to enable this section.');
}

$request = $radiologyService->getRequestById($requestId, $currentUser);
if (!$request) {
    http_response_code(404);
    exit('Radiology request not found.');
}

$visit = radiologyRequireVisit($visitService, (int)$request['visit_id']);
$patient = $patientService->getPatientById((int)$request['patient_id']);
if (!$patient) {
    http_response_code(404);
    exit('Patient not found.');
}

$result = $radiologyService->getResult($requestId, $currentUser);
$canProcess = $permissionService->canProcessRadiologyRequest($visit, $currentUser);
$canEnter = $permissionService->canEnterRadiologyResult($visit, $currentUser);
$canEdit = $permissionService->canEditRadiologyResult($visit, $currentUser);
$canComplete = $permissionService->canCompleteRadiologyRequest($visit, $currentUser);
$isClosed = in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true);
$isRequestClosed = in_array((string)($request['status'] ?? ''), ['Completed', 'Cancelled'], true);

$pageTitle = 'Radiology Request';
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
            <h1>Radiology Request #<?= (int)$request['id'] ?></h1>
            <p><?= e((string)($request['visit_number'] ?? ('Encounter #' . (int)$request['visit_id']))) ?></p>
        </div>
        <div class="form-actions">
            <?php if ($permissionService->canViewRadiologyWorklist($currentUser)): ?>
                <a class="btn-secondary" href="index.php">Worklist</a>
            <?php endif; ?>
            <a class="btn-secondary" href="<?= e(radiologyBackToWorkspace((int)$request['visit_id'])) ?>">Workspace</a>
            <a class="btn-secondary" href="history.php?visit=<?= (int)$request['visit_id'] ?>">History</a>
            <?php if (!$isClosed && $permissionService->canCreateBillingRequest($currentUser)): ?>
                <a class="btn-secondary" href="../billing/request_create.php?visit=<?= (int)$request['visit_id'] ?>&source_module=Radiology&source_record_id=<?= (int)$request['id'] ?>&description=<?= urlencode('Radiology: ' . (string)($request['study_requested'] ?? $request['tests_requested'] ?? '')) ?>">Request Billing</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="summary-grid">
            <div class="summary-item"><span class="summary-label">Patient</span> <span class="summary-value"><?= e((string)($request['patient_name'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Hospital Number</span> <span class="summary-value"><?= e((string)($request['hospital_number'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Source</span> <span class="summary-value"><?= e((string)$request['request_source']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Priority</span> <span class="summary-value"><?= e((string)$request['priority']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Status</span> <span class="summary-value"><?= e((string)$request['status']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Requested By</span> <span class="summary-value"><?= e((string)($request['requested_by_name'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Requested</span> <span class="summary-value"><?= e((string)($request['created_at'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Department</span> <span class="summary-value"><?= e((string)($request['department_name'] ?? '-')) ?></span></div>
        </div>
    </div>

    <div class="card">
        <h3>Study Requested</h3>
        <p><?= nl2br(e((string)($request['study_requested'] ?? $request['tests_requested'] ?? '')) ) ?></p>
    </div>

    <div class="card">
        <h3>Clinical Indication</h3>
        <?php if (trim((string)($request['clinical_indication'] ?? $request['clinical_information'] ?? '')) === ''): ?>
            <p class="text-muted">No clinical indication recorded.</p>
        <?php else: ?>
            <p><?= nl2br(e((string)($request['clinical_indication'] ?? $request['clinical_information'] ?? '')) ) ?></p>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="form-actions">
            <?php if (!$isClosed && !$isRequestClosed && $canProcess && (string)$request['status'] === 'Requested'): ?>
                <form method="post" action="start.php">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int)$request['id'] ?>">
                    <button type="submit" class="btn-primary">Start</button>
                </form>
            <?php endif; ?>
            <?php if (!$isClosed && !$isRequestClosed && ($canEnter || $canEdit)): ?>
                <a class="btn-secondary" href="report.php?id=<?= (int)$request['id'] ?>"><?= $result ? 'Edit Report' : 'Enter Report' ?></a>
            <?php endif; ?>
            <?php if (!$isClosed && !$isRequestClosed && $canComplete): ?>
                <form method="post" action="complete.php">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int)$request['id'] ?>">
                    <button type="submit" class="btn-secondary">Complete</button>
                </form>
            <?php endif; ?>
            <?php if (!$isClosed && !$isRequestClosed && $canProcess && (string)$request['status'] !== 'Completed'): ?>
                <form method="post" action="cancel.php" onsubmit="return confirm('Cancel this radiology request?');">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int)$request['id'] ?>">
                    <button type="submit" class="btn-danger">Cancel</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <h3>Report</h3>
        <?php if (!$result || trim((string)($result['impression'] ?? '')) === ''): ?>
            <p class="text-muted">No radiology result recorded.</p>
        <?php else: ?>
            <div class="summary-grid">
                <div class="summary-item"><span class="summary-label">Findings</span> <span class="summary-value"><?= e((string)($result['findings'] ?? '-')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Impression</span> <span class="summary-value"><?= e((string)($result['impression'] ?? '-')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Recommendation</span> <span class="summary-value"><?= e((string)($result['recommendation'] ?? '-')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Performed By</span> <span class="summary-value"><?= e((string)($result['performed_by_name'] ?? '-')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Completed By</span> <span class="summary-value"><?= e((string)($result['completed_by_name'] ?? '-')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Completed At</span> <span class="summary-value"><?= e((string)($result['result_completed_at'] ?? '-')) ?></span></div>
            </div>
            <?php if (trim((string)($result['findings'] ?? '')) !== ''): ?>
                <h4>Findings</h4>
                <p><?= nl2br(e((string)$result['findings'])) ?></p>
            <?php endif; ?>
            <h4>Impression</h4>
            <p><?= nl2br(e((string)$result['impression'])) ?></p>
            <?php if (trim((string)($result['recommendation'] ?? '')) !== ''): ?>
                <h4>Recommendation</h4>
                <p><?= nl2br(e((string)$result['recommendation'])) ?></p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>

