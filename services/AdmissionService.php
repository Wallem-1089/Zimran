<?php

declare(strict_types=1);

require_once __DIR__ . '/AuditService.php';
require_once __DIR__ . '/EncounterEventService.php';
require_once __DIR__ . '/PermissionService.php';

class AdmissionService
{
    private AuditService $auditService;
    private EncounterEventService $eventService;
    private PermissionService $permissionService;

    public function __construct(
        private PDO $pdo,
        ?AuditService $auditService = null,
        ?EncounterEventService $eventService = null,
        ?PermissionService $permissionService = null
    ) {
        $this->auditService = $auditService ?? new AuditService($pdo);
        $this->eventService = $eventService ?? new EncounterEventService($pdo);
        $this->permissionService = $permissionService ?? new PermissionService($pdo);
    }

    public function createWard(array $data, array $user): array
    {
        if (!$this->permissionService->hasPermission('manage_wards_beds', $user)) {
            return $this->failure(['You are not allowed to manage wards and beds.']);
        }

        $name = $this->text($data['ward_name'] ?? '');
        $code = strtoupper($this->text($data['ward_code'] ?? ''));
        $departmentId = $this->nullableInt($data['department_id'] ?? null);
        $description = $this->nullableText($data['description'] ?? null);
        $errors = [];

        if ($name === '') {
            $errors[] = 'Ward name is required.';
        }
        if ($code === '') {
            $errors[] = 'Ward code is required.';
        }
        if ($departmentId !== null && !$this->departmentExists($departmentId)) {
            $errors[] = 'Selected department is invalid.';
        }

        if ($errors) {
            return $this->failure($errors);
        }

        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare('
                INSERT INTO wards (ward_name, ward_code, department_id, description, created_by)
                VALUES (:ward_name, :ward_code, :department_id, :description, :created_by)
            ');
            $stmt->execute([
                ':ward_name' => $name,
                ':ward_code' => $code,
                ':department_id' => $departmentId,
                ':description' => $description,
                ':created_by' => (int)$user['id'],
            ]);
            $wardId = (int)$this->pdo->lastInsertId();
            $this->audit(null, (int)$user['id'], 'WARD_CREATED', 'Created ward #' . $wardId . '.');
            $this->pdo->commit();
            return ['success' => true, 'ward_id' => $wardId, 'errors' => []];
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure(['Unable to create ward. Ward name/code may already exist.']);
        }
    }

    public function addBed(array $data, array $user): array
    {
        if (!$this->permissionService->hasPermission('manage_wards_beds', $user)) {
            return $this->failure(['You are not allowed to manage wards and beds.']);
        }

        $wardId = (int)($data['ward_id'] ?? 0);
        $label = $this->text($data['bed_label'] ?? '');

        if ($wardId <= 0 || !$this->wardExists($wardId)) {
            return $this->failure(['A valid ward is required.']);
        }
        if ($label === '') {
            return $this->failure(['Bed label is required.']);
        }

        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare('
                INSERT INTO ward_beds (ward_id, bed_label, created_by)
                VALUES (:ward_id, :bed_label, :created_by)
            ');
            $stmt->execute([
                ':ward_id' => $wardId,
                ':bed_label' => $label,
                ':created_by' => (int)$user['id'],
            ]);
            $bedId = (int)$this->pdo->lastInsertId();
            $this->audit(null, (int)$user['id'], 'WARD_BED_CREATED', 'Created bed #' . $bedId . '.');
            $this->pdo->commit();
            return ['success' => true, 'bed_id' => $bedId, 'errors' => []];
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure(['Unable to create bed. Bed label may already exist in this ward.']);
        }
    }

