<?php

declare(strict_types=1);

$demographicRows = [
    'Hospital Number' => $patient['hospital_number'],
    'First Name' => $patient['first_name'],
    'Middle Name' => $patient['middle_name'],
    'Last Name' => $patient['last_name'],
    'Gender' => $patient['gender'],
    'Date of Birth' => $patient['date_of_birth'],
    'Marital Status' => $patient['marital_status'],
    'Occupation' => $patient['occupation'],
    'Phone' => $patient['phone'],
    'Email' => $patient['email'],
    'Address' => $patient['address'],
    'State of Origin' => $patient['state_of_origin'],
    'Nationality' => $patient['nationality'],
    'Blood Group' => $patient['blood_group'],
    'Genotype' => $patient['genotype'],
    'Next of Kin' => $patient['next_of_kin'],
    'Next of Kin Relationship' => $patient['next_of_kin_relationship'],
    'Next of Kin Phone' => $patient['next_of_kin_phone']
];
?>

<div class="card">
    <div class="card-header">
        <div>
            <h2>Current Demographics</h2>
            <p>Current version: <?= (int)$patient['demographic_version'] ?></p>
        </div>
        <?php if ($canEditDemographics): ?>
            <a href="../patients/edit.php?id=<?= (int)$patient['id'] ?>"
                class="btn-primary">Edit Demographics</a>
        <?php endif; ?>
    </div>
    <div class="chart-detail-grid">
        <?php foreach ($demographicRows as $label => $value): ?>
            <div>
                <span><?= e($label) ?></span>
                <strong><?= nl2br(e((string)($value ?: '-'))) ?></strong>
            </div>
        <?php endforeach; ?>
    </div>
</div>
