<?php

declare(strict_types=1);

require_once __DIR__ . '/AuditService.php';
require_once __DIR__ . '/EncounterEventService.php';
require_once __DIR__ . '/PermissionService.php';
require_once __DIR__ . '/RecordAmendmentService.php';
require_once __DIR__ . '/SettingsService.php';

class ClinicalNoteService
{
    private const NOTE_TYPES = [
        'general_clinical_note', 'medical_records_note', 'progress_note',
        'care_coordination_note', 'patient_communication_note',
        'administrative_clinical_note', 'external_record_summary', 'other'
    ];
    private const CONFIDENTIALITY = ['Standard', 'Restricted', 'Confidential', 'Highly Confidential'];

    private PermissionService $permissions;
    private SettingsService $settings;
    private AuditService $audit;
    private EncounterEventService $events;
    private RecordAmendmentService $amendments;

    public function __construct(
        private PDO $pdo,
        ?PermissionService $permissions = null,
        ?SettingsService $settings = null,
        ?AuditService $audit = null,
        ?EncounterEventService $events = null,
        ?RecordAmendmentService $amendments = null
    ) {
        $this->settings = $settings ?? new SettingsService($pdo);
        $this->permissions = $permissions ?? new PermissionService($pdo, $this->settings);
        $this->audit = $audit ?? new AuditService($pdo);
        $this->events = $events ?? new EncounterEventService($pdo);
        $this->amendments = $amendments ?? new RecordAmendmentService($pdo);
    }

