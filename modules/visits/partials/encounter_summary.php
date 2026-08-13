<?php

declare(strict_types=1);

if (!isset($visit)) {
    return;
}

$visitDate = !empty($visit['visit_date'])
    ? date('d M Y h:i A', strtotime((string)$visit['visit_date']))
    : '-';

$updatedAt = !empty($visit['updated_at'])
    ? date('d M Y h:i A', strtotime((string)$visit['updated_at']))
    : '-';

$completedAt = !empty($visit['completed_at'])
    ? date('d M Y h:i A', strtotime((string)$visit['completed_at']))
    : '-';
?>

<div class="encounter-summary-split">
    <div class="card encounter-summary-card">
        <h2>Visit Information</h2>

        <div class="summary-grid">
            <div class="summary-item">
                <span class="summary-label">Visit Number</span>
                <span class="summary-value"><?= e((string)($visit['visit_number'] ?? '-')) ?></span>
            </div>

            <div class="summary-item">
                <span class="summary-label">Visit Type</span>
                <span class="summary-value"><?= e((string)($visit['visit_type'] ?? '-')) ?></span>
            </div>

            <div class="summary-item">
                <span class="summary-label">Current Status</span>
                <span class="summary-value"><?= e((string)($visit['visit_status'] ?? $visit['status'] ?? '-')) ?></span>
            </div>

            <div class="summary-item">
                <span class="summary-label">Current Department</span>
                <span class="summary-value"><?= e((string)($visit['department_name'] ?? 'Not Assigned')) ?></span>
            </div>
        </div>
    </div>

    <div class="card encounter-summary-card">
        <h2>Clinical Assignment</h2>

        <div class="summary-grid">
            <div class="summary-item">
                <span class="summary-label">Department</span>
                <span class="summary-value"><?= e((string)($visit['department_name'] ?? 'Not Assigned')) ?></span>
            </div>

            <div class="summary-item">
                <span class="summary-label">Attending Doctor</span>
                <span class="summary-value"><?= e((string)($visit['doctor_name'] ?? 'Not Assigned')) ?></span>
            </div>
        </div>
    </div>

    <div class="card encounter-summary-card">
        <h2>Registration</h2>

        <div class="summary-grid">
            <div class="summary-item">
                <span class="summary-label">Visit Date</span>
                <span class="summary-value"><?= e($visitDate) ?></span>
            </div>

            <div class="summary-item">
                <span class="summary-label">Registered By</span>
                <span class="summary-value"><?= e((string)($visit['registered_by_name'] ?? '-')) ?></span>
            </div>

            <div class="summary-item">
                <span class="summary-label">Last Updated</span>
                <span class="summary-value"><?= e($updatedAt) ?></span>
            </div>

            <div class="summary-item">
                <span class="summary-label">Encounter ID</span>
                <span class="summary-value">#<?= e((string)($visit['id'] ?? '-')) ?></span>
            </div>
        </div>
    </div>

    <?php if ((string)($visit['visit_status'] ?? '') === 'Completed'): ?>
        <div class="card encounter-summary-card">
            <h2>Discharge Details</h2>

            <div class="summary-grid">
                <div class="summary-item">
                    <span class="summary-label">Completed At</span>
                    <span class="summary-value"><?= e($completedAt) ?></span>
                </div>

                <div class="summary-item">
                    <span class="summary-label">Discharge Diagnosis</span>
                    <span class="summary-value"><?= e((string)($visit['discharge_diagnosis'] ?? '-')) ?></span>
                </div>

                <div class="summary-item">
                    <span class="summary-label">Discharge Notes</span>
                    <span class="summary-value"><?= e((string)($visit['discharge_notes'] ?? '-')) ?></span>
                </div>

                <div class="summary-item">
                    <span class="summary-label">Follow-up</span>
                    <span class="summary-value"><?= e((string)($visit['follow_up_instructions'] ?? '-')) ?></span>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