    public function admit(array $data, array $user): array
    {
        try {
            $this->pdo->beginTransaction();

            $visit = $this->lockVisit((int)($data['visit_id'] ?? 0));
            $errors = $this->validateEncounterMutation($visit, $user, 'create_admission');
            $wardId = (int)($data['ward_id'] ?? 0);
            $bedId = (int)($data['bed_id'] ?? 0);
            $type = $this->enum((string)($data['admission_type'] ?? 'Emergency'), ['Emergency', 'Elective', 'Transfer', 'Observation'], 'Emergency');
            $diagnosis = $this->nullableText($data['admission_diagnosis'] ?? null);
            $notes = $this->nullableText($data['admission_notes'] ?? null);
            $admittedAt = $this->dateTimeOrNow($data['admitted_at'] ?? null);

            if ((int)($data['patient_id'] ?? $visit['patient_id'] ?? 0) !== (int)($visit['patient_id'] ?? 0)) {
                $errors[] = 'Patient and encounter do not match.';
            }
            if ($this->getByVisitForLock((int)$visit['id']) !== null) {
                $errors[] = 'This encounter already has an admission record.';
            }
            $bed = $this->lockBed($bedId);
            if (!$bed || (int)$bed['ward_id'] !== $wardId || (int)$bed['is_active'] !== 1) {
                $errors[] = 'Selected bed is invalid for the selected ward.';
            } elseif ((string)$bed['bed_status'] !== 'Available') {
                $errors[] = 'Selected bed is not available.';
            }

            if ($errors) {
                $this->rollback();
                return $this->failure($errors);
            }

            $stmt = $this->pdo->prepare('
                INSERT INTO admissions (
                    visit_id, patient_id, ward_id, bed_id, admission_type,
                    admission_diagnosis, admission_notes, status, admitted_by, admitted_at
                ) VALUES (
                    :visit_id, :patient_id, :ward_id, :bed_id, :admission_type,
                    :admission_diagnosis, :admission_notes, \'Admitted\', :admitted_by, :admitted_at
                )
            ');
            $stmt->execute([
                ':visit_id' => (int)$visit['id'],
                ':patient_id' => (int)$visit['patient_id'],
                ':ward_id' => $wardId,
                ':bed_id' => $bedId,
                ':admission_type' => $type,
                ':admission_diagnosis' => $diagnosis,
                ':admission_notes' => $notes,
                ':admitted_by' => (int)$user['id'],
                ':admitted_at' => $admittedAt,
            ]);
            $admissionId = (int)$this->pdo->lastInsertId();

            $this->setBedStatus($bedId, 'Occupied', (int)$user['id']);
            $this->movement($admissionId, $visit, null, null, $wardId, $bedId, 'Admission', $notes, (int)$user['id']);
            $this->audit($visit, (int)$user['id'], 'PATIENT_ADMITTED', 'Admitted patient to ward/bed.');
            $this->event((int)$visit['id'], 'PATIENT_ADMITTED', 'Patient Admitted', 'Patient admitted to inpatient ward/bed.', $wardId, (int)$user['id']);

            $this->pdo->commit();
            return ['success' => true, 'admission_id' => $admissionId, 'errors' => []];
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure(['Unable to admit patient.']);
        }
    }

    public function transfer(int $admissionId, array $data, array $user): array
    {
        try {
            $this->pdo->beginTransaction();
            $admission = $this->lockAdmission($admissionId);
            if (!$admission) {
                $this->rollback();
                return $this->failure(['Admission not found.']);
            }
            $visit = $this->lockVisit((int)$admission['visit_id']);
            $errors = $this->validateEncounterMutation($visit, $user, 'transfer_admission');
            if (!in_array((string)$admission['status'], ['Admitted', 'Transferred'], true)) {
                $errors[] = 'Only active admissions can be transferred.';
            }

            $wardId = (int)($data['ward_id'] ?? 0);
            $bedId = (int)($data['bed_id'] ?? 0);
            $reason = $this->nullableText($data['reason'] ?? null);
            $bed = $this->lockBed($bedId);
            if (!$bed || (int)$bed['ward_id'] !== $wardId || (int)$bed['is_active'] !== 1) {
                $errors[] = 'Selected transfer bed is invalid.';
            } elseif ((string)$bed['bed_status'] !== 'Available' && $bedId !== (int)$admission['bed_id']) {
                $errors[] = 'Selected transfer bed is not available.';
            }

            if ($errors) {
                $this->rollback();
                return $this->failure($errors);
            }

            $fromWard = (int)$admission['ward_id'];
            $fromBed = (int)$admission['bed_id'];
            if ($fromBed !== $bedId) {
                $this->setBedStatus($fromBed, 'Available', (int)$user['id']);
                $this->setBedStatus($bedId, 'Occupied', (int)$user['id']);
            }

            $stmt = $this->pdo->prepare('
                UPDATE admissions
                SET ward_id = :ward_id,
                    bed_id = :bed_id,
                    status = \'Transferred\',
                    updated_at = NOW()
                WHERE id = :id
            ');
            $stmt->execute([':ward_id' => $wardId, ':bed_id' => $bedId, ':id' => $admissionId]);
            $this->movement($admissionId, $visit, $fromWard, $fromBed, $wardId, $bedId, 'Transfer', $reason, (int)$user['id']);
            $this->audit($visit, (int)$user['id'], 'ADMISSION_TRANSFERRED', 'Transferred inpatient admission #' . $admissionId . '.');
            $this->event((int)$visit['id'], 'ADMISSION_TRANSFERRED', 'Admission Transferred', 'Patient moved to another ward/bed.', $wardId, (int)$user['id']);

            $this->pdo->commit();
            return ['success' => true, 'admission_id' => $admissionId, 'errors' => []];
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure(['Unable to transfer admission.']);
        }
    }

    public function discharge(int $admissionId, array $data, array $user): array
    {
        return $this->closeAdmission($admissionId, $data, $user, 'Discharged');
    }

    public function cancel(int $admissionId, array $data, array $user): array
    {
        return $this->closeAdmission($admissionId, $data, $user, 'Cancelled');
    }

    public function getAdmissionById(int $admissionId, ?array $user = null): ?array
    {
        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE a.id = :id LIMIT 1');
        $stmt->execute([':id' => $admissionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row && $this->canViewRow($row, $user) ? $row : null;
    }

    public function getByVisit(int $visitId, ?array $user = null): ?array
    {
        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE a.visit_id = :visit_id LIMIT 1');
        $stmt->execute([':visit_id' => $visitId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row && $this->canViewRow($row, $user) ? $row : null;
    }

    public function listByPatient(int $patientId, ?array $user = null): array
    {
        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE a.patient_id = :patient_id ORDER BY a.admitted_at DESC, a.id DESC');
        $stmt->execute([':patient_id' => $patientId]);
        return array_values(array_filter($stmt->fetchAll(PDO::FETCH_ASSOC), fn($row) => $this->canViewRow($row, $user)));
    }

    public function listActive(?array $user = null): array
    {
        $stmt = $this->pdo->query($this->baseSelect() . " WHERE a.status IN ('Admitted','Transferred') ORDER BY a.admitted_at DESC, a.id DESC");
        return array_values(array_filter($stmt->fetchAll(PDO::FETCH_ASSOC), fn($row) => $this->canViewRow($row, $user)));
    }

    public function listMovements(int $admissionId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT am.*, fw.ward_name AS from_ward_name, fb.bed_label AS from_bed_label,
                   tw.ward_name AS to_ward_name, tb.bed_label AS to_bed_label,
                   CONCAT(u.first_name, \' \', u.last_name) AS performed_by_name
            FROM admission_movements am
            LEFT JOIN wards fw ON fw.id = am.from_ward_id
            LEFT JOIN ward_beds fb ON fb.id = am.from_bed_id
            LEFT JOIN wards tw ON tw.id = am.to_ward_id
            LEFT JOIN ward_beds tb ON tb.id = am.to_bed_id
            LEFT JOIN users u ON u.id = am.performed_by
            WHERE am.admission_id = :admission_id
            ORDER BY am.created_at, am.id
        ');
        $stmt->execute([':admission_id' => $admissionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listWards(bool $activeOnly = true): array
    {
        $sql = '
            SELECT w.*, d.department_name,
                   COUNT(wb.id) AS total_beds,
                   SUM(CASE WHEN wb.bed_status = \'Available\' AND wb.is_active = 1 THEN 1 ELSE 0 END) AS available_beds,
                   SUM(CASE WHEN wb.bed_status = \'Occupied\' AND wb.is_active = 1 THEN 1 ELSE 0 END) AS occupied_beds
            FROM wards w
            LEFT JOIN departments d ON d.id = w.department_id
            LEFT JOIN ward_beds wb ON wb.ward_id = w.id
        ';
        if ($activeOnly) {
            $sql .= ' WHERE w.is_active = 1';
        }
        $sql .= ' GROUP BY w.id ORDER BY w.ward_name';
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listBedsByWard(int $wardId, bool $activeOnly = true): array
    {
        $sql = 'SELECT * FROM ward_beds WHERE ward_id = :ward_id';
        if ($activeOnly) {
            $sql .= ' AND is_active = 1';
        }
        $sql .= ' ORDER BY bed_label';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':ward_id' => $wardId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listAvailableBeds(?int $wardId = null): array
    {
        $sql = '
            SELECT wb.*, w.ward_name
            FROM ward_beds wb
            INNER JOIN wards w ON w.id = wb.ward_id
            WHERE wb.is_active = 1
              AND w.is_active = 1
              AND wb.bed_status = \'Available\'
        ';
        $params = [];
        if ($wardId !== null && $wardId > 0) {
            $sql .= ' AND wb.ward_id = :ward_id';
            $params[':ward_id'] = $wardId;
        }
        $sql .= ' ORDER BY w.ward_name, wb.bed_label';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function closeAdmission(int $admissionId, array $data, array $user, string $status): array
    {
        try {
            $this->pdo->beginTransaction();
            $admission = $this->lockAdmission($admissionId);
            if (!$admission) {
                $this->rollback();
                return $this->failure(['Admission not found.']);
            }
            $visit = $this->lockVisit((int)$admission['visit_id']);
            $errors = $this->validateEncounterMutation($visit, $user, 'discharge_admission');
            if (!in_array((string)$admission['status'], ['Admitted', 'Transferred'], true)) {
                $errors[] = 'Admission is already closed.';
            }
            $notes = $this->nullableText($data['discharge_notes'] ?? $data['reason'] ?? null);
            $destination = $status === 'Discharged' ? $this->nullableText($data['discharge_destination'] ?? null, 120) : null;
            if ($status === 'Discharged' && $notes === null) {
                $errors[] = 'Discharge notes are required.';
            }
            if ($status === 'Cancelled' && $notes === null) {
                $errors[] = 'Cancellation reason is required.';
            }

            if ($errors) {
                $this->rollback();
                return $this->failure($errors);
            }

            $stmt = $this->pdo->prepare('
                UPDATE admissions
                SET status = :status,
                    discharged_by = :discharged_by,
                    discharged_at = NOW(),
                    discharge_destination = :discharge_destination,
                    discharge_notes = :discharge_notes,
                    updated_at = NOW()
                WHERE id = :id
            ');
            $stmt->execute([
                ':status' => $status,
                ':discharged_by' => (int)$user['id'],
                ':discharge_destination' => $destination,
                ':discharge_notes' => $notes,
                ':id' => $admissionId,
            ]);
            $this->setBedStatus((int)$admission['bed_id'], 'Available', (int)$user['id']);
            $movementType = $status === 'Discharged' ? 'Discharge' : 'Cancel';
            $this->movement($admissionId, $visit, (int)$admission['ward_id'], (int)$admission['bed_id'], null, null, $movementType, $notes, (int)$user['id']);
            $auditAction = $status === 'Discharged' ? 'PATIENT_DISCHARGED_FROM_WARD' : 'ADMISSION_CANCELLED';
            $eventType = $status === 'Discharged' ? 'PATIENT_DISCHARGED_FROM_WARD' : 'ADMISSION_CANCELLED';
            $eventTitle = $status === 'Discharged' ? 'Patient Discharged From Ward' : 'Admission Cancelled';
            $this->audit($visit, (int)$user['id'], $auditAction, $eventTitle . '.');
            $this->event((int)$visit['id'], $eventType, $eventTitle, $eventTitle . '.', (int)$admission['ward_id'], (int)$user['id']);
            $this->pdo->commit();
            return ['success' => true, 'admission_id' => $admissionId, 'errors' => []];
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure(['Unable to close admission.']);
        }
    }

    private function validateEncounterMutation(array $visit, array $user, string $permission): array
    {
        $errors = [];
        if (!$visit) {
            return ['Encounter not found.'];
        }
        if (!$this->permissionService->hasPermission($permission, $user)) {
            $errors[] = 'You are not allowed to perform this admission action.';
        }
        if (!$this->permissionService->canViewEncounter($visit, $user)) {
            $errors[] = 'You cannot access this encounter.';
        }
        if (in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true)) {
            $errors[] = 'Completed or cancelled encounters cannot be admitted or transferred.';
        }
        return $errors;
    }

    private function baseSelect(): string
    {
        return '
            SELECT a.*, v.visit_number, v.visit_status, v.visit_type,
                   p.hospital_number, CONCAT(p.first_name, \' \', p.last_name) AS patient_name,
                   w.ward_name, w.ward_code, wb.bed_label, wb.bed_status,
                   CONCAT(adm.first_name, \' \', adm.last_name) AS admitted_by_name,
                   CONCAT(dis.first_name, \' \', dis.last_name) AS discharged_by_name
            FROM admissions a
            INNER JOIN visits v ON v.id = a.visit_id
            INNER JOIN patients p ON p.id = a.patient_id
            INNER JOIN wards w ON w.id = a.ward_id
            INNER JOIN ward_beds wb ON wb.id = a.bed_id
            LEFT JOIN users adm ON adm.id = a.admitted_by
            LEFT JOIN users dis ON dis.id = a.discharged_by
        ';
    }

    private function canViewRow(array $row, ?array $user): bool
    {
        return $user === null
            || $this->permissionService->isAdministrator($user)
            || $this->permissionService->hasPermission('view_admissions', $user);
    }

    private function getByVisitForLock(int $visitId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM admissions WHERE visit_id = :visit_id LIMIT 1 FOR UPDATE');
        $stmt->execute([':visit_id' => $visitId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function lockAdmission(int $admissionId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM admissions WHERE id = :id LIMIT 1 FOR UPDATE');
        $stmt->execute([':id' => $admissionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function lockVisit(int $visitId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT v.*, d.department_name
            FROM visits v
            LEFT JOIN departments d ON d.id = v.current_department_id
            WHERE v.id = :id
            LIMIT 1
            FOR UPDATE
        ');
        $stmt->execute([':id' => $visitId]);
        $visit = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$visit) {
            throw new RuntimeException('Encounter not found.');
        }
        return $visit;
    }

    private function lockBed(int $bedId): ?array
    {
        if ($bedId <= 0) {
            return null;
        }
        $stmt = $this->pdo->prepare('SELECT * FROM ward_beds WHERE id = :id LIMIT 1 FOR UPDATE');
        $stmt->execute([':id' => $bedId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function setBedStatus(int $bedId, string $status, int $userId): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE ward_beds
            SET bed_status = :status, updated_by = :updated_by, updated_at = NOW()
            WHERE id = :id
        ');
        $stmt->execute([':status' => $status, ':updated_by' => $userId, ':id' => $bedId]);
    }

    private function movement(int $admissionId, array $visit, ?int $fromWard, ?int $fromBed, ?int $toWard, ?int $toBed, string $type, ?string $reason, int $userId): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO admission_movements (
                admission_id, visit_id, patient_id, from_ward_id, from_bed_id,
                to_ward_id, to_bed_id, movement_type, reason, performed_by
            ) VALUES (
                :admission_id, :visit_id, :patient_id, :from_ward_id, :from_bed_id,
                :to_ward_id, :to_bed_id, :movement_type, :reason, :performed_by
            )
        ');
        $stmt->execute([
            ':admission_id' => $admissionId,
            ':visit_id' => (int)$visit['id'],
            ':patient_id' => (int)$visit['patient_id'],
            ':from_ward_id' => $fromWard,
            ':from_bed_id' => $fromBed,
            ':to_ward_id' => $toWard,
            ':to_bed_id' => $toBed,
            ':movement_type' => $type,
            ':reason' => $reason,
            ':performed_by' => $userId,
        ]);
    }

    private function audit(?array $visit, int $userId, string $action, string $description): void
    {
        if ($visit) {
            $ok = $this->auditService->logPatient($userId, (int)$visit['patient_id'], (int)$visit['id'], 'Admissions', $action, $description, null, 'INFO', $action);
        } else {
            $ok = $this->auditService->log($userId, null, 'Admissions', $action, $description, null, 'INFO', $action);
        }
        if (!$ok) {
            throw new RuntimeException('Unable to record audit.');
        }
    }

    private function event(int $visitId, string $type, string $title, string $description, ?int $departmentId, int $userId): void
    {
        $result = $this->eventService->record($visitId, $type, $title, $description, $departmentId, $userId);
        if (!($result['success'] ?? false)) {
            throw new RuntimeException('Unable to record encounter event.');
        }
    }

    private function wardExists(int $wardId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM wards WHERE id = :id AND is_active = 1 LIMIT 1');
        $stmt->execute([':id' => $wardId]);
        return (bool)$stmt->fetchColumn();
    }

    private function departmentExists(int $departmentId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM departments WHERE id = :id AND is_active = 1 LIMIT 1');
        $stmt->execute([':id' => $departmentId]);
        return (bool)$stmt->fetchColumn();
    }

    private function text(mixed $value, int $max = 255): string
    {
        return mb_substr(trim((string)$value), 0, $max);
    }

    private function nullableText(mixed $value, int $max = 5000): ?string
    {
        $text = $this->text($value, $max);
        return $text === '' ? null : $text;
    }

    private function nullableInt(mixed $value): ?int
    {
        $int = filter_var($value, FILTER_VALIDATE_INT);
        return $int === false || $int <= 0 ? null : $int;
    }

    private function enum(string $value, array $allowed, string $default): string
    {
        return in_array($value, $allowed, true) ? $value : $default;
    }

    private function dateTimeOrNow(mixed $value): string
    {
        $raw = trim((string)$value);
        if ($raw === '') {
            return date('Y-m-d H:i:s');
        }
        $timestamp = strtotime($raw);
        return $timestamp ? date('Y-m-d H:i:s', $timestamp) : date('Y-m-d H:i:s');
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
