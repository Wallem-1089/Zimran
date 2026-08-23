<?php

declare(strict_types=1);

require_once __DIR__ . '/AuditService.php';
require_once __DIR__ . '/EncounterEventService.php';
require_once __DIR__ . '/PermissionService.php';

class PhysiotherapyService
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

    public function createRecord(array $data, array $user): array
    {
        try {
            $this->pdo->beginTransaction();

            $visit = $this->lockVisit((int)($data['visit_id'] ?? 0));
            $source = $this->normalizeSource((string)($data['record_source'] ?? $data['source'] ?? 'Clinical'));
            $departmentId = $this->resolvePhysiotherapyDepartmentId();
            $errors = $this->validateRecord($visit, $user, $source, $data);

            if (isset($data['patient_id']) && (int)$data['patient_id'] !== (int)$visit['patient_id']) {
                $errors[] = 'Patient and encounter do not match.';
            }

            if ($this->getByVisit((int)$visit['id'], $user) !== null) {
                $errors[] = 'A physiotherapy record already exists for this encounter.';
            }

            if ($departmentId === null) {
                $errors[] = 'Physiotherapy department is not available.';
            }

            if ($errors !== []) {
                $this->rollback();
                return $this->failure($errors);
            }

            $statement = $this->pdo->prepare('
                INSERT INTO physiotherapy_records (
                    visit_id, patient_id, physiotherapist_id, department_id, record_source,
                    referral_reason, presenting_problem, assessment, functional_limitations,
                    treatment_plan, goals, precautions, status, created_by, created_at, updated_at
                ) VALUES (
                    :visit_id, :patient_id, :physiotherapist_id, :department_id, :record_source,
                    :referral_reason, :presenting_problem, :assessment, :functional_limitations,
                    :treatment_plan, :goals, :precautions, \'Active\', :created_by, NOW(), NOW()
                )
            ');
            $statement->execute([
                ':visit_id' => (int)$visit['id'],
                ':patient_id' => (int)$visit['patient_id'],
                ':physiotherapist_id' => $this->resolveAssignedPhysiotherapistId(null, $user, $source),
                ':department_id' => $departmentId,
                ':record_source' => $source,
                ':referral_reason' => $this->nullableText($data['referral_reason'] ?? null),
                ':presenting_problem' => $this->requiredText($data['presenting_problem'] ?? $data['assessment'] ?? ''),
                ':assessment' => $this->requiredText($data['assessment'] ?? ''),
                ':functional_limitations' => $this->nullableText($data['functional_limitations'] ?? null),
                ':treatment_plan' => $this->requiredText($data['treatment_plan'] ?? ''),
                ':goals' => $this->nullableText($data['goals'] ?? null),
                ':precautions' => $this->nullableText($data['precautions'] ?? null),
                ':created_by' => (int)$user['id'],
            ]);
            $recordId = (int)$this->pdo->lastInsertId();

            if (!$this->audit(
                'PHYSIOTHERAPY_CREATED',
                $visit,
                $user,
                'Created physiotherapy record #' . $recordId . '.'
            )) {
                throw new RuntimeException('Unable to audit physiotherapy creation.');
            }

            if (!$this->event(
                (int)$visit['id'],
                'PHYSIOTHERAPY_STARTED',
                'Physiotherapy Started',
                'Physiotherapy record created.',
                $visit,
                $user
            )) {
                throw new RuntimeException('Unable to record physiotherapy start event.');
            }

            $this->pdo->commit();

            return [
                'success' => true,
                'physiotherapy_record_id' => $recordId,
                'visit_id' => (int)$visit['id'],
                'patient_id' => (int)$visit['patient_id'],
                'errors' => [],
            ];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to save physiotherapy record.']);
        }
    }

    public function createRequest(array $data, array $user): array
    {
        return $this->createRecord($data, $user);
    }

    public function getRecordById(int $recordId, ?array $user = null): ?array
    {
        $stmt = $this->pdo->prepare($this->baseRecordSelect() . ' WHERE pr.id = :id LIMIT 1');
        $stmt->execute([':id' => $recordId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        if ($user !== null && !$this->canViewRow($row, $user)) {
            return null;
        }

        return $this->decorateRecord($row);
    }

    public function getRequestById(int $recordId, ?array $user = null): ?array
    {
        return $this->getRecordById($recordId, $user);
    }

    public function getByVisit(int $visitId, ?array $user = null): ?array
    {
        if ($visitId <= 0) {
            return null;
        }

        $stmt = $this->pdo->prepare($this->baseRecordSelect() . ' WHERE pr.visit_id = :visit_id ORDER BY pr.id DESC LIMIT 1');
        $stmt->execute([':visit_id' => $visitId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        if ($user !== null && !$this->canViewRow($row, $user)) {
            return null;
        }

        return $this->decorateRecord($row);
    }

    public function listByPatient(int $patientId, ?array $user = null): array
    {
        if ($patientId <= 0) {
            return [];
        }

        $stmt = $this->pdo->prepare($this->baseRecordSelect() . ' WHERE pr.patient_id = :patient_id ORDER BY pr.created_at DESC, pr.id DESC');
        $stmt->execute([':patient_id' => $patientId]);
        return $this->filterRecords($stmt->fetchAll(PDO::FETCH_ASSOC), $user);
    }

    public function getResult(int $recordId, ?array $user = null): ?array
    {
        $session = $this->latestSession($recordId);
        if (!$session) {
            return null;
        }

        if ($user !== null && !$this->canViewRow($session, $user)) {
            return null;
        }

        return $this->decorateSession($session);
    }

    public function listWorklist(?array $user = null, array $filters = []): array
    {
        if ($user !== null && !$this->permissionService->canViewPhysiotherapyWorklist($user)) {
            return [];
        }

        $status = $this->normalizeWorklistStatus((string)($filters['status'] ?? ''));
        $params = [];
        $where = '';

        if ($status === '') {
            $where = " WHERE pr.status = 'Active'";
        } elseif ($status !== 'All') {
            $where = ' WHERE pr.status = :status';
            $params[':status'] = $status;
        }

        $stmt = $this->pdo->prepare($this->baseRecordSelect() . $where . ' ORDER BY pr.created_at DESC, pr.id DESC');
        $stmt->execute($params);
        return $this->filterRecords($stmt->fetchAll(PDO::FETCH_ASSOC), $user);
    }

    public function updateRecord(int $recordId, array $data, array $user): array
    {
        try {
            $this->pdo->beginTransaction();

            $record = $this->lockRecord($recordId);
            if (!$record) {
                $this->rollback();
                return $this->failure(['Physiotherapy record not found.']);
            }

            $visit = $this->lockVisit((int)$record['visit_id']);
            $errors = $this->validateRecordMutation($record, $visit, $user, 'edit_physiotherapy');

            if ((string)$record['status'] !== 'Active') {
                $errors[] = 'Completed or cancelled physiotherapy records are view-only.';
            }

            if (isset($data['patient_id']) && (int)$data['patient_id'] !== (int)$visit['patient_id']) {
                $errors[] = 'Patient and encounter do not match.';
            }

            $payload = $this->recordPayload($data, $record);
            $errors = array_merge($errors, $payload['errors']);

            if ($errors !== []) {
                $this->rollback();
                return $this->failure($errors);
            }

            $statement = $this->pdo->prepare('
                UPDATE physiotherapy_records
                SET physiotherapist_id = :physiotherapist_id,
                    department_id = :department_id,
                    referral_reason = :referral_reason,
                    presenting_problem = :presenting_problem,
                    assessment = :assessment,
                    functional_limitations = :functional_limitations,
                    treatment_plan = :treatment_plan,
                    goals = :goals,
                    precautions = :precautions,
                    updated_by = :updated_by
                WHERE id = :id
            ');
            $statement->execute([
                ':physiotherapist_id' => $this->resolveAssignedPhysiotherapistId($record, $user, (string)$record['record_source']),
                ':department_id' => $this->resolvePhysiotherapyDepartmentId(),
                ':referral_reason' => $payload['referral_reason'],
                ':presenting_problem' => $payload['presenting_problem'],
                ':assessment' => $payload['assessment'],
                ':functional_limitations' => $payload['functional_limitations'],
                ':treatment_plan' => $payload['treatment_plan'],
                ':goals' => $payload['goals'],
                ':precautions' => $payload['precautions'],
                ':updated_by' => (int)$user['id'],
                ':id' => $recordId,
            ]);

            if (!$this->audit('PHYSIOTHERAPY_UPDATED', $visit, $user, 'Updated physiotherapy record #' . $recordId . '.')) {
                throw new RuntimeException('Unable to audit physiotherapy update.');
            }

            $this->pdo->commit();

            return [
                'success' => true,
                'physiotherapy_record_id' => $recordId,
                'visit_id' => (int)$visit['id'],
                'patient_id' => (int)$visit['patient_id'],
                'errors' => [],
            ];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to update physiotherapy record.']);
        }
    }

    public function addSession(array $data, array $user): array
    {
        try {
            $this->pdo->beginTransaction();

            $recordId = (int)($data['physiotherapy_record_id'] ?? $data['record_id'] ?? 0);
            $record = $this->lockRecord($recordId);
            if (!$record) {
                $this->rollback();
                return $this->failure(['Physiotherapy record not found.']);
            }

            $visit = $this->lockVisit((int)$record['visit_id']);
            $errors = $this->validateSessionMutation($record, $visit, $user, 'manage_physiotherapy_sessions');
            $sessionDate = $this->parseSessionDate($data['session_date'] ?? null);
            $treatmentGiven = $this->requiredText($data['treatment_given'] ?? '');
            $patientResponse = $this->nullableText($data['patient_response'] ?? null);
            $progressNotes = $this->nullableText($data['progress_notes'] ?? null);
            $nextPlan = $this->nullableText($data['next_plan'] ?? null);

            if ($sessionDate === null) {
                $errors[] = 'Session date is required.';
            }
            if ($this->textLength($treatmentGiven) > 10000) {
                $errors[] = 'Treatment given is too long.';
            }
            if ($patientResponse !== null && $this->textLength($patientResponse) > 10000) {
                $errors[] = 'Patient response is too long.';
            }
            if ($progressNotes !== null && $this->textLength($progressNotes) > 10000) {
                $errors[] = 'Progress notes are too long.';
            }
            if ($nextPlan !== null && $this->textLength($nextPlan) > 10000) {
                $errors[] = 'Next plan is too long.';
            }

            if ($errors !== []) {
                $this->rollback();
                return $this->failure($errors);
            }

            $statement = $this->pdo->prepare('
                INSERT INTO physiotherapy_sessions (
                    physiotherapy_record_id, visit_id, patient_id, session_date,
                    treatment_given, patient_response, progress_notes, next_plan,
                    recorded_by, created_at, updated_at
                ) VALUES (
                    :physiotherapy_record_id, :visit_id, :patient_id, :session_date,
                    :treatment_given, :patient_response, :progress_notes, :next_plan,
                    :recorded_by, NOW(), NOW()
                )
            ');
            $statement->execute([
                ':physiotherapy_record_id' => $recordId,
                ':visit_id' => (int)$visit['id'],
                ':patient_id' => (int)$visit['patient_id'],
                ':session_date' => $sessionDate->format('Y-m-d H:i:s'),
                ':treatment_given' => $treatmentGiven,
                ':patient_response' => $patientResponse,
                ':progress_notes' => $progressNotes,
                ':next_plan' => $nextPlan,
                ':recorded_by' => (int)$user['id'],
            ]);
            $sessionId = (int)$this->pdo->lastInsertId();

            if (!$this->audit('PHYSIOTHERAPY_SESSION_CREATED', $visit, $user, 'Created physiotherapy session #' . $sessionId . '.')) {
                throw new RuntimeException('Unable to audit physiotherapy session creation.');
            }

            $this->pdo->commit();

            return [
                'success' => true,
                'physiotherapy_session_id' => $sessionId,
                'physiotherapy_record_id' => $recordId,
                'visit_id' => (int)$visit['id'],
                'patient_id' => (int)$visit['patient_id'],
                'errors' => [],
            ];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to save physiotherapy session.']);
        }
    }

    public function saveResult(array $data, array $user): array
    {
        return $this->addSession($data, $user);
    }

    public function updateResult(array $data, array $user): array
    {
        return $this->updateSession((int)($data['physiotherapy_session_id'] ?? $data['session_id'] ?? 0), $data, $user);
    }

    public function getSessionById(int $sessionId, ?array $user = null): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT ps.*, pr.status AS record_status,
                   p.hospital_number,
                   CONCAT(p.first_name, " ", p.last_name) AS patient_name,
                   v.visit_number,
                   CONCAT(rn.first_name, " ", rn.last_name) AS recorded_by_name
            FROM physiotherapy_sessions ps
            INNER JOIN physiotherapy_records pr ON pr.id = ps.physiotherapy_record_id
            INNER JOIN patients p ON p.id = ps.patient_id
            INNER JOIN visits v ON v.id = ps.visit_id
            LEFT JOIN users rn ON rn.id = ps.recorded_by
            WHERE ps.id = :id
            LIMIT 1
        ');
        $stmt->execute([':id' => $sessionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        if ($user !== null && !$this->canViewRow($row, $user)) {
            return null;
        }

        return $this->decorateSession($row);
    }

    public function listSessions(int $recordId, ?array $user = null): array
    {
        if ($recordId <= 0) {
            return [];
        }

        $stmt = $this->pdo->prepare('
            SELECT ps.*, pr.status AS record_status,
                   p.hospital_number,
                   CONCAT(p.first_name, " ", p.last_name) AS patient_name,
                   v.visit_number,
                   CONCAT(rn.first_name, " ", rn.last_name) AS recorded_by_name
            FROM physiotherapy_sessions ps
            INNER JOIN physiotherapy_records pr ON pr.id = ps.physiotherapy_record_id
            INNER JOIN patients p ON p.id = ps.patient_id
            INNER JOIN visits v ON v.id = ps.visit_id
            LEFT JOIN users rn ON rn.id = ps.recorded_by
            WHERE ps.physiotherapy_record_id = :record_id
            ORDER BY ps.session_date DESC, ps.id DESC
        ');
        $stmt->execute([':record_id' => $recordId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($user === null) {
            return array_map([$this, 'decorateSession'], $rows);
        }

        $rows = array_filter($rows, fn (array $row): bool => $this->canViewRow($row, $user));
        return array_map([$this, 'decorateSession'], array_values($rows));
    }

    public function updateSession(int $sessionId, array $data, array $user): array
    {
        try {
            $this->pdo->beginTransaction();

            $session = $this->lockSession($sessionId);
            if (!$session) {
                $this->rollback();
                return $this->failure(['Physiotherapy session not found.']);
            }

            $record = $this->lockRecord((int)$session['physiotherapy_record_id']);
            if (!$record) {
                $this->rollback();
                return $this->failure(['Physiotherapy record not found.']);
            }

            $visit = $this->lockVisit((int)$record['visit_id']);
            $errors = $this->validateSessionMutation($record, $visit, $user, 'manage_physiotherapy_sessions');

            if ((string)$record['status'] !== 'Active') {
                $errors[] = 'Completed or cancelled physiotherapy records are view-only.';
            }

            $sessionDate = $this->parseSessionDate($data['session_date'] ?? null, $session['session_date']);
            $treatmentGiven = $this->requiredText($data['treatment_given'] ?? '');
            $patientResponse = $this->nullableText($data['patient_response'] ?? null);
            $progressNotes = $this->nullableText($data['progress_notes'] ?? null);
            $nextPlan = $this->nullableText($data['next_plan'] ?? null);

            if ($sessionDate === null) {
                $errors[] = 'Session date is required.';
            }
            if ($this->textLength($treatmentGiven) > 10000) {
                $errors[] = 'Treatment given is too long.';
            }
            if ($patientResponse !== null && $this->textLength($patientResponse) > 10000) {
                $errors[] = 'Patient response is too long.';
            }
            if ($progressNotes !== null && $this->textLength($progressNotes) > 10000) {
                $errors[] = 'Progress notes are too long.';
            }
            if ($nextPlan !== null && $this->textLength($nextPlan) > 10000) {
                $errors[] = 'Next plan is too long.';
            }

            if ($errors !== []) {
                $this->rollback();
                return $this->failure($errors);
            }

            $statement = $this->pdo->prepare('
                UPDATE physiotherapy_sessions
                SET session_date = :session_date,
                    treatment_given = :treatment_given,
                    patient_response = :patient_response,
                    progress_notes = :progress_notes,
                    next_plan = :next_plan,
                    updated_at = NOW()
                WHERE id = :id
            ');
            $statement->execute([
                ':session_date' => $sessionDate->format('Y-m-d H:i:s'),
                ':treatment_given' => $treatmentGiven,
                ':patient_response' => $patientResponse,
                ':progress_notes' => $progressNotes,
                ':next_plan' => $nextPlan,
                ':id' => $sessionId,
            ]);

            if (!$this->audit('PHYSIOTHERAPY_SESSION_UPDATED', $visit, $user, 'Updated physiotherapy session #' . $sessionId . '.')) {
                throw new RuntimeException('Unable to audit physiotherapy session update.');
            }

            $this->pdo->commit();

            return [
                'success' => true,
                'physiotherapy_session_id' => $sessionId,
                'physiotherapy_record_id' => (int)$record['id'],
                'visit_id' => (int)$visit['id'],
                'patient_id' => (int)$visit['patient_id'],
                'errors' => [],
            ];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to update physiotherapy session.']);
        }
    }

    public function completeRecord(int $recordId, array $user): array
    {
        try {
            $this->pdo->beginTransaction();

            $record = $this->lockRecord($recordId);
            if (!$record) {
                $this->rollback();
                return $this->failure(['Physiotherapy record not found.']);
            }

            $visit = $this->lockVisit((int)$record['visit_id']);
            $errors = $this->validateCompletion($record, $visit, $user);

            if ($errors !== []) {
                $this->rollback();
                return $this->failure($errors);
            }

            $statement = $this->pdo->prepare('
                UPDATE physiotherapy_records
                SET status = \'Completed\',
                    physiotherapist_id = COALESCE(physiotherapist_id, :physiotherapist_id),
                    completed_by = :completed_by,
                    completed_at = NOW(),
                    updated_by = :updated_by
                WHERE id = :id
            ');
            $statement->execute([
                ':physiotherapist_id' => $this->resolveAssignedPhysiotherapistId($record, $user, (string)$record['record_source']),
                ':completed_by' => (int)$user['id'],
                ':updated_by' => (int)$user['id'],
                ':id' => $recordId,
            ]);

            if (!$this->audit('PHYSIOTHERAPY_COMPLETED', $visit, $user, 'Completed physiotherapy record #' . $recordId . '.')) {
                throw new RuntimeException('Unable to audit physiotherapy completion.');
            }

            if (!$this->event(
                (int)$visit['id'],
                'PHYSIOTHERAPY_COMPLETED',
                'Physiotherapy Completed',
                'Physiotherapy record completed.',
                $visit,
                $user
            )) {
                throw new RuntimeException('Unable to record physiotherapy completion event.');
            }

            $this->pdo->commit();

            return ['success' => true, 'physiotherapy_record_id' => $recordId, 'errors' => []];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to complete physiotherapy record.']);
        }
    }

    public function completeRequest(int $recordId, array $user): array
    {
        return $this->completeRecord($recordId, $user);
    }

    public function cancelRecord(int $recordId, array $user): array
    {
        try {
            $this->pdo->beginTransaction();

            $record = $this->lockRecord($recordId);
            if (!$record) {
                $this->rollback();
                return $this->failure(['Physiotherapy record not found.']);
            }

            $visit = $this->lockVisit((int)$record['visit_id']);
            $errors = $this->validateRecordMutation($record, $visit, $user, 'edit_physiotherapy');

            if ((string)$record['status'] !== 'Active') {
                $errors[] = 'Only active physiotherapy records can be cancelled.';
            }

            if ($errors !== []) {
                $this->rollback();
                return $this->failure($errors);
            }

            $statement = $this->pdo->prepare('
                UPDATE physiotherapy_records
                SET status = \'Cancelled\',
                    updated_by = :updated_by
                WHERE id = :id
            ');
            $statement->execute([
                ':updated_by' => (int)$user['id'],
                ':id' => $recordId,
            ]);

            if (!$this->audit('PHYSIOTHERAPY_CANCELLED', $visit, $user, 'Cancelled physiotherapy record #' . $recordId . '.')) {
                throw new RuntimeException('Unable to audit physiotherapy cancellation.');
            }

            $this->pdo->commit();
            return ['success' => true, 'physiotherapy_record_id' => $recordId, 'errors' => []];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to cancel physiotherapy record.']);
        }
    }

    public function cancelRequest(int $recordId, array $user): array
    {
        return $this->cancelRecord($recordId, $user);
    }

    public function startRequest(int $recordId, array $user): array
    {
        $record = $this->getRecordById($recordId, $user);
        if (!$record) {
            return $this->failure(['Physiotherapy record not found.']);
        }

        return ['success' => true, 'physiotherapy_record_id' => $recordId, 'errors' => []];
    }

    public function listByVisit(int $visitId, ?array $user = null): array
    {
        if ($visitId <= 0) {
            return [];
        }

        $stmt = $this->pdo->prepare($this->baseRecordSelect() . ' WHERE pr.visit_id = :visit_id ORDER BY pr.created_at DESC, pr.id DESC');
        $stmt->execute([':visit_id' => $visitId]);
        return $this->filterRecords($stmt->fetchAll(PDO::FETCH_ASSOC), $user);
    }

    private function validateRecord(array $visit, array $user, string $source, array $data): array
    {
        $errors = [];

        if (!$this->canAccessPhysiotherapyEncounter($visit, $user)) {
            $errors[] = 'You cannot access this encounter.';
        }

        if (in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true)
            && !$this->permissionService->isAdministrator($user)) {
            $errors[] = 'Completed or cancelled encounters cannot receive physiotherapy mutations.';
        }

        if ($source === 'Clinical') {
            if (!$this->permissionService->canCreatePhysiotherapy($visit, $user, 'Clinical')) {
                $errors[] = 'You cannot create a clinical physiotherapy referral.';
            }
        } elseif ($source === 'Direct') {
            if (!$this->permissionService->canCreatePhysiotherapy($visit, $user, 'Direct')) {
                $errors[] = 'You cannot create a direct physiotherapy record.';
            }

            if (!$this->isPhysiotherapyEncounter($visit)) {
                $errors[] = 'Direct physiotherapy records require an active Physiotherapy encounter.';
            }
        } else {
            $errors[] = 'Invalid physiotherapy record source.';
        }

        $presentingProblem = $this->requiredText($data['presenting_problem'] ?? '');
        $assessment = $this->requiredText($data['assessment'] ?? '');
        $treatmentPlan = $this->requiredText($data['treatment_plan'] ?? '');

        if ($presentingProblem === '') {
            $errors[] = 'Presenting problem is required.';
        }
        if ($assessment === '') {
            $errors[] = 'Assessment is required.';
        }
        if ($treatmentPlan === '') {
            $errors[] = 'Treatment plan is required.';
        }

        if ($this->textLength($presentingProblem) > 10000) {
            $errors[] = 'Presenting problem is too long.';
        }
        if ($this->textLength($assessment) > 10000) {
            $errors[] = 'Assessment is too long.';
        }
        if ($this->textLength($treatmentPlan) > 10000) {
            $errors[] = 'Treatment plan is too long.';
        }

        $referralReason = $this->nullableText($data['referral_reason'] ?? null);
        $functionalLimitations = $this->nullableText($data['functional_limitations'] ?? null);
        $goals = $this->nullableText($data['goals'] ?? null);
        $precautions = $this->nullableText($data['precautions'] ?? null);

        foreach ([$referralReason, $functionalLimitations, $goals, $precautions] as $text) {
            if ($text !== null && $this->textLength($text) > 10000) {
                $errors[] = 'Physiotherapy text is too long.';
                break;
            }
        }

        return $errors;
    }

    private function validateRecordMutation(array $record, array $visit, array $user, string $permission): array
    {
        $errors = [];

        if (!$this->canAccessPhysiotherapyEncounter($visit, $user)) {
            $errors[] = 'You cannot access this encounter.';
        }

        if (in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true)
            && !$this->permissionService->isAdministrator($user)) {
            $errors[] = 'Completed or cancelled encounters cannot receive physiotherapy mutations.';
        }

        if (!$this->permissionService->hasPermission($permission, $user) && !$this->permissionService->isAdministrator($user)) {
            $errors[] = 'You cannot modify this physiotherapy record.';
        } elseif ($permission === 'edit_physiotherapy' && !$this->permissionService->canEditPhysiotherapy($visit, $user)) {
            $errors[] = 'You cannot edit this physiotherapy record.';
        }

        if ((string)($record['status'] ?? '') === 'Cancelled') {
            $errors[] = 'Cancelled physiotherapy records are view-only.';
        }

        if ((string)($record['status'] ?? '') === 'Completed') {
            $errors[] = 'Completed physiotherapy records are view-only.';
        }

        return $errors;
    }

    private function validateSessionMutation(array $record, array $visit, array $user, string $permission): array
    {
        $errors = $this->validateRecordMutation($record, $visit, $user, 'edit_physiotherapy');

        if (!$this->permissionService->hasPermission($permission, $user) && !$this->permissionService->isAdministrator($user)) {
            $errors[] = 'You cannot manage physiotherapy sessions.';
        }

        if (!$this->permissionService->canManagePhysiotherapySessions($visit, $user) && !$this->permissionService->isAdministrator($user)) {
            $errors[] = 'You cannot manage physiotherapy sessions.';
        }

        return $errors;
    }

    private function validateCompletion(array $record, array $visit, array $user): array
    {
        $errors = $this->validateRecordMutation($record, $visit, $user, 'complete_physiotherapy');

        if ((string)($record['status'] ?? '') !== 'Active') {
            $errors[] = 'Only active physiotherapy records can be completed.';
        }

        if (!$this->hasMeaningfulRecordContent($record)) {
            $errors[] = 'Add assessment and treatment plan before completing the physiotherapy record.';
        }

        if ($this->countSessions((int)$record['id']) < 1) {
            $errors[] = 'Add at least one physiotherapy session before completing the record.';
        }

        if (!$this->permissionService->canCompletePhysiotherapy($visit, $user) && !$this->permissionService->isAdministrator($user)) {
            $errors[] = 'You cannot complete this physiotherapy record.';
        }

        return $errors;
    }

    private function countSessions(int $recordId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM physiotherapy_sessions WHERE physiotherapy_record_id = :id');
        $stmt->execute([':id' => $recordId]);
        return (int)$stmt->fetchColumn();
    }

    private function hasMeaningfulRecordContent(array $record): bool
    {
        $fields = [
            (string)($record['presenting_problem'] ?? ''),
            (string)($record['assessment'] ?? ''),
            (string)($record['treatment_plan'] ?? ''),
        ];

        foreach ($fields as $field) {
            if (trim($field) === '') {
                return false;
            }
        }

        return true;
    }

    private function resolveAssignedPhysiotherapistId(?array $record, array $user, string $source): ?int
    {
        if ($record !== null && !empty($record['physiotherapist_id'])) {
            return (int)$record['physiotherapist_id'];
        }

        $role = (string)($user['role_name'] ?? '');
        $department = (string)($user['department_name'] ?? '');

        if (in_array($role, ['Physiotherapist', 'Physiotherapy'], true)
            || in_array($department, ['Physiotherapy', 'Physio', 'Rehabilitation'], true)
            || $this->permissionService->isAdministrator($user)) {
            return (int)$user['id'];
        }

        return null;
    }

    private function canAccessPhysiotherapyEncounter(array $visit, array $user): bool
    {
        if ($this->permissionService->isAdministrator($user)) {
            return true;
        }

        if ($this->permissionService->hasPermission('view_physiotherapy', $user)
            && in_array((string)($user['role_name'] ?? ''), ['Physiotherapist', 'Physiotherapy'], true)) {
            return true;
        }

        return $this->permissionService->canViewEncounter($visit, $user);
    }

    private function canViewRow(array $row, array $user): bool
    {
        $patientId = (int)($row['patient_id'] ?? 0);
        return $this->permissionService->canViewPhysiotherapy($patientId, $user)
            || $this->permissionService->isAdministrator($user);
    }

    private function filterRecords(array $rows, ?array $user): array
    {
        if ($user === null) {
            return array_map([$this, 'decorateRecord'], $rows);
        }

        $rows = array_filter($rows, fn (array $row): bool => $this->canViewRow($row, $user));
        return array_map([$this, 'decorateRecord'], array_values($rows));
    }

    private function decorateRecord(array $row): array
    {
        $summary = $this->latestSessionSummary((int)($row['id'] ?? 0));
        $row['session_count'] = $summary['count'];
        $row['latest_session_date'] = $summary['latest_session_date'];
        $row['latest_session_summary'] = $summary['summary'];
        $row['summary'] = $this->summarizeRecord($row);
        return $row;
    }

    private function decorateSession(array $row): array
    {
        $row['summary'] = $this->summarizeSession($row);
        return $row;
    }

    private function latestSessionSummary(int $recordId): array
    {
        if ($recordId <= 0) {
            return ['count' => 0, 'latest_session_date' => null, 'summary' => null];
        }

        $stmt = $this->pdo->prepare('
            SELECT COUNT(*) AS session_count,
                   MAX(session_date) AS latest_session_date
            FROM physiotherapy_sessions
            WHERE physiotherapy_record_id = :id
        ');
        $stmt->execute([':id' => $recordId]);
        $aggregate = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $latest = $this->latestSession($recordId);
        return [
            'count' => (int)($aggregate['session_count'] ?? 0),
            'latest_session_date' => $aggregate['latest_session_date'] ?? null,
            'summary' => $latest ? $this->summarizeSession($latest) : null,
        ];
    }

    private function latestSession(int $recordId): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT ps.*, CONCAT(u.first_name, " ", u.last_name) AS recorded_by_name
            FROM physiotherapy_sessions ps
            LEFT JOIN users u ON u.id = ps.recorded_by
            WHERE ps.physiotherapy_record_id = :id
            ORDER BY ps.session_date DESC, ps.id DESC
            LIMIT 1
        ');
        $stmt->execute([':id' => $recordId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function summarizeRecord(array $row): string
    {
        $problem = trim((string)($row['presenting_problem'] ?? ''));
        if ($problem === '') {
            return 'Physiotherapy record.';
        }

        return $this->textLength($problem) > 180
            ? (function_exists('mb_substr') ? mb_substr($problem, 0, 177) : substr($problem, 0, 177)) . '...'
            : $problem;
    }

    private function summarizeSession(array $row): string
    {
        $treatment = trim((string)($row['treatment_given'] ?? ''));
        if ($treatment === '') {
            return 'Physiotherapy session.';
        }

        return $this->textLength($treatment) > 180
            ? (function_exists('mb_substr') ? mb_substr($treatment, 0, 177) : substr($treatment, 0, 177)) . '...'
            : $treatment;
    }

    private function normalizeSource(string $source): string
    {
        return in_array($source, ['Direct', 'Clinical'], true) ? $source : 'Clinical';
    }

    private function normalizeWorklistStatus(string $status): string
    {
        return in_array($status, ['Active', 'Completed', 'Cancelled', 'All'], true) ? $status : '';
    }

    private function recordPayload(array $data, ?array $record = null): array
    {
        $referralReason = $this->nullableText($data['referral_reason'] ?? ($record['referral_reason'] ?? null));
        $presentingProblem = $this->requiredText($data['presenting_problem'] ?? ($record['presenting_problem'] ?? ''));
        $assessment = $this->requiredText($data['assessment'] ?? ($record['assessment'] ?? ''));
        $functionalLimitations = $this->nullableText($data['functional_limitations'] ?? ($record['functional_limitations'] ?? null));
        $treatmentPlan = $this->requiredText($data['treatment_plan'] ?? ($record['treatment_plan'] ?? ''));
        $goals = $this->nullableText($data['goals'] ?? ($record['goals'] ?? null));
        $precautions = $this->nullableText($data['precautions'] ?? ($record['precautions'] ?? null));
        $errors = [];

        foreach ([
            $presentingProblem,
            $assessment,
            $functionalLimitations,
            $treatmentPlan,
            $goals,
            $precautions,
        ] as $text) {
            if ($text !== null && $this->textLength($text) > 10000) {
                $errors[] = 'Physiotherapy text is too long.';
                break;
            }
        }

        return [
            'referral_reason' => $referralReason,
            'presenting_problem' => $presentingProblem,
            'assessment' => $assessment,
            'functional_limitations' => $functionalLimitations,
            'treatment_plan' => $treatmentPlan,
            'goals' => $goals,
            'precautions' => $precautions,
            'errors' => $errors,
        ];
    }

    private function lockVisit(int $visitId): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM visits WHERE id = :id FOR UPDATE');
        $statement->execute([':id' => $visitId]);
        $visit = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$visit) {
            throw new RuntimeException('Encounter not found.');
        }
        return $visit;
    }

    private function lockRecord(int $recordId): ?array
    {
        $statement = $this->pdo->prepare($this->baseRecordSelect() . ' WHERE pr.id = :id LIMIT 1 FOR UPDATE');
        $statement->execute([':id' => $recordId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function lockSession(int $sessionId): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM physiotherapy_sessions WHERE id = :id FOR UPDATE');
        $statement->execute([':id' => $sessionId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function resolvePhysiotherapyDepartmentId(): ?int
    {
        $statement = $this->pdo->prepare("
            SELECT id
            FROM departments
            WHERE department_name IN ('Physiotherapy', 'Physio', 'Rehabilitation')
            ORDER BY CASE
                WHEN department_name = 'Physiotherapy' THEN 0
                WHEN department_name = 'Physio' THEN 1
                ELSE 2
            END, id ASC
            LIMIT 1
        ");
        $statement->execute();
        $id = $statement->fetchColumn();
        return $id !== false ? (int)$id : null;
    }

    private function isPhysiotherapyEncounter(array $visit): bool
    {
        $departmentId = $this->resolvePhysiotherapyDepartmentId();
        if ($departmentId !== null && (int)($visit['current_department_id'] ?? 0) === $departmentId) {
            return true;
        }

        return in_array((string)($visit['department_name'] ?? ''), ['Physiotherapy', 'Physio', 'Rehabilitation'], true);
    }

    private function validateSourcePermission(array $visit, array $user, string $source): array
    {
        $errors = [];

        if ($source === 'Clinical') {
            if (!$this->permissionService->canCreatePhysiotherapy($visit, $user, 'Clinical')) {
                $errors[] = 'You cannot create a clinical physiotherapy referral.';
            }
        } elseif ($source === 'Direct') {
            if (!$this->permissionService->canCreatePhysiotherapy($visit, $user, 'Direct')) {
                $errors[] = 'You cannot create a direct physiotherapy record.';
            }

            if (!$this->isPhysiotherapyEncounter($visit)) {
                $errors[] = 'Direct physiotherapy records require an active Physiotherapy encounter.';
            }
        } else {
            $errors[] = 'Invalid physiotherapy record source.';
        }

        return $errors;
    }

    private function audit(string $action, array $visit, array $user, string $description): bool
    {
        return $this->auditService->logPatient(
            (int)($user['id'] ?? 0),
            (int)$visit['patient_id'],
            (int)$visit['id'],
            'Physiotherapy',
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

    private function baseRecordSelect(): string
    {
        return '
            SELECT pr.*,
                   p.hospital_number,
                   CONCAT(p.first_name, " ", p.last_name) AS patient_name,
                   v.visit_number,
                   v.visit_status,
                   d.department_name,
                   CONCAT(req.first_name, " ", req.last_name) AS created_by_name,
                   CONCAT(upd.first_name, " ", upd.last_name) AS updated_by_name,
                   CONCAT(comp.first_name, " ", comp.last_name) AS completed_by_name,
                   CONCAT(pf.first_name, " ", pf.last_name) AS physiotherapist_name
            FROM physiotherapy_records pr
            INNER JOIN patients p ON p.id = pr.patient_id
            INNER JOIN visits v ON v.id = pr.visit_id
            LEFT JOIN departments d ON d.id = pr.department_id
            LEFT JOIN users req ON req.id = pr.created_by
            LEFT JOIN users upd ON upd.id = pr.updated_by
            LEFT JOIN users comp ON comp.id = pr.completed_by
            LEFT JOIN users pf ON pf.id = pr.physiotherapist_id
        ';
    }

    private function requiredText(mixed $value): string
    {
        return trim((string)$value);
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

    private function parseSessionDate(mixed $value, ?string $fallback = null): ?DateTimeImmutable
    {
        $raw = trim((string)($value ?? ''));
        if ($raw === '' && $fallback !== null && $fallback !== '') {
            $raw = $fallback;
        }

        if ($raw === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($raw);
        } catch (Throwable) {
            return null;
        }
    }

    private function failure(array $errors): array
    {
        return [
            'success' => false,
            'errors' => array_values(array_filter($errors, static fn ($error): bool => trim((string)$error) !== '')),
        ];
    }

    private function rollback(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }
}
