<?php

declare(strict_types=1);

require_once __DIR__ . '/AuditService.php';
require_once __DIR__ . '/PermissionService.php';

class VitalSignsService
{
    private AuditService $auditService;
    private PermissionService $permissionService;

    public function __construct(
        private PDO $pdo,
        ?AuditService $auditService = null,
        ?PermissionService $permissionService = null
    ) {
        $this->auditService = $auditService ?? new AuditService($pdo);
        $this->permissionService = $permissionService ?? new PermissionService($pdo);
    }

    public function create(array $data, array $user): array
    {
        try {
            $this->pdo->beginTransaction();
            $visit = $this->lockVisit((int)($data['visit_id'] ?? 0));

            $errors = $this->validateMutation($visit, $user, 'create_vital_signs');
            if (isset($data['patient_id']) && (int)$data['patient_id'] !== (int)$visit['patient_id']) {
                $errors[] = 'Patient and encounter do not match.';
            }

            $payload = $this->preparePayload($data);
            $errors = array_merge($errors, $payload['errors']);

            if ($errors !== []) {
                $this->rollback();
                return $this->failure($errors);
            }

            $stmt = $this->pdo->prepare('
                INSERT INTO vital_signs (
                    visit_id, patient_id, department_id, recorded_by,
                    temperature, pulse, respiratory_rate, systolic_bp, diastolic_bp,
                    oxygen_saturation, weight, height, bmi, blood_glucose, pain_score, notes
                ) VALUES (
                    :visit_id, :patient_id, :department_id, :recorded_by,
                    :temperature, :pulse, :respiratory_rate, :systolic_bp, :diastolic_bp,
                    :oxygen_saturation, :weight, :height, :bmi, :blood_glucose, :pain_score, :notes
                )
            ');
            $stmt->execute([
                ':visit_id' => (int)$visit['id'],
                ':patient_id' => (int)$visit['patient_id'],
                ':department_id' => $this->recordingDepartmentId($visit, $user),
                ':recorded_by' => (int)($user['id'] ?? 0),
                ':temperature' => $payload['temperature'],
                ':pulse' => $payload['pulse'],
                ':respiratory_rate' => $payload['respiratory_rate'],
                ':systolic_bp' => $payload['systolic_bp'],
                ':diastolic_bp' => $payload['diastolic_bp'],
                ':oxygen_saturation' => $payload['oxygen_saturation'],
                ':weight' => $payload['weight'],
                ':height' => $payload['height'],
                ':bmi' => $payload['bmi'],
                ':blood_glucose' => $payload['blood_glucose'],
                ':pain_score' => $payload['pain_score'],
                ':notes' => $payload['notes']
            ]);

            $vitalSignsId = (int)$this->pdo->lastInsertId();
            if (!$this->audit(
                'VITAL_SIGNS_CREATED',
                $visit,
                $user,
                'Recorded vital signs #' . $vitalSignsId . '.'
            )) {
                throw new RuntimeException('Unable to audit vital signs creation.');
            }

            $this->pdo->commit();

            return [
                'success' => true,
                'vital_signs_id' => $vitalSignsId,
                'visit_id' => (int)$visit['id'],
                'patient_id' => (int)$visit['patient_id'],
                'errors' => []
            ];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to save vital signs.']);
        }
    }

    public function getById(int $vitalSignsId, ?array $user = null): ?array
    {
        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE vs.id = :id LIMIT 1');
        $stmt->execute([':id' => $vitalSignsId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        if ($user !== null && !$this->canViewRow($row, $user)) {
            return null;
        }

        return $this->decorateRow($row);
    }

    public function getLatestByVisit(int $visitId, ?array $user = null): ?array
    {
        $records = $this->listByVisit($visitId, $user, 1);
        return $records[0] ?? null;
    }

    public function listByVisit(int $visitId, ?array $user = null, int $limit = 0): array
    {
        $visit = $this->visitById($visitId);
        if (!$visit) {
            return [];
        }

        if ($user !== null && !$this->permissionService->canViewVitalSigns((int)$visit['patient_id'], $user)) {
            return [];
        }

        $sql = $this->baseSelect() . ' WHERE vs.visit_id = :visit_id ORDER BY vs.created_at DESC, vs.id DESC';
        if ($limit > 0) {
            $sql .= ' LIMIT ' . (int)$limit;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':visit_id' => $visitId]);
        return array_map([$this, 'decorateRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function listByPatient(int $patientId, ?array $user = null): array
    {
        if ($patientId <= 0) {
            return [];
        }

        if ($user !== null && !$this->permissionService->canViewVitalSigns($patientId, $user)) {
            return [];
        }

        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE vs.patient_id = :patient_id ORDER BY vs.created_at DESC, vs.id DESC');
        $stmt->execute([':patient_id' => $patientId]);
        return array_map([$this, 'decorateRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function update(int $vitalSignsId, array $data, array $user): array
    {
        try {
            $this->pdo->beginTransaction();

            $record = $this->lockRecord($vitalSignsId);
            if (!$record) {
                $this->rollback();
                return $this->failure(['Vital signs record not found.']);
            }

            $visit = $this->lockVisit((int)$record['visit_id']);
            $errors = $this->validateMutation($visit, $user, 'edit_vital_signs');
            if ((int)$visit['patient_id'] !== (int)$record['patient_id']) {
                $errors[] = 'Patient and encounter do not match.';
            }

            $payload = $this->preparePayload($data, $record);
            $errors = array_merge($errors, $payload['errors']);

            if ($errors !== []) {
                $this->rollback();
                return $this->failure($errors);
            }

            $stmt = $this->pdo->prepare('
                UPDATE vital_signs
                SET department_id = :department_id,
                    temperature = :temperature,
                    pulse = :pulse,
                    respiratory_rate = :respiratory_rate,
                    systolic_bp = :systolic_bp,
                    diastolic_bp = :diastolic_bp,
                    oxygen_saturation = :oxygen_saturation,
                    weight = :weight,
                    height = :height,
                    bmi = :bmi,
                    blood_glucose = :blood_glucose,
                    pain_score = :pain_score,
                    notes = :notes
                WHERE id = :id
            ');
            $stmt->execute([
                ':department_id' => $this->recordingDepartmentId($visit, $user),
                ':temperature' => $payload['temperature'],
                ':pulse' => $payload['pulse'],
                ':respiratory_rate' => $payload['respiratory_rate'],
                ':systolic_bp' => $payload['systolic_bp'],
                ':diastolic_bp' => $payload['diastolic_bp'],
                ':oxygen_saturation' => $payload['oxygen_saturation'],
                ':weight' => $payload['weight'],
                ':height' => $payload['height'],
                ':bmi' => $payload['bmi'],
                ':blood_glucose' => $payload['blood_glucose'],
                ':pain_score' => $payload['pain_score'],
                ':notes' => $payload['notes'],
                ':id' => $vitalSignsId
            ]);

            if (!$this->audit(
                'VITAL_SIGNS_UPDATED',
                $visit,
                $user,
                'Updated vital signs #' . $vitalSignsId . '.'
            )) {
                throw new RuntimeException('Unable to audit vital signs update.');
            }

            $this->pdo->commit();

            return [
                'success' => true,
                'vital_signs_id' => $vitalSignsId,
                'visit_id' => (int)$visit['id'],
                'patient_id' => (int)$visit['patient_id'],
                'errors' => []
            ];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to update vital signs.']);
        }
    }

    public function canViewVitalSigns(array $encounter, ?array $user = null): bool
    {
        $patientId = (int)($encounter['patient_id'] ?? 0);
        return $patientId > 0 && $this->permissionService->canViewVitalSigns($patientId, $user);
    }

    public function canCreateVitalSigns(array $encounter, ?array $user = null): bool
    {
        return $this->canMutateVitalSigns('create_vital_signs', $encounter, $user);
    }

    public function canEditVitalSigns(array $encounter, ?array $user = null): bool
    {
        return $this->canMutateVitalSigns('edit_vital_signs', $encounter, $user);
    }

    private function canMutateVitalSigns(string $permission, array $encounter, ?array $user = null): bool
    {
        $user = $user ?? $this->currentUser();
        if (!$user) {
            return false;
        }

        if (in_array((string)($encounter['visit_status'] ?? ''), ['Completed', 'Cancelled'], true)) {
            return false;
        }

        if ($this->permissionService->isAdministrator($user)) {
            return true;
        }

        return $this->permissionService->hasPermission($permission, $user)
            && in_array((string)($user['role_name'] ?? ''), ['Doctor', 'Nurse'], true)
            && $this->permissionService->canViewEncounter($encounter, $user);
    }

    private function validateMutation(array $visit, array $user, string $permission): array
    {
        $errors = [];
        if (!$this->permissionService->hasPermission($permission, $user)) {
            $errors[] = 'You are not allowed to perform this vital signs action.';
        }
        if (!$this->permissionService->canViewEncounter($visit, $user)) {
            $errors[] = 'You cannot access this encounter.';
        }
        if (in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true)) {
            $errors[] = 'Completed or cancelled encounters are read-only.';
        }
        return $errors;
    }

    private function preparePayload(array $data, ?array $existing = null): array
    {
        $errors = [];
        $temperature = $this->numberOrNull($data['temperature'] ?? null);
        $pulse = $this->intOrNull($data['pulse'] ?? null);
        $respiratoryRate = $this->intOrNull($data['respiratory_rate'] ?? null);
        $systolic = $this->intOrNull($data['systolic_bp'] ?? null);
        $diastolic = $this->intOrNull($data['diastolic_bp'] ?? null);
        $oxygen = $this->numberOrNull($data['oxygen_saturation'] ?? null);
        $weight = $this->numberOrNull($data['weight'] ?? null);
        $height = $this->numberOrNull($data['height'] ?? null);
        $bmiInput = $this->numberOrNull($data['bmi'] ?? null);
        $bloodGlucose = $this->numberOrNull($data['blood_glucose'] ?? null);
        $painScore = $this->intOrNull($data['pain_score'] ?? null);
        $notes = $this->nullableText($data['notes'] ?? null);

        if ($temperature !== null && ($temperature < 30 || $temperature > 45)) {
            $errors[] = 'Temperature must be between 30 and 45 C.';
        }
        if ($pulse !== null && ($pulse < 20 || $pulse > 250)) {
            $errors[] = 'Pulse must be between 20 and 250 bpm.';
        }
        if ($respiratoryRate !== null && ($respiratoryRate < 5 || $respiratoryRate > 80)) {
            $errors[] = 'Respiratory rate must be between 5 and 80.';
        }
        if ($systolic !== null && ($systolic < 40 || $systolic > 260)) {
            $errors[] = 'Systolic blood pressure must be between 40 and 260.';
        }
        if ($diastolic !== null && ($diastolic < 20 || $diastolic > 160)) {
            $errors[] = 'Diastolic blood pressure must be between 20 and 160.';
        }
        if ($oxygen !== null && ($oxygen < 0 || $oxygen > 100)) {
            $errors[] = 'Oxygen saturation must be between 0 and 100.';
        }
        if ($weight !== null && $weight <= 0) {
            $errors[] = 'Weight must be positive.';
        }
        if ($height !== null && $height <= 0) {
            $errors[] = 'Height must be positive.';
        }
        if ($bmiInput !== null && ($bmiInput < 5 || $bmiInput > 100)) {
            $errors[] = 'BMI must be between 5 and 100.';
        }
        if ($bloodGlucose !== null && $bloodGlucose < 0) {
            $errors[] = 'Blood glucose must be positive.';
        }
        if ($painScore !== null && ($painScore < 0 || $painScore > 10)) {
            $errors[] = 'Pain score must be between 0 and 10.';
        }

        $bmi = null;
        if ($weight !== null && $height !== null) {
            $bmi = round($weight / pow($height / 100, 2), 2);
        } elseif ($bmiInput !== null) {
            $bmi = $bmiInput;
        } elseif ($existing !== null) {
            $bmi = $this->numberOrNull($existing['bmi'] ?? null);
        }

        if ($this->allMeasurementsEmpty([
            $temperature,
            $pulse,
            $respiratoryRate,
            $systolic,
            $diastolic,
            $oxygen,
            $weight,
            $height,
            $bmi,
            $bloodGlucose,
            $painScore,
            $notes
        ])) {
            $errors[] = 'Enter at least one vital sign value or note.';
        }

        return [
            'errors' => $errors,
            'temperature' => $temperature,
            'pulse' => $pulse,
            'respiratory_rate' => $respiratoryRate,
            'systolic_bp' => $systolic,
            'diastolic_bp' => $diastolic,
            'oxygen_saturation' => $oxygen,
            'weight' => $weight,
            'height' => $height,
            'bmi' => $bmi,
            'blood_glucose' => $bloodGlucose,
            'pain_score' => $painScore,
            'notes' => $notes
        ];
    }

    private function allMeasurementsEmpty(array $values): bool
    {
        foreach ($values as $value) {
            if ($value !== null && $value !== '') {
                return false;
            }
        }
        return true;
    }

    private function numberOrNull(mixed $value): ?float
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }
        return is_numeric($value) ? (float)$value : null;
    }

    private function intOrNull(mixed $value): ?int
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }
        return is_numeric($value) ? (int)round((float)$value) : null;
    }

    private function nullableText(mixed $value): ?string
    {
        $value = trim((string)$value);
        return $value === '' ? null : $value;
    }

    private function recordingDepartmentId(array $visit, array $user): ?int
    {
        $departmentId = (int)($visit['current_department_id'] ?? 0);
        if ($departmentId > 0) {
            return $departmentId;
        }
        return (int)($user['department_id'] ?? 0) ?: null;
    }

    private function lockVisit(int $visitId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM visits WHERE id = :id FOR UPDATE');
        $stmt->execute([':id' => $visitId]);
        $visit = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$visit) {
            throw new RuntimeException('Encounter not found.');
        }
        return $visit;
    }

    private function visitById(int $visitId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM visits WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $visitId]);
        $visit = $stmt->fetch(PDO::FETCH_ASSOC);
        return $visit ?: null;
    }

    private function lockRecord(int $vitalSignsId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM vital_signs WHERE id = :id FOR UPDATE');
        $stmt->execute([':id' => $vitalSignsId]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        return $record ?: null;
    }

    private function decorateRow(array $row): array
    {
        $systolic = trim((string)($row['systolic_bp'] ?? ''));
        $diastolic = trim((string)($row['diastolic_bp'] ?? ''));
        $row['blood_pressure'] = $systolic === '' && $diastolic === ''
            ? null
            : trim($systolic . '/' . $diastolic, '/');
        return $row;
    }

    private function canViewRow(array $row, array $user): bool
    {
        return $this->permissionService->canViewVitalSigns((int)($row['patient_id'] ?? 0), $user);
    }

    private function audit(
        string $action,
        array $visit,
        array $user,
        string $description
    ): bool {
        return $this->auditService->logPatient(
            (int)($user['id'] ?? 0),
            (int)$visit['patient_id'],
            (int)$visit['id'],
            'Vital Signs',
            $action,
            $description,
            (int)($visit['current_department_id'] ?? 0) ?: null,
            'INFO',
            $action
        );
    }

    private function baseSelect(): string
    {
        return '
            SELECT vs.*,
                   v.visit_number,
                   v.visit_status,
                   p.hospital_number,
                   p.first_name,
                   p.last_name,
                   d.department_name,
                   CONCAT(recorder.first_name, " ", recorder.last_name) AS recorded_by_name
            FROM vital_signs vs
            INNER JOIN visits v ON v.id = vs.visit_id
            INNER JOIN patients p ON p.id = vs.patient_id
            LEFT JOIN departments d ON d.id = vs.department_id
            LEFT JOIN users recorder ON recorder.id = vs.recorded_by
        ';
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

    private function currentUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }
}
