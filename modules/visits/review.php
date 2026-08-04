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
| POST Only
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header('Location: create.php');

    exit;

}

requireCsrfToken();

/*
|--------------------------------------------------------------------------
| Build Visit Array
|--------------------------------------------------------------------------
*/

$fields = [

    'patient_id',

    'visit_date',

    'visit_type',

    'current_department_id'

];

$visit = [];

foreach ($fields as $field) {

    $visit[$field] = trim($_POST[$field] ?? '');

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

$patient = $patientService->getPatientById(

    (int)$visit['patient_id']

);

if (!$patient) {

    exit('Patient not found.');

}

/*
|--------------------------------------------------------------------------
| Department
|--------------------------------------------------------------------------
*/

$departmentName = 'Unknown Department';

foreach ($visitService->getDepartments() as $department) {

    if (

        (int)$department['id'] ===

        (int)$visit['current_department_id']

    ) {

        $departmentName =

            $department['department_name'];

        break;

    }

}

/*
|--------------------------------------------------------------------------
| Page Configuration
|--------------------------------------------------------------------------
*/

$pageTitle = 'Review Encounter';

$moduleStylesheet =
    '/modules/visits/assets/visits.css';

$moduleScript =
    '/modules/visits/assets/visits.js';

/*
|--------------------------------------------------------------------------
| Helper
|--------------------------------------------------------------------------
*/

function reviewItem(
    string $label,
    string $value
): void {

?>

<div class="review-item">

    <div class="review-label">

        <?= e($label) ?>

    </div>

    <div class="review-value">

        <?= nl2br(

            e($value !== '' ? $value : '-')

        ) ?>

    </div>

</div>

<?php

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

        <h1>

            Review Encounter

        </h1>

        <p>

            Please review the encounter details before creating it.

        </p>

    </div>

</div>

<div class="card review-card">

    <div class="review-section">

        <h2>

            Patient Information

        </h2>

        <?php

        reviewItem(

            'Hospital Number',

            $patient['hospital_number']

        );

        reviewItem(

            'Patient Name',

            $patient['first_name'] .

            ' ' .

            $patient['last_name']

        );

        ?>

    </div>

    <hr>

    <div class="review-section">

        <h2>

            Encounter Information

        </h2>

        <?php

        reviewItem(

            'Visit Date',

            date(

                'd M Y h:i A',

                strtotime($visit['visit_date'])

            )

        );

        reviewItem(

            'Visit Type',

            $visit['visit_type']

        );

        reviewItem(

            'Department',

            $departmentName

        );

        ?>

    </div>

</div>

<form

    method="POST"

    action="save.php"

    class="confirmation-form">

<?= csrfField() ?>

<?php foreach ($visit as $field => $value) : ?>

<input

    type="hidden"

    name="<?= e($field) ?>"

    value="<?= e($value) ?>">

<?php endforeach; ?>

<div class="form-actions">

<button

    type="submit"

    formaction="create.php"

    class="btn-secondary">

    ← Back & Edit

</button>

<button

    type="submit"

    class="btn-primary">

    Confirm & Create Encounter

</button>

</div>

</form>

</main>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
