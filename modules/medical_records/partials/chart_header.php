<?php

declare(strict_types=1);

$fullName = trim(implode(' ', array_filter([
    $patient['first_name'] ?? '',
    $patient['middle_name'] ?? '',
    $patient['last_name'] ?? ''
])));
?>

<div class="card patient-chart-header">

    <div>

        <span class="chart-label">Longitudinal Patient Chart</span>

        <h1><?= e($fullName) ?></h1>

        <p>
            Hospital No. <strong><?= e($patient['hospital_number']) ?></strong>
            &middot; Version <?= (int)$patient['demographic_version'] ?>
        </p>

    </div>

    <div class="chart-header-actions">

        <a href="../patients/view.php?id=<?= (int)$patient['id'] ?>"
            class="btn-secondary">Patient Profile</a>

        <?php if ($canEditDemographics): ?>

            <a href="../patients/edit.php?id=<?= (int)$patient['id'] ?>"
                class="btn-primary">Edit Demographics</a>

        <?php endif; ?>

    </div>

</div>
