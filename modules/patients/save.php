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
require_once __DIR__ . '/../../services/PermissionService.php';

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

/*
|--------------------------------------------------------------------------
| Collect Patient Data
|--------------------------------------------------------------------------
*/

$patient = [

    'first_name' => trim($_POST['first_name'] ?? ''),

    'middle_name' => trim($_POST['middle_name'] ?? ''),

    'last_name' => trim($_POST['last_name'] ?? ''),

    'gender' => trim($_POST['gender'] ?? ''),

    'date_of_birth' => trim($_POST['date_of_birth'] ?? ''),

    'marital_status' => trim($_POST['marital_status'] ?? ''),

    'occupation' => trim($_POST['occupation'] ?? ''),

    'place_of_work' => trim($_POST['place_of_work'] ?? ''),

    'phone' => trim($_POST['phone'] ?? ''),

    'whatsapp_number' => trim($_POST['whatsapp_number'] ?? ''),

    'email' => trim($_POST['email'] ?? ''),

    'address' => trim($_POST['address'] ?? ''),

    'state_of_origin' => trim($_POST['state_of_origin'] ?? ''),

    'nationality' => trim($_POST['nationality'] ?? ''),

    'ethnic_group' => trim($_POST['ethnic_group'] ?? ''),

    'religion' => trim($_POST['religion'] ?? ''),

    'blood_group' => trim($_POST['blood_group'] ?? ''),

    'genotype' => trim($_POST['genotype'] ?? ''),

    'allergies' => trim($_POST['allergies'] ?? ''),

    'next_of_kin' => trim($_POST['next_of_kin'] ?? ''),

    'next_of_kin_relationship'
        => trim($_POST['next_of_kin_relationship'] ?? ''),

    'next_of_kin_phone' => trim($_POST['next_of_kin_phone'] ?? '')

    ,'next_of_kin_address' => trim($_POST['next_of_kin_address'] ?? '')

    ,'duplicate_review_ack' => trim($_POST['duplicate_review_ack'] ?? '')

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

$permissionService = new PermissionService($pdo);

if (!$permissionService->canRegisterPatient($currentUser)) {
    http_response_code(403);
    exit('You do not have permission to register patients.');
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

    if (!empty($result['duplicate_review_required'])) {
        $_SESSION['duplicate_candidates'] = $result['duplicate_candidates'] ?? [];
        header('Location: register.php');
        exit;
    }

    header('Location: register.php');

    exit;

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
