<?php

declare(strict_types=1);

require_once __DIR__ . '/AuditService.php';
require_once __DIR__ . '/EncounterEventService.php';
require_once __DIR__ . '/PermissionService.php';

class TheatreService
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

    public function create(array $data, array $user): array
    {
        try {
            $this->pdo->beginTransaction();

            $visit = $this->lockVisit((int)($data['visit_id'] ?? 0));
            $errors = $this->validateMutation($visit, $user, 'create_theatre');

            if (isset($data['patient_id']) && (int)$data['patient_id'] !== (int)$visit['patient_id']) {
                $errors[] = 'Patient and encounter do not match.';
            }

            if ($this->getByVisit((int)$visit['id'], $user) !== null) {
                $errors[] = 'A theatre record already exists for this encounter.';
            }

            $departmentId = $this->resolveTheatreDepartmentId();
            if ($departmentId === null) {
                $errors[] = 'Theatre department is not available.';
            }

            if ($errors !== []) {
                $this->rollback();
                return $this->failure($errors);
            }

            $payload = $this->normalizePayload($data);
            $record = $payload['data'];
            $payloadErrors = $payload['errors'];
            if ($payloadErrors !== []) {
                $errors = array_merge($errors, $payloadErrors);
            }
            $surgeonId = $this->resolveClinicalOwnerId($visit, $user);

            $stmt = $this->pdo->prepare("
                INSERT INTO theatre_records (
                    visit_id, patient_id, surgeon_id, department_id,
                    procedure_name, indication, preoperative_notes, procedure_details,
                    findings, complications, postoperative_notes, postoperative_plan,
                    anaesthesia_notes, status, created_by, created_at, updated_at
                ) VALUES (
                    :visit_id, :patient_id, :surgeon_id, :department_id,
                    :procedure_name, :indication, :preoperative_notes, :procedure_details,
                    :findings, :complications, :postoperative_notes, :postoperative_plan,
                    :anaesthesia_notes, 'Draft', :created_by, NOW(), NOW()
                )
            ");
            $stmt->execute([
                ':visit_id' => (int)$visit['id'],
                ':patient_id' => (int)$visit['patient_id'],
                ':surgeon_id' => $surgeonId,
                ':department_id' => $departmentId,
                ':procedure_name' => $record['procedure_name'],
                ':indication' => $record['indication'],
                ':preoperative_notes' => $record['preoperative_notes'],
                ':procedure_details' => $record['procedure_details'],
                ':findings' => $record['findings'],
                ':complications' => $record['complications'],
                ':postoperative_notes' => $record['postoperative_notes'],
                ':postoperative_plan' => $record['postoperative_plan'],
                ':anaesthesia_notes' => $record['anaesthesia_notes'],
                ':created_by' => (int)$user['id'],
            ]);
            $recordId = (int)$this->pdo->lastInsertId();

            if (!$this->audit('THEATRE_CREATED', $visit, $user, 'Created theatre record #' . $recordId . '.')) {
                throw new RuntimeException('Unable to audit theatre creation.');
            }
            if (!$this->event(
                (int)$visit['id'],
                'THEATRE_STARTED',
                'Theatre Started',
                'Theatre record created.',
                $visit,
                $user
            )) {
                throw new RuntimeException('Unable to record theatre start event.');
            }

            $this->pdo->commit();

            return [
                'success' => true,
                'theatre_record_id' => $recordId,
                'visit_id' => (int)$visit['id'],
                'patient_id' => (int)$visit['patient_id'],
                'errors' => [],
            ];
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure(['Unable to save theatre record.']);
        }
    }

    public function getById(int $recordId, ?array $user = null): ?array
    {
        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE tr.id = :id LIMIT 1');
        $stmt->execute([':id' => $recordId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        if ($user !== null && !$this->canViewRow($row, $user)) {
            return null;
        }

        return $this->decorate($row);
    }

    public function getByVisit(int $visitId, ?array $user = null): ?array
    {
        if ($visitId <= 0) {
            return null;
        }

        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE tr.visit_id = :visit_id ORDER BY tr.id DESC LIMIT 1');
        $stmt->execute([':visit_id' => $visitId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        if ($user !== null && !$this->canViewRow($row, $user)) {
            return null;
        }

        return $this->decorate($row);
    }

    public function listByPatient(int $patientId, ?array $user = null): array
    {
        if ($patientId <= 0) {
            return [];
        }

        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE tr.patient_id = :patient_id ORDER BY tr.created_at DESC, tr.id DESC');
        $stmt->execute([':patient_id' => $patientId]);

        return $this->filterRows($stmt->fetchAll(PDO::FETCH_ASSOC), $user);
    }

    public function listWorklist(?array $user = null): array
    {
        if ($user !== null && !$this->permissionService->hasPermission('view_theatre', $user) && !$this->permissionService->isAdministrator($user)) {
            return [];
        }

        $stmt = $this->pdo->query(
            $this->baseSelect() . " WHERE tr.status = 'Draft' ORDER BY tr.created_at DESC, tr.id DESC"
        );

        return $this->filterRows($stmt->fetchAll(PDO::FETCH_ASSOC), $user);
    }

    public function update(int $recordId, array $data, array $user): array
    {
        try {
            $this->pdo->beginTransaction();

            $record = $this->lockRecord($recordId);
            if (!$record) {
                $this->rollback();
                return $this->failure(['Theatre record not found.']);
            }

            $visit = $this->lockVisit((int)$record['visit_id']);
            $errors = $this->validateMutation($visit, $user, 'edit_theatre');
            if ((string)$record['status'] !== 'Draft') {
                $errors[] = 'Completed theatre records are view-only.';
            }

            if (isset($data['patient_id']) && (int)$data['patient_id'] !== (int)$visit['patient_id']) {
                $errors[] = 'Patient and encounter do not match.';
            }

            $recordData = $this->normalizePayload($data, $record);
            $errors = array_merge($errors, $recordData['errors']);

            if ($errors !== []) {
                $this->rollback();
                return $this->failure($errors);
            }

            $stmt = $this->pdo->prepare('
                UPDATE theatre_records
                SET procedure_name = :procedure_name,
                    indication = :indication,
                    preoperative_notes = :preoperative_notes,
                    procedure_details = :procedure_details,
                    findings = :findings,
                    complications = :complications,
                    postoperative_notes = :postoperative_notes,
                    postoperative_plan = :postoperative_plan,
                    anaesthesia_notes = :anaesthesia_notes,
                    updated_by = :updated_by,
                    updated_at = NOW()
                WHERE id = :id
            ');
            $stmt->execute([
                ':procedure_name' => $recordData['data']['procedure_name'],
                ':indication' => $recordData['data']['indication'],
                ':preoperative_notes' => $recordData['data']['preoperative_notes'],
                ':procedure_details' => $recordData['data']['procedure_details'],
                ':findings' => $recordData['data']['findings'],
                ':complications' => $recordData['data']['complications'],
                ':postoperative_notes' => $recordData['data']['postoperative_notes'],
                ':postoperative_plan' => $recordData['data']['postoperative_plan'],
                ':anaesthesia_notes' => $recordData['data']['anaesthesia_notes'],
                ':updated_by' => (int)$user['id'],
                ':id' => $recordId,
            ]);

            if (!$this->audit('THEATRE_UPDATED', $visit, $user, 'Updated theatre record #' . $recordId . '.')) {
                throw new RuntimeException('Unable to audit theatre update.');
            }

            $this->pdo->commit();

            return ['success' => true, 'theatre_record_id' => $recordId, 'errors' => []];
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure(['Unable to update theatre record.']);
        }
    }

    public function complete(int $recordId, array $user): array
    {
        try {
            $this->pdo->beginTransaction();

            $record = $this->lockRecord($recordId);
            if (!$record) {
                $this->rollback();
                return $this->failure(['Theatre record not found.']);
            }

            $visit = $this->lockVisit((int)$record['visit_id']);
            $errors = $this->validateMutation($visit, $user, 'complete_theatre');
            if ((string)$record['status'] !== 'Draft') {
                $errors[] = 'Only draft theatre records can be completed.';
            }
            if (trim((string)($record['procedure_name'] ?? '')) === '') {
                $errors[] = 'Procedure name is required.';
            }
            if (trim((string)($record['procedure_details'] ?? '')) === '') {
                $errors[] = 'Procedure details are required before completion.';
            }

            if ($errors !== []) {
                $this->rollback();
                return $this->failure($errors);
            }

            $stmt = $this->pdo->prepare('
                UPDATE theatre_records
                SET status = \'Completed\',
                    completed_by = :completed_by,
                    completed_at = NOW(),
                    updated_by = :updated_by,
                    updated_at = NOW()
                WHERE id = :id
            ');
            $stmt->execute([
                ':completed_by' => (int)$user['id'],
                ':updated_by' => (int)$user['id'],
                ':id' => $recordId,
            ]);

            if (!$this->audit('THEATRE_COMPLETED', $visit, $user, 'Completed theatre record #' . $recordId . '.')) {
                throw new RuntimeException('Unable to audit theatre completion.');
            }
            if (!$this->event(
                (int)$visit['id'],
                'THEATRE_COMPLETED',
                'Theatre Completed',
                'Theatre record completed.',
                $visit,
                $user
            )) {
                throw new RuntimeException('Unable to record theatre completion event.');
            }

            $this->pdo->commit();

            return ['success' => true, 'theatre_record_id' => $recordId, 'errors' => []];
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure(['Unable to complete theatre record.']);
        }
    }

    public function getByVisitForEdit(int $visitId, ?array $user = null): ?array
    {
        return $this->getByVisit($visitId, $user);
    }

    public function listByVisit(int $visitId, ?array $user = null): array
    {
        if ($visitId <= 0) {
            return [];
        }

        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE tr.visit_id = :visit_id ORDER BY tr.created_at DESC, tr.id DESC');
        $stmt->execute([':visit_id' => $visitId]);

        return $this->filterRows($stmt->fetchAll(PDO::FETCH_ASSOC), $user);
    }

    private function validateMutation(array $visit, array $user, string $permission): array
    {
        $errors = [];
        if (!$this->permissionService->hasPermission($permission, $user)) {
            $errors[] = 'You are not allowed to perform this theatre action.';
        }
        if (!$this->permissionService->canViewEncounter($visit, $user)) {
            $errors[] = 'You cannot access this encounter.';
        }
        if (in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true)) {
            $errors[] = 'Completed or cancelled encounters are read-only.';
        }
        return $errors;
    }

    private function normalizePayload(array $data, ?array $existing = null): array
    {
        $procedureName = $this->requiredText($data['procedure_name'] ?? ($existing['procedure_name'] ?? ''));
        $procedureDetails = $this->nullableText($data['procedure_details'] ?? ($existing['procedure_details'] ?? null));

        return [
            'errors' => $procedureName === '' ? ['Procedure name is required.'] : [],
            'data' => [
                'procedure_name' => $procedureName,
                'indication' => $this->nullableText($data['indication'] ?? ($existing['indication'] ?? null)),
                'preoperative_notes' => $this->nullableText($data['preoperative_notes'] ?? ($existing['preoperative_notes'] ?? null)),
                'procedure_details' => $procedureDetails,
                'findings' => $this->nullableText($data['findings'] ?? ($existing['findings'] ?? null)),
                'complications' => $this->nullableText($data['complications'] ?? ($existing['complications'] ?? null)),
                'postoperative_notes' => $this->nullableText($data['postoperative_notes'] ?? ($existing['postoperative_notes'] ?? null)),
                'postoperative_plan' => $this->nullableText($data['postoperative_plan'] ?? ($existing['postoperative_plan'] ?? null)),
                'anaesthesia_notes' => $this->nullableText($data['anaesthesia_notes'] ?? ($existing['anaesthesia_notes'] ?? null)),
            ],
        ];
    }

    private function canViewRow(array $row, array $user): bool
    {
        if ($this->permissionService->isAdministrator($user)) {
            return true;
        }

        return $this->permissionService->hasPermission('view_theatre', $user)
            && $this->permissionService->canViewEncounter($row, $user);
    }

    private function filterRows(array $rows, ?array $user): array
    {
        if ($user === null) {
            return array_map([$this, 'decorate'], $rows);
        }

        return array_values(array_filter(
            array_map([$this, 'decorate'], $rows),
            fn (array $row): bool => $this->canViewRow($row, $user)
        ));
    }

    private function decorate(array $row): array
    {
        $row['summary'] = trim((string)($row['procedure_name'] ?? ''));
        return $row;
    }

    private function baseSelect(): string
    {
        return '
            SELECT
                tr.*,
                CONCAT(surgeon.first_name, " ", surgeon.last_name) AS surgeon_name,
                CONCAT(created_by.first_name, " ", created_by.last_name) AS created_by_name,
                CONCAT(updated_by.first_name, " ", updated_by.last_name) AS updated_by_name,
                CONCAT(completed_by.first_name, " ", completed_by.last_name) AS completed_by_name,
                CONCAT(p.first_name, " ", p.last_name) AS patient_name,
                p.hospital_number,
                v.visit_number,
                v.visit_status,
                v.current_department_id,
                v.attending_doctor_id,
                d.department_name
            FROM theatre_records tr
            LEFT JOIN users surgeon ON surgeon.id = tr.surgeon_id
            LEFT JOIN users created_by ON created_by.id = tr.created_by
            LEFT JOIN users updated_by ON updated_by.id = tr.updated_by
            LEFT JOIN users completed_by ON completed_by.id = tr.completed_by
            LEFT JOIN patients p ON p.id = tr.patient_id
            LEFT JOIN visits v ON v.id = tr.visit_id
            LEFT JOIN departments d ON d.id = tr.department_id
        ';
    }

    private function lockVisit(int $visitId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM visits WHERE id = :id LIMIT 1 FOR UPDATE');
        $stmt->execute([':id' => $visitId]);
        $visit = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$visit) {
            throw new RuntimeException('Encounter not found.');
        }

        return $visit;
    }

    private function lockRecord(int $recordId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM theatre_records WHERE id = :id LIMIT 1 FOR UPDATE');
        $stmt->execute([':id' => $recordId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    private function resolveTheatreDepartmentId(): ?int
    {
        $stmt = $this->pdo->prepare("
            SELECT id
            FROM departments
            WHERE department_name IN ('Theatre', 'Operating Theatre', 'Surgical Theatre')
            ORDER BY CASE department_name
                WHEN 'Theatre' THEN 0
                WHEN 'Operating Theatre' THEN 1
                WHEN 'Surgical Theatre' THEN 2
                ELSE 3
            END, id ASC
            LIMIT 1
        ");
        $stmt->execute();
        $value = $stmt->fetchColumn();

        return $value === false ? null : (int)$value;
    }

    private function resolveClinicalOwnerId(array $visit, array $user): ?int
    {
        $doctorId = (int)($visit['attending_doctor_id'] ?? 0);
        if ($doctorId > 0) {
            return $doctorId;
        }

        $role = (string)($user['role_name'] ?? '');
        if (in_array($role, ['Doctor', 'Theatre Staff'], true)) {
            return (int)$user['id'];
        }

        return null;
    }

    private function requiredText(mixed $value): string
    {
        return trim((string)$value);
    }

    private function nullableText(mixed $value): ?string
    {
        $text = trim((string)($value ?? ''));
        return $text === '' ? null : $text;
    }

    private function audit(string $action, array $visit, array $user, string $description): bool
    {
        $result = $this->auditService->log(
            (int)($user['id'] ?? 0),
            (int)($visit['id'] ?? 0),
            'Theatre',
            $action,
            $description
        );

        return $result === true
            || (($result['success'] ?? false) === true);
    }

    private function event(
        int $visitId,
        string $eventType,
        string $title,
        string $description,
        array $visit,
        array $user
    ): bool {
        $result = $this->eventService->record(
            $visitId,
            $eventType,
            $title,
            $description,
            (int)($visit['current_department_id'] ?? 0) ?: null,
            (int)($user['id'] ?? 0) ?: null
        );

        return ($result['success'] ?? false) === true;
    }

    private function rollback(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    private function failure(array $errors): array
    {
        return ['success' => false, 'errors' => $errors];
    }
}
