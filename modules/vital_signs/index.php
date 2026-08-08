<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$visitId = filter_input(INPUT_GET, 'visit', FILTER_VALIDATE_INT) ?: 0;
$patientId = filter_input(INPUT_GET, 'patient', FILTER_VALIDATE_INT) ?: 0;

if ($visitId > 0) {
    header('Location: history.php?visit=' . $visitId);
    exit;
}

if ($patientId > 0) {
    header('Location: history.php?patient=' . $patientId);
    exit;
}

$pageTitle = 'Vital Signs';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header"><div><h1>Vital Signs</h1><p>Open a patient chart or encounter to record vital signs.</p></div></div>
    <div class="card">
        <p>Select an active encounter from patient search or the Encounter Workspace to manage vital signs.</p>
    </div>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
