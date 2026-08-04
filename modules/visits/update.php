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

    header('Location: index.php');

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

if (!$visitId) {

    $_SESSION['error_message'] =

        'Invalid encounter selected.';

    header('Location: index.php');

    exit;

}

/*
|--------------------------------------------------------------------------
| Build Update Array
|--------------------------------------------------------------------------
*/

$visit = [

    'visit_type' => trim(

        $_POST['visit_type'] ?? ''

    ),

    'current_department_id' =>

        filter_input(

            INPUT_POST,

            'current_department_id',

            FILTER_VALIDATE_INT

        ),

    'attending_doctor_id' =>

        !empty($_POST['attending_doctor_id'])

            ? (int)$_POST['attending_doctor_id']

            : null

];

/*
|--------------------------------------------------------------------------
| Services
|--------------------------------------------------------------------------
*/

$visitService = new VisitService($pdo);


/*
|--------------------------------------------------------------------------
| Update Encounter
|--------------------------------------------------------------------------
*/

$result = $visitService->updateVisit(

    $visitId,

    $visit,

    $userId

);

/*
|--------------------------------------------------------------------------
| Validation Failed
|--------------------------------------------------------------------------
*/

if (!$result['success']) {

    $_SESSION['validation_errors'] =

        $result['errors'];

    $_SESSION['old_visit'] =

        $_POST;

    header(

        'Location: edit.php?id=' . $visitId

    );

    exit;

}

/*
|--------------------------------------------------------------------------
| Success Message
|--------------------------------------------------------------------------
*/

$_SESSION['success_message'] =

    'Encounter updated successfully.';

/*
|--------------------------------------------------------------------------
| Redirect
|--------------------------------------------------------------------------
*/

header(

    'Location: workspace.php?id=' . $visitId

);

exit;
