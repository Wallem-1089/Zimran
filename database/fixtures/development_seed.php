<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    throw new RuntimeException('Development fixtures are CLI-only.');
}

$config = require dirname(__DIR__, 2) . '/config/app.php';
$environment = (string)($config['app']['environment'] ?? 'production');
if (!in_array($environment, ['development', 'testing'], true)
    || getenv('HMS_ALLOW_DEV_FIXTURES') !== '1'
) {
    throw new RuntimeException(
        'Fixtures require HMS_APP_ENV=development and HMS_ALLOW_DEV_FIXTURES=1.'
    );
}

$password = (string)getenv('HMS_DEV_FIXTURE_PASSWORD');
if (strlen($password) < 12) {
    throw new RuntimeException('HMS_DEV_FIXTURE_PASSWORD must contain at least 12 characters.');
}

$database = $config['database'];
if ($environment === 'testing') {
    require_once dirname(__DIR__) . '/tools/DatabaseSafety.php';
    $resolved = DatabaseSafety::resolveTestDatabase($config);
    $database['name'] = $resolved['test'];
    fwrite(STDOUT, 'Fixture target test database: ' . $database['name'] . PHP_EOL);
}
$pdo = new PDO(
    'mysql:host=' . $database['host'] . ';dbname=' . $database['name']
        . ';charset=utf8mb4',
    $database['user'],
    $database['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$fixtures = [
    ['DEV-WALTER-001', 'Walter', 'Ikhile', 'walter', 'Super Administrator', 'Super Administrator'],
    ['DEV-REC-001', 'Development', 'Receptionist', 'dev_reception', 'Reception', 'Receptionist'],
    ['DEV-REC-002', 'Development', 'Records', 'dev_records', 'Records', 'Records Officer'],
    ['DEV-NUR-001', 'Development', 'Nurse', 'dev_nurse', 'Nursing', 'Nurse'],
    ['DEV-DOC-001', 'Amara', 'Okafor', 'dev_doctor', 'Doctor', 'Doctor'],
    ['DEV-LAB-001', 'Development', 'Laboratory', 'dev_laboratory', 'Laboratory', 'Laboratory Scientist'],
    ['DEV-RAD-001', 'Development', 'Radiology', 'dev_radiology', 'X-Ray', 'Radiographer'],
    ['DEV-PHA-001', 'Development', 'Pharmacy', 'dev_pharmacy', 'Pharmacy', 'Pharmacist'],
    ['DEV-ACC-001', 'Development', 'Accounts', 'dev_accounts', 'Accounts', 'Accountant']
];

$pdo->beginTransaction();
try {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $adminPassword = $pdo->prepare('
        UPDATE users
        SET password = :password, must_change_password = 1,
            status = \'Active\', failed_login_attempts = 0,
            locked_at = NULL, locked_by = NULL, lock_reason = NULL
        WHERE username = \'admin\' AND employee_id = \'EMP000001\'
    ');
    $adminPassword->execute([':password' => $hash]);
    $insert = $pdo->prepare('
        INSERT INTO users (
            employee_id, first_name, last_name, gender, email, username,
            password, department_id, role_id, status, must_change_password
        )
        SELECT :employee_id, :first_name, :last_name, \'Female\', :email,
               :username, :password, d.id, r.id, \'Active\', 1
        FROM departments d INNER JOIN roles r
        WHERE d.department_name = :department_name
          AND r.role_name = :role_name
        ON DUPLICATE KEY UPDATE
            first_name = VALUES(first_name), last_name = VALUES(last_name),
            department_id = VALUES(department_id), role_id = VALUES(role_id),
            status = \'Active\', must_change_password = 1
    ');
    $membership = $pdo->prepare('
        INSERT INTO user_departments (
            user_id, department_id, is_primary, is_active, assigned_by
        ) SELECT u.id, u.department_id, 1, 1, 1
          FROM users u WHERE u.username = :username
        ON DUPLICATE KEY UPDATE is_primary = 1, is_active = 1
    ');

    foreach ($fixtures as $fixture) {
        [$employeeId, $firstName, $lastName, $username, $department, $role] = $fixture;
        $insert->execute([
            ':employee_id' => $employeeId,
            ':first_name' => $firstName,
            ':last_name' => $lastName,
            ':email' => $username . '@development.invalid',
            ':username' => $username,
            ':password' => $hash,
            ':department_name' => $department,
            ':role_name' => $role
        ]);
        $membership->execute([':username' => $username]);
    }

    $patients = [
        ['DEV-PATIENT-0001', 'Development', 'PatientOne', 'Unknown', '1985-01-15', '08000000001'],
        ['DEV-PATIENT-0002', 'Development', 'PatientTwo', 'Unknown', '1992-06-30', '08000000002']
    ];
    $patientInsert = $pdo->prepare('
        INSERT INTO patients (
            hospital_number, first_name, normalized_first_name,
            last_name, normalized_last_name, gender, date_of_birth,
            phone, normalized_phone, registered_by
        ) VALUES (
            :hospital_number, :first_name, :normalized_first_name,
            :last_name, :normalized_last_name, :gender, :date_of_birth,
            :phone, :normalized_phone, 1
        ) ON DUPLICATE KEY UPDATE hospital_number = VALUES(hospital_number)
    ');
    foreach ($patients as $patient) {
        [$number, $first, $last, $gender, $dob, $phone] = $patient;
        $patientInsert->execute([
            ':hospital_number' => $number,
            ':first_name' => $first,
            ':normalized_first_name' => strtolower($first),
            ':last_name' => $last,
            ':normalized_last_name' => strtolower($last),
            ':gender' => $gender,
            ':date_of_birth' => $dob,
            ':phone' => $phone,
            ':normalized_phone' => $phone
        ]);
    }

    $pdo->commit();
    fwrite(STDOUT, 'Deterministic development fixtures applied.' . PHP_EOL);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $exception;
}
