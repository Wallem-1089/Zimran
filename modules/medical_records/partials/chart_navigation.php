<?php

declare(strict_types=1);

$chartTabs = [
    'overview' => 'Overview',
    'demographics' => 'Demographics',
    'identifiers' => 'Identifiers',
    'safety' => 'Clinical Safety',
    'problems' => 'Problem List',
    'medical_history' => 'Medical History',
    'nursing' => 'Nursing',
    'documents' => 'Medical Documents',
    'notes' => 'Clinical Notes',
    'encounters' => 'Encounter History',
    'history' => 'Demographic History'
];

if (!empty($canViewVitalSigns)) {
    $chartTabs['vitals'] = 'Vital Signs';
}

if (!empty($canViewLaboratory)) {
    $chartTabs['laboratory'] = 'Laboratory';
}

if (!empty($canViewRadiology)) {
    $chartTabs['radiology'] = 'Radiology';
}

if (!empty($canViewPhysiotherapy)) {
    $chartTabs['physiotherapy'] = 'Physiotherapy';
}

if (!empty($canViewTheatre)) {
    $chartTabs['theatre'] = 'Theatre';
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
