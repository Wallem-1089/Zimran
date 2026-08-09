<?php

declare(strict_types=1);

$pageTitle = 'Patient Chart';
$moduleStylesheet = '/modules/medical_records/assets/medical_records.css';

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../services/MedicalRecordService.php';
require_once __DIR__ . '/../../services/PatientService.php';
require_once __DIR__ . '/../../services/PermissionService.php';
require_once __DIR__ . '/../../services/PatientIdentifierService.php';
require_once __DIR__ . '/../../services/ClinicalSafetyService.php';
require_once __DIR__ . '/../../services/ProblemListService.php';
require_once __DIR__ . '/../../services/MedicalDocumentService.php';
require_once __DIR__ . '/../../services/ClinicalNoteService.php';
require_once __DIR__ . '/../../services/VisitService.php';
require_once __DIR__ . '/../../services/VitalSignsService.php';
require_once __DIR__ . '/../../services/NursingService.php';

function chartTableExists(PDO $pdo, string $table): bool
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

$patientId = filter_input(INPUT_GET, 'patient', FILTER_VALIDATE_INT);

if (!$patientId) {
    header('Location: index.php');
    exit;
}

$patientService = new PatientService($pdo);
$permissionService = new PermissionService($pdo);
$patient = $patientService->getPatientById($patientId);

if (!$patient) {
    http_response_code(404);
    exit('Patient not found.');
}

$visitId = filter_input(INPUT_GET, 'visit', FILTER_VALIDATE_INT) ?: null;
if ($visitId !== null) {
    $contextVisit = (new VisitService($pdo))->getVisitById((int)$visitId);
    if (!$contextVisit
        || (int)$contextVisit['patient_id'] !== $patientId
        || !$permissionService->canViewEncounter($contextVisit, $currentUser)
    ) {
        $permissionService->logPatientDenied(
            (int)($currentUser['id'] ?? 0),
            $patientId,
            'CLINICAL_SAFETY_ACCESS_DENIED',
            'Invalid Patient Chart encounter context was rejected.'
        );
        http_response_code(403);
        exit('The encounter context is not available for this patient chart.');
    }
}
$chartContextQuery = $visitId === null ? '' : '&visit=' . (int)$visitId;

if (!$permissionService->canViewMedicalRecord($patientId, $currentUser)) {
    $permissionService->logPatientDenied(
        isset($currentUser['id']) ? (int)$currentUser['id'] : null,
        $patientId,
        'PATIENT_CHART_ACCESS_DENIED',
        'User attempted to view a patient chart without authorization.'
    );

    http_response_code(403);
    exit('You do not have permission to view this patient chart.');
}

$allowedTabs = [
    'overview',
    'demographics',
    'identifiers',
    'safety',
    'vitals',
    'problems',
    'medical_history',
    'documents',
    'notes',
    'nursing',
    'encounters',
    'history',
    'audit'
];
$activeTab = (string)($_GET['tab'] ?? 'overview');

if (!in_array($activeTab, $allowedTabs, true)) {
    $activeTab = 'overview';
}

$canEditDemographics = $permissionService->canEditPatientDemographics(
    $patientId,
    $currentUser
);
$canViewAudit = $permissionService->canViewPatientAuditHistory(
    $patientId,
    $currentUser
);
$canViewIdentifiers = $permissionService->canViewPatientIdentifiers(
    $patientId,
    $currentUser
);
$canManageIdentifiers = $permissionService->canManagePatientIdentifiers(
    $patientId,
    null,
    $currentUser
);
$canViewClinicalSafety = $permissionService->canViewClinicalSafety(
    $patientId,
    $currentUser
);
$canViewConfidentialAlerts = $permissionService->canViewConfidentialAlerts(
    $patientId,
    $currentUser
);
$identifiers = [];
$duplicateWarning = null;
$safetyBanner = ['success' => true, 'data' => ['items' => []], 'errors' => []];
$allergies = [];
$alerts = [];
$problems = [];
$medicalHistory = [];
$canViewProblemList = $permissionService->canViewProblemList($patientId, $currentUser);
$canManageProblemList = $permissionService->canManageProblemList($patientId, $currentUser);
$canViewMedicalHistory = $permissionService->canViewStructuredMedicalHistory($patientId, $currentUser);
$canManageMedicalHistory = $permissionService->canManageStructuredMedicalHistory($patientId, $currentUser);
$canViewMedicalDocuments = $permissionService->canViewMedicalDocuments($patientId, $currentUser);
$canUploadMedicalDocuments = $permissionService->canUploadMedicalDocuments($patientId, null, $currentUser);
$medicalDocuments = [];
$canViewClinicalNotes = $permissionService->canViewClinicalNotes($patientId, $currentUser);
$canCreatePatientNotes = $permissionService->canCreateClinicalNote($patientId, false, null, $currentUser);
$clinicalNotes = ['success' => true, 'data' => ['records' => [], 'total_results' => 0], 'errors' => []];
$clinicalNoteFilterOptions = ['authors' => [], 'departments' => []];
$vitalSignsTablesReady = chartTableExists($pdo, 'vital_signs');
$canViewVitalSigns = $vitalSignsTablesReady && $permissionService->canViewVitalSigns($patientId, $currentUser);
$vitalSignsService = $vitalSignsTablesReady ? new VitalSignsService($pdo, null, $permissionService) : null;
$vitalSignsHistory = [];
$latestVitalSigns = null;
$nursingTablesReady = chartTableExists($pdo, 'nursing_assessments');
$canViewNursing = $nursingTablesReady && $permissionService->canViewNursing($patientId, $currentUser);
$nursingService = $nursingTablesReady ? new NursingService($pdo, null, null, $permissionService) : null;
$nursingHistory = [];
$latestNursingAssessment = null;

