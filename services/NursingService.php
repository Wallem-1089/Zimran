<?php

declare(strict_types=1);

require_once __DIR__ . '/AuditService.php';
require_once __DIR__ . '/EncounterEventService.php';
require_once __DIR__ . '/PermissionService.php';

class NursingService
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
            $errors = $this->validateVisitForMutation($visit, $user, 'create_nursing');

            if (isset($data['patient_id']) && (int)$data['patient_id'] !== (int)$visit['patient_id']) {
                $errors[] = 'Patient and encounter do not match.';
            }

            if ($this->getByVisit((int)$visit['id']) !== null) {
                $errors[] = 'A nursing assessment already exists for this encounter.';
            }

            $payload = $this->preparePayload($data);
            $errors = array_merge($errors, $payload['errors']);

            if ($errors !== []) {
                $this->rollback();
                return $this->failure($errors);
            }

            $stmt = $this->pdo->prepare('
                INSERT INTO nursing_assessments (
                    visit_id, patient_id, nurse_id, department_id,
                    general_condition, nursing_observation, pain_assessment, mobility,
                    nutrition, elimination, skin_assessment, fall_risk,
                    nursing_interventions, patient_response, handover_notes,
                    additional_notes, status, created_by
                ) VALUES (
                    :visit_id, :patient_id, :nurse_id, :department_id,
                    :general_condition, :nursing_observation, :pain_assessment, :mobility,
                    :nutrition, :elimination, :skin_assessment, :fall_risk,
                    :nursing_interventions, :patient_response, :handover_notes,
                    :additional_notes, \'Draft\', :created_by
                )
            ');
            $stmt->execute($this->parameters($visit, $user, $payload, null) + [
                ':created_by' => (int)$user['id']
            ]);
            $assessmentId = (int)$this->pdo->lastInsertId();

            if (!$this->audit('NURSING_ASSESSMENT_CREATED', $visit, $user, 'Created nursing assessment #' . $assessmentId . '.')) {
                throw new RuntimeException('Unable to audit nursing assessment creation.');
            }

            if (!$this->event(
                (int)$visit['id'],
                'NURSING_ASSESSMENT_STARTED',
                'Nursing Assessment Started',
                'Nursing assessment created.',
                $visit,
                $user
            )) {
                throw new RuntimeException('Unable to record nursing assessment start event.');
            }

            $this->pdo->commit();

            return [
                'success' => true,
                'nursing_assessment_id' => $assessmentId,
                'visit_id' => (int)$visit['id'],
                'patient_id' => (int)$visit['patient_id'],
                'errors' => []
            ];
        } catch (Throwable $throwable) {
            $this->rollback();
            return $this->failure(['Unable to save nursing assessment.']);
        }
    }

    public function getById(int $assessmentId, ?array $user = null): ?array
    {
        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE na.id = :id LIMIT 1');
        $stmt->execute([':id' => $assessmentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        if ($user !== null && !$this->canViewRow($row, $user)) {
            return null;
        }

        return $this->decorateRow($row);
    }

    public function getByVisit(int $visitId, ?array $user = null): ?array
    {
        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE na.visit_id = :visit_id LIMIT 1');
        $stmt->execute([':visit_id' => $visitId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        if ($user !== null && !$this->canViewRow($row, $user)) {
            return null;
        }

        return $this->decorateRow($row);
    }

    public function update(int $assessmentId, array $data, array $user): array
    {
        try {
            $this->pdo->beginTransaction();

            $assessment = $this->lockAssessment($assessmentId);
            if (!$assessment) {
                $this->rollback();
                return $this->failure(['Nursing assessment not found.']);
            }

            $visit = $this->lockVisit((int)$assessment['visit_id']);
            $errors = $this->validateVisitForMutation($visit, $user, 'edit_nursing');
            if ((string)$assessment['status'] !== 'Draft') {
                $errors[] = 'Completed nursing assessments are view-only.';
            }
            if (isset($data['patient_id']) && (int)$data['patient_id'] !== (int)$visit['patient_id']) {
                $errors[] = 'Patient and encounter do not match.';
            }

            $payload = $this->preparePayload($data, $assessment);
            $errors = array_merge($errors, $payload['errors']);

            if ($errors !== []) {
                $this->rollback();
                return $this->failure($errors);
            }

            $stmt = $this->pdo->prepare('
                UPDATE nursing_assessments
                SET nurse_id = :nurse_id,
                    department_id = :department_id,
                    general_condition = :general_condition,
                    nursing_observation = :nursing_observation,
                    pain_assessment = :pain_assessment,
                    mobility = :mobility,
                    nutrition = :nutrition,
                    elimination = :elimination,
                    skin_assessment = :skin_assessment,
                    fall_risk = :fall_risk,
                    nursing_interventions = :nursing_interventions,
                    patient_response = :patient_response,
                    handover_notes = :handover_notes,
                    additional_notes = :additional_notes,
                    updated_by = :updated_by
                WHERE id = :id
            ');
            $stmt->execute($this->updateParameters($visit, $user, $payload, $assessment) + [
                ':updated_by' => (int)$user['id'],
                ':id' => $assessmentId
            ]);

            if (!$this->audit('NURSING_ASSESSMENT_UPDATED', $visit, $user, 'Updated nursing assessment #' . $assessmentId . '.')) {
                throw new RuntimeException('Unable to audit nursing assessment update.');
            }

            $this->pdo->commit();

            return [
                'success' => true,
                'nursing_assessment_id' => $assessmentId,
                'visit_id' => (int)$visit['id'],
                'patient_id' => (int)$visit['patient_id'],
                'errors' => []
            ];
        } catch (Throwable $throwable) {
            $this->rollback();
            return $this->failure(['Unable to update nursing assessment.']);
        }
    }

    public function complete(int $assessmentId, array $user): array
    {
        try {
            $this->pdo->beginTransaction();

            $assessment = $this->lockAssessment($assessmentId);
            if (!$assessment) {
                $this->rollback();
                return $this->failure(['Nursing assessment not found.']);
            }

            $visit = $this->lockVisit((int)$assessment['visit_id']);
            $errors = $this->validateVisitForMutation($visit, $user, 'complete_nursing');
            if ((string)$assessment['status'] !== 'Draft') {
                $errors[] = 'Only draft nursing assessments can be completed.';
            }
            if (!$this->hasMeaningfulContent($assessment)) {
                $errors[] = 'Add assessment details before completing the nursing record.';
            }

            if ($errors !== []) {
                $this->rollback();
                return $this->failure($errors);
            }

            $nurseId = $this->resolveNurseId($assessment, $user);
            $stmt = $this->pdo->prepare('
                UPDATE nursing_assessments
                SET status = \'Completed\',
                    nurse_id = :nurse_id,
                    completed_by = :completed_by,
                    completed_at = NOW(),
                    updated_by = :updated_by
                WHERE id = :id
            ');
            $stmt->execute([
                ':nurse_id' => $nurseId,
                ':completed_by' => (int)$user['id'],
                ':updated_by' => (int)$user['id'],
                ':id' => $assessmentId
            ]);

            if (!$this->audit('NURSING_ASSESSMENT_COMPLETED', $visit, $user, 'Completed nursing assessment #' . $assessmentId . '.')) {
                throw new RuntimeException('Unable to audit nursing assessment completion.');
            }

            if (!$this->event(
                (int)$visit['id'],
                'NURSING_ASSESSMENT_COMPLETED',
                'Nursing Assessment Completed',
                'Nursing assessment completed.',
                $visit,
                $user
            )) {
                throw new RuntimeException('Unable to record nursing assessment completion event.');
            }

            $this->pdo->commit();

            return [
                'success' => true,
                'nursing_assessment_id' => $assessmentId,
                'visit_id' => (int)$visit['id'],
                'patient_id' => (int)$visit['patient_id'],
                'errors' => []
            ];
        } catch (Throwable $throwable) {
            $this->rollback();
            return $this->failure(['Unable to complete nursing assessment.']);
        }
    }

    public function listByPatient(int $patientId, ?array $user = null): array
    {
        if ($patientId <= 0) {
            return [];
        }

        if ($user !== null && !$this->permissionService->canViewNursing($patientId, $user)) {
            return [];
        }

        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE na.patient_id = :patient_id ORDER BY na.created_at DESC, na.id DESC');
        $stmt->execute([':patient_id' => $patientId]);
        return array_map([$this, 'decorateRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function listByVisit(int $visitId, ?array $user = null): array
    {
        $visit = $this->visitById($visitId);
        if (!$visit) {
            return [];
        }

        if ($user !== null && !$this->canViewRow($visit, $user)) {
            return [];
        }

        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE na.visit_id = :visit_id ORDER BY na.created_at DESC, na.id DESC');
        $stmt->execute([':visit_id' => $visitId]);
        return array_map([$this, 'decorateRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getLatestByVisit(int $visitId, ?array $user = null): ?array
    {
        return $this->getByVisit($visitId, $user);
    }

    private function validateVisitForMutation(array $visit, array $user, string $permission): array
    {
        $errors = [];
        if (!$this->permissionService->hasPermission($permission, $user)) {
            $errors[] = 'You are not allowed to perform this nursing action.';
        }
        if (!$this->permissionService->canViewEncounter($visit, $user)) {
            $errors[] = 'You cannot access this encounter.';
        }
        if (!$this->permissionService->isAdministrator($user) && in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true)) {
            $errors[] = 'Completed or cancelled encounters are read-only.';
        }
        if (!$this->permissionService->isAdministrator($user) && (string)($user['role_name'] ?? '') !== 'Nurse') {
            $errors[] = 'Only nurses may perform this nursing action.';
        }
        return $errors;
    }

    private function preparePayload(array $data, ?array $existing = null): array
    {
        $errors = [];
        $payload = [];

        foreach ($this->fieldMap() as $field => $label) {
            $value = $this->nullableText($data[$field] ?? null);
            if ($value !== null && $this->textLength($value) > 5000) {
                $errors[] = $label . ' is too long.';
            }
            $payload[$field] = $value ?? ($existing[$field] ?? null);
        }

        if ($this->hasAnyText($payload) === false && $existing === null) {
            // Drafts may be created with no narrative yet, but keep the payload valid.
        }

        return ['errors' => $errors, 'payload' => $payload];
    }

    private function hasMeaningfulContent(array $record): bool
    {
        foreach ($this->fieldMap() as $field => $_label) {
            if ($this->nullableText($record[$field] ?? null) !== null) {
                return true;
            }
        }
        return false;
    }

    private function hasAnyText(array $payload): bool
    {
        foreach ($payload as $value) {
            if ($value !== null && $value !== '') {
                return true;
            }
        }
        return false;
    }

    private function resolveNurseId(array $record, array $user): ?int
    {
        if (!empty($record['nurse_id'])) {
            return (int)$record['nurse_id'];
        }

        return (string)($user['role_name'] ?? '') === 'Nurse'
            ? (int)($user['id'] ?? 0)
            : null;
    }

    private function parameters(array $visit, array $user, array $payload, ?array $existing): array
    {
        return [
            ':visit_id' => (int)$visit['id'],
            ':patient_id' => (int)$visit['patient_id'],
            ':nurse_id' => $this->resolveNurseId($existing ?? [], $user),
            ':department_id' => $this->recordingDepartmentId($visit, $user),
            ':general_condition' => $payload['payload']['general_condition'],
            ':nursing_observation' => $payload['payload']['nursing_observation'],
            ':pain_assessment' => $payload['payload']['pain_assessment'],
            ':mobility' => $payload['payload']['mobility'],
            ':nutrition' => $payload['payload']['nutrition'],
            ':elimination' => $payload['payload']['elimination'],
            ':skin_assessment' => $payload['payload']['skin_assessment'],
            ':fall_risk' => $payload['payload']['fall_risk'],
            ':nursing_interventions' => $payload['payload']['nursing_interventions'],
            ':patient_response' => $payload['payload']['patient_response'],
            ':handover_notes' => $payload['payload']['handover_notes'],
            ':additional_notes' => $payload['payload']['additional_notes'],
        ];
    }

    private function updateParameters(array $visit, array $user, array $payload, ?array $existing): array
    {
        return [
            ':nurse_id' => $this->resolveNurseId($existing ?? [], $user),
            ':department_id' => $this->recordingDepartmentId($visit, $user),
            ':general_condition' => $payload['payload']['general_condition'],
            ':nursing_observation' => $payload['payload']['nursing_observation'],
            ':pain_assessment' => $payload['payload']['pain_assessment'],
            ':mobility' => $payload['payload']['mobility'],
            ':nutrition' => $payload['payload']['nutrition'],
            ':elimination' => $payload['payload']['elimination'],
            ':skin_assessment' => $payload['payload']['skin_assessment'],
            ':fall_risk' => $payload['payload']['fall_risk'],
            ':nursing_interventions' => $payload['payload']['nursing_interventions'],
            ':patient_response' => $payload['payload']['patient_response'],
            ':handover_notes' => $payload['payload']['handover_notes'],
            ':additional_notes' => $payload['payload']['additional_notes'],
        ];
    }

    private function fieldMap(): array
    {
        return [
            'general_condition' => 'General condition',
            'nursing_observation' => 'Nursing observation',
            'pain_assessment' => 'Pain assessment',
            'mobility' => 'Mobility',
            'nutrition' => 'Nutrition',
            'elimination' => 'Elimination',
            'skin_assessment' => 'Skin assessment',
            'fall_risk' => 'Fall risk',
            'nursing_interventions' => 'Nursing interventions',
            'patient_response' => 'Patient response',
            'handover_notes' => 'Handover notes',
            'additional_notes' => 'Additional notes'
        ];
    }

    private function decorateRow(array $row): array
    {
        $row['summary'] = $this->summarize($row);
        return $row;
    }

    private function summarize(array $row): string
    {
        foreach (['general_condition', 'nursing_observation', 'nursing_interventions', 'patient_response', 'handover_notes', 'additional_notes'] as $field) {
            $text = trim((string)($row[$field] ?? ''));
            if ($text !== '') {
                return mb_strlen($text) > 180 ? mb_substr($text, 0, 177) . '...' : $text;
            }
        }

        return 'Draft nursing assessment.';
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

    private function lockAssessment(int $assessmentId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM nursing_assessments WHERE id = :id FOR UPDATE');
        $stmt->execute([':id' => $assessmentId]);
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

    private function baseSelect(): string
    {
        return '
            SELECT na.*,
                   v.visit_number,
                   v.visit_status,
                   p.hospital_number,
                   p.first_name,
                   p.last_name,
                   d.department_name,
                   CONCAT(nurse.first_name, " ", nurse.last_name) AS nurse_name,
                   CONCAT(created.first_name, " ", created.last_name) AS created_by_name,
                   CONCAT(updated.first_name, " ", updated.last_name) AS updated_by_name,
                   CONCAT(completed.first_name, " ", completed.last_name) AS completed_by_name
            FROM nursing_assessments na
            INNER JOIN visits v ON v.id = na.visit_id
            INNER JOIN patients p ON p.id = na.patient_id
            LEFT JOIN departments d ON d.id = na.department_id
            LEFT JOIN users nurse ON nurse.id = na.nurse_id
            LEFT JOIN users created ON created.id = na.created_by
            LEFT JOIN users updated ON updated.id = na.updated_by
            LEFT JOIN users completed ON completed.id = na.completed_by
        ';
    }
}
