<?php

declare(strict_types=1);

require_once __DIR__ . '/AuditService.php';
require_once __DIR__ . '/PermissionService.php';

class MedicationAdministrationService
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
            $errors = $this->validateMutation($visit, $user, 'create_nursing');
            if (isset($data['patient_id']) && (int)$data['patient_id'] !== (int)$visit['patient_id']) {
                $errors[] = 'Patient and encounter do not match.';
            }

            $payload = $this->preparePayload($data, $visit);
            $errors = array_merge($errors, $payload['errors']);

            if ($errors !== []) {
                $this->rollback();
                return $this->failure($errors);
            }

            $stmt = $this->pdo->prepare('
                INSERT INTO medication_administration_records (
                    prescription_id, visit_id, patient_id, medication_name,
                    scheduled_time, dose_given, route, administration_status,
                    notes, administered_by
                ) VALUES (
                    :prescription_id, :visit_id, :patient_id, :medication_name,
                    :scheduled_time, :dose_given, :route, :administration_status,
                    :notes, :administered_by
                )
            ');
            $stmt->execute([
                ':prescription_id' => $payload['data']['prescription_id'],
                ':visit_id' => (int)$visit['id'],
                ':patient_id' => (int)$visit['patient_id'],
                ':medication_name' => $payload['data']['medication_name'],
                ':scheduled_time' => $payload['data']['scheduled_time'],
                ':dose_given' => $payload['data']['dose_given'],
                ':route' => $payload['data']['route'],
                ':administration_status' => $payload['data']['administration_status'],
                ':notes' => $payload['data']['notes'],
                ':administered_by' => (int)$user['id'],
            ]);

            $recordId = (int)$this->pdo->lastInsertId();

            if (!$this->audit('MEDICATION_ADMINISTRATION_RECORDED', $visit, $user, 'Recorded medication administration #' . $recordId . '.')) {
                throw new RuntimeException('Unable to audit medication administration.');
            }

            $this->pdo->commit();

            return [
                'success' => true,
                'medication_administration_id' => $recordId,
                'visit_id' => (int)$visit['id'],
                'patient_id' => (int)$visit['patient_id'],
                'errors' => [],
            ];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to save drug chart entry.']);
        }
    }

    public function update(int $recordId, array $data, array $user): array
    {
        try {
            $this->pdo->beginTransaction();

            $record = $this->lockRecord($recordId);
            if (!$record) {
                $this->rollback();
                return $this->failure(['Drug chart entry not found.']);
            }

            $visit = $this->lockVisit((int)$record['visit_id']);
            $errors = $this->validateMutation($visit, $user, 'edit_nursing');
            if (isset($data['patient_id']) && (int)$data['patient_id'] !== (int)$visit['patient_id']) {
                $errors[] = 'Patient and encounter do not match.';
            }

            $payload = $this->preparePayload($data, $visit);
            $errors = array_merge($errors, $payload['errors']);

            if ($errors !== []) {
                $this->rollback();
                return $this->failure($errors);
            }

            $stmt = $this->pdo->prepare('
                UPDATE medication_administration_records
                SET prescription_id = :prescription_id,
                    medication_name = :medication_name,
                    scheduled_time = :scheduled_time,
                    dose_given = :dose_given,
                    route = :route,
                    administration_status = :administration_status,
                    notes = :notes
                WHERE id = :id
            ');
            $stmt->execute([
                ':prescription_id' => $payload['data']['prescription_id'],
                ':medication_name' => $payload['data']['medication_name'],
                ':scheduled_time' => $payload['data']['scheduled_time'],
                ':dose_given' => $payload['data']['dose_given'],
                ':route' => $payload['data']['route'],
                ':administration_status' => $payload['data']['administration_status'],
                ':notes' => $payload['data']['notes'],
                ':id' => $recordId,
            ]);

            if (!$this->audit('MEDICATION_ADMINISTRATION_UPDATED', $visit, $user, 'Updated medication administration #' . $recordId . '.')) {
                throw new RuntimeException('Unable to audit medication administration update.');
            }

            $this->pdo->commit();

            return [
                'success' => true,
                'medication_administration_id' => $recordId,
                'visit_id' => (int)$visit['id'],
                'patient_id' => (int)$visit['patient_id'],
                'errors' => [],
            ];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to update drug chart entry.']);
        }
    }

    public function getById(int $recordId, ?array $user = null): ?array
    {
        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE mar.id = :id LIMIT 1');
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

        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE mar.visit_id = :visit_id ORDER BY mar.scheduled_time DESC, mar.id DESC');
        $stmt->execute([':visit_id' => $visitId]);
        return array_map([$this, 'decorateRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function listByPatient(int $patientId, ?array $user = null): array
    {
        if ($patientId <= 0 || ($user !== null && !$this->permissionService->canViewNursing($patientId, $user))) {
            return [];
        }

        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE mar.patient_id = :patient_id ORDER BY mar.scheduled_time DESC, mar.id DESC');
        $stmt->execute([':patient_id' => $patientId]);
        return array_map([$this, 'decorateRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function listPrescriptionsForVisit(int $visitId, ?array $user = null): array
    {
        $visit = $this->visitById($visitId);
        if (!$visit || ($user !== null && !$this->canViewRow($visit, $user))) {
            return [];
        }

        $stmt = $this->pdo->prepare('
            SELECT p.id, p.medication_name, p.dosage, p.frequency, p.duration,
                   p.quantity, p.status, p.instructions, p.created_at,
                   CONCAT(u.first_name, " ", u.last_name) AS prescribed_by_name
            FROM prescriptions p
            LEFT JOIN users u ON u.id = p.prescribed_by
            WHERE p.visit_id = :visit_id
              AND p.patient_id = :patient_id
              AND p.status <> "Cancelled"
            ORDER BY p.created_at DESC, p.id DESC
        ');
        $stmt->execute([
            ':visit_id' => (int)$visit['id'],
            ':patient_id' => (int)$visit['patient_id'],
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function preparePayload(array $data, array $visit): array
    {
        $errors = [];
        $prescriptionId = (int)($data['prescription_id'] ?? 0);
        $prescription = null;
        if ($prescriptionId > 0) {
            $prescription = $this->prescriptionById($prescriptionId);
            if (!$prescription) {
                $errors[] = 'Selected prescription was not found.';
            } elseif ((int)$prescription['visit_id'] !== (int)$visit['id'] || (int)$prescription['patient_id'] !== (int)$visit['patient_id']) {
                $errors[] = 'Selected prescription does not belong to this encounter.';
            } elseif ((string)$prescription['status'] === 'Cancelled') {
                $errors[] = 'Cancelled prescriptions cannot be administered.';
            }
        }

        $medicationName = trim((string)($data['medication_name'] ?? ''));
        if ($prescription !== null) {
            $medicationName = (string)$prescription['medication_name'];
        }
        if ($medicationName === '') {
            $errors[] = 'Medication is required.';
        } elseif ($this->textLength($medicationName) > 255) {
            $errors[] = 'Medication name is too long.';
        }

        $scheduledTime = trim((string)($data['scheduled_time'] ?? ''));
        if ($scheduledTime === '') {
            $errors[] = 'Scheduled/administered time is required.';
        } else {
            $scheduledTime = str_replace('T', ' ', $scheduledTime);
            if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $scheduledTime)) {
                $scheduledTime .= ':00';
            } elseif (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $scheduledTime)) {
                $errors[] = 'Scheduled/administered time is invalid.';
            }
        }

        $doseGiven = trim((string)($data['dose_given'] ?? ''));
        if ($doseGiven === '') {
            $errors[] = 'Dose given is required.';
        } elseif ($this->textLength($doseGiven) > 100) {
            $errors[] = 'Dose given is too long.';
        }

        $route = $this->nullableText($data['route'] ?? null);
        if ($route !== null && $this->textLength($route) > 100) {
            $errors[] = 'Route is too long.';
        }

        $status = trim((string)($data['administration_status'] ?? 'Given'));
        if (!in_array($status, ['Given', 'Missed', 'Refused', 'Held'], true)) {
            $errors[] = 'Administration status is invalid.';
        }

        $notes = $this->nullableText($data['notes'] ?? null);
        if ($notes !== null && $this->textLength($notes) > 5000) {
            $errors[] = 'Notes are too long.';
        }

        return [
            'errors' => $errors,
            'data' => [
                'prescription_id' => $prescriptionId > 0 ? $prescriptionId : null,
                'medication_name' => $medicationName,
                'scheduled_time' => $scheduledTime,
                'dose_given' => $doseGiven,
                'route' => $route,
                'administration_status' => $status,
                'notes' => $notes,
            ],
        ];
    }

    private function validateMutation(array $visit, array $user, string $permission): array
    {
        $errors = [];
        if (!$this->permissionService->hasPermission($permission, $user)) {
            $errors[] = 'You are not allowed to perform this drug chart action.';
        }
        if (!$this->permissionService->canViewEncounter($visit, $user)) {
            $errors[] = 'You cannot access this encounter.';
        }
        if (!$this->permissionService->isAdministrator($user) && in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true)) {
            $errors[] = 'Completed or cancelled encounters are read-only.';
        }
        if (!$this->permissionService->isAdministrator($user) && (string)($user['role_name'] ?? '') !== 'Nurse') {
            $errors[] = 'Only nurses may record drug chart entries.';
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
        $stmt = $this->pdo->prepare('SELECT * FROM medication_administration_records WHERE id = :id FOR UPDATE');
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

    private function prescriptionById(int $prescriptionId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM prescriptions WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $prescriptionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function canViewRow(array $row, array $user): bool
    {
        return $this->permissionService->canViewNursing((int)($row['patient_id'] ?? 0), $user)
            || $this->permissionService->isAdministrator($user);
    }

    private function decorateRow(array $row): array
    {
        $row['summary'] = trim((string)($row['dose_given'] ?? '') . ' ' . (string)($row['route'] ?? ''));
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
            SELECT mar.*,
                   v.visit_number,
                   v.visit_status,
                   p.hospital_number,
                   p.first_name,
                   p.last_name,
                   pres.dosage AS prescribed_dosage,
                   pres.frequency AS prescribed_frequency,
                   pres.duration AS prescribed_duration,
                   pres.instructions AS prescribed_instructions,
                   pres.status AS prescription_status,
                   CONCAT(administered.first_name, " ", administered.last_name) AS administered_by_name
            FROM medication_administration_records mar
            INNER JOIN visits v ON v.id = mar.visit_id
            INNER JOIN patients p ON p.id = mar.patient_id
            LEFT JOIN prescriptions pres ON pres.id = mar.prescription_id
            LEFT JOIN users administered ON administered.id = mar.administered_by
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