    public function createDraft(array $data, array $user): array
    {
        $data = $this->prepare($data);
        $actorId = (int)($user['id'] ?? 0);
        $errors = $this->validateDraft($data, $actorId);
        if ($errors !== []) {
            return $this->failure($errors);
        }
        if (!$this->permissions->canCreateClinicalNote(
            $data['patient_id'],
            $data['visit_id'] !== null,
            $data['note_type'],
            $user
        )) {
            $this->auditDenied($actorId, $data['patient_id'], 'CLINICAL_NOTE_ACCESS_DENIED');
            return $this->forbidden('You do not have permission to create this Clinical Note.');
        }
        $owns = !$this->pdo->inTransaction();
        try {
            if ($owns) {
                $this->pdo->beginTransaction();
            }
            $this->lockPatient($data['patient_id']);
            $visit = $this->lockVisitForPatient($data['visit_id'], $data['patient_id'], $user, true);
            $stmt = $this->pdo->prepare('INSERT INTO clinical_notes
                (patient_id,visit_id,note_type,title,department_id,author_id,confidentiality_level,note_status,current_version,version)
                VALUES (:patient_id,:visit_id,:note_type,:title,:department_id,:author_id,:confidentiality,\'Draft\',1,1)');
            $departmentId = $this->departmentId($user);
            $stmt->execute([
                ':patient_id' => $data['patient_id'], ':visit_id' => $data['visit_id'],
                ':note_type' => $data['note_type'], ':title' => $data['title'],
                ':department_id' => $departmentId, ':author_id' => $actorId,
                ':confidentiality' => $data['confidentiality_level']
            ]);
            $noteId = (int)$this->pdo->lastInsertId();
            $versionId = $this->insertVersion(
                $noteId, 1, $data['content'], 'Draft', $actorId, $departmentId,
                $data['confidentiality_level'], null, null, null, null
            );
            $this->writeAudit($actorId, $data['patient_id'], $data['visit_id'], $departmentId,
                'CLINICAL_NOTE_CREATED', 'Created Clinical Note draft #' . $noteId . '.');
            if ($owns) {
                $this->pdo->commit();
            }
            return $this->success(['note_id' => $noteId, 'version_id' => $versionId, 'version' => 1]);
        } catch (Throwable $exception) {
            return $this->writeFailure($exception, $owns, 'Unable to create Clinical Note draft.');
        }
    }

    public function updateDraft(
        int $noteId,
        array $data,
        int $expectedVersion,
        array $user
    ): array {
        $actorId = (int)($user['id'] ?? 0);
        $owns = !$this->pdo->inTransaction();
        try {
            if ($owns) {
                $this->pdo->beginTransaction();
            }
            $note = $this->lockNote($noteId);
            if (!$note) {
                throw new RuntimeException('Clinical Note not found.');
            }
            if ($note['note_status'] !== 'Draft') {
                throw new DomainException('Signed or terminal Clinical Notes cannot be edited as drafts.');
            }
            $own = (int)$note['author_id'] === $actorId;
            if (($own && !$this->permissions->canEditOwnNoteDraft((int)$note['patient_id'], $user))
                || (!$own && !$this->permissions->canEditAnyNoteDraft((int)$note['patient_id'], $user))
            ) {
                throw new UnexpectedValueException('FORBIDDEN');
            }
            if ((int)$note['version'] !== $expectedVersion) {
                if ($owns) {
                    $this->pdo->rollBack();
                }
                return $this->conflict('This draft was changed by another user. Reload it before editing.', (int)$note['version']);
            }
            $prepared = $this->prepare([
                'patient_id' => $note['patient_id'], 'visit_id' => $note['visit_id'],
                'note_type' => $data['note_type'] ?? $note['note_type'],
                'title' => $data['title'] ?? $note['title'],
                'content' => $data['content'] ?? '',
                'confidentiality_level' => $data['confidentiality_level'] ?? $note['confidentiality_level']
            ]);
            $errors = $this->validateDraft($prepared, $actorId);
            if ($errors !== []) {
                if ($owns) {
                    $this->pdo->rollBack();
                }
                return $this->failure($errors);
            }
            if (!$this->permissions->canCreateClinicalNote(
                (int)$note['patient_id'], $note['visit_id'] !== null, $prepared['note_type'], $user
            )) {
                throw new UnexpectedValueException('FORBIDDEN');
            }
            $this->lockVisitForPatient($this->nullableInt($note['visit_id']), (int)$note['patient_id'], $user, true);
            $current = $this->currentVersionForUpdate($note);
            $next = $this->nextVersionNumber($noteId);
            $versionId = $this->insertVersion(
                $noteId, $next, $prepared['content'], 'Draft', $actorId,
                $this->departmentId($user), $prepared['confidentiality_level'],
                null, null, null, (int)$current['id']
            );
            $update = $this->pdo->prepare('UPDATE clinical_notes SET note_type=:note_type,title=:title,
                confidentiality_level=:confidentiality,current_version=:current_version,version=version+1
                WHERE id=:id AND version=:expected AND note_status=\'Draft\'');
            $update->execute([
                ':note_type' => $prepared['note_type'], ':title' => $prepared['title'],
                ':confidentiality' => $prepared['confidentiality_level'], ':current_version' => $next,
                ':id' => $noteId, ':expected' => $expectedVersion
            ]);
            $this->assertAffected($update, 'The draft changed concurrently.');
            $this->writeAudit($actorId, (int)$note['patient_id'], $this->nullableInt($note['visit_id']),
                $this->departmentId($user), 'CLINICAL_NOTE_DRAFT_UPDATED', 'Updated Clinical Note draft #' . $noteId . '.');
            if ($owns) {
                $this->pdo->commit();
            }
            return $this->success(['note_id' => $noteId, 'version_id' => $versionId, 'version' => $expectedVersion + 1]);
        } catch (Throwable $exception) {
            return $this->writeFailure($exception, $owns, 'Unable to update Clinical Note draft.');
        }
    }

    public function signNote(int $noteId, int $expectedVersion, array $user, ?int $visitId = null): array
    {
        $actorId = (int)($user['id'] ?? 0);
        $owns = !$this->pdo->inTransaction();
        try {
            if ($owns) {
                $this->pdo->beginTransaction();
            }
            $note = $this->lockNote($noteId);
            if (!$note) {
                throw new RuntimeException('Clinical Note not found.');
            }
            if ($note['note_status'] !== 'Draft') {
                throw new DomainException('Only a draft Clinical Note can be signed.');
            }
            if (!$this->permissions->canSignClinicalNotes((int)$note['patient_id'], $user)) {
                throw new UnexpectedValueException('FORBIDDEN');
            }
            if ((int)$note['version'] !== $expectedVersion) {
                if ($owns) {
                    $this->pdo->rollBack();
                }
                return $this->conflict('This note changed before signing. Reload it and review the latest draft.', (int)$note['version']);
            }
            if ((int)$note['author_id'] === $actorId
                && !$this->settings->getBoolean('clinical_notes.allow_self_signing', true)
            ) {
                throw new DomainException('Self-signing is disabled by Clinical Note policy.');
            }
            $effectiveVisitId = $visitId ?? $this->nullableInt($note['visit_id']);
            if ($effectiveVisitId !== $this->nullableInt($note['visit_id'])) {
                throw new DomainException('A Clinical Note cannot be moved to another encounter while signing.');
            }
            $visit = $this->lockVisitForPatient($effectiveVisitId, (int)$note['patient_id'], $user, false);
            $current = $this->currentVersionForUpdate($note);
            $next = $this->nextVersionNumber($noteId);
            $versionId = $this->insertVersion(
                $noteId, $next, (string)$current['content'], 'Signed', (int)$current['author_id'],
                $this->nullableInt($current['department_id']), (string)$current['confidentiality_level'],
                $actorId, date('Y-m-d H:i:s'), null, (int)$current['id']
            );
            $update = $this->pdo->prepare('UPDATE clinical_notes SET note_status=\'Signed\',current_version=:current_version,
                confidentiality_level=:confidentiality,signed_by=:signed_by,signed_at=NOW(),locked_at=NOW(),version=version+1
                WHERE id=:id AND version=:expected AND note_status=\'Draft\'');
            $update->execute([
                ':current_version' => $next, ':confidentiality' => $current['confidentiality_level'],
                ':signed_by' => $actorId, ':id' => $noteId, ':expected' => $expectedVersion
            ]);
            $this->assertAffected($update, 'The note changed concurrently.');
            $this->writeAudit($actorId, (int)$note['patient_id'], $effectiveVisitId, $this->departmentId($user),
                'CLINICAL_NOTE_SIGNED', 'Signed and locked Clinical Note #' . $noteId . '.');
            $this->encounterEvent($visit, 'CLINICAL_NOTE_SIGNED', 'Clinical Note signed', 'Clinical Note #' . $noteId . ' was signed.', $actorId, $this->departmentId($user));
            if ($owns) {
                $this->pdo->commit();
            }
            return $this->success(['note_id' => $noteId, 'version_id' => $versionId, 'version' => $expectedVersion + 1]);
        } catch (Throwable $exception) {
            return $this->writeFailure($exception, $owns, 'Unable to sign Clinical Note.');
        }
    }

    public function requestAmendment(
        int $noteId,
        string $content,
        string $reason,
        int $expectedVersion,
        array $user,
        ?int $visitId = null
    ): array {
        $reason = trim($reason);
        $actorId = (int)($user['id'] ?? 0);
        if ($reason === '') {
            return $this->failure(['An amendment reason is required.']);
        }
        $owns = !$this->pdo->inTransaction();
        try {
            if ($owns) {
                $this->pdo->beginTransaction();
            }
            $note = $this->lockNote($noteId);
            if (!$note || !in_array($note['note_status'], ['Signed', 'Amended'], true)) {
                throw new DomainException('Only a signed or amended note can be amended.');
            }
            if (!$this->permissions->canAmendSignedNotes((int)$note['patient_id'], $user)) {
                throw new UnexpectedValueException('FORBIDDEN');
            }
            if ((int)$note['version'] !== $expectedVersion) {
                if ($owns) {
                    $this->pdo->rollBack();
                }
                return $this->conflict('The signed note changed before this amendment request.', (int)$note['version']);
            }
            $content = $this->validateContent($content);
            $effectiveVisitId = $visitId ?? $this->nullableInt($note['visit_id']);
            if ($effectiveVisitId !== $this->nullableInt($note['visit_id'])) {
                throw new DomainException('Amendment encounter context must match the note.');
            }
            $this->lockVisitForPatient($effectiveVisitId, (int)$note['patient_id'], $user, false);
            $pending = $this->pdo->prepare('SELECT id FROM record_amendments
                WHERE record_type=\'ClinicalNote\' AND record_id=:id AND status=\'Requested\' LIMIT 1');
            $pending->execute([':id' => $noteId]);
            if ($pending->fetchColumn()) {
                throw new DomainException('A pending amendment request already exists for this note.');
            }
            $current = $this->currentVersionForUpdate($note);
            if (hash_equals((string)$current['content_checksum'], hash('sha256', $content))) {
                throw new DomainException('The proposed amendment does not change the note content.');
            }
            $proposalNumber = $this->nextVersionNumber($noteId);
            $proposalId = $this->insertVersion(
                $noteId, $proposalNumber, $content, 'Amendment Proposal', $actorId,
                $this->departmentId($user), (string)$note['confidentiality_level'],
                null, null, $reason, (int)$current['id']
            );
            $request = $this->amendments->createRequest([
                'patient_id' => (int)$note['patient_id'], 'visit_id' => $effectiveVisitId,
                'record_type' => 'ClinicalNote', 'record_id' => $noteId,
                'proposed_changes' => [
                    'proposal_version_id' => $proposalId,
                    'expected_note_version' => $expectedVersion,
                    'expected_current_version' => (int)$note['current_version']
                ],
                'reason' => $reason, 'requested_by' => $actorId
            ]);
            if (!($request['success'] ?? false)) {
                throw new RuntimeException('Unable to create amendment request.');
            }
            $amendmentId = (int)$request['amendment_id'];
            $this->writeAudit($actorId, (int)$note['patient_id'], $effectiveVisitId, $this->departmentId($user),
                'CLINICAL_NOTE_AMENDMENT_REQUESTED', 'Requested amendment #' . $amendmentId . ' for Clinical Note #' . $noteId . '.');
            if ($owns) {
                $this->pdo->commit();
            }
            return $this->success(['note_id' => $noteId, 'amendment_id' => $amendmentId, 'proposal_version_id' => $proposalId]);
        } catch (Throwable $exception) {
            return $this->writeFailure($exception, $owns, 'Unable to request Clinical Note amendment.');
        }
    }

    public function amendNote(
        int $noteId,
        string $content,
        string $reason,
        int $expectedVersion,
        array $user,
        ?int $visitId = null
    ): array {
        if ($this->settings->getBoolean('clinical_notes.amendment_approval_required', true)) {
            return $this->requestAmendment($noteId, $content, $reason, $expectedVersion, $user, $visitId);
        }
        return $this->applyDirectAmendment($noteId, $content, $reason, $expectedVersion, $user, $visitId);
    }

    public function approveAmendment(int $amendmentId, array $user): array
    {
        $actorId = (int)($user['id'] ?? 0);
        $owns = !$this->pdo->inTransaction();
        try {
            if ($owns) {
                $this->pdo->beginTransaction();
            }
            $request = $this->amendments->lockRequest($amendmentId);
            if (!$request || $request['record_type'] !== 'ClinicalNote' || $request['status'] !== 'Requested') {
                throw new DomainException('Pending Clinical Note amendment request not found.');
            }
            $note = $this->lockNote((int)$request['record_id']);
            if (!$note || !in_array($note['note_status'], ['Signed', 'Amended'], true)) {
                throw new DomainException('The Clinical Note is no longer amendable.');
            }
            if (!$this->permissions->canApproveNoteAmendments((int)$note['patient_id'], $user)) {
                throw new UnexpectedValueException('FORBIDDEN');
            }
            if ((int)$request['requested_by'] === $actorId
                && !$this->settings->getBoolean('clinical_notes.allow_self_amendment_approval', false)
            ) {
                throw new DomainException('An amendment requester cannot approve the same request.');
            }
            $proposal = json_decode((string)$request['proposed_changes'], true, 512, JSON_THROW_ON_ERROR);
            if ((int)($proposal['expected_note_version'] ?? 0) !== (int)$note['version']
                || (int)($proposal['expected_current_version'] ?? 0) !== (int)$note['current_version']
            ) {
                throw new DomainException('The note changed after the amendment was requested. Review a new proposal.');
            }
            $proposalVersion = $this->getVersionByIdForUpdate((int)($proposal['proposal_version_id'] ?? 0), (int)$note['id']);
            if (!$proposalVersion || $proposalVersion['version_status'] !== 'Amendment Proposal') {
                throw new RuntimeException('The immutable amendment proposal is unavailable.');
            }
            $visit = $this->lockVisitForPatient($this->nullableInt($request['visit_id']), (int)$note['patient_id'], $user, false);
            $this->amendments->approveLocked($amendmentId, $actorId);
            $next = $this->nextVersionNumber((int)$note['id']);
            $versionId = $this->insertVersion(
                (int)$note['id'], $next, (string)$proposalVersion['content'], 'Amended',
                (int)$proposalVersion['author_id'], $this->nullableInt($proposalVersion['department_id']),
                (string)$proposalVersion['confidentiality_level'], $actorId, date('Y-m-d H:i:s'),
                (string)$request['reason'], (int)$note['current_version_id']
            );
            $update = $this->pdo->prepare('UPDATE clinical_notes SET note_status=\'Amended\',current_version=:current_version,
                confidentiality_level=:confidentiality,signed_by=:signed_by,signed_at=NOW(),locked_at=NOW(),amended_at=NOW(),version=version+1
                WHERE id=:id AND version=:expected');
            $update->execute([
                ':current_version' => $next, ':confidentiality' => $proposalVersion['confidentiality_level'],
                ':signed_by' => $actorId, ':id' => $note['id'], ':expected' => $note['version']
            ]);
            $this->assertAffected($update, 'The Clinical Note changed concurrently.');
            $this->amendments->markAppliedLocked($amendmentId, $actorId);
            $this->writeAudit($actorId, (int)$note['patient_id'], $this->nullableInt($request['visit_id']), $this->departmentId($user),
                'CLINICAL_NOTE_AMENDMENT_APPROVED', 'Approved amendment #' . $amendmentId . ' for Clinical Note #' . $note['id'] . '.');
            $this->writeAudit($actorId, (int)$note['patient_id'], $this->nullableInt($request['visit_id']), $this->departmentId($user),
                'CLINICAL_NOTE_AMENDED', 'Applied approved amendment to Clinical Note #' . $note['id'] . '.');
            $this->encounterEvent($visit, 'CLINICAL_NOTE_AMENDED', 'Clinical Note amended', 'Clinical Note #' . $note['id'] . ' was amended.', $actorId, $this->departmentId($user));
            if ($owns) {
                $this->pdo->commit();
            }
            return $this->success(['note_id' => (int)$note['id'], 'amendment_id' => $amendmentId, 'version_id' => $versionId]);
        } catch (Throwable $exception) {
            return $this->writeFailure($exception, $owns, 'Unable to approve Clinical Note amendment.');
        }
    }

    public function rejectAmendment(int $amendmentId, string $reason, array $user): array
    {
        $reason = trim($reason);
        if ($reason === '') {
            return $this->failure(['A rejection reason is required.']);
        }
        $actorId = (int)($user['id'] ?? 0);
        $owns = !$this->pdo->inTransaction();
        try {
            if ($owns) {
                $this->pdo->beginTransaction();
            }
            $request = $this->amendments->lockRequest($amendmentId);
            if (!$request || $request['record_type'] !== 'ClinicalNote' || $request['status'] !== 'Requested') {
                throw new DomainException('Pending Clinical Note amendment request not found.');
            }
            $note = $this->lockNote((int)$request['record_id']);
            if (!$note || !$this->permissions->canApproveNoteAmendments((int)$note['patient_id'], $user)) {
                throw new UnexpectedValueException('FORBIDDEN');
            }
            if ((int)$request['requested_by'] === $actorId
                && !$this->settings->getBoolean('clinical_notes.allow_self_amendment_approval', false)
            ) {
                throw new DomainException('An amendment requester cannot review the same request.');
            }
            $this->amendments->rejectLocked($amendmentId, $actorId, $reason);
            $this->writeAudit($actorId, (int)$note['patient_id'], $this->nullableInt($request['visit_id']), $this->departmentId($user),
                'CLINICAL_NOTE_AMENDMENT_REJECTED', 'Rejected amendment #' . $amendmentId . ' for Clinical Note #' . $note['id'] . '. Reason recorded.');
            if ($owns) {
                $this->pdo->commit();
            }
            return $this->success(['note_id' => (int)$note['id'], 'amendment_id' => $amendmentId]);
        } catch (Throwable $exception) {
            return $this->writeFailure($exception, $owns, 'Unable to reject Clinical Note amendment.');
        }
    }

    public function markNoteEnteredInError(
        int $noteId,
        string $reason,
        int $expectedVersion,
        array $user,
        ?int $visitId = null
    ): array {
        $reason = trim($reason);
        if ($reason === '') {
            return $this->failure(['A reason is required.']);
        }
        $actorId = (int)($user['id'] ?? 0);
        $owns = !$this->pdo->inTransaction();
        try {
            if ($owns) {
                $this->pdo->beginTransaction();
            }
            $note = $this->lockNote($noteId);
            if (!$note || $note['note_status'] === 'Entered-in-error') {
                throw new DomainException('Clinical Note is unavailable or already entered in error.');
            }
            if (!$this->permissions->canMarkNoteEnteredInError((int)$note['patient_id'], $user)) {
                throw new UnexpectedValueException('FORBIDDEN');
            }
            if ((int)$note['version'] !== $expectedVersion) {
                if ($owns) {
                    $this->pdo->rollBack();
                }
                return $this->conflict('The note changed before it could be marked entered in error.', (int)$note['version']);
            }
            $effectiveVisitId = $visitId ?? $this->nullableInt($note['visit_id']);
            if ($effectiveVisitId !== $this->nullableInt($note['visit_id'])) {
                throw new DomainException('Encounter context must match the note.');
            }
            $visit = $this->lockVisitForPatient($effectiveVisitId, (int)$note['patient_id'], $user, false);
            $current = $this->currentVersionForUpdate($note);
            $pending = $this->pendingAmendmentIds($noteId);
            foreach ($pending as $pendingId) {
                $this->amendments->rejectLocked($pendingId, $actorId, 'Clinical Note entered in error before amendment review.');
                $this->writeAudit($actorId, (int)$note['patient_id'], $effectiveVisitId, $this->departmentId($user),
                    'CLINICAL_NOTE_AMENDMENT_REJECTED', 'Rejected superseded amendment #' . $pendingId . ' for Clinical Note #' . $noteId . '.');
            }
            $next = $this->nextVersionNumber($noteId);
            $versionId = $this->insertVersion(
                $noteId, $next, (string)$current['content'], 'Entered-in-error', $actorId,
                $this->departmentId($user), (string)$current['confidentiality_level'],
                null, null, $reason, (int)$current['id']
            );
            $update = $this->pdo->prepare('UPDATE clinical_notes SET note_status=\'Entered-in-error\',current_version=:current_version,
                locked_at=NOW(),version=version+1 WHERE id=:id AND version=:expected');
            $update->execute([':current_version' => $next, ':id' => $noteId, ':expected' => $expectedVersion]);
            $this->assertAffected($update, 'The Clinical Note changed concurrently.');
            $this->writeAudit($actorId, (int)$note['patient_id'], $effectiveVisitId, $this->departmentId($user),
                'CLINICAL_NOTE_ENTERED_IN_ERROR', 'Marked Clinical Note #' . $noteId . ' entered in error.');
            $this->encounterEvent($visit, 'CLINICAL_NOTE_ENTERED_IN_ERROR', 'Clinical Note entered in error', 'Clinical Note #' . $noteId . ' was marked entered in error.', $actorId, $this->departmentId($user));
            if ($owns) {
                $this->pdo->commit();
            }
            return $this->success(['note_id' => $noteId, 'version_id' => $versionId]);
        } catch (Throwable $exception) {
            return $this->writeFailure($exception, $owns, 'Unable to mark Clinical Note entered in error.');
        }
    }

    public function getNoteById(int $noteId): ?array
    {
        $note = $this->getNoteInternal($noteId);
        return $note
            ? ($this->isConfidential($note) ? $this->maskedNote($note) : $this->minimumNote($note))
            : null;
    }

    public function getNoteByIdForUser(int $noteId, array $user, bool $auditAccess = true): array
    {
        $note = $this->getNoteInternal($noteId);
        if (!$note) {
            return $this->failure(['Clinical Note not found.']);
        }
        $authorization = $this->authorizeNote($note, $user);
        if (!($authorization['success'] ?? false)) {
            return $authorization;
        }
        if ($auditAccess && !$this->recordReadAccess($note, $user, false)) {
            return ['success' => false, 'data' => null, 'audit_failed' => true, 'errors' => ['Protected Clinical Note access could not be recorded.']];
        }
        return $this->success(['note' => $note]);
    }

    public function listPatientNotes(
        int $patientId,
        array $user,
        array $filters = [],
        int $page = 1,
        int $pageSize = 25
    ): array {
        if (!$this->permissions->canViewClinicalNotes($patientId, $user)) {
            return $this->forbidden('Clinical Note access is denied.');
        }
        return $this->listNotes($patientId, null, $user, $filters, $page, $pageSize);
    }

    public function listEncounterNotes(
        int $visitId,
        array $user,
        array $filters = [],
        int $page = 1,
        int $pageSize = 25
    ): array {
        $stmt = $this->pdo->prepare('SELECT * FROM visits WHERE id=:id');
        $stmt->execute([':id' => $visitId]);
        $visit = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        $patientId = (int)($visit['patient_id'] ?? 0);
        $recordsAccess = $this->permissions->isAdministrator($user)
            || (string)($user['role_name'] ?? '') === 'Records Officer'
            || (string)($user['department_name'] ?? '') === 'Records';
        if (!$visit || $patientId <= 0
            || (!$recordsAccess && !$this->permissions->canViewEncounter($visit, $user))
            || !$this->permissions->canViewClinicalNotes($patientId, $user)
        ) {
            return $this->forbidden('Encounter Clinical Note access is denied.');
        }
        return $this->listNotes($patientId, $visitId, $user, $filters, $page, $pageSize);
    }

    public function getNoteVersions(int $noteId, array $user): array
    {
        $note = $this->getNoteInternal($noteId);
        if (!$note || !$this->permissions->canViewNoteHistory((int)$note['patient_id'], $user)) {
            return $this->forbidden('Clinical Note history access is denied.');
        }
        $canConfidential = $this->permissions->canViewConfidentialNotes((int)$note['patient_id'], $user);
        $draftAllowed = $this->canViewDraft($note, $user);
        $stmt = $this->pdo->prepare('SELECT v.*,CONCAT(a.first_name,\' \',a.last_name) author_name,
                CONCAT(s.first_name,\' \',s.last_name) signed_by_name,d.department_name
            FROM clinical_note_versions v INNER JOIN users a ON a.id=v.author_id
            LEFT JOIN users s ON s.id=v.signed_by LEFT JOIN departments d ON d.id=v.department_id
            WHERE v.note_id=:id ORDER BY v.version_number DESC');
        $stmt->execute([':id' => $noteId]);
        $versions = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $version) {
            if (in_array($version['version_status'], ['Draft', 'Amendment Proposal'], true) && !$draftAllowed) {
                continue;
            }
            if ($this->isConfidential($version) && !$canConfidential) {
                $version['content'] = null;
                $version['amendment_reason'] = null;
                $version['masked'] = true;
            } else {
                $version['masked'] = false;
            }
            $versions[] = $version;
        }
        if (!$this->recordReadAccess($note, $user, true)) {
            return ['success' => false, 'data' => null, 'audit_failed' => true, 'errors' => ['Clinical Note history access could not be recorded.']];
        }
        return $this->success([
            'note' => $this->minimumNote($note),
            'versions' => $versions,
            'amendments' => $this->safeAmendments($this->amendments->listForRecord('ClinicalNote', $noteId), $canConfidential)
        ]);
    }

    public function getNoteHistory(int $noteId, array $user): array
    {
        return $this->getNoteVersions($noteId, $user);
    }

    public function listPendingAmendments(array $user, int $page = 1, int $pageSize = 25): array
    {
        if (!$this->permissions->hasPermission('approve_note_amendments', $user)) {
            return $this->forbidden('Clinical Note amendment review is denied.');
        }
        $page = max(1, $page);
        $pageSize = max(1, min(100, $pageSize));
        $where = "a.record_type='ClinicalNote' AND a.status='Requested'";
        $count = (int)$this->pdo->query('SELECT COUNT(*) FROM record_amendments a WHERE ' . $where)->fetchColumn();
        $stmt = $this->pdo->prepare('SELECT a.id,a.patient_id,a.visit_id,a.record_id note_id,a.reason,a.status,
                a.requested_at,a.requested_by,CONCAT(u.first_name,\' \',u.last_name) requester_name,
                n.title,n.note_type,n.confidentiality_level,n.note_status,n.version note_version,
                p.hospital_number,p.first_name,p.last_name
            FROM record_amendments a
            INNER JOIN clinical_notes n ON n.id=a.record_id
            INNER JOIN patients p ON p.id=a.patient_id
            INNER JOIN users u ON u.id=a.requested_by
            WHERE ' . $where . ' ORDER BY a.requested_at ASC,a.id ASC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
        $stmt->bindValue(':offset', ($page - 1) * $pageSize, PDO::PARAM_INT);
        $stmt->execute();
        $records = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (!$this->permissions->canApproveNoteAmendments((int)$row['patient_id'], $user)) {
                continue;
            }
            if ($this->isConfidential($row)
                && !$this->permissions->canViewConfidentialNotes((int)$row['patient_id'], $user)
            ) {
                $row['title'] = 'Confidential Clinical Note';
                $row['reason'] = 'Protected amendment reason';
                $row['masked'] = true;
            } else {
                $row['masked'] = false;
            }
            $records[] = $row;
        }
        return $this->success([
            'records' => $records, 'current_page' => $page, 'page_size' => $pageSize,
            'total_results' => $count, 'total_pages' => max(1, (int)ceil($count / $pageSize))
        ]);
    }

    public function getNoteSummary(int $patientId, array $user, int $limit = 8): array
    {
        $result = $this->listPatientNotes($patientId, $user, ['status' => 'Signed,Amended'], 1, max(1, min(25, $limit)));
        if (!($result['success'] ?? false)) {
            return $result;
        }
        return $this->success(['notes' => $result['data']['records'], 'total' => $result['data']['total_results']]);
    }

    public function getNoteFilterOptions(int $patientId, array $user): array
    {
        if (!$this->permissions->canViewClinicalNotes($patientId, $user)) {
            return $this->forbidden('Clinical Note access is denied.');
        }
        $draftSql = '';
        $params = [':patient_id' => $patientId];
        if (!$this->permissions->canEditAnyNoteDraft($patientId, $user)) {
            $draftSql = ' AND (n.note_status<>\'Draft\' OR n.author_id=:viewer_id)';
            $params[':viewer_id'] = (int)($user['id'] ?? 0);
        }
        $authors = $this->pdo->prepare('SELECT DISTINCT u.id,CONCAT(u.first_name,\' \',u.last_name) name
            FROM clinical_notes n INNER JOIN users u ON u.id=n.author_id
            WHERE n.patient_id=:patient_id' . $draftSql . ' ORDER BY name');
        $authors->execute($params);
        $departments = $this->pdo->prepare('SELECT DISTINCT d.id,d.department_name name
            FROM clinical_notes n INNER JOIN departments d ON d.id=n.department_id
            WHERE n.patient_id=:patient_id' . $draftSql . ' ORDER BY name');
        $departments->execute($params);
        return $this->success(['authors' => $authors->fetchAll(PDO::FETCH_ASSOC), 'departments' => $departments->fetchAll(PDO::FETCH_ASSOC)]);
    }

    public function getAllowedNoteTypes(): array
    {
        return $this->settingSubset('clinical_notes.enabled_types', self::NOTE_TYPES);
    }

    public function getAllowedConfidentialityLevels(): array
    {
        return $this->settingSubset('clinical_notes.confidentiality_levels', self::CONFIDENTIALITY);
    }

    private function applyDirectAmendment(int $noteId, string $content, string $reason, int $expectedVersion, array $user, ?int $visitId): array
    {
        $actorId = (int)($user['id'] ?? 0);
        $reason = trim($reason);
        if ($reason === '') {
            return $this->failure(['An amendment reason is required.']);
        }
        $owns = !$this->pdo->inTransaction();
        try {
            if ($owns) {
                $this->pdo->beginTransaction();
            }
            $note = $this->lockNote($noteId);
            if (!$note || !in_array($note['note_status'], ['Signed', 'Amended'], true)) {
                throw new DomainException('Only a signed or amended note can be amended.');
            }
            if (!$this->permissions->canAmendSignedNotes((int)$note['patient_id'], $user)) {
                throw new UnexpectedValueException('FORBIDDEN');
            }
            if ((int)$note['version'] !== $expectedVersion) {
                if ($owns) {
                    $this->pdo->rollBack();
                }
                return $this->conflict('The Clinical Note changed before amendment.', (int)$note['version']);
            }
            $content = $this->validateContent($content);
            $effectiveVisitId = $visitId ?? $this->nullableInt($note['visit_id']);
            if ($effectiveVisitId !== $this->nullableInt($note['visit_id'])) {
                throw new DomainException('Encounter context must match the note.');
            }
            $visit = $this->lockVisitForPatient($effectiveVisitId, (int)$note['patient_id'], $user, false);
            $current = $this->currentVersionForUpdate($note);
            if ($this->pendingAmendmentIds($noteId) !== []) {
                throw new DomainException('A pending amendment request must be reviewed before direct amendment.');
            }
            $next = $this->nextVersionNumber($noteId);
            $versionId = $this->insertVersion($noteId, $next, $content, 'Amended', $actorId,
                $this->departmentId($user), (string)$note['confidentiality_level'], $actorId,
                date('Y-m-d H:i:s'), $reason, (int)$current['id']);
            $update = $this->pdo->prepare('UPDATE clinical_notes SET note_status=\'Amended\',current_version=:current_version,
                signed_by=:actor,signed_at=NOW(),locked_at=NOW(),amended_at=NOW(),version=version+1
                WHERE id=:id AND version=:expected');
            $update->execute([':current_version' => $next, ':actor' => $actorId, ':id' => $noteId, ':expected' => $expectedVersion]);
            $this->assertAffected($update, 'The Clinical Note changed concurrently.');
            $this->writeAudit($actorId, (int)$note['patient_id'], $effectiveVisitId, $this->departmentId($user),
                'CLINICAL_NOTE_AMENDED', 'Amended Clinical Note #' . $noteId . '.');
            $this->encounterEvent($visit, 'CLINICAL_NOTE_AMENDED', 'Clinical Note amended', 'Clinical Note #' . $noteId . ' was amended.', $actorId, $this->departmentId($user));
            if ($owns) {
                $this->pdo->commit();
            }
            return $this->success(['note_id' => $noteId, 'version_id' => $versionId]);
        } catch (Throwable $exception) {
            return $this->writeFailure($exception, $owns, 'Unable to amend Clinical Note.');
        }
    }

    private function listNotes(int $patientId, ?int $visitId, array $user, array $filters, int $page, int $pageSize): array
    {
        $page = max(1, $page);
        $pageSize = max(1, min(100, $pageSize));
        $where = ['n.patient_id=:patient_id'];
        $params = [':patient_id' => $patientId];
        if (!$this->permissions->canEditAnyNoteDraft($patientId, $user)) {
            $where[] = '(n.note_status<>\'Draft\' OR n.author_id=:viewer_id)';
            $params[':viewer_id'] = (int)($user['id'] ?? 0);
        }
        if ($visitId !== null) {
            $where[] = 'n.visit_id=:visit_id';
            $params[':visit_id'] = $visitId;
        }
        if (!empty($filters['type'])) {
            $where[] = 'n.note_type=:note_type';
            $params[':note_type'] = (string)$filters['type'];
        }
        if (!empty($filters['author_id'])) {
            $where[] = 'n.author_id=:author_id';
            $params[':author_id'] = (int)$filters['author_id'];
        }
        if (!empty($filters['department_id'])) {
            $where[] = 'n.department_id=:department_id';
            $params[':department_id'] = (int)$filters['department_id'];
        }
        if (!empty($filters['status'])) {
            $statuses = array_values(array_intersect(explode(',', (string)$filters['status']), ['Draft','Signed','Amended','Entered-in-error']));
            if ($statuses !== []) {
                $holders = [];
                foreach ($statuses as $index => $status) {
                    $key = ':status_' . $index;
                    $holders[] = $key;
                    $params[$key] = $status;
                }
                $where[] = 'n.note_status IN (' . implode(',', $holders) . ')';
            }
        }
        if (!empty($filters['date_from']) && $this->validDate((string)$filters['date_from'])) {
            $where[] = 'n.created_at>=:date_from';
            $params[':date_from'] = (string)$filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to']) && $this->validDate((string)$filters['date_to'])) {
            $where[] = 'n.created_at<=:date_to';
            $params[':date_to'] = (string)$filters['date_to'] . ' 23:59:59';
        }
        if (!empty($filters['q'])) {
            $where[] = 'n.title LIKE :title_prefix';
            $params[':title_prefix'] = trim((string)$filters['q']) . '%';
        }
        $whereSql = implode(' AND ', $where);
        $count = $this->pdo->prepare('SELECT COUNT(*) FROM clinical_notes n WHERE ' . $whereSql);
        $count->execute($params);
        $total = (int)$count->fetchColumn();
        $sql = $this->noteSelect() . ' WHERE ' . $whereSql
            . ' ORDER BY COALESCE(n.updated_at,n.created_at) DESC,n.id DESC LIMIT :limit OFFSET :offset';
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
        $stmt->bindValue(':offset', ($page - 1) * $pageSize, PDO::PARAM_INT);
        $stmt->execute();
        $records = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $note) {
            if ($note['note_status'] === 'Draft' && !$this->canViewDraft($note, $user)) {
                continue;
            }
            $records[] = $this->isConfidential($note)
                && !$this->permissions->canViewConfidentialNotes($patientId, $user)
                ? $this->maskedNote($note)
                : $this->minimumNote($note);
        }
        return $this->success([
            'records' => $records, 'current_page' => $page, 'page_size' => $pageSize,
            'total_results' => $total, 'total_pages' => max(1, (int)ceil($total / $pageSize)),
            'applied_filters' => $filters
        ]);
    }

    private function authorizeNote(array $note, array $user): array
    {
        $patientId = (int)$note['patient_id'];
        if (!$this->permissions->canViewClinicalNotes($patientId, $user)) {
            $this->auditDenied((int)($user['id'] ?? 0), $patientId, 'CLINICAL_NOTE_ACCESS_DENIED');
            return $this->forbidden('Clinical Note access is denied.');
        }
        if ($note['note_status'] === 'Draft' && !$this->canViewDraft($note, $user)) {
            $this->auditDenied((int)($user['id'] ?? 0), $patientId, 'CLINICAL_NOTE_ACCESS_DENIED');
            return $this->forbidden('This Clinical Note draft is not visible to you.');
        }
        if ($this->isConfidential($note)
            && !$this->permissions->canViewConfidentialNotes($patientId, $user)
        ) {
            $this->auditDenied((int)($user['id'] ?? 0), $patientId, 'CONFIDENTIAL_NOTE_ACCESS_DENIED');
            return $this->forbidden('Confidential Clinical Note access is denied.');
        }
        return $this->success([]);
    }

    private function canViewDraft(array $note, array $user): bool
    {
        if ((int)$note['author_id'] === (int)($user['id'] ?? 0)) {
            return $this->permissions->canEditOwnNoteDraft((int)$note['patient_id'], $user);
        }
        if ($this->settings->getString('clinical_notes.draft_visibility', 'author_and_authorized_editors') === 'author_only') {
            return false;
        }
        return $this->permissions->canEditAnyNoteDraft((int)$note['patient_id'], $user);
    }

    private function prepare(array $data): array
    {
        return [
            'patient_id' => (int)($data['patient_id'] ?? 0),
            'visit_id' => $this->nullableInt($data['visit_id'] ?? null),
            'note_type' => trim((string)($data['note_type'] ?? $this->settings->getString('clinical_notes.default_type', 'general_clinical_note'))),
            'title' => trim((string)($data['title'] ?? '')),
            'content' => $this->normalizeContent((string)($data['content'] ?? '')),
            'confidentiality_level' => trim((string)($data['confidentiality_level'] ?? $this->settings->getString('clinical_notes.default_confidentiality', 'Standard')))
        ];
    }

    private function validateDraft(array $data, int $actorId): array
    {
        $errors = [];
        if ($data['patient_id'] <= 0 || $actorId <= 0) {
            $errors[] = 'Patient and authenticated author are required.';
        }
        if (!in_array($data['note_type'], $this->getAllowedNoteTypes(), true)) {
            $errors[] = 'Select a supported Clinical Note type.';
        }
        if ($data['title'] === '' || mb_strlen($data['title']) > 200) {
            $errors[] = 'Title is required and must not exceed 200 characters.';
        }
        if (!in_array($data['confidentiality_level'], $this->getAllowedConfidentialityLevels(), true)) {
            $errors[] = 'Select a supported confidentiality level.';
        }
        try {
            $this->validateContent($data['content']);
        } catch (DomainException $exception) {
            $errors[] = $exception->getMessage();
        }
        return $errors;
    }

    private function validateContent(string $content): string
    {
        $content = $this->normalizeContent($content);
        if ($content === '') {
            throw new DomainException('Clinical Note content is required.');
        }
        if (!mb_check_encoding($content, 'UTF-8')) {
            throw new DomainException('Clinical Note content must be valid UTF-8 text.');
        }
        if (str_contains($content, "\0")
            || preg_match('/<\s*\/?\s*(script|iframe|object|embed|style|link|meta)\b/i', $content)
        ) {
            throw new DomainException('Executable or unsafe markup is not accepted in Clinical Notes.');
        }
        $maximum = $this->settings->getInteger('clinical_notes.maximum_content_length', 50000);
        if (mb_strlen($content) > $maximum) {
            throw new DomainException('Clinical Note content exceeds the configured maximum length.');
        }
        return $content;
    }

    private function normalizeContent(string $content): string
    {
        return trim(str_replace(["\r\n", "\r"], "\n", $content));
    }

    private function insertVersion(
        int $noteId,
        int $number,
        string $content,
        string $status,
        int $authorId,
        ?int $departmentId,
        string $confidentiality,
        ?int $signedBy,
        ?string $signedAt,
        ?string $reason,
        ?int $supersedesId
    ): int {
        $stmt = $this->pdo->prepare('INSERT INTO clinical_note_versions
            (note_id,version_number,content,content_format,version_status,author_id,department_id,
             confidentiality_level,content_checksum,signed_by,signed_at,amendment_reason,supersedes_version_id)
            VALUES (:note_id,:version_number,:content,\'Plain Text\',:status,:author_id,:department_id,
             :confidentiality,:checksum,:signed_by,:signed_at,:reason,:supersedes)');
        $stmt->execute([
            ':note_id' => $noteId, ':version_number' => $number, ':content' => $content,
            ':status' => $status, ':author_id' => $authorId, ':department_id' => $departmentId,
            ':confidentiality' => $confidentiality, ':checksum' => hash('sha256', $content),
            ':signed_by' => $signedBy, ':signed_at' => $signedAt,
            ':reason' => $reason, ':supersedes' => $supersedesId
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    private function noteSelect(): string
    {
        return 'SELECT n.*,v.id current_version_id,v.content,v.content_format,v.version_status,
            v.content_checksum,v.amendment_reason,CONCAT(a.first_name,\' \',a.last_name) author_name,
            CONCAT(s.first_name,\' \',s.last_name) signed_by_name,d.department_name
            FROM clinical_notes n
            INNER JOIN clinical_note_versions v ON v.note_id=n.id AND v.version_number=n.current_version
            INNER JOIN users a ON a.id=n.author_id LEFT JOIN users s ON s.id=n.signed_by
            LEFT JOIN departments d ON d.id=n.department_id';
    }

    private function getNoteInternal(int $noteId): ?array
    {
        $stmt = $this->pdo->prepare($this->noteSelect() . ' WHERE n.id=:id LIMIT 1');
        $stmt->execute([':id' => $noteId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function lockNote(int $noteId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT n.*,v.id current_version_id,v.content,v.content_format,v.version_status,
            v.content_checksum,v.department_id version_department_id,v.confidentiality_level version_confidentiality,
            v.author_id version_author_id FROM clinical_notes n
            INNER JOIN clinical_note_versions v ON v.note_id=n.id AND v.version_number=n.current_version
            WHERE n.id=:id FOR UPDATE');
        $stmt->execute([':id' => $noteId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function currentVersionForUpdate(array $note): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM clinical_note_versions WHERE id=:id FOR UPDATE');
        $stmt->execute([':id' => (int)$note['current_version_id']]);
        $version = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$version) {
            throw new RuntimeException('Current Clinical Note version is missing.');
        }
        return $version;
    }

    private function getVersionByIdForUpdate(int $versionId, int $noteId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM clinical_note_versions WHERE id=:id AND note_id=:note_id FOR UPDATE');
        $stmt->execute([':id' => $versionId, ':note_id' => $noteId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function nextVersionNumber(int $noteId): int
    {
        $stmt = $this->pdo->prepare('SELECT COALESCE(MAX(version_number),0)+1 FROM clinical_note_versions WHERE note_id=:id');
        $stmt->execute([':id' => $noteId]);
        return (int)$stmt->fetchColumn();
    }

    private function pendingAmendmentIds(int $noteId): array
    {
        $stmt = $this->pdo->prepare("SELECT id FROM record_amendments
            WHERE record_type='ClinicalNote' AND record_id=:id AND status='Requested'
            ORDER BY id FOR UPDATE");
        $stmt->execute([':id' => $noteId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    private function lockPatient(int $patientId): void
    {
        $stmt = $this->pdo->prepare('SELECT id FROM patients WHERE id=:id FOR UPDATE');
        $stmt->execute([':id' => $patientId]);
        if (!$stmt->fetchColumn()) {
            throw new DomainException('Patient not found.');
        }
    }

    private function lockVisitForPatient(?int $visitId, int $patientId, array $user, bool $mutation): ?array
    {
        if ($visitId === null) {
            return null;
        }
        $stmt = $this->pdo->prepare('SELECT * FROM visits WHERE id=:id FOR UPDATE');
        $stmt->execute([':id' => $visitId]);
        $visit = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        $recordsAccess = $this->permissions->isAdministrator($user)
            || (string)($user['role_name'] ?? '') === 'Records Officer'
            || (string)($user['department_name'] ?? '') === 'Records';
        if (!$visit || (int)$visit['patient_id'] !== $patientId
            || (!$recordsAccess && !$this->permissions->canViewEncounter($visit, $user))
        ) {
            throw new DomainException('The encounter context is invalid or inaccessible.');
        }
        if ($mutation
            && in_array((string)$visit['visit_status'], ['Completed', 'Cancelled'], true)
            && !$this->settings->getBoolean('clinical_notes.closed_encounter_new_notes', false)
        ) {
            throw new DomainException('New or edited notes are not allowed on a closed encounter. Use the amendment workflow for signed records.');
        }
        return $visit;
    }

    private function recordReadAccess(array $note, array $user, bool $history): bool
    {
        $actorId = (int)($user['id'] ?? 0);
        $action = $history ? 'CLINICAL_NOTE_HISTORY_VIEWED'
            : ($this->isConfidential($note) ? 'CONFIDENTIAL_NOTE_VIEWED' : 'CLINICAL_NOTE_VIEWED');
        $owns = !$this->pdo->inTransaction();
        try {
            if ($owns) {
                $this->pdo->beginTransaction();
            }
            if (!$this->audit->logPatientAccess(
                $actorId, (int)$note['patient_id'], $this->nullableInt($note['visit_id']),
                $this->departmentId($user), 'VIEW', $history ? 'ClinicalNoteHistory' : 'ClinicalNote',
                (int)$note['id'], $history ? 'Clinical Note version history access.' : 'Clinical Note content access.'
            )) {
                throw new RuntimeException('Unable to record protected access.');
            }
            $this->writeAudit($actorId, (int)$note['patient_id'], $this->nullableInt($note['visit_id']),
                $this->departmentId($user), $action,
                ($history ? 'Viewed history for Clinical Note #' : 'Viewed Clinical Note #') . $note['id'] . '.');
            if ($owns) {
                $this->pdo->commit();
            }
            return true;
        } catch (Throwable) {
            if ($owns && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return false;
        }
    }

    private function writeAudit(int $actorId, int $patientId, ?int $visitId, ?int $departmentId, string $action, string $description): void
    {
        if (!$this->audit->logPatient($actorId, $patientId, $visitId, 'Medical Records', $action,
            $description, $departmentId, 'INFO', $action)) {
            throw new RuntimeException('Clinical Note audit write failed.');
        }
    }

    private function auditDenied(int $actorId, int $patientId, string $action): void
    {
        try {
            $this->audit->logPatient($actorId > 0 ? $actorId : null, $patientId, null, 'Security', $action,
                'Clinical Note access was denied by policy.', null, 'WARNING', $action);
        } catch (Throwable) {
        }
    }

    private function encounterEvent(?array $visit, string $type, string $title, string $description, int $actorId, ?int $departmentId): void
    {
        if (!$visit) {
            return;
        }
        $result = $this->events->record((int)$visit['id'], $type, $title, $description, $departmentId, $actorId);
        if (!($result['success'] ?? false)) {
            throw new RuntimeException('Clinical Note encounter event failed.');
        }
    }

    private function minimumNote(array $note): array
    {
        unset($note['content']);
        $note['masked'] = false;
        return $note;
    }

    private function maskedNote(array $note): array
    {
        $safe = $this->minimumNote($note);
        $safe['title'] = 'Confidential Clinical Note';
        $safe['note_type'] = 'confidential';
        $safe['author_name'] = null;
        $safe['masked'] = true;
        return $safe;
    }

    private function safeAmendments(array $requests, bool $canConfidential): array
    {
        return array_map(static function (array $request) use ($canConfidential): array {
            $proposal = json_decode((string)$request['proposed_changes'], true);
            $request['proposed_changes'] = is_array($proposal) ? $proposal : [];
            if (!$canConfidential) {
                $request['reason'] = 'Protected amendment reason';
            }
            return $request;
        }, $requests);
    }

    private function isConfidential(array $record): bool
    {
        return in_array((string)($record['confidentiality_level'] ?? ''), ['Restricted','Confidential','Highly Confidential'], true);
    }

    private function settingSubset(string $key, array $supported): array
    {
        $configured = $this->settings->getArray($key, $supported);
        $effective = array_values(array_intersect($supported, array_map('strval', $configured)));
        return $effective !== [] ? $effective : $supported;
    }

    private function departmentId(array $user): ?int
    {
        $id = (int)($user['active_department_id'] ?? $user['department_id'] ?? 0);
        return $id > 0 ? $id : null;
    }

    private function nullableInt(mixed $value): ?int
    {
        $value = filter_var($value, FILTER_VALIDATE_INT);
        return $value && $value > 0 ? (int)$value : null;
    }

    private function validDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }

    private function assertAffected(PDOStatement $statement, string $message): void
    {
        if ($statement->rowCount() !== 1) {
            throw new RuntimeException($message);
        }
    }

    private function success(array $data): array
    {
        return ['success' => true, 'data' => $data] + $data + ['errors' => []];
    }

    private function failure(array $errors): array
    {
        return ['success' => false, 'data' => null, 'errors' => $errors];
    }

    private function forbidden(string $message): array
    {
        return ['success' => false, 'data' => null, 'forbidden' => true, 'errors' => [$message]];
    }

    private function conflict(string $message, int $version): array
    {
        return ['success' => false, 'data' => null, 'conflict' => true, 'current_version' => $version, 'errors' => [$message]];
    }

    private function writeFailure(Throwable $exception, bool $owns, string $fallback): array
    {
        if ($owns && $this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
        if (!$owns) {
            throw $exception;
        }
        if ($exception instanceof UnexpectedValueException && $exception->getMessage() === 'FORBIDDEN') {
            return $this->forbidden('Clinical Note action is not authorized.');
        }
        return $this->failure([
            $exception instanceof DomainException ? $exception->getMessage() : $fallback
        ]);
    }
}
