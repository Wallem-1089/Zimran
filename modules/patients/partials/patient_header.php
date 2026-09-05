<?php

declare(strict_types=1);

$firstName = $patient['first_name'] ?? '';
$lastName = $patient['last_name'] ?? '';
$hospitalNumber = $patient['hospital_number'] ?? '';
$gender = $patient['gender'] ?? '';
$phone = $patient['phone'] ?? '';
$whatsappNumber = $patient['whatsapp_number'] ?? '';
$dateOfBirth = $patient['date_of_birth'] ?? '';

$age = '-';

if ($dateOfBirth !== '') {
    try {
        $dob = new DateTime($dateOfBirth);
        $today = new DateTime();
        $age = $dob->diff($today)->y . ' Years';
    } catch (Throwable $e) {
        $age = '-';
    }
}

?>

<div class="card patient-header">

    <div class="patient-header-left">

        <div class="patient-avatar">
            <?= strtoupper(substr($firstName, 0, 1)) ?>
            <?= strtoupper(substr($lastName, 0, 1)) ?>
        </div>

        <div class="patient-basic-info">

            <h2>
                <?= e($firstName) ?>
                <?= e($lastName) ?>
            </h2>

            <div class="patient-hospital-number">
                Hospital No.
                <strong>
                    <?= e($hospitalNumber) ?>
                </strong>
            </div>

            <div class="patient-meta">
                <span><?= e($gender ?: '-') ?></span>
                <span>•</span>
                <span><?= e($age) ?></span>
                <span>•</span>
                <span><?= e($phone ?: '-') ?></span>
            </div>

        </div>

    </div>

    <div class="patient-status">
        <span class="status-badge active">
            Active
        </span>
    </div>

</div>
