<?php

declare(strict_types=1);

require_once __DIR__ . '/AuditService.php';
require_once __DIR__ . '/SettingsService.php';

class PatientService
{
    public const SUPPORTED_GENDERS = [
        'Male',
        'Female',
        'Other',
        'Unknown'
    ];

    private const DEMOGRAPHIC_FIELDS = [
        'first_name',
        'middle_name',
        'last_name',
        'gender',
        'date_of_birth',
        'marital_status',
        'occupation',
        'place_of_work',
        'phone',
        'whatsapp_number',
        'email',
        'address',
        'state_of_origin',
        'nationality',
        'ethnic_group',
        'religion',
        'blood_group',
        'genotype',
        'next_of_kin',
        'next_of_kin_relationship',
        'next_of_kin_phone',
        'next_of_kin_address'
    ];

    private PDO $pdo;
    private array $config;
    private AuditService $auditService;
    private SettingsService $settingsService;
    private ?bool $patientSoftDeleteAvailable = null;

    public function __construct(PDO $pdo, ?AuditService $auditService = null)
    {
        $this->pdo = $pdo;
        $this->config = require __DIR__ . '/../config/app.php';
        $this->auditService = $auditService ?? new AuditService($pdo);
        $this->settingsService = new SettingsService($pdo);

    }

    /*
    |--------------------------------------------------------------------------
    | Supported Patient Genders
    |--------------------------------------------------------------------------
    */

    public static function supportedGenders(): array
    {
        return self::SUPPORTED_GENDERS;
    }

    /*
    |--------------------------------------------------------------------------
    | Create Patient
    |--------------------------------------------------------------------------
    */

    public function createPatient(array $patient, int $registeredBy): array
    {
        $patient = $this->normalizeDemographics($patient);
        $errors = $this->validate($patient);

        if (!empty($errors)) {

            return [

                'success' => false,

                'patient_id' => null,

                'hospital_number' => null,

                'errors' => $errors

            ];

        }

        try {

            $this->pdo->beginTransaction();

            $duplicates = $this->findPossibleDuplicatesInternal($patient);
            $blocking = array_values(array_filter(
                $duplicates,
                static fn (array $candidate): bool => in_array(
                    $candidate['classification'],
                    ['Exact Match', 'Strong Possible Match'],
                    true
                )
            ));

            if ($blocking !== [] && empty($patient['duplicate_review_ack'])) {
                $this->pdo->rollBack();
                return [
                    'success' => false,
                    'patient_id' => null,
                    'hospital_number' => null,
                    'duplicate_review_required' => true,
                    'duplicate_candidates' => $duplicates,
                    'errors' => ['Review the possible matching patient records before registration.']
                ];
            }

            $sql = "

                INSERT INTO patients (

                    hospital_number,

                    first_name,

                    normalized_first_name,

                    middle_name,

                    normalized_middle_name,

                    last_name,

                    normalized_last_name,

                    gender,

                    date_of_birth,

                    marital_status,

                    occupation,

                    place_of_work,

                    phone,

                    whatsapp_number,

                    normalized_phone,

                    email,

                    normalized_email,

                    address,

                    state_of_origin,

                    nationality,

                    ethnic_group,

                    religion,

                    blood_group,

                    genotype,

                    allergies,

                    next_of_kin,

                    next_of_kin_relationship,

                    next_of_kin_phone,

                    next_of_kin_address,

                    registered_by

                )

                VALUES (

                    NULL,

                    :first_name,

                    :normalized_first_name,

                    :middle_name,

                    :normalized_middle_name,

                    :last_name,

                    :normalized_last_name,

                    :gender,

                    :date_of_birth,

                    :marital_status,

                    :occupation,

                    :place_of_work,

                    :phone,

                    :whatsapp_number,

                    :normalized_phone,

                    :email,

                    :normalized_email,

                    :address,

                    :state_of_origin,

                    :nationality,

                    :ethnic_group,

                    :religion,

                    :blood_group,

                    :genotype,

                    :allergies,

                    :next_of_kin,

                    :next_of_kin_relationship,

                    :next_of_kin_phone,

                    :next_of_kin_address,

                    :registered_by

                )

            ";

            $stmt = $this->pdo->prepare($sql);

            $stmt->execute([

                ':first_name'        => $patient['first_name'],
                ':normalized_first_name' => $this->normalizeName($patient['first_name']),
                ':middle_name'       => $patient['middle_name'],
                ':normalized_middle_name' => $this->normalizeName($patient['middle_name']),
                ':last_name'         => $patient['last_name'],
                ':normalized_last_name' => $this->normalizeName($patient['last_name']),
                ':gender'            => $patient['gender'],
                ':date_of_birth'     => $patient['date_of_birth'],
                ':marital_status'    => $patient['marital_status'],
                ':occupation'        => $patient['occupation'],
                ':place_of_work'     => $patient['place_of_work'],
                ':phone'             => $patient['phone'],
                ':whatsapp_number'   => $patient['whatsapp_number'] ?? null,
                ':normalized_phone'  => $this->normalizePhone($patient['phone']),
                ':email'             => $patient['email'],
                ':normalized_email'  => $this->normalizeEmail($patient['email']),
                ':address'           => $patient['address'],
                ':state_of_origin'   => $patient['state_of_origin'],
                ':nationality'       => $patient['nationality'],
                ':ethnic_group'      => $patient['ethnic_group'],
                ':religion'          => $patient['religion'],
                ':blood_group'       => $patient['blood_group'],
                ':genotype'          => $patient['genotype'],
                ':allergies'         => trim((string)($patient['allergies'] ?? '')),
                ':next_of_kin'       => $patient['next_of_kin'],
                ':next_of_kin_relationship'
                                      => $patient['next_of_kin_relationship'],
                ':next_of_kin_phone' => $patient['next_of_kin_phone'],
                ':next_of_kin_address'
                                      => $patient['next_of_kin_address'],
                ':registered_by'     => $registeredBy

            ]);

            $patientId = (int)$this->pdo->lastInsertId();

            $hospitalNumber = $this->generateHospitalNumber($patientId);

            $update = $this->pdo->prepare(

                "

                UPDATE patients

                SET hospital_number = :hospital_number

                WHERE id = :id

                "

            );

            $update->execute([

                ':hospital_number' => $hospitalNumber,

                ':id' => $patientId

            ]);

            foreach ($duplicates as $candidate) {
                if ($candidate['classification'] === 'Low Confidence') {
                    continue;
                }
                $this->createDuplicateCandidate(
                    $patientId,
                    (int)$candidate['id'],
                    (float)$candidate['match_score'],
                    (string)$candidate['classification'],
                    (array)$candidate['matched_factors'],
                    $registeredBy
                );
            }

            if (!$this->auditService->logPatient(
                $registeredBy,
                $patientId,
                null,
                'Patients',
                'PATIENT_REGISTERED',
                'Registered patient ' . $hospitalNumber . '.',
                null,
                'INFO',
                'PATIENT_REGISTERED'
            )) {
                throw new RuntimeException('Unable to record audit log.');
            }

            $this->pdo->commit();

            return [

                'success' => true,

                'patient_id' => $patientId,

                'hospital_number' => $hospitalNumber,

                'errors' => []

            ];

        } catch (Throwable $e) {

    if ($this->pdo->inTransaction()) {

        $this->pdo->rollBack();

    }

    return [

        'success' => false,

        'patient_id' => null,

        'hospital_number' => null,

        'errors' => [

            'Unable to register patient.'

        ]

    ];

}
    }

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    private function validate(array $patient): array
    {
        $errors = [];

        if (empty($patient['first_name'])) {

            $errors[] = 'First name is required.';

        }

        if (empty($patient['last_name'])) {

            $errors[] = 'Last name is required.';

        }

        $gender = trim((string)($patient['gender'] ?? ''));

        if ($gender === '') {

            $errors[] = 'Gender is required.';

        } elseif (!in_array($gender, self::SUPPORTED_GENDERS, true)) {

            $errors[] = 'Select a valid gender.';

        }

        $dateOfBirth = trim((string)($patient['date_of_birth'] ?? ''));

        if ($dateOfBirth === '') {

            $errors[] = 'Date of birth is required.';

        } else {

            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $dateOfBirth);

            if (!$date || $date->format('Y-m-d') !== $dateOfBirth) {

                $errors[] = 'Select a valid date of birth.';

            } elseif ($date > new DateTimeImmutable('today')) {

                $errors[] = 'Date of birth cannot be in the future.';

            }

        }

