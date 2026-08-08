<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/test_database.php';
require_once __DIR__ . '/../services/AuditService.php';
require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../services/PatientService.php';
require_once __DIR__ . '/../services/SessionService.php';
require_once __DIR__ . '/../services/UserService.php';

final class FailingPatientAuditService extends AuditService
{
    public function log(
        ?int $userId,
        ?int $visitId,
        string $module,
        string $action,
        string $description,
        ?int $departmentId = null,
        string $severity = 'INFO',
        ?string $eventType = null
    ): bool {
        return false;
    }

    public function logPatient(
        ?int $userId,
        int $patientId,
        ?int $visitId,
        string $module,
        string $action,
        string $description,
        ?int $departmentId = null,
        string $severity = 'INFO',
        ?string $eventType = null
    ): bool {
        return false;
    }
}

function assertRemediation(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function patientFixture(string $unique, string $gender): array
{
    return [
        'first_name' => 'PhaseOneEight',
        'last_name' => 'Gender' . $unique,
        'gender' => $gender,
        'date_of_birth' => '1990-01-01',
        'phone' => '',
        'email' => '',
        'address' => 'Phase 1.8 remediation verification.',
        'blood_group' => '',
        'genotype' => '',
        'allergies' => '',
        'next_of_kin' => '',
        'next_of_kin_phone' => ''
    ];
}

function loadEnvironmentConfig(?string $environment, ?string $bypass): array
{
    $environment === null
        ? putenv('HMS_APP_ENV')
        : putenv('HMS_APP_ENV=' . $environment);

    $bypass === null
        ? putenv('HMS_ENABLE_DEV_AUTH_BYPASS')
        : putenv('HMS_ENABLE_DEV_AUTH_BYPASS=' . $bypass);

    return require __DIR__ . '/../config/app.php';
}

$admin = $pdo->query('
    SELECT u.*, d.department_name, r.role_name
    FROM users u
    INNER JOIN departments d ON d.id = u.department_id
    INNER JOIN roles r ON r.id = u.role_id
    WHERE r.role_name = \'System Administrator\'
    ORDER BY u.id
    LIMIT 1
')->fetch(PDO::FETCH_ASSOC);

assertRemediation((bool)$admin, 'An administrator account is required.');

$_SESSION['user'] = $admin;
$adminId = (int)$admin['id'];
$service = new PatientService($pdo);
$unique = date('YmdHis') . random_int(1000, 9999);

assertRemediation(
    PatientService::supportedGenders() === ['Male', 'Female', 'Other', 'Unknown'],
    'The supported patient gender contract is incorrect.'
);

$column = $pdo->query("SHOW COLUMNS FROM patients LIKE 'gender'")
    ->fetch(PDO::FETCH_ASSOC);
assertRemediation(
    ($column['Type'] ?? '') === "enum('Male','Female','Other','Unknown')",
    'The live patient gender enum is not aligned.'
);

$createdPatientIds = [];

foreach (PatientService::supportedGenders() as $index => $gender) {
    $fixture = patientFixture($unique . $index, $gender);
    $result = $service->createPatient($fixture, $adminId);

    assertRemediation(
        ($result['success'] ?? false) === true,
        $gender . ' patient registration failed.'
    );

    $createdPatientIds[] = (int)$result['patient_id'];
    $description = 'Registered patient ' . $result['hospital_number'] . '.';
    $audit = $pdo->prepare('
        SELECT COUNT(*)
        FROM audit_logs
        WHERE action = \'PATIENT_REGISTERED\'
          AND description = :description
    ');
    $audit->execute([':description' => $description]);

    assertRemediation(
        (int)$audit->fetchColumn() === 1,
        $gender . ' registration did not create exactly one audit record.'
    );
}

$invalid = $service->createPatient(
    patientFixture($unique . 'invalid', 'NotSupported'),
    $adminId
);
assertRemediation(!$invalid['success'], 'Unsupported patient gender was accepted.');
assertRemediation(
    in_array('Select a valid gender.', $invalid['errors'], true),
    'Unsupported patient gender did not return the expected validation error.'
);

$updateId = $createdPatientIds[0];
$updatedFixture = patientFixture($unique . '0', 'Unknown');
$updatedFixture['first_name'] = 'PhaseOneEightUpdated';
$updated = $service->updatePatient($updateId, $updatedFixture);
assertRemediation($updated['success'], 'Valid patient update failed.');

$updateAudit = $pdo->prepare('
    SELECT COUNT(*)
    FROM audit_logs
    WHERE action = \'DEMOGRAPHICS_UPDATED\'
      AND description = :description
');
$updateAudit->execute([
    ':description' => 'Updated demographics for patient #' . $updateId . '.'
]);
assertRemediation(
    (int)$updateAudit->fetchColumn() === 1,
    'Patient update did not create exactly one audit record.'
);

$failingAudit = new FailingPatientAuditService($pdo);
$failingService = new PatientService($pdo, $failingAudit);
$failedFixture = patientFixture($unique . 'rollback', 'Other');
$failedCreate = $failingService->createPatient($failedFixture, $adminId);
assertRemediation(!$failedCreate['success'], 'Audit failure allowed patient creation.');

$failedPatient = $pdo->prepare(
    'SELECT COUNT(*) FROM patients WHERE last_name = :last_name'
);
$failedPatient->execute([':last_name' => $failedFixture['last_name']]);
assertRemediation(
    (int)$failedPatient->fetchColumn() === 0,
    'Patient creation was not rolled back after audit failure.'
);

$beforeFailure = $service->getPatientById($updateId);
$failedUpdateFixture = $updatedFixture;
$failedUpdateFixture['first_name'] = 'MustRollback';
$failedUpdate = $failingService->updatePatient($updateId, $failedUpdateFixture);
assertRemediation(!$failedUpdate['success'], 'Audit failure allowed patient update.');
$afterFailure = $service->getPatientById($updateId);
assertRemediation(
    $afterFailure['first_name'] === $beforeFailure['first_name'],
    'Patient update was not rolled back after audit failure.'
);

$saveController = file_get_contents(__DIR__ . '/../modules/patients/save.php');
$updateController = file_get_contents(__DIR__ . '/../modules/patients/update.php');
assertRemediation(
    !str_contains($saveController, 'AuditService')
        && !str_contains($updateController, 'AuditService'),
    'A patient controller still owns audit logging.'
);

$originalEnvironment = getenv('HMS_APP_ENV');
$originalBypass = getenv('HMS_ENABLE_DEV_AUTH_BYPASS');

try {
    $config = loadEnvironmentConfig(null, null);
    assertRemediation(
        $config['app']['environment'] === 'production'
            && $config['app']['development_auth_bypass'] === false,
        'Missing environment did not fail closed.'
    );

    $config = loadEnvironmentConfig('invalid', 'true');
    assertRemediation(
        $config['app']['environment'] === 'production'
            && $config['app']['development_auth_bypass'] === false,
        'Invalid environment enabled authentication bypass.'
    );

    $config = loadEnvironmentConfig('development', null);
    assertRemediation(
        $config['app']['development_auth_bypass'] === false,
        'Development mode enabled bypass without the explicit flag.'
    );

    $config = loadEnvironmentConfig('development', 'true');
    assertRemediation(
        $config['app']['environment'] === 'development'
            && $config['app']['development_auth_bypass'] === true,
        'Explicit development bypass was not enabled.'
    );

    $_GET['HMS_APP_ENV'] = 'development';
    $_POST['HMS_ENABLE_DEV_AUTH_BYPASS'] = 'true';
    $_COOKIE['HMS_APP_ENV'] = 'development';
    $config = loadEnvironmentConfig(null, null);
    assertRemediation(
        $config['app']['environment'] === 'production'
            && $config['app']['development_auth_bypass'] === false,
        'Browser-controlled input affected environment resolution.'
    );
} finally {
    $originalEnvironment === false
        ? putenv('HMS_APP_ENV')
        : putenv('HMS_APP_ENV=' . $originalEnvironment);
    $originalBypass === false
        ? putenv('HMS_ENABLE_DEV_AUTH_BYPASS')
        : putenv('HMS_ENABLE_DEV_AUTH_BYPASS=' . $originalBypass);
}

$authService = new AuthService($pdo);
$fixturePassword = (string)getenv('HMS_DEV_FIXTURE_PASSWORD');
assertRemediation(
    strlen($fixturePassword) >= 12,
    'HMS_DEV_FIXTURE_PASSWORD is required for the isolated authentication test.'
);
$adminLogin = $authService->login('admin', $fixturePassword);
assertRemediation(
    ($adminLogin['success'] ?? false) === true,
    'The existing administrator login failed.'
);

$receptionRoleId = (int)$pdo->query(
    "SELECT id FROM roles WHERE role_name = 'Receptionist' LIMIT 1"
)->fetchColumn();
$receptionDepartmentId = (int)$pdo->query(
    "SELECT id FROM departments WHERE department_name = 'Reception' LIMIT 1"
)->fetchColumn();
assertRemediation(
    $receptionRoleId > 0 && $receptionDepartmentId > 0,
    'Reception role and department are required.'
);

$userService = new UserService($pdo);
$temporaryLogin = 'phase18.auth.' . strtolower($unique);
$temporaryUser = $userService->createUser([
    'employee_id' => 'P18-' . $unique,
    'first_name' => 'PhaseOneEight',
    'last_name' => 'Authentication',
    'gender' => 'Male',
    'phone' => null,
    'email' => null,
    'username' => $temporaryLogin,
    'password' => 'Phase18Auth@123',
    'department_id' => $receptionDepartmentId,
    'role_id' => $receptionRoleId,
    'status' => 'Active',
    'must_change_password' => 0
], $adminId);
assertRemediation($temporaryUser['success'], 'Department test user creation failed.');
$temporaryUserId = (int)$temporaryUser['user_id'];

try {
    $departmentLogin = $authService->login($temporaryLogin, 'Phase18Auth@123');
    assertRemediation(
        ($departmentLogin['success'] ?? false) === true,
        'Valid department-user login failed.'
    );

    assertRemediation(
        $userService->lockUser($temporaryUserId, $adminId, 'Phase 1.8 test.')['success'],
        'Temporary account lock failed.'
    );
    $lockedLogin = $authService->login($temporaryLogin, 'Phase18Auth@123');
    assertRemediation(
        !$lockedLogin['success'] && ($lockedLogin['code'] ?? 0) === 423,
        'Locked account was not rejected.'
    );

    assertRemediation(
        $userService->unlockUser($temporaryUserId, $adminId)['success'],
        'Temporary account unlock failed.'
    );
    assertRemediation(
        $userService->deactivateUser($temporaryUserId, $adminId)['success'],
        'Temporary account deactivation failed.'
    );
    $inactiveLogin = $authService->login($temporaryLogin, 'Phase18Auth@123');
    assertRemediation(
        !$inactiveLogin['success'] && ($inactiveLogin['code'] ?? 0) === 403,
        'Inactive account was not rejected.'
    );
} finally {
    $pdo->beginTransaction();
    $pdo->prepare('DELETE FROM active_sessions WHERE user_id = :user_id')
        ->execute([':user_id' => $temporaryUserId]);
    $pdo->prepare('DELETE FROM password_history WHERE user_id = :user_id')
        ->execute([':user_id' => $temporaryUserId]);
    $pdo->prepare('DELETE FROM user_departments WHERE user_id = :user_id')
        ->execute([':user_id' => $temporaryUserId]);
    $pdo->prepare('DELETE FROM users WHERE id = :user_id')
        ->execute([':user_id' => $temporaryUserId]);
    $pdo->commit();
}

$expiredSessionId = 'phase18-expired-' . strtolower($unique);
$expiredSession = $pdo->prepare('
    INSERT INTO active_sessions (
        session_id,
        user_id,
        login_at,
        last_activity,
        expires_at,
        active_department_id,
        status
    ) VALUES (
        :session_id,
        :user_id,
        DATE_SUB(NOW(), INTERVAL 2 HOUR),
        DATE_SUB(NOW(), INTERVAL 2 HOUR),
        DATE_SUB(NOW(), INTERVAL 1 HOUR),
        :department_id,
        \'Active\'
    )
');
$expiredSession->execute([
    ':session_id' => $expiredSessionId,
    ':user_id' => $adminId,
    ':department_id' => (int)$admin['department_id']
]);

try {
    $expired = (new SessionService($pdo))->terminateExpiredSessions();
    assertRemediation($expired['success'], 'Expired session cleanup failed.');
    $expiredStatus = $pdo->prepare(
        'SELECT status FROM active_sessions WHERE session_id = :session_id'
    );
    $expiredStatus->execute([':session_id' => $expiredSessionId]);
    assertRemediation(
        $expiredStatus->fetchColumn() === 'Expired',
        'Expired session remained active.'
    );
} finally {
    $pdo->prepare('DELETE FROM active_sessions WHERE session_id = :session_id')
        ->execute([':session_id' => $expiredSessionId]);
}

echo 'PASS: Phase 1.8 patient gender, audit ownership, rollback, and environment safety remediation.' . PHP_EOL;
