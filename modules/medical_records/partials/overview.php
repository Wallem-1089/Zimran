<?php

declare(strict_types=1);
?>

<div class="chart-summary-grid">
    <div class="card summary-card">
        <span>Total Encounters</span>
        <strong><?= (int)($summary['encounter_count'] ?? 0) ?></strong>
    </div>
    <div class="card summary-card">
        <span>Active Encounters</span>
        <strong><?= (int)($summary['active_encounter_count'] ?? 0) ?></strong>
    </div>
    <div class="card summary-card">
        <span>Demographic Amendments</span>
        <strong><?= (int)($summary['demographic_change_count'] ?? 0) ?></strong>
    </div>
    <div class="card summary-card">
        <span>Chart Version</span>
        <strong><?= (int)$patient['demographic_version'] ?></strong>
    </div>
</div>

<div class="card">
    <h2>Patient Summary</h2>
    <div class="chart-detail-grid">
        <div><span>Gender</span><strong><?= e($patient['gender']) ?></strong></div>
        <div><span>Date of Birth</span><strong><?= e($patient['date_of_birth']) ?></strong></div>
        <div><span>Phone</span><strong><?= e($patient['phone'] ?: '-') ?></strong></div>
        <div><span>Email</span><strong><?= e($patient['email'] ?: '-') ?></strong></div>
    </div>
</div>

<div class="card chart-foundation-notice">
    <h2>Clinical Chart Sections</h2>
    <p>
        Patient identifiers, allergies and alerts, problems, medical history,
        longitudinal clinical information is managed in the dedicated chart
        sections. Consultation and module-specific documentation remain
        <strong>not yet implemented</strong>.
    </p>
</div>
