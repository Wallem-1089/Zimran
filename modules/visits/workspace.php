<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Page Configuration
|--------------------------------------------------------------------------
*/

$pageTitle = 'Encounter Workspace';

$moduleStylesheet =
    '/modules/visits/assets/visits.css';

$moduleScript =
    '/modules/visits/assets/visits.js';

/*
|--------------------------------------------------------------------------
| Bootstrap
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

require_once __DIR__ . '/../../services/VisitService.php';
require_once __DIR__ . '/../../services/PatientService.php';
require_once __DIR__ . '/../../services/PermissionService.php';
require_once __DIR__ . '/../../services/ClinicalSafetyService.php';
require_once __DIR__ . '/../../services/ProblemListService.php';
require_once __DIR__ . '/../../services/MedicalDocumentService.php';
require_once __DIR__ . '/../../services/ClinicalNoteService.php';
require_once __DIR__ . '/../../services/ConsultationService.php';
require_once __DIR__ . '/../../services/DepartmentNotificationService.php';
require_once __DIR__ . '/../../services/VitalSignsService.php';

function workspaceTableExists(PDO $pdo, string $table): bool
{
    try {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = :table'
        );
        $stmt->execute([':table' => $table]);
        return (int)$stmt->fetchColumn() > 0;
    } catch (Throwable) {
        return false;
    }
}

/*
|--------------------------------------------------------------------------
| Visit ID
|--------------------------------------------------------------------------
*/

$visitId = filter_input(

    INPUT_GET,

    'id',

    FILTER_VALIDATE_INT

);

if (!$visitId) {

    header('Location: ../patients/search.php');

    exit;

}

/*
|--------------------------------------------------------------------------
| Active Tab
|--------------------------------------------------------------------------
*/

$activeTab = $_GET['tab'] ?? 'overview';

/*
|--------------------------------------------------------------------------
| Services
|--------------------------------------------------------------------------
*/

$visitService = new VisitService($pdo);

$patientService = new PatientService($pdo);

/*
|--------------------------------------------------------------------------
| Load Encounter
|--------------------------------------------------------------------------
*/

$visit = $visitService->getVisitById($visitId);

if (!$visit) {

    http_response_code(404);

    exit('Encounter not found.');

}

$permissionService = new PermissionService($pdo);

if (!$permissionService->canViewEncounter($visit, $currentUser)) {

    $permissionService->logDenied(
        isset($currentUser['id']) ? (int)$currentUser['id'] : null,
        $visitId,
        'WORKSPACE_ACCESS_DENIED',
        'User attempted to access an encounter workspace outside their department.'
    );

    http_response_code(403);

    require_once __DIR__ . '/../../layouts/header.php';

    ?>

    <main class="content">

        <div class="card alert-danger">

            <h1>Access Denied</h1>

            <p>

                You do not have permission to access this encounter workspace.

            </p>

            <p>

                Please return to your department encounter list.

            </p>

        </div>

    </main>

    <?php

    require_once __DIR__ . '/../../layouts/footer.php';

    exit;

}

$canViewPatientChart = $permissionService->canViewMedicalRecord(
    (int)$visit['patient_id'],
    $currentUser
);
$canChangeEncounterStatus = $permissionService->canChangeEncounterStatus(
    $visit,
    $currentUser
);
$canViewClinicalSafety = $permissionService->canViewClinicalSafety(
    (int)$visit['patient_id'],
    $currentUser
);
$safetyBanner = ['success' => true, 'data' => ['items' => []], 'errors' => []];

if ($canViewClinicalSafety) {
    $clinicalSafetyService = new ClinicalSafetyService($pdo);
    $safetyBanner = $clinicalSafetyService->getSafetyBannerForUser(
        (int)$visit['patient_id'],
        $currentUser,
        $visitId
    );
    if (!($safetyBanner['success'] ?? false)) {
        http_response_code(503);
        exit('The encounter cannot display protected Clinical Safety information because access could not be recorded.');
    }
}

$errorMessage = $_SESSION['error_message'] ?? null;

