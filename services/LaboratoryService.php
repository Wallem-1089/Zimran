<?php

declare(strict_types=1);

require_once __DIR__ . '/AuditService.php';
require_once __DIR__ . '/EncounterEventService.php';
require_once __DIR__ . '/PermissionService.php';

class LaboratoryService
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

    public function createRequest(array $data, array $user): array
    {
        try {
            $this->pdo->beginTransaction();

            $visit = $this->lockVisit((int)($data['visit_id'] ?? 0));
            $source = $this->normalizeSource((string)($data['request_source'] ?? 'Clinical'));
            $priority = $this->normalizePriority((string)($data['priority'] ?? 'Routine'));
            $testsRequested = trim((string)($data['tests_requested'] ?? ''));
            $clinicalInformation = $this->nullableText($data['clinical_information'] ?? null);
            $errors = $this->validateRequest($visit, $user, $source, $priority, $testsRequested, $clinicalInformation);

            if (isset($data['patient_id']) && (int)$data['patient_id'] !== (int)$visit['patient_id']) {
                $errors[] = 'Patient and encounter do not match.';
            }

            $departmentId = $this->resolveLaboratoryDepartmentId();
            if ($departmentId === null) {
                $errors[] = 'Laboratory department is not available.';
            }

            if ($errors !== []) {
                $this->rollback();
                return $this->failure($errors);
            }

            $stmt = $this->pdo->prepare('
                INSERT INTO laboratory_requests (
                    visit_id, patient_id, requested_by, department_id,
                    request_source, tests_requested, clinical_information,
                    priority, status, created_at, updated_at
                ) VALUES (
                    :visit_id, :patient_id, :requested_by, :department_id,
                    :request_source, :tests_requested, :clinical_information,
                    :priority, \'Requested\', NOW(), NOW()
                )
            ');
            $stmt->execute([
                ':visit_id' => (int)$visit['id'],
                ':patient_id' => (int)$visit['patient_id'],
                ':requested_by' => (int)$user['id'],
                ':department_id' => $departmentId,
                ':request_source' => $source,
                ':tests_requested' => $testsRequested,
                ':clinical_information' => $clinicalInformation,
                ':priority' => $priority,
            ]);
            $requestId = (int)$this->pdo->lastInsertId();

            if (!$this->audit(
                'LABORATORY_REQUEST_CREATED',
                $visit,
                $user,
                'Created laboratory request #' . $requestId . '.'
            )) {
                throw new RuntimeException('Unable to audit laboratory request creation.');
            }

            if (!$this->event(
                (int)$visit['id'],
                'LABORATORY_REQUESTED',
                'Laboratory Request Created',
                'Laboratory request created.',
                $visit,
                $user
            )) {
                throw new RuntimeException('Unable to record laboratory request event.');
            }

            $this->pdo->commit();

            return [
                'success' => true,
                'laboratory_request_id' => $requestId,
                'visit_id' => (int)$visit['id'],
                'patient_id' => (int)$visit['patient_id'],
                'errors' => [],
            ];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to save laboratory request.']);
        }
    }

    public function getRequestById(int $requestId, ?array $user = null): ?array
    {
        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE lr.id = :id LIMIT 1');
        $stmt->execute([':id' => $requestId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        if ($user !== null && !$this->canViewRow($row, $user)) {
            return null;
        }

        return $this->decorateRow($row);
    }

    public function listByVisit(int $visitId, ?array $user = null): array
    {
        if ($visitId <= 0) {
            return [];
        }

        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE lr.visit_id = :visit_id ORDER BY lr.created_at DESC, lr.id DESC');
        $stmt->execute([':visit_id' => $visitId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->filterRows($rows, $user);
    }

    public function listByPatient(int $patientId, ?array $user = null): array
    {
        if ($patientId <= 0) {
            return [];
        }

        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE lr.patient_id = :patient_id ORDER BY lr.created_at DESC, lr.id DESC');
        $stmt->execute([':patient_id' => $patientId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->filterRows($rows, $user);
    }

    public function listWorklist(?array $user = null, array $filters = []): array
    {
        if ($user !== null && !$this->permissionService->canViewLaboratory(1, $user) && !$this->permissionService->isAdministrator($user)) {
            return [];
        }

        $status = $this->normalizeWorklistStatus((string)($filters['status'] ?? ''));
        $params = [];
        $where = '';

        if ($status === '') {
            $where = " WHERE lr.status IN ('Requested', 'In Progress')";
        } elseif ($status !== 'All') {
            $where = ' WHERE lr.status = :status';
            $params[':status'] = $status;
        }

        $stmt = $this->pdo->prepare($this->baseSelect() . $where . ' ORDER BY lr.created_at DESC, lr.id DESC');
        $stmt->execute($params);
        return $this->filterRows($stmt->fetchAll(PDO::FETCH_ASSOC), $user);
    }

    public function startRequest(int $requestId, array $user): array
    {
        return $this->transitionRequest($requestId, $user, 'In Progress', 'LABORATORY_REQUEST_STARTED', 'Started laboratory request #' . $requestId . '.');
    }

    public function saveResult(array $data, array $user): array
    {
        return $this->saveOrUpdateResult($data, $user, false);
    }

    public function updateResult(array $data, array $user): array
    {
        return $this->saveOrUpdateResult($data, $user, true);
    }

    public function getResult(int $requestId, ?array $user = null): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT lr.*, lres.id AS result_id, lres.result, lres.interpretation,
                   lres.performed_by, lres.completed_by, lres.created_at AS result_created_at,
                   lres.updated_at AS result_updated_at, lres.completed_at AS result_completed_at,
                   CONCAT(performed.first_name, " ", performed.last_name) AS performed_by_name,
                   CONCAT(completed.first_name, " ", completed.last_name) AS completed_by_name
            FROM laboratory_requests lr
            LEFT JOIN laboratory_results lres ON lres.laboratory_request_id = lr.id
            LEFT JOIN users performed ON performed.id = lres.performed_by
            LEFT JOIN users completed ON completed.id = lres.completed_by
            WHERE lr.id = :id
            LIMIT 1
        ');
        $stmt->execute([':id' => $requestId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        if ($user !== null && !$this->canViewRow($row, $user)) {
            return null;
        }

        return $this->decorateRow($row);
    }

    public function completeRequest(int $requestId, array $user): array
    {
        try {
            $this->pdo->beginTransaction();
            $request = $this->lockRequest($requestId);
            if (!$request) {
                $this->rollback();
                return $this->failure(['Laboratory request not found.']);
            }

            $visit = $this->lockVisit((int)$request['visit_id']);
            $errors = $this->validateProcessing($request, $visit, $user, true);
            $result = $this->lockResultByRequest($requestId);

            if (!$result) {
                $errors[] = 'Enter a laboratory result before completing the request.';
            }

            if ($errors !== []) {
                $this->rollback();
                return $this->failure($errors);
            }

            $this->transitionRequestRecord($requestId, 'Completed', $user['id']);
            $this->markResultCompleted($requestId, (int)$user['id']);

            if (!$this->audit(
                'LABORATORY_REQUEST_COMPLETED',
                $visit,
                $user,
                'Completed laboratory request #' . $requestId . '.'
            )) {
                throw new RuntimeException('Unable to audit laboratory completion.');
            }

            if (!$this->event(
                (int)$visit['id'],
                'LABORATORY_COMPLETED',
                'Laboratory Request Completed',
                'Laboratory request completed.',
                $visit,
                $user
            )) {
                throw new RuntimeException('Unable to record laboratory completion event.');
            }

            $this->pdo->commit();

            return ['success' => true, 'laboratory_request_id' => $requestId, 'errors' => []];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to complete laboratory request.']);
        }
    }

    public function cancelRequest(int $requestId, array $user): array
    {
        try {
            $this->pdo->beginTransaction();
            $request = $this->lockRequest($requestId);
            if (!$request) {
                $this->rollback();
                return $this->failure(['Laboratory request not found.']);
            }

            $visit = $this->lockVisit((int)$request['visit_id']);
            $errors = $this->validateProcessing($request, $visit, $user, false);

            if ((string)($request['status'] ?? '') !== 'Requested' && (string)($request['status'] ?? '') !== 'In Progress') {
                $errors[] = 'Only active laboratory requests can be cancelled.';
            }

            if ($this->lockResultByRequest($requestId) !== null) {
                $errors[] = 'Requests with results cannot be cancelled.';
            }

            if ($errors !== []) {
                $this->rollback();
                return $this->failure($errors);
            }

            $this->transitionRequestRecord($requestId, 'Cancelled', $user['id']);

            if (!$this->audit(
                'LABORATORY_REQUEST_CANCELLED',
                $visit,
                $user,
                'Cancelled laboratory request #' . $requestId . '.'
            )) {
                throw new RuntimeException('Unable to audit laboratory cancellation.');
            }

            $this->pdo->commit();
            return ['success' => true, 'laboratory_request_id' => $requestId, 'errors' => []];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to cancel laboratory request.']);
        }
    }

    private function saveOrUpdateResult(array $data, array $user, bool $mustExist): array
    {
        try {
            $this->pdo->beginTransaction();

            $requestId = (int)($data['laboratory_request_id'] ?? $data['request_id'] ?? 0);
            $request = $this->lockRequest($requestId);
            if (!$request) {
                $this->rollback();
                return $this->failure(['Laboratory request not found.']);
            }

            $visit = $this->lockVisit((int)$request['visit_id']);
            $errors = $this->validateProcessing($request, $visit, $user, true);
            $resultText = trim((string)($data['result'] ?? ''));
            $interpretation = $this->nullableText($data['interpretation'] ?? null);

            if ($resultText === '') {
                $errors[] = 'Result is required.';
            }

            if ($this->textLength($resultText) > 10000) {
                $errors[] = 'Result is too long.';
            }

            if ($interpretation !== null && $this->textLength($interpretation) > 5000) {
                $errors[] = 'Interpretation is too long.';
            }

            if ($errors !== []) {
                $this->rollback();
                return $this->failure($errors);
            }

            $existing = $this->lockResultByRequest($requestId);
            if ($mustExist && !$existing) {
                $this->rollback();
                return $this->failure(['Laboratory result not found.']);
            }

            if ($existing) {
                $stmt = $this->pdo->prepare('
                    UPDATE laboratory_results
                    SET result = :result,
                        interpretation = :interpretation,
                        performed_by = :performed_by,
                        updated_at = NOW()
                    WHERE laboratory_request_id = :laboratory_request_id
                ');
                $stmt->execute([
                    ':result' => $resultText,
                    ':interpretation' => $interpretation,
                    ':performed_by' => (int)$user['id'],
                    ':laboratory_request_id' => $requestId,
                ]);

                if (!$this->audit(
                    'LABORATORY_RESULT_UPDATED',
                    $visit,
                    $user,
                    'Updated laboratory result for request #' . $requestId . '.'
                )) {
                    throw new RuntimeException('Unable to audit laboratory result update.');
                }
            } else {
                $stmt = $this->pdo->prepare('
                    INSERT INTO laboratory_results (
                        laboratory_request_id, visit_id, patient_id, result,
                        interpretation, performed_by, created_at, updated_at
                    ) VALUES (
                        :laboratory_request_id, :visit_id, :patient_id, :result,
                        :interpretation, :performed_by, NOW(), NOW()
                    )
                ');
                $stmt->execute([
                    ':laboratory_request_id' => $requestId,
                    ':visit_id' => (int)$visit['id'],
                    ':patient_id' => (int)$visit['patient_id'],
                    ':result' => $resultText,
                    ':interpretation' => $interpretation,
                    ':performed_by' => (int)$user['id'],
                ]);

                if ((string)($request['status'] ?? '') === 'Requested') {
                    $this->transitionRequestRecord($requestId, 'In Progress', $user['id']);
                }

                if (!$this->audit(
                    'LABORATORY_RESULT_CREATED',
                    $visit,
                    $user,
                    'Created laboratory result for request #' . $requestId . '.'
                )) {
                    throw new RuntimeException('Unable to audit laboratory result creation.');
                }
            }

            $this->pdo->commit();

            return [
                'success' => true,
                'laboratory_request_id' => $requestId,
                'errors' => [],
            ];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to save laboratory result.']);
        }
    }

    private function validateRequest(
        array $visit,
        array $user,
        string $source,
        string $priority,
        string $testsRequested,
        ?string $clinicalInformation
    ): array {
        $errors = [];
        if (!$this->permissionService->canViewEncounter($visit, $user)) {
            $errors[] = 'You cannot access this encounter.';
        }

        if (in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true)
            && !$this->permissionService->isAdministrator($user)) {
            $errors[] = 'Completed or cancelled encounters cannot receive laboratory mutations.';
        }

        if ($source === 'Clinical') {
            if (!$this->permissionService->canCreateLaboratoryRequest($visit, $user, 'Clinical')) {
                $errors[] = 'You cannot create a clinical laboratory request.';
            }
        } elseif ($source === 'Direct') {
            if (!$this->permissionService->canCreateLaboratoryRequest($visit, $user, 'Direct')) {
                $errors[] = 'You cannot create a direct laboratory request.';
            }

            if (!$this->isLaboratoryEncounter($visit)) {
                $errors[] = 'Direct laboratory requests require an active Laboratory encounter.';
            }
        } else {
            $errors[] = 'Invalid laboratory request source.';
        }

        if ($testsRequested === '') {
            $errors[] = 'Tests requested are required.';
        }

        if ($this->textLength($testsRequested) > 5000) {
            $errors[] = 'Tests requested is too long.';
        }

        if ($clinicalInformation !== null && $this->textLength($clinicalInformation) > 5000) {
            $errors[] = 'Clinical information is too long.';
        }

        if (!in_array($priority, ['Routine', 'Urgent'], true)) {
            $errors[] = 'Priority is invalid.';
        }

        return $errors;
    }

    private function validateProcessing(array $request, array $visit, array $user, bool $requireProcessPermission): array
    {
        $errors = [];
        if (!$this->permissionService->canViewEncounter($visit, $user)) {
            $errors[] = 'You cannot access this encounter.';
        }

        if (in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true)
            && !$this->permissionService->isAdministrator($user)) {
            $errors[] = 'Completed or cancelled encounters cannot receive laboratory mutations.';
        }

        if ($requireProcessPermission && !$this->permissionService->canProcessLaboratoryRequest($visit, $user)) {
            $errors[] = 'You cannot process this laboratory request.';
        }

        if (!$requireProcessPermission && !$this->permissionService->canEnterLaboratoryResult($visit, $user)) {
            $errors[] = 'You cannot complete this laboratory request.';
        }

        if ((string)($request['status'] ?? '') === 'Cancelled') {
            $errors[] = 'Cancelled laboratory requests cannot be modified.';
        }

        if ((string)($request['status'] ?? '') === 'Completed') {
            $errors[] = 'Completed laboratory requests are view-only.';
        }

        return $errors;
    }

    private function transitionRequest(int $requestId, array $user, string $newStatus, string $action, string $description): array
    {
        try {
            $this->pdo->beginTransaction();
            $request = $this->lockRequest($requestId);
            if (!$request) {
                $this->rollback();
                return $this->failure(['Laboratory request not found.']);
            }

            $visit = $this->lockVisit((int)$request['visit_id']);
            $errors = $this->validateProcessing($request, $visit, $user, true);

            if ($newStatus === 'In Progress' && (string)($request['status'] ?? '') !== 'Requested') {
                $errors[] = 'Only requested laboratory requests can be started.';
            }

            if ($newStatus === 'In Progress' && !$this->permissionService->canProcessLaboratoryRequest($visit, $user)) {
                $errors[] = 'You cannot start this laboratory request.';
            }

            if ($errors !== []) {
                $this->rollback();
                return $this->failure($errors);
            }

            $this->transitionRequestRecord($requestId, $newStatus, $user['id']);

            if (!$this->audit($action, $visit, $user, $description)) {
                throw new RuntimeException('Unable to audit laboratory request transition.');
            }

            $this->pdo->commit();
            return ['success' => true, 'laboratory_request_id' => $requestId, 'errors' => []];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to update laboratory request.']);
        }
    }

    private function transitionRequestRecord(int $requestId, string $status, int $actorId): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE laboratory_requests
            SET status = :status,
                updated_at = NOW()
            WHERE id = :id
        ');
        $stmt->execute([
            ':status' => $status,
            ':id' => $requestId,
        ]);
    }

    private function markResultCompleted(int $requestId, int $completedBy): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE laboratory_results
            SET completed_by = COALESCE(completed_by, :completed_by),
                completed_at = COALESCE(completed_at, NOW()),
                updated_at = NOW()
            WHERE laboratory_request_id = :laboratory_request_id
        ');
        $stmt->execute([
            ':completed_by' => $completedBy,
            ':laboratory_request_id' => $requestId,
        ]);
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
        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE lr.id = :id LIMIT 1 FOR UPDATE');
        $stmt->execute([':id' => $requestId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function lockResultByRequest(int $requestId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM laboratory_results WHERE laboratory_request_id = :id FOR UPDATE');
        $stmt->execute([':id' => $requestId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function canViewRow(array $row, array $user): bool
    {
        return $this->permissionService->canViewLaboratory((int)($row['patient_id'] ?? 0), $user)
            || $this->permissionService->isAdministrator($user);
    }

    private function filterRows(array $rows, ?array $user): array
    {
        if ($user === null) {
            return array_map([$this, 'decorateRow'], $rows);
        }

        $rows = array_filter($rows, fn (array $row): bool => $this->canViewRow($row, $user));
        return array_map([$this, 'decorateRow'], array_values($rows));
    }

    private function decorateRow(array $row): array
    {
        $row['result_status'] = !empty($row['result_id'])
            ? ((string)($row['result_completed_at'] ?? '') !== '' ? 'Completed' : 'Recorded')
            : 'Pending';
        $row['summary'] = $this->summarize($row);
        return $row;
    }

    private function summarize(array $row): string
    {
        $tests = trim((string)($row['tests_requested'] ?? ''));
        if ($tests === '') {
            return 'Laboratory request.';
        }

        return mb_strlen($tests) > 180
            ? mb_substr($tests, 0, 177) . '...'
            : $tests;
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
        return in_array($status, ['Requested', 'In Progress', 'Completed', 'Cancelled', 'All'], true)
            ? $status
            : '';
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

    private function resolveLaboratoryDepartmentId(): ?int
    {
        $stmt = $this->pdo->prepare("
            SELECT id
            FROM departments
            WHERE department_name = 'Laboratory'
            LIMIT 1
        ");
        $stmt->execute();
        $id = $stmt->fetchColumn();
        return $id !== false ? (int)$id : null;
    }

    private function isLaboratoryEncounter(array $visit): bool
    {
        $labDepartmentId = $this->resolveLaboratoryDepartmentId();
        if ($labDepartmentId !== null && (int)($visit['current_department_id'] ?? 0) === $labDepartmentId) {
            return true;
        }

        return (string)($visit['visit_status'] ?? '') === 'Laboratory';
    }

    private function audit(string $action, array $visit, array $user, string $description): bool
    {
        return $this->auditService->logPatient(
            (int)($user['id'] ?? 0),
            (int)$visit['patient_id'],
            (int)$visit['id'],
            'Laboratory',
            $action,
            $description,
            (int)($visit['current_department_id'] ?? 0) ?: null,
            'INFO',
            $action
        );
    }

    private function event(
        int $visitId,
        string $type,
        string $title,
        string $description,
        array $visit,
        array $user
    ): bool {
        $event = $this->eventService->record(
            $visitId,
            $type,
            $title,
            $description,
            (int)($visit['current_department_id'] ?? 0) ?: null,
            (int)($user['id'] ?? 0) ?: null
        );
        return (bool)($event['success'] ?? false);
    }

    private function baseSelect(): string
    {
        return '
            SELECT lr.*,
                   p.hospital_number,
                   CONCAT(p.first_name, " ", p.last_name) AS patient_name,
                   v.visit_number,
                   v.visit_status,
                   d.department_name,
                   CONCAT(req.first_name, " ", req.last_name) AS requested_by_name,
                   CONCAT(resper.first_name, " ", resper.last_name) AS result_performed_by_name,
                   CONCAT(rescomp.first_name, " ", rescomp.last_name) AS result_completed_by_name,
                   lres.id AS result_id,
                   lres.result,
                   lres.interpretation,
                   lres.performed_by AS result_performed_by,
                   lres.completed_by AS result_completed_by,
                   lres.created_at AS result_created_at,
                   lres.updated_at AS result_updated_at,
                   lres.completed_at AS result_completed_at
            FROM laboratory_requests lr
            INNER JOIN visits v ON v.id = lr.visit_id
            INNER JOIN patients p ON p.id = lr.patient_id
            LEFT JOIN departments d ON d.id = lr.department_id
            LEFT JOIN users req ON req.id = lr.requested_by
            LEFT JOIN laboratory_results lres ON lres.laboratory_request_id = lr.id
            LEFT JOIN users resper ON resper.id = lres.performed_by
            LEFT JOIN users rescomp ON rescomp.id = lres.completed_by
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
}
