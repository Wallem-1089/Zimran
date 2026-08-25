<?php

declare(strict_types=1);

require_once __DIR__ . '/AuditService.php';
require_once __DIR__ . '/PermissionService.php';

class DiabetesMonitoringService
{
    private const MEAL_STATUSES = ['Before Meal', 'After Meal', 'Fasting', 'Random', 'Bedtime', 'Not Recorded'];

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
            $errors = $this->validateMutation($visit, $user, 'create_nursing');
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
                INSERT INTO diabetes_monitoring (
                    visit_id, patient_id, recorded_at, blood_glucose,
                    insulin_given, meal_status, symptoms, notes, recorded_by
                ) VALUES (
                    :visit_id, :patient_id, :recorded_at, :blood_glucose,
                    :insulin_given, :meal_status, :symptoms, :notes, :recorded_by
                )
            ');
            $stmt->execute([
                ':visit_id' => (int)$visit['id'],
                ':patient_id' => (int)$visit['patient_id'],
                ':recorded_at' => $payload['data']['recorded_at'],
                ':blood_glucose' => $payload['data']['blood_glucose'],
                ':insulin_given' => $payload['data']['insulin_given'],
                ':meal_status' => $payload['data']['meal_status'],
                ':symptoms' => $payload['data']['symptoms'],
                ':notes' => $payload['data']['notes'],
                ':recorded_by' => (int)$user['id'],
            ]);

            $recordId = (int)$this->pdo->lastInsertId();
            if (!$this->audit('DIABETES_MONITORING_RECORDED', $visit, $user, 'Recorded DM Sheet entry #' . $recordId . '.')) {
                throw new RuntimeException('Unable to audit DM Sheet entry.');
            }

