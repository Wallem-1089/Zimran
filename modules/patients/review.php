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
| Page Configuration
|--------------------------------------------------------------------------
*/

$moduleStylesheet =
    '/modules/patients/assets/patients.css';

$moduleScript =
    '/modules/patients/assets/patients.js';

/*
|--------------------------------------------------------------------------
| Determine Action
|--------------------------------------------------------------------------
*/

$action = $_POST['action'] ?? 'create';

if (!in_array($action, ['create', 'update'], true)) {

    $action = 'create';

}

$isUpdate = ($action === 'update');

$pageTitle = $isUpdate
    ? 'Review Patient Changes'
    : 'Review Patient Registration';

/*
|--------------------------------------------------------------------------
| Allow POST Only
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header('Location: register.php');

    exit;

}

requireCsrfToken();

/*
|--------------------------------------------------------------------------
| Validate Request
|--------------------------------------------------------------------------
*/

if ($isUpdate && empty($_POST['id'])) {

    $_SESSION['error_message'] =
        'Invalid patient selected.';

    header('Location: search.php');

    exit;

}

/*
|--------------------------------------------------------------------------
| Build Patient Array
|--------------------------------------------------------------------------
*/

$fields = [

    'id',

    'demographic_version',

    'amendment_reason',

    'first_name',

    'middle_name',

    'last_name',

    'gender',

    'date_of_birth',

    'marital_status',

    'occupation',

    'phone',

    'email',

    'address',

    'state_of_origin',

    'nationality',

    'blood_group',

    'genotype',

    'allergies',

    'next_of_kin',

    'next_of_kin_relationship',

    'next_of_kin_phone'

    ,'duplicate_review_ack'

];

$patient = [];

foreach ($fields as $field) {

    $patient[$field] = trim($_POST[$field] ?? '');

}

if ($isUpdate) {
    $patientId = (int)$_POST['id'];
    $permissionService = new PermissionService($pdo);

    if (!$permissionService->canEditPatientDemographics(
        $patientId,
        $currentUser
    )) {
        $permissionService->logPatientDenied(
            isset($currentUser['id']) ? (int)$currentUser['id'] : null,
            $patientId,
            'PATIENT_CHART_ACCESS_DENIED',
            'User attempted to review a demographic amendment without permission.'
        );

        http_response_code(403);
        exit('You do not have permission to edit this patient record.');
    }
}

if (!in_array($patient['gender'], PatientService::supportedGenders(), true)) {

    $_SESSION['validation_errors'] = ['Select a valid gender.'];
    $_SESSION['old_patient'] = $_POST;

    header(
        'Location: '
        . ($isUpdate ? 'edit.php?id=' . (int)$patient['id'] : 'register.php')
    );

    exit;

}

if ($isUpdate && $patient['amendment_reason'] === '') {
    $_SESSION['validation_errors'] = [
        'A reason for the demographic change is required.'
    ];
    $_SESSION['old_patient'] = $_POST;

    header('Location: edit.php?id=' . (int)$patient['id']);
    exit;
}

$duplicateCandidates = [];
$duplicateReviewRequired = false;

if (!$isUpdate) {
    $patientService = new PatientService($pdo);
    $duplicateResult = $patientService->findPossibleDuplicates($patient);
    $duplicateCandidates = $duplicateResult['candidates'] ?? [];
    $duplicateReviewRequired = empty($patient['duplicate_review_ack'])
        && array_filter(
            $duplicateCandidates,
            static fn (array $candidate): bool => in_array(
                $candidate['classification'],
                ['Exact Match', 'Strong Possible Match'],
                true
            )
        ) !== [];
}

/*
|--------------------------------------------------------------------------
| Helper Function
|--------------------------------------------------------------------------
*/

function reviewItem(
    string $label,
    string $value
): void {

?>

<div class="review-item">

    <div class="review-label">

        <?= e($label) ?>

    </div>

    <div class="review-value">

        <?= nl2br(
            e($value !== '' ? $value : '-')
        ) ?>

    </div>

</div>

<?php

}

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

        <h1>

            <?= $isUpdate
                ? 'Review Patient Changes'
                : 'Patient Registration Review' ?>

        </h1>

        <p>

            <?= $isUpdate

                ? 'Review the updated patient information before saving the changes.'

                : 'Review the patient information before creating the patient record.'

            ?>

        </p>

    </div>

