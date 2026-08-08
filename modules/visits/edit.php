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

require_once __DIR__ . '/../../services/VisitService.php';
require_once __DIR__ . '/../../services/EncounterStateService.php';

/*
|--------------------------------------------------------------------------
| Page Configuration
|--------------------------------------------------------------------------
*/

$pageTitle = 'Edit Encounter';

$moduleStylesheet = '/modules/visits/assets/visits.css';
$moduleScript     = '/modules/visits/assets/visits.js';

/*
|--------------------------------------------------------------------------
| Visit ID
|--------------------------------------------------------------------------
*/

$visitId = filter_input(

    INPUT_GET,

    'id',

    FILTER_VALIDATE_INT

);

if (!$visitId) {

    header('Location: index.php');

    exit;

}

/*
|--------------------------------------------------------------------------
| Services
|--------------------------------------------------------------------------
*/

$visitService = new VisitService($pdo);

$visit = $visitService->getVisitById($visitId);

if (!$visit) {

    http_response_code(404);

    exit('Encounter not found.');

}

$stateService = new EncounterStateService();

$stateValidation = $stateService->validateEditableEncounter($visit);

if (!$stateValidation['success']) {

    $_SESSION['error_message'] = implode(
        '<br>',
        $stateValidation['errors']
    );

    header('Location: workspace.php?id=' . $visitId);

    exit;

}

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

        <h1>Edit Encounter</h1>

        <p>

            Update administrative encounter information.

        </p>

    </div>

</div>

<form
    method="POST"
    action="update.php"
    class="card">

<?= csrfField() ?>

<input
    type="hidden"
    name="visit_id"
    value="<?= (int)$visit['visit_id'] ?>">

<h2>

    Encounter Information

</h2>

<div class="form-grid">

    <div class="form-group">

        <label>

            Visit Number

        </label>

        <input
            type="text"
            value="<?= e($visit['visit_number']) ?>"
            readonly>

    </div>

    <div class="form-group">

        <label>

            Patient

        </label>

        <input
            type="text"
            value="<?= e($visit['patient_name']) ?>"
            readonly>

    </div>

    <div class="form-group">

        <label>

            Hospital Number

        </label>

        <input
            type="text"
            value="<?= e($visit['patient_hospital_number']) ?>"
            readonly>

    </div>

    <div class="form-group">

        <label>

            Visit Date

        </label>

        <input
            type="text"
            value="<?= e(date(
                'd M Y h:i A',
                strtotime($visit['visit_date'])
            )) ?>"
            readonly>

    </div>

    <div class="form-group">

        <label>

            Visit Type

        </label>

        <select
            name="visit_type"
            required>

            <?php

            $types = [

                'Outpatient',

                'Inpatient',

                'Emergency',

                'Referral'

            ];

            foreach ($types as $type):

            ?>

            <option
                value="<?= e($type) ?>"
                <?= $visit['visit_type'] === $type ? 'selected' : '' ?>>

                <?= e($type) ?>

            </option>

            <?php endforeach; ?>

        </select>

    </div>

    <div class="form-group">

        <label>

            Department

        </label>

        <input
            type="text"
            value="<?= e($visit['department_name']) ?>"
            readonly>

        <input
            type="hidden"
            name="current_department_id"
            value="<?= (int)$visit['current_department_id'] ?>">

        <small>Use the transfer workflow to change department.</small>

    </div>

    <div class="form-group">

        <label>

            Attending Doctor

        </label>

        <input
            type="text"
            value="<?= e((string)($visit['doctor_name'] ?? 'Not assigned')) ?>"
            readonly>

        <input
            type="hidden"
            name="attending_doctor_id"
            value="<?= (int)($visit['attending_doctor_id'] ?? 0) ?>">

        <small>Use the doctor assignment workflow to change this value.</small>

    </div>

    <div class="form-group">

        <label>

            Current Status

        </label>

        <input
            type="text"
            value="<?= e($visit['visit_status']) ?>"
            readonly>

    </div>

</div>

<div class="form-actions">

    <a
        href="workspace.php?id=<?= (int)$visit['visit_id'] ?>"
        class="btn-secondary">

        Cancel

    </a>

    <button
        type="submit"
        class="btn-primary">

        Save Changes

    </button>

</div>

</form>

</main>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>

</div>
