<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Bootstrap
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

require_once __DIR__ . '/../../services/PatientService.php';
require_once __DIR__ . '/../../services/PermissionService.php';

/*
|--------------------------------------------------------------------------
| Page Configuration
|--------------------------------------------------------------------------
*/

$pageTitle = 'Edit Patient';

$moduleStylesheet =
    '/modules/patients/assets/patients.css';

$moduleScript =
    '/modules/patients/assets/patients.js';

/*
|--------------------------------------------------------------------------
| Services
|--------------------------------------------------------------------------
*/

$patientService = new PatientService($pdo);
$permissionService = new PermissionService($pdo);

/*
|--------------------------------------------------------------------------
| Restore Previous Form Data
|--------------------------------------------------------------------------
|
| When returning from review.php after clicking
| "Back & Edit", reuse the submitted values.
|
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    requireCsrfToken();

    $patient = $_POST;

    $id = isset($patient['id'])
        ? (int)$patient['id']
        : 0;

} else {

    $id = filter_input(
        INPUT_GET,
        'id',
        FILTER_VALIDATE_INT
    );

    if (!$id) {

        $_SESSION['error_message'] =
            'Invalid patient selected.';

        header('Location: search.php');

        exit;

    }

    $patient = $patientService->getPatientById($id);

    if (!$patient) {

        $_SESSION['error_message'] =
            'Patient not found.';

        header('Location: search.php');

        exit;

    }

}

if ((int)($patient['is_deleted'] ?? 0) === 1) {
    http_response_code(410);
    exit('This patient record has been deleted/voided and cannot be edited.');
}

if (!$patient
    || !$permissionService->canEditPatientDemographics(
        (int)$id,
        $currentUser
    )
) {
    if ($patient) {
        $permissionService->logPatientDenied(
            isset($currentUser['id']) ? (int)$currentUser['id'] : null,
            (int)$id,
            'PATIENT_CHART_ACCESS_DENIED',
            'User attempted to edit patient demographics without permission.'
        );
    }

    http_response_code(403);
    exit('You do not have permission to edit this patient record.');
}

/*
|--------------------------------------------------------------------------
| Flash Messages
|--------------------------------------------------------------------------
*/

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

            <h1>Edit Patient</h1>

            <p>

                Update the patient's information.

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

        <input
            type="hidden"
            name="id"
            value="<?= (int)$patient['id'] ?>">

        <input
            type="hidden"
            name="action"
            value="update">

        <input
            type="hidden"
            name="demographic_version"
            value="<?= (int)($patient['demographic_version'] ?? 1) ?>">

        <?php

        /*
        |--------------------------------------------------------------------------
        | Shared Patient Form
        |--------------------------------------------------------------------------
        |
        | Shared between:
        |
        | • register.php
        | • edit.php
        |
        | Automatically fills fields using $patient.
        |
        */

        require __DIR__ . '/partials/patient_form.php';

        ?>

        <div class="form-section">

            <h2>Reason for Change</h2>

            <div class="form-group">

                <label for="amendment_reason">
                    Amendment Reason <span class="required">*</span>
                </label>

                <textarea
                    id="amendment_reason"
                    name="amendment_reason"
                    rows="3"
                    required><?= field('amendment_reason', $patient) ?></textarea>

                <small>
                    This reason is retained in the permanent demographic history.
                </small>

            </div>

        </div>

        <div class="form-actions">

            <button
                type="submit"
                class="btn-primary">

                Review Changes

            </button>

            <a
                href="view.php?id=<?= (int)$patient['id'] ?>"
                class="btn-secondary">

                Cancel

            </a>

        </div>

    </form>

</main>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
