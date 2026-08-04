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
| User
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
| Input
|--------------------------------------------------------------------------
*/

$visitId = (int)($_POST['visit_id'] ?? 0);

if ($visitId <= 0) {

    $_SESSION['error_message'] =

        'Invalid encounter.';

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
| Receive
|--------------------------------------------------------------------------
*/

$result = $visitService->receiveVisit(

    $visitId,

    $userId

);

if (!$result['success']) {

    $_SESSION['error_message'] =

        implode('<br>', $result['errors']);

    header(

        'Location: receive.php?visit='

        . $visitId

    );

    exit;

}

/*
|--------------------------------------------------------------------------
| Success
|--------------------------------------------------------------------------
*/

$_SESSION['success_message'] =

    'Patient successfully received in '

    .

    $result['department_name']

    .

    '.';

/*
|--------------------------------------------------------------------------
| Redirect
|--------------------------------------------------------------------------
*/

header(

    'Location: workspace.php?id='

    . $visitId

);

exit;