            $this->pdo->commit();
            return [
                'success' => true,
                'diabetes_monitoring_id' => $recordId,
                'visit_id' => (int)$visit['id'],
                'patient_id' => (int)$visit['patient_id'],
                'errors' => [],
            ];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to save DM Sheet entry.']);
        }
    }

    public function update(int $recordId, array $data, array $user): array
    {
        try {
            $this->pdo->beginTransaction();
            $record = $this->lockRecord($recordId);
            if (!$record) {
                $this->rollback();
                return $this->failure(['DM Sheet entry not found.']);
            }
            $visit = $this->lockVisit((int)$record['visit_id']);
            $errors = $this->validateMutation($visit, $user, 'edit_nursing');
            $payload = $this->preparePayload($data);
            $errors = array_merge($errors, $payload['errors']);

            if ($errors !== []) {
                $this->rollback();
                return $this->failure($errors);
            }

            $stmt = $this->pdo->prepare('
                UPDATE diabetes_monitoring
                SET recorded_at = :recorded_at,
                    blood_glucose = :blood_glucose,
                    insulin_given = :insulin_given,
                    meal_status = :meal_status,
                    symptoms = :symptoms,
                    notes = :notes
                WHERE id = :id
            ');
            $stmt->execute([
                ':recorded_at' => $payload['data']['recorded_at'],
                ':blood_glucose' => $payload['data']['blood_glucose'],
                ':insulin_given' => $payload['data']['insulin_given'],
                ':meal_status' => $payload['data']['meal_status'],
                ':symptoms' => $payload['data']['symptoms'],
                ':notes' => $payload['data']['notes'],
                ':id' => $recordId,
            ]);

            if (!$this->audit('DIABETES_MONITORING_UPDATED', $visit, $user, 'Updated DM Sheet entry #' . $recordId . '.')) {
                throw new RuntimeException('Unable to audit DM Sheet update.');
            }

            $this->pdo->commit();
            return [
                'success' => true,
                'diabetes_monitoring_id' => $recordId,
                'visit_id' => (int)$visit['id'],
                'patient_id' => (int)$visit['patient_id'],
                'errors' => [],
            ];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to update DM Sheet entry.']);
        }
    }

    public function getById(int $recordId, ?array $user = null): ?array
    {
        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE dm.id = :id LIMIT 1');
        $stmt->execute([':id' => $recordId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || ($user !== null && !$this->canViewRow($row, $user))) {
            return null;
        }
        return $this->decorateRow($row);
    }

    public function listByVisit(int $visitId, ?array $user = null): array
    {
        $visit = $this->visitById($visitId);
        if (!$visit || ($user !== null && !$this->canViewRow($visit, $user))) {
            return [];
        }
        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE dm.visit_id = :visit_id ORDER BY dm.recorded_at DESC, dm.id DESC');
        $stmt->execute([':visit_id' => $visitId]);
        return array_map([$this, 'decorateRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function listByPatient(int $patientId, ?array $user = null): array
    {
        if ($patientId <= 0 || ($user !== null && !$this->permissionService->canViewNursing($patientId, $user))) {
            return [];
        }
        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE dm.patient_id = :patient_id ORDER BY dm.recorded_at DESC, dm.id DESC');
        $stmt->execute([':patient_id' => $patientId]);
        return array_map([$this, 'decorateRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getMealStatuses(): array
    {
        return self::MEAL_STATUSES;
    }

    private function preparePayload(array $data): array
    {
        $errors = [];
        $recordedAt = trim((string)($data['recorded_at'] ?? ''));
        if ($recordedAt === '') {
            $errors[] = 'Recorded time is required.';
        } else {
            $recordedAt = str_replace('T', ' ', $recordedAt);
            if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $recordedAt)) {
                $recordedAt .= ':00';
            } elseif (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $recordedAt)) {
                $errors[] = 'Recorded time is invalid.';
            }
        }

        $bloodGlucose = trim((string)($data['blood_glucose'] ?? ''));
        if ($bloodGlucose === '' || !is_numeric($bloodGlucose)) {
            $errors[] = 'Blood glucose is required.';
            $bloodGlucose = '0';
        } elseif ((float)$bloodGlucose <= 0 || (float)$bloodGlucose > 1000) {
            $errors[] = 'Blood glucose is outside a practical range.';
        }

        $insulinGiven = $this->nullableText($data['insulin_given'] ?? null);
        if ($insulinGiven !== null && $this->textLength($insulinGiven) > 255) {
            $errors[] = 'Insulin given is too long.';
        }

        $mealStatus = trim((string)($data['meal_status'] ?? 'Not Recorded'));
        if (!in_array($mealStatus, self::MEAL_STATUSES, true)) {
            $errors[] = 'Meal status is invalid.';
        }

        $symptoms = $this->nullableText($data['symptoms'] ?? null);
        $notes = $this->nullableText($data['notes'] ?? null);
        foreach (['Symptoms' => $symptoms, 'Notes' => $notes] as $label => $value) {
            if ($value !== null && $this->textLength($value) > 5000) {
                $errors[] = $label . ' are too long.';
            }
        }

        return [
            'errors' => $errors,
            'data' => [
                'recorded_at' => $recordedAt,
                'blood_glucose' => number_format((float)$bloodGlucose, 2, '.', ''),
                'insulin_given' => $insulinGiven,
                'meal_status' => $mealStatus,
                'symptoms' => $symptoms,
                'notes' => $notes,
            ],
        ];
    }

    private function validateMutation(array $visit, array $user, string $permission): array
    {
        $errors = [];
        if (!$this->permissionService->hasPermission($permission, $user)) {
            $errors[] = 'You are not allowed to perform this DM Sheet action.';
        }
        if (!$this->permissionService->canViewEncounter($visit, $user)) {
            $errors[] = 'You cannot access this encounter.';
        }
        if (!$this->permissionService->isAdministrator($user) && in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true)) {
            $errors[] = 'Completed or cancelled encounters are read-only.';
        }
        if (!$this->permissionService->isAdministrator($user) && (string)($user['role_name'] ?? '') !== 'Nurse') {
            $errors[] = 'Only nurses may record DM Sheet entries.';
        }
        return $errors;
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

    private function lockRecord(int $recordId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM diabetes_monitoring WHERE id = :id FOR UPDATE');
        $stmt->execute([':id' => $recordId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function visitById(int $visitId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM visits WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $visitId]);
        $visit = $stmt->fetch(PDO::FETCH_ASSOC);
        return $visit ?: null;
    }

    private function canViewRow(array $row, array $user): bool
    {
        return $this->permissionService->canViewNursing((int)($row['patient_id'] ?? 0), $user)
            || $this->permissionService->isAdministrator($user);
    }

    private function decorateRow(array $row): array
    {
        $row['summary'] = trim((string)$row['blood_glucose'] . ' - ' . (string)$row['meal_status']);
        return $row;
    }

    private function audit(string $action, array $visit, array $user, string $description): bool
    {
        return $this->auditService->logPatient(
            (int)($user['id'] ?? 0),
            (int)$visit['patient_id'],
            (int)$visit['id'],
            'Nursing',
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
            SELECT dm.*,
                   v.visit_number,
                   v.visit_status,
                   p.hospital_number,
                   p.first_name,
                   p.last_name,
                   CONCAT(recorded.first_name, " ", recorded.last_name) AS recorded_by_name
            FROM diabetes_monitoring dm
            INNER JOIN visits v ON v.id = dm.visit_id
            INNER JOIN patients p ON p.id = dm.patient_id
            LEFT JOIN users recorded ON recorded.id = dm.recorded_by
        ';
    }

    private function textLength(string $value): int
    {
        return function_exists('mb_strlen') ? (int)mb_strlen($value) : strlen($value);
    }

    private function nullableText(mixed $value): ?string
    {
        $text = trim((string)$value);
        return $text === '' ? null : $text;
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