unset($_SESSION['error_message']);

$successMessage = $_SESSION['success_message'] ?? null;

unset($_SESSION['success_message']);

/*
|--------------------------------------------------------------------------
| Department Access
|--------------------------------------------------------------------------
|
| Enterprise Workflow
|
| Newly-created encounters are automatically received by the
| creating department.
|
| Only transferred encounters that have NOT yet been received
| should be blocked.
|
*/

$hasPendingTransfer = $visitService->hasPendingTransfer($visitId);

$canAccessDepartment = !$hasPendingTransfer;

/*
|--------------------------------------------------------------------------
| Load Patient
|--------------------------------------------------------------------------
*/

$patient = $patientService->getPatientById(

    (int)$visit['patient_id']

);

if (!$patient) {

    http_response_code(404);

    exit('Patient not found.');

}

$problemListService = new ProblemListService($pdo);
$canViewProblemList = $permissionService->canViewProblemList(
    (int)$patient['id'],
    $currentUser
);
$canViewMedicalHistory = $permissionService->canViewStructuredMedicalHistory(
    (int)$patient['id'],
    $currentUser
);
$workspaceProblemSummary = $canViewProblemList
    ? $problemListService->getProblemSummary((int)$patient['id'], $currentUser)
    : ['success' => true, 'data' => ['active_confirmed' => [], 'severe_active_confirmed' => []], 'errors' => []];
$workspaceMedicalHistorySummary = $canViewMedicalHistory
    ? $problemListService->getMedicalHistorySummary((int)$patient['id'], $currentUser, 6)
    : ['success' => true, 'data' => ['entries' => []], 'errors' => []];

/*
|--------------------------------------------------------------------------
| Future Workspace Data
|--------------------------------------------------------------------------
*/

$consultationTablesReady = workspaceTableExists($pdo, 'consultations');
$notificationTablesReady = workspaceTableExists($pdo, 'department_notifications');
$vitalSignsTablesReady = workspaceTableExists($pdo, 'vital_signs');
$consultationService = $consultationTablesReady ? new ConsultationService($pdo) : null;
$consultation = $consultationService ? $consultationService->getByVisit($visitId) : null;
$canViewConsultation = $permissionService->canViewConsultation($visit, $currentUser);
$canCreateConsultation = $permissionService->canCreateConsultation($visit, $currentUser);
$canEditConsultation = $permissionService->canEditConsultation($visit, $currentUser);
$canCompleteConsultation = $permissionService->canCompleteConsultation($visit, $currentUser);
$vitalSignsService = $vitalSignsTablesReady ? new VitalSignsService($pdo, null, $permissionService) : null;
$vitalSignsHistory = $vitalSignsService ? $vitalSignsService->listByVisit($visitId, $currentUser) : [];
$latestVitalSigns = $vitalSignsHistory[0] ?? null;
$canViewVitalSigns = $permissionService->canViewVitalSigns((int)$visit['patient_id'], $currentUser);
$canCreateVitalSigns = $permissionService->canCreateVitalSigns($visit, $currentUser);
$canEditVitalSigns = $permissionService->canEditVitalSigns($visit, $currentUser);
$departments = $visitService->getDepartments();
$departmentNotificationService = $notificationTablesReady ? new DepartmentNotificationService($pdo) : null;
$visitNotifications = $departmentNotificationService ? $departmentNotificationService->listForVisit($visitId) : [];

$nursing = null;

$laboratory = [];

$radiology = [];

$pharmacy = [];

$billing = [];

$physiotherapy = [];

$theatre = [];

$medicalDocumentService = new MedicalDocumentService($pdo);
$canViewMedicalDocuments = $permissionService->canViewMedicalDocuments((int)$patient['id'], $currentUser);
$canUploadMedicalDocuments = $permissionService->canUploadMedicalDocuments((int)$patient['id'], null, $currentUser)
    && $medicalDocumentService->canAcceptEncounterUpload($visit);
