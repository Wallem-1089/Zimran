<?php

declare(strict_types=1);

if (!isset($visit, $patient)) {
    return;
}

$workspaceConsultation = $consultation ?? null;
$consultationStatus = $workspaceConsultation['status'] ?? 'Not Started';
$isClosedEncounter = in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true);
$canCreateClinicalLaboratoryRequest = isset($permissionService)
    && ($laboratoryTablesReady ?? false)
    && $permissionService->canCreateLaboratoryRequest($visit, $currentUser, 'Clinical');
$canCreateClinicalRadiologyRequest = isset($permissionService)
    && ($radiologyTablesReady ?? false)
    && $permissionService->canCreateRadiologyRequest($visit, $currentUser, 'Clinical');
$canCreateClinicalEcgRequest = isset($permissionService)
    && ($ecgTablesReady ?? false)
    && $permissionService->canCreateEcgRequest($visit, $currentUser, 'Clinical');
$canCreateClinicalPopRequest = isset($permissionService)
    && ($popTablesReady ?? false)
    && $permissionService->canCreatePopRequest($visit, $currentUser, 'Clinical');
$canCreateClinicalPhysiotherapyRequest = isset($permissionService)
    && ($physiotherapyTablesReady ?? false)
    && $permissionService->canCreatePhysiotherapyRequest($visit, $currentUser, 'Clinical');
$canStartClinicalTheatreRecord = isset($permissionService)
    && ($theatreTablesReady ?? false)
    && $permissionService->canCreateTheatre($visit, $currentUser);
$canCreateClinicalPrescription = isset($permissionService)
    && ($pharmacyTablesReady ?? false)
    && $permissionService->canCreatePrescription($visit, $currentUser, 'Clinical');
?>