if ($activeTab === 'problems' && !$canViewProblemList) {
    $permissionService->logPatientDenied((int)$currentUser['id'], $patientId, 'PROBLEM_LIST_ACCESS_DENIED', 'Problem List access denied.');
    http_response_code(403);
    exit('You do not have permission to view the Problem List.');
}
if ($activeTab === 'medical_history' && !$canViewMedicalHistory) {
    $permissionService->logPatientDenied((int)$currentUser['id'], $patientId, 'MEDICAL_HISTORY_ACCESS_DENIED', 'Medical history access denied.');
    http_response_code(403);
    exit('You do not have permission to view medical history.');
}
if ($activeTab === 'documents' && !$canViewMedicalDocuments) {
    $permissionService->logPatientDenied((int)$currentUser['id'], $patientId, 'DOCUMENT_ACCESS_DENIED', 'Medical Document list access denied.');
    http_response_code(403);
    exit('You do not have permission to view Medical Documents.');
}
if ($activeTab === 'notes' && !$canViewClinicalNotes) {
    $permissionService->logPatientDenied((int)$currentUser['id'], $patientId, 'CLINICAL_NOTE_ACCESS_DENIED', 'Clinical Note list access denied.');
    http_response_code(403);
    exit('You do not have permission to view Clinical Notes.');
}
if ($activeTab === 'vitals' && !$canViewVitalSigns) {
    $permissionService->logPatientDenied((int)$currentUser['id'], $patientId, 'VITAL_SIGNS_ACCESS_DENIED', 'Vital Signs access denied.');
    http_response_code(403);
    exit('You do not have permission to view Vital Signs.');
}
if ($activeTab === 'nursing' && !$canViewNursing) {
    $permissionService->logPatientDenied((int)$currentUser['id'], $patientId, 'NURSING_ACCESS_DENIED', 'Nursing access denied.');
    http_response_code(403);
    exit('You do not have permission to view Nursing assessments.');
}
$problemListService = new ProblemListService($pdo);
if ($canViewProblemList) {
    $problems = $problemListService->getPatientProblemsForUser($patientId, $currentUser, true);
}
if ($canViewMedicalHistory) {
    $medicalHistory = $problemListService->getPatientMedicalHistoryForUser($patientId, $currentUser, false);
}
$medicalDocumentService = new MedicalDocumentService($pdo);
if ($canViewMedicalDocuments) {
    $medicalDocuments = $medicalDocumentService->listPatientDocuments($patientId, $currentUser, true);
}
$clinicalNoteService = new ClinicalNoteService($pdo);
if ($canViewClinicalNotes) {
    $clinicalNotes = $clinicalNoteService->listPatientNotes(
        $patientId,
        $currentUser,
        [
            'type' => $_GET['note_type'] ?? '',
            'status' => $_GET['note_status'] ?? '',
            'q' => $_GET['note_q'] ?? '',
            'author_id' => $_GET['note_author_id'] ?? '',
            'department_id' => $_GET['note_department_id'] ?? '',
            'date_from' => $_GET['note_date_from'] ?? '',
            'date_to' => $_GET['note_date_to'] ?? ''
        ],
        max(1, (int)($_GET['page'] ?? 1)),
        25
    );
    $noteOptionsResult = $clinicalNoteService->getNoteFilterOptions($patientId, $currentUser);
    $clinicalNoteFilterOptions = $noteOptionsResult['data'] ?? $clinicalNoteFilterOptions;
}

if ($canViewVitalSigns) {
    $vitalSignsHistory = $vitalSignsService->listByPatient($patientId, $currentUser);
    $latestVitalSigns = $vitalSignsHistory[0] ?? null;
}

