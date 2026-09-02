<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/test_database.php';
require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../services/DashboardService.php';
require_once __DIR__ . '/../services/DepartmentService.php';
require_once __DIR__ . '/../services/PatientService.php';
require_once __DIR__ . '/../services/PermissionService.php';
require_once __DIR__ . '/../services/QueueService.php';
require_once __DIR__ . '/../services/RoleService.php';
require_once __DIR__ . '/../services/SessionService.php';
require_once __DIR__ . '/../services/UserDepartmentService.php';
require_once __DIR__ . '/../services/UserService.php';
require_once __DIR__ . '/../services/VisitService.php';

function assertRegression(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function successful(array $result, string $operation): array
{
    assertRegression(
        ($result['success'] ?? false) === true,
        $operation . ' failed: ' . implode(' ', $result['errors'] ?? [])
    );

    return $result;
}

$admin = $pdo->query('
    SELECT u.*, d.department_name, r.role_name
    FROM users u
    INNER JOIN departments d ON d.id = u.department_id
    INNER JOIN roles r ON r.id = u.role_id
    WHERE u.username = \'walter\'
    ORDER BY u.id
    LIMIT 1
')->fetch(PDO::FETCH_ASSOC);

assertRegression((bool)$admin, 'A super administrator account is required.');

$_SESSION['user'] = $admin;
$_SESSION['active_department_id'] = (int)$admin['department_id'];
$_SESSION['active_department_name'] = (string)$admin['department_name'];
$_SESSION['user']['active_department_id'] = (int)$admin['department_id'];

$adminId = (int)$admin['id'];
$temporaryUserId = null;
$temporarySecondaryAssignment = false;

try {
    $userService = new UserService($pdo);
    $departmentService = new DepartmentService($pdo);
    $userDepartmentService = new UserDepartmentService($pdo);
    $roleService = new RoleService($pdo);
    $permissionService = new PermissionService($pdo);
    $patientService = new PatientService($pdo);
    $visitService = new VisitService($pdo);
    $queueService = new QueueService($pdo);
    $authService = new AuthService($pdo);
    $dashboardService = new DashboardService($pdo);

    $staleRegressionVisits = $pdo->query("
        SELECT v.id
        FROM visits v
        INNER JOIN patients p ON p.id = v.patient_id
        WHERE p.last_name = 'RegressionPatient'
          AND v.visit_status NOT IN ('Completed', 'Cancelled')
        ORDER BY v.id
    ")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($staleRegressionVisits as $staleVisitId) {
        successful(
            $visitService->updateStatus((int)$staleVisitId, 'Cancelled'),
            'Stale regression encounter cleanup'
        );
    }

    assertRegression($permissionService->isAdministrator($admin), 'Super Administrator override failed.');
    assertRegression($permissionService->canManageUsers($admin), 'Administrator user management permission failed.');
    assertRegression($permissionService->canManageSettings($admin), 'Administrator settings permission failed.');

    $roles = $roleService->listRoles();
    $departments = $departmentService->listDepartments();
    $permissions = $permissionService->listPermissions();
    assertRegression($roles !== [] && $departments !== [] && $permissions !== [], 'Administration catalogues are unavailable.');

    $duplicateRole = $roleService->createRole(
        (string)$roles[0]['role_name'],
        'Regression duplicate check.',
        $adminId
    );
    assertRegression(!$duplicateRole['success'], 'Duplicate role creation was not prevented.');

    $duplicateDepartment = $departmentService->createDepartment([
        'department_name' => (string)$departments[0]['department_name'],
        'department_code' => (string)$departments[0]['department_code'],
        'description' => 'Regression duplicate check.',
        'location' => null,
        'contact_extension' => null,
        'department_type' => 'Support',
        'queue_enabled' => 0,
        'is_active' => 1,
        'display_order' => 999
    ], $adminId);
    assertRegression(!$duplicateDepartment['success'], 'Duplicate department creation was not prevented.');

    $receptionRole = $pdo->query("SELECT id FROM roles WHERE role_name = 'Receptionist' LIMIT 1")->fetchColumn();
    assertRegression((bool)$receptionRole, 'Receptionist role is required.');

    $unique = date('YmdHis') . '-' . random_int(1000, 9999);
    $createdUser = successful($userService->createUser([
        'employee_id' => 'REG-' . $unique,
        'first_name' => 'PhaseOne',
        'last_name' => 'Regression',
        'gender' => 'Male',
        'phone' => null,
        'email' => null,
        'username' => 'phase1.regression.' . strtolower($unique),
        'password' => 'Regression@123',
        'department_id' => 2,
        'role_id' => (int)$receptionRole,
        'status' => 'Active',
        'must_change_password' => 1
    ], $adminId), 'User creation');
    $temporaryUserId = (int)$createdUser['user_id'];

    $primaryMemberships = (int)$pdo->query(
        'SELECT COUNT(*) FROM user_departments WHERE user_id = '
        . $temporaryUserId . ' AND department_id = 2 AND is_primary = 1 AND is_active = 1'
    )->fetchColumn();
    assertRegression($primaryMemberships === 1, 'User primary department was not synchronized on creation.');

    $auditFailurePropagated = false;
    try {
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE users SET first_name = \'ShouldRollback\' WHERE id = :id')
            ->execute([':id' => $temporaryUserId]);
        (new AuditService($pdo))->log(
            $adminId,
            null,
            'Regression',
            'AUDIT_FAILURE_TEST',
            'This write must fail and roll back.',
            999999999
        );
        $pdo->commit();
    } catch (Throwable $exception) {
        $auditFailurePropagated = true;
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
    assertRegression($auditFailurePropagated, 'Nested audit failure did not propagate to its transaction owner.');
    $rolledBackName = $pdo->query('SELECT first_name FROM users WHERE id = ' . $temporaryUserId)->fetchColumn();
    assertRegression($rolledBackName === 'PhaseOne', 'Business state committed after an audit failure.');

    successful($userService->updateUser($temporaryUserId, [
        'employee_id' => 'REG-' . $unique,
        'first_name' => 'PhaseOne',
        'last_name' => 'Regression',
        'gender' => 'Male',
        'phone' => null,
        'email' => null,
        'username' => 'phase1.regression.' . strtolower($unique),
        'department_id' => 4,
        'role_id' => (int)$receptionRole,
        'status' => 'Active',
        'must_change_password' => 1
    ], $adminId), 'User update');

    $primaryMemberships = (int)$pdo->query(
        'SELECT COUNT(*) FROM user_departments WHERE user_id = '
        . $temporaryUserId . ' AND department_id = 4 AND is_primary = 1 AND is_active = 1'
    )->fetchColumn();
    assertRegression($primaryMemberships === 1, 'User primary department was not synchronized on update.');

    successful($userService->resetPassword($temporaryUserId, 'Regression@456', $adminId), 'Password reset');
    assertRegression(count($userService->getPasswordHistory($temporaryUserId)) === 1, 'Password reset history was not recorded.');
    successful($userService->lockUser($temporaryUserId, $adminId, 'Regression test.'), 'Account lock');
    successful($userService->unlockUser($temporaryUserId, $adminId), 'Account unlock');
    successful($userService->deactivateUser($temporaryUserId, $adminId), 'User deactivation');

    $adminHadReception = (bool)$pdo->query(
        'SELECT 1 FROM user_departments WHERE user_id = ' . $adminId
        . ' AND department_id = 2 AND is_active = 1 LIMIT 1'
    )->fetchColumn();

    if (!$adminHadReception) {
        successful($userDepartmentService->assignDepartment($adminId, 2, $adminId), 'Secondary department assignment');
        $temporarySecondaryAssignment = true;
    }

    successful($userDepartmentService->switchDepartment($adminId, 2, $adminId), 'Active department switch');
    successful($userDepartmentService->switchDepartment($adminId, (int)$admin['department_id'], $adminId), 'Primary department switch');

    if ($temporarySecondaryAssignment) {
        successful($userDepartmentService->removeDepartment($adminId, 2, $adminId), 'Secondary department removal');
        $temporarySecondaryAssignment = false;
    }

    $passwordHash = $authService->hashPassword('ContractTest@123');
    assertRegression($authService->verifyPassword('ContractTest@123', $passwordHash), 'Password hashing contract failed.');

    $patient = successful($patientService->createPatient([
        'first_name' => 'PhaseOne',
        'last_name' => 'RegressionPatient',
        'gender' => 'Other',
        'date_of_birth' => '1990-01-01',
        'phone' => 'REG-' . substr($unique, -8),
        'email' => '',
        'address' => 'Automated regression verification record.',
        'blood_group' => '',
        'genotype' => '',
        'allergies' => '',
        'next_of_kin' => '',
        'next_of_kin_phone' => ''
    ], $adminId), 'Patient registration');
    $patientId = (int)$patient['patient_id'];

    successful($patientService->updatePatient($patientId, [
        'first_name' => 'PhaseOne',
        'last_name' => 'RegressionPatient',
        'gender' => 'Other',
        'date_of_birth' => '1990-01-01',
        'phone' => 'REG-' . substr($unique, -8),
        'email' => '',
        'address' => 'Verified automated regression record.',
        'blood_group' => '',
        'genotype' => '',
        'allergies' => '',
        'next_of_kin' => '',
        'next_of_kin_phone' => ''
    ]), 'Patient update');

    $invalidEncounter = $visitService->createVisit([
        'patient_id' => $patientId,
        'visit_date' => date('Y-m-d H:i:s'),
        'visit_type' => 'Outpatient',
        'current_department_id' => 1
    ], $adminId);
    assertRegression(!$invalidEncounter['success'], 'Unsupported department status was accepted.');

    $encounter = successful($visitService->createVisit([
        'patient_id' => $patientId,
        'visit_date' => date('Y-m-d H:i:s'),
        'visit_type' => 'Outpatient',
        'current_department_id' => 2
    ], $adminId), 'Encounter creation');
    $visitId = (int)$encounter['visit_id'];

    $initialQueue = $visitService->getQueueEntryForVisit($visitId);
    assertRegression((bool)$initialQueue && $initialQueue['queue_status'] === 'Waiting', 'Initial queue entry is missing.');

    successful($visitService->updateVisit($visitId, [
        'visit_type' => 'Outpatient',
        'current_department_id' => 2,
        'attending_doctor_id' => null
    ], $adminId), 'Encounter administrative update');

    $bypassedTransfer = $visitService->updateVisit($visitId, [
        'visit_type' => 'Outpatient',
        'current_department_id' => 5,
        'attending_doctor_id' => null
    ], $adminId);
    assertRegression(!$bypassedTransfer['success'], 'Encounter edit bypassed the transfer workflow.');

    $duplicateQueue = $visitService->enqueueEncounter($visitId, 2, $adminId);
    assertRegression(!$duplicateQueue['success'], 'Duplicate active queue entry was not prevented.');

    $called = successful($visitService->callNextPatient(2, $adminId), 'Queue call');
    assertRegression((int)$called['visit_id'] === $visitId, 'Queue ordering returned the wrong encounter.');
    successful($visitService->startService((int)$called['queue_id'], $adminId), 'Queue service start');
    successful($visitService->completeQueueEntry((int)$called['queue_id'], $adminId), 'Queue service completion');

    $invalidTransfer = $visitService->transferVisit($visitId, 1, $adminId);
    assertRegression(!$invalidTransfer['success'], 'Unsupported workflow department accepted a transfer.');

    successful($visitService->transferVisit($visitId, 5, $adminId), 'Transfer to Nursing');
    $nursingQueue = $visitService->getQueueEntryForVisit($visitId);
    assertRegression((bool)$nursingQueue, 'Transferred encounter was not queued.');
    $nursingCalled = successful($visitService->callNextPatient(5, $adminId), 'Nursing queue call');
    $prematureStart = $visitService->startService((int)$nursingCalled['queue_id'], $adminId);
    assertRegression(!$prematureStart['success'], 'Pending transfer started service before receipt.');
    successful($visitService->receiveVisit($visitId, $adminId), 'Nursing receipt');
    successful($visitService->startService((int)$nursingCalled['queue_id'], $adminId), 'Nursing service start');
    successful($visitService->completeQueueEntry((int)$nursingCalled['queue_id'], $adminId), 'Nursing service completion');

    successful($visitService->transferVisit($visitId, 4, $adminId), 'Transfer to Doctor');
    successful($visitService->receiveVisit($visitId, $adminId), 'Doctor receipt');
    $doctorId = (int)$pdo->query("SELECT u.id FROM users u INNER JOIN roles r ON r.id = u.role_id WHERE r.role_name = 'Doctor' AND u.department_id = 4 AND u.status = 'Active' ORDER BY u.id LIMIT 1")->fetchColumn();
    assertRegression($doctorId > 0, 'An active Doctor account is required.');
    successful($visitService->assignDoctor($visitId, $doctorId, $adminId), 'Doctor assignment');
    successful($visitService->updateStatus($visitId, 'Completed'), 'Encounter completion');

    assertRegression(!$visitService->transferVisit($visitId, 5, $adminId)['success'], 'Completed encounter allowed transfer.');
    assertRegression(!$visitService->assignDoctor($visitId, $doctorId, $adminId)['success'], 'Completed encounter allowed doctor assignment.');
    assertRegression(!$visitService->enqueueEncounter($visitId, 4, $adminId)['success'], 'Completed encounter entered a queue.');
    assertRegression(!$visitService->updateStatus($visitId, 'Doctor')['success'], 'Completed encounter status was reopened.');

    $timeline = $visitService->getVisitTimeline($visitId);
    $activeDatabase = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
    assertRegression(
        $activeDatabase === (string)getenv('HMS_TEST_DB_NAME'),
        'Regression PDO changed away from the dedicated test database.'
    );
    $eventCount = (int)$pdo->query(
        'SELECT COUNT(*) FROM encounter_events WHERE visit_id = ' . $visitId
    )->fetchColumn();
    $eventExists = $pdo->prepare('
        SELECT COUNT(*) FROM encounter_events
        WHERE visit_id = :visit_id AND event_type = :event_type
    ');
    foreach (['ENCOUNTER_CREATED', 'QUEUED', 'CALLED', 'SERVICE_STARTED', 'SERVICE_COMPLETED', 'TRANSFERRED', 'PATIENT_RECEIVED', 'DOCTOR_ASSIGNED', 'STATUS_CHANGED'] as $eventType) {
        $eventExists->execute([
            ':visit_id' => $visitId,
            ':event_type' => $eventType
        ]);
        $matchedEventCount = (int)$eventExists->fetchColumn();
        assertRegression(
            $matchedEventCount > 0,
            'Missing encounter event: ' . $eventType . ' for visit #' . $visitId
                . ' (total events visible: ' . $eventCount . ')'
        );
    }
    assertRegression(count($timeline) >= $eventCount, 'Encounter timeline did not include recorded workflow history.');

    $auditCount = $pdo->prepare('SELECT COUNT(*) FROM audit_logs WHERE visit_id = :visit_id');
    $auditCount->execute([':visit_id' => $visitId]);
    assertRegression((int)$auditCount->fetchColumn() >= 10, 'Encounter workflow audit trail is incomplete.');

    $dashboard = $dashboardService->getAdministratorDashboard();
    assertRegression(($dashboard['success'] ?? false) === true, 'Administrator dashboard failed to load.');
    assertRegression(
        isset(
            $dashboard['data']['users'],
            $dashboard['data']['departments'],
            $dashboard['data']['encounters'],
            $dashboard['data']['queue']
        ),
        'Administrator dashboard data contract is incomplete.'
    );

    $sessionService = new SessionService($pdo);
    $sessionService->login($admin);
    $activeSessions = $sessionService->getAllActiveSessions(['user_id' => $adminId]);
    $currentSession = array_values(array_filter(
        $activeSessions,
        static fn (array $session): bool => $session['session_id'] === session_id()
    ));
    assertRegression($currentSession !== [], 'Persistent session was not registered.');
    successful(
        $sessionService->terminateSession((int)$currentSession[0]['id'], $adminId, 'Regression verification.'),
        'Session termination'
    );

    echo 'PASS: Phase 1 administration, security, patient, encounter, queue, transfer, receive, assignment, lifecycle, timeline, and audit regression.' . PHP_EOL;
} finally {
    if ($temporarySecondaryAssignment) {
        $pdo->prepare('UPDATE user_departments SET is_active = 0, is_primary = 0 WHERE user_id = :user_id AND department_id = 2')
            ->execute([':user_id' => $adminId]);
    }

    if ($temporaryUserId !== null) {
        $pdo->beginTransaction();
        $pdo->prepare('DELETE FROM active_sessions WHERE user_id = :user_id')->execute([':user_id' => $temporaryUserId]);
        $pdo->prepare('DELETE FROM password_history WHERE user_id = :user_id')->execute([':user_id' => $temporaryUserId]);
        $pdo->prepare('DELETE FROM user_departments WHERE user_id = :user_id')->execute([':user_id' => $temporaryUserId]);
        $pdo->prepare('DELETE FROM users WHERE id = :user_id')->execute([':user_id' => $temporaryUserId]);
        $pdo->commit();
    }
}
