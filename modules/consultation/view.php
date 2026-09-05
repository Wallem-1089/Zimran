<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$consultationId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$visitId = filter_input(INPUT_GET, 'visit', FILTER_VALIDATE_INT) ?: 0;
$consultation = $consultationId > 0
    ? $consultationService->getById($consultationId)
    : ($visitId > 0 ? $consultationService->getByVisit($visitId) : null);
if (!$consultation) {
    http_response_code(404);
    exit('Consultation not found.');
}

$visit = consultationRequireVisit($visitService, (int)$consultation['visit_id']);
consultationRequireAccess($permissionService, $visit, $currentUser);

$canEdit = (string)$consultation['status'] === 'Draft'
    && $permissionService->canEditConsultation($visit, $currentUser);
$canComplete = (string)$consultation['status'] === 'Draft'
    && $permissionService->canCompleteConsultation($visit, $currentUser);
$latestVitalSigns = $vitalSignsService
    ? $vitalSignsService->getLatestByVisit((int)$visit['id'], $currentUser)
    : null;
$latestLaboratoryRequests = $laboratoryService
    ? $laboratoryService->listByVisit((int)$visit['id'], $currentUser)
    : [];
$latestLaboratoryResult = ($latestLaboratoryRequests !== [] && $laboratoryService)
    ? $laboratoryService->getResult((int)$latestLaboratoryRequests[0]['id'], $currentUser)
    : null;
$latestRadiologyRequests = $radiologyService
    ? $radiologyService->listByVisit((int)$visit['id'], $currentUser)
    : [];
$latestRadiologyReport = ($latestRadiologyRequests !== [] && $radiologyService)
    ? $radiologyService->getResult((int)$latestRadiologyRequests[0]['id'], $currentUser)
    : null;
$latestEcgRequests = $ecgService
    ? $ecgService->listByVisit((int)$visit['id'], $currentUser)
    : [];
$latestEcgReport = ($latestEcgRequests !== [] && $ecgService)
    ? $ecgService->getReport((int)$latestEcgRequests[0]['id'], $currentUser)
    : null;
$latestPopRequests = $popService
    ? $popService->listByVisit((int)$visit['id'], $currentUser)
    : [];
$latestPopRecord = ($latestPopRequests !== [] && $popService)
    ? $popService->getRecord((int)$latestPopRequests[0]['id'], $currentUser)
    : null;
$latestPharmacyRequests = $pharmacyService
    ? $pharmacyService->listByVisit((int)$visit['id'], $currentUser)
    : [];
$latestPharmacyPrescription = $latestPharmacyRequests[0] ?? null;
$canCreatePrescription = $permissionService->canCreatePrescription($visit, $currentUser, 'Clinical');

