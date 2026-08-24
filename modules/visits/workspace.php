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
require_once __DIR__ . '/../../services/UserNotificationService.php';
require_once __DIR__ . '/../../services/LaboratoryService.php';
require_once __DIR__ . '/../../services/RadiologyService.php';
require_once __DIR__ . '/../../services/PhysiotherapyService.php';
require_once __DIR__ . '/../../services/TheatreService.php';
require_once __DIR__ . '/../../services/PharmacyService.php';
require_once __DIR__ . '/../../services/BillingService.php';
require_once __DIR__ . '/../../services/StoreService.php';
require_once __DIR__ . '/../../services/VitalSignsService.php';
require_once __DIR__ . '/../../services/NursingService.php';
require_once __DIR__ . '/../../services/DressingRecordService.php';
require_once __DIR__ . '/../../services/AdmissionService.php';

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
        'User attempted to access an encounter workspace without authentication or view permission.'
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

$workspaceUserRole = (string)($currentUser['role_name'] ?? '');
$workspaceUserDepartment = (string)(
    $currentUser['active_department_name']
    ?? $_SESSION['active_department_name']
    ?? $currentUser['department_name']
    ?? ''
);
$canOpenLaboratoryWorklist = $permissionService->canViewLaboratoryWorklist($currentUser);
$canOpenRadiologyWorklist = $permissionService->canViewRadiologyWorklist($currentUser);
$canOpenPharmacyWorklist = $permissionService->canViewPharmacyWorklist($currentUser);
$canOpenPhysiotherapyWorklist = $permissionService->canViewPhysiotherapyWorklist($currentUser);
$canOpenAdmissionCensus = $permissionService->isAdministrator($currentUser)
    || in_array($workspaceUserRole, ['Receptionist', 'Records Officer', 'Nurse'], true)
    || in_array($workspaceUserDepartment, ['Reception', 'Records', 'Nursing'], true);

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

$theatreAccessDenied = false;
if ($theatreAccessDenied) {
    http_response_code(403);
    exit('You do not have permission to view Theatre.');
}

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
$userNotificationTablesReady = workspaceTableExists($pdo, 'user_notifications');
$vitalSignsTablesReady = workspaceTableExists($pdo, 'vital_signs');
$nursingTablesReady = workspaceTableExists($pdo, 'nursing_assessments');
$dressingTablesReady = workspaceTableExists($pdo, 'dressing_records');
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
$nursingService = $nursingTablesReady ? new NursingService($pdo, null, null, $permissionService) : null;
$nursingHistory = $nursingService ? $nursingService->listByVisit($visitId, $currentUser) : [];
$nursing = $nursingHistory[0] ?? null;
$dressingRecordService = $dressingTablesReady ? new DressingRecordService($pdo, null, $permissionService) : null;
$dressingRecords = $dressingRecordService ? $dressingRecordService->listByVisit($visitId, $currentUser) : [];
$latestDressingRecord = $dressingRecords[0] ?? null;
$canViewNursing = $permissionService->canViewNursing((int)$visit['patient_id'], $currentUser);
$canCreateNursing = $permissionService->canCreateNursing($visit, $currentUser);
$canEditNursing = $permissionService->canEditNursing($visit, $currentUser);
$canCompleteNursing = $permissionService->canCompleteNursing($visit, $currentUser);
$physiotherapyTablesReady = workspaceTableExists($pdo, 'physiotherapy_records')
    && workspaceTableExists($pdo, 'physiotherapy_sessions');
$physiotherapyService = $physiotherapyTablesReady ? new PhysiotherapyService($pdo, null, null, $permissionService) : null;
$physiotherapyHistory = $physiotherapyService ? $physiotherapyService->listByVisit($visitId, $currentUser) : [];
$latestPhysiotherapyRecord = $physiotherapyHistory[0] ?? null;
$latestPhysiotherapySession = $latestPhysiotherapyRecord
    ? $physiotherapyService->getResult((int)$latestPhysiotherapyRecord['id'], $currentUser)
    : null;
