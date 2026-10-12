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
require_once __DIR__ . '/../../services/VisitService.php';
require_once __DIR__ . '/../../services/PatientRegistrationBillingService.php';
require_once __DIR__ . '/../../services/PermissionService.php';

/*
|--------------------------------------------------------------------------
| Page Configuration
|--------------------------------------------------------------------------
*/

$pageTitle = 'New Encounter';

$moduleStylesheet = '/modules/visits/assets/visits.css';
$moduleScript = '/modules/visits/assets/visits.js';

/*
|--------------------------------------------------------------------------
| Validate Patient
|--------------------------------------------------------------------------
*/

$patientId = filter_input(

    INPUT_GET,

    'patient',

    FILTER_VALIDATE_INT

);

if (!$patientId) {

    header('Location: ../patients/search.php');

    exit;

}

/*
|--------------------------------------------------------------------------
| Services
|--------------------------------------------------------------------------
*/

$patientService = new PatientService($pdo);
$visitService   = new VisitService($pdo);
$registrationBillingService = new PatientRegistrationBillingService($pdo);
$permissionService = new PermissionService($pdo);
$currentUser = $currentUser ?? ($_SESSION['user'] ?? null);
$canUseEmergencyWorkflow = $permissionService->canUseEmergencyWorkflow($currentUser);

/*
|--------------------------------------------------------------------------
| Patient
|--------------------------------------------------------------------------
*/

$patient = $patientService->getPatientById($patientId);

if (!$patient) {

    http_response_code(404);

    exit('Patient not found.');

}

if ((int)($patient['is_deleted'] ?? 0) === 1) {

    http_response_code(410);

    exit('This patient record has been deleted/voided. New encounters cannot be created for deleted patients.');

}

