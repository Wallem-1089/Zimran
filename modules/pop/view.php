<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$requestId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
if (!$requestId) {
    header('Location: index.php');
    exit;
}
if (!$popTablesReady) {
    http_response_code(503);
    exit('POP tables are not available yet. Apply Migration 059 to enable this section.');
}

$request = $popService->getRequestById($requestId, $currentUser);
if (!$request) {
    http_response_code(404);
    exit('POP request not found.');
}
$visit = popRequireVisit($visitService, (int)$request['visit_id']);
$patient = $patientService->getPatientById((int)$request['patient_id']);
if (!$patient) {
    http_response_code(404);
    exit('Patient not found.');
}

$record = $popService->getRecord($requestId, $currentUser);
$canProcess = $permissionService->canProcessPopRequest($visit, $currentUser);
$canRecord = $permissionService->canRecordPopProcedure($visit, $currentUser);
$canEdit = $permissionService->canEditPopRecord($visit, $currentUser);
$canComplete = $permissionService->canCompletePopRequest($visit, $currentUser);
$isClosed = in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true);
$isRequestClosed = in_array((string)($request['status'] ?? ''), ['Completed', 'Cancelled'], true);
$hasRecord = $record && !empty($record['record_id']);

$pageTitle = 'POP Request';
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
            <ul><?php foreach ((array)$_SESSION['validation_errors'] as $error): ?><li><?= e((string)$error) ?></li><?php endforeach; ?></ul>
        </div>
        <?php unset($_SESSION['validation_errors']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert-success"><?= e((string)$_SESSION['success_message']) ?></div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <div class="page-header">
        <div>
            <h1>POP Request #<?= (int)$request['id'] ?></h1>
            <p><?= e((string)($request['visit_number'] ?? ('Encounter #' . (int)$request['visit_id']))) ?></p>
        </div>
        <div class="form-actions">
            <button class="btn-secondary" type="button" onclick="window.print()">Print POP Record</button>
            <?php if ($permissionService->canViewPopWorklist($currentUser)): ?>
                <a class="btn-secondary" href="index.php">Worklist</a>
            <?php endif; ?>
            <a class="btn-secondary" href="<?= e(popBackToWorkspace((int)$request['visit_id'])) ?>">Workspace</a>
            <a class="btn-secondary" href="history.php?visit=<?= (int)$request['visit_id'] ?>">History</a>
            <?php if (!$isClosed && $permissionService->canCreateBillingRequest($currentUser)): ?>
                <a class="btn-secondary" href="../billing/request_create.php?visit=<?= (int)$request['visit_id'] ?>&source_module=POP&source_record_id=<?= (int)$request['id'] ?>&description=<?= urlencode('POP: ' . (string)($request['procedure_requested'] ?? 'POP / Casting')) ?>">Request Billing</a>
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
            <div class="summary-item"><span class="summary-label">Department</span> <span class="summary-value"><?= e((string)($request['department_name'] ?? 'POP')) ?></span></div>
        </div>
    </div>

    <div class="card">
        <h3>Procedure Requested</h3>
        <p><?= nl2br(e((string)($request['procedure_requested'] ?? 'POP / Casting'))) ?></p>
        <h3>Clinical Indication / Reason</h3>
        <p><?= trim((string)($request['clinical_indication'] ?? '')) === '' ? '<span class="text-muted">No clinical indication recorded.</span>' : nl2br(e((string)$request['clinical_indication'])) ?></p>
    </div>

    <div class="card">
        <div class="form-actions">
            <?php if (!$isClosed && !$isRequestClosed && $canProcess && (string)$request['status'] === 'Requested'): ?>
                <form method="post" action="start.php"><?= csrfField() ?><input type="hidden" name="id" value="<?= (int)$request['id'] ?>"><button type="submit" class="btn-primary">Start</button></form>
            <?php endif; ?>
            <?php if (!$isClosed && !$isRequestClosed && ($canRecord || $canEdit)): ?>
                <a class="btn-secondary" href="record.php?id=<?= (int)$request['id'] ?>"><?= $hasRecord ? 'Edit POP Record' : 'Record POP Procedure' ?></a>
            <?php endif; ?>
            <?php if (!$isClosed && !$isRequestClosed && $canComplete): ?>
                <form method="post" action="complete.php"><?= csrfField() ?><input type="hidden" name="id" value="<?= (int)$request['id'] ?>"><button type="submit" class="btn-secondary">Complete</button></form>
            <?php endif; ?>
            <?php if (!$isClosed && !$isRequestClosed && $canProcess): ?>
                <form method="post" action="cancel.php" onsubmit="return confirm('Cancel this POP request?');"><?= csrfField() ?><input type="hidden" name="id" value="<?= (int)$request['id'] ?>"><button type="submit" class="btn-secondary">Cancel Request</button></form>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <h3>POP / Casting Record</h3>
        <?php if ($hasRecord): ?>
            <div class="summary-grid">
                <div class="summary-item"><span class="summary-label">Cast Type</span> <span class="summary-value"><?= e((string)($record['cast_type'] ?? '-')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Body Part</span> <span class="summary-value"><?= e((string)($record['body_part'] ?? '-')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Performed By</span> <span class="summary-value"><?= e((string)($record['performed_by_name'] ?? '-')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Completed By</span> <span class="summary-value"><?= e((string)($record['completed_by_name'] ?? '-')) ?></span></div>
            </div>
            <h4>Procedure Notes</h4><p><?= nl2br(e((string)($record['procedure_notes'] ?? ''))) ?></p>
            <h4>Materials Used</h4><p><?= trim((string)($record['materials_used'] ?? '')) === '' ? '<span class="text-muted">No materials recorded.</span>' : nl2br(e((string)$record['materials_used'])) ?></p>
            <h4>Aftercare Instructions</h4><p><?= trim((string)($record['aftercare_instructions'] ?? '')) === '' ? '<span class="text-muted">No aftercare instructions recorded.</span>' : nl2br(e((string)$record['aftercare_instructions'])) ?></p>
            <h4>Remarks</h4><p><?= trim((string)($record['remarks'] ?? '')) === '' ? '<span class="text-muted">No remarks recorded.</span>' : nl2br(e((string)$record['remarks'])) ?></p>
        <?php else: ?>
            <p class="text-muted">No POP procedure record yet.</p>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
