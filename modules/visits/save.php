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

    header('Location: create.php');

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
| Visit Data
|--------------------------------------------------------------------------
*/

$visit = [

    'patient_id' =>

        (int)($_POST['patient_id'] ?? 0),

    'visit_date' =>

        trim($_POST['visit_date'] ?? ''),

    'visit_type' =>

        trim($_POST['visit_type'] ?? ''),

    'current_department_id' =>

        (int)($_POST['current_department_id'] ?? 0)

];

/*
|--------------------------------------------------------------------------
| Services
|--------------------------------------------------------------------------
*/

$visitService = new VisitService($pdo);


/*
|--------------------------------------------------------------------------
| Create Encounter
|--------------------------------------------------------------------------
*/

$result = $visitService->createVisit(

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

        'Location: create.php?patient=' .

        $visit['patient_id']

    );

    exit;

}

/*
|--------------------------------------------------------------------------
| Success Message
|--------------------------------------------------------------------------
*/

$visitNumber =

    $visitService->getVisitNumber(

        $result['visit_id']

    );

$_SESSION['success_message'] =

    'Encounter ' .

    $visitNumber .

    ' created successfully.';

/*
|--------------------------------------------------------------------------
| Redirect
|--------------------------------------------------------------------------
*/

header(

    'Location: workspace.php?id=' .

    $result['visit_id']

);

exit;
