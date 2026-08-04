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

require_once __DIR__ . '/../../services/PatientService.php';
require_once __DIR__ . '/../../services/AuditService.php';

/*
|--------------------------------------------------------------------------
| Allow POST Requests Only
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header('Location: register.php');

    exit;

}

requireCsrfToken();

/*
|--------------------------------------------------------------------------
| Basic Validation
|--------------------------------------------------------------------------
*/

if (empty($_POST['first_name'])) {

    header('Location: register.php');

    exit;

}
/*
|--------------------------------------------------------------------------
| Services
|--------------------------------------------------------------------------
*/

$patientService = new PatientService($pdo);

$auditService = new AuditService($pdo);

/*
|--------------------------------------------------------------------------
| Collect Patient Data
|--------------------------------------------------------------------------
*/

$patient = [

    'first_name' => trim($_POST['first_name'] ?? ''),

    'last_name' => trim($_POST['last_name'] ?? ''),

    'gender' => trim($_POST['gender'] ?? ''),

    'date_of_birth' => trim($_POST['date_of_birth'] ?? ''),

    'phone' => trim($_POST['phone'] ?? ''),

    'email' => trim($_POST['email'] ?? ''),

    'address' => trim($_POST['address'] ?? ''),

    'blood_group' => trim($_POST['blood_group'] ?? ''),

    'genotype' => trim($_POST['genotype'] ?? ''),

    'allergies' => trim($_POST['allergies'] ?? ''),

    'next_of_kin' => trim($_POST['next_of_kin'] ?? ''),

    'next_of_kin_phone' => trim($_POST['next_of_kin_phone'] ?? '')

];

/*
|--------------------------------------------------------------------------
| Current User
|--------------------------------------------------------------------------
*/

if (!isset($currentUser)) {

    $currentUser = $_SESSION['user'] ?? null;

}

if (!$currentUser) {

    $_SESSION['error_message'] =
        'Your session has expired.';

    header('Location: ../../authentication/login.php');

    exit;

}

$registeredBy = (int)$currentUser['id'];

/*
|--------------------------------------------------------------------------
| Create Patient
|--------------------------------------------------------------------------
*/

$result = $patientService->createPatient(

    $patient,

    $registeredBy

);

/*
|--------------------------------------------------------------------------
| Validation Failed
|--------------------------------------------------------------------------
|
| Store the validation errors and the user's
| previously entered data, then redirect back
| to the registration form.
|
*/

if (!$result['success']) {

    $_SESSION['validation_errors'] =

        $result['errors'];

    $_SESSION['old_patient'] =

        $_POST;

    header('Location: register.php');

    exit;

}

/*
|--------------------------------------------------------------------------
| Audit Log
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Audit Log
|--------------------------------------------------------------------------
*/

try {

    $auditService->patientRegistered(
        $registeredBy,
        $result['hospital_number']
    );

} catch (Throwable $e) {

    die($e->getMessage());

}

/*
|--------------------------------------------------------------------------
| Success Message
|--------------------------------------------------------------------------
*/

$_SESSION['success_message'] =

    'Patient registered successfully. Hospital Number: '

    . $result['hospital_number'];

/*
|--------------------------------------------------------------------------
| Redirect
|--------------------------------------------------------------------------
*/

header(
    'Location: view.php?id=' . $result['patient_id']
);

exit;
