<?php

declare(strict_types=1);

require_once __DIR__ . '/AuditService.php';

class StaffProfileService
{
    private AuditService $auditService;

    public function __construct(private PDO $pdo)
    {
        $this->auditService = new AuditService($pdo);
    }

    public function getProfile(int $userId): array
    {
        if ($userId <= 0) {
            return [
                'profile' => [],
                'education' => [],
                'professional' => [],
            ];
        }

        $stmt = $this->pdo->prepare('SELECT * FROM staff_profiles WHERE user_id = :user_id LIMIT 1');
        $stmt->execute([':user_id' => $userId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $education = [];
        $professional = [];

        if (!empty($profile['id'])) {
            $educationStmt = $this->pdo->prepare('
                SELECT *
                FROM staff_educational_qualifications
                WHERE staff_profile_id = :profile_id
                ORDER BY sort_order, id
            ');
            $educationStmt->execute([':profile_id' => (int)$profile['id']]);
            $education = $educationStmt->fetchAll(PDO::FETCH_ASSOC);

            $professionalStmt = $this->pdo->prepare('
                SELECT *
                FROM staff_professional_qualifications
                WHERE staff_profile_id = :profile_id
                ORDER BY sort_order, id
            ');
            $professionalStmt->execute([':profile_id' => (int)$profile['id']]);
            $professional = $professionalStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return [
            'profile' => $profile,
            'education' => $education,
            'professional' => $professional,
        ];
    }

    public function saveProfile(int $userId, array $data, int $actorId, array $files = []): array
    {
        if ($userId <= 0) {
            return $this->failure(['A valid user is required.']);
        }

        $user = $this->lockableUser($userId);
        if (!$user) {
            return $this->failure(['User not found.']);
        }

        $existingProfileId = $this->profileIdForUserNoLock($userId);
        $existingProfile = $existingProfileId > 0 ? $this->getProfile($userId)['profile'] : [];
        $profile = $this->normalizeProfile($data, $existingProfile);
        $education = $this->normalizeEducation($data['education'] ?? []);
        $professional = $this->normalizeProfessional($data['professional'] ?? []);
        $errors = $this->validateProfile($profile);
        $photoResult = $this->handleProfilePhotoUpload($files['profile_photo'] ?? null, $userId);
        if (($photoResult['success'] ?? true) !== true) {
            $errors = array_merge($errors, $photoResult['errors'] ?? ['Invalid profile photo.']);
        } elseif (!empty($photoResult['path']) && $this->columnExists('staff_profiles', 'profile_photo_path')) {
            $profile['profile_photo_path'] = (string)$photoResult['path'];
        }

        if ($errors !== []) {
            return $this->failure($errors);
        }

        try {
            $this->pdo->beginTransaction();

            $locked = $this->lockableUser($userId, true);
            if (!$locked) {
                throw new RuntimeException('User not found.');
            }

            $existingProfileId = $this->profileIdForUser($userId);
            if ($existingProfileId > 0) {
                $this->updateProfile($existingProfileId, $profile, $actorId);
                $profileId = $existingProfileId;
                $action = 'STAFF_PROFILE_UPDATED';
                $description = 'Updated staff profile for user account #' . $userId . '.';
            } else {
                $profileId = $this->insertProfile($userId, $profile, $actorId);
                $action = 'STAFF_PROFILE_CREATED';
                $description = 'Created staff profile for user account #' . $userId . '.';
            }

            $this->replaceEducation($profileId, $education);
            $this->replaceProfessional($profileId, $professional);

            $this->auditService->log(
                $actorId,
                null,
                'Administration',
                $action,
                $description
            );

            $this->pdo->commit();

            return [
                'success' => true,
                'staff_profile_id' => $profileId,
                'user_id' => $userId,
                'errors' => [],
            ];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to save staff profile.']);
        }
    }

    public function defaultProfileFromUser(array $user): array
    {
        return [
            'surname' => (string)($user['last_name'] ?? ''),
            'first_name' => (string)($user['first_name'] ?? ''),
            'middle_name' => '',
            'title' => '',
            'gender' => (string)($user['gender'] ?? ''),
            'date_of_birth' => '',
            'place_of_birth' => '',
            'marital_status' => '',
            'home_town' => '',
            'permanent_home_address' => '',
            'residential_address' => '',
            'phone_no' => (string)($user['phone'] ?? ''),
            'personal_email' => '',
            'religion' => '',
            'national_identity_number' => '',
            'first_appointment_date' => '',
            'first_appointment_salary_grade' => '',
            'employee_no' => (string)($user['employee_id'] ?? ''),
            'official_email' => (string)($user['email'] ?? ''),
            'salary_bank' => '',
            'salary_bank_account_no' => '',
            'next_of_kin1_name' => '',
            'next_of_kin1_address' => '',
            'next_of_kin1_date_of_birth' => '',
            'next_of_kin1_relationship' => '',
            'next_of_kin1_phone' => '',
            'next_of_kin2_name' => '',
            'next_of_kin2_address' => '',
            'next_of_kin2_date_of_birth' => '',
            'next_of_kin2_relationship' => '',
            'next_of_kin2_phone' => '',
        ];
    }

    private function normalizeProfile(array $data, array $existing = []): array
    {
        $fields = [
            'surname',
            'first_name',
            'middle_name',
            'title',
            'gender',
            'date_of_birth',
            'place_of_birth',
            'marital_status',
            'home_town',
            'permanent_home_address',
            'residential_address',
            'phone_no',
            'personal_email',
            'religion',
            'national_identity_number',
            'first_appointment_date',
            'first_appointment_salary_grade',
            'employee_no',
            'official_email',
            'salary_bank',
            'salary_bank_account_no',
            'next_of_kin1_name',
            'next_of_kin1_address',
            'next_of_kin1_date_of_birth',
            'next_of_kin1_relationship',
            'next_of_kin1_phone',
            'next_of_kin2_name',
            'next_of_kin2_address',
            'next_of_kin2_date_of_birth',
            'next_of_kin2_relationship',
            'next_of_kin2_phone',
        ];

        $profile = [];
        foreach ($fields as $field) {
            $profile[$field] = trim((string)($data[$field] ?? ($existing[$field] ?? '')));
        }

        foreach (['date_of_birth', 'first_appointment_date', 'next_of_kin1_date_of_birth', 'next_of_kin2_date_of_birth'] as $dateField) {
            $profile[$dateField] = $this->normalizeDate($profile[$dateField]);
        }

        if ($this->columnExists('staff_profiles', 'profile_photo_path')) {
            $profile['profile_photo_path'] = trim((string)($data['profile_photo_path'] ?? ($existing['profile_photo_path'] ?? '')));
        }

        return $profile;
    }

    private function normalizeEducation(mixed $rows): array
    {
        if (!is_array($rows)) {
            return [];
        }

        $normalized = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $entry = [
                'school_attended_with_dates' => trim((string)($row['school_attended_with_dates'] ?? '')),
                'course_of_study' => trim((string)($row['course_of_study'] ?? '')),
                'certificates_obtained' => trim((string)($row['certificates_obtained'] ?? '')),
                'class_of_degree' => trim((string)($row['class_of_degree'] ?? '')),
                'year_of_graduation' => trim((string)($row['year_of_graduation'] ?? '')),
                'remarks' => trim((string)($row['remarks'] ?? '')),
            ];
            if (implode('', $entry) !== '') {
                $normalized[] = $entry;
            }
        }

        return $normalized;
    }

    private function normalizeProfessional(mixed $rows): array
    {
        if (!is_array($rows)) {
            return [];
        }

        $normalized = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $entry = [
                'certification_type' => trim((string)($row['certification_type'] ?? '')),
                'certificates' => trim((string)($row['certificates'] ?? '')),
                'certification_license_no' => trim((string)($row['certification_license_no'] ?? '')),
                'certification_expiration_date' => $this->normalizeDate(trim((string)($row['certification_expiration_date'] ?? ''))),
                'year_attained' => trim((string)($row['year_attained'] ?? '')),
                'remarks' => trim((string)($row['remarks'] ?? '')),
            ];
            if (implode('', $entry) !== '') {
                $normalized[] = $entry;
            }
        }

        return $normalized;
    }

    private function validateProfile(array $profile): array
    {
        $errors = [];
        foreach (['surname' => 'Surname', 'first_name' => 'First name', 'employee_no' => 'Employee number'] as $field => $label) {
            if ($profile[$field] === '') {
                $errors[] = $label . ' is required.';
            }
        }

        foreach ([
            'date_of_birth' => 'Date of birth',
            'first_appointment_date' => 'Date of first appointment',
            'next_of_kin1_date_of_birth' => 'Next of kin 1 date of birth',
            'next_of_kin2_date_of_birth' => 'Next of kin 2 date of birth',
        ] as $field => $label) {
            if ($profile[$field] === false) {
                $errors[] = $label . ' is invalid.';
            }
        }

        return $errors;
    }

    private function insertProfile(int $userId, array $profile, int $actorId): int
    {
        $columns = array_keys($profile);
        $columnList = implode(', ', array_merge(['user_id'], $columns, ['created_by', 'updated_by']));
        $placeholderList = implode(', ', array_merge([':user_id'], array_map(fn (string $column): string => ':' . $column, $columns), [':created_by', ':updated_by']));

        $stmt = $this->pdo->prepare("INSERT INTO staff_profiles ($columnList) VALUES ($placeholderList)");
        $params = [':user_id' => $userId, ':created_by' => $actorId ?: null, ':updated_by' => $actorId ?: null];
        foreach ($profile as $column => $value) {
            $params[':' . $column] = $value === '' ? null : $value;
        }
        $stmt->execute($params);

        return (int)$this->pdo->lastInsertId();
    }

    private function updateProfile(int $profileId, array $profile, int $actorId): void
    {
        $assignments = implode(', ', array_map(fn (string $column): string => $column . ' = :' . $column, array_keys($profile)));
        $stmt = $this->pdo->prepare("
            UPDATE staff_profiles
            SET $assignments,
                updated_by = :updated_by,
                updated_at = NOW()
            WHERE id = :id
        ");
        $params = [':id' => $profileId, ':updated_by' => $actorId ?: null];
        foreach ($profile as $column => $value) {
            $params[':' . $column] = $value === '' ? null : $value;
        }
        $stmt->execute($params);
    }

    private function replaceEducation(int $profileId, array $rows): void
    {
        $this->pdo->prepare('DELETE FROM staff_educational_qualifications WHERE staff_profile_id = :profile_id')
            ->execute([':profile_id' => $profileId]);

        $stmt = $this->pdo->prepare('
            INSERT INTO staff_educational_qualifications (
                staff_profile_id, sort_order, school_attended_with_dates,
                course_of_study, certificates_obtained, class_of_degree,
                year_of_graduation, remarks
            ) VALUES (
                :profile_id, :sort_order, :school_attended_with_dates,
                :course_of_study, :certificates_obtained, :class_of_degree,
                :year_of_graduation, :remarks
            )
        ');

        foreach ($rows as $index => $row) {
            $stmt->execute([
                ':profile_id' => $profileId,
                ':sort_order' => $index + 1,
                ':school_attended_with_dates' => $row['school_attended_with_dates'] ?: null,
                ':course_of_study' => $row['course_of_study'] ?: null,
                ':certificates_obtained' => $row['certificates_obtained'] ?: null,
                ':class_of_degree' => $row['class_of_degree'] ?: null,
                ':year_of_graduation' => $row['year_of_graduation'] ?: null,
                ':remarks' => $row['remarks'] ?: null,
            ]);
        }
    }

    private function replaceProfessional(int $profileId, array $rows): void
    {
        $this->pdo->prepare('DELETE FROM staff_professional_qualifications WHERE staff_profile_id = :profile_id')
            ->execute([':profile_id' => $profileId]);

        $stmt = $this->pdo->prepare('
            INSERT INTO staff_professional_qualifications (
                staff_profile_id, sort_order, certification_type, certificates,
                certification_license_no, certification_expiration_date,
                year_attained, remarks
            ) VALUES (
                :profile_id, :sort_order, :certification_type, :certificates,
                :certification_license_no, :certification_expiration_date,
                :year_attained, :remarks
            )
        ');

        foreach ($rows as $index => $row) {
            $stmt->execute([
                ':profile_id' => $profileId,
                ':sort_order' => $index + 1,
                ':certification_type' => $row['certification_type'] ?: null,
                ':certificates' => $row['certificates'] ?: null,
                ':certification_license_no' => $row['certification_license_no'] ?: null,
                ':certification_expiration_date' => $row['certification_expiration_date'] ?: null,
                ':year_attained' => $row['year_attained'] ?: null,
                ':remarks' => $row['remarks'] ?: null,
            ]);
        }
    }

    private function profileIdForUser(int $userId): int
    {
        $stmt = $this->pdo->prepare('SELECT id FROM staff_profiles WHERE user_id = :user_id LIMIT 1 FOR UPDATE');
        $stmt->execute([':user_id' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    private function profileIdForUserNoLock(int $userId): int
    {
        $stmt = $this->pdo->prepare('SELECT id FROM staff_profiles WHERE user_id = :user_id LIMIT 1');
        $stmt->execute([':user_id' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    private function columnExists(string $table, string $column): bool
    {
        try {
            $stmt = $this->pdo->prepare('
                SELECT COUNT(*)
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = :table
                  AND COLUMN_NAME = :column
            ');
            $stmt->execute([
                ':table' => $table,
                ':column' => $column,
            ]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (Throwable) {
            return false;
        }
    }

    private function handleProfilePhotoUpload(mixed $file, int $userId): array
    {
        if (!is_array($file) || (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['success' => true, 'path' => null, 'errors' => []];
        }

        if ((int)($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return ['success' => false, 'errors' => ['Profile picture upload failed.']];
        }

        if ((int)($file['size'] ?? 0) <= 0 || (int)$file['size'] > 2 * 1024 * 1024) {
            return ['success' => false, 'errors' => ['Profile picture must be 2 MB or smaller.']];
        }

        $tmpName = (string)($file['tmp_name'] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            return ['success' => false, 'errors' => ['Profile picture upload is invalid.']];
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$finfo->file($tmpName);
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ];

        if (!isset($extensions[$mime])) {
            return ['success' => false, 'errors' => ['Profile picture must be JPG, PNG, WebP, or GIF.']];
        }

        $storageDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'staff_profiles';
        if (!is_dir($storageDir) && !mkdir($storageDir, 0775, true) && !is_dir($storageDir)) {
            return ['success' => false, 'errors' => ['Unable to prepare staff profile photo storage.']];
        }

        $filename = 'staff_' . $userId . '_' . bin2hex(random_bytes(8)) . '.' . $extensions[$mime];
        $target = $storageDir . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file($tmpName, $target)) {
            return ['success' => false, 'errors' => ['Unable to save profile picture.']];
        }

        return [
            'success' => true,
            'path' => 'storage/staff_profiles/' . $filename,
            'errors' => [],
        ];
    }

    private function lockableUser(int $userId, bool $forUpdate = false): ?array
    {
        $sql = 'SELECT id FROM users WHERE id = :id LIMIT 1' . ($forUpdate ? ' FOR UPDATE' : '');
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function normalizeDate(string $value): string|false
    {
        if ($value === '') {
            return '';
        }

        $formats = ['Y-m-d', 'd/m/Y', 'd/m/y'];
        foreach ($formats as $format) {
            $date = DateTimeImmutable::createFromFormat('!' . $format, $value);
            if ($date instanceof DateTimeImmutable && $date->format($format) === $value) {
                return $date->format('Y-m-d');
            }
        }

        return false;
    }

    private function failure(array $errors): array
    {
        return ['success' => false, 'errors' => $errors];
    }

    private function rollback(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }
}
