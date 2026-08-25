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
require_once __DIR__ . '/../../services/VisitService.php';

/*
|--------------------------------------------------------------------------
| Page Configuration
|--------------------------------------------------------------------------
*/

$pageTitle = 'New Encounter';

$moduleStylesheet = '/modules/visits/assets/visits.css';
$moduleScript = '/modules/visits/assets/visits.js';

/*
|--------------------------------------------------------------------------
| Validate Patient
|--------------------------------------------------------------------------
*/

$patientId = filter_input(

    INPUT_GET,

    'patient',

    FILTER_VALIDATE_INT

);

if (!$patientId) {

    header('Location: ../patients/search.php');

    exit;

}

/*
|--------------------------------------------------------------------------
| Services
|--------------------------------------------------------------------------
*/

$patientService = new PatientService($pdo);
$visitService   = new VisitService($pdo);

/*
|--------------------------------------------------------------------------
| Patient
|--------------------------------------------------------------------------
*/

$patient = $patientService->getPatientById($patientId);

if (!$patient) {

    http_response_code(404);

    exit('Patient not found.');

}

if ((int)($patient['is_deleted'] ?? 0) === 1) {

    http_response_code(410);

    exit('This patient record has been deleted/voided. New encounters cannot be created for deleted patients.');

}

/*
|--------------------------------------------------------------------------
| Prevent Duplicate Active Encounter
|--------------------------------------------------------------------------
*/

$activeVisit = $visitService->getActiveVisit($patientId);

if ($activeVisit !== null) {

    $_SESSION['warning_message'] =
        'This patient already has an active encounter.';

    header(

        'Location: workspace.php?id=' .
        (int)$activeVisit['id']

    );

    exit;

}

/*
|--------------------------------------------------------------------------
| Departments
|--------------------------------------------------------------------------
*/

$departments = $visitService->getDepartments();

/*
|--------------------------------------------------------------------------
| Old Input
|--------------------------------------------------------------------------
*/

$old = $_SESSION['old_visit'] ?? [];

unset($_SESSION['old_visit']);

$errors = $_SESSION['validation_errors'] ?? [];

unset($_SESSION['validation_errors']);

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

        <h1>New Encounter</h1>

        <p>

            Register a new patient encounter.

        </p>

    </div>

</div>

<?php if (!empty($errors)) : ?>

<div class="alert-danger">

    <strong>Please correct the following:</strong>

    <ul>

        <?php foreach ($errors as $error) : ?>

        <li><?= e($error) ?></li>

        <?php endforeach; ?>

    </ul>

</div>

<?php endif; ?>

<div class="card">

    <h2>Patient Information</h2>

    <div class="review-item">

        <div class="review-label">

            Hospital Number

        </div>

        <div class="review-value">

            <?= e($patient['hospital_number']) ?>

        </div>

    </div>

    <div class="review-item">

        <div class="review-label">

            Patient Name

        </div>

        <div class="review-value">

            <?= e($patient['first_name']) ?>

            <?= e($patient['last_name']) ?>

        </div>

    </div>

</div>

<form

    method="POST"

    action="review.php"

    class="card">

<?= csrfField() ?>

<input

    type="hidden"

    name="patient_id"

    value="<?= (int)$patient['id'] ?>">

<div class="form-section">

<h2>Encounter Information</h2>

<div class="form-grid">

<div class="form-group">

<label>

Visit Date

<span class="required">*</span>

</label>

<input

type="datetime-local"

name="visit_date"

required

value="<?= e(

$old['visit_date']

?? date('Y-m-d\TH:i')

) ?>">

</div>

<div class="form-group">

<label>

Visit Type

<span class="required">*</span>

</label>

<select

name="visit_type"

required>

<option value="">Select Visit Type</option>

<?php

$types = [

'Outpatient',

'Inpatient',

'Emergency',

'Referral'

];

foreach ($types as $type) :

?>

<option

value="<?= $type ?>"

<?=

($old['visit_type'] ?? '') === $type

? 'selected'

: ''

?>>

<?= $type ?>

</option>

<?php endforeach; ?>

</select>

</div>

<div class="form-group">

<label>

Department

<span class="required">*</span>

</label>

<select

name="current_department_id"

required>

<option value="">

Select Department

</option>

<?php foreach ($departments as $department) : ?>

<option

value="<?= (int)$department['id'] ?>"

<?=

($old['current_department_id'] ?? '') ==

$department['id']

? 'selected'

: ''

?>>

<?= e($department['department_name']) ?>

</option>

<?php endforeach; ?>

</select>

</div>

</div>

</div>

<div class="form-actions">

<a

href="../patients/view.php?id=<?= (int)$patient['id'] ?>"

class="btn-secondary">

Cancel

</a>

<button

type="submit"

class="btn-primary">

Continue

</button>

</div>

</form>

</main>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
