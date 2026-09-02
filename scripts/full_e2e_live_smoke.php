<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/AccountsService.php';
require_once __DIR__ . '/../services/AdmissionService.php';
require_once __DIR__ . '/../services/BillingService.php';
require_once __DIR__ . '/../services/ClinicalNoteService.php';
require_once __DIR__ . '/../services/ClinicalSafetyService.php';
require_once __DIR__ . '/../services/ConsultationService.php';
require_once __DIR__ . '/../services/DepartmentNotificationService.php';
require_once __DIR__ . '/../services/DiabetesMonitoringService.php';
require_once __DIR__ . '/../services/DressingRecordService.php';
require_once __DIR__ . '/../services/ECGService.php';
require_once __DIR__ . '/../services/LaboratoryService.php';
require_once __DIR__ . '/../services/MedicalDocumentService.php';
require_once __DIR__ . '/../services/MedicationAdministrationService.php';
require_once __DIR__ . '/../services/NursingService.php';
require_once __DIR__ . '/../services/PatientService.php';
require_once __DIR__ . '/../services/PatientStockUsageService.php';
require_once __DIR__ . '/../services/PermissionService.php';
require_once __DIR__ . '/../services/PharmacyService.php';
require_once __DIR__ . '/../services/PhysiotherapyService.php';
require_once __DIR__ . '/../services/POPService.php';
require_once __DIR__ . '/../services/ProblemListService.php';
require_once __DIR__ . '/../services/RadiologyService.php';
require_once __DIR__ . '/../services/StockRequestService.php';
require_once __DIR__ . '/../services/StoreService.php';
require_once __DIR__ . '/../services/TheatreService.php';
require_once __DIR__ . '/../services/UserNotificationService.php';
require_once __DIR__ . '/../services/VisitService.php';
require_once __DIR__ . '/../services/VitalSignsService.php';

set_time_limit(300);

function log_step(string $message): void
{
    echo '[E2E] ' . $message . PHP_EOL;
}