$registrationGate = $patientService->getRegistrationGateStatus($patientId);
$activeFamilyCardCoverage = $registrationGate['family_card'] ?? null;
if ($activeFamilyCardCoverage === null) {
    try {
        $familyCoverageStmt = $pdo->prepare('
            SELECT fc.id,
                   fc.card_number,
                   fc.valid_until,
                   fcm.relationship,
                   fcm.member_type
            FROM family_card_members fcm
            INNER JOIN family_cards fc ON fc.id = fcm.family_card_id
            WHERE fcm.patient_id = :patient_id
              AND fcm.status = \'Active\'
              AND fc.status = \'Active\'
              AND fc.valid_until >= CURDATE()
            ORDER BY fc.valid_until DESC, fc.id DESC
            LIMIT 1
        ');
        $familyCoverageStmt->execute([':patient_id' => $patientId]);
        $activeFamilyCardCoverage = $familyCoverageStmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable) {
        $activeFamilyCardCoverage = null;
    }
}
$canCreateEmergencyEncounterFromGate = !empty($registrationGate['can_create_emergency_encounter'])
    && $canUseEmergencyWorkflow;
$emergencyOnlyEncounter = empty($registrationGate['can_create_encounter'])
    && $canCreateEmergencyEncounterFromGate;
if (empty($registrationGate['can_create_encounter']) && !$canCreateEmergencyEncounterFromGate) {
    $currentUser = $currentUser ?? ($_SESSION['user'] ?? null);
    if (($registrationGate['status'] ?? '') === 'Expired' && $currentUser) {
        $registrationBillingService->ensureRenewalRequest($patientId, $currentUser);
    }

    $_SESSION['validation_errors'] = [
        (string)($registrationGate['message'] ?? 'Registration payment is required before creating an encounter.')
    ];

    header('Location: ../patients/view.php?id=' . $patientId);
    exit;
}

/*
|--------------------------------------------------------------------------
| Prevent Duplicate Active Encounter
|--------------------------------------------------------------------------
*/

$activeVisit = $visitService->getActiveVisit($patientId);

if ($activeVisit !== null) {

    $_SESSION['warning_message'] =
        'This patient already has an active encounter.';

    header(

        'Location: workspace.php?id=' .
        (int)$activeVisit['id']

    );

    exit;

}

/*
|--------------------------------------------------------------------------
| Departments
|--------------------------------------------------------------------------
*/

$departments = $visitService->getEncounterCreationDepartments();
if ($emergencyOnlyEncounter) {
    $departments = array_values(array_filter(
        $departments,
        static fn (array $department): bool => strcasecmp((string)($department['department_name'] ?? ''), 'Emergency') === 0
    ));
} elseif (!$canUseEmergencyWorkflow) {
    $departments = array_values(array_filter(
        $departments,
        static fn (array $department): bool => strcasecmp((string)($department['department_name'] ?? ''), 'Emergency') !== 0
    ));
}
$defaultDepartmentId = $visitService->getDefaultEncounterDepartmentId();
if ($emergencyOnlyEncounter && $departments !== []) {
    $defaultDepartmentId = (int)$departments[0]['id'];
}

/*
|--------------------------------------------------------------------------
| Old Input
|--------------------------------------------------------------------------
*/

$old = $_SESSION['old_visit'] ?? [];

unset($_SESSION['old_visit']);

$errors = $_SESSION['validation_errors'] ?? [];

unset($_SESSION['validation_errors']);

/*
|--------------------------------------------------------------------------
| Layout
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../../layouts/header.php';
require_once __DIR__ . '/../../layouts/sidebar.php';

?>

<div class="main-container">

<?php require_once __DIR__ . '/../../layouts/navbar.php'; ?>

<main class="content">

<div class="page-header">

    <div>

        <h1>New Encounter</h1>

        <p>

            Register a new patient encounter.

        </p>

    </div>

</div>

<?php if (!empty($errors)) : ?>

<div class="alert-danger">

    <strong>Please correct the following:</strong>

    <ul>

        <?php foreach ($errors as $error) : ?>

        <li><?= e($error) ?></li>

        <?php endforeach; ?>

    </ul>

</div>

<?php endif; ?>

<?php if ($emergencyOnlyEncounter): ?>
<div class="alert-warning">
    <strong>Emergency registration payment pending.</strong>
    This encounter can be opened for Emergency care before Accounts clears the registration fee.
</div>
<?php endif; ?>

<?php if (is_array($activeFamilyCardCoverage)): ?>
<div class="alert-info">
    <strong>Covered by Family Card.</strong>
    This patient is covered by Family Card
    <strong><?= e((string)$activeFamilyCardCoverage['card_number']) ?></strong>
    until <strong><?= e((string)$activeFamilyCardCoverage['valid_until']) ?></strong>.
    If the Family Card expires before the patient's normal registration, normal registration coverage will continue until its own expiry.
</div>
<?php endif; ?>

<div class="card">

    <h2>Patient Information</h2>

    <div class="review-item">

        <div class="review-label">

            Hospital Number

        </div>

        <div class="review-value">

            <?= e($patient['hospital_number']) ?>

        </div>

    </div>

    <div class="review-item">

        <div class="review-label">

            Patient Name

        </div>

        <div class="review-value">

            <?= e($patient['first_name']) ?>

            <?= e($patient['last_name']) ?>

        </div>

    </div>

</div>

<form

    method="POST"

    action="review.php"

    class="card">

<?= csrfField() ?>

<input

    type="hidden"

    name="patient_id"

    value="<?= (int)$patient['id'] ?>">

<div class="form-section">

<h2>Encounter Information</h2>

<div class="form-grid">

<div class="form-group">

<label>

Visit Date

<span class="required">*</span>

</label>

<input

type="datetime-local"

name="visit_date"

required

value="<?= e(

$old['visit_date']

?? date('Y-m-d\TH:i')

) ?>">

</div>

<div class="form-group">

<label>

Visit Type

<span class="required">*</span>

</label>

<select

name="visit_type"

required>

<option value="">Select Visit Type</option>

<?php

$types = $emergencyOnlyEncounter ? ['Emergency'] : [

'Outpatient',

'Inpatient',

'Referral'

];

if (!$emergencyOnlyEncounter && $canUseEmergencyWorkflow) {
    $types[] = 'Emergency';
}

foreach ($types as $type) :

?>

<option

value="<?= $type ?>"

<?=

($old['visit_type'] ?? '') === $type

? 'selected'

: ''

?>>

<?= $type ?>

</option>

<?php endforeach; ?>

</select>

</div>

<div class="form-group">

<label>

Initial Encounter Department

<span class="required">*</span>

</label>

<select

name="current_department_id"

required>

<option value="">

Select Department

</option>

<?php foreach ($departments as $department) : ?>

<?php $selectedDepartmentId = $old['current_department_id'] ?? $defaultDepartmentId; ?>

<option

value="<?= (int)$department['id'] ?>"

<?=
($selectedDepartmentId ?? '') ==

$department['id']

? 'selected'

: ''

?>>

<?= e($department['department_name']) ?>

</option>

<?php endforeach; ?>

</select>
<small>Doctor is the normal starting point. Laboratory, Radiology/X-Ray, ECG, Plaster, Physiotherapy, Pharmacy, and Store work from requests instead of encounter ownership.</small>

</div>

</div>

</div>

<div class="form-actions">

<a

href="../patients/view.php?id=<?= (int)$patient['id'] ?>"

class="btn-secondary">

Cancel

</a>

<button

type="submit"

class="btn-primary">

Continue

</button>

</div>

</form>

</main>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
