<?php

declare(strict_types=1);

require_once __DIR__ . '/AuditService.php';
require_once __DIR__ . '/EncounterEventService.php';
require_once __DIR__ . '/PermissionService.php';

class ConsultationService
{
    public function __construct(
        private PDO $pdo,
        private ?AuditService $auditService = null,
        private ?EncounterEventService $eventService = null,
        private ?PermissionService $permissionService = null
    ) {
        $this->auditService ??= new AuditService($pdo);
        $this->eventService ??= new EncounterEventService($pdo);
        $this->permissionService ??= new PermissionService($pdo);
    }

    public function create(array $data, array $user): array
    {
        try {
            $this->pdo->beginTransaction();
            $visit = $this->lockVisit((int)($data['visit_id'] ?? 0));
            $errors = $this->validateConsultation($data, $visit, $user, 'create_consultation');

            if ($errors !== []) {
                $this->pdo->rollBack();
                return $this->failure($errors);
            }

            if ($this->getByVisit((int)$visit['id']) !== null) {
                $this->pdo->rollBack();
                return $this->failure(['A consultation already exists for this encounter.']);
            }

            $doctorId = $this->clinicalDoctorId($visit, $user);
            if ($doctorId <= 0) {
                $this->pdo->rollBack();
                return $this->failure(['Assign a clinical doctor before creating the consultation.']);
            }

            $stmt = $this->pdo->prepare("
                INSERT INTO consultations (
                    visit_id, patient_id, doctor_id, department_id,
                    presenting_complaint, history_of_presenting_complaint,
                    examination_findings, assessment, diagnosis, treatment_plan,
                    advice, follow_up, referral_notes, status, created_by
                ) VALUES (
                    :visit_id, :patient_id, :doctor_id, :department_id,
                    :presenting_complaint, :history_of_presenting_complaint,
                    :examination_findings, :assessment, :diagnosis, :treatment_plan,
                    :advice, :follow_up, :referral_notes, 'Draft', :created_by
                )
            ");
            $stmt->execute($this->consultationParameters($data, $visit, $doctorId, (int)$user['id']));
            $consultationId = (int)$this->pdo->lastInsertId();

            if (!$this->audit('CONSULTATION_CREATED', $visit, $user, 'Created consultation #' . $consultationId . '.')) {
                throw new RuntimeException('Unable to audit consultation creation.');
            }
            if (!$this->event((int)$visit['id'], 'CONSULTATION_STARTED', 'Consultation Started', 'Consultation record opened.', $visit, $user)) {
                throw new RuntimeException('Unable to record consultation event.');
            }

            $this->pdo->commit();
            return ['success' => true, 'consultation_id' => $consultationId, 'errors' => []];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to create consultation.']);
        }
    }

    public function getById(int $consultationId): ?array
    {
        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE c.id = :id LIMIT 1');
        $stmt->execute([':id' => $consultationId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getByVisit(int $visitId): ?array
    {
        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE c.visit_id = :visit_id LIMIT 1');
        $stmt->execute([':visit_id' => $visitId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function update(int $consultationId, array $data, array $user): array
    {
        try {
            $this->pdo->beginTransaction();
            $consultation = $this->lockConsultation($consultationId);
            if (!$consultation) {
                $this->pdo->rollBack();
                return $this->failure(['Consultation not found.']);
            }

            $visit = $this->lockVisit((int)$consultation['visit_id']);
            $errors = $this->validateConsultation($data, $visit, $user, 'edit_consultation');
            if ((string)$consultation['status'] !== 'Draft') {
                $errors[] = 'Completed consultations are view-only.';
            }

            if ($errors !== []) {
                $this->pdo->rollBack();
                return $this->failure($errors);
            }

            $stmt = $this->pdo->prepare('
                UPDATE consultations
                SET presenting_complaint = :presenting_complaint,
                    history_of_presenting_complaint = :history_of_presenting_complaint,
                    examination_findings = :examination_findings,
                    assessment = :assessment,
                    diagnosis = :diagnosis,
                    treatment_plan = :treatment_plan,
                    advice = :advice,
                    follow_up = :follow_up,
                    referral_notes = :referral_notes,
                    updated_by = :updated_by
                WHERE id = :id
            ');
            $params = $this->textParameters($data);
            $params[':updated_by'] = (int)$user['id'];
            $params[':id'] = $consultationId;
            $stmt->execute($params);

            if (!$this->audit('CONSULTATION_UPDATED', $visit, $user, 'Updated consultation #' . $consultationId . '.')) {
                throw new RuntimeException('Unable to audit consultation update.');
            }

            $this->pdo->commit();
            return ['success' => true, 'consultation_id' => $consultationId, 'errors' => []];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to update consultation.']);
        }
    }

    public function complete(int $consultationId, array $user): array
    {
        try {
            $this->pdo->beginTransaction();
            $consultation = $this->lockConsultation($consultationId);
            if (!$consultation) {
                $this->pdo->rollBack();
                return $this->failure(['Consultation not found.']);
            }

            $visit = $this->lockVisit((int)$consultation['visit_id']);
            $errors = $this->validateVisitForMutation($visit, $user, 'complete_consultation');
            if ((string)$consultation['status'] !== 'Draft') {
                $errors[] = 'Only draft consultations can be completed.';
            }

            if ($errors !== []) {
                $this->pdo->rollBack();
                return $this->failure($errors);
            }

            $stmt = $this->pdo->prepare("
                UPDATE consultations
                SET status = 'Completed',
                    completed_by = :completed_by,
                    completed_at = NOW(),
                    updated_by = :updated_by
                WHERE id = :id
            ");
            $stmt->execute([
                ':completed_by' => (int)$user['id'],
                ':updated_by' => (int)$user['id'],
                ':id' => $consultationId
            ]);

            if (!$this->audit('CONSULTATION_COMPLETED', $visit, $user, 'Completed consultation #' . $consultationId . '.')) {
                throw new RuntimeException('Unable to audit consultation completion.');
            }
            if (!$this->event((int)$visit['id'], 'CONSULTATION_COMPLETED', 'Consultation Completed', 'Consultation completed.', $visit, $user)) {
                throw new RuntimeException('Unable to record consultation completion event.');
            }

            $this->pdo->commit();
            return ['success' => true, 'consultation_id' => $consultationId, 'errors' => []];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to complete consultation.']);
        }
    }

    public function listByPatient(int $patientId): array
    {
        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE c.patient_id = :patient_id ORDER BY c.created_at DESC, c.id DESC');
        $stmt->execute([':patient_id' => $patientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function validateConsultation(array $data, array $visit, array $user, string $permission): array
    {
        $errors = $this->validateVisitForMutation($visit, $user, $permission);
        foreach ([
            'presenting_complaint' => 'Presenting complaint',
            'history_of_presenting_complaint' => 'History of presenting complaint',
            'examination_findings' => 'Examination findings',
            'assessment' => 'Assessment',
            'diagnosis' => 'Diagnosis',
            'treatment_plan' => 'Treatment plan'
        ] as $field => $label) {
            if (trim((string)($data[$field] ?? '')) === '') {
                $errors[] = $label . ' is required.';
            }
        }
        return $errors;
    }

    private function validateVisitForMutation(array $visit, array $user, string $permission): array
    {
        $errors = [];
        if (!$this->permissionService->hasPermission($permission, $user)) {
            $errors[] = 'You are not allowed to perform this consultation action.';
        }
        if (!$this->permissionService->canViewEncounter($visit, $user)) {
            $errors[] = 'You cannot access this encounter.';
        }
        if (in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true)) {
            $errors[] = 'Completed or cancelled encounters are read-only.';
        }
        return $errors;
    }

    private function clinicalDoctorId(array $visit, array $user): int
    {
        $assignedDoctor = (int)($visit['attending_doctor_id'] ?? 0);
        if ($assignedDoctor > 0) {
            return $assignedDoctor;
        }
        return (string)($user['role_name'] ?? '') === 'Doctor' ? (int)$user['id'] : 0;
    }

    private function consultationParameters(array $data, array $visit, int $doctorId, int $actorId): array
    {
        return $this->textParameters($data) + [
            ':visit_id' => (int)$visit['id'],
            ':patient_id' => (int)$visit['patient_id'],
            ':doctor_id' => $doctorId,
            ':department_id' => (int)($visit['current_department_id'] ?? 0) ?: null,
            ':created_by' => $actorId
        ];
    }

    private function textParameters(array $data): array
    {
        return [
            ':presenting_complaint' => trim((string)($data['presenting_complaint'] ?? '')),
            ':history_of_presenting_complaint' => trim((string)($data['history_of_presenting_complaint'] ?? '')),
            ':examination_findings' => trim((string)($data['examination_findings'] ?? '')),
            ':assessment' => trim((string)($data['assessment'] ?? '')),
            ':diagnosis' => trim((string)($data['diagnosis'] ?? '')),
            ':treatment_plan' => trim((string)($data['treatment_plan'] ?? '')),
            ':advice' => $this->nullableText($data['advice'] ?? null),
            ':follow_up' => $this->nullableText($data['follow_up'] ?? null),
            ':referral_notes' => $this->nullableText($data['referral_notes'] ?? null)
        ];
    }

    private function nullableText(mixed $value): ?string
    {
        $text = trim((string)$value);
        return $text === '' ? null : $text;
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

    private function lockConsultation(int $consultationId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM consultations WHERE id = :id FOR UPDATE');
        $stmt->execute([':id' => $consultationId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function audit(string $action, array $visit, array $user, string $description): bool
    {
        return $this->auditService->logPatient(
            (int)($user['id'] ?? 0),
            (int)$visit['patient_id'],
            (int)$visit['id'],
            'Consultation',
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
            SELECT c.*,
                   v.visit_number,
                   v.visit_status,
                   p.hospital_number,
                   p.first_name,
                   p.last_name,
                   d.department_name,
                   CONCAT(doc.first_name, " ", doc.last_name) AS doctor_name,
                   CONCAT(creator.first_name, " ", creator.last_name) AS created_by_name,
                   CONCAT(updater.first_name, " ", updater.last_name) AS updated_by_name,
                   CONCAT(completer.first_name, " ", completer.last_name) AS completed_by_name
            FROM consultations c
            INNER JOIN visits v ON v.id = c.visit_id
            INNER JOIN patients p ON p.id = c.patient_id
            LEFT JOIN departments d ON d.id = c.department_id
            LEFT JOIN users doc ON doc.id = c.doctor_id
            LEFT JOIN users creator ON creator.id = c.created_by
            LEFT JOIN users updater ON updater.id = c.updated_by
            LEFT JOIN users completer ON completer.id = c.completed_by
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
