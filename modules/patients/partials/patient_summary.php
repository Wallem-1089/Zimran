<?php

declare(strict_types=1);

if (!isset($patient) || !is_array($patient)) {

    return;

}

if (!function_exists('patientField')) {

    function patientField(string $label, string $value): void
    {
        ?>

        <div class="review-item">

            <div class="review-label">

                <?= e($label) ?>

            </div>

            <div class="review-value">

                <?= nl2br(e($value !== '' ? $value : '-')) ?>

            </div>

        </div>

        <?php
    }

}

?>

<div class="card patient-summary">

    <h2>

        Patient Summary

    </h2>

    <div class="review-section">

        <h3>Patient Identification</h3>

        <?php

        patientField(
            'Hospital Number',
            $patient['hospital_number'] ?? ''
        );

        patientField(
            'First Name',
            $patient['first_name'] ?? ''
        );

        patientField(
            'Last Name',
            $patient['last_name'] ?? ''
        );

        patientField(
            'Gender',
            $patient['gender'] ?? ''
        );

        patientField(
            'Date of Birth',
            $patient['date_of_birth'] ?? ''
        );

        ?>

    </div>

    <hr>

    <div class="review-section">

        <h3>Contact Information</h3>

        <?php

        patientField(
            'Phone Number',
            $patient['phone'] ?? ''
        );

        patientField(
            'Email Address',
            $patient['email'] ?? ''
        );

        patientField(
            'Residential Address',
            $patient['address'] ?? ''
        );

        ?>

    </div>

    <hr>

    <div class="review-section">

        <h3>Medical Information</h3>

        <?php

        patientField(
            'Blood Group',
            $patient['blood_group'] ?? ''
        );

        patientField(
            'Genotype',
            $patient['genotype'] ?? ''
        );

        ?>

    </div>

    <hr>

    <div class="review-section">

        <h3>Next of Kin</h3>

        <?php

        patientField(
            'Full Name',
            $patient['next_of_kin'] ?? ''
        );

        patientField(
            'Phone Number',
            $patient['next_of_kin_phone'] ?? ''
        );

        ?>

    </div>

</div>