<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/test_database.php';
require_once __DIR__ . '/../services/PermissionService.php';

function assertCurrentScopePermission(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function currentScopeUser(string $role, string $department): array
{
    return [
        'id' => crc32($role . '|' . $department),
        'role_id' => 0,
        'role_name' => $role,
        'department_id' => 0,
        'department_name' => $department,
        'active_department_id' => 0,
        'active_department_name' => $department,
    ];
}

$permissionService = new PermissionService($pdo);

$doctor = currentScopeUser('Doctor', 'Doctor');
$nurse = currentScopeUser('Nurse', 'Nursing');
$reception = currentScopeUser('Receptionist', 'Reception');
$records = currentScopeUser('Records Officer', 'Records');
$accounts = currentScopeUser('Accountant', 'Accounts');
$laboratory = currentScopeUser('Laboratory Scientist', 'Laboratory');
$radiology = currentScopeUser('Radiographer', 'X-Ray');
$ecg = currentScopeUser('ECG Technician', 'ECG');
$plaster = currentScopeUser('Plaster Technician', 'Plaster');
$physio = currentScopeUser('Physiotherapist', 'Physiotherapy');
$theatre = currentScopeUser('Theatre Staff', 'Theatre');
$pharmacy = currentScopeUser('Pharmacist', 'Pharmacy');
$store = currentScopeUser('Store Officer', 'Store');
$admin = currentScopeUser('System Administrator', 'Administrator');
$superAdmin = currentScopeUser('Super Administrator', 'Super Administrator');

$doctorEncounter = [
    'id' => 1,
    'patient_id' => 1,
    'visit_status' => 'Doctor',
    'current_department_id' => 4,
    'attending_doctor_id' => (int)$doctor['id'],
];

$requestDepartments = [
    'Laboratory' => [$laboratory, 'canViewLaboratoryWorklist', 'canProcessLaboratoryRequest'],
    'Radiology' => [$radiology, 'canViewRadiologyWorklist', 'canProcessRadiologyRequest'],
    'ECG' => [$ecg, 'canViewEcgWorklist', 'canProcessEcgRequest'],
    'Plaster' => [$plaster, 'canViewPopWorklist', 'canProcessPopRequest'],
    'Physiotherapy' => [$physio, 'canViewPhysiotherapyWorklist', 'canProcessPhysiotherapyRequest'],
    'Pharmacy' => [$pharmacy, 'canViewPharmacyWorklist', 'canDispensePrescription'],
];

assertCurrentScopePermission($permissionService->canCreateLaboratoryRequest($doctorEncounter, $doctor, 'Clinical'), 'Doctor should create Laboratory clinical requests.');
assertCurrentScopePermission($permissionService->canCreateRadiologyRequest($doctorEncounter, $doctor, 'Clinical'), 'Doctor should create Radiology clinical requests.');
assertCurrentScopePermission($permissionService->canCreateEcgRequest($doctorEncounter, $doctor, 'Clinical'), 'Doctor should create ECG clinical requests.');
assertCurrentScopePermission($permissionService->canCreatePopRequest($doctorEncounter, $doctor, 'Clinical'), 'Doctor should create Plaster clinical requests.');
assertCurrentScopePermission($permissionService->canCreatePhysiotherapyRequest($doctorEncounter, $doctor, 'Clinical'), 'Doctor should create Physiotherapy clinical referrals.');
assertCurrentScopePermission($permissionService->canCreateTheatre($doctorEncounter, $doctor), 'Doctor should create Theatre clinical records.');
assertCurrentScopePermission($permissionService->canCreatePrescription($doctorEncounter, $doctor, 'Clinical'), 'Doctor should create clinical prescriptions.');

foreach ($requestDepartments as $label => [$user, $worklistMethod, $processMethod]) {
    assertCurrentScopePermission($permissionService->{$worklistMethod}($user), $label . ' should view its own request worklist.');
    assertCurrentScopePermission($permissionService->{$processMethod}($doctorEncounter, $user), $label . ' should process its own request after billing clearance.');
}

assertCurrentScopePermission(!$permissionService->canViewLaboratoryWorklist($pharmacy), 'Pharmacy should not browse Laboratory worklist.');
assertCurrentScopePermission(!$permissionService->canViewRadiologyWorklist($laboratory), 'Laboratory should not browse Radiology worklist.');
assertCurrentScopePermission(!$permissionService->canViewEcgWorklist($radiology), 'Radiology should not browse ECG worklist.');
assertCurrentScopePermission(!$permissionService->canViewPopWorklist($ecg), 'ECG should not browse Plaster worklist.');
assertCurrentScopePermission(!$permissionService->canViewPhysiotherapyWorklist($nurse), 'Nurse should not browse Physiotherapy worklist.');
assertCurrentScopePermission(!$permissionService->canViewPharmacyWorklist($doctor), 'Doctor should not browse Pharmacy worklist.');

assertCurrentScopePermission($permissionService->canViewBillingRequests($accounts), 'Accounts should view billing requests.');
assertCurrentScopePermission($permissionService->canReviewBillingRequest($accounts), 'Accounts should review billing requests.');
assertCurrentScopePermission($permissionService->canRecordPayment($accounts), 'Accounts should record payments.');
assertCurrentScopePermission(!$permissionService->canReviewBillingRequest($doctor), 'Doctor should not clear Accounts billing requests.');
assertCurrentScopePermission(!$permissionService->canRecordPayment($nurse), 'Nurse should not record payments.');

assertCurrentScopePermission($permissionService->canCreateBillableItems($pharmacy), 'Pharmacy should create price catalogue items.');
assertCurrentScopePermission($permissionService->canEditBillableItems($pharmacy), 'Pharmacy should edit price catalogue items.');
assertCurrentScopePermission($permissionService->canManageBillableItemStatus($pharmacy), 'Pharmacy should manage price catalogue item status.');
assertCurrentScopePermission(!$permissionService->canCreateBillableItems($accounts), 'Accounts should not create price catalogue items after Pharmacy ownership change.');
assertCurrentScopePermission(!$permissionService->canEditBillableItems($accounts), 'Accounts should not edit price catalogue items after Pharmacy ownership change.');

assertCurrentScopePermission($permissionService->canReceiveStock($store), 'Store should receive stock brought into the hospital.');
assertCurrentScopePermission($permissionService->canIssueStock($store), 'Store should move stock to Pharmacy.');
assertCurrentScopePermission($permissionService->canIssueStock($pharmacy), 'Pharmacy should move stock onward to departments.');
assertCurrentScopePermission(!$permissionService->canReceiveStock($pharmacy), 'Pharmacy should not receive hospital stock intake directly.');

assertCurrentScopePermission($permissionService->hasPermission('register_patient', $reception), 'Reception should register patients.');
assertCurrentScopePermission($permissionService->hasPermission('create_encounter', $reception), 'Reception should create encounters.');
assertCurrentScopePermission($permissionService->hasPermission('register_patient', $records), 'Records should inherit patient registration.');
assertCurrentScopePermission($permissionService->hasPermission('create_encounter', $records), 'Records should inherit encounter creation.');

assertCurrentScopePermission($permissionService->canManageUsers($admin), 'System Administrator should manage users.');
assertCurrentScopePermission($permissionService->canViewAllDepartmentWorklists($admin), 'System Administrator should view all department worklists.');
assertCurrentScopePermission(!$permissionService->canCreateConsultation($doctorEncounter, $admin), 'System Administrator should not inherit clinical mutation access.');
assertCurrentScopePermission($permissionService->canCreateConsultation($doctorEncounter, $superAdmin), 'Super Administrator should retain clinical override.');
assertCurrentScopePermission($permissionService->canRecordPayment($superAdmin), 'Super Administrator should retain billing override.');

assertCurrentScopePermission($permissionService->canViewEmergency($admin), 'Administrator should view Emergency.');
assertCurrentScopePermission($permissionService->canViewEmergency($superAdmin), 'Super Administrator should view Emergency.');

$emergencyRoleId = (int)$pdo->query("SELECT id FROM roles WHERE role_name IN ('Emergency Nurse','Triage Nurse') ORDER BY FIELD(role_name, 'Emergency Nurse', 'Triage Nurse') LIMIT 1")->fetchColumn();
$emergencyDepartmentId = (int)$pdo->query("SELECT id FROM departments WHERE department_name = 'Emergency' LIMIT 1")->fetchColumn();
assertCurrentScopePermission($emergencyRoleId > 0 && $emergencyDepartmentId > 0, 'Emergency role/department seed is missing.');

$pdo->exec("DELETE FROM user_permissions WHERE user_id IN (SELECT id FROM users WHERE username = 'perm_matrix_emergency')");
$pdo->exec("DELETE FROM users WHERE username = 'perm_matrix_emergency'");
$pdo->prepare("
    INSERT INTO users (
        employee_id, first_name, last_name, gender, email, username,
        password, department_id, role_id, status, must_change_password, created_at
    ) VALUES (
        'PERM-EMERGENCY', 'Permission', 'Emergency', 'Female', 'perm.emergency@example.invalid', 'perm_matrix_emergency',
        :password, :department_id, :role_id, 'Active', 0, NOW()
    )
")->execute([
    ':password' => password_hash('permission-test', PASSWORD_DEFAULT),
    ':department_id' => $emergencyDepartmentId,
    ':role_id' => $emergencyRoleId,
]);
$selectedEmergencyUserId = (int)$pdo->lastInsertId();
$pdo->prepare("
    INSERT INTO user_permissions (user_id, permission_id, effect, created_at)
    SELECT :user_id, p.id, 'Allow', NOW()
    FROM permissions p
    WHERE p.permission_key IN ('view_emergency', 'view_emergency_worklist', 'view_emergency_reports')
")->execute([':user_id' => $selectedEmergencyUserId]);

$selectedEmergency = $pdo->query("
    SELECT u.*, r.role_name, d.department_name
    FROM users u
    INNER JOIN roles r ON r.id = u.role_id
    INNER JOIN departments d ON d.id = u.department_id
    WHERE u.username = 'perm_matrix_emergency'
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);
assertCurrentScopePermission((bool)$selectedEmergency, 'Selected Emergency test user was not created.');
assertCurrentScopePermission($permissionService->canViewEmergencyWorklist($selectedEmergency), 'Selected Emergency users should view Emergency worklist.');
assertCurrentScopePermission(!$permissionService->canViewReports($selectedEmergency), 'Selected Emergency users should not inherit all reports.');
assertCurrentScopePermission($permissionService->canViewEmergencyReports($selectedEmergency), 'Selected Emergency users should view Emergency reports.');

$pdo->exec("DELETE FROM user_permissions WHERE user_id = {$selectedEmergencyUserId}");
$pdo->exec("DELETE FROM users WHERE id = {$selectedEmergencyUserId}");

echo "PASS: Current-scope permission matrix regression passed.\n";
