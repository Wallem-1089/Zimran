<?php

declare(strict_types=1);

$pageTitle = 'Print Patient Face Sheet';
$moduleStylesheet = '/modules/patients/assets/patients.css';

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../services/PatientService.php';
require_once __DIR__ . '/../../services/PermissionService.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: search.php');
    exit;
}

$patientService = new PatientService($pdo);
$permissionService = new PermissionService($pdo);
$patient = $patientService->getPatientById($id);

if (!$patient || ((int)($patient['is_deleted'] ?? 0) === 1 && !$permissionService->canViewDeletedPatient($currentUser))) {
    http_response_code(404);
    exit('Patient not found.');
}

require_once __DIR__ . '/../../layouts/header.php';
?>

<style>
    body {
        background: #ffffff;
    }

    .print-shell {
        max-width: 900px;
        margin: 0 auto;
        padding: 2rem;
    }

    .print-header {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        border-bottom: 2px solid #172033;
        padding-bottom: 1rem;
        margin-bottom: 1.5rem;
    }

    .print-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .8rem 1.5rem;
    }

    .print-field {
        border-bottom: 1px solid #d6e0ec;
        padding-bottom: .45rem;
    }

    .print-field small {
        display: block;
        color: #667085;
        font-weight: 700;
        text-transform: uppercase;
        font-size: .72rem;
        letter-spacing: .04em;
    }

    .print-section {
        margin-top: 1.5rem;
    }

    .print-actions {
        display: flex;
        justify-content: flex-end;
        gap: .75rem;
        margin-bottom: 1rem;
    }

    @media print {
        .print-actions,
        .no-print {
            display: none !important;
        }

        .print-shell {
            max-width: none;
            padding: 0;
        }
    }
</style>

<div class="print-shell">
    <div class="print-actions no-print">
        <a class="btn-secondary" href="view.php?id=<?= (int)$patient['id'] ?>">Back to Patient Profile</a>
        <button class="btn-primary" type="button" onclick="window.print()">Print Face Sheet</button>
    </div>

    <header class="print-header">
        <div>
            <h1>Patient Face Sheet</h1>
            <p><?= e((string)($patient['first_name'] ?? '')) ?> <?= e((string)($patient['last_name'] ?? '')) ?></p>
        </div>
        <div>
            <strong>Hospital No.</strong><br>
            <?= e((string)($patient['hospital_number'] ?? 'Pending')) ?>
        </div>
    </header>

    <section class="print-section">
        <h2>Personal Information</h2>
        <div class="print-grid">
            <div class="print-field"><small>First Name</small><?= e((string)($patient['first_name'] ?? '')) ?></div>
            <div class="print-field"><small>Middle Name</small><?= e((string)($patient['middle_name'] ?? '')) ?></div>
            <div class="print-field"><small>Last Name</small><?= e((string)($patient['last_name'] ?? '')) ?></div>
            <div class="print-field"><small>Gender</small><?= e((string)($patient['gender'] ?? '')) ?></div>
            <div class="print-field"><small>Date of Birth</small><?= e((string)($patient['date_of_birth'] ?? '')) ?></div>
            <div class="print-field"><small>Phone</small><?= e((string)($patient['phone'] ?? '')) ?></div>
            <div class="print-field"><small>Email</small><?= e((string)($patient['email'] ?? '')) ?></div>
            <div class="print-field"><small>Address</small><?= nl2br(e((string)($patient['address'] ?? ''))) ?></div>
        </div>
    </section>

    <section class="print-section">
        <h2>Medical Summary</h2>
        <div class="print-grid">
            <div class="print-field"><small>Blood Group</small><?= e((string)($patient['blood_group'] ?? '')) ?></div>
            <div class="print-field"><small>Genotype</small><?= e((string)($patient['genotype'] ?? '')) ?></div>
            <div class="print-field"><small>Occupation</small><?= e((string)($patient['occupation'] ?? '')) ?></div>
            <div class="print-field"><small>Place of Work</small><?= e((string)($patient['place_of_work'] ?? '')) ?></div>
        </div>
    </section>

    <section class="print-section">
        <h2>Next of Kin</h2>
        <div class="print-grid">
            <div class="print-field"><small>Name</small><?= e((string)($patient['next_of_kin'] ?? '')) ?></div>
            <div class="print-field"><small>Relationship</small><?= e((string)($patient['next_of_kin_relationship'] ?? '')) ?></div>
            <div class="print-field"><small>Phone</small><?= e((string)($patient['next_of_kin_phone'] ?? '')) ?></div>
            <div class="print-field"><small>Address</small><?= nl2br(e((string)($patient['next_of_kin_address'] ?? ''))) ?></div>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
