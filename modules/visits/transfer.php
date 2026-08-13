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
| Services
|--------------------------------------------------------------------------
*/

$visitService = new VisitService($pdo);

/*
|--------------------------------------------------------------------------
| Load Visit
|--------------------------------------------------------------------------
*/

$visit = $visitService->getVisitById($visitId);

if (!$visit) {

    http_response_code(404);

    exit('Encounter not found.');

}

$permissionService = new PermissionService($pdo);

if (!$permissionService->canTransferEncounter($visit, $currentUser)) {
    securityFailure(
        'You do not have permission to transfer this encounter.',
        $visitId,
        'TRANSFER_ACCESS_DENIED'
    );
}

/*
|--------------------------------------------------------------------------
| Encounter State
|--------------------------------------------------------------------------
*/

$stateService = new EncounterStateService();

$stateValidation = $stateService->validateEditableEncounter($visit);

if (!$stateValidation['success']) {

    $_SESSION['error_message'] =

        implode('<br>', $stateValidation['errors']);

    header(

        'Location: workspace.php?id=' .

        $visitId

    );

    exit;

}

/*
|--------------------------------------------------------------------------
| Departments
|--------------------------------------------------------------------------
*/

$departments =

    $visitService->getDepartments();

/*
|--------------------------------------------------------------------------
| Remove Current Department
|--------------------------------------------------------------------------
*/

$departments = array_filter(

    $departments,

    fn($department) =>

        (int)$department['id'] !==

        (int)$visit['current_department_id']

);

/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

$pageTitle = 'Transfer Patient';

$moduleStylesheet =

    '/modules/visits/assets/visits.css';

$moduleScript =

    '/modules/visits/assets/visits.js';

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

            Transfer Patient

        </h1>

        <p>

            Transfer this encounter to another department.

        </p>

    </div>

</div>

<div class="card">

    <h2>

        Current Encounter

    </h2>

    <div class="summary-item">

        <strong>Visit Number</strong>

        <span>

            <?= e($visit['visit_number']) ?>

        </span>

    </div>

    <div class="summary-item">

        <strong>Patient</strong>

        <span>

            <?= e(

                $visit['first_name'] .

                ' ' .

                $visit['last_name']

            ) ?>

        </span>

    </div>

    <div class="summary-item">

        <strong>Current Department</strong>

        <span>

            <?= e(

                $visit['department_name']

            ) ?>

        </span>

    </div>

    <div class="summary-item">

        <strong>Status</strong>

        <span>

            <?= e(

                $visit['visit_status']

            ) ?>

        </span>

    </div>

</div>

<form

    action="transfer_save.php"

    method="POST"

    class="card">

    <?= csrfField() ?>

    <input

        type="hidden"

        name="visit_id"

        value="<?= $visitId ?>">

    <div class="form-group">

        <label>

            Destination Department

        </label>

        <select

            name="department_id"

            required>

            <option value="">

                Select Department

            </option>

            <?php foreach ($departments as $department): ?>

                <option

                    value="<?=

                        (int)$department['id']

                    ?>">

                    <?=

                        e(

                            $department['department_name']

                        )

                    ?>

                </option>

            <?php endforeach; ?>

        </select>

    </div>

    <div class="form-group">

        <label>

            Transfer Type

        </label>

        <select

            name="transfer_type">

            <option value="Forward">

                Forward

            </option>

            <option value="Return">

                Return

            </option>

            <option value="Referral">

                Referral

            </option>

            <option value="Discharge">

                Discharge

            </option>

        </select>

    </div>

    <div class="form-group">

        <label>

            Remarks

        </label>

        <textarea

            name="remarks"

            rows="5"

            placeholder="Reason for transfer..."></textarea>

    </div>

    <div class="form-actions">

        <a

            href="workspace.php?id=<?= $visitId ?>"

            class="btn-secondary">

            Cancel

        </a>

        <button

            type="submit"

            class="btn-primary">

            Transfer Patient

        </button>

    </div>

</form>

</main>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>

</div>
