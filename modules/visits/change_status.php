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
require_once __DIR__ . '/../../services/PermissionService.php';

/*
|--------------------------------------------------------------------------
| POST Only
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header('Location: ../patients/search.php');

    exit;

}

requireCsrfToken();

/*
|--------------------------------------------------------------------------
| Logged-in User
|--------------------------------------------------------------------------
*/

$currentUser = $currentUser ?? ($_SESSION['user'] ?? null);

if (!$currentUser) {

    $_SESSION['error_message'] =

        'Your session has expired.';

    header(

        'Location: ../../authentication/login.php'

    );

    exit;

}

$userId = (int)$currentUser['id'];

/*
|--------------------------------------------------------------------------
| Visit ID
|--------------------------------------------------------------------------
*/

$visitId = filter_input(

    INPUT_POST,

    'visit_id',

    FILTER_VALIDATE_INT

);

$status = trim(

    $_POST['visit_status'] ?? ''

);

if (!$visitId || $status === '') {

    $_SESSION['error_message'] =

        'Invalid request.';

    header(

        'Location: ../patients/search.php'

    );

    exit;

}

/*
|--------------------------------------------------------------------------
| Services
|--------------------------------------------------------------------------
*/

$visitService = new VisitService($pdo);
$permissionService = new PermissionService($pdo);


/*
|--------------------------------------------------------------------------
| Verify Encounter
|--------------------------------------------------------------------------
*/

$visit = $visitService->getVisitById($visitId);

if (!$visit) {

    $_SESSION['error_message'] =

        'Encounter not found.';

    header(

        'Location: ../patients/search.php'

    );

    exit;

}

if ($status === 'Cancelled'
    && !$permissionService->canCancelEncounter($visit, $currentUser)
) {
    $_SESSION['error_message'] =
        'You are not allowed to cancel this encounter.';

    header(
        'Location: workspace.php?id=' . $visitId
    );

    exit;
}

if ($status === 'Completed') {
    $_SESSION['error_message'] =
        'Use the Complete Encounter review form to enter discharge details.';

    header(
        'Location: complete.php?visit=' . $visitId
    );

    exit;
}

if ($status !== 'Cancelled'
    && !$permissionService->canChangeEncounterStatus($visit, $currentUser)
) {
    $_SESSION['error_message'] =
        'You are not allowed to update this encounter status.';

    header(
        'Location: workspace.php?id=' . $visitId
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| Update Status
|--------------------------------------------------------------------------
*/

$updated = $visitService->updateStatus(

    $visitId,

    $status

);

if (!$updated['success']) {

    $_SESSION['error_message'] =

        'Unable to update encounter status.';

    header(

        'Location: workspace.php?id=' . $visitId

    );

    exit;

}

/*
|--------------------------------------------------------------------------
| Success
|--------------------------------------------------------------------------
*/

$_SESSION['success_message'] =

    'Encounter status updated successfully.';

header(

    'Location: workspace.php?id=' . $visitId

);

exit;
