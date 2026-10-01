<?php

declare(strict_types=1);

$chartTabs = [
    'overview' => 'Overview',
    'demographics' => 'Demographics',
    'encounters' => 'Encounter History',
    'history' => 'Demographic History'
];

if (!empty($canViewIdentifiers)) {
    $chartTabs['identifiers'] = 'Identifiers';
}

if (!empty($canViewClinicalSafety)) {
    $chartTabs['safety'] = 'Clinical Safety';
}

if (!empty($canViewProblemList)) {
    $chartTabs['problems'] = 'Problem List';
}

if (!empty($canViewMedicalHistory)) {
    $chartTabs['medical_history'] = 'Medical History';
}

if (!empty($canViewNursing)) {
    $chartTabs['nursing'] = 'Nursing';
}

if (!empty($canViewMedicalDocuments)) {
    $chartTabs['documents'] = 'Medical Documents';
}

if (!empty($canViewClinicalNotes)) {
    $chartTabs['notes'] = 'Clinical Notes';
}

if (!empty($canViewVitalSigns)) {
    $chartTabs['vitals'] = 'Vital Signs';
}

if (!empty($canViewBloodCard)) {
    $chartTabs['blood_card'] = 'Blood Card';
}

if (!empty($canViewLaboratory)) {
    $chartTabs['laboratory'] = 'Laboratory';
}

if (!empty($canViewRadiology)) {
    $chartTabs['radiology'] = 'Radiology';
}

if (!empty($canViewEcg)) {
    $chartTabs['ecg'] = 'ECG';
}

if (!empty($canViewPop)) {
    $chartTabs['pop'] = 'Plaster';
}

if (!empty($canViewPhysiotherapy)) {
    $chartTabs['physiotherapy'] = 'Physiotherapy';
}

if (!empty($canViewTheatre)) {
    $chartTabs['theatre'] = 'Theatre';
}

if (!empty($canViewDressings)) {
    $chartTabs['dressings'] = 'Dressings';
}

if (!empty($canViewDrugChart)) {
    $chartTabs['drug_chart'] = 'Drug Chart';
}

if (!empty($canViewDmSheet)) {
    $chartTabs['dm_sheet'] = 'DM Sheet';
}

if (!empty($canViewPatientStockUsage)) {
    $chartTabs['stock_usage'] = 'Stock Used';
}

if (!empty($canViewPatientCommunications)) {
    $chartTabs['communications'] = 'Communications';
}

if ($canViewAudit) {
    $chartTabs['audit'] = 'Audit History';
}
?>

<nav class="card chart-navigation" aria-label="Patient Chart">

    <?php foreach ($chartTabs as $tab => $label): ?>

        <a href="chart.php?patient=<?= (int)$patient['id'] ?>&tab=<?= e($tab) ?><?= e($chartContextQuery ?? '') ?>"
            class="<?= $activeTab === $tab ? 'active' : '' ?>">
            <?= e($label) ?>
        </a>

    <?php endforeach; ?>

</nav>
