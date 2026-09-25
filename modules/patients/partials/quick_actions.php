<?php

declare(strict_types=1);

/** @var array $patient */
$isDeletedPatient = (int)($patient['is_deleted'] ?? 0) === 1;
?>

<div class="card patient-quick-actions-card">
    <h2>Quick Actions</h2>

    <div class="patient-actions-grid">
        <?php if (!empty($canViewMedicalRecord)): ?>
            <a href="../medical_records/chart.php?patient=<?= (int)$patient['id'] ?>" class="action-card action-primary">
                <div class="action-content">
                    <strong>View Patient Chart</strong>
                    <span>Open the longitudinal medical record</span>
                </div>
            </a>
        <?php endif; ?>

        <?php if (!$isDeletedPatient): ?>
            <a href="../visits/create.php?patient=<?= (int)$patient['id'] ?>" class="action-card action-primary">
                <span class="action-icon" aria-hidden="true">+</span>
                <div class="action-content">
                    <strong>New Encounter</strong>
                    <span>Register a new patient visit</span>
                </div>
            </a>
        <?php endif; ?>

        <?php if (!$isDeletedPatient): ?>
            <a href="edit.php?id=<?= (int)$patient['id'] ?>" class="action-card">
                <span class="action-icon" aria-hidden="true">ED</span>
                <div class="action-content">
                    <strong>Edit Patient</strong>
                    <span>Update patient information</span>
                </div>
            </a>
        <?php endif; ?>

        <a href="history.php?id=<?= (int)$patient['id'] ?>" class="action-card">
            <span class="action-icon" aria-hidden="true">H</span>
            <div class="action-content">
                <strong>View History</strong>
                <span>Registration and activity history</span>
            </div>
        </a>

        <a href="search.php" class="action-card">
            <span class="action-icon" aria-hidden="true">⌕</span>
            <div class="action-content">
                <strong>Search Patients</strong>
                <span>Find another patient</span>
            </div>
        </a>

        <?php if (!empty($canViewBilling)): ?>
            <a href="../billing/index.php?hospital_number=<?= urlencode((string)($patient['hospital_number'] ?? '')) ?>" class="action-card">
                <span class="action-icon" aria-hidden="true">₦</span>
                <div class="action-content">
                    <strong>Billing</strong>
                    <span>View patient bills</span>
                </div>
            </a>
        <?php endif; ?>

        <?php if (!empty($canViewLaboratory)): ?>
            <a href="../laboratory/history.php?patient=<?= (int)$patient['id'] ?>" class="action-card">
                <span class="action-icon" aria-hidden="true">LB</span>
                <div class="action-content">
                    <strong>Laboratory</strong>
                    <span>Laboratory requests and results</span>
                </div>
            </a>
        <?php endif; ?>
    </div>
</div>