</div>

<div class="card review-card">

    <div class="review-section">

        <h2>Personal Information</h2>

        <?php

        reviewItem(
            'First Name',
            $patient['first_name']
        );

        reviewItem(
            'Middle Name',
            $patient['middle_name']
        );

        reviewItem(
            'Last Name',
            $patient['last_name']
        );

        reviewItem(
            'Gender',
            $patient['gender']
        );

        reviewItem(
            'Date of Birth',
            $patient['date_of_birth']
        );

        reviewItem(
            'Marital Status',
            $patient['marital_status']
        );

        reviewItem(
            'Occupation',
            $patient['occupation']
        );

        ?>

    </div>

    <hr>

    <div class="review-section">

        <h2>Contact Information</h2>

        <?php

        reviewItem(
            'Phone Number',
            $patient['phone']
        );

        reviewItem(
            'Email Address',
            $patient['email']
        );

        reviewItem(
            'Address',
            $patient['address']
        );

        reviewItem(
            'State of Origin',
            $patient['state_of_origin']
        );

        reviewItem(
            'Nationality',
            $patient['nationality']
        );

        ?>

    </div>

    <hr>

    <div class="review-section">

        <h2>Medical Information</h2>

        <?php

        reviewItem(
            'Blood Group',
            $patient['blood_group']
        );

        reviewItem(
            'Genotype',
            $patient['genotype']
        );

        ?>

    </div>

    <hr>

    <div class="review-section">

        <h2>Next of Kin</h2>

        <?php

        reviewItem(
            'Name',
            $patient['next_of_kin']
        );

        reviewItem(
            'Relationship',
            $patient['next_of_kin_relationship']
        );

        reviewItem(
            'Phone Number',
            $patient['next_of_kin_phone']
        );

        ?>

    </div>

    <?php if ($isUpdate): ?>

        <hr>

        <div class="review-section">

            <h2>Amendment</h2>

            <?php reviewItem('Reason for Change', $patient['amendment_reason']); ?>

        </div>

    <?php endif; ?>

</div>

<?php if (!$isUpdate && $duplicateCandidates !== []): ?>
<div class="card">
    <h2>Possible Matching Patients</h2>
    <p>Review these existing records before creating a new patient.</p>
    <?php foreach ($duplicateCandidates as $candidate): ?>
        <?php if ($candidate['classification'] === 'Low Confidence') { continue; } ?>
        <div class="review-item">
            <div class="review-label"><?= e($candidate['classification']) ?> (<?= (int)$candidate['match_score'] ?>)</div>
            <div class="review-value">
                <?= e($candidate['hospital_number']) ?> — <?= e($candidate['first_name'] . ' ' . $candidate['last_name']) ?>
                <a href="../medical_records/chart.php?patient=<?= (int)$candidate['id'] ?>">Open existing chart</a>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<form
    method="POST"
    action="<?= $isUpdate ? 'update.php' : 'save.php' ?>"
    class="confirmation-form">

    <?= csrfField() ?>

    <?php foreach ($patient as $field => $value): ?>

        <input
            type="hidden"
            name="<?= e($field) ?>"
            value="<?= e($value) ?>">

    <?php endforeach; ?>

    <input
        type="hidden"
        name="action"
        value="<?= e($action) ?>">

    <div class="form-actions">

        <button
            type="submit"
            formaction="<?= $isUpdate ? 'edit.php' : 'register.php' ?>"
            class="btn-secondary">

            ← Back & Edit

        </button>

        <button
            type="submit"
            class="btn-primary"
            <?= $duplicateReviewRequired ? 'disabled aria-disabled="true"' : '' ?>>

            <?= $isUpdate
                ? 'Save Changes'
                : 'Confirm & Register' ?>

        </button>

        <?php if ($duplicateReviewRequired): ?>
            <button type="submit" name="duplicate_review_ack" value="1" class="btn-secondary">
                I reviewed these records — continue registration
            </button>
        <?php endif; ?>

    </div>

</form>

</main>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
