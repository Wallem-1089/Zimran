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

if (

    $_SERVER['REQUEST_METHOD']

    !==

    'POST'

) {

    header(

        'Location: ../patients/search.php'

    );

    exit;

}

requireCsrfToken();

/*
|--------------------------------------------------------------------------
| User
|--------------------------------------------------------------------------
*/

$currentUser =

$currentUser

??

($_SESSION['user'] ?? null);

if (!$currentUser) {

    $_SESSION['error_message'] =

        'Your session has expired.';

    header(

        'Location: ../../authentication/login.php'

    );

    exit;

}

$userId =

(int)$currentUser['id'];

/*
|--------------------------------------------------------------------------
| Input
|--------------------------------------------------------------------------
*/

$visitId =

(int)(

$_POST['visit_id']

?? 0

);

$doctorId =

(int)(

$_POST['doctor_id']

?? 0

);

if (

$visitId <= 0

||

$doctorId <= 0

) {

    $_SESSION['error_message'] =

        'Invalid doctor assignment.';

    header(

        'Location: assign_doctor.php?visit='

        .

        $visitId

    );

    exit;

}

/*
|--------------------------------------------------------------------------
| Services
|--------------------------------------------------------------------------
*/

$visitService =

new VisitService($pdo);


/*
|--------------------------------------------------------------------------
| Assign Doctor
|--------------------------------------------------------------------------
*/

$result =

$visitService->assignDoctor(

    $visitId,

    $doctorId,

    $userId

);

if (

!$result['success']

) {

    $_SESSION['error_message'] =

        implode(

            '<br>',

            $result['errors']

        );

    header(

        'Location: assign_doctor.php?visit='

        .

        $visitId

    );

    exit;

}

/*
|--------------------------------------------------------------------------
| Success
|--------------------------------------------------------------------------
*/

$_SESSION['success_message'] =
    'Doctor assigned successfully: ' .
    $result['doctor_name'] . '.';

/*
|--------------------------------------------------------------------------
| Redirect
|--------------------------------------------------------------------------
*/

header('Location: workspace.php?id=' . $visitId);

exit;
