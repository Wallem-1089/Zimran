<?php

declare(strict_types=1);

/** @var array $patient */

$dateOfBirth = $patient['date_of_birth'] ?? '';

$age = '-';

if (!empty($dateOfBirth)) {

    try {

        $dob = new DateTime($dateOfBirth);

        $today = new DateTime();

        $age = $today->diff($dob)->y . ' years';

    } catch (Throwable $e) {

        $age = '-';

    }

}

?>

<div class="card">

    <h2>

        Patient Summary

    </h2>

    <div class="patient-summary">

        <div class="summary-card">

            <h3>

                Demographics

            </h3>

            <div class="summary-item">

                <strong>Hospital No.</strong>

                <span>

                    <?= e($patient['hospital_number']) ?>

                </span>

            </div>

            <div class="summary-item">

                <strong>Age</strong>

                <span>

                    <?= e($age) ?>

                </span>

            </div>

            <div class="summary-item">

                <strong>Gender</strong>

                <span>

                    <?= e($patient['gender']) ?>

                </span>

            </div>

            <div class="summary-item">

                <strong>Phone</strong>

                <span>

                    <?= e($patient['phone'] ?: '-') ?>

                </span>

            </div>

        </div>

        <div class="summary-card">

            <h3>

                Medical

            </h3>

            <div class="summary-item">

                <strong>Blood Group</strong>

                <span>

                    <?= e($patient['blood_group'] ?: '-') ?>

                </span>

            </div>

            <div class="summary-item">

                <strong>Genotype</strong>

                <span>

                    <?= e($patient['genotype'] ?: '-') ?>

                </span>

            </div>

            <div class="summary-item">

                <strong>Allergies</strong>

                <span>

                    <?= e($patient['allergies'] ?: '-') ?>

                </span>

            </div>

        </div>

        <div class="summary-card">

            <h3>

                Registration

            </h3>

            <div class="summary-item">

                <strong>Registered</strong>

                <span>

                    <?= e($patient['created_at'] ?? '-') ?>

                </span>

            </div>

            <div class="summary-item">

                <strong>Last Updated</strong>

                <span>

                    <?= e($patient['updated_at'] ?? '-') ?>

                </span>

            </div>

            <div class="summary-item">

                <strong>Status</strong>

                <span>

                    Active

                </span>

            </div>

        </div>

    </div>

</div>