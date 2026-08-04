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

    $_SESSION['error_message'] = 'Your session has expired.';

    header('Location: ../../authentication/login.php');

    exit;

}

$userId = (int)$currentUser['id'];

/*
|--------------------------------------------------------------------------
| Input
|--------------------------------------------------------------------------
*/

$visitId = (int)($_POST['visit_id'] ?? 0);

$remarks = trim($_POST['remarks'] ?? '');

if ($visitId <= 0) {

    $_SESSION['error_message'] = 'Invalid receive request.';

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
| Receive Patient
|--------------------------------------------------------------------------
*/

$result = $visitService->receiveVisit(

    $visitId,

    $userId,

    $remarks !== '' ? $remarks : null

);

/*
|--------------------------------------------------------------------------
| Failed
|--------------------------------------------------------------------------
*/

if (!$result['success']) {

    $_SESSION['error_message'] = implode(PHP_EOL, $result['errors']);

    header('Location: receive.php?visit=' . $visitId);

    exit;

}

/*
|--------------------------------------------------------------------------
| Success Message
|--------------------------------------------------------------------------
*/

$_SESSION['success_message'] = sprintf(

    'Patient successfully received in %s.',

    $result['department_name']

);

/*
|--------------------------------------------------------------------------
| Redirect
|--------------------------------------------------------------------------
*/

header('Location: workspace.php?id=' . $visitId);

exit;
