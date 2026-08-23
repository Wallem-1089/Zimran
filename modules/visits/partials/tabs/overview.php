<?php

declare(strict_types=1);

/**
 * Variables supplied by workspace.php
 *
 * @var array $patient
 * @var array $visit
 */

if (!isset($patient, $visit)) {
    return;
}

$consultation = $consultation ?? null;
$nursing = $nursing ?? null;
$laboratoryRequests = $laboratoryRequests ?? [];
$latestLaboratoryRequest = $latestLaboratoryRequest ?? ($laboratoryRequests[0] ?? null);
$latestLaboratoryResult = $latestLaboratoryResult ?? null;
$radiologyRequests = $radiologyRequests ?? [];
$latestRadiologyRequest = $latestRadiologyRequest ?? ($radiologyRequests[0] ?? null);
$latestRadiologyResult = $latestRadiologyResult ?? null;
$pharmacy = $pharmacy ?? [];
$latestPharmacyPrescription = $latestPharmacyPrescription ?? ($pharmacy[0] ?? null);
$billingSummary = $billingSummary ?? ['total_charges' => 0, 'amount_paid' => 0, 'balance_due' => 0, 'status' => 'Unbilled'];
$documents = $documents ?? [];

?>

<div
    id="tab-overview"
    class="workspace-tab active">

    <!-- ========================================================= -->
    <!-- Encounter Overview -->
    <!-- ========================================================= -->

    <div class="card">

        <h2>

            Encounter Overview

        </h2>

        <div class="summary-grid">

            <div class="summary-item">

                <span class="summary-label">

                    Patient

                </span>

                <span class="summary-value">

                    <?= e($patient['first_name']) ?>

                    <?= e($patient['last_name']) ?>

                </span>

            </div>

            <div class="summary-item">

                <span class="summary-label">

                    Hospital Number

                </span>

                <span class="summary-value">

                    <?= e($patient['hospital_number']) ?>

                </span>

            </div>

            <div class="summary-item">

                <span class="summary-label">

                    Encounter ID

                </span>

                <span class="summary-value">

                    #<?= (int)$visit['id'] ?>

                </span>

            </div>

            <div class="summary-item">

                <span class="summary-label">

                    Visit Date

                </span>

                <span class="summary-value">

                    <?= e($visit['created_at']) ?>

                </span>

            </div>

            <div class="summary-item">

                <span class="summary-label">

                    Status

                </span>

                <span class="summary-value">

                    <?= e((string)($visit['visit_status'] ?? $visit['status'] ?? 'Unknown')) ?>

                </span>

            </div>

            <div class="summary-item">

                <span class="summary-label">

                    Department

                </span>

                <span class="summary-value">

                    <?= e($visit['department_name'] ?? 'Reception') ?>

                </span>

            </div>

        </div>

    </div>

    <!-- ========================================================= -->
    <!-- Clinical Summary -->
    <!-- ========================================================= -->

    <div class="card">

        <h2>

            Clinical Summary

        </h2>

        <?php if (!empty($consultation)) : ?>
            <div class="summary-grid">
                <div class="summary-item">
                    <span class="summary-label">Status</span>
                    <span class="summary-value"><?= e((string)($consultation['status'] ?? 'Draft')) ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Presenting Complaint</span>
                    <span class="summary-value"><?= e((string)($consultation['presenting_complaint'] ?? '-')) ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Diagnosis</span>
                    <span class="summary-value"><?= e((string)($consultation['diagnosis'] ?? $consultation['diagnosis_summary'] ?? '-')) ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Updated</span>
                    <span class="summary-value"><?= e((string)($consultation['updated_at'] ?? $consultation['created_at'] ?? '-')) ?></span>
                </div>
            </div>
            <div class="form-actions">
                <a class="btn-secondary" href="../consultation/view.php?visit=<?= (int)$visit['id'] ?>">Open Consultation</a>
            </div>
        <?php else : ?>
            <div class="empty-state">
                No consultation has been recorded for this encounter.
            </div>
        <?php endif; ?>

    </div>

    <!-- ========================================================= -->
    <!-- Nursing -->
    <!-- ========================================================= -->

    <div class="card">

        <h2>

            Nursing

        </h2>

        <?php if (!empty($nursing)) : ?>
            <div class="summary-grid">
                <div class="summary-item">
                    <span class="summary-label">Status</span>
                    <span class="summary-value"><?= e((string)($nursing['status'] ?? 'Draft')) ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Nurse</span>
                    <span class="summary-value"><?= e((string)($nursing['nurse_name'] ?? 'Unknown')) ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Recorded At</span>
                    <span class="summary-value"><?= e((string)($nursing['created_at'] ?? '-')) ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Observation</span>
                    <span class="summary-value"><?= e((string)($nursing['nursing_observation'] ?? $nursing['general_condition'] ?? '-')) ?></span>
                </div>
            </div>
            <div class="form-actions">
                <a class="btn-secondary" href="../nursing/view.php?id=<?= (int)$nursing['id'] ?>">Open Nursing</a>
                <a class="btn-secondary" href="../nursing/history.php?visit=<?= (int)$visit['id'] ?>">View History</a>
            </div>
        <?php else : ?>
            <div class="empty-state">
                No nursing assessment available.
            </div>
        <?php endif; ?>

    </div>

    <!-- ========================================================= -->
    <!-- Laboratory -->
    <!-- ========================================================= -->

    <div class="card">

        <h2>

            Laboratory

        </h2>

        <?php if (!empty($latestLaboratoryRequest)) : ?>
            <div class="summary-grid">
                <div class="summary-item"><span class="summary-label">Latest Tests</span> <span class="summary-value"><?= e((string)($latestLaboratoryRequest['tests_requested'] ?? '-')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Status</span> <span class="summary-value"><?= e((string)($latestLaboratoryRequest['status'] ?? '-')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Result</span> <span class="summary-value"><?= trim((string)($latestLaboratoryResult['result'] ?? '')) !== '' ? 'Recorded' : 'Pending' ?></span></div>
                <div class="summary-item"><span class="summary-label">Total Requests</span> <span class="summary-value"><?= count($laboratoryRequests) ?></span></div>
            </div>
            <div class="form-actions">
                <a class="btn-secondary" href="../laboratory/view.php?id=<?= (int)$latestLaboratoryRequest['id'] ?>">Open Laboratory</a>
                <a class="btn-secondary" href="../laboratory/history.php?visit=<?= (int)$visit['id'] ?>">View History</a>
            </div>
        <?php else : ?>
            <div class="empty-state">
                No laboratory requests.
            </div>
        <?php endif; ?>

    </div>

    <!-- ========================================================= -->
    <!-- Radiology -->
    <!-- ========================================================= -->

    <div class="card">

        <h2>

            Radiology

        </h2>

        <?php if (!empty($latestRadiologyRequest)) : ?>
            <div class="summary-grid">
                <div class="summary-item"><span class="summary-label">Latest Study</span> <span class="summary-value"><?= e((string)($latestRadiologyRequest['study_requested'] ?? '-')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Status</span> <span class="summary-value"><?= e((string)($latestRadiologyRequest['status'] ?? '-')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Report</span> <span class="summary-value"><?= trim((string)($latestRadiologyResult['impression'] ?? '')) !== '' ? 'Recorded' : 'Pending' ?></span></div>
                <div class="summary-item"><span class="summary-label">Total Requests</span> <span class="summary-value"><?= count($radiologyRequests) ?></span></div>
            </div>
            <div class="form-actions">
                <a class="btn-secondary" href="../radiology/view.php?id=<?= (int)$latestRadiologyRequest['id'] ?>">Open Radiology</a>
                <a class="btn-secondary" href="../radiology/history.php?visit=<?= (int)$visit['id'] ?>">View History</a>
            </div>
        <?php else : ?>
            <div class="empty-state">
                No radiology investigations.
            </div>
        <?php endif; ?>

    </div>

    <!-- ========================================================= -->
    <!-- Pharmacy -->
    <!-- ========================================================= -->

    <div class="card">

        <h2>

            Pharmacy

        </h2>

        <?php if (!empty($latestPharmacyPrescription)) : ?>
            <div class="summary-grid">
                <div class="summary-item"><span class="summary-label">Latest Medication</span> <span class="summary-value"><?= e((string)($latestPharmacyPrescription['medication_name'] ?? '-')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Quantity</span> <span class="summary-value"><?= e((string)($latestPharmacyPrescription['quantity'] ?? '-')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Status</span> <span class="summary-value"><?= e((string)($latestPharmacyPrescription['status'] ?? '-')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Total Prescriptions</span> <span class="summary-value"><?= count($pharmacy) ?></span></div>
            </div>
            <div class="form-actions">
                <a class="btn-secondary" href="../pharmacy/view.php?id=<?= (int)$latestPharmacyPrescription['id'] ?>">Open Pharmacy</a>
                <a class="btn-secondary" href="../pharmacy/history.php?visit=<?= (int)$visit['id'] ?>">View History</a>
            </div>
        <?php else : ?>
            <div class="empty-state">
                No medications have been prescribed.
            </div>
        <?php endif; ?>

    </div>

    <!-- ========================================================= -->
    <!-- Billing -->
    <!-- ========================================================= -->

    <div class="card">

        <h2>

            Billing Summary

        </h2>

        <div class="summary-grid">

            <div class="summary-item">

                <span class="summary-label">

                    Outstanding Balance

                </span>

                <span class="summary-value">

                    ₦<?= number_format((float)($billingSummary['balance_due'] ?? 0), 2) ?>

                </span>

            </div>

            <div class="summary-item">

                <span class="summary-label">

                    Charges

                </span>

                <span class="summary-value">

                    ₦<?= number_format((float)($billingSummary['total_charges'] ?? 0), 2) ?>

                </span>

            </div>

            <div class="summary-item">

                <span class="summary-label">

                    Payments

                </span>

                <span class="summary-value">

                    ₦<?= number_format((float)($billingSummary['amount_paid'] ?? 0), 2) ?>

                </span>

            </div>

            <div class="summary-item">

                <span class="summary-label">

                    Status

                </span>

                <span class="summary-value">

                    <?= e((string)($billingSummary['status'] ?? 'Unbilled')) ?>

                </span>

            </div>

        </div>

        <div class="form-actions">
            <a class="btn-secondary" href="../billing/view.php?visit=<?= (int)$visit['id'] ?>">Open Billing</a>
        </div>

    </div>

    <!-- ========================================================= -->
    <!-- Documents -->
    <!-- ========================================================= -->

    <div class="card">

        <h2>

            Documents

        </h2>

        <?php if (!empty($documents)) : ?>
            <?php $latestDocument = $documents[0]; ?>
            <div class="summary-grid">
                <div class="summary-item"><span class="summary-label">Total Documents</span> <span class="summary-value"><?= count($documents) ?></span></div>
                <div class="summary-item"><span class="summary-label">Latest Title</span> <span class="summary-value"><?= e((string)($latestDocument['title'] ?? '-')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Type</span> <span class="summary-value"><?= e(ucwords(str_replace('_', ' ', (string)($latestDocument['document_type'] ?? '-')))) ?></span></div>
                <div class="summary-item"><span class="summary-label">Status</span> <span class="summary-value"><?= e((string)($latestDocument['document_status'] ?? '-')) ?></span></div>
            </div>
            <div class="form-actions">
                <a class="btn-secondary" href="../medical_records/chart.php?patient=<?= (int)$patient['id'] ?>&tab=documents&visit=<?= (int)$visit['id'] ?>">Open Documents</a>
            </div>
        <?php else : ?>
            <div class="empty-state">
                No documents uploaded.
            </div>
        <?php endif; ?>

    </div>

</div>