$pageTitle = 'Consultation';
$moduleStylesheet = '/modules/visits/assets/visits.css';
$fields = [
    'presenting_complaint' => 'Presenting Complaint',
    'history_of_presenting_complaint' => 'History of Presenting Complaint',
    'examination_findings' => 'Examination Findings',
    'assessment' => 'Assessment',
    'diagnosis' => 'Diagnosis',
    'treatment_plan' => 'Treatment Plan',
    'advice' => 'Advice',
    'follow_up' => 'Follow Up',
    'referral_notes' => 'Referral Notes'
];
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert-success"><?= e($_SESSION['success_message']) ?></div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['validation_errors'])): ?>
        <div class="alert-danger"><?= e(implode(' ', (array)$_SESSION['validation_errors'])) ?></div>
        <?php unset($_SESSION['validation_errors']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert-danger"><?= nl2br(e((string)$_SESSION['error_message'])) ?></div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <div class="page-header">
        <div>
            <h1>Consultation</h1>
            <p><?= e((string)$consultation['visit_number']) ?> | <?= e((string)$consultation['status']) ?></p>
        </div>
        <div>
            <a class="btn-secondary" href="<?= e(consultationBackToWorkspace((int)$consultation['visit_id'])) ?>">Workspace</a>
            <?php if (!in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true) && $permissionService->canCreateBillingRequest($currentUser)): ?>
                <a class="btn-secondary" href="../billing/request_create.php?visit=<?= (int)$consultation['visit_id'] ?>&source_module=Consultation&source_record_id=<?= (int)$consultation['id'] ?>&description=<?= urlencode('Consultation: ' . (string)($consultation['diagnosis'] ?? $consultation['presenting_complaint'] ?? '')) ?>">Request Billing</a>
            <?php endif; ?>
            <?php if ($canEdit): ?>
                <a class="btn-secondary" href="edit.php?id=<?= (int)$consultation['id'] ?>">Edit</a>
            <?php endif; ?>
            <?php if ($canComplete): ?>
                <form method="post" action="complete.php" style="display:inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int)$consultation['id'] ?>">
                    <button class="btn-primary" type="submit">Complete</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="summary-grid">
            <div class="summary-item"><span class="summary-label">Clinical Doctor</span> <span class="summary-value"><?= e((string)$consultation['doctor_name']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Department</span> <span class="summary-value"><?= e((string)$consultation['department_name']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Created By</span> <span class="summary-value"><?= e((string)$consultation['created_by_name']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Completed</span> <span class="summary-value"><?= e((string)($consultation['completed_at'] ?? 'Not completed')) ?></span></div>
        </div>
    </div>

    <?php if ($latestVitalSigns !== null): ?>
        <div class="card">
            <h3>Latest Vital Signs</h3>
            <?php $latest = $latestVitalSigns; require __DIR__ . '/../vital_signs/partials/record_card.php'; ?>
        </div>
    <?php else: ?>
        <div class="card"><h3>Latest Vital Signs</h3><p>No vital signs recorded.</p></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <div>
                <h3>Laboratory Requests</h3>
                <p>Encounter laboratory requests and results.</p>
            </div>
            <?php if ($permissionService->canCreateLaboratoryRequest($visit, $currentUser, 'Clinical')): ?>
                <a class="btn-primary" href="../laboratory/create.php?visit=<?= (int)$visit['id'] ?>&source=Clinical">Request Laboratory Test</a>
            <?php endif; ?>
        </div>
        <?php if ($latestLaboratoryRequests === []): ?>
            <p class="text-muted">No laboratory requests recorded.</p>
        <?php else: ?>
            <ul class="clean-list">
                <?php foreach (array_slice($latestLaboratoryRequests, 0, 5) as $request): ?>
                    <li>
                        <a href="../laboratory/view.php?id=<?= (int)$request['id'] ?>">#<?= (int)$request['id'] ?></a>
                        — <?= e((string)$request['tests_requested']) ?>
                        (<?= e((string)$request['status']) ?>)
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php if ($latestLaboratoryResult !== null && trim((string)($latestLaboratoryResult['result'] ?? '')) !== ''): ?>
                <div class="summary-grid">
                    <div class="summary-item"><span class="summary-label">Sample Taken</span> <span class="summary-value"><?php hmsRenderNarrative((string)($latestLaboratoryResult['sample_taken'] ?? '-')); ?></span></div>
                    <div class="summary-item"><span class="summary-label">Findings</span> <span class="summary-value"><?php hmsRenderNarrative((string)($latestLaboratoryResult['findings'] ?? '-')); ?></span></div>
                    <div class="summary-item"><span class="summary-label">Result</span> <span class="summary-value"><?php hmsRenderNarrative((string)$latestLaboratoryResult['result']); ?></span></div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <h3>Radiology Requests</h3>
                <p>Encounter radiology requests and reports.</p>
            </div>
            <?php if ($permissionService->canCreateRadiologyRequest($visit, $currentUser, 'Clinical')): ?>
                <a class="btn-primary" href="../radiology/request.php?visit=<?= (int)$visit['id'] ?>&source=Clinical">Request X-Ray / Radiology</a>
            <?php endif; ?>
        </div>
        <?php if ($latestRadiologyRequests === []): ?>
            <p class="text-muted">No radiology requests recorded.</p>
        <?php else: ?>
            <ul class="clean-list">
                <?php foreach (array_slice($latestRadiologyRequests, 0, 5) as $request): ?>
                    <li>
                        <a href="../radiology/view.php?id=<?= (int)$request['id'] ?>">#<?= (int)$request['id'] ?></a>
                        — <?= e((string)($request['study_requested'] ?? $request['tests_requested'] ?? '')) ?>
                        (<?= e((string)$request['status']) ?>)
                    </li>
                <?php endforeach; ?>
            </ul>
                <?php if ($latestRadiologyReport !== null && trim((string)($latestRadiologyReport['impression'] ?? '')) !== ''): ?>
                    <div class="summary-grid">
                        <div class="summary-item"><span class="summary-label">Findings</span> <span class="summary-value"><?php hmsRenderNarrative((string)($latestRadiologyReport['findings'] ?? '-')); ?></span></div>
                        <div class="summary-item"><span class="summary-label">Impression</span> <span class="summary-value"><?php hmsRenderNarrative((string)($latestRadiologyReport['impression'] ?? '-')); ?></span></div>
                        <div class="summary-item"><span class="summary-label">Recommendation</span> <span class="summary-value"><?php hmsRenderNarrative((string)($latestRadiologyReport['recommendation'] ?? '-')); ?></span></div>
                    </div>
                <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <h3>ECG Requests</h3>
                <p>Encounter ECG requests and scanned chart status.</p>
            </div>
            <?php if ($ecgService && $permissionService->canCreateEcgRequest($visit, $currentUser, 'Clinical')): ?>
                <a class="btn-primary" href="../ecg/request.php?visit=<?= (int)$visit['id'] ?>&source=Clinical">Request ECG</a>
            <?php endif; ?>
        </div>
        <?php if (!$ecgService): ?>
            <p class="text-muted">ECG tables are not available yet. Apply Migration 058 to enable this section.</p>
        <?php elseif ($latestEcgRequests === []): ?>
            <p class="text-muted">No ECG requests recorded.</p>
        <?php else: ?>
            <ul class="clean-list">
                <?php foreach (array_slice($latestEcgRequests, 0, 5) as $request): ?>
                    <li>
                        <a href="../ecg/view.php?id=<?= (int)$request['id'] ?>">#<?= (int)$request['id'] ?></a>
                        — <?= e((string)($request['study_requested'] ?? 'ECG')) ?>
                        (<?= e((string)$request['status']) ?>)
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php if ($latestEcgReport !== null && !empty($latestEcgReport['report_id'])): ?>
                <div class="summary-grid">
                    <div class="summary-item"><span class="summary-label">Chart</span> <span class="summary-value"><?= !empty($latestEcgReport['chart_stored_path']) ? 'Uploaded' : 'Pending' ?></span></div>
                    <div class="summary-item"><span class="summary-label">Notes</span> <span class="summary-value"><?php hmsRenderNarrative(trim((string)($latestEcgReport['notes'] ?? '')) !== '' ? (string)$latestEcgReport['notes'] : '-'); ?></span></div>
                    <div class="summary-item"><span class="summary-label">Remarks</span> <span class="summary-value"><?php hmsRenderNarrative(trim((string)($latestEcgReport['remarks'] ?? '')) !== '' ? (string)$latestEcgReport['remarks'] : '-'); ?></span></div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <h3>POP / Casting Requests</h3>
                <p>Encounter POP, casting and splinting requests.</p>
            </div>
            <?php if ($popService && $permissionService->canCreatePopRequest($visit, $currentUser, 'Clinical')): ?>
                <a class="btn-primary" href="../pop/request.php?visit=<?= (int)$visit['id'] ?>&source=Clinical">Request POP / Casting</a>
            <?php endif; ?>
        </div>
        <?php if (!$popService): ?>
            <p class="text-muted">POP tables are not available yet.</p>
        <?php elseif ($latestPopRequests === []): ?>
            <p class="text-muted">No POP / Casting requests recorded.</p>
        <?php else: ?>
            <ul class="clean-list">
                <?php foreach (array_slice($latestPopRequests, 0, 5) as $request): ?>
                    <li>
                        <a href="../pop/view.php?id=<?= (int)$request['id'] ?>">#<?= (int)$request['id'] ?></a>
                        — <?= e((string)($request['procedure_requested'] ?? 'POP / Casting')) ?>
                        (<?= e((string)$request['status']) ?>)
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php if ($latestPopRecord !== null && !empty($latestPopRecord['record_id'])): ?>
                <div class="summary-grid">
                    <div class="summary-item"><span class="summary-label">Procedure Done</span> <span class="summary-value"><?php hmsRenderNarrative((string)($latestPopRecord['procedure_done'] ?? '-')); ?></span></div>
                    <div class="summary-item"><span class="summary-label">Findings</span> <span class="summary-value"><?php hmsRenderNarrative((string)($latestPopRecord['findings'] ?? '-')); ?></span></div>
                    <div class="summary-item"><span class="summary-label">Remarks</span> <span class="summary-value"><?php hmsRenderNarrative((string)($latestPopRecord['remarks'] ?? '-')); ?></span></div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <h3>Pharmacy Prescriptions</h3>
                <p>Encounter prescriptions and dispensing status.</p>
            </div>
            <?php if ($pharmacyService && $canCreatePrescription): ?>
                <a class="btn-primary" href="../pharmacy/prescribe.php?visit=<?= (int)$visit['id'] ?>&source=Clinical">Create Prescription</a>
            <?php endif; ?>
        </div>
        <?php if (!$pharmacyService): ?>
            <p class="text-muted">Pharmacy tables are not available yet. Apply Migration 032 to enable this section.</p>
        <?php elseif ($latestPharmacyRequests === []): ?>
            <p class="text-muted">No prescriptions recorded for this encounter.</p>
        <?php else: ?>
            <ul class="clean-list">
                <?php foreach (array_slice($latestPharmacyRequests, 0, 5) as $prescription): ?>
                    <li>
                        <a href="../pharmacy/view.php?id=<?= (int)$prescription['id'] ?>">#<?= (int)$prescription['id'] ?></a>
                        — <?= e((string)$prescription['medication_name']) ?>
                        (<?= e((string)$prescription['status']) ?>)
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php if ($latestPharmacyPrescription !== null && (string)($latestPharmacyPrescription['status'] ?? '') === 'Dispensed'): ?>
                <div class="summary-grid">
                    <div class="summary-item"><span class="summary-label">Dispensed By</span> <span class="summary-value"><?= e((string)($latestPharmacyPrescription['dispensed_by_name'] ?? '-')) ?></span></div>
                    <div class="summary-item"><span class="summary-label">Dispensed At</span> <span class="summary-value"><?= e((string)($latestPharmacyPrescription['dispensed_recorded_at'] ?? '-')) ?></span></div>
                    <div class="summary-item"><span class="summary-label">Quantity Dispensed</span> <span class="summary-value"><?= e((string)($latestPharmacyPrescription['quantity_dispensed'] ?? '-')) ?></span></div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <h3>Physiotherapy</h3>
                <p>Encounter physiotherapy records and sessions.</p>
            </div>
            <?php if ($physiotherapyService && $permissionService->canCreatePhysiotherapyRequest($visit, $currentUser, 'Clinical')): ?>
                <a class="btn-primary" href="../physiotherapy/request.php?visit=<?= (int)$visit['id'] ?>&source=Clinical">Refer to Physiotherapy</a>
            <?php endif; ?>
        </div>
        <?php if (!$physiotherapyService): ?>
            <p class="text-muted">Physiotherapy tables are not available yet.</p>
        <?php else: ?>
            <?php
                $latestPhysiotherapyRecords = $physiotherapyService->listByVisit((int)$visit['id'], $currentUser);
                $latestPhysiotherapyRecord = $latestPhysiotherapyRecords[0] ?? null;
                $latestPhysiotherapySession = $latestPhysiotherapyRecord
                    ? $physiotherapyService->getResult((int)$latestPhysiotherapyRecord['id'], $currentUser)
                    : null;
            ?>
            <?php if ($latestPhysiotherapyRecords === []): ?>
                <p class="text-muted">No physiotherapy records recorded.</p>
            <?php else: ?>
                <ul class="clean-list">
                    <?php foreach (array_slice($latestPhysiotherapyRecords, 0, 5) as $record): ?>
                        <li>
                            <a href="../physiotherapy/view.php?id=<?= (int)$record['id'] ?>">#<?= (int)$record['id'] ?></a>
                            — <?= e((string)($record['presenting_problem'] ?? $record['summary'] ?? '')) ?>
                            (<?= e((string)$record['status']) ?>)
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php if ($latestPhysiotherapySession !== null && trim((string)($latestPhysiotherapySession['treatment_given'] ?? '')) !== ''): ?>
                    <div class="summary-grid">
                        <div class="summary-item"><span class="summary-label">Treatment Given</span> <span class="summary-value"><?php hmsRenderNarrative((string)($latestPhysiotherapySession['treatment_given'] ?? '-')); ?></span></div>
                        <div class="summary-item"><span class="summary-label">Patient Response</span> <span class="summary-value"><?php hmsRenderNarrative((string)($latestPhysiotherapySession['patient_response'] ?? '-')); ?></span></div>
                        <div class="summary-item"><span class="summary-label">Next Plan</span> <span class="summary-value"><?php hmsRenderNarrative((string)($latestPhysiotherapySession['next_plan'] ?? '-')); ?></span></div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <div class="card">
        <div class="card-header">
            <div>
                <h3>Theatre</h3>
                <p>Open or review the current theatre record.</p>
            </div>
            <?php if ($theatreService && $permissionService->canCreateTheatre($visit, $currentUser)): ?>
                <?php $existingTheatre = $theatreService->getByVisit((int)$visit['id'], $currentUser); ?>
                <a class="btn-primary" href="<?= $existingTheatre ? '../theatre/view.php?id=' . (int)$existingTheatre['id'] : '../theatre/create.php?visit=' . (int)$visit['id'] ?>">
                    <?= $existingTheatre ? 'Open Theatre' : 'Start Theatre Record' ?>
                </a>
            <?php endif; ?>
        </div>
        <?php if (!$theatreService): ?>
            <p class="text-muted">Theatre tables are not available yet.</p>
        <?php else: ?>
            <?php $existingTheatre = $theatreService->getByVisit((int)$visit['id'], $currentUser); ?>
            <?php if ($existingTheatre === null): ?>
                <p class="text-muted">No theatre record recorded.</p>
            <?php else: ?>
                <div class="summary-grid">
                    <div class="summary-item"><span class="summary-label">Procedure</span> <span class="summary-value"><?= e((string)($existingTheatre['procedure_name'] ?? '-')) ?></span></div>
                    <div class="summary-item"><span class="summary-label">Surgeon</span> <span class="summary-value"><?= e((string)($existingTheatre['surgeon_name'] ?? '-')) ?></span></div>
                    <div class="summary-item"><span class="summary-label">Status</span> <span class="summary-value"><?= e((string)($existingTheatre['status'] ?? '-')) ?></span></div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <?php foreach ($fields as $field => $label): ?>
        <div class="card">
            <h3><?= e($label) ?></h3>
            <?php consultationRenderNarrative((string)($consultation[$field] ?? '')); ?>
        </div>
    <?php endforeach; ?>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
