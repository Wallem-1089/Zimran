<?php

declare(strict_types=1);

if (!isset($visit, $patient)) {
    return;
}

$assessment = $nursing ?? null;
$latestVitals = $latestVitalSigns ?? null;
$dressingTablesReady = $dressingTablesReady ?? false;
$dressingRecords = $dressingRecords ?? [];
$latestDressingRecord = $latestDressingRecord ?? ($dressingRecords[0] ?? null);
$medicationAdministrationTablesReady = $medicationAdministrationTablesReady ?? false;
$medicationAdministrationRecords = $medicationAdministrationRecords ?? [];
$latestMedicationAdministrationRecord = $latestMedicationAdministrationRecord ?? ($medicationAdministrationRecords[0] ?? null);
$drugChartPrescriptions = $drugChartPrescriptions ?? [];
$diabetesMonitoringTablesReady = $diabetesMonitoringTablesReady ?? false;
$diabetesMonitoringRecords = $diabetesMonitoringRecords ?? [];
$latestDiabetesMonitoringRecord = $latestDiabetesMonitoringRecord ?? ($diabetesMonitoringRecords[0] ?? null);
$isClosedEncounter = in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true);
?>

<section id="tab-nursing" class="workspace-tab">
    <div class="card">
        <div class="card-header">
            <div>
                <h2>Nursing Assessment</h2>
                <p>Nursing observations, interventions and handover notes.</p>
            </div>
            <div>
                <?php if (!$nursingTablesReady): ?>
                    <span class="badge badge-warning">Migration required</span>
                <?php elseif (!$canViewNursing): ?>
                    <span class="badge badge-warning">No nursing permission</span>
                <?php elseif ($assessment === null && $canCreateNursing && (!$isClosedEncounter || $permissionService->isAdministrator($currentUser))): ?>
                    <a href="../nursing/assessment.php?visit=<?= (int)$visit['id'] ?>" class="btn-primary">Start Nursing Assessment</a>
                <?php elseif ($assessment !== null): ?>
                    <a href="../nursing/history.php?visit=<?= (int)$visit['id'] ?>" class="btn-secondary">View History</a>
                    <?php if ($canCreateNursing && (!$isClosedEncounter || $permissionService->isAdministrator($currentUser)) && (string)($assessment['status'] ?? '') === 'Draft'): ?>
                        <a href="../nursing/edit.php?id=<?= (int)$assessment['id'] ?>" class="btn-primary">Continue/Edit</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

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
                <span class="summary-label">Latest Record</span>
                <span class="summary-value"><?= e($assessment['created_at'] ?? 'Not recorded') ?></span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Nurse</span>
                <span class="summary-value"><?= e($assessment['nurse_name'] ?? 'Unknown') ?></span>
            </div>
        </div>
    </div>

    <?php if (!$nursingTablesReady): ?>
        <div class="card">
            <p>Nursing tables are not available yet. Apply Migration 024 to enable this section.</p>
        </div>
    <?php elseif (!$canViewNursing): ?>
        <div class="card alert-warning">
            You do not have permission to view nursing assessments.
        </div>
    <?php elseif ($assessment === null): ?>
        <div class="card">
            <p class="text-muted">No nursing assessment recorded.</p>
            <?php if ($canCreateNursing && (!$isClosedEncounter || $permissionService->isAdministrator($currentUser))): ?>
                <p><a href="../nursing/create.php?visit=<?= (int)$visit['id'] ?>" class="btn-primary">Start Nursing Assessment</a></p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="card">
            <h3>Latest Nursing Assessment</h3>
            <?php $latest = $assessment; require __DIR__ . '/../../../nursing/partials/record_card.php'; ?>
        </div>

        <div class="card">
            <div class="form-actions">
                <a href="../nursing/history.php?visit=<?= (int)$visit['id'] ?>" class="btn-secondary">View History</a>
                <a href="../medical_records/chart.php?patient=<?= (int)$patient['id'] ?>&tab=nursing&visit=<?= (int)$visit['id'] ?>" class="btn-secondary">Open Patient Chart</a>
                <?php if ($canCreateNursing && (!$isClosedEncounter || $permissionService->isAdministrator($currentUser))): ?>
                    <a href="../nursing/create.php?visit=<?= (int)$visit['id'] ?>" class="btn-primary">Start New Draft</a>
                <?php endif; ?>
                <?php if ($canEditNursing && !$isClosedEncounter && (string)($assessment['status'] ?? '') === 'Draft'): ?>
                    <a href="../nursing/edit.php?id=<?= (int)$assessment['id'] ?>" class="btn-secondary">Edit Draft</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($latestVitals !== null): ?>
        <div class="card">
            <h3>Latest Vital Signs</h3>
            <?php $latest = $latestVitals; require __DIR__ . '/../../../vital_signs/partials/record_card.php'; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="section-heading">
            <div>
                <h3>Drug Chart / MAR</h3>
                <p>Medication administration records linked to Pharmacy prescriptions where available.</p>
            </div>
            <div class="form-actions">
                <?php if ($medicationAdministrationTablesReady && $canViewNursing): ?>
                    <a href="../nursing/drug_chart/history.php?visit=<?= (int)$visit['id'] ?>" class="btn-secondary">View Drug Chart</a>
                <?php endif; ?>
                <?php if ($medicationAdministrationTablesReady && $canCreateNursing && (!$isClosedEncounter || $permissionService->isAdministrator($currentUser))): ?>
                    <a href="../nursing/drug_chart/create.php?visit=<?= (int)$visit['id'] ?>" class="btn-primary">New Drug Chart Entry</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!$medicationAdministrationTablesReady): ?>
            <p>Drug Chart tables are not available yet. Apply Migration 046 to enable this section.</p>
        <?php elseif (!$canViewNursing): ?>
            <p class="text-muted">You do not have permission to view Drug Chart.</p>
        <?php elseif ($latestMedicationAdministrationRecord === null): ?>
            <p class="text-muted">No drug chart entries found for this encounter.</p>
            <?php if (!empty($drugChartPrescriptions)): ?>
                <p class="text-muted"><?= count($drugChartPrescriptions) ?> prescription(s) available to link when recording administration.</p>
            <?php endif; ?>
        <?php else: ?>
            <div class="summary-grid">
                <div class="summary-item"><span class="summary-label">Medication</span> <span class="summary-value"><?= e((string)$latestMedicationAdministrationRecord['medication_name']) ?></span></div>
                <div class="summary-item"><span class="summary-label">Dose</span> <span class="summary-value"><?= e((string)$latestMedicationAdministrationRecord['dose_given']) ?></span></div>
                <div class="summary-item"><span class="summary-label">Route</span> <span class="summary-value"><?= e((string)($latestMedicationAdministrationRecord['route'] ?? '-')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Status</span> <span class="summary-value"><?= e((string)$latestMedicationAdministrationRecord['administration_status']) ?></span></div>
                <div class="summary-item"><span class="summary-label">Time</span> <span class="summary-value"><?= e((string)$latestMedicationAdministrationRecord['scheduled_time']) ?></span></div>
                <div class="summary-item"><span class="summary-label">Administered By</span> <span class="summary-value"><?= e((string)($latestMedicationAdministrationRecord['administered_by_name'] ?? '-')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Total Entries</span> <span class="summary-value"><?= count($medicationAdministrationRecords) ?></span></div>
            </div>
            <div class="form-actions">
                <a href="../nursing/drug_chart/view.php?id=<?= (int)$latestMedicationAdministrationRecord['id'] ?>" class="btn-secondary">View Latest</a>
                <?php if ($canEditNursing && !$isClosedEncounter): ?>
                    <a href="../nursing/drug_chart/edit.php?id=<?= (int)$latestMedicationAdministrationRecord['id'] ?>" class="btn-secondary">Edit Latest</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="section-heading">
            <div>
                <h3>DM Sheet</h3>
                <p>Blood glucose monitoring, insulin given, meal status, symptoms, and notes.</p>
            </div>
            <div class="form-actions">
                <?php if ($diabetesMonitoringTablesReady && $canViewNursing): ?>
                    <a href="../nursing/dm_sheet/history.php?visit=<?= (int)$visit['id'] ?>" class="btn-secondary">View DM Sheet</a>
                <?php endif; ?>
                <?php if ($diabetesMonitoringTablesReady && $canCreateNursing && (!$isClosedEncounter || $permissionService->isAdministrator($currentUser))): ?>
                    <a href="../nursing/dm_sheet/create.php?visit=<?= (int)$visit['id'] ?>" class="btn-primary">New DM Entry</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!$diabetesMonitoringTablesReady): ?>
            <p>DM Sheet tables are not available yet. Apply Migration 048 to enable this section.</p>
        <?php elseif (!$canViewNursing): ?>
            <p class="text-muted">You do not have permission to view DM Sheet.</p>
        <?php elseif ($latestDiabetesMonitoringRecord === null): ?>
            <p class="text-muted">No DM Sheet entries found for this encounter.</p>
        <?php else: ?>
            <div class="summary-grid">
                <div class="summary-item"><span class="summary-label">Blood Glucose</span> <span class="summary-value"><?= e((string)$latestDiabetesMonitoringRecord['blood_glucose']) ?></span></div>
                <div class="summary-item"><span class="summary-label">Meal Status</span> <span class="summary-value"><?= e((string)$latestDiabetesMonitoringRecord['meal_status']) ?></span></div>
                <div class="summary-item"><span class="summary-label">Insulin Given</span> <span class="summary-value"><?= e((string)($latestDiabetesMonitoringRecord['insulin_given'] ?? '-')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Recorded At</span> <span class="summary-value"><?= e((string)$latestDiabetesMonitoringRecord['recorded_at']) ?></span></div>
                <div class="summary-item"><span class="summary-label">Recorded By</span> <span class="summary-value"><?= e((string)($latestDiabetesMonitoringRecord['recorded_by_name'] ?? '-')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Total Entries</span> <span class="summary-value"><?= count($diabetesMonitoringRecords) ?></span></div>
            </div>
            <div class="form-actions">
                <a href="../nursing/dm_sheet/view.php?id=<?= (int)$latestDiabetesMonitoringRecord['id'] ?>" class="btn-secondary">View Latest</a>
                <?php if ($canEditNursing && !$isClosedEncounter): ?>
                    <a href="../nursing/dm_sheet/edit.php?id=<?= (int)$latestDiabetesMonitoringRecord['id'] ?>" class="btn-secondary">Edit Latest</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="section-heading">
            <div>
                <h3>Dressing Book</h3>
                <p>Wound care, dressing done, supplies used, and follow-up date.</p>
            </div>
            <div class="form-actions">
                <?php if ($dressingTablesReady && $canViewNursing): ?>
                    <a href="../nursing/dressings/history.php?visit=<?= (int)$visit['id'] ?>" class="btn-secondary">View Dressing Book</a>
                <?php endif; ?>
                <?php if ($dressingTablesReady && $canCreateNursing && (!$isClosedEncounter || $permissionService->isAdministrator($currentUser))): ?>
                    <a href="../nursing/dressings/create.php?visit=<?= (int)$visit['id'] ?>" class="btn-primary">New Dressing Record</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!$dressingTablesReady): ?>
            <p>Dressing Book tables are not available yet. Apply Migration 045 to enable this section.</p>
        <?php elseif (!$canViewNursing): ?>
            <p class="text-muted">You do not have permission to view Dressing Book.</p>
        <?php elseif ($latestDressingRecord === null): ?>
            <p class="text-muted">No dressing records found for this encounter.</p>
        <?php else: ?>
            <div class="summary-grid">
                <div class="summary-item"><span class="summary-label">Latest Wound Site</span> <span class="summary-value"><?= e((string)$latestDressingRecord['wound_site']) ?></span></div>
                <div class="summary-item"><span class="summary-label">Recorded At</span> <span class="summary-value"><?= e((string)($latestDressingRecord['created_at'] ?? '-')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Recorded By</span> <span class="summary-value"><?= e((string)($latestDressingRecord['recorded_by_name'] ?? '-')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Next Dressing</span> <span class="summary-value"><?= e((string)($latestDressingRecord['next_dressing_date'] ?? '-')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Total Records</span> <span class="summary-value"><?= count($dressingRecords) ?></span></div>
                <div class="summary-item"><span class="summary-label">Summary</span> <span class="summary-value"><?= e((string)($latestDressingRecord['summary'] ?? '-')) ?></span></div>
            </div>
            <div class="form-actions">
                <a href="../nursing/dressings/view.php?id=<?= (int)$latestDressingRecord['id'] ?>" class="btn-secondary">View Latest</a>
                <?php if ($canEditNursing && !$isClosedEncounter): ?>
                    <a href="../nursing/dressings/edit.php?id=<?= (int)$latestDressingRecord['id'] ?>" class="btn-secondary">Edit Latest</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