function must(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function ok(array $result, string $label): array
{
    must(($result['success'] ?? false) === true, $label . ': ' . implode(' ', $result['errors'] ?? []));
    log_step('OK - ' . $label);
    return $result;
}

function find_user(PDO $pdo, array $preferred): array
{
    foreach ($preferred as $username) {
        $stmt = $pdo->prepare('
            SELECT u.*, r.role_name, d.department_name, d.department_name AS active_department_name
            FROM users u
            LEFT JOIN roles r ON r.id = u.role_id
            LEFT JOIN departments d ON d.id = u.department_id
            WHERE u.username = :username
            LIMIT 1
        ');
        $stmt->execute([':username' => $username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $row;
        }
    }

    throw new RuntimeException('Missing user: ' . implode(' / ', $preferred));
}

function dept_id(PDO $pdo, array $names): int
{
    foreach ($names as $name) {
        $stmt = $pdo->prepare('SELECT id FROM departments WHERE department_name = :name LIMIT 1');
        $stmt->execute([':name' => $name]);
        $id = $stmt->fetchColumn();
        if ($id !== false) {
            return (int)$id;
        }
    }

    throw new RuntimeException('Missing department: ' . implode(' / ', $names));
}

function ensure_billable(AccountsService $accounts, PDO $pdo, array $user, string $code, string $name, string $type, ?int $departmentId, float $price, string $unit = ''): int
{
    $existing = $accounts->getItemByCode($code, $user);
    if ($existing) {
        return (int)$existing['id'];
    }

    $created = ok($accounts->createItem([
        'item_code' => $code,
        'item_name' => $name,
        'item_type' => $type,
        'department_id' => $departmentId,
        'description' => 'E2E smoke-test catalogue item.',
        'unit_price' => $price,
        'unit' => $unit,
        'is_active' => 1,
    ], $user), 'Accounts catalogue item ' . $code);

    return (int)$created['billable_item_id'];
}

function ensure_inventory(StoreService $store, array $user, string $code, string $name, string $category, string $unit, ?int $billableItemId): int
{
    $existing = $store->getItemByCode($code, $user);
    if ($existing) {
        return (int)$existing['id'];
    }

    $created = ok($store->createItem([
        'item_code' => $code,
        'item_name' => $name,
        'category' => $category,
        'unit' => $unit,
        'description' => 'E2E smoke-test inventory item.',
        'billable_item_id' => $billableItemId,
        'is_active' => 1,
    ], $user), 'Store inventory item ' . $code);

    return (int)$created['inventory_item_id'];
}

function act_as(array $user): void
{
    $_SESSION['user'] = $user;
    $_SESSION['user_id'] = (int)($user['id'] ?? 0);
}

function upload_smoke_document(MedicalDocumentService $documents, int $patientId, int $visitId, array $user): int
{
    $tmp = tempnam(sys_get_temp_dir(), 'e2e-doc-');
    file_put_contents((string)$tmp, "E2E blood card placeholder\n");
    putenv('HMS_APP_ENV=testing');
    $result = ok($documents->uploadDocument([
        'patient_id' => $patientId,
        'visit_id' => $visitId,
        'document_type' => 'blood_card',
        'title' => 'E2E Blood Card Placeholder',
        'description' => 'Smoke-test blood card upload.',
        'confidentiality_level' => 'Standard',
    ], [
        'name' => 'e2e-blood-card.txt',
        'type' => 'text/plain',
        'tmp_name' => (string)$tmp,
        'error' => UPLOAD_ERR_OK,
        'size' => filesize((string)$tmp),
    ], $user), 'Medical document upload');
    putenv('HMS_APP_ENV');
    return (int)$result['document_id'];
}

$databaseName = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
log_step('Live database: ' . $databaseName);

$permission = new PermissionService($pdo);
$patientService = new PatientService($pdo);
$visitService = new VisitService($pdo);
$consultation = new ConsultationService($pdo);
$vitals = new VitalSignsService($pdo, null, $permission);
$nursing = new NursingService($pdo, null, null, $permission);
$lab = new LaboratoryService($pdo, null, null, $permission);
$radiology = new RadiologyService($pdo, null, null, $permission);
$ecgSmokeStorage = __DIR__ . '/../storage/e2e_ecg_charts';
$ecg = new ECGService($pdo, null, null, $permission, $ecgSmokeStorage);
$pop = new POPService($pdo, null, null, $permission);
$physio = new PhysiotherapyService($pdo, null, null, $permission);
$theatre = new TheatreService($pdo, null, null, $permission);
$accounts = new AccountsService($pdo, null, $permission);
$store = new StoreService($pdo, null, $permission);
$billing = new BillingService($pdo, $accounts, null, $permission);
$pharmacy = new PharmacyService($pdo, $store, null, null, null, $permission, $visitService);
$stockRequests = new StockRequestService($pdo, null, $permission, $store);
$stockUsage = new PatientStockUsageService($pdo, $store, $billing, null, $permission);
$admissions = new AdmissionService($pdo, null, null, $permission);
$departmentNotifications = new DepartmentNotificationService($pdo);
$userNotifications = new UserNotificationService($pdo);
$safety = new ClinicalSafetyService($pdo, null, null, null, $permission);
$problems = new ProblemListService($pdo);
$notes = new ClinicalNoteService($pdo);
$dressing = new DressingRecordService($pdo, null, $permission);
$mar = new MedicationAdministrationService($pdo, null, $permission);
$dm = new DiabetesMonitoringService($pdo, null, $permission);
$documents = new MedicalDocumentService(
    $pdo,
    new SecureLocalDocumentStorage(__DIR__ . '/../storage/e2e_documents'),
    null,
    null,
    null,
    $permission
);

$superadmin = find_user($pdo, ['walter', 'mmobash']);
$doctor = find_user($pdo, ['eessien', 'dev_doctor']);
$nurse = find_user($pdo, ['nblessing', 'dev_nurse']);
$records = find_user($pdo, ['ochristabel', 'dev_records']);
$reception = find_user($pdo, ['reception', 'dev_reception']);
$labUser = find_user($pdo, ['esylvester', 'dev_laboratory']);
$xrayUser = find_user($pdo, ['ajohn', 'dev_radiology']);
$ecgUser = find_user($pdo, ['ebright', 'dev_ecg']);
$popUser = find_user($pdo, ['oosasumwen', 'dev_pop']);
$physioUser = find_user($pdo, ['iirene', 'dev_physiotherapy']);
$storeUser = find_user($pdo, ['aagnes', 'dev_store']);
$pharmacist = find_user($pdo, ['ifestus', 'dev_pharmacy']);
$accountsUser = find_user($pdo, ['ofestusiyen', 'dev_accounts']);
$orderly = find_user($pdo, ['ograce']);

$doctorDept = dept_id($pdo, ['Doctor']);
$nurseDept = dept_id($pdo, ['Nursing', 'Nurse']);
$labDept = dept_id($pdo, ['Laboratory', 'Lab']);
$xrayDept = dept_id($pdo, ['X-Ray', 'Radiology']);
$ecgDept = dept_id($pdo, ['ECG']);
$popDept = dept_id($pdo, ['POP']);
$physioDept = dept_id($pdo, ['Physiotherapy', 'Physio']);
$theatreDept = dept_id($pdo, ['Theatre']);
$storeDept = dept_id($pdo, ['Store']);
$pharmacyDept = dept_id($pdo, ['Pharmacy']);
$accountsDept = dept_id($pdo, ['Accounts', 'Account']);
$orderlyDept = (int)($orderly['department_id'] ?? 0);

$stamp = date('YmdHis');
act_as($records);
$patient = ok($patientService->createPatient([
    'first_name' => 'E2E',
    'last_name' => 'HospitalFlow' . $stamp,
    'other_names' => 'Full Module Smoke',
    'gender' => 'Female',
    'date_of_birth' => '1988-05-12',
    'phone' => 'E2E-' . $stamp,
    'email' => '',
    'address' => 'E2E test address',
    'nationality' => 'Nigerian',
    'ethnic_group' => 'E2E',
    'occupation' => 'Test patient',
    'place_of_work' => 'Zimran QA',
    'religion' => 'Not specified',
    'blood_group' => 'O+',
    'genotype' => 'AA',
    'allergies' => 'Penicillin allergy for E2E safety banner.',
    'next_of_kin' => 'E2E Kin',
    'next_of_kin_phone' => '08000000000',
    'next_of_kin_relationship' => 'Sibling',
    'next_of_kin_address' => 'E2E kin address',
], (int)$records['id']), 'Records patient registration');
$patientId = (int)$patient['patient_id'];
log_step('Patient ID ' . $patientId);

act_as($reception);
$encounter = ok($visitService->createVisit([
    'patient_id' => $patientId,
    'visit_date' => date('Y-m-d H:i:s'),
    'visit_type' => 'Emergency',
    'current_department_id' => $doctorDept,
    'attending_doctor_id' => (int)$doctor['id'],
], (int)$reception['id']), 'Reception encounter creation');
$visitId = (int)$encounter['visit_id'];
$visitNumber = (string)($encounter['visit_number'] ?? $visitService->getVisitNumber($visitId));
log_step('Encounter ID ' . $visitId . ' / ' . $visitNumber);

act_as($doctor);
ok($visitService->assignDoctor($visitId, (int)$doctor['id'], (int)$doctor['id']), 'Doctor assigns doctor');

ok($safety->recordAllergy([
    'patient_id' => $patientId,
    'source_visit_id' => $visitId,
    'substance' => 'Penicillin',
    'reaction' => 'Rash and itching',
    'severity' => 'Moderate',
    'allergy_type' => 'Drug',
    'reason' => 'Patient-reported E2E safety history.',
], (int)$nurse['id']), 'Clinical safety allergy');

ok($safety->createAlert([
    'patient_id' => $patientId,
    'visit_id' => $visitId,
    'alert_type' => 'Fall Risk',
    'title' => 'Fall risk observation',
    'reason' => 'E2E alert: patient should be assisted when dizzy.',
    'priority' => 'Medium',
    'confidentiality_level' => 'Standard',
    'change_reason' => 'Initial E2E alert.',
], (int)$nurse['id']), 'Clinical safety alert');

ok($problems->addProblem([
    'patient_id' => $patientId,
    'source_visit_id' => $visitId,
    'problem_name' => 'Acute febrile illness',
    'category' => 'Acute Problem',
    'severity' => 'Moderate',
    'reason' => 'E2E clinical problem setup.',
    'notes' => 'E2E problem list item.',
], (int)$doctor['id'], $doctorDept), 'Problem list');

ok($problems->addHistoryEntry([
    'patient_id' => $patientId,
    'source_visit_id' => $visitId,
    'history_type' => 'Past Medical History',
    'title' => 'Previous malaria episode',
    'description' => 'Had malaria-like illness last year.',
    'event_date' => '2025-08-01',
    'date_precision' => 'Year',
    'status' => 'Historical',
    'confidentiality_level' => 'Standard',
    'reason' => 'E2E medical history setup.',
], (int)$doctor['id'], $doctorDept), 'Structured medical history');

$consult = ok($consultation->create([
    'visit_id' => $visitId,
    'patient_id' => $patientId,
    'presenting_complaint' => 'Fever for 3 days with body weakness.',
    'history_of_presenting_complaint' => 'Fever, headache, malaise and reduced appetite.',
    'examination_findings' => 'Febrile, hydrated, no respiratory distress.',
    'assessment' => 'Suspected malaria with mild dehydration.',
    'diagnosis' => 'Acute febrile illness; rule out malaria.',
    'treatment_plan' => 'Vitals, malaria test, FBC, hydration and review.',
    'advice' => 'Return urgently if worsening.',
    'follow_up' => 'Review after laboratory result.',
    'referral_notes' => 'Lab, x-ray/ECG/POP/physio/theatre smoke paths requested for E2E.',
], $doctor), 'Doctor consultation draft');
$consultationId = (int)$consult['consultation_id'];

ok($vitals->create([
    'visit_id' => $visitId,
    'patient_id' => $patientId,
    'department_id' => $nurseDept,
    'temperature' => '38.2',
    'pulse' => '96',
    'respiratory_rate' => '20',
    'systolic_bp' => '122',
    'diastolic_bp' => '78',
    'oxygen_saturation' => '98',
    'weight' => '70',
    'height' => '170',
    'blood_glucose' => '6.2',
    'pain_score' => '4',
    'notes' => 'E2E initial vital signs.',
], $nurse), 'Nurse vital signs');

$nursingRecord = ok($nursing->create([
    'visit_id' => $visitId,
    'patient_id' => $patientId,
    'general_condition' => 'Stable, febrile.',
    'nursing_observation' => 'Alert and oriented.',
    'pain_assessment' => 'Mild body pain.',
    'mobility' => 'Ambulates with minimal assistance.',
    'nutrition' => 'Reduced appetite.',
    'elimination' => 'No complaint.',
    'skin_assessment' => 'Skin intact.',
    'fall_risk' => 'Low to moderate due to weakness.',
    'nursing_interventions' => 'Tepid sponging, oral fluids encouraged.',
    'patient_response' => 'Comfort improved.',
    'handover_notes' => 'Monitor temperature.',
    'additional_notes' => 'E2E nursing assessment.',
], $nurse), 'Nursing assessment');
$nursingId = (int)$nursingRecord['nursing_assessment_id'];

ok($dressing->create([
    'visit_id' => $visitId,
    'patient_id' => $patientId,
    'wound_site' => 'Left forearm',
    'wound_condition' => 'Clean superficial abrasion.',
    'dressing_done' => 'Cleaned with normal saline and covered.',
    'supplies_used' => 'Gauze and gloves.',
    'next_dressing_date' => date('Y-m-d', strtotime('+2 days')),
], $nurse), 'Dressing book record');

ok($dm->create([
    'visit_id' => $visitId,
    'patient_id' => $patientId,
    'recorded_at' => date('Y-m-d H:i:s'),
    'blood_glucose' => '6.2',
    'insulin_given' => 'None',
    'meal_status' => 'Before Meal',
    'symptoms' => 'No hypoglycaemic symptoms.',
    'notes' => 'E2E DM sheet check.',
], $nurse), 'DM sheet record');

$labRequest = ok($lab->createRequest([
    'visit_id' => $visitId,
    'patient_id' => $patientId,
    'department_id' => $labDept,
    'request_source' => 'Clinical',
    'tests_requested' => 'Malaria Parasite, Full Blood Count',
    'clinical_information' => 'Fever for 3 days; suspected malaria.',
    'priority' => 'Routine',
], $doctor), 'Doctor laboratory request');
$labId = (int)$labRequest['laboratory_request_id'];
ok($lab->startRequest($labId, $labUser), 'Laboratory starts request');
ok($lab->saveResult([
    'laboratory_request_id' => $labId,
    'result' => "Sample taken: venous blood\nMalaria parasites detected (++).\nHb 11.2 g/dL, WBC 8.4 x10^9/L.",
    'interpretation' => 'Positive malaria parasites; mild anaemia.',
], $labUser), 'Laboratory enters result');
ok($lab->completeRequest($labId, $labUser), 'Laboratory completes request');

$radRequest = ok($radiology->createRequest([
    'visit_id' => $visitId,
    'patient_id' => $patientId,
    'department_id' => $xrayDept,
    'request_source' => 'Clinical',
    'study_requested' => 'Chest X-Ray',
    'clinical_indication' => 'Fever with cough for E2E radiology workflow.',
    'priority' => 'Routine',
], $doctor), 'Doctor radiology request');
$radId = (int)$radRequest['radiology_request_id'];
ok($radiology->startRequest($radId, $xrayUser), 'Radiology starts request');
ok($radiology->saveResult([
    'radiology_request_id' => $radId,
    'findings' => 'No focal lung consolidation.',
    'impression' => 'No acute cardiopulmonary abnormality.',
    'recommendation' => 'Clinical correlation advised.',
], $xrayUser), 'Radiology enters report');
ok($radiology->completeRequest($radId, $xrayUser), 'Radiology completes request');

$ecgRequest = ok($ecg->createRequest([
    'visit_id' => $visitId,
    'patient_id' => $patientId,
    'department_id' => $ecgDept,
    'request_source' => 'Clinical',
    'clinical_indication' => 'Palpitations during febrile illness.',
    'priority' => 'Routine',
], $doctor), 'Doctor ECG request');
$ecgId = (int)$ecgRequest['ecg_request_id'];
ok($ecg->startRequest($ecgId, $ecgUser), 'ECG starts request');
$tmpEcgChart = tempnam(sys_get_temp_dir(), 'e2e-ecg-');
file_put_contents((string)$tmpEcgChart, "%PDF-1.4\n% E2E ECG chart placeholder\n");
ok($ecg->saveReport([
    'ecg_request_id' => $ecgId,
    'notes' => 'Sinus rhythm, rate 92 bpm.',
    'remarks' => 'No acute ECG abnormality.',
], $ecgUser, [
    'name' => 'e2e-ecg-chart.pdf',
    'type' => 'application/pdf',
    'tmp_name' => (string)$tmpEcgChart,
    'error' => UPLOAD_ERR_OK,
    'size' => filesize((string)$tmpEcgChart),
]), 'ECG chart upload/report');
ok($ecg->completeRequest($ecgId, $ecgUser), 'ECG completes request');

$popRequest = ok($pop->createRequest([
    'visit_id' => $visitId,
    'patient_id' => $patientId,
    'department_id' => $popDept,
    'request_source' => 'Clinical',
    'procedure_requested' => 'Below elbow POP support',
    'clinical_indication' => 'Soft tissue support after minor injury.',
    'priority' => 'Routine',
], $doctor), 'Doctor POP request');
$popId = (int)$popRequest['pop_request_id'];
ok($pop->startRequest($popId, $popUser), 'POP starts request');
ok($pop->saveRecord([
    'pop_request_id' => $popId,
    'cast_type' => 'Below elbow POP support',
    'body_part' => 'Left forearm',
    'procedure_notes' => 'Below elbow POP backslab applied.',
    'materials_used' => 'POP bandage and padding.',
    'aftercare_instructions' => 'Keep dry and return if swelling occurs.',
    'remarks' => 'Patient tolerated procedure well.',
], $popUser), 'POP record');
ok($pop->completeRequest($popId, $popUser), 'POP completes request');

$physioRecord = ok($physio->createRecord([
    'visit_id' => $visitId,
    'patient_id' => $patientId,
    'department_id' => $physioDept,
    'record_source' => 'Clinical',
    'referral_reason' => 'Reduced mobility after febrile weakness.',
    'presenting_problem' => 'General body weakness.',
    'assessment' => 'Mild deconditioning.',
    'functional_limitations' => 'Reduced walking endurance.',
    'treatment_plan' => 'Mobility exercises and breathing exercises.',
    'goals' => 'Independent ambulation.',
    'precautions' => 'Stop if dizzy.',
], $doctor), 'Physiotherapy referral/record');
$physioId = (int)$physioRecord['physiotherapy_record_id'];
ok($physio->addSession([
    'physiotherapy_record_id' => $physioId,
    'visit_id' => $visitId,
    'patient_id' => $patientId,
    'session_date' => date('Y-m-d'),
    'treatment_given' => 'Mobility and breathing exercises.',
    'patient_response' => 'Tolerated well.',
    'progress_notes' => 'Walking endurance improved.',
    'next_plan' => 'Continue mobility exercises.',
], $physioUser), 'Physiotherapy session');

$theatreRecord = ok($theatre->create([
    'visit_id' => $visitId,
    'patient_id' => $patientId,
    'department_id' => $theatreDept,
    'procedure_name' => 'Minor wound exploration',
    'indication' => 'E2E minor procedure pathway.',
    'preoperative_notes' => 'Consent documented in medical documents.',
    'procedure_details' => 'Minor wound exploration and cleaning performed.',
    'findings' => 'No foreign body seen.',
    'complications' => 'None.',
    'postoperative_notes' => 'Stable.',
    'postoperative_plan' => 'Continue dressing.',
    'anaesthesia_notes' => 'Local anaesthesia.',
], $doctor), 'Theatre record');
$theatreId = (int)$theatreRecord['theatre_record_id'];

$drugBillable = ensure_billable($accounts, $pdo, $accountsUser, 'E2E-DRG-' . $stamp, 'E2E Paracetamol 500 mg', 'Product', $pharmacyDept, 150, 'Tablet');
$supplyBillable = ensure_billable($accounts, $pdo, $accountsUser, 'E2E-SUP-' . $stamp, 'E2E Syringe 5 ml', 'Product', $storeDept, 100, 'Piece');
$consultBillable = ensure_billable($accounts, $pdo, $accountsUser, 'E2E-CONS-' . $stamp, 'E2E General Consultation', 'Service', $doctorDept, 5000, '');
$drugItem = ensure_inventory($store, $storeUser, 'E2E-DRG-' . $stamp, 'E2E Paracetamol 500 mg', 'Medication', 'Tablet', $drugBillable);
$supplyItem = ensure_inventory($store, $storeUser, 'E2E-SUP-' . $stamp, 'E2E Syringe 5 ml', 'Consumable', 'Piece', $supplyBillable);

ok($store->receiveStock([
    'inventory_item_id' => $drugItem,
    'quantity' => 1000,
    'reference' => 'E2E-RCV-' . $stamp,
    'remarks' => 'E2E stock receipt for pharmacy.',
], $storeUser), 'Store receives pharmacy stock');
ok($store->issueStock([
    'inventory_item_id' => $drugItem,
    'department_id' => $pharmacyDept,
    'quantity' => 200,
    'reference' => 'E2E-ISS-PHA-' . $stamp,
    'remarks' => 'Issue to Pharmacy for E2E dispense.',
], $storeUser), 'Store issues stock to Pharmacy');
ok($store->receiveStock([
    'inventory_item_id' => $supplyItem,
    'quantity' => 100,
    'reference' => 'E2E-RCV-SUP-' . $stamp,
    'remarks' => 'E2E stock receipt for nursing usage.',
], $storeUser), 'Store receives supply stock');
ok($store->issueStock([
    'inventory_item_id' => $supplyItem,
    'department_id' => $nurseDept,
    'quantity' => 20,
    'reference' => 'E2E-ISS-NUR-' . $stamp,
    'remarks' => 'Issue to Nursing for patient usage.',
], $storeUser), 'Store issues stock to Nursing');

$stockReq = ok($stockRequests->createRequest([
    'requesting_department_id' => $nurseDept,
    'reason' => 'E2E ward restock request.',
    'inventory_item_id' => [$supplyItem],
    'quantity_requested' => [5],
    'notes' => ['For dressing trolley.'],
], $nurse), 'Nursing creates stock request');
$stockReqId = (int)$stockReq['stock_request_id'];
ok($stockRequests->approveRequest($stockReqId, $storeUser), 'Store approves stock request');
$stockReqRow = $stockRequests->getRequestById($stockReqId, $storeUser);
$stockReqLineId = (int)($stockReqRow['items'][0]['id'] ?? 0);
must($stockReqLineId > 0, 'Stock request line was not retrievable.');
ok($stockRequests->issueRequest($stockReqId, [$stockReqLineId => 5], $storeUser), 'Store issues stock request');

$orderlyReq = ok($stockRequests->createRequest([
    'requesting_department_id' => $orderlyDept,
    'reason' => 'E2E orderly restock request.',
    'inventory_item_id' => [$supplyItem],
    'quantity_requested' => [3],
    'notes' => ['Orderly cleaning/support supplies.'],
], $orderly), 'Orderly creates stock request');
$orderlyReqId = (int)$orderlyReq['stock_request_id'];
ok($stockRequests->approveRequest($orderlyReqId, $storeUser), 'Store approves Orderly stock request');
$orderlyReqRow = $stockRequests->getRequestById($orderlyReqId, $storeUser);
$orderlyReqLineId = (int)($orderlyReqRow['items'][0]['id'] ?? 0);
must($orderlyReqLineId > 0, 'Orderly stock request line was not retrievable.');
ok($stockRequests->issueRequest($orderlyReqId, [$orderlyReqLineId => 3], $storeUser), 'Store issues stock request to Orderly');

ok($stockUsage->createUsage([
    'visit_id' => $visitId,
    'patient_id' => $patientId,
    'department_id' => $nurseDept,
    'inventory_item_id' => $supplyItem,
    'quantity' => 2,
    'usage_reason' => 'Used during dressing care.',
    'source_module' => 'Nursing',
    'source_record_id' => $nursingId,
    'request_billing' => 1,
], $nurse), 'Patient stock usage with billing request');

$prescription = ok($pharmacy->createPrescription([
    'visit_id' => $visitId,
    'patient_id' => $patientId,
    'prescription_source' => 'Clinical',
    'inventory_item_id' => $drugItem,
    'medication_name' => 'E2E Paracetamol 500 mg',
    'dosage' => '1 tablet',
    'frequency' => '3 times daily',
    'duration' => '3 days',
    'quantity' => 9,
    'instructions' => 'Take after meals.',
], $doctor), 'Doctor creates prescription');
$prescriptionId = (int)$prescription['prescription_id'];
ok($pharmacy->dispense($prescriptionId, [
    'quantity_dispensed' => 9,
    'dispensing_notes' => 'Dispensed after clinical safety review.',
], $pharmacist), 'Pharmacy dispenses prescription and reduces stock');

ok($mar->create([
    'prescription_id' => $prescriptionId,
    'visit_id' => $visitId,
    'patient_id' => $patientId,
    'scheduled_time' => date('Y-m-d H:i:s'),
    'dose_given' => '1 tablet',
    'route' => 'Oral',
    'administration_status' => 'Given',
    'notes' => 'First dose given on ward.',
], $nurse), 'Nurse drug chart/MAR');

$billingRequest = ok($billing->createBillingRequest([
    'visit_id' => $visitId,
    'department_id' => $doctorDept,
    'source_module' => 'Consultation',
    'source_record_id' => $consultationId,
    'description' => 'E2E consultation completed; please bill consultation fee.',
    'suggested_billable_item_id' => $consultBillable,
    'quantity' => 1,
], $doctor), 'Doctor creates billing request');
$billingRequestId = (int)$billingRequest['billing_request_id'];
ok($billing->chargeBillingRequest([
    'billing_request_id' => $billingRequestId,
    'billable_item_id' => $consultBillable,
    'quantity' => 1,
    'description' => 'E2E consultation fee from billing request.',
    'notes' => 'Approved during E2E smoke.',
], $accountsUser), 'Accounts converts billing request to charge');

ok($billing->createCharge([
    'visit_id' => $visitId,
    'billable_item_id' => $drugBillable,
    'quantity' => 9,
    'description' => 'E2E dispensed paracetamol charge.',
    'source_module' => 'Pharmacy',
    'source_record_id' => $prescriptionId,
], $accountsUser), 'Accounts manual/source charge for pharmacy item');

$invoiceResult = ok($billing->createInvoice($visitId, $accountsUser), 'Accounts creates invoice');
$invoice = $billing->getInvoiceByVisit($visitId, $accountsUser);
must($invoice !== null, 'Invoice not retrievable after creation.');
ok($billing->recordPayment([
    'invoice_id' => (int)$invoice['id'],
    'amount' => (float)$invoice['balance_due'],
    'payment_method' => 'Cash',
    'reference' => 'E2E-PAY-' . $stamp,
    'notes' => 'E2E full settlement.',
], $accountsUser), 'Billing records payment/receipt');

ok($departmentNotifications->send([
    'visit_id' => $visitId,
    'to_department_id' => $labDept,
    'reason' => 'E2E department notification to laboratory.',
], $doctor), 'Department notification');

ok($userNotifications->send([
    'visit_id' => $visitId,
    'to_user_id' => (int)$labUser['id'],
    'message' => 'E2E user notification for lab review.',
], $doctor), 'User notification');

$ward = $admissions->listWards(true)[0] ?? null;
if ($ward === null || (($admissions->listAvailableBeds((int)$ward['id'])[0] ?? null) === null)) {
    $wardResult = ok($admissions->createWard([
        'ward_name' => 'E2E Observation Ward ' . $stamp,
        'ward_code' => 'E2E' . substr($stamp, -6),
        'department_id' => $nurseDept,
        'description' => 'Temporary E2E smoke-test ward.',
    ], $nurse), 'Nursing creates E2E ward');
    $bedResult = ok($admissions->addBed([
        'ward_id' => (int)$wardResult['ward_id'],
        'bed_label' => 'E2E Bed ' . substr($stamp, -6),
    ], $nurse), 'Nursing creates E2E bed');
    $ward = ['id' => (int)$wardResult['ward_id']];
    $beds = [['id' => (int)$bedResult['bed_id']]];
} else {
    $beds = $admissions->listAvailableBeds((int)$ward['id']);
}
$admission = ok($admissions->admit([
    'visit_id' => $visitId,
    'patient_id' => $patientId,
    'ward_id' => (int)$ward['id'],
    'bed_id' => (int)$beds[0]['id'],
    'admission_type' => 'Emergency',
    'admission_diagnosis' => 'E2E observation admission.',
    'admission_notes' => 'Admitted for observation during smoke test.',
], $nurse), 'Nurse admits patient');
$admissionId = (int)$admission['admission_id'];

ok($notes->createDraft([
    'patient_id' => $patientId,
    'visit_id' => $visitId,
    'note_type' => 'progress_note',
    'title' => 'E2E progress note',
    'content' => 'Patient reviewed after department workflows.',
    'confidentiality_level' => 'Standard',
], $doctor), 'Clinical note draft');

$documentId = upload_smoke_document($documents, $patientId, $visitId, $records);
log_step('OK - Medical document placeholder #' . $documentId);

ok($consultation->complete($consultationId, $doctor), 'Doctor completes consultation');
ok($nursing->complete($nursingId, $nurse), 'Nurse completes nursing assessment');
ok($physio->completeRecord($physioId, $physioUser), 'Physiotherapy completes record');
ok($theatre->complete($theatreId, $doctor), 'Theatre completes record');

$balance = $billing->getEncounterBalance($visitId, $accountsUser);
must(abs((float)($balance['balance_due'] ?? 0)) < 0.01, 'Encounter billing balance is not settled.');
must(count($lab->listByVisit($visitId, $doctor)) >= 1, 'Laboratory visit list empty.');
must(count($radiology->listByVisit($visitId, $doctor)) >= 1, 'Radiology visit list empty.');
must(count($pharmacy->listByVisit($visitId, $doctor)) >= 1, 'Pharmacy visit list empty.');
must(count($store->listDepartmentLedger($nurseDept, $nurse, 20)) >= 1, 'Nursing department ledger empty.');
must(count($billing->listBillingRequests(['visit_id' => $visitId], $accountsUser)) >= 1, 'Billing request list empty.');

log_step('DONE');
echo json_encode([
    'database' => $databaseName,
    'patient_id' => $patientId,
    'visit_id' => $visitId,
    'visit_number' => $visitNumber,
    'patient_name' => 'E2E HospitalFlow' . $stamp,
], JSON_PRETTY_PRINT) . PHP_EOL;
