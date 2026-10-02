<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../../services/ClinicalBillingGateService.php';

$requestId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
if (!$requestId) {
    header('Location: index.php');
    exit;
}

if (!$ecgTablesReady) {
    http_response_code(503);
    exit('ECG tables are not available yet. Apply Migration 058 to enable this section.');
}

$request = $ecgService->getRequestById($requestId, $currentUser);
if (!$request) {
    http_response_code(404);
    exit('ECG request not found.');
}

$visit = ecgRequireVisit($visitService, (int)$request['visit_id']);
$patient = $patientService->getPatientById((int)$request['patient_id']);
if (!$patient) {
    http_response_code(404);
    exit('Patient not found.');
}

$report = $ecgService->getReport($requestId, $currentUser);
$diagnosticAttachments = $diagnosticAttachmentService->listForSource('ECG', $requestId, $currentUser);
$billingGate = new ClinicalBillingGateService($pdo);
$billingClearance = $billingGate->status('ECG', $requestId, (int)$request['visit_id'], $currentUser);
$billingCleared = (bool)($billingClearance['cleared'] ?? false);
$canCancelForBilling = $billingGate->cancellationErrors('ECG', $requestId, (int)$request['visit_id'], $currentUser) === [];
$canProcess = $permissionService->canProcessEcgRequest($visit, $currentUser);
$canUpload = $permissionService->canUploadEcgChart($visit, $currentUser);
$canEdit = $permissionService->canEditEcgReport($visit, $currentUser);
$canComplete = $permissionService->canCompleteEcgRequest($visit, $currentUser);
$isClosed = in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true);
$isRequestClosed = in_array((string)($request['status'] ?? ''), ['Completed', 'Cancelled'], true);
$ecgConfiguredDisplayValues = $configurableFormService->getResponseValues('ecg_report', 'ECG Report', $requestId);