$canViewPhysiotherapy = $permissionService->canViewPhysiotherapy((int)$visit['patient_id'], $currentUser);
$physiotherapyRequestSource = in_array((string)($visit['department_name'] ?? ''), ['Physiotherapy', 'Physio', 'Rehabilitation'], true)
    ? 'Direct'
    : 'Clinical';
$canCreatePhysiotherapyRequest = $permissionService->canCreatePhysiotherapyRequest($visit, $currentUser, $physiotherapyRequestSource);
$canProcessPhysiotherapyRequest = $permissionService->canProcessPhysiotherapyRequest($visit, $currentUser);
$canEnterPhysiotherapyReport = $permissionService->canEnterPhysiotherapyResult($visit, $currentUser);
$canEditPhysiotherapyReport = $permissionService->canEditPhysiotherapyResult($visit, $currentUser);
$canCompletePhysiotherapyRequest = $permissionService->canCompletePhysiotherapyRequest($visit, $currentUser);
$theatreTablesReady = workspaceTableExists($pdo, 'theatre_records');
$theatreService = $theatreTablesReady ? new TheatreService($pdo, null, null, $permissionService) : null;
$theatreHistory = $theatreService ? $theatreService->listByVisit($visitId, $currentUser) : [];
$latestTheatreRecord = $theatreHistory[0] ?? null;
$canViewTheatre = $permissionService->canViewTheatre($visit, $currentUser);
$canCreateTheatre = $permissionService->canCreateTheatre($visit, $currentUser);
$canEditTheatre = $permissionService->canEditTheatre($visit, $currentUser);
$canCompleteTheatre = $permissionService->canCompleteTheatre($visit, $currentUser);
$theatreAccessDenied = false;
if ($activeTab === 'theatre' && !$canViewTheatre) {
    $permissionService->logDenied(
        (int)($currentUser['id'] ?? 0),
        $visitId,
        'THEATRE_ACCESS_DENIED',
        'Theatre access denied.'
    );
    $theatreAccessDenied = true;
}
$departments = $visitService->getDepartments();
$departmentNotificationService = $notificationTablesReady ? new DepartmentNotificationService($pdo) : null;
$visitNotifications = $departmentNotificationService ? $departmentNotificationService->listForVisit($visitId) : [];
$userNotificationService = $userNotificationTablesReady ? new UserNotificationService($pdo) : null;
$notificationUsers = $userNotificationService ? $userNotificationService->listActiveUsers() : [];

$laboratoryTablesReady = workspaceTableExists($pdo, 'laboratory_requests')
    && workspaceTableExists($pdo, 'laboratory_results');
$laboratoryService = $laboratoryTablesReady ? new LaboratoryService($pdo, null, null, $permissionService) : null;
$laboratoryRequests = $laboratoryService ? $laboratoryService->listByVisit($visitId, $currentUser) : [];
$latestLaboratoryRequest = $laboratoryRequests[0] ?? null;
$latestLaboratoryResult = $latestLaboratoryRequest
    ? $laboratoryService->getResult((int)$latestLaboratoryRequest['id'], $currentUser)
    : null;
$canViewLaboratory = $permissionService->canViewLaboratory((int)$visit['patient_id'], $currentUser);
$canCreateLaboratoryRequest = $permissionService->canCreateLaboratoryRequest($visit, $currentUser, ($visit['department_name'] ?? '') === 'Laboratory' ? 'Direct' : 'Clinical');
$canProcessLaboratoryRequest = $permissionService->canProcessLaboratoryRequest($visit, $currentUser);
$canEnterLaboratoryResult = $permissionService->canEnterLaboratoryResult($visit, $currentUser);
$canEditLaboratoryResult = $permissionService->canEditLaboratoryResult($visit, $currentUser);
$canCompleteLaboratoryRequest = $permissionService->canCompleteLaboratoryRequest($visit, $currentUser);
$laboratoryRequestSource = ($visit['department_name'] ?? '') === 'Laboratory' ? 'Direct' : 'Clinical';
$radiologyTablesReady = workspaceTableExists($pdo, 'radiology_requests')
    && workspaceTableExists($pdo, 'radiology_reports');
