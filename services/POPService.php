<?php

declare(strict_types=1);

require_once __DIR__ . '/AuditService.php';
require_once __DIR__ . '/ClinicalBillingGateService.php';
require_once __DIR__ . '/EncounterEventService.php';
require_once __DIR__ . '/PermissionService.php';

class POPService
{
    private AuditService $auditService;
    private ClinicalBillingGateService $billingGateService;
    private EncounterEventService $eventService;
    private PermissionService $permissionService;

    public function __construct(
        private PDO $pdo,
        ?AuditService $auditService = null,
        ?EncounterEventService $eventService = null,
        ?PermissionService $permissionService = null,
        ?ClinicalBillingGateService $billingGateService = null
    ) {
        $this->auditService = $auditService ?? new AuditService($pdo);
        $this->billingGateService = $billingGateService ?? new ClinicalBillingGateService($pdo);
        $this->eventService = $eventService ?? new EncounterEventService($pdo);
        $this->permissionService = $permissionService ?? new PermissionService($pdo);
    }

    public function createRequest(array $data, array $user): array
    {
        try {
            $this->pdo->beginTransaction();
            $visit = $this->lockVisit((int)($data['visit_id'] ?? 0));
            $source = $this->normalizeSource((string)($data['request_source'] ?? 'Clinical'));
            $priority = $this->normalizePriority((string)($data['priority'] ?? 'Routine'));
            $procedure = trim((string)($data['procedure_requested'] ?? 'Plaster'));
            $indication = $this->nullableText($data['clinical_indication'] ?? null);
            $suggestedBillableItemIds = ClinicalBillingGateService::normalizeBillableItemIds($data);
            $errors = $this->validateRequest($visit, $user, $source, $priority, $procedure, $indication);

            if (isset($data['patient_id']) && (int)$data['patient_id'] !== (int)$visit['patient_id']) {
                $errors[] = 'Patient and encounter do not match.';
            }

            $departmentId = $this->resolvePopDepartmentId();
            if ($departmentId === null) {
                $errors[] = 'Plaster department is not available.';
            }
            if ($source === 'Clinical' && $suggestedBillableItemIds === []) {
                $errors[] = 'At least one billable item is required before sending this request to Accounts.';
            }

            if ($errors !== []) {
                $this->rollback();
                return $this->failure($errors);
            }

            $stmt = $this->pdo->prepare('
                INSERT INTO pop_requests (
                    visit_id, patient_id, requested_by, department_id,
                    request_source, procedure_requested, clinical_indication,
                    priority, status, created_at, updated_at
                ) VALUES (
                    :visit_id, :patient_id, :requested_by, :department_id,
                    :request_source, :procedure_requested, :clinical_indication,
                    :priority, "Requested", NOW(), NOW()
                )
            ');
            $stmt->execute([
                ':visit_id' => (int)$visit['id'],
                ':patient_id' => (int)$visit['patient_id'],
                ':requested_by' => (int)$user['id'],
                ':department_id' => $departmentId,
                ':request_source' => $source,
                ':procedure_requested' => $procedure,
                ':clinical_indication' => $indication,
                ':priority' => $priority,
            ]);
            $requestId = (int)$this->pdo->lastInsertId();

            $billingTask = $this->billingGateService->ensureBillingRequest(
                'POP',
                $requestId,
                (int)$visit['id'],
                (int)$visit['patient_id'],
                (int)$user['id'],
                'Plaster: ' . $procedure,
                $departmentId,
                $suggestedBillableItemIds
            );
            if (($billingTask['success'] ?? false) !== true) {
                $this->rollback();
                return $this->failure($billingTask['errors'] ?? ['Unable to create Accounts billing task.']);
            }

            $this->audit('POP_REQUEST_CREATED', $visit, $user, 'Created Plaster request #' . $requestId . '.');
            $this->event((int)$visit['id'], 'POP_REQUESTED', 'Plaster Requested', 'Plaster request created.', $visit, $user);
            $this->pdo->commit();

            return ['success' => true, 'pop_request_id' => $requestId, 'visit_id' => (int)$visit['id'], 'patient_id' => (int)$visit['patient_id'], 'errors' => []];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to save Plaster request.']);
        }
    }

    public function getRequestById(int $requestId, ?array $user = null): ?array
    {
        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE pr.id = :id LIMIT 1');
        $stmt->execute([':id' => $requestId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || ($user !== null && !$this->canViewRow($row, $user))) {
            return null;
        }
        return $this->decorateRow($row);
    }

    public function listByVisit(int $visitId, ?array $user = null): array
    {
        if ($visitId <= 0) {
            return [];
        }
        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE pr.visit_id = :visit_id ORDER BY pr.created_at DESC, pr.id DESC');
        $stmt->execute([':visit_id' => $visitId]);
        return $this->filterRows($stmt->fetchAll(PDO::FETCH_ASSOC), $user);
    }

    public function listByPatient(int $patientId, ?array $user = null): array
    {
        if ($patientId <= 0) {
            return [];
        }
        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE pr.patient_id = :patient_id ORDER BY pr.created_at DESC, pr.id DESC');
        $stmt->execute([':patient_id' => $patientId]);
        return $this->filterRows($stmt->fetchAll(PDO::FETCH_ASSOC), $user);
    }

    public function listWorklist(?array $user = null, array $filters = []): array
    {
        if ($user !== null && !$this->permissionService->canViewPopWorklist($user)) {
            return [];
        }
        $status = $this->normalizeWorklistStatus((string)($filters['status'] ?? ''));
        $params = [];
        $where = " WHERE pr.request_source = 'Clinical'";
        if ($status === '') {
            $where .= " AND pr.status IN ('Requested','In Progress')";
        } elseif ($status !== 'All') {
            $where .= ' AND pr.status = :status';
            $params[':status'] = $status;
        }
        $stmt = $this->pdo->prepare($this->baseSelect() . $where . " ORDER BY CASE WHEN pr.priority = 'Urgent' THEN 0 ELSE 1 END, pr.created_at DESC, pr.id DESC");
        $stmt->execute($params);
        return array_map([$this, 'decorateRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function startRequest(int $requestId, array $user): array
    {
        return $this->transitionRequest($requestId, $user, 'In Progress', 'POP_REQUEST_STARTED', 'Started Plaster request #' . $requestId . '.');
    }

    public function saveRecord(array $data, array $user): array
    {
        return $this->saveOrUpdateRecord($data, $user, false);
    }

    public function updateRecord(array $data, array $user): array
    {
        return $this->saveOrUpdateRecord($data, $user, true);
    }

    public function getRecord(int $requestId, ?array $user = null): ?array
    {
        $row = $this->getRequestById($requestId, $user);
        if (!$row || empty($row['record_id'])) {
            return null;
        }

        return $row;
    }

    public function completeRequest(int $requestId, array $user): array
    {
        try {
            $this->pdo->beginTransaction();
            $request = $this->lockRequest($requestId);
            if (!$request) {
                $this->rollback();
                return $this->failure(['Plaster request not found.']);
            }
            $visit = $this->lockVisit((int)$request['visit_id']);
            $errors = $this->validateProcessing($request, $visit, $user, 'complete_pop_request');
            $record = $this->lockRecordByRequest($requestId);
            if (!$record || trim((string)($record['procedure_notes'] ?? '')) === '') {
                $errors[] = 'Record Plaster procedure notes before completion.';
            }
            if ($errors !== []) {
                $this->rollback();
                return $this->failure($errors);
            }
            $this->transitionRequestRecord($requestId, 'Completed');
            $stmt = $this->pdo->prepare('UPDATE pop_records SET completed_by = COALESCE(completed_by, :user_id), completed_at = COALESCE(completed_at, NOW()), updated_at = NOW() WHERE pop_request_id = :id');
            $stmt->execute([':user_id' => (int)$user['id'], ':id' => $requestId]);
            $this->audit('POP_REQUEST_COMPLETED', $visit, $user, 'Completed Plaster request #' . $requestId . '.');
            $this->event((int)$visit['id'], 'POP_COMPLETED', 'Plaster Completed', 'Plaster request completed.', $visit, $user);
            $this->pdo->commit();
            return ['success' => true, 'pop_request_id' => $requestId, 'errors' => []];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to complete Plaster request.']);
        }
    }

    public function cancelRequest(int $requestId, array $user): array
    {
        return $this->transitionRequest($requestId, $user, 'Cancelled', 'POP_REQUEST_CANCELLED', 'Cancelled Plaster request #' . $requestId . '.');
    }

    private function saveOrUpdateRecord(array $data, array $user, bool $mustExist): array
    {
        try {
            $this->pdo->beginTransaction();
            $requestId = (int)($data['pop_request_id'] ?? $data['request_id'] ?? 0);
            $request = $this->lockRequest($requestId);
            if (!$request) {
                $this->rollback();
                return $this->failure(['Plaster request not found.']);
            }
            $visit = $this->lockVisit((int)$request['visit_id']);
            $permission = $mustExist ? 'edit_pop_record' : 'record_pop_procedure';
            $errors = $this->validateProcessing($request, $visit, $user, $permission);
            $existing = $this->lockRecordByRequest($requestId);
            if ($mustExist && !$existing) {
                $errors[] = 'Plaster record not found.';
            }
            if (!$mustExist && $existing) {
                $errors[] = 'Plaster record already exists. Use edit instead.';
            }

            $castType = $this->nullableText($data['cast_type'] ?? null);
            $bodyPart = $this->nullableText($data['body_part'] ?? null);
            $procedureNotes = $this->nullableText($data['procedure_notes'] ?? null);
            $materials = $this->nullableText($data['materials_used'] ?? null);
            $aftercare = $this->nullableText($data['aftercare_instructions'] ?? null);
            $remarks = $this->nullableText($data['remarks'] ?? null);

            if ($procedureNotes === null) {
                $errors[] = 'Procedure notes are required.';
            }
            if (($castType !== null && $this->textLength($castType) > 255) || ($bodyPart !== null && $this->textLength($bodyPart) > 255)) {
                $errors[] = 'Cast type or body part is too long.';
            }
            foreach ([$procedureNotes, $materials, $aftercare, $remarks] as $value) {
                if ($value !== null && $this->textLength($value) > 5000) {
                    $errors[] = 'Plaster record text is too long.';
                    break;
                }
            }
            if ($errors !== []) {
                $this->rollback();
                return $this->failure($errors);
            }

            if ($existing) {
                $stmt = $this->pdo->prepare('
                    UPDATE pop_records
                    SET cast_type = :cast_type,
                        body_part = :body_part,
                        procedure_notes = :procedure_notes,
                        materials_used = :materials_used,
                        aftercare_instructions = :aftercare_instructions,
                        remarks = :remarks,
                        updated_by = :updated_by,
                        updated_at = NOW()
                    WHERE pop_request_id = :request_id
                ');
                $stmt->execute([
                    ':cast_type' => $castType,
                    ':body_part' => $bodyPart,
                    ':procedure_notes' => $procedureNotes,
                    ':materials_used' => $materials,
                    ':aftercare_instructions' => $aftercare,
                    ':remarks' => $remarks,
                    ':updated_by' => (int)$user['id'],
                    ':request_id' => $requestId,
                ]);
                $this->audit('POP_RECORD_UPDATED', $visit, $user, 'Updated Plaster record for request #' . $requestId . '.');
            } else {
                $stmt = $this->pdo->prepare('
                    INSERT INTO pop_records (
                        pop_request_id, visit_id, patient_id, cast_type, body_part,
                        procedure_notes, materials_used, aftercare_instructions,
                        remarks, performed_by, created_at, updated_at
                    ) VALUES (
                        :request_id, :visit_id, :patient_id, :cast_type, :body_part,
                        :procedure_notes, :materials_used, :aftercare_instructions,
                        :remarks, :performed_by, NOW(), NOW()
                    )
                ');
                $stmt->execute([
                    ':request_id' => $requestId,
                    ':visit_id' => (int)$visit['id'],
                    ':patient_id' => (int)$visit['patient_id'],
                    ':cast_type' => $castType,
                    ':body_part' => $bodyPart,
                    ':procedure_notes' => $procedureNotes,
                    ':materials_used' => $materials,
                    ':aftercare_instructions' => $aftercare,
                    ':remarks' => $remarks,
                    ':performed_by' => (int)$user['id'],
                ]);
                if ((string)$request['status'] === 'Requested') {
                    $this->transitionRequestRecord($requestId, 'In Progress');
                }
                $this->audit('POP_RECORD_CREATED', $visit, $user, 'Created Plaster record for request #' . $requestId . '.');
            }
            $this->pdo->commit();
            return ['success' => true, 'pop_request_id' => $requestId, 'errors' => []];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to save Plaster record.']);
        }
    }

    private function validateRequest(array $visit, array $user, string $source, string $priority, string $procedure, ?string $indication): array
    {
        $errors = [];
        if (!$this->permissionService->canViewEncounter($visit, $user)) {
            $errors[] = 'You cannot access this encounter.';
        }
        if (in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true) && !$this->permissionService->isAdministrator($user)) {
            $errors[] = 'Completed or cancelled encounters cannot receive Plaster mutations.';
        }
        if ($source === 'Clinical' && !$this->permissionService->canCreatePopRequest($visit, $user, 'Clinical')) {
            $errors[] = 'You cannot create a clinical Plaster request.';
        }
        if ($source === 'Direct') {
            if (!$this->permissionService->canCreatePopRequest($visit, $user, 'Direct')) {
                $errors[] = 'You cannot create a direct Plaster request.';
            }
            if (!$this->isPopEncounter($visit)) {
                $errors[] = 'Direct Plaster requests require an active Plaster encounter.';
            }
        }
        if ($procedure === '') {
            $errors[] = 'Procedure requested is required.';
        }
        if (!in_array($priority, ['Routine', 'Urgent'], true)) {
            $errors[] = 'Priority is invalid.';
        }
        if ($this->textLength($procedure) > 255 || ($indication !== null && $this->textLength($indication) > 5000)) {
            $errors[] = 'Plaster request text is too long.';
        }
        return $errors;
    }

    private function validateProcessing(array $request, array $visit, array $user, string $permissionKey, bool $requireBillingClearance = true): array
    {
        $errors = [];
        if ((string)($request['request_source'] ?? 'Clinical') !== 'Clinical') {
            $errors[] = 'Only clinical Plaster requests can be processed.';
        }

        $allowed = match ($permissionKey) {
            'process_pop_request' => $this->permissionService->canProcessPopRequest($visit, $user),
            'record_pop_procedure' => $this->permissionService->canRecordPopProcedure($visit, $user),
            'edit_pop_record' => $this->permissionService->canEditPopRecord($visit, $user),
            'complete_pop_request' => $this->permissionService->canCompletePopRequest($visit, $user),
            default => false,
        };
        if (!$allowed) {
            $errors[] = 'You cannot perform this Plaster action.';
        }
        if ((string)$request['status'] === 'Cancelled') {
            $errors[] = 'Cancelled Plaster requests cannot be modified.';
        }
        if ((string)$request['status'] === 'Completed') {
            $errors[] = 'Completed Plaster requests are view-only.';
        }
        if (in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true) && !$this->permissionService->isAdministrator($user)) {
            $errors[] = 'Completed or cancelled encounters cannot receive Plaster mutations.';
        }
        if ($requireBillingClearance) {
            $errors = array_merge(
                $errors,
                $this->billingGateService->processingErrors(
                    'POP',
                    (int)($request['id'] ?? 0),
                    (int)($visit['id'] ?? $request['visit_id'] ?? 0),
                    $user
                )
            );
        }
        return $errors;
    }

    private function transitionRequest(int $requestId, array $user, string $status, string $action, string $description): array
    {
        try {
            $this->pdo->beginTransaction();
            $request = $this->lockRequest($requestId);
            if (!$request) {
                $this->rollback();
                return $this->failure(['Plaster request not found.']);
            }
            $visit = $this->lockVisit((int)$request['visit_id']);
            $errors = $this->validateProcessing($request, $visit, $user, 'process_pop_request', $status !== 'Cancelled');
            if ($status === 'In Progress' && (string)$request['status'] !== 'Requested') {
                $errors[] = 'Only requested Plaster requests can be started.';
            }
            if ($status === 'Cancelled' && !in_array((string)$request['status'], ['Requested', 'In Progress'], true)) {
                $errors[] = 'Only active Plaster requests can be cancelled.';
            }
            if ($status === 'Cancelled') {
                $errors = array_merge(
                    $errors,
                    $this->billingGateService->cancellationErrors('POP', $requestId, (int)$request['visit_id'], $user)
                );
            }
            if ($errors !== []) {
                $this->rollback();
                return $this->failure($errors);
            }
            $this->transitionRequestRecord($requestId, $status);
            $this->audit($action, $visit, $user, $description);
            $this->pdo->commit();
            return ['success' => true, 'pop_request_id' => $requestId, 'errors' => []];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to update Plaster request.']);
        }
    }

    private function transitionRequestRecord(int $requestId, string $status): void
    {
        $stmt = $this->pdo->prepare('UPDATE pop_requests SET status = :status, updated_at = NOW(), completed_at = CASE WHEN :status_completed = "Completed" THEN NOW() ELSE completed_at END WHERE id = :id');
        $stmt->execute([':status' => $status, ':status_completed' => $status, ':id' => $requestId]);
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

    private function lockRequest(int $requestId): ?array
    {
        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE pr.id = :id LIMIT 1 FOR UPDATE');
        $stmt->execute([':id' => $requestId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function lockRecordByRequest(int $requestId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM pop_records WHERE pop_request_id = :id FOR UPDATE');
        $stmt->execute([':id' => $requestId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function canViewRow(array $row, array $user): bool
    {
        return $this->permissionService->isAdministrator($user)
            || $this->permissionService->canViewPop((int)($row['patient_id'] ?? 0), $user);
    }

    private function filterRows(array $rows, ?array $user): array
    {
        $rows = $user === null ? $rows : array_filter($rows, fn (array $row): bool => $this->canViewRow($row, $user));
        return array_map([$this, 'decorateRow'], array_values($rows));
    }

    private function decorateRow(array $row): array
    {
        $row['record_status'] = !empty($row['record_id'])
            ? ((string)($row['record_completed_at'] ?? '') !== '' ? 'Completed' : 'Recorded')
            : 'Pending';
        return $row;
    }

    private function baseSelect(): string
    {
        return '
            SELECT pr.*,
                   p.hospital_number,
                   CONCAT(p.first_name, " ", p.last_name) AS patient_name,
                   v.visit_number,
                   v.visit_status,
                   d.department_name,
                   CONCAT(req.first_name, " ", req.last_name) AS requested_by_name,
                   rec.id AS record_id,
                   rec.cast_type,
                   rec.body_part,
                   rec.procedure_notes,
                   rec.materials_used,
                   rec.aftercare_instructions,
                   rec.remarks,
                   rec.performed_by AS record_performed_by,
                   rec.updated_by AS record_updated_by,
                   rec.completed_by AS record_completed_by,
                   rec.created_at AS record_created_at,
                   rec.updated_at AS record_updated_at,
                   rec.completed_at AS record_completed_at,
                   CONCAT(performed.first_name, " ", performed.last_name) AS performed_by_name,
                   CONCAT(completed.first_name, " ", completed.last_name) AS completed_by_name
            FROM pop_requests pr
            INNER JOIN visits v ON v.id = pr.visit_id
            INNER JOIN patients p ON p.id = pr.patient_id
            LEFT JOIN departments d ON d.id = pr.department_id
            LEFT JOIN users req ON req.id = pr.requested_by
            LEFT JOIN pop_records rec ON rec.pop_request_id = pr.id
            LEFT JOIN users performed ON performed.id = rec.performed_by
            LEFT JOIN users completed ON completed.id = rec.completed_by
        ';
    }

    private function resolvePopDepartmentId(): ?int
    {
        $id = $this->pdo->query("
            SELECT id
            FROM departments
            WHERE department_name IN ('Plaster', 'POP')
            ORDER BY CASE WHEN department_name = 'Plaster' THEN 0 ELSE 1 END
            LIMIT 1
        ")->fetchColumn();
        return $id !== false ? (int)$id : null;
    }

    private function isPopEncounter(array $visit): bool
    {
        return in_array((string)($visit['visit_status'] ?? ''), ['Plaster', 'POP'], true)
            || in_array((string)($visit['department_name'] ?? ''), ['Plaster', 'POP'], true)
            || (int)($visit['current_department_id'] ?? 0) === (int)($this->resolvePopDepartmentId() ?? 0);
    }

    private function normalizeSource(string $source): string
    {
        return in_array($source, ['Direct', 'Clinical'], true) ? $source : 'Clinical';
    }

    private function normalizePriority(string $priority): string
    {
        return in_array($priority, ['Routine', 'Urgent'], true) ? $priority : 'Routine';
    }

    private function normalizeWorklistStatus(string $status): string
    {
        return in_array($status, ['Requested', 'In Progress', 'Completed', 'Cancelled', 'All'], true) ? $status : '';
    }

    private function nullableText(mixed $value): ?string
    {
        $text = trim((string)$value);
        return $text === '' ? null : $text;
    }

    private function textLength(string $value): int
    {
        return function_exists('mb_strlen') ? (int)mb_strlen($value) : strlen($value);
    }

    private function audit(string $action, array $visit, array $user, string $description): bool
    {
        return $this->auditService->logPatient(
            (int)($user['id'] ?? 0),
            (int)($visit['patient_id'] ?? 0),
            (int)($visit['id'] ?? 0),
            'Plaster',
            $action,
            $description,
            (int)($visit['current_department_id'] ?? 0) ?: null,
            'INFO',
            $action
        );
    }

    private function event(int $visitId, string $type, string $title, string $description, array $visit, array $user): bool
    {
        $event = $this->eventService->record($visitId, $type, $title, $description, (int)($visit['current_department_id'] ?? 0) ?: null, (int)($user['id'] ?? 0) ?: null);
        return (bool)($event['success'] ?? false);
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
