<?php

declare(strict_types=1);

$pageTitle = 'Patient Encounter History';
$moduleStylesheet = '../../modules/patients/assets/patients.css';

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

require_once __DIR__ . '/../../services/PatientService.php';
require_once __DIR__ . '/../../services/VisitService.php';

/*
|--------------------------------------------------------------------------
| Patient ID
|--------------------------------------------------------------------------
*/

$id = filter_input(

    INPUT_GET,

    'id',

    FILTER_VALIDATE_INT

);

if (!$id) {

    header('Location: search.php');

    exit;

}

/*
|--------------------------------------------------------------------------
| Services
|--------------------------------------------------------------------------
*/

$patientService = new PatientService($pdo);
$visitService = new VisitService($pdo);

/*
|--------------------------------------------------------------------------
| Patient
|--------------------------------------------------------------------------
*/

$patient = $patientService->getPatientById($id);

if (!$patient) {

    http_response_code(404);

    exit('Patient not found.');

}

/*
|--------------------------------------------------------------------------
| Encounters
|--------------------------------------------------------------------------
*/

$visits = $visitService->getPatientVisits($id);

/*
|--------------------------------------------------------------------------
| Group Encounters By Year
|--------------------------------------------------------------------------
*/

$groupedVisits = [];

foreach ($visits as $visit) {

    $year = date(

        'Y',

        strtotime($visit['visit_date'])

    );

    $groupedVisits[$year][] = $visit;

}

krsort($groupedVisits);

require_once __DIR__ . '/../../layouts/header.php';
require_once __DIR__ . '/../../layouts/sidebar.php';

?>

<div class="main-container">

<?php require_once __DIR__ . '/../../layouts/navbar.php'; ?>

<main class="content">

<div class="page-header">

    <div>

        <h1>

            Patient Encounter History

        </h1>

        <p>

            View all encounters for this patient.

        </p>

    </div>

</div>

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

<?php if (empty($visits)) : ?>

<div class="card empty-state">

    <h2>

        No Encounters Found

    </h2>

    <p>

        This patient has not been registered for any encounter.

    </p>

    <a

        href="../visits/create.php?patient=<?= (int)$patient['id'] ?>"

        class="btn-primary">

        Create First Encounter

    </a>

</div>

<?php else : ?>

<?php foreach ($groupedVisits as $year => $yearVisits) : ?>

<div class="card">

    <h2>

        <?= $year ?>

        (<?= count($yearVisits) ?>

        Encounter<?= count($yearVisits) !== 1 ? 's' : '' ?>)

    </h2>

    <div class="table-responsive">

        <table class="table">

            <thead>

                <tr>

                    <th>Visit Number</th>

                    <th>Date</th>

                    <th>Type</th>

                    <th>Department</th>

                    <th>Status</th>

                    <th>Doctor</th>

                    <th></th>

                </tr>

            </thead>

            <tbody>

            <?php foreach ($yearVisits as $visit) : ?>

                <?php

                $badge = 'badge-secondary';

                switch ($visit['visit_status']) {

                    case 'Completed':
                        $badge = 'badge-success';
                        break;

                    case 'Cancelled':
                        $badge = 'badge-danger';
                        break;

                    case 'Waiting':
                        $badge = 'badge-warning';
                        break;

                    default:
                        $badge = 'badge-primary';

                }

                ?>

                <tr>

                    <td>

                        <?= e($visit['visit_number']) ?>

                    </td>

                    <td>

                        <?= date(

                            'd M Y h:i A',

                            strtotime($visit['visit_date'])

                        ) ?>

                    </td>

                    <td>

                        <?= e($visit['visit_type']) ?>

                    </td>

                    <td>

                        <?= e(

                            $visit['department_name']

                            ?? 'Unassigned'

                        ) ?>

                    </td>

                    <td>

                        <span class="status-badge <?= $badge ?>">

                            <?= e($visit['visit_status']) ?>

                        </span>

                    </td>

                    <td>

                        <?= e(

                            $visit['doctor_name']

                            ?? 'Not Assigned'

                        ) ?>

                    </td>

                    <td>

                        <a

                            href="../visits/workspace.php?id=<?= (int)$visit['id'] ?>"

                            class="btn-secondary btn-sm">

                            Open

                        </a>

                    </td>

                </tr>

            <?php endforeach; ?>

            </tbody>

        </table>

    </div>

</div>

<?php endforeach; ?>

<?php endif; ?>

<div class="form-actions">

    <a

        href="view.php?id=<?= (int)$patient['id'] ?>"

        class="btn-secondary">

        Back to Patient

    </a>

    <a

        href="../visits/create.php?patient=<?= (int)$patient['id'] ?>"

        class="btn-primary">

        New Encounter

    </a>

</div>

</main>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>