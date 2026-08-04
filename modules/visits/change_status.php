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
