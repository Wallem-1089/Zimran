<?php

declare(strict_types=1);

$pageTitle = 'Patient Profile';
$moduleStylesheet = '../../modules/patients/assets/patients.css';

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

require_once __DIR__ . '/../../services/PatientService.php';

$id = filter_input(

    INPUT_GET,

    'id',

    FILTER_VALIDATE_INT

);

if (!$id) {

    header('Location: search.php');

    exit;

}

$patientService = new PatientService($pdo);

$patient = $patientService->getPatientById($id);

if (!$patient) {

    http_response_code(404);

    exit('Patient not found.');

}

require_once __DIR__ . '/../../layouts/header.php';
require_once __DIR__ . '/../../layouts/sidebar.php';

?>

<div class="main-container">

<?php require_once __DIR__ . '/../../layouts/navbar.php'; ?>

<main class="content">

<div class="page-header">

    <div>

        <h1>

            Patient Profile

        </h1>

        <p>

            Patient information and registration details.

        </p>

    </div>

</div>

<?php if (isset($_SESSION['success_message'])) : ?>

<div class="alert-success">

    <?= e($_SESSION['success_message']) ?>

</div>

<?php unset($_SESSION['success_message']); ?>

<?php endif; ?>

<div class="card">

    <h2>

        <?= e($patient['first_name']) ?>

        <?= e($patient['last_name']) ?>

    </h2>

    <p>

        <strong>Hospital Number:</strong>

        <?= e($patient['hospital_number']) ?>

    </p>

</div>

<div class="card">

    <h2>Personal Information</h2>

    <div class="review-item">

        <div class="review-label">First Name</div>

        <div class="review-value">

            <?= e($patient['first_name']) ?>

        </div>

    </div>

    <div class="review-item">

        <div class="review-label">Last Name</div>

        <div class="review-value">

            <?= e($patient['last_name']) ?>

        </div>

    </div>

    <div class="review-item">

        <div class="review-label">Gender</div>

        <div class="review-value">

            <?= e($patient['gender']) ?>

        </div>

    </div>

    <div class="review-item">

        <div class="review-label">Date of Birth</div>

        <div class="review-value">

            <?= e($patient['date_of_birth']) ?>

        </div>

    </div>

</div>

<div class="card">

    <h2>Contact Information</h2>

    <div class="review-item">

        <div class="review-label">Phone Number</div>

        <div class="review-value">

            <?= e($patient['phone']) ?>

        </div>

    </div>

    <div class="review-item">

        <div class="review-label">Email Address</div>

        <div class="review-value">

            <?= e($patient['email']) ?>

        </div>

    </div>

    <div class="review-item">

        <div class="review-label">Address</div>

        <div class="review-value">

            <?= nl2br(e($patient['address'])) ?>

        </div>

    </div>

</div>

<div class="card">

    <h2>Medical Information</h2>

    <div class="review-item">

        <div class="review-label">Blood Group</div>

        <div class="review-value">

            <?= e($patient['blood_group']) ?>

        </div>

    </div>

    <div class="review-item">

        <div class="review-label">Genotype</div>

        <div class="review-value">

            <?= e($patient['genotype']) ?>

        </div>

    </div>

</div>

<div class="card">

    <h2>Next of Kin</h2>

    <div class="review-item">

        <div class="review-label">Full Name</div>

        <div class="review-value">

            <?= e($patient['next_of_kin']) ?>

        </div>

    </div>

    <div class="review-item">

        <div class="review-label">Phone Number</div>

        <div class="review-value">

            <?= e($patient['next_of_kin_phone']) ?>

        </div>

    </div>

</div>

<div class="form-actions">

    <a
        href="edit.php?id=<?= $patient['id'] ?>"
        class="btn-primary">

        Edit Patient

    </a>

    <a
        href="../visits/create.php?patient=<?= $patient['id'] ?>"
        class="btn-primary">

        Create Encounter

    </a>

    <a
        href="search.php"
        class="btn-secondary">

        Search Patients

    </a>

</div>

</main>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>