<?php

declare(strict_types=1);
?>
<div class="card">
    <div class="page-header">
        <div>
            <h2>Clinical Safety</h2>
            <p>Longitudinal allergies and alerts shared across every encounter.</p>
        </div>
        <a class="btn-primary" href="safety/index.php?patient=<?= (int)$patient['id'] ?><?= e($chartContextQuery ?? '') ?>">
            Manage Clinical Safety
        </a>
    </div>
</div>

<?php require __DIR__ . '/clinical_safety_banner.php'; ?>

<div class="chart-detail-grid">
    <section class="card">
        <h3>Allergies</h3>
        <?php if ($safetyAllergies === []): ?>
            <p>No structured allergy records.</p>
        <?php endif; ?>
        <?php foreach ($safetyAllergies as $allergy): ?>
            <p>
                <strong><?= e($allergy['substance']) ?></strong>
                — <?= e($allergy['severity']) ?>,
                <?= e($allergy['clinical_status']) ?>,
                <?= e($allergy['verification_status']) ?>
            </p>
        <?php endforeach; ?>
    </section>
    <section class="card">
        <h3>Clinical Alerts</h3>
        <?php if ($safetyAlerts === []): ?>
            <p>No clinical alert records.</p>
        <?php endif; ?>
        <?php foreach ($safetyAlerts as $alert): ?>
            <p>
                <strong><?= e($alert['title']) ?></strong>
                — <?= e($alert['priority']) ?>,
                <?= e((string)($alert['effective_status'] ?? (!empty($alert['is_active']) ? 'Active' : 'Closed'))) ?>
            </p>
        <?php endforeach; ?>
    </section>
</div>