$documents = $canViewMedicalDocuments
    ? $medicalDocumentService->listEncounterDocuments($visitId, $currentUser)
    : [];

$clinicalNoteService = new ClinicalNoteService($pdo);
$canViewClinicalNotes = $permissionService->canViewClinicalNotes((int)$patient['id'], $currentUser);
$canCreateEncounterNotes = $permissionService->canCreateClinicalNote((int)$patient['id'], true, null, $currentUser);
$workspaceNotesResult = $canViewClinicalNotes
    ? $clinicalNoteService->listEncounterNotes($visitId, $currentUser, [], 1, 25)
    : ['success' => true, 'data' => ['records' => [], 'total_results' => 0], 'errors' => []];
$notes = $workspaceNotesResult['data']['records'] ?? [];

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

    <?php if ($errorMessage !== null): ?>

        <div class="alert-danger">

            <?= nl2br(e((string)$errorMessage)) ?>

        </div>

    <?php endif; ?>

    <?php if ($successMessage !== null): ?>

        <div class="alert-success">

            <?= nl2br(e((string)$successMessage)) ?>

        </div>

    <?php endif; ?>

    <?php require __DIR__ . '/partials/encounter_header.php'; ?>

    <?php if ($canViewClinicalSafety): ?>
        <?php $safetyBannerUrl = '../medical_records/chart.php?patient=' . (int)$visit['patient_id'] . '&tab=safety&visit=' . $visitId; ?>
        <?php require __DIR__ . '/../medical_records/partials/clinical_safety_banner.php'; ?>
    <?php endif; ?>

    <?php if ($canViewProblemList || $canViewMedicalHistory): ?>
        <?php require __DIR__ . '/partials/longitudinal_summary.php'; ?>
    <?php endif; ?>

    <?php require __DIR__ . '/partials/quick_actions.php'; ?>

    <?php require __DIR__ . '/partials/queue_status.php'; ?>

    <?php require __DIR__ . '/partials/encounter_summary.php'; ?>

    <?php require __DIR__ . '/partials/encounter_status.php'; ?>

    <?php require __DIR__ . '/partials/timeline.php'; ?>

    <?php if (!$canAccessDepartment) : ?>

        <div class="card receive-card">

            <h2>

                Patient Awaiting Department Reception

            </h2>

            <p>

                This patient has been transferred to

                <strong>

                    <?= e($visit['department_name']) ?>

                </strong>

                but has not yet been officially received.

            </p>

            <p>

                Department activities remain locked until the
                receiving department confirms receipt.

            </p>

            <a

                href="receive.php?visit=<?= (int)$visit['id'] ?>"

                class="btn-primary">

                Receive Patient

            </a>

        </div>

    <?php else : ?>

        <?php require __DIR__ . '/partials/workspace_navigation.php'; ?>

        <?php

        switch ($activeTab) {

            case 'consultation':

                require __DIR__ . '/partials/tabs/consultation.php';

                break;

            case 'vitals':

                require __DIR__ . '/partials/tabs/vitals.php';

                break;

            case 'nursing':

                require __DIR__ . '/partials/tabs/nursing.php';

                break;

            case 'laboratory':

                require __DIR__ . '/partials/tabs/laboratory.php';

                break;

            case 'radiology':

                require __DIR__ . '/partials/tabs/radiology.php';

                break;

            case 'pharmacy':

                require __DIR__ . '/partials/tabs/pharmacy.php';

                break;

            case 'billing':

                require __DIR__ . '/partials/tabs/billing.php';

                break;

            case 'physiotherapy':

                require __DIR__ . '/partials/tabs/physiotherapy.php';

                break;

            case 'theatre':

                require __DIR__ . '/partials/tabs/theatre.php';

                break;

            case 'documents':

                require __DIR__ . '/partials/tabs/documents.php';

                break;

            case 'notes':

                require __DIR__ . '/partials/tabs/notes.php';

                break;

            case 'overview':

            default:

                require __DIR__ . '/partials/tabs/overview.php';

                break;

        }

        ?>

    <?php endif; ?>

</main>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>

</div>