<section id="tab-consultation" class="workspace-tab">
    <div class="card">
        <div class="card-header">
            <div>
                <h2>Consultation</h2>
                <p>Encounter consultation, clinical assessment, diagnosis summary and treatment plan.</p>
            </div>
            <div>
                <?php if (!$canViewConsultation): ?>
                    <span class="badge badge-warning">No consultation permission</span>
                <?php elseif (!$workspaceConsultation && $canCreateConsultation): ?>
                    <a href="../consultation/create.php?visit=<?= (int)$visit['id'] ?>" class="btn-primary">Start Consultation</a>
                <?php elseif ($workspaceConsultation): ?>
                    <a href="../consultation/view.php?id=<?= (int)$workspaceConsultation['id'] ?>" class="btn-secondary">View</a>
                    <?php if ((string)$workspaceConsultation['status'] === 'Draft' && $canEditConsultation): ?>
                        <a href="../consultation/edit.php?id=<?= (int)$workspaceConsultation['id'] ?>" class="btn-primary">Continue/Edit</a>
                    <?php endif; ?>
                    <?php if (!$isClosedEncounter && !empty($billingRequestsReady) && !empty($canCreateBillingRequest)): ?>
                        <a href="../billing/request_create.php?visit=<?= (int)$visit['id'] ?>&source_module=Consultation&source_record_id=<?= (int)$workspaceConsultation['id'] ?>&description=<?= rawurlencode('Consultation') ?>" class="btn-secondary">Request Billing</a>
                    <?php endif; ?>
                <?php endif; ?>
        </div>
    </div>

    <?php if (!$isClosedEncounter && (
        $canCreateClinicalLaboratoryRequest
        || $canCreateClinicalRadiologyRequest
        || $canCreateClinicalEcgRequest
        || $canCreateClinicalPopRequest
        || $canCreateClinicalPhysiotherapyRequest
        || $canStartClinicalTheatreRecord
        || $canCreateClinicalPrescription
    )): ?>
        <div class="card">
            <div class="card-header">
                <div>
                    <h3>Clinical Requests &amp; Orders</h3>
                    <p>Create patient-specific requests from the Consultation context. Processing remains with the receiving department.</p>
                </div>
                <div class="form-actions">
                    <?php if ($canCreateClinicalLaboratoryRequest): ?>
                        <a class="btn-secondary" href="../laboratory/create.php?visit=<?= (int)$visit['id'] ?>&source=Clinical">Request Laboratory</a>
                    <?php endif; ?>
                    <?php if ($canCreateClinicalRadiologyRequest): ?>
                        <a class="btn-secondary" href="../radiology/request.php?visit=<?= (int)$visit['id'] ?>&source=Clinical">Request X-Ray / Radiology</a>
                    <?php endif; ?>
                    <?php if ($canCreateClinicalEcgRequest): ?>
                        <a class="btn-secondary" href="../ecg/request.php?visit=<?= (int)$visit['id'] ?>&source=Clinical">Request ECG</a>
                    <?php endif; ?>
                    <?php if ($canCreateClinicalPopRequest): ?>
                        <a class="btn-secondary" href="../pop/request.php?visit=<?= (int)$visit['id'] ?>&source=Clinical">Request POP / Casting</a>
                    <?php endif; ?>
                    <?php if ($canCreateClinicalPhysiotherapyRequest): ?>
                        <a class="btn-secondary" href="../physiotherapy/request.php?visit=<?= (int)$visit['id'] ?>&source=Clinical">Refer Physiotherapy</a>
                    <?php endif; ?>
                    <?php if ($canStartClinicalTheatreRecord): ?>
                        <a class="btn-secondary" href="<?= !empty($latestTheatreRecord) ? '../theatre/view.php?id=' . (int)$latestTheatreRecord['id'] : '../theatre/create.php?visit=' . (int)$visit['id'] ?>">
                            <?= !empty($latestTheatreRecord) ? 'Open Theatre Record' : 'Start Theatre Record' ?>
                        </a>
                    <?php endif; ?>
                    <?php if ($canCreateClinicalPrescription): ?>
                        <a class="btn-primary" href="../pharmacy/prescribe.php?visit=<?= (int)$visit['id'] ?>&source=Clinical">Create Prescription</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($canViewVitalSigns) && !$canViewVitalSigns): ?>
        <div class="card alert-warning">
            You do not have permission to view vital signs.
        </div>
    <?php elseif (!empty($latestVitalSigns) || isset($vitalSignsTablesReady)): ?>
        <?php if (!$vitalSignsTablesReady): ?>
            <div class="card">
                <p>Vital Signs tables are not available yet. Apply Migration 023 to enable this section.</p>
            </div>
        <?php elseif (!empty($latestVitalSigns)): ?>
            <div class="card">
                <h3>Latest Vital Signs</h3>
                <div class="summary-grid">
                    <div class="summary-item"><span class="summary-label">Temperature</span> <span class="summary-value"><?= e((string)($latestVitalSigns['temperature'] ?? '-')) ?></span></div>
                    <div class="summary-item"><span class="summary-label">Pulse</span> <span class="summary-value"><?= e((string)($latestVitalSigns['pulse'] ?? '-')) ?></span></div>
                    <div class="summary-item"><span class="summary-label">Respiratory Rate</span> <span class="summary-value"><?= e((string)($latestVitalSigns['respiratory_rate'] ?? '-')) ?></span></div>
                    <div class="summary-item"><span class="summary-label">Blood Pressure</span> <span class="summary-value"><?= e((string)($latestVitalSigns['blood_pressure'] ?? '-')) ?></span></div>
                    <div class="summary-item"><span class="summary-label">Oxygen Saturation</span> <span class="summary-value"><?= e((string)($latestVitalSigns['oxygen_saturation'] ?? '-')) ?></span></div>
                    <div class="summary-item"><span class="summary-label">BMI</span> <span class="summary-value"><?= e((string)($latestVitalSigns['bmi'] ?? '-')) ?></span></div>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <h3>Latest Vital Signs</h3>
                <p>No vital signs recorded.</p>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="summary-grid">
        <div class="summary-item">
            <span class="summary-label">Encounter</span>
            <span class="summary-value"><?= e((string)($visit['visit_number'] ?? ('#' . (int)$visit['id']))) ?></span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Hospital Number</span>
                <span class="summary-value"><?= e((string)$patient['hospital_number']) ?></span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Consultation Status</span>
                <span class="summary-value"><?= e((string)$consultationStatus) ?></span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Clinical Doctor</span>
                <span class="summary-value"><?= e((string)($workspaceConsultation['doctor_name'] ?? $visit['doctor_name'] ?? 'Not Assigned')) ?></span>
            </div>
        </div>
    </div>

    <?php if (!$canViewConsultation): ?>
        <div class="card alert-warning">
            You do not have permission to view consultation details.
        </div>
    <?php elseif (!$workspaceConsultation): ?>
        <div class="card">
            <h3>No Consultation Yet</h3>
            <?php if (!($consultationTablesReady ?? true)): ?>
                <p>Consultation tables are not available yet. Apply Migration 022 to enable this section.</p>
            <?php else: ?>
                <p>No consultation record has been opened for this encounter.</p>
            <?php endif; ?>
            <?php if (!$canCreateConsultation): ?>
                <p class="text-muted">You can view the workspace, but you cannot start a consultation.</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <?php
        $sections = [
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
        ?>
        <?php foreach ($sections as $field => $label): ?>
            <div class="card">
                <h3><?= e($label) ?></h3>
                <?php if (trim((string)($workspaceConsultation[$field] ?? '')) === ''): ?>
                    <div class="empty-state">Not recorded.</div>
                <?php else: ?>
                    <p><?= nl2br(e((string)$workspaceConsultation[$field])) ?></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <?php if ((string)$workspaceConsultation['status'] === 'Draft' && $canCompleteConsultation): ?>
            <div class="card">
                <h3>Complete Consultation</h3>
                <p>Completing the consultation makes it view-only.</p>
                <form method="post" action="../consultation/complete.php">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int)$workspaceConsultation['id'] ?>">
                    <button type="submit" class="btn-primary">Complete Consultation</button>
                </form>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>