        if (!empty($patient['email'])) {

            if (!filter_var($patient['email'], FILTER_VALIDATE_EMAIL)) {

                $errors[] = 'Invalid email address.';

            }

        }

        return $errors;
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Hospital Number
    |--------------------------------------------------------------------------
    */

    private function generateHospitalNumber(int $patientId): string
    {
        $hospitalCode = (string)$this->settingsService->get(
            'hospital.code',
            $this->config['hospital']['code'] ?? 'HMS'
        );
        $hospitalCode = preg_replace('/[^A-Za-z0-9_-]/', '', trim($hospitalCode)) ?: 'HMS';

        return sprintf(

            '%s-%s-%06d',

            $hospitalCode,

            date('Y'),

            $patientId

        );
    }

    /*
    |--------------------------------------------------------------------------
    | Get Patient
    |--------------------------------------------------------------------------
    */

    public function getPatientById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(

            "SELECT * FROM patients WHERE id = ?"

        );

        $stmt->execute([$id]);

        $patient = $stmt->fetch(PDO::FETCH_ASSOC);

        return $patient ?: null;
    }

    /*
    |--------------------------------------------------------------------------
    | Search Patients
    |--------------------------------------------------------------------------
    */

public function searchPatients(array $filters): array
{
    $sql = "SELECT * FROM patients";

    $conditions = [];

    if ($this->patientSoftDeleteAvailable()) {
        $conditions[] = "COALESCE(is_deleted, 0) = 0";
    }

    $params = [];

    if (!empty($filters['hospital_number'])) {

        $conditions[] = "hospital_number LIKE :hospital_number";

        $params['hospital_number'] =
            '%' . trim($filters['hospital_number']) . '%';

    }

    if (!empty($filters['first_name'])) {

        $conditions[] = "first_name LIKE :first_name";

        $params['first_name'] =
            '%' . trim($filters['first_name']) . '%';

    }

    if (!empty($filters['last_name'])) {

        $conditions[] = "last_name LIKE :last_name";

        $params['last_name'] =
            '%' . trim($filters['last_name']) . '%';

    }

    if (!empty($filters['phone'])) {

        $conditions[] = "phone LIKE :phone";

        $params['phone'] =
            '%' . trim($filters['phone']) . '%';

    }

    if (!empty($filters['date_of_birth'])) {

        $conditions[] = "date_of_birth = :date_of_birth";

        $params['date_of_birth'] =
            $filters['date_of_birth'];

    }

    if (!empty($filters['gender'])
        && in_array($filters['gender'], self::SUPPORTED_GENDERS, true)
    ) {

        $conditions[] = "gender = :gender";

        $params['gender'] =
            $filters['gender'];

    }

    if ($conditions !== []) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    $sql .= " ORDER BY last_name, first_name";

    $stmt = $this->pdo->prepare($sql);

    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
    

    /*
|--------------------------------------------------------------------------
| Update Patient
|--------------------------------------------------------------------------
*/

public function updatePatient(
    int $id,
    array $patient
): array
{
    $userId = $this->currentUserId();

    if ($userId === null) {
        return [
            'success' => false,
            'errors' => ['Your session has expired.']
        ];
    }

    $expectedVersion = isset($patient['demographic_version'])
        ? (int)$patient['demographic_version']
        : null;

    $reason = trim((string)(
        $patient['amendment_reason']
        ?? 'Compatibility update through PatientService::updatePatient().'
    ));

    return $this->updatePatientWithContext(
        $id,
        $patient,
        $reason,
        $expectedVersion,
        $userId
    );
}

public function deletePatient(
    int $id,
    int $deletedBy,
    string $reason
): array {
    if ($id <= 0 || $deletedBy <= 0) {
        return [
            'success' => false,
            'errors' => ['Patient and user are required.']
        ];
    }

    $reason = trim($reason);
    if ($reason === '') {
        return [
            'success' => false,
            'errors' => ['A reason is required before deleting a patient.']
        ];
    }

    try {
        $this->pdo->beginTransaction();

        $lock = $this->pdo->prepare('SELECT * FROM patients WHERE id = :id FOR UPDATE');
        $lock->execute([':id' => $id]);
        $patient = $lock->fetch(PDO::FETCH_ASSOC);

        if (!$patient) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'errors' => ['Patient not found.']
            ];
        }

        if ((int)($patient['is_deleted'] ?? 0) === 1) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'errors' => ['This patient is already deleted.']
            ];
        }

        $delete = $this->pdo->prepare('
            UPDATE patients
            SET is_deleted = 1,
                deleted_at = NOW(),
                deleted_by = :deleted_by,
                deletion_reason = :deletion_reason,
                updated_at = NOW()
            WHERE id = :id
              AND COALESCE(is_deleted, 0) = 0
        ');
        $delete->execute([
            ':deleted_by' => $deletedBy,
            ':deletion_reason' => $reason,
            ':id' => $id,
        ]);

        if ($delete->rowCount() !== 1) {
            throw new RuntimeException('Patient soft deletion did not affect exactly one record.');
        }

        if (!$this->auditService->logPatient(
            $deletedBy,
            $id,
            null,
            'Patients',
            'PATIENT_DELETED',
            'Soft-deleted patient registration #' . $id . '. Reason: ' . $reason,
            $this->currentDepartmentId(),
            'WARNING',
            'PATIENT_DELETED'
        )) {
            throw new RuntimeException('Unable to record patient deletion audit log.');
        }

        $this->pdo->commit();

        return [
            'success' => true,
            'patient_id' => $id,
            'errors' => []
        ];
    } catch (Throwable) {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }

        return [
            'success' => false,
            'errors' => ['Unable to delete patient.']
        ];
    }
}

    public function findPatientByHospitalNumberExact(string $hospitalNumber): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM patients WHERE hospital_number = :hospital_number LIMIT 1
        ');
        $stmt->execute([':hospital_number' => trim($hospitalNumber)]);
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);
        return $patient ?: null;
    }

    public function searchPatientsPaginated(
        array $filters,
        int $page = 1,
        int $pageSize = 25
    ): array {
        $page = max(1, $page);
        $pageSize = max(1, min(100, $pageSize));
        $conditions = [];
        if ($this->patientSoftDeleteAvailable()) {
            $conditions[] = 'COALESCE(p.is_deleted, 0) = 0';
        }
        $parameters = [];
        $rank = '50';

        $query = trim((string)($filters['query'] ?? ''));
        if ($query !== '') {
            $queryName = $this->normalizeName($query);
            $queryPhone = $this->normalizePhone($query);
            $queryIdentifier = $this->normalizeIdentifierValue($query);
            $conditions[] = '(
                p.hospital_number = :query_hospital
                OR EXISTS (
                    SELECT 1 FROM patient_identifiers qi
                    WHERE qi.patient_id = p.id AND qi.is_active = 1
                      AND qi.normalized_value = :query_identifier
                )
                OR p.normalized_phone = :query_phone_exact
                OR p.normalized_last_name LIKE :query_last_prefix
                OR p.normalized_first_name LIKE :query_first_prefix
                OR p.normalized_phone LIKE :query_phone_prefix
            )';
            $parameters[':query_hospital'] = $query;
            $parameters[':query_identifier'] = $queryIdentifier;
            $parameters[':query_phone_exact'] = $queryPhone;
            $parameters[':query_last_prefix'] = $queryName . '%';
            $parameters[':query_first_prefix'] = $queryName . '%';
            $parameters[':query_phone_prefix'] = $queryPhone . '%';
            $parameters[':rank_hospital'] = $query;
            $parameters[':rank_identifier'] = $queryIdentifier;
            $parameters[':rank_phone_exact'] = $queryPhone;
            $parameters[':rank_last_prefix'] = $queryName . '%';
            $parameters[':rank_first_prefix'] = $queryName . '%';
            $parameters[':rank_phone_prefix'] = $queryPhone . '%';
            $rank = 'CASE
                WHEN p.hospital_number = :rank_hospital THEN 1
                WHEN EXISTS (
                    SELECT 1 FROM patient_identifiers ri
                    WHERE ri.patient_id = p.id AND ri.is_active = 1
                      AND ri.normalized_value = :rank_identifier
                ) THEN 2
                WHEN p.normalized_phone = :rank_phone_exact THEN 3
                WHEN p.normalized_last_name LIKE :rank_last_prefix
                  OR p.normalized_first_name LIKE :rank_first_prefix THEN 4
                WHEN p.normalized_phone LIKE :rank_phone_prefix THEN 5
                ELSE 50 END';
            if (!$this->settingsService->getBoolean('mpi.exact_match_priority', true)) {
                $rank = '50';
            }
        }

        if (trim((string)($filters['hospital_number'] ?? '')) !== '') {
            $conditions[] = 'p.hospital_number = :hospital_number';
            $parameters[':hospital_number'] = trim((string)$filters['hospital_number']);
            $rank = '1';
        }
        if (trim((string)($filters['alternate_identifier'] ?? '')) !== '') {
            $conditions[] = 'EXISTS (
                SELECT 1 FROM patient_identifiers i
                WHERE i.patient_id = p.id AND i.is_active = 1
                  AND i.normalized_value = :alternate_identifier
            )';
            $parameters[':alternate_identifier'] = $this->normalizeIdentifierValue(
                (string)$filters['alternate_identifier']
            );
            $rank = 'LEAST(' . $rank . ', 2)';
        }
        if (trim((string)($filters['phone'] ?? '')) !== '') {
            $conditions[] = 'p.normalized_phone = :phone';
            $parameters[':phone'] = $this->normalizePhone((string)$filters['phone']);
            $rank = 'LEAST(' . $rank . ', 3)';
        }
        if (trim((string)($filters['email'] ?? '')) !== '') {
            $conditions[] = 'p.normalized_email = :email';
            $parameters[':email'] = $this->normalizeEmail((string)$filters['email']);
        }
        foreach (['first_name', 'middle_name', 'last_name'] as $field) {
            if (trim((string)($filters[$field] ?? '')) === '') {
                continue;
            }
            $conditions[] = 'p.normalized_' . $field . ' LIKE :' . $field;
            $parameters[':' . $field] = $this->normalizeName($filters[$field]) . '%';
        }
        if (trim((string)($filters['date_of_birth'] ?? '')) !== '') {
            $conditions[] = 'p.date_of_birth = :date_of_birth';
            $parameters[':date_of_birth'] = trim((string)$filters['date_of_birth']);
        }
        if (trim((string)($filters['gender'] ?? '')) !== ''
            && in_array($filters['gender'], self::SUPPORTED_GENDERS, true)
        ) {
            $conditions[] = 'p.gender = :gender';
            $parameters[':gender'] = $filters['gender'];
        }

        $where = $conditions === [] ? '1 = 1' : implode(' AND ', $conditions);
        $count = $this->pdo->prepare('SELECT COUNT(*) FROM patients p WHERE ' . $where);
        $countParameters = array_filter(
            $parameters,
            static fn (string $key): bool => !str_starts_with($key, ':rank_'),
            ARRAY_FILTER_USE_KEY
        );
        $count->execute($countParameters);
        $total = (int)$count->fetchColumn();

        $sql = 'SELECT p.*, ' . $rank . ' AS match_rank
            FROM patients p WHERE ' . $where . '
            ORDER BY match_rank, p.normalized_last_name, p.normalized_first_name
            LIMIT :limit OFFSET :offset';
        $stmt = $this->pdo->prepare($sql);
        foreach ($parameters as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
        $stmt->bindValue(':offset', ($page - 1) * $pageSize, PDO::PARAM_INT);
        $stmt->execute();
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'success' => true,
            'data' => [
                'records' => $records,
                'current_page' => $page,
                'page_size' => $pageSize,
                'total_results' => $total,
                'total_pages' => max(1, (int)ceil($total / $pageSize)),
                'applied_filters' => $filters
            ],
            'records' => $records,
            'current_page' => $page,
            'page_size' => $pageSize,
            'total_results' => $total,
            'total_pages' => max(1, (int)ceil($total / $pageSize)),
            'applied_filters' => $filters,
            'errors' => []
        ];
    }

    public function findPossibleDuplicates(
        array $patient,
        ?int $excludePatientId = null
    ): array {
        try {
            $candidates = $this->findPossibleDuplicatesInternal(
                $this->normalizeDemographics($patient),
                $excludePatientId
            );
            return [
                'success' => true,
                'data' => $candidates,
                'candidates' => $candidates,
                'errors' => []
            ];
        } catch (Throwable $exception) {
            return [
                'success' => false,
                'data' => [],
                'candidates' => [],
                'errors' => ['Unable to evaluate possible duplicate patients.']
            ];
        }
    }

    public function getDuplicateCandidates(
        string $status = 'Pending',
        int $page = 1,
        int $pageSize = 25
    ): array {
        $page = max(1, $page);
        $pageSize = max(1, min(100, $pageSize));
        $where = $status === '' ? '1 = 1' : 'c.status = :status';
        $params = $status === '' ? [] : [':status' => $status];
        $count = $this->pdo->prepare(
            'SELECT COUNT(*) FROM patient_duplicate_candidates c WHERE ' . $where
        );
        $count->execute($params);
        $total = (int)$count->fetchColumn();
        $stmt = $this->pdo->prepare('
            SELECT c.*,
                p1.hospital_number AS low_hospital_number,
                CONCAT(p1.first_name, " ", p1.last_name) AS low_patient_name,
                p2.hospital_number AS high_hospital_number,
                CONCAT(p2.first_name, " ", p2.last_name) AS high_patient_name
            FROM patient_duplicate_candidates c
            INNER JOIN patients p1 ON p1.id = c.patient_id_low
            INNER JOIN patients p2 ON p2.id = c.patient_id_high
            WHERE ' . $where . '
            ORDER BY c.match_score DESC, c.detected_at DESC
            LIMIT :limit OFFSET :offset
        ');
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
        $stmt->bindValue(':offset', ($page - 1) * $pageSize, PDO::PARAM_INT);
        $stmt->execute();
        return [
            'success' => true,
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total_results' => $total,
            'current_page' => $page,
            'total_pages' => max(1, (int)ceil($total / $pageSize)),
            'errors' => []
        ];
    }

    public function getDuplicateCandidate(int $candidateId): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT c.*, p1.*, p2.hospital_number AS comparison_hospital_number,
                p2.first_name AS comparison_first_name,
                p2.middle_name AS comparison_middle_name,
                p2.last_name AS comparison_last_name,
                p2.date_of_birth AS comparison_date_of_birth,
                p2.gender AS comparison_gender,
                p2.phone AS comparison_phone,
                p2.email AS comparison_email
            FROM patient_duplicate_candidates c
            INNER JOIN patients p1 ON p1.id = c.patient_id_low
            INNER JOIN patients p2 ON p2.id = c.patient_id_high
            WHERE c.id = :id
        ');
        $stmt->execute([':id' => $candidateId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getUnresolvedDuplicateWarning(int $patientId): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT id, classification, match_score
            FROM patient_duplicate_candidates
            WHERE (patient_id_low = :patient_id_low OR patient_id_high = :patient_id_high)
              AND status IN (\'Pending\', \'Deferred\', \'Merge Requested\')
              AND classification IN (\'Exact Match\', \'Strong Possible Match\')
            ORDER BY match_score DESC LIMIT 1
        ');
        $stmt->execute([
            ':patient_id_low' => $patientId,
            ':patient_id_high' => $patientId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function reviewDuplicateCandidate(
        int $candidateId,
        string $decision,
        string $reason,
        int $reviewedBy,
        int $expectedVersion = 1
    ): array {
        $allowed = ['Confirmed Duplicate', 'Not Duplicate', 'Deferred', 'Merge Requested'];
        if (!in_array($decision, $allowed, true) || trim($reason) === '') {
            return ['success' => false, 'data' => null, 'errors' => ['A valid decision and reason are required.']];
        }
        try {
            $this->pdo->beginTransaction();
            $lock = $this->pdo->prepare(
                'SELECT * FROM patient_duplicate_candidates WHERE id = :id FOR UPDATE'
            );
            $lock->execute([':id' => $candidateId]);
            $candidate = $lock->fetch(PDO::FETCH_ASSOC);
            if (!$candidate || (int)$candidate['version'] !== $expectedVersion) {
                $this->pdo->rollBack();
                return ['success' => false, 'data' => null, 'conflict' => true, 'errors' => ['The duplicate case changed. Reload it before review.']];
            }
            $stmt = $this->pdo->prepare('
                UPDATE patient_duplicate_candidates
                SET status = :status, review_decision = :decision,
                    review_reason = :reason, reviewed_by = :reviewed_by,
                    reviewed_at = NOW(), version = version + 1
                WHERE id = :id
            ');
            $stmt->execute([
                ':status' => $decision,
                ':decision' => $decision,
                ':reason' => trim($reason),
                ':reviewed_by' => $reviewedBy,
                ':id' => $candidateId
            ]);
            $auditAction = $decision === 'Not Duplicate'
                ? 'DUPLICATE_DISMISSED'
                : 'DUPLICATE_REVIEWED';
            foreach ([(int)$candidate['patient_id_low'], (int)$candidate['patient_id_high']] as $patientId) {
                if (!$this->auditService->logPatient(
                    $reviewedBy,
                    $patientId,
                    null,
                    'Medical Records',
                    $auditAction,
                    'Reviewed duplicate candidate case #' . $candidateId . ' as ' . $decision . '.',
                    null,
                    'INFO',
                    $auditAction
                )) {
                    throw new RuntimeException('Unable to audit duplicate review.');
                }
            }
            $this->pdo->commit();
            return ['success' => true, 'data' => ['candidate_id' => $candidateId, 'status' => $decision], 'candidate_id' => $candidateId, 'errors' => []];
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['success' => false, 'data' => null, 'errors' => ['Unable to review duplicate candidate.']];
        }
    }

public function updatePatientWithContext(
    int $id,
    array $patient,
    string $reason,
    ?int $expectedVersion,
    int $changedBy,
    ?int $visitId = null
): array {
    if ($id <= 0 || $changedBy <= 0) {
        return [
            'success' => false,
            'errors' => ['Patient and user are required.']
        ];
    }

    if (trim($reason) === '') {
        return [
            'success' => false,
            'errors' => ['A reason for the demographic change is required.']
        ];
    }

    try {
        $this->pdo->beginTransaction();

        $lock = $this->pdo->prepare(
            'SELECT * FROM patients WHERE id = :id FOR UPDATE'
        );
        $lock->execute([':id' => $id]);
        $current = $lock->fetch(PDO::FETCH_ASSOC);

        if (!$current) {
            throw new RuntimeException('Patient not found.');
        }

        $currentVersion = max(1, (int)$current['demographic_version']);

        if ($expectedVersion !== null
            && $expectedVersion > 0
            && $expectedVersion !== $currentVersion
        ) {
            $this->pdo->rollBack();

            $this->auditService->logPatient(
                $changedBy,
                $id,
                $visitId,
                'Medical Records',
                'DEMOGRAPHIC_UPDATE_REJECTED',
                'Rejected a stale demographic update for patient #' . $id . '.',
                $this->currentDepartmentId(),
                'WARNING',
                'DEMOGRAPHIC_UPDATE_REJECTED'
            );

            return [
                'success' => false,
                'conflict' => true,
                'current_version' => $currentVersion,
                'errors' => [
                    'This patient was updated by another user. Reload the record and review the newer information before trying again.'
                ]
            ];
        }

        $updated = $this->normalizeDemographics($patient, $current);
        $errors = $this->validate($updated);

        if ($errors !== []) {
            $this->pdo->rollBack();

            return [
                'success' => false,
                'conflict' => false,
                'current_version' => $currentVersion,
                'errors' => $errors
            ];
        }

        $changedFields = [];
        $previousValues = [];
        $newValues = [];

        foreach (self::DEMOGRAPHIC_FIELDS as $field) {
            $previous = $current[$field] ?? null;
            $new = $updated[$field] ?? null;

            if ((string)$previous === (string)$new) {
                continue;
            }

            $changedFields[] = $field;
            $previousValues[$field] = $previous;
            $newValues[$field] = $new;
        }

        if ($changedFields === []) {
            $this->pdo->commit();

            return [
                'success' => true,
                'patient_id' => $id,
                'demographic_version' => $currentVersion,
                'changed_fields' => [],
                'errors' => []
            ];
        }

        $newVersion = $currentVersion + 1;
        $changesJson = json_encode(
            $newValues,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
        );

        $amendment = $this->pdo->prepare('
            INSERT INTO record_amendments (
                patient_id,
                visit_id,
                record_type,
                record_id,
                proposed_changes,
                reason,
                status,
                requested_by,
                reviewed_by,
                reviewed_at,
                applied_at
            ) VALUES (
                :patient_id,
                :visit_id,
                \'PatientDemographics\',
                :record_id,
                :proposed_changes,
                :reason,
                \'Applied\',
                :requested_by,
                :reviewed_by,
                NOW(),
                NOW()
            )
        ');
        $amendment->execute([
            ':patient_id' => $id,
            ':visit_id' => $visitId,
            ':record_id' => $id,
            ':proposed_changes' => $changesJson,
            ':reason' => trim($reason),
            ':requested_by' => $changedBy,
            ':reviewed_by' => $changedBy
        ]);
        $amendmentId = (int)$this->pdo->lastInsertId();

        $history = $this->pdo->prepare('
            INSERT INTO patient_demographic_history (
                patient_id,
                amendment_id,
                version_no,
                previous_values,
                new_values,
                changed_fields,
                reason,
                changed_by
            ) VALUES (
                :patient_id,
                :amendment_id,
                :version_no,
                :previous_values,
                :new_values,
                :changed_fields,
                :reason,
                :changed_by
            )
        ');
        $history->execute([
            ':patient_id' => $id,
            ':amendment_id' => $amendmentId,
            ':version_no' => $newVersion,
            ':previous_values' => json_encode(
                $previousValues,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
            ),
            ':new_values' => $changesJson,
            ':changed_fields' => json_encode(
                $changedFields,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
            ),
            ':reason' => trim($reason),
            ':changed_by' => $changedBy
        ]);

        $stmt = $this->pdo->prepare('
            UPDATE patients
            SET first_name = :first_name,
                normalized_first_name = :normalized_first_name,
                middle_name = :middle_name,
                normalized_middle_name = :normalized_middle_name,
                last_name = :last_name,
                normalized_last_name = :normalized_last_name,
                gender = :gender,
                date_of_birth = :date_of_birth,
                marital_status = :marital_status,
                occupation = :occupation,
                place_of_work = :place_of_work,
                phone = :phone,
                whatsapp_number = :whatsapp_number,
                normalized_phone = :normalized_phone,
                email = :email,
                normalized_email = :normalized_email,
                address = :address,
                state_of_origin = :state_of_origin,
                nationality = :nationality,
                ethnic_group = :ethnic_group,
                religion = :religion,
                blood_group = :blood_group,
                genotype = :genotype,
                next_of_kin = :next_of_kin,
                next_of_kin_relationship = :next_of_kin_relationship,
                next_of_kin_phone = :next_of_kin_phone,
                next_of_kin_address = :next_of_kin_address,
                demographic_version = :demographic_version
            WHERE id = :id
        ');
        $stmt->execute([
            ':first_name' => $updated['first_name'],
            ':normalized_first_name' => $this->normalizeName($updated['first_name']),
            ':middle_name' => $updated['middle_name'],
            ':normalized_middle_name' => $this->normalizeName($updated['middle_name']),
            ':last_name' => $updated['last_name'],
            ':normalized_last_name' => $this->normalizeName($updated['last_name']),
            ':gender' => $updated['gender'],
            ':date_of_birth' => $updated['date_of_birth'],
            ':marital_status' => $updated['marital_status'],
            ':occupation' => $updated['occupation'],
            ':place_of_work' => $updated['place_of_work'],
            ':phone' => $updated['phone'],
            ':whatsapp_number' => $updated['whatsapp_number'] ?? null,
            ':normalized_phone' => $this->normalizePhone($updated['phone']),
            ':email' => $updated['email'],
            ':normalized_email' => $this->normalizeEmail($updated['email']),
            ':address' => $updated['address'],
            ':state_of_origin' => $updated['state_of_origin'],
            ':nationality' => $updated['nationality'],
            ':ethnic_group' => $updated['ethnic_group'],
            ':religion' => $updated['religion'],
            ':blood_group' => $updated['blood_group'],
            ':genotype' => $updated['genotype'],
            ':next_of_kin' => $updated['next_of_kin'],
            ':next_of_kin_relationship' => $updated['next_of_kin_relationship'],
            ':next_of_kin_phone' => $updated['next_of_kin_phone'],
            ':next_of_kin_address' => $updated['next_of_kin_address'],
            ':demographic_version' => $newVersion,
            ':id' => $id
        ]);

        foreach ([
            'DEMOGRAPHICS_UPDATED' => 'Updated demographics for patient #' . $id . '.',
            'DEMOGRAPHIC_HISTORY_CREATED' => 'Created demographic history version ' . $newVersion . ' for patient #' . $id . '.'
        ] as $action => $description) {
            if (!$this->auditService->logPatient(
                $changedBy,
                $id,
                $visitId,
                'Medical Records',
                $action,
                $description,
                $this->currentDepartmentId(),
                'INFO',
                $action
            )) {
                throw new RuntimeException('Unable to record demographic audit.');
            }
        }

        $this->pdo->commit();

        return [
            'success' => true,
            'patient_id' => $id,
            'amendment_id' => $amendmentId,
            'demographic_version' => $newVersion,
            'changed_fields' => $changedFields,
            'errors' => []
        ];
    } catch (Throwable $exception) {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }

        return [
            'success' => false,
            'conflict' => false,
            'errors' => ['Unable to update patient.']
        ];
    }
}

    private function normalizeDemographics(
        array $patient,
        ?array $current = null
    ): array {
        $normalized = [];
        $required = [
            'first_name',
            'last_name',
            'gender',
            'date_of_birth'
        ];

        foreach (self::DEMOGRAPHIC_FIELDS as $field) {
            $value = array_key_exists($field, $patient)
                ? $patient[$field]
                : ($current[$field] ?? null);
            $value = trim((string)$value);
            $normalized[$field] = in_array($field, $required, true)
                ? $value
                : ($value === '' ? null : $value);
        }

        if (array_key_exists('allergies', $patient)) {
            $normalized['allergies'] = trim((string)$patient['allergies']);
        } elseif ($current !== null) {
            $normalized['allergies'] = (string)($current['allergies'] ?? '');
        }

        if (!empty($patient['duplicate_review_ack'])) {
            $normalized['duplicate_review_ack'] = '1';
        }

        return $normalized;
    }

    private function findPossibleDuplicatesInternal(
        array $patient,
        ?int $excludePatientId = null
    ): array {
        $first = $this->normalizeName($patient['first_name'] ?? null);
        $middle = $this->normalizeName($patient['middle_name'] ?? null);
        $last = $this->normalizeName($patient['last_name'] ?? null);
        $phone = $this->normalizePhone($patient['phone'] ?? null);
        $email = $this->normalizeEmail($patient['email'] ?? null);
        $dob = trim((string)($patient['date_of_birth'] ?? ''));
        $hospitalNumber = trim((string)($patient['hospital_number'] ?? ''));
        $alternateIdentifier = $this->normalizeIdentifierValue(
            (string)($patient['alternate_identifier'] ?? '')
        );

        if ($first === '' || $last === '') {
            return [];
        }

        $conditions = [
            '(normalized_first_name = :first_name
              AND normalized_last_name = :last_name
              AND date_of_birth = :date_of_birth)'
        ];
        $params = [
            ':first_name' => $first,
            ':last_name' => $last,
            ':date_of_birth' => $dob
        ];
        if ($phone !== '') {
            $conditions[] = 'normalized_phone = :phone';
            $params[':phone'] = $phone;
        }
        if ($email !== '') {
            $conditions[] = 'normalized_email = :email';
            $params[':email'] = $email;
        }
        if ($hospitalNumber !== '') {
            $conditions[] = 'hospital_number = :hospital_number';
            $params[':hospital_number'] = $hospitalNumber;
        }
        if ($alternateIdentifier !== '') {
            $conditions[] = 'EXISTS (
                SELECT 1 FROM patient_identifiers pi
                WHERE pi.patient_id = patients.id
                  AND pi.is_active = 1
                  AND pi.normalized_value = :alternate_identifier
            )';
            $params[':alternate_identifier'] = $alternateIdentifier;
        }

        $baseConditions = [];
        if ($this->patientSoftDeleteAvailable()) {
            $baseConditions[] = 'COALESCE(is_deleted, 0) = 0';
        }
        $baseConditions[] = '(' . implode(' OR ', $conditions) . ')';

        $sql = 'SELECT * FROM patients WHERE ' . implode(' AND ', $baseConditions);
        if ($excludePatientId !== null) {
            $sql .= ' AND id <> :exclude_id';
            $params[':exclude_id'] = $excludePatientId;
        }
        $sql .= ' ORDER BY id LIMIT 50';
        if ($this->pdo->inTransaction()) {
            $sql .= ' FOR UPDATE';
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $strong = $this->settingsService->getInteger('mpi.strong_match_threshold', 80);
        $possible = $this->settingsService->getInteger(
            'mpi.duplicate_threshold',
            $this->settingsService->getInteger('mpi.possible_match_threshold', 55)
        );
        $exact = $this->settingsService->getInteger('mpi.exact_match_threshold', 100);
        $results = [];
        $alternatePatientIds = [];
        if ($alternateIdentifier !== '') {
            $identifierMatches = $this->pdo->prepare('
                SELECT patient_id FROM patient_identifiers
                WHERE is_active = 1 AND normalized_value = :normalized_value
            ');
            $identifierMatches->execute([':normalized_value' => $alternateIdentifier]);
            $alternatePatientIds = array_fill_keys(
                array_map('intval', $identifierMatches->fetchAll(PDO::FETCH_COLUMN)),
                true
            );
        }

        foreach ($rows as $row) {
            $score = 0;
            $factors = [];
            if ($hospitalNumber !== ''
                && $hospitalNumber === (string)$row['hospital_number']
            ) {
                $score = 100;
                $factors[] = 'Exact hospital number';
            }
            if (isset($alternatePatientIds[(int)$row['id']])) {
                $score = 100;
                $factors[] = 'Exact alternate identifier';
            }
            if ($phone !== '' && $phone === (string)$row['normalized_phone']) {
                $score += 35;
                $factors[] = 'Exact normalized phone';
            }
            if ($email !== '' && $email === (string)$row['normalized_email']) {
                $score += 25;
                $factors[] = 'Exact normalized email';
            }
            if ($dob !== '' && $dob === (string)$row['date_of_birth']) {
                $score += 25;
                $factors[] = 'Exact date of birth';
            }
            if ($first === (string)$row['normalized_first_name']) {
                $score += 15;
                $factors[] = 'Exact first name';
            }
            if ($last === (string)$row['normalized_last_name']) {
                $score += 20;
                $factors[] = 'Exact last name';
            }
            if ($middle !== '' && $middle === (string)$row['normalized_middle_name']) {
                $score += 5;
                $factors[] = 'Exact middle name';
            }
            if (($patient['gender'] ?? '') !== ''
                && ($patient['gender'] ?? '') === ($row['gender'] ?? '')
            ) {
                $score += 5;
                $factors[] = 'Same gender';
            }
            $score = min(100, $score);
            $classification = $score >= $exact
                ? 'Exact Match'
                : ($score >= $strong
                    ? 'Strong Possible Match'
                    : ($score >= $possible ? 'Possible Match' : 'Low Confidence'));
            $row['match_score'] = $score;
            $row['classification'] = $classification;
            $row['matched_factors'] = $factors;
            $results[] = $row;
        }

        usort($results, static fn (array $a, array $b): int =>
            $b['match_score'] <=> $a['match_score']
        );
        return $results;
    }

    private function createDuplicateCandidate(
        int $firstPatientId,
        int $secondPatientId,
        float $score,
        string $classification,
        array $factors,
        int $detectedBy
    ): void {
        if ($firstPatientId === $secondPatientId) {
            return;
        }
        $low = min($firstPatientId, $secondPatientId);
        $high = max($firstPatientId, $secondPatientId);
        $stmt = $this->pdo->prepare('
            INSERT IGNORE INTO patient_duplicate_candidates (
                patient_id_low, patient_id_high, match_score,
                classification, matched_factors, detected_by
            ) VALUES (
                :low_id, :high_id, :score, :classification,
                :factors, :detected_by
            )
        ');
        $stmt->execute([
            ':low_id' => $low,
            ':high_id' => $high,
            ':score' => $score,
            ':classification' => $classification,
            ':factors' => json_encode($factors, JSON_THROW_ON_ERROR),
            ':detected_by' => $detectedBy
        ]);
        if ($stmt->rowCount() > 0) {
            foreach ([$low, $high] as $patientId) {
                if (!$this->auditService->logPatient(
                    $detectedBy,
                    $patientId,
                    null,
                    'Medical Records',
                    'DUPLICATE_CANDIDATE_CREATED',
                    'Created duplicate candidate case for patient #' . $patientId . '.',
                    null,
                    'WARNING',
                    'DUPLICATE_CANDIDATE_CREATED'
                )) {
                    throw new RuntimeException('Unable to audit duplicate candidate creation.');
                }
            }
        }
    }

    private function normalizeName(mixed $value): string
    {
        return mb_strtolower(trim((string)$value), 'UTF-8');
    }

    private function normalizePhone(mixed $value): string
    {
        return preg_replace('/\D+/', '', trim((string)$value)) ?? '';
    }

    private function normalizeEmail(mixed $value): string
    {
        return mb_strtolower(trim((string)$value), 'UTF-8');
    }

    private function normalizeIdentifierValue(string $value): string
    {
        return preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($value))) ?? '';
    }

    private function patientDeletionBlockers(int $patientId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT
                k.TABLE_NAME AS table_name,
                k.COLUMN_NAME AS column_name,
                rc.DELETE_RULE AS delete_rule
            FROM information_schema.KEY_COLUMN_USAGE k
            INNER JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
                ON rc.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA
               AND rc.CONSTRAINT_NAME = k.CONSTRAINT_NAME
               AND rc.TABLE_NAME = k.TABLE_NAME
            WHERE k.TABLE_SCHEMA = DATABASE()
              AND k.REFERENCED_TABLE_NAME = "patients"
              AND rc.DELETE_RULE IN ("RESTRICT", "NO ACTION")
            ORDER BY k.TABLE_NAME, k.COLUMN_NAME
        ');
        $stmt->execute();

        $blockers = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $reference) {
            $table = (string)$reference['table_name'];
            $column = (string)$reference['column_name'];
            if ($table === '' || $column === '') {
                continue;
            }

            $safeTable = '`' . str_replace('`', '``', $table) . '`';
            $safeColumn = '`' . str_replace('`', '``', $column) . '`';
            $count = $this->pdo
                ->prepare("SELECT COUNT(*) FROM {$safeTable} WHERE {$safeColumn} = :patient_id");
            $count->execute([':patient_id' => $patientId]);

            if ((int)$count->fetchColumn() > 0) {
                $blockers[] = $table;
            }
        }

        return array_values(array_unique($blockers));
    }

    private function patientSoftDeleteAvailable(): bool
    {
        if ($this->patientSoftDeleteAvailable !== null) {
            return $this->patientSoftDeleteAvailable;
        }

        try {
            $stmt = $this->pdo->prepare('
                SELECT COUNT(*)
                FROM information_schema.columns
                WHERE table_schema = DATABASE()
                  AND table_name = :table
                  AND column_name = :column
            ');
            $stmt->execute([
                ':table' => 'patients',
                ':column' => 'is_deleted',
            ]);
            $this->patientSoftDeleteAvailable = (int)$stmt->fetchColumn() > 0;
        } catch (Throwable) {
            $this->patientSoftDeleteAvailable = false;
        }

        return $this->patientSoftDeleteAvailable;
    }

    private function currentUserId(): ?int
    {
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        return $userId > 0 ? $userId : null;
    }

    private function currentDepartmentId(): ?int
    {
        $departmentId = (int)(
            $_SESSION['active_department_id']
            ?? $_SESSION['user']['active_department_id']
            ?? $_SESSION['user']['department_id']
            ?? 0
        );

        return $departmentId > 0 ? $departmentId : null;
    }
}
