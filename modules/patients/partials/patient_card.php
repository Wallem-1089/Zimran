<?php

declare(strict_types=1);

if (!isset($patient) || !is_array($patient)) {
    return;
}

$fullName = trim(
    ($patient['first_name'] ?? '') . ' ' .
    ($patient['last_name'] ?? '')
);

$age = '-';

if (!empty($patient['date_of_birth'])) {

    try {

        $dob = new DateTime($patient['date_of_birth']);

        $age = (string)$dob->diff(new DateTime())->y . ' years';

    } catch (Exception $e) {

        $age = '-';

    }

}
?>

<div class="patient-card">

    <div class="patient-card-header">

        <div>

            <h2>

                <?= e($fullName) ?>

            </h2>

            <span class="hospital-number">

                <?= e($patient['hospital_number'] ?? '-') ?>

            </span>

        </div>

        <div class="patient-status">

            <span class="status-badge">

                Active

            </span>

        </div>

    </div>

    <div class="patient-card-body">

        <div class="patient-grid">

            <div class="patient-item">

                <strong>Gender</strong>

                <span>

                    <?= e($patient['gender'] ?? '-') ?>

                </span>

            </div>

            <div class="patient-item">

                <strong>Age</strong>

                <span>

                    <?= e($age) ?>

                </span>

            </div>

            <div class="patient-item">

                <strong>Blood Group</strong>

                <span>

                    <?= e($patient['blood_group'] ?? '-') ?>

                </span>

            </div>

            <div class="patient-item">

                <strong>Genotype</strong>

                <span>

                    <?= e($patient['genotype'] ?? '-') ?>

                </span>

            </div>

            <div class="patient-item">

                <strong>Phone</strong>

                <span>

                    <?= e($patient['phone'] ?? '-') ?>

                </span>

            </div>

            <div class="patient-item">

                <strong>Email</strong>

                <span>

                    <?= e($patient['email'] ?? '-') ?>

                </span>

            </div>

        </div>

    </div>

    <div class="patient-card-footer">

        <a
            href="view.php?id=<?= (int)($patient['id'] ?? 0) ?>"
            class="btn-primary">

            View Profile

        </a>

        <a
            href="../visits/create.php?patient=<?= (int)($patient['id'] ?? 0) ?>"
            class="btn-secondary">

            Create Encounter

        </a>

    </div>

</div>