<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Page Configuration
|--------------------------------------------------------------------------
*/

$pageTitle = 'Encounter Workspace';

$moduleStylesheet =
    '/modules/visits/assets/visits.css';

$moduleScript =
    '/modules/visits/assets/visits.js';

/*
|--------------------------------------------------------------------------
| Bootstrap
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

require_once __DIR__ . '/../../services/VisitService.php';
require_once __DIR__ . '/../../services/PatientService.php';
require_once __DIR__ . '/../../services/PermissionService.php';

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

    header('Location: ../patients/search.php');

    exit;

}

/*
|--------------------------------------------------------------------------
| Active Tab
|--------------------------------------------------------------------------
*/

$activeTab = $_GET['tab'] ?? 'overview';

/*
|--------------------------------------------------------------------------
| Services
|--------------------------------------------------------------------------
*/

$visitService = new VisitService($pdo);

$patientService = new PatientService($pdo);

/*
|--------------------------------------------------------------------------
| Load Encounter
|--------------------------------------------------------------------------
*/

$visit = $visitService->getVisitById($visitId);

if (!$visit) {

    http_response_code(404);

    exit('Encounter not found.');

}

$permissionService = new PermissionService($pdo);

if (!$permissionService->canViewEncounter($visit, $currentUser)) {

    $permissionService->logDenied(
        isset($currentUser['id']) ? (int)$currentUser['id'] : null,
        $visitId,
        'WORKSPACE_ACCESS_DENIED',
        'User attempted to access an encounter workspace outside their department.'
    );

    http_response_code(403);

    require_once __DIR__ . '/../../layouts/header.php';

    ?>

    <main class="content">

        <div class="card alert-danger">

            <h1>Access Denied</h1>

            <p>

                You do not have permission to access this encounter workspace.

            </p>

            <p>

                Please return to your department encounter list.

            </p>

        </div>

    </main>

    <?php

    require_once __DIR__ . '/../../layouts/footer.php';

    exit;

}

$errorMessage = $_SESSION['error_message'] ?? null;

unset($_SESSION['error_message']);

$successMessage = $_SESSION['success_message'] ?? null;

unset($_SESSION['success_message']);

/*
|--------------------------------------------------------------------------
| Department Access
|--------------------------------------------------------------------------
|
| Enterprise Workflow
|
| Newly-created encounters are automatically received by the
| creating department.
|
| Only transferred encounters that have NOT yet been received
| should be blocked.
|
*/

$hasPendingTransfer = $visitService->hasPendingTransfer($visitId);

$canAccessDepartment = !$hasPendingTransfer;

/*
|--------------------------------------------------------------------------
| Load Patient
|--------------------------------------------------------------------------
*/

$patient = $patientService->getPatientById(

    (int)$visit['patient_id']

);

if (!$patient) {

    http_response_code(404);

    exit('Patient not found.');

}

/*
|--------------------------------------------------------------------------
| Future Workspace Data
|--------------------------------------------------------------------------
*/

$consultation = null;

$nursing = null;

$laboratory = [];

$radiology = [];

$pharmacy = [];

$billing = [];

$physiotherapy = [];

$theatre = [];

$documents = [];

$notes = [];

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

    <?php if ($errorMessage !== null): ?>

        <div class="alert-danger">

            <?= nl2br(e((string)$errorMessage)) ?>

        </div>

    <?php endif; ?>

    <?php if ($successMessage !== null): ?>

        <div class="alert-success">

            <?= nl2br(e((string)$successMessage)) ?>

        </div>

    <?php endif; ?>

    <?php require __DIR__ . '/partials/encounter_header.php'; ?>

    <?php require __DIR__ . '/partials/quick_actions.php'; ?>

    <?php require __DIR__ . '/partials/queue_status.php'; ?>

    <?php require __DIR__ . '/partials/encounter_summary.php'; ?>

    <?php require __DIR__ . '/partials/encounter_status.php'; ?>

    <?php require __DIR__ . '/partials/timeline.php'; ?>

    <?php if (!$canAccessDepartment) : ?>

        <div class="card receive-card">

            <h2>

                Patient Awaiting Department Reception

            </h2>

            <p>

                This patient has been transferred to

                <strong>

                    <?= e($visit['department_name']) ?>

                </strong>

                but has not yet been officially received.

            </p>

            <p>

                Department activities remain locked until the
                receiving department confirms receipt.

            </p>

            <a

                href="receive.php?visit=<?= (int)$visit['id'] ?>"

                class="btn-primary">

                Receive Patient

            </a>

        </div>

    <?php else : ?>

        <?php require __DIR__ . '/partials/workspace_navigation.php'; ?>

        <?php

        switch ($activeTab) {

            case 'consultation':

                require __DIR__ . '/partials/tabs/consultation.php';

                break;

            case 'nursing':

                require __DIR__ . '/partials/tabs/nursing.php';

                break;

            case 'laboratory':

                require __DIR__ . '/partials/tabs/laboratory.php';

                break;

            case 'radiology':

                require __DIR__ . '/partials/tabs/radiology.php';

                break;

            case 'pharmacy':

                require __DIR__ . '/partials/tabs/pharmacy.php';

                break;

            case 'billing':

                require __DIR__ . '/partials/tabs/billing.php';

                break;

            case 'physiotherapy':

                require __DIR__ . '/partials/tabs/physiotherapy.php';

                break;

            case 'theatre':

                require __DIR__ . '/partials/tabs/theatre.php';

                break;

            case 'documents':

                require __DIR__ . '/partials/tabs/documents.php';

                break;

            case 'notes':

                require __DIR__ . '/partials/tabs/notes.php';

                break;

            case 'overview':

            default:

                require __DIR__ . '/partials/tabs/overview.php';

                break;

        }

        ?>

    <?php endif; ?>

</main>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>

</div>