if ($canViewNursing) {
    $nursingHistory = $nursingService->listByPatient($patientId, $currentUser);
    $latestNursingAssessment = $nursingHistory[0] ?? null;
}

if ($activeTab === 'identifiers' && !$canViewIdentifiers) {
    http_response_code(403);
    exit('You do not have permission to view patient identifiers.');
}

if ($canViewIdentifiers) {
    $identifierService = new PatientIdentifierService($pdo);
    $identifiers = $identifierService->getPatientIdentifiers($patientId, true);
    $duplicateWarning = $patientService->getUnresolvedDuplicateWarning($patientId);
}

if ($activeTab === 'audit' && !$canViewAudit) {
    $permissionService->logPatientDenied(
        (int)$currentUser['id'],
        $patientId,
        'PATIENT_CHART_ACCESS_DENIED',
        'User attempted to view patient audit history without permission.'
    );

    http_response_code(403);
    exit('You do not have permission to view patient audit history.');
}

if ($activeTab === 'safety' && !$canViewClinicalSafety) {
    $permissionService->logPatientDenied(
        (int)($currentUser['id'] ?? 0),
        $patientId,
        'CLINICAL_SAFETY_ACCESS_DENIED',
        'User attempted to view clinical safety information without authorization.'
    );

    http_response_code(403);
    exit('You do not have permission to view clinical safety information.');
}

if ($canViewClinicalSafety) {
    $clinicalSafetyService = new ClinicalSafetyService($pdo);
    $safetyBanner = $clinicalSafetyService->getSafetyBannerForUser(
        $patientId,
        $currentUser,
        $visitId
    );
    if (!($safetyBanner['success'] ?? false)) {
        http_response_code(503);
        exit('The patient chart cannot display protected Clinical Safety information because access could not be recorded.');
    }

    if ($activeTab === 'safety') {
        $safetyAllergies = $clinicalSafetyService->getPatientAllergies($patientId, true);
        $safetyAlerts = $clinicalSafetyService->getPatientAlertsForUser(
            $patientId,
            $currentUser,
            true
        );
    }
}

$medicalRecordService = new MedicalRecordService($pdo);
$chartResult = $medicalRecordService->getPatientChart(
    $patientId,
    $currentUser
);

if (!($chartResult['success'] ?? false)) {
    http_response_code(500);
    exit('Unable to load the patient chart.');
}

$chart = $chartResult['data'];
$patient = $chart['patient'];
$summary = $chart['summary'];
$encounters = $chart['encounters'];
$demographicHistory = $chart['demographic_history'];
$auditHistory = [];

if ($activeTab === 'audit' && $canViewAudit) {
    $auditResult = $medicalRecordService->getPatientAuditHistory(
        $patientId,
        max(1, (int)($_GET['page'] ?? 1)),
        25
    );
    $auditHistory = $auditResult['data'] ?? [];
}

require_once __DIR__ . '/../../layouts/header.php';
require_once __DIR__ . '/../../layouts/sidebar.php';

?>

<div class="main-container">

<?php require_once __DIR__ . '/../../layouts/navbar.php'; ?>

<main class="content">

    <?php require __DIR__ . '/partials/chart_header.php'; ?>

    <?php if ($canViewClinicalSafety): ?>
        <?php $safetyBannerUrl = 'chart.php?patient=' . $patientId . '&tab=safety' . $chartContextQuery; ?>
        <?php require __DIR__ . '/partials/clinical_safety_banner.php'; ?>
    <?php endif; ?>

    <?php require __DIR__ . '/partials/chart_navigation.php'; ?>

    <?php

    switch ($activeTab) {
        case 'demographics':
            require __DIR__ . '/partials/demographics.php';
            break;

        case 'encounters':
            require __DIR__ . '/partials/encounters.php';
            break;

        case 'identifiers':
            require __DIR__ . '/partials/identifiers.php';
            break;

        case 'safety':
            require __DIR__ . '/partials/clinical_safety.php';
            break;

        case 'vitals':
            require __DIR__ . '/partials/vital_signs.php';
            break;

        case 'nursing':
            require __DIR__ . '/partials/nursing.php';
            break;

        case 'problems':
            require __DIR__ . '/partials/problems.php';
            break;

        case 'medical_history':
            require __DIR__ . '/partials/medical_history.php';
            break;

        case 'documents':
            require __DIR__ . '/partials/documents.php';
            break;

        case 'notes':
            require __DIR__ . '/partials/clinical_notes.php';
            break;

        case 'history':
            require __DIR__ . '/partials/demographic_history.php';
            break;

        case 'audit':
            require __DIR__ . '/partials/audit_history.php';
            break;

        case 'overview':
        default:
            require __DIR__ . '/partials/overview.php';
            break;
    }

    ?>

</main>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>

</div>
