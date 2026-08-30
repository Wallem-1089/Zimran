<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/test_database.php';
require_once __DIR__ . '/../database/tools/DatabaseSafety.php';
require_once __DIR__ . '/../database/tools/MigrationManager.php';
require_once __DIR__ . '/../services/PermissionService.php';

function assertUserPermissionOverride(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$config = require __DIR__ . '/../config/app.php';
$resolved = DatabaseSafety::resolveTestDatabase($config);
$databaseName = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
assertUserPermissionOverride($databaseName === $resolved['test'] && $databaseName !== $resolved['live'], 'User permission override test is not isolated from live.');

$manager = new MigrationManager($pdo, $databaseName);
$manager->ensureLedger();
$manager->apply(__DIR__ . '/../database/migrations/056_user_permission_overrides_up.sql', 56);

$permissionService = new PermissionService($pdo);

$doctorRoleId = (int)$pdo->query("SELECT id FROM roles WHERE role_name = 'Doctor' LIMIT 1")->fetchColumn();
$nurseRoleId = (int)$pdo->query("SELECT id FROM roles WHERE role_name = 'Nurse' LIMIT 1")->fetchColumn();
$doctorDepartmentId = (int)$pdo->query("SELECT id FROM departments WHERE department_name = 'Doctor' LIMIT 1")->fetchColumn();
$nursingDepartmentId = (int)$pdo->query("SELECT id FROM departments WHERE department_name = 'Nursing' LIMIT 1")->fetchColumn();

assertUserPermissionOverride($doctorRoleId > 0 && $doctorDepartmentId > 0, 'Doctor role/department seed is missing.');
assertUserPermissionOverride($nurseRoleId > 0 && $nursingDepartmentId > 0, 'Nurse role/department seed is missing.');

$pdo->exec("DELETE FROM user_permissions WHERE user_id IN (SELECT id FROM users WHERE username IN ('perm_override_doctor','perm_override_nurse'))");
$pdo->exec("DELETE FROM users WHERE username IN ('perm_override_doctor','perm_override_nurse')");

$insertUser = $pdo->prepare("
    INSERT INTO users (
        employee_id, first_name, last_name, gender, username, password,
        department_id, role_id, status
    ) VALUES (
        :employee_id, :first_name, :last_name, :gender, :username, :password,
        :department_id, :role_id, 'Active'
    )
");
$insertUser->execute([
    ':employee_id' => 'PERM-DOC',
    ':first_name' => 'Permission',
    ':last_name' => 'Doctor',
    ':gender' => 'Male',
    ':username' => 'perm_override_doctor',
    ':password' => password_hash('test-password', PASSWORD_DEFAULT),
    ':department_id' => $doctorDepartmentId,
    ':role_id' => $doctorRoleId,
]);
$insertUser->execute([
    ':employee_id' => 'PERM-NUR',
    ':first_name' => 'Permission',
    ':last_name' => 'Nurse',
    ':gender' => 'Female',
    ':username' => 'perm_override_nurse',
    ':password' => password_hash('test-password', PASSWORD_DEFAULT),
    ':department_id' => $nursingDepartmentId,
    ':role_id' => $nurseRoleId,
]);

$rows = $pdo->query("
    SELECT u.*, r.role_name, d.department_name
    FROM users u
    INNER JOIN roles r ON r.id = u.role_id
    INNER JOIN departments d ON d.id = u.department_id
    WHERE u.username IN ('admin','perm_override_doctor','perm_override_nurse')
")->fetchAll(PDO::FETCH_ASSOC);

$users = [];
foreach ($rows as $row) {
    $users[(string)$row['username']] = $row;
}

foreach (['admin', 'perm_override_doctor', 'perm_override_nurse'] as $username) {
    assertUserPermissionOverride(isset($users[$username]), 'Missing fixture user ' . $username . '.');
}

$admin = $users['admin'];
$doctor = $users['perm_override_doctor'];
$nurse = $users['perm_override_nurse'];

$permissionIds = $pdo->query("
    SELECT permission_key, id
    FROM permissions
    WHERE permission_key IN ('use_consultation_handwriting','create_vital_signs')
")->fetchAll(PDO::FETCH_KEY_PAIR);

assertUserPermissionOverride(isset($permissionIds['use_consultation_handwriting']), 'Missing Consultation handwriting permission.');
assertUserPermissionOverride(isset($permissionIds['create_vital_signs']), 'Missing create vital signs permission.');

$doctorId = (int)$doctor['id'];
$nurseId = (int)$nurse['id'];
$pdo->prepare('DELETE FROM user_permissions WHERE user_id IN (?, ?)')
    ->execute([$doctorId, $nurseId]);

assertUserPermissionOverride($permissionService->canUseConsultationHandwriting($doctor), 'Doctor should inherit Consultation handwriting by role.');
assertUserPermissionOverride($permissionService->hasPermission('create_vital_signs', $nurse), 'Nurse should inherit Vital Signs creation by role.');

$denyDoctorWriting = $permissionService->assignUserPermissionOverrides(
    $doctorId,
    [(int)$permissionIds['use_consultation_handwriting'] => 'Deny'],
    (int)$admin['id']
);
assertUserPermissionOverride(($denyDoctorWriting['success'] ?? false) === true, 'Doctor handwriting deny override should save.');
assertUserPermissionOverride(!$permissionService->canUseConsultationHandwriting($doctor), 'User-level deny should hide Consultation handwriting for only this doctor.');
assertUserPermissionOverride($permissionService->hasPermission('create_consultation', $doctor), 'Denying handwriting should not remove normal Consultation CRUD.');

$denyNurseVitals = $permissionService->assignUserPermissionOverrides(
    $nurseId,
    [(int)$permissionIds['create_vital_signs'] => 'Deny'],
    (int)$admin['id']
);
assertUserPermissionOverride(($denyNurseVitals['success'] ?? false) === true, 'Nurse vital signs deny override should save.');
assertUserPermissionOverride(!$permissionService->hasPermission('create_vital_signs', $nurse), 'User-level deny should override inherited Nurse Vital Signs creation.');

$allowNurseWriting = $permissionService->assignUserPermissionOverrides(
    $nurseId,
    [
        (int)$permissionIds['create_vital_signs'] => 'Deny',
        (int)$permissionIds['use_consultation_handwriting'] => 'Allow',
    ],
    (int)$admin['id']
);
assertUserPermissionOverride(($allowNurseWriting['success'] ?? false) === true, 'Nurse handwriting allow override should save.');
assertUserPermissionOverride($permissionService->canUseConsultationHandwriting($nurse), 'User-level allow should grant Consultation handwriting to this account.');

$pdo->prepare('DELETE FROM user_permissions WHERE user_id IN (?, ?)')
    ->execute([$doctorId, $nurseId]);

$pdo->exec("DELETE FROM users WHERE username IN ('perm_override_doctor','perm_override_nurse')");

echo "User permission overrides test passed.\n";
