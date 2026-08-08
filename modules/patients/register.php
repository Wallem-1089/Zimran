<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Bootstrap
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../services/PatientService.php';

/*
|--------------------------------------------------------------------------
| Page Configuration
|--------------------------------------------------------------------------
*/

$pageTitle = 'Register Patient';

$moduleStylesheet =
    '/modules/patients/assets/patients.css';

$moduleScript =
    '/modules/patients/assets/patients.js';

/*
|--------------------------------------------------------------------------
| Restore Previous Form Data
|--------------------------------------------------------------------------
|
| If validation fails and the user returns from review.php
| or save.php, restore the submitted values.
|
*/

$patient =

    $_SESSION['old_patient'] ?? [];

unset($_SESSION['old_patient']);

$errors = $_SESSION['validation_errors'] ?? [];

unset($_SESSION['validation_errors']);

$errorMessage = $_SESSION['error_message'] ?? null;

unset($_SESSION['error_message']);

$successMessage = $_SESSION['success_message'] ?? null;

unset($_SESSION['success_message']);

/*
|--------------------------------------------------------------------------
| Layout
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../../layouts/header.php';
require_once __DIR__ . '/../../layouts/sidebar.php';

?>

<div class="main-container">

<?php require_once __DIR__ . '/../../layouts/navbar.php'; ?>

<main class="content">

    <div class="page-header">

        <div>

            <h1>Patient Registration</h1>

            <p>

                Register a new patient into the Hospital Management System.

            </p>

        </div>

    </div>

    <?php if ($errorMessage): ?>

        <div class="alert alert-danger">

            <?= e($errorMessage) ?>

        </div>

    <?php endif; ?>

    <?php if ($successMessage): ?>

        <div class="alert alert-success">

            <?= e($successMessage) ?>

        </div>

    <?php endif; ?>

    <?php if (!empty($errors)): ?>

        <div class="alert alert-danger">

            <ul>

                <?php foreach ($errors as $error): ?>

                    <li><?= e($error) ?></li>

                <?php endforeach; ?>

            </ul>

        </div>

    <?php endif; ?>

    <form
        method="POST"
        action="review.php"
        class="card">

        <?= csrfField() ?>

        <!-- Tell review.php this is a NEW registration -->

        <input
            type="hidden"
            name="action"
            value="create">

        <?php

        /*
        |--------------------------------------------------------------------------
        | Patient Registration Form
        |--------------------------------------------------------------------------
        |
        | Shared by:
        |   • register.php
        |   • edit.php
        |
        | The partial uses the $patient array to automatically
        | populate form values.
        |
        */

        require __DIR__ . '/partials/patient_form.php';

        ?>

        <div class="form-actions">

            <button
                type="submit"
                class="btn-primary">

                Review Patient Information

            </button>

            <a
                href="../../dashboard/index.php"
                class="btn-secondary">

                Cancel

            </a>

        </div>

    </form>

</main>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
