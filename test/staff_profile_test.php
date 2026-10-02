<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/test_database.php';
require_once __DIR__ . '/../database/tools/DatabaseSafety.php';
require_once __DIR__ . '/../database/tools/MigrationManager.php';
require_once __DIR__ . '/../services/StaffProfileService.php';

function assertStaffProfile(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$config = require __DIR__ . '/../config/app.php';
$resolved = DatabaseSafety::resolveTestDatabase($config);
$databaseName = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
assertStaffProfile(
    $databaseName === $resolved['test'] && $databaseName !== $resolved['live'],
    'Staff profile tests are not isolated from the live database.'
);

$manager = new MigrationManager($pdo, $databaseName);
$manager->ensureLedger();
$manager->apply(__DIR__ . '/../database/migrations/074_staff_profiles_up.sql', 74);

$user = $pdo->query("SELECT id, employee_id, first_name, last_name, gender, phone, email FROM users WHERE username = 'dev_doctor' LIMIT 1")
    ->fetch(PDO::FETCH_ASSOC);
assertStaffProfile((bool)$user, 'Missing dev_doctor test user.');

$service = new StaffProfileService($pdo);
$default = $service->defaultProfileFromUser($user);
assertStaffProfile($default['surname'] === $user['last_name'], 'Default profile did not use user surname.');
assertStaffProfile($default['employee_no'] === $user['employee_id'], 'Default profile did not use employee number.');

$profilePayload = [
    'surname' => 'Doctor',
    'first_name' => 'Development',
    'middle_name' => 'Test',
    'title' => 'Dr',
    'gender' => 'Male',
    'date_of_birth' => '1980-01-02',
    'place_of_birth' => 'Lagos',
    'marital_status' => 'Married',
    'home_town' => 'Ikeja',
    'permanent_home_address' => 'Permanent address',
    'residential_address' => 'Residential address',
    'phone_no' => '08000000000',
    'personal_email' => 'personal@example.test',
    'religion' => 'Christianity',
    'national_identity_number' => '12345678901',
    'first_appointment_date' => '2010-03-04',
    'first_appointment_salary_grade' => 'GL 10',
    'employee_no' => 'DEV-DOC-001',
    'official_email' => 'doctor@example.test',
    'salary_bank' => 'Demo Bank',
    'salary_bank_account_no' => '0123456789',
    'next_of_kin1_name' => 'Kin One',
    'next_of_kin1_address' => 'Kin address one',
    'next_of_kin1_date_of_birth' => '1970-01-01',
    'next_of_kin1_relationship' => 'Spouse',
    'next_of_kin1_phone' => '08000000001',
    'next_of_kin2_name' => 'Kin Two',
    'next_of_kin2_address' => 'Kin address two',
    'next_of_kin2_date_of_birth' => '1975-01-01',
    'next_of_kin2_relationship' => 'Sibling',
    'next_of_kin2_phone' => '08000000002',
    'education' => [
        [
            'school_attended_with_dates' => 'Demo University 2000-2005',
            'course_of_study' => 'Medicine',
            'certificates_obtained' => 'MBBS',
            'class_of_degree' => 'Second Class Upper',
            'year_of_graduation' => '2005',
            'remarks' => 'Verified',
        ],
        [
            'school_attended_with_dates' => 'Demo College 1998-2000',
            'certificates_obtained' => 'A Levels',
        ],
    ],
    'professional' => [
        [
            'certification_type' => 'License',
            'certificates' => 'Medical License',
            'certification_license_no' => 'LIC-001',
            'certification_expiration_date' => '2030-12-31',
            'year_attained' => '2006',
            'remarks' => 'Current',
        ],
    ],
];

$result = $service->saveProfile((int)$user['id'], $profilePayload, 1);

assertStaffProfile(($result['success'] ?? false) === true, 'Staff profile save failed: ' . implode(' ', $result['errors'] ?? []));

$loaded = $service->getProfile((int)$user['id']);
assertStaffProfile((string)$loaded['profile']['surname'] === 'Doctor', 'Saved profile surname was not loaded.');
assertStaffProfile(count($loaded['education']) === 2, 'Educational qualification rows were not saved.');
assertStaffProfile(count($loaded['professional']) === 1, 'Professional qualification rows were not saved.');

$pngBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==', true);
assertStaffProfile(is_string($pngBytes), 'Unable to prepare profile photo fixture.');
$tmpPhoto = tempnam(sys_get_temp_dir(), 'staff-profile-photo-');
assertStaffProfile(is_string($tmpPhoto) && $tmpPhoto !== '', 'Unable to create temporary profile photo.');
file_put_contents($tmpPhoto, $pngBytes);
$photoResult = $service->saveProfile((int)$user['id'], $profilePayload, 1, [
    'profile_photo' => [
        'name' => 'profile.png',
        'type' => 'image/png',
        'tmp_name' => $tmpPhoto,
        'error' => UPLOAD_ERR_OK,
        'size' => strlen($pngBytes),
    ],
]);
assertStaffProfile(($photoResult['success'] ?? false) === true, 'Staff profile photo save failed: ' . implode(' ', $photoResult['errors'] ?? []));
$loadedWithPhoto = $service->getProfile((int)$user['id']);
$photoPath = (string)($loadedWithPhoto['profile']['profile_photo_path'] ?? '');
assertStaffProfile(str_starts_with($photoPath, 'storage/staff_profiles/'), 'Saved profile photo path was not stored in staff profile storage.');
$absolutePhotoPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $photoPath);
assertStaffProfile(is_file($absolutePhotoPath), 'Saved profile photo file is missing from storage.');

$invalid = $service->saveProfile((int)$user['id'], [
    'surname' => 'Doctor',
    'first_name' => 'Development',
    'employee_no' => 'DEV-DOC-001',
    'date_of_birth' => '31-99-2020',
], 1);
assertStaffProfile(($invalid['success'] ?? true) === false, 'Invalid profile date was accepted.');

$staffProfilePage = file_get_contents(__DIR__ . '/../modules/administration/users/staff_profile.php');
$staffViewPage = file_get_contents(__DIR__ . '/../modules/administration/users/view.php');
$staffPhotoController = file_get_contents(__DIR__ . '/../modules/administration/users/staff_photo.php');
assertStaffProfile(is_string($staffProfilePage) && str_contains($staffProfilePage, 'staff_photo.php?'), 'Staff profile edit page should preview saved photos through the protected staff photo endpoint.');
assertStaffProfile(is_string($staffViewPage) && str_contains($staffViewPage, 'staff_photo.php?'), 'Staff profile view page should render saved photos through the protected staff photo endpoint.');
assertStaffProfile(is_string($staffPhotoController) && str_contains($staffPhotoController, 'administrationGuardSuperAdministratorUser'), 'Staff photo endpoint should enforce administration profile access rules.');
@unlink($absolutePhotoPath);

fwrite(STDOUT, "Staff profile tests passed.\n");