$radiologyService = $radiologyTablesReady ? new RadiologyService($pdo, null, null, $permissionService) : null;
$radiologyRequestSource = in_array((string)($visit['department_name'] ?? ''), ['Radiology', 'X-Ray'], true)
    ? 'Direct'
    : 'Clinical';
$radiologyRequests = $radiologyService ? $radiologyService->listByVisit($visitId, $currentUser) : [];
$latestRadiologyRequest = $radiologyRequests[0] ?? null;
$latestRadiologyResult = $latestRadiologyRequest
    ? $radiologyService->getResult((int)$latestRadiologyRequest['id'], $currentUser)
    : null;
$canViewRadiology = $permissionService->canViewRadiology((int)$visit['patient_id'], $currentUser);
$canCreateRadiologyRequest = $permissionService->canCreateRadiologyRequest($visit, $currentUser, $radiologyRequestSource);
$canProcessRadiologyRequest = $permissionService->canProcessRadiologyRequest($visit, $currentUser);
$canEnterRadiologyReport = $permissionService->canEnterRadiologyReport($visit, $currentUser);
$canEditRadiologyReport = $permissionService->canEditRadiologyReport($visit, $currentUser);
$canCompleteRadiologyRequest = $permissionService->canCompleteRadiologyRequest($visit, $currentUser);
$radiology = [];
$pharmacyRequestSource = in_array((string)($visit['department_name'] ?? ''), ['Pharmacy'], true)
    ? 'Direct'
    : 'Clinical';
$pharmacyClinicalSafetyService = isset($clinicalSafetyService)
    ? $clinicalSafetyService
    : new ClinicalSafetyService($pdo);
$pharmacyTablesReady = workspaceTableExists($pdo, 'prescriptions')
    && workspaceTableExists($pdo, 'pharmacy_dispensing');
$pharmacyService = $pharmacyTablesReady
    ? new PharmacyService(
        $pdo,
        new StoreService($pdo, null, $permissionService),
        $pharmacyClinicalSafetyService,
        null,
        null,
        $permissionService,
        $visitService
    )
    : null;
$pharmacy = $pharmacyService ? $pharmacyService->listByVisit($visitId, $currentUser) : [];
$latestPharmacyPrescription = $pharmacy[0] ?? null;
$canViewPharmacy = $permissionService->canViewPharmacy((int)$visit['patient_id'], $currentUser);
$canCreatePrescription = $permissionService->canCreatePrescription($visit, $currentUser, $pharmacyRequestSource);
$canDispensePrescription = $permissionService->canDispensePrescription($visit, $currentUser);
$canViewBilling = $permissionService->canViewBilling($currentUser);
$canCreatePatientCharge = $permissionService->canCreatePatientCharge($currentUser);
$canCancelPatientCharge = $permissionService->canCancelPatientCharge($currentUser);
$canCreateBillingRequest = $permissionService->canCreateBillingRequest($currentUser);
$canViewBillingRequests = $permissionService->canViewBillingRequests($currentUser);
$canReviewBillingRequest = $permissionService->canReviewBillingRequest($currentUser);
$canCancelBillingRequest = $permissionService->canCancelBillingRequest($currentUser);
$canCreateInvoice = $permissionService->canCreateInvoice($currentUser);
$canRecordPayment = $permissionService->canRecordPayment($currentUser);
$canViewReceipts = $permissionService->canViewReceipts($currentUser);
$billingTablesReady = workspaceTableExists($pdo, 'patient_charges')
    && workspaceTableExists($pdo, 'invoices')
    && workspaceTableExists($pdo, 'payments');