$pageTitle = 'ECG Request';
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
            <h1>ECG Request #<?= (int)$request['id'] ?></h1>
            <p><?= e((string)($request['visit_number'] ?? ('Encounter #' . (int)$request['visit_id']))) ?></p>
        </div>
        <div class="form-actions">
            <button class="btn-secondary" type="button" onclick="window.print()">Print ECG Record</button>
            <?php if ($permissionService->canViewEcgWorklist($currentUser)): ?>
                <a class="btn-secondary" href="index.php">Worklist</a>
            <?php endif; ?>
            <a class="btn-secondary" href="<?= e(ecgBackToWorkspace((int)$request['visit_id'])) ?>">Workspace</a>
            <a class="btn-secondary" href="history.php?visit=<?= (int)$request['visit_id'] ?>">History</a>
            <?php if (!$isClosed && $permissionService->canCreateBillingRequest($currentUser)): ?>
                <a class="btn-secondary" href="../billing/request_create.php?visit=<?= (int)$request['visit_id'] ?>&source_module=ECG&source_record_id=<?= (int)$request['id'] ?>&description=<?= urlencode('ECG: ' . (string)($request['study_requested'] ?? 'ECG')) ?>">Request Billing</a>
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
            <div class="summary-item"><span class="summary-label">Accounts Clearance</span> <span class="summary-value"><?= e((string)($billingClearance['label'] ?? 'Unknown')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Requested By</span> <span class="summary-value"><?= e((string)($request['requested_by_name'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Requested</span> <span class="summary-value"><?= e((string)($request['created_at'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Department</span> <span class="summary-value"><?= e((string)($request['department_name'] ?? 'ECG')) ?></span></div>
        </div>
    </div>

    <div class="card">
        <h3>Study Requested</h3>
        <p><?php hmsRenderNarrative((string)($request['study_requested'] ?? 'ECG')); ?></p>
    </div>

    <div class="card">
        <h3>Clinical Indication</h3>
        <?php if (trim((string)($request['clinical_indication'] ?? '')) === ''): ?>
            <p class="text-muted">No clinical indication recorded.</p>
        <?php else: ?>
            <p><?php hmsRenderNarrative((string)$request['clinical_indication']); ?></p>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="form-actions">
            <?php if (!$billingCleared): ?>
                <p class="text-muted">Awaiting Accounts clearance before ECG can start, upload chart, or complete this request.</p>
            <?php endif; ?>
            <?php if ($billingCleared && !$isClosed && !$isRequestClosed && ($canUpload || $canEdit)): ?>
                <a class="btn-primary" href="report.php?id=<?= (int)$request['id'] ?>"><?= $report && !empty($report['report_id']) ? 'Edit ECG Report' : 'Enter ECG Report' ?></a>
            <?php endif; ?>
            <?php if ($billingCleared && !$isClosed && !$isRequestClosed && $canComplete): ?>
                <form method="post" action="complete.php">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int)$request['id'] ?>">
                    <button type="submit" class="btn-secondary">Complete</button>
                </form>
            <?php endif; ?>
            <?php if ($canCancelForBilling && !$isClosed && !$isRequestClosed && $canProcess): ?>
                <form method="post" action="cancel.php" onsubmit="return confirm('Cancel this ECG request?');">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int)$request['id'] ?>">
                    <button type="submit" class="btn-secondary">Cancel Request</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <h3>ECG Report and Notes</h3>
        <?php if ($report && !empty($report['report_id'])): ?>
            <div class="summary-grid">
                <div class="summary-item"><span class="summary-label">Legacy Chart</span> <span class="summary-value">
                    <?php if (!empty($report['chart_stored_path'])): ?>
                        <a href="download_chart.php?id=<?= (int)$request['id'] ?>" target="_blank" rel="noopener">Open scanned ECG chart</a>
                    <?php else: ?>
                        Use Uploaded Attachments below
                    <?php endif; ?>
                </span></div>
                <div class="summary-item"><span class="summary-label">Uploaded By</span> <span class="summary-value"><?= e((string)($report['performed_by_name'] ?? '-')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Completed By</span> <span class="summary-value"><?= e((string)($report['completed_by_name'] ?? '-')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Completed At</span> <span class="summary-value"><?= e((string)($report['report_completed_at'] ?? '-')) ?></span></div>
            </div>
            <h4>Notes</h4>
            <p><?php trim((string)($report['notes'] ?? '')) === '' ? print '<span class="text-muted">No ECG notes recorded.</span>' : hmsRenderNarrative((string)$report['notes']); ?></p>
            <h4>Remarks</h4>
            <p><?php trim((string)($report['remarks'] ?? '')) === '' ? print '<span class="text-muted">No ECG remarks recorded.</span>' : hmsRenderNarrative((string)$report['remarks']); ?></p>
        <?php else: ?>
            <p class="text-muted">No ECG report notes recorded.</p>
        <?php endif; ?>
    </div>
    <?php if ($diagnosticAttachments !== []): ?>
        <div class="card">
            <h3>Uploaded Attachments</h3>
            <div class="table-responsive">
                <table class="summary-table">
                    <thead>
                        <tr>
                            <th>File</th>
                            <th>Uploaded By</th>
                            <th>Uploaded At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($diagnosticAttachments as $attachment): ?>
                            <tr>
                                <td><?= e((string)$attachment['original_filename']) ?></td>
                                <td><?= e((string)($attachment['uploaded_by_name'] ?? '-')) ?></td>
                                <td><?= e((string)($attachment['uploaded_at'] ?? '-')) ?></td>
                                <td><a class="btn-secondary btn-sm" href="../diagnostic_attachments/download.php?id=<?= (int)$attachment['id'] ?>" target="_blank" rel="noopener">Open</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
    <?php hmsRenderConfiguredValues($ecgConfiguredDisplayValues); ?>
    <?php if ($report && !empty($report['report_id'])): ?>
        <div class="card whatsapp-handoff-card">
            <div class="whatsapp-handoff-header">
                <div>
                    <h3>Send ECG via WhatsApp</h3>
                    <p class="text-muted">Opens WhatsApp with a safe message. Select files below, then attach them manually in WhatsApp.</p>
                </div>
                <span class="whatsapp-pill">Patient handoff</span>
            </div>
            <form class="whatsapp-handoff-form" method="post" action="../patient_communications/whatsapp_handoff.php" target="_blank">
                <?= csrfField() ?>
                <input type="hidden" name="source_type" value="ecg_report">
                <input type="hidden" name="source_id" value="<?= (int)$request['id'] ?>">
                <input type="hidden" name="return_url" value="../ecg/view.php?id=<?= (int)$request['id'] ?>">
                <?php if ($diagnosticAttachments !== []): ?>
                    <div class="form-group">
                        <label>Attachments to send</label>
                        <?php foreach ($diagnosticAttachments as $attachment): ?>
                            <label class="inline-check">
                                <input type="checkbox" name="attachment_ids[]" value="<?= (int)$attachment['id'] ?>">
                                <?= e((string)$attachment['original_filename']) ?>
                            </label>
                        <?php endforeach; ?>
                        <small class="text-muted">Selected files are listed in the WhatsApp message for manual attachment.</small>
                    </div>
                <?php endif; ?>
                <label class="inline-check whatsapp-consent">
                    <input type="checkbox" name="patient_consent_confirmed" value="1" required>
                    Patient consent confirmed
                </label>
                <button class="btn-whatsapp" type="submit">Send via WhatsApp</button>
            </form>
        </div>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
