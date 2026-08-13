<?php

declare(strict_types=1);

if (!isset($visit, $patient)) {
    return;
}

$latest = $latestVitalSigns ?? null;
$isClosedEncounter = in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true);
?>

<section id="tab-vitals" class="workspace-tab">
    <div class="card">
        <div class="card-header">
            <div>
                <h2>Vital Signs</h2>
                <p>Encounter measurements and observations.</p>
            </div>
            <div>
                <?php if (!$vitalSignsTablesReady): ?>
                    <span class="badge badge-warning">Migration required</span>
                <?php elseif (!$canViewVitalSigns): ?>
                    <span class="badge badge-warning">No vital signs permission</span>
                <?php elseif ($latest === null && $canCreateVitalSigns && !$isClosedEncounter): ?>
                    <a href="../vital_signs/create.php?visit=<?= (int)$visit['id'] ?>" class="btn-primary">Record Vital Signs</a>
                <?php elseif ($latest !== null): ?>
                    <a href="../vital_signs/history.php?visit=<?= (int)$visit['id'] ?>" class="btn-secondary">View History</a>
                    <?php if ($canCreateVitalSigns && !$isClosedEncounter): ?>
                        <a href="../vital_signs/create.php?visit=<?= (int)$visit['id'] ?>" class="btn-primary">Record New</a>
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
                <span class="summary-value"><?= e($latest['created_at'] ?? 'Not recorded') ?></span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Recorded By</span>
                <span class="summary-value"><?= e($latest['recorded_by_name'] ?? 'Unknown') ?></span>
            </div>
        </div>
    </div>

    <?php if (!$vitalSignsTablesReady): ?>
        <div class="card">
            <p>Vital Signs tables are not available yet. Apply Migration 023 to enable this section.</p>
        </div>
    <?php elseif (!$canViewVitalSigns): ?>
        <div class="card alert-warning">
            You do not have permission to view vital signs.
        </div>
    <?php elseif ($latest === null): ?>
        <div class="card">
            <p class="text-muted">No vital signs recorded.</p>
            <?php if ($canCreateVitalSigns && !$isClosedEncounter): ?>
                <p><a href="../vital_signs/create.php?visit=<?= (int)$visit['id'] ?>" class="btn-primary">Record Vital Signs</a></p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="card">
            <h3>Latest Vital Signs</h3>
            <div class="summary-grid">
                <div class="summary-item"><span class="summary-label">Temperature</span> <span class="summary-value"><?= e((string)($latest['temperature'] ?? '-')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Pulse</span> <span class="summary-value"><?= e((string)($latest['pulse'] ?? '-')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Respiratory Rate</span> <span class="summary-value"><?= e((string)($latest['respiratory_rate'] ?? '-')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Blood Pressure</span> <span class="summary-value"><?= e((string)($latest['blood_pressure'] ?? '-')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Oxygen Saturation</span> <span class="summary-value"><?= e((string)($latest['oxygen_saturation'] ?? '-')) ?></span></div>
                <div class="summary-item"><span class="summary-label">BMI</span> <span class="summary-value"><?= e((string)($latest['bmi'] ?? '-')) ?></span></div>
            </div>
        </div>

        <div class="card">
            <div class="form-actions">
                <?php if ($canCreateVitalSigns && !$isClosedEncounter): ?>
                    <a href="../vital_signs/create.php?visit=<?= (int)$visit['id'] ?>" class="btn-primary">Record New</a>
                <?php endif; ?>
                <a href="../vital_signs/history.php?visit=<?= (int)$visit['id'] ?>" class="btn-secondary">View History</a>
                <?php if ($canEditVitalSigns && !$isClosedEncounter): ?>
                    <a href="../vital_signs/edit.php?id=<?= (int)$latest['id'] ?>" class="btn-secondary">Edit Latest</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</section>