$billingRequestsReady = workspaceTableExists($pdo, 'billing_requests');
$billingService = $billingTablesReady ? new BillingService($pdo) : null;
$billingSummary = $canViewBilling && $billingService
    ? $billingService->getEncounterBalance($visitId, $currentUser)
    : ['success' => true, 'invoice' => null, 'total_charges' => 0, 'amount_paid' => 0, 'balance_due' => 0, 'status' => 'Unbilled', 'errors' => []];
$billingCharges = $canViewBilling && $billingService ? $billingService->listChargesByVisit($visitId, $currentUser) : [];
$billingPayments = $canViewBilling && $billingService ? $billingService->listPayments($visitId, $currentUser) : [];
$billingInvoice = $billingSummary['invoice'] ?? null;
$billingRequests = $billingRequestsReady && $billingService
    ? $billingService->listBillingRequests(['visit_id' => $visitId], $currentUser)
    : [];
$admissionTablesReady = workspaceTableExists($pdo, 'wards')
    && workspaceTableExists($pdo, 'ward_beds')
    && workspaceTableExists($pdo, 'admissions')
    && workspaceTableExists($pdo, 'admission_movements');
$admissionService = $admissionTablesReady ? new AdmissionService($pdo, null, null, $permissionService) : null;
$canViewAdmissions = $permissionService->canViewAdmissions($currentUser);
$canCreateAdmission = $permissionService->canCreateAdmission($visit, $currentUser);
$canTransferAdmission = $permissionService->canTransferAdmission($visit, $currentUser);
$canDischargeAdmission = $permissionService->canDischargeAdmission($visit, $currentUser);
$admission = $canViewAdmissions && $admissionService ? $admissionService->getByVisit($visitId, $currentUser) : null;
$physiotherapy = $latestPhysiotherapyRecord ? [$latestPhysiotherapyRecord] : [];
$theatre = $latestTheatreRecord ? [$latestTheatreRecord] : [];

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

$workspaceTabTitles = [
    'overview' => 'Encounter Overview',
    'consultation' => 'Encounter Consultation',
    'vitals' => 'Encounter Vital Signs',
    'nursing' => 'Encounter Nurse',
    'laboratory' => 'Encounter Laboratory',
    'radiology' => 'Encounter X-Ray',
    'pharmacy' => 'Encounter Pharmacy',
    'billing' => 'Encounter Billing',
    'admission' => 'Encounter Admission',
    'physiotherapy' => 'Encounter Physiotherapy',
    'theatre' => 'Encounter Theatre',
    'documents' => 'Encounter Documents',
    'notes' => 'Encounter Notes',
];
$pageTitle = $workspaceTabTitles[(string)$activeTab] ?? 'Encounter Workspace';

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

    <?php if ($latestLaboratoryResult !== null && trim((string)($latestLaboratoryResult['result'] ?? '')) !== ''): ?>
        <div class="card">
            <h3>Latest Laboratory Result</h3>
            <div class="summary-grid">
                <div class="summary-item"><span class="summary-label">Sample Taken</span> <span class="summary-value"><?= e((string)($latestLaboratoryResult['sample_taken'] ?? '-')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Findings</span> <span class="summary-value"><?= e((string)($latestLaboratoryResult['findings'] ?? '-')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Result</span> <span class="summary-value"><?= e((string)$latestLaboratoryResult['result']) ?></span></div>
                <div class="summary-item"><span class="summary-label">Interpretation</span> <span class="summary-value"><?= e((string)($latestLaboratoryResult['interpretation'] ?? '-')) ?></span></div>
            </div>
            <div class="form-actions">
                <a class="btn-secondary" href="../laboratory/view.php?id=<?= (int)$latestLaboratoryRequest['id'] ?>">Open Result</a>
            </div>
        </div>
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

            case 'admission':

                require __DIR__ . '/partials/tabs/admission.php';

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
