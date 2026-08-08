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

    header('Location: search.php');

    exit;

}

requireCsrfToken();

/*
|--------------------------------------------------------------------------
| Validate Patient ID
|--------------------------------------------------------------------------
*/

$id = filter_input(
    INPUT_POST,
    'id',
    FILTER_VALIDATE_INT
);

if (!$id) {

    $_SESSION['error_message'] =
        'Invalid patient selected.';

    header('Location: search.php');

    exit;

}

/*
|--------------------------------------------------------------------------
| Services
|--------------------------------------------------------------------------
*/

$patientService = new PatientService($pdo);
$permissionService = new PermissionService($pdo);

/*
|--------------------------------------------------------------------------
| Collect Patient Data
|--------------------------------------------------------------------------
*/

$patient = [

    'first_name'        => trim($_POST['first_name'] ?? ''),

    'middle_name'       => trim($_POST['middle_name'] ?? ''),

    'last_name'         => trim($_POST['last_name'] ?? ''),

    'gender'            => trim($_POST['gender'] ?? ''),

    'date_of_birth'     => trim($_POST['date_of_birth'] ?? ''),

    'marital_status'    => trim($_POST['marital_status'] ?? ''),

    'occupation'        => trim($_POST['occupation'] ?? ''),

    'phone'             => trim($_POST['phone'] ?? ''),

    'email'             => trim($_POST['email'] ?? ''),

    'address'           => trim($_POST['address'] ?? ''),

    'state_of_origin'   => trim($_POST['state_of_origin'] ?? ''),

    'nationality'       => trim($_POST['nationality'] ?? ''),

    'blood_group'       => trim($_POST['blood_group'] ?? ''),

    'genotype'          => trim($_POST['genotype'] ?? ''),

    'allergies'         => trim($_POST['allergies'] ?? ''),

    'next_of_kin'       => trim($_POST['next_of_kin'] ?? ''),

    'next_of_kin_relationship'
                        => trim($_POST['next_of_kin_relationship'] ?? ''),

    'next_of_kin_phone' => trim($_POST['next_of_kin_phone'] ?? '')

];

$expectedVersion = filter_input(
    INPUT_POST,
    'demographic_version',
    FILTER_VALIDATE_INT
);

$amendmentReason = trim($_POST['amendment_reason'] ?? '');

/*
|--------------------------------------------------------------------------
| Current User
|--------------------------------------------------------------------------
*/

$currentUser = $_SESSION['user'] ?? null;

if (!$currentUser) {

    $_SESSION['error_message'] =
        'Your session has expired.';

    header('Location: ../../authentication/login.php');

    exit;

}

if (!$permissionService->canEditPatientDemographics($id, $currentUser)) {
    $permissionService->logPatientDenied(
        (int)$currentUser['id'],
        $id,
        'PATIENT_CHART_ACCESS_DENIED',
        'User attempted to update patient demographics without permission.'
    );

    http_response_code(403);
    exit('You do not have permission to edit this patient record.');
}

/*
|--------------------------------------------------------------------------
| Update Patient
|--------------------------------------------------------------------------
*/

try {

    $result = $patientService->updatePatientWithContext(
        $id,
        $patient,
        $amendmentReason,
        $expectedVersion !== false ? $expectedVersion : null,
        (int)$currentUser['id']
    );


} catch (Throwable $e) {

    $_SESSION['error_message'] =
        'Unable to update the patient record.';

    header('Location: edit.php?id=' . $id);

    exit;

}

/*
|--------------------------------------------------------------------------
| Update Failed
|--------------------------------------------------------------------------
*/

if (!$result['success']) {

    $_SESSION['validation_errors'] =

        $result['errors'];

    $_SESSION['old_patient'] =

        $_POST;

    header('Location: edit.php?id=' . $id);

    exit;

}

/*
|--------------------------------------------------------------------------
| Success Message
|--------------------------------------------------------------------------
*/

$_SESSION['success_message'] =
    'Patient record updated successfully.';

/*
|--------------------------------------------------------------------------
| Redirect
|--------------------------------------------------------------------------
*/

header(

    'Location: view.php?id=' . $id

);

exit;
