<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/test_database.php';
require_once __DIR__ . '/../database/tools/DatabaseSafety.php';
require_once __DIR__ . '/../database/tools/MigrationManager.php';
require_once __DIR__ . '/../services/BillingService.php';
require_once __DIR__ . '/../services/ConsultationService.php';
require_once __DIR__ . '/../services/PermissionService.php';
require_once __DIR__ . '/../services/POPService.php';

function assertPop(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function requirePopSuccess(array $result, string $operation): array
{
    assertPop(($result['success'] ?? false) === true, $operation . ': ' . implode(' ', $result['errors'] ?? []));
    return $result;
}

function popUser(PDO $pdo, string $username): array
{
    $stmt = $pdo->prepare('
        SELECT u.*, r.role_name, d.department_name, d.department_name AS active_department_name
        FROM users u
        INNER JOIN roles r ON r.id = u.role_id
        INNER JOIN departments d ON d.id = u.department_id
        WHERE u.username = :username
        LIMIT 1
    ');
    $stmt->execute([':username' => $username]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    assertPop((bool)$row, 'Missing test user ' . $username . '.');
    return $row;
}

function popEnsureTechnician(PDO $pdo): array
{
    $password = password_hash('development-password', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('
        INSERT INTO users (
            employee_id, first_name, last_name, gender, email, username,
            password, department_id, role_id, status, must_change_password
        )
        SELECT "DEV-POP-001", "Development", "Plaster", "Female", "dev_pop@development.invalid",
               "dev_pop", :password, d.id, r.id, "Active", 0
        FROM departments d
        INNER JOIN roles r
        WHERE d.department_name = "Plaster"
          AND r.role_name = "Plaster Technician"
        ON DUPLICATE KEY UPDATE
            department_id = VALUES(department_id),
            role_id = VALUES(role_id),
            status = "Active",
            must_change_password = 0
    ');
    $stmt->execute([':password' => $password]);

    if (in_array('user_departments', $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN), true)) {
        $pdo->exec('
            INSERT INTO user_departments (user_id, department_id, is_primary, is_active, assigned_by)
            SELECT u.id, u.department_id, 1, 1, 1
            FROM users u
            WHERE u.username = "dev_pop"
            ON DUPLICATE KEY UPDATE is_primary = 1, is_active = 1
        ');
    }

    return popUser($pdo, 'dev_pop');
}

function popCreateEncounter(PDO $pdo, array $actor, int $patientId, int $departmentId, string $status, string $suffix): int
{
    $stmt = $pdo->prepare('
        INSERT INTO visits (
            visit_number, patient_id, visit_date, visit_type, current_department_id,
            attending_doctor_id, current_department_received_status, visit_status, created_by
        ) VALUES (
            :visit_number, :patient_id, NOW(), "Outpatient", :department_id,
            :attending_doctor_id, "Received", :visit_status, :created_by
        )
    ');
    $stmt->execute([
        ':visit_number' => 'POP-' . $status . '-' . $suffix,
        ':patient_id' => $patientId,
        ':department_id' => $departmentId,
        ':attending_doctor_id' => (int)($actor['id'] ?? 0),
        ':visit_status' => $status,
        ':created_by' => (int)($actor['id'] ?? 0),
    ]);

    return (int)$pdo->lastInsertId();
}

function popWorklistContains(array $rows, int $requestId): bool
{
    foreach ($rows as $row) {
        if ((int)($row['id'] ?? 0) === $requestId) {
            return true;
        }
    }
    return false;
}

$config = require __DIR__ . '/../config/app.php';
$resolved = DatabaseSafety::resolveTestDatabase($config);
$databaseName = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
assertPop($databaseName === $resolved['test'] && $databaseName !== $resolved['live'], 'Plaster tests are not isolated from the live database.');

$manager = new MigrationManager($pdo, $databaseName);
$manager->ensureLedger();
$manager->apply(__DIR__ . '/../database/migrations/059_pop_department_crud_up.sql', 59);
$manager->apply(__DIR__ . '/../database/migrations/073_rename_pop_to_plaster_up.sql', 73);

$pdo->exec("DELETE ce FROM encounter_events ce INNER JOIN visits v ON v.id = ce.visit_id WHERE v.visit_number LIKE 'POP-%'");
$pdo->exec("DELETE al FROM audit_logs al INNER JOIN visits v ON v.id = al.visit_id WHERE v.visit_number LIKE 'POP-%'");
$pdo->exec("DELETE prc FROM pop_records prc INNER JOIN pop_requests pr ON pr.id = prc.pop_request_id INNER JOIN visits v ON v.id = pr.visit_id WHERE v.visit_number LIKE 'POP-%'");
$pdo->exec("DELETE pr FROM pop_requests pr INNER JOIN visits v ON v.id = pr.visit_id WHERE v.visit_number LIKE 'POP-%'");
$pdo->exec("DELETE FROM billing_requests WHERE visit_id IN (SELECT id FROM visits WHERE visit_number LIKE 'POP-%')");
$pdo->exec("DELETE FROM payments WHERE invoice_id IN (SELECT id FROM invoices WHERE visit_id IN (SELECT id FROM visits WHERE visit_number LIKE 'POP-%'))");
$pdo->exec("DELETE FROM patient_charges WHERE visit_id IN (SELECT id FROM visits WHERE visit_number LIKE 'POP-%')");
$pdo->exec("DELETE FROM invoices WHERE visit_id IN (SELECT id FROM visits WHERE visit_number LIKE 'POP-%')");
$pdo->exec("DELETE FROM visits WHERE visit_number LIKE 'POP-%'");

$doctor = popUser($pdo, 'dev_doctor');
$nurse = popUser($pdo, 'dev_nurse');
$accounts = popUser($pdo, 'dev_accounts');
$popTech = popEnsureTechnician($pdo);

$patientRows = $pdo->query("
    SELECT id
    FROM patients
    WHERE hospital_number IN ('DEV-PATIENT-0001','DEV-PATIENT-0002')
    ORDER BY hospital_number
")->fetchAll(PDO::FETCH_COLUMN);
assertPop(count($patientRows) === 2, 'Dedicated patient fixtures are missing.');
[$patientId, $otherPatientId] = array_map('intval', $patientRows);

$permissionService = new PermissionService($pdo);
$popService = new POPService($pdo, null, null, $permissionService);
$billingService = new BillingService($pdo);
$consultationService = new ConsultationService($pdo);
$plasterDepartmentId = (int)$pdo->query("SELECT id FROM departments WHERE department_name IN ('Plaster','POP','Casting') ORDER BY CASE department_name WHEN 'Plaster' THEN 0 WHEN 'POP' THEN 1 ELSE 2 END, id ASC LIMIT 1")->fetchColumn();
$pdo->prepare("
    INSERT INTO billable_items (item_code, item_name, item_type, department_id, description, unit_price, unit, is_active, created_by, created_at, updated_at)
    SELECT 'POP-AUTO', 'Plaster Procedure', 'Service', :department_id, 'Plaster fixture.', 2500.00, '', 1, :created_by, NOW(), NOW()
    WHERE NOT EXISTS (SELECT 1 FROM billable_items WHERE item_code = 'POP-AUTO')
")->execute([':department_id' => $plasterDepartmentId, ':created_by' => (int)$accounts['id']]);
$plasterBillableItemId = (int)$pdo->query("SELECT id FROM billable_items WHERE item_code = 'POP-AUTO' LIMIT 1")->fetchColumn();

$clinicalVisitId = popCreateEncounter($pdo, $doctor, $patientId, (int)$doctor['department_id'], 'Doctor', (string)time());
$directVisitId = popCreateEncounter($pdo, $popTech, $patientId, (int)$popTech['department_id'], 'Plaster', (string)(time() + 1));
$completedVisitId = popCreateEncounter($pdo, $doctor, $patientId, (int)$doctor['department_id'], 'Completed', (string)(time() + 2));

$clinical = requirePopSuccess($popService->createRequest([
    'visit_id' => $clinicalVisitId,
    'patient_id' => $patientId,
    'request_source' => 'Clinical',
    'procedure_requested' => 'Below-knee plaster cast',
    'clinical_indication' => 'Suspected ankle fracture.',
    'priority' => 'Urgent',
    'suggested_billable_item_id' => $plasterBillableItemId,
], $doctor), 'Clinical Plaster request');
$clinicalRequestId = (int)$clinical['pop_request_id'];
$pdo->prepare("UPDATE billing_requests SET status = 'Charged', reviewed_by = :reviewed_by, reviewed_at = NOW(), updated_at = NOW() WHERE source_module = 'POP' AND source_record_id = :source_record_id")
    ->execute([':reviewed_by' => (int)$accounts['id'], ':source_record_id' => $clinicalRequestId]);
$plasterInvoice = $billingService->getInvoiceByVisit($clinicalVisitId);
assertPop($plasterInvoice !== null, 'Plaster invoice was not created.');
requirePopSuccess($billingService->recordPayment([
    'invoice_id' => (int)$plasterInvoice['id'],
    'amount' => (float)$plasterInvoice['balance_due'],
    'payment_method' => 'Cash',
    'reference' => 'POP-PAID',
], $accounts), 'Pay Plaster invoice');
assertPop(popWorklistContains($popService->listWorklist($popTech, ['status' => 'Requested']), $clinicalRequestId), 'Plaster worklist did not show the clinical request.');

requirePopSuccess($popService->startRequest($clinicalRequestId, $popTech), 'Start Plaster request');
requirePopSuccess($popService->saveRecord([
    'pop_request_id' => $clinicalRequestId,
    'cast_type' => 'Below-knee plaster',
    'body_part' => 'Right ankle',
    'procedure_notes' => 'Plaster cast applied with limb supported and circulation checked.',
    'materials_used' => 'Plaster rolls, cotton wool, bandage.',
    'aftercare_instructions' => 'Keep dry and return immediately if swelling or numbness occurs.',
    'remarks' => 'Tolerated procedure.',
], $popTech), 'Save Plaster record');
requirePopSuccess($popService->updateRecord([
    'pop_request_id' => $clinicalRequestId,
    'cast_type' => 'Below-knee plaster',
    'body_part' => 'Right ankle',
    'procedure_notes' => 'Plaster cast applied; distal circulation and sensation intact.',
    'materials_used' => 'Plaster rolls, cotton wool, bandage.',
    'aftercare_instructions' => 'Keep dry and elevate limb.',
    'remarks' => 'Reviewed after setting.',
], $popTech), 'Update Plaster record');
requirePopSuccess($popService->completeRequest($clinicalRequestId, $popTech), 'Complete Plaster request');
$completed = $popService->getRequestById($clinicalRequestId, $doctor);
assertPop($completed !== null && (string)$completed['status'] === 'Completed', 'Completed Plaster request was not visible to Doctor.');

$readonly = $popService->updateRecord([
    'pop_request_id' => $clinicalRequestId,
    'procedure_notes' => 'Should not update',
], $popTech);
assertPop(($readonly['success'] ?? true) === false, 'Completed Plaster request accepted record update.');

$direct = $popService->createRequest([
    'visit_id' => $directVisitId,
    'patient_id' => $patientId,
    'request_source' => 'Direct',
    'procedure_requested' => 'Arm sling / plaster review',
    'clinical_indication' => 'Direct Plaster attendance.',
    'priority' => 'Routine',
], $popTech);
assertPop(($direct['success'] ?? true) === false, 'Plaster created a direct request unexpectedly.');

$mismatch = $popService->createRequest([
    'visit_id' => $clinicalVisitId,
    'patient_id' => $otherPatientId,
    'request_source' => 'Clinical',
    'procedure_requested' => 'Plaster cast',
], $doctor);
assertPop(($mismatch['success'] ?? true) === false, 'Patient/visit mismatch was accepted.');

$unauthorized = $popService->createRequest([
    'visit_id' => $clinicalVisitId,
    'patient_id' => $patientId,
    'request_source' => 'Clinical',
    'procedure_requested' => 'Plaster cast',
], $nurse);
assertPop(($unauthorized['success'] ?? true) === false, 'Nurse created a Plaster request unexpectedly.');

$locked = $popService->createRequest([
    'visit_id' => $completedVisitId,
    'patient_id' => $patientId,
    'request_source' => 'Clinical',
    'procedure_requested' => 'Plaster cast',
], $doctor);
assertPop(($locked['success'] ?? true) === false, 'Completed encounter accepted Plaster request.');

$auditCount = (int)$pdo->query("SELECT COUNT(*) FROM audit_logs WHERE action IN ('POP_REQUEST_CREATED','POP_REQUEST_COMPLETED')")->fetchColumn();
assertPop($auditCount > 0, 'Plaster audit records were not created.');

$eventCount = (int)$pdo->query("SELECT COUNT(*) FROM encounter_events WHERE event_type IN ('POP_REQUESTED','POP_COMPLETED')")->fetchColumn();
assertPop($eventCount > 0, 'Plaster encounter events were not created.');

fwrite(STDOUT, "Plaster workflow tests passed.\n");
