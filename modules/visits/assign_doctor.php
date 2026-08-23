<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Page Configuration
|--------------------------------------------------------------------------
*/

$pageTitle = 'Assign Doctor';

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
require_once __DIR__ . '/../../services/PermissionService.php';

/*
|--------------------------------------------------------------------------
| Visit
|--------------------------------------------------------------------------
*/

$visitId = filter_input(

    INPUT_GET,

    'visit',

    FILTER_VALIDATE_INT

);

if (!$visitId) {

    header('Location: ../patients/search.php');

    exit;

}

/*
|--------------------------------------------------------------------------
| Service
|--------------------------------------------------------------------------
*/

$visitService = new VisitService($pdo);

$visit = $visitService->getVisitById($visitId);

if (!$visit) {

    http_response_code(404);

    exit('Encounter not found.');

}

$permissionService = new PermissionService($pdo);

if (!$permissionService->canAssignDoctor($visit, $currentUser)) {
    securityFailure(
        'You do not have permission to assign a doctor to this encounter.',
        $visitId,
        'ASSIGN_DOCTOR_ACCESS_DENIED'
    );
}

$stateService = new EncounterStateService();

$stateValidation = $stateService->validateDoctorAssignment($visit);

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
| Doctors
|--------------------------------------------------------------------------
*/

$doctors =

    $visitService->getAvailableDoctors(

        null

    );

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

<div class="card">

<h2>

Assign Doctor

</h2>

<p>

Encounter:

<strong>

<?= e($visit['visit_number']) ?>

</strong>

</p>

<form

    action="assign_doctor_save.php"

    method="POST"

>

<?= csrfField() ?>

<input
    type="hidden"
    name="visit_id"
    value="<?= (int)$visitId ?>">

<div class="form-group">

<label>

Doctor

</label>

<select

    name="doctor_id"

    required

>

<option value="">

Select Doctor

</option>

<?php foreach ($doctors as $doctor) : ?>

<option

    value="<?= (int)$doctor['id'] ?>"

    <?=

    (

        isset($visit['attending_doctor_id'])

        &&

        (int)$visit['attending_doctor_id']

        ===

        (int)$doctor['id']

    )

    ? 'selected'

    : ''

    ?>

>

<?=

e(

$doctor['full_name']

)

?>

(

<?=

e(

$doctor['employee_id']

)

?>

)

</option>

<?php endforeach; ?>

</select>

</div>

<div class="form-actions">

<button

    type="submit"

    class="btn btn-primary"

>

Assign Doctor

</button>

<a

    href="workspace.php?id=<?= (int)$visitId ?>"

    class="btn btn-secondary"

>

Cancel

</a>

</div>

</form>

</div>

</main>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>

</div>
