<?php

declare(strict_types=1);

require_once __DIR__ . '/AuditService.php';
require_once __DIR__ . '/EncounterEventService.php';
require_once __DIR__ . '/PermissionService.php';
require_once __DIR__ . '/SettingsService.php';

class ProblemListService
{
    private const PROBLEM_CATEGORIES = [
        'Chronic Condition', 'Acute Problem', 'Historical Diagnosis',
        'Surgical Condition', 'Risk Factor', 'Other'
    ];
    private const PROBLEM_SEVERITIES = ['Mild', 'Moderate', 'Severe', 'Unknown'];
    private const HISTORY_TYPES = [
        'Past Medical History', 'Surgical History', 'Family History',
        'Social History', 'Obstetric History', 'Immunization History',
        'Previous Hospitalization', 'Previous Procedure', 'Other'
    ];
    private const CONFIDENTIALITY_LEVELS = ['Standard', 'Restricted', 'Confidential'];
    private const DATE_PRECISIONS = ['Exact', 'Month', 'Year', 'Unknown'];
    private const HISTORY_STATUSES = ['Active', 'Historical', 'Entered-in-error'];

    public function __construct(
        private PDO $pdo,
        private ?AuditService $auditService = null,
        private ?EncounterEventService $eventService = null,
        private ?SettingsService $settingsService = null,
        private ?PermissionService $permissionService = null
    ) {
        $this->auditService ??= new AuditService($pdo);
        $this->eventService ??= new EncounterEventService($pdo);
        $this->settingsService ??= new SettingsService($pdo);
        $this->permissionService ??= new PermissionService($pdo, $this->settingsService);
    }

    /*
    |--------------------------------------------------------------------------
    | Problem Commands
    |--------------------------------------------------------------------------
    */

    public function addProblem(array $data, int $actorId, ?int $departmentId = null): array
    {
        $prepared = $this->prepareProblem($data);
        $errors = $this->validateProblem($prepared, $actorId);
        if ($errors !== []) {
            return $this->failure($errors);
        }

        try {
            $this->pdo->beginTransaction();
            $this->lockPatient((int)$prepared['patient_id']);
            $visit = $this->lockVisitForPatient(
                $prepared['source_visit_id'],
                (int)$prepared['patient_id']
            );
            $this->lockMatchingProblems(
                (int)$prepared['patient_id'],
                (string)$prepared['normalized_problem_name'],
                (string)$prepared['category']
            );
            $stmt = $this->pdo->prepare('
                INSERT INTO patient_problems (
                    patient_id,source_visit_id,problem_code_system,problem_code,
                    problem_name,normalized_problem_name,category,clinical_status,
                    verification_status,severity,confidentiality_level,onset_date,
                    recorded_date,active_problem_key,recorded_by,notes
                ) VALUES (
                    :patient_id,:source_visit_id,:problem_code_system,:problem_code,
                    :problem_name,:normalized_problem_name,:category,\'Active\',
                    \'Unverified\',:severity,:confidentiality_level,:onset_date,
                    :recorded_date,:active_problem_key,:recorded_by,:notes
                )
            ');
            $stmt->execute([
                ':patient_id' => $prepared['patient_id'],
                ':source_visit_id' => $prepared['source_visit_id'],
                ':problem_code_system' => $prepared['problem_code_system'],
                ':problem_code' => $prepared['problem_code'],
                ':problem_name' => $prepared['problem_name'],
                ':normalized_problem_name' => $prepared['normalized_problem_name'],
                ':category' => $prepared['category'],
                ':severity' => $prepared['severity'],
                ':confidentiality_level' => $prepared['confidentiality_level'],
                ':onset_date' => $prepared['onset_date'],
                ':recorded_date' => $prepared['recorded_date'],
                ':active_problem_key' => $this->problemKey($prepared),
                ':recorded_by' => $actorId,
                ':notes' => $prepared['notes']
            ]);
            $problemId = (int)$this->pdo->lastInsertId();
            $current = $this->getProblemInternal($problemId);
            $this->recordProblemHistory(
                $current,
                null,
                'Added',
                (string)$prepared['reason'],
                $actorId,
                $departmentId,
                $prepared['source_visit_id']
            );
            $this->audit(
                $actorId,
                (int)$prepared['patient_id'],
                $prepared['source_visit_id'],
                'PROBLEM_ADDED',
                'Added a longitudinal patient problem.'
            );
            $this->encounterEvent(
                $visit,
                'PROBLEM_ADDED',
                'Problem Added',
                'A longitudinal patient problem was added during this encounter.',
                $actorId
            );
            $this->pdo->commit();

            return $this->success(['problem_id' => $problemId, 'patient_id' => (int)$prepared['patient_id']]);
        } catch (PDOException $exception) {
            $this->rollback();
            return $this->failure([(string)$exception->getCode() === '23000'
                ? 'An active problem with this name and category already exists.'
                : 'Unable to add the patient problem.']);
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure([$this->safeWriteError($exception, 'Unable to add the patient problem.')]);
        }
    }

    public function updateProblem(
        int $problemId,
        array $data,
        int $expectedVersion,
        int $actorId,
        ?int $departmentId = null
    ): array {
        return $this->updateProblemRecord(
            $problemId,
            $data,
            $expectedVersion,
            $actorId,
            $departmentId,
            'Updated'
        );
    }

    public function verifyProblem(
        int $problemId,
        string $reason,
        int $actorId,
        int $expectedVersion,
        ?int $visitId = null,
        ?int $departmentId = null
    ): array {
        return $this->transitionProblem($problemId, 'verify', $reason, $actorId, $expectedVersion, $visitId, $departmentId);
    }

    public function refuteProblem(
        int $problemId,
        string $reason,
        int $actorId,
        int $expectedVersion,
        ?int $visitId = null,
        ?int $departmentId = null
    ): array {
        return $this->transitionProblem($problemId, 'refute', $reason, $actorId, $expectedVersion, $visitId, $departmentId);
    }

    public function deactivateProblem(
        int $problemId,
        string $reason,
        int $actorId,
        int $expectedVersion,
        ?int $visitId = null,
        ?int $departmentId = null
    ): array {
        return $this->transitionProblem($problemId, 'deactivate', $reason, $actorId, $expectedVersion, $visitId, $departmentId);
    }

    public function reactivateProblem(
        int $problemId,
        string $reason,
        int $actorId,
        int $expectedVersion,
        ?int $visitId = null,
        ?int $departmentId = null
    ): array {
        return $this->transitionProblem($problemId, 'reactivate', $reason, $actorId, $expectedVersion, $visitId, $departmentId);
    }

    public function resolveProblem(
        int $problemId,
        string $reason,
        int $actorId,
        int $expectedVersion,
        ?int $visitId = null,
        ?string $resolvedDate = null,
        ?int $departmentId = null
    ): array {
        return $this->transitionProblem(
            $problemId,
            'resolve',
            $reason,
            $actorId,
            $expectedVersion,
            $visitId,
            $departmentId,
            $resolvedDate
        );
    }

    public function markProblemEnteredInError(
        int $problemId,
        string $reason,
        int $actorId,
        int $expectedVersion,
        ?int $visitId = null,
        ?int $departmentId = null
    ): array {
        return $this->transitionProblem($problemId, 'entered_error', $reason, $actorId, $expectedVersion, $visitId, $departmentId);
    }

    /*
    |--------------------------------------------------------------------------
    | Structured Medical History Commands
    |--------------------------------------------------------------------------
    */

    public function addHistoryEntry(array $data, int $actorId, ?int $departmentId = null): array
    {
        $prepared = $this->prepareHistory($data);
        $errors = $this->validateHistory($prepared, $actorId);
        if ($errors !== []) {
            return $this->failure($errors);
        }

        try {
            $this->pdo->beginTransaction();
            $this->lockPatient((int)$prepared['patient_id']);
            $visit = $this->lockVisitForPatient($prepared['source_visit_id'], (int)$prepared['patient_id']);
            $stmt = $this->pdo->prepare('
                INSERT INTO patient_medical_history (
                    patient_id,source_visit_id,history_type,title,normalized_title,
                    description,event_date,date_precision,status,source,
                    confidentiality_level,recorded_by
                ) VALUES (
                    :patient_id,:source_visit_id,:history_type,:title,:normalized_title,
                    :description,:event_date,:date_precision,:status,:source,
                    :confidentiality_level,:recorded_by
                )
            ');
            $stmt->execute([
                ':patient_id' => $prepared['patient_id'],
                ':source_visit_id' => $prepared['source_visit_id'],
                ':history_type' => $prepared['history_type'],
                ':title' => $prepared['title'],
                ':normalized_title' => $prepared['normalized_title'],
                ':description' => $prepared['description'],
                ':event_date' => $prepared['event_date'],
                ':date_precision' => $prepared['date_precision'],
                ':status' => $prepared['status'],
                ':source' => $prepared['source'],
                ':confidentiality_level' => $prepared['confidentiality_level'],
                ':recorded_by' => $actorId
            ]);
            $entryId = (int)$this->pdo->lastInsertId();
            $current = $this->getHistoryInternal($entryId);
            $this->recordMedicalHistoryVersion($current, null, 'Added', (string)$prepared['reason'], $actorId, $departmentId, $prepared['source_visit_id']);
            $this->audit($actorId, (int)$prepared['patient_id'], $prepared['source_visit_id'], 'MEDICAL_HISTORY_ADDED', 'Added structured medical history information.');
            $this->encounterEvent($visit, 'MEDICAL_HISTORY_RECORDED', 'Medical History Recorded', 'Structured medical history was recorded during this encounter.', $actorId);
            $this->pdo->commit();

            return $this->success(['history_entry_id' => $entryId, 'patient_id' => (int)$prepared['patient_id']]);
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure([$this->safeWriteError($exception, 'Unable to add medical history.')]);
        }
    }

    public function updateHistoryEntry(
        int $entryId,
        array $data,
        int $expectedVersion,
        int $actorId,
        ?int $departmentId = null
    ): array {
        return $this->updateHistoryRecord($entryId, $data, $expectedVersion, $actorId, $departmentId, 'Updated');
    }

    public function correctHistoryEntry(
        int $entryId,
        array $data,
        int $expectedVersion,
        int $actorId,
        ?int $departmentId = null
    ): array {
        return $this->updateHistoryRecord($entryId, $data, $expectedVersion, $actorId, $departmentId, 'Corrected');
    }

    public function verifyHistoryEntry(
        int $entryId,
        string $reason,
        int $actorId,
        int $expectedVersion,
        ?int $visitId = null,
        ?int $departmentId = null
    ): array {
        if (trim($reason) === '') {
            return $this->failure(['A verification reason is required.']);
        }
        try {
            $this->pdo->beginTransaction();
            $current = $this->lockHistoryEntry($entryId);
            if (!$current) {
                throw new RuntimeException('Medical history entry not found.');
            }
            if ((int)$current['version'] !== $expectedVersion) {
                $this->rollback();
                return $this->conflict('The medical history entry changed. Reload and try again.', (int)$current['version']);
            }
            if ((string)$current['status'] === 'Entered-in-error') {
                $this->rollback();
                return $this->failure(['An entered-in-error history entry cannot be verified.']);
            }
            if (!$this->settingsService->getBoolean('medical_history.allow_self_verification', false)
                && $this->latestHistoryAuthorId($entryId, $current) === $actorId
            ) {
                $this->rollback();
                return $this->failure(['The latest author cannot verify this medical history entry.']);
            }
            $visit = $this->lockVisitForPatient($visitId, (int)$current['patient_id']);
            $newVersion = $expectedVersion + 1;
            $stmt = $this->pdo->prepare('UPDATE patient_medical_history SET verified_by=:actor_id,verified_at=NOW(),version=:version WHERE id=:id AND version=:expected_version');
            $stmt->execute([':actor_id' => $actorId, ':version' => $newVersion, ':id' => $entryId, ':expected_version' => $expectedVersion]);
            $this->assertAffected($stmt, 'Concurrent medical-history verification detected.');
            $updated = $this->getHistoryInternal($entryId);
            $this->recordMedicalHistoryVersion($updated, $current, 'Verified', $reason, $actorId, $departmentId, $visitId);
            $this->audit($actorId, (int)$current['patient_id'], $visitId, 'MEDICAL_HISTORY_VERIFIED', 'Verified structured medical history information.');
            $this->pdo->commit();
            return $this->success(['history_entry_id' => $entryId, 'version' => $newVersion]);
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure([$this->safeWriteError($exception, 'Unable to verify medical history.')]);
        }
    }

    public function markHistoryEnteredInError(
        int $entryId,
        string $reason,
        int $actorId,
        int $expectedVersion,
        ?int $visitId = null,
        ?int $departmentId = null
    ): array {
        if (trim($reason) === '') {
            return $this->failure(['An entered-in-error reason is required.']);
        }
        try {
            $this->pdo->beginTransaction();
            $current = $this->lockHistoryEntry($entryId);
            if (!$current) {
                throw new RuntimeException('Medical history entry not found.');
            }
            if ((int)$current['version'] !== $expectedVersion) {
                $this->rollback();
                return $this->conflict('The medical history entry changed. Reload and try again.', (int)$current['version']);
            }
            if ((string)$current['status'] === 'Entered-in-error') {
                $this->rollback();
                return $this->failure(['This history entry is already entered in error.']);
            }
            $this->lockVisitForPatient($visitId, (int)$current['patient_id']);
            $newVersion = $expectedVersion + 1;
            $stmt = $this->pdo->prepare("UPDATE patient_medical_history SET status='Entered-in-error',verified_by=NULL,verified_at=NULL,version=:version WHERE id=:id AND version=:expected_version");
            $stmt->execute([':version' => $newVersion, ':id' => $entryId, ':expected_version' => $expectedVersion]);
            $this->assertAffected($stmt, 'Concurrent medical-history transition detected.');
            $updated = $this->getHistoryInternal($entryId);
            $this->recordMedicalHistoryVersion($updated, $current, 'EnteredInError', $reason, $actorId, $departmentId, $visitId);
            $this->audit($actorId, (int)$current['patient_id'], $visitId, 'MEDICAL_HISTORY_ENTERED_IN_ERROR', 'Marked structured medical history entered in error.');
            $this->pdo->commit();
            return $this->success(['history_entry_id' => $entryId, 'version' => $newVersion]);
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure([$this->safeWriteError($exception, 'Unable to update medical-history status.')]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Queries and Summaries
    |--------------------------------------------------------------------------
    */

    public function getProblemById(int $problemId): ?array
    {
        $problem = $this->getProblemInternal($problemId);
        return $problem ? $this->protectRecord($problem, false) : null;
    }

    public function getProblemByIdForUser(int $problemId, array $user, bool $auditAccess = true): array
    {
        $problem = $this->getProblemInternal($problemId);
        if (!$problem) {
            return $this->failure(['Problem not found.']);
        }
        return $this->authorizeRecord($problem, $user, 'problem', $auditAccess);
    }

    public function getPatientProblems(int $patientId, bool $includeInactive = true): array
    {
        return array_map(
            fn (array $row): array => $this->protectRecord($row, false),
            $this->queryProblems($patientId, $includeInactive)
        );
    }

    public function getPatientProblemsForUser(int $patientId, array $user, bool $includeInactive = true): array
    {
        if (!$this->permissionService->canViewProblemList($patientId, $user)) {
            return [];
        }
        // Lists and summaries remain minimum-necessary. Full restricted detail
        // is available only through the user-aware single-record contract,
        // which records protected access and fails closed if auditing fails.
        return array_map(
            fn (array $row): array => $this->protectRecord($row, false),
            $this->queryProblems($patientId, $includeInactive)
        );
    }

    public function getProblemHistory(int $problemId): array
    {
        return array_map(
            fn (array $row): array => $this->protectVersion($row, false),
            $this->queryProblemHistory($problemId)
        );
    }

    public function getProblemHistoryForUser(int $problemId, array $user): array
    {
        $problem = $this->getProblemInternal($problemId);
        if (!$problem || !$this->permissionService->canViewProblemHistory((int)$problem['patient_id'], $user)) {
            return $this->forbidden('You do not have permission to view problem history.');
        }
        $canViewConfidential = $this->permissionService->canViewConfidentialMedicalHistory((int)$problem['patient_id'], $user);
        $history = array_map(
            fn (array $row): array => $this->protectVersion($row, $canViewConfidential),
            $this->queryProblemHistory($problemId)
        );
        $audit = $this->recordReadAudit(
            (int)$problem['patient_id'],
            (int)$user['id'],
            null,
            'PROBLEM_HISTORY_VIEWED',
            $canViewConfidential && $this->containsRestrictedVersion($history)
                ? 'Viewed authorized Problem List history containing restricted versions.'
                : 'Viewed authorized Problem List history.'
        );
        if (!($audit['success'] ?? false)) {
            return $audit;
        }
        return $this->success(['problem' => $this->protectRecord($problem, $canViewConfidential), 'history' => $history]);
    }

    public function getHistoryEntryById(int $entryId): ?array
    {
        $entry = $this->getHistoryInternal($entryId);
        return $entry ? $this->protectRecord($entry, false) : null;
    }

    public function getHistoryEntryByIdForUser(int $entryId, array $user, bool $auditAccess = true): array
    {
        $entry = $this->getHistoryInternal($entryId);
        if (!$entry) {
            return $this->failure(['Medical history entry not found.']);
        }
        return $this->authorizeRecord($entry, $user, 'history', $auditAccess);
    }

    public function getPatientMedicalHistory(int $patientId, bool $includeEnteredInError = false): array
    {
        return array_map(
            fn (array $row): array => $this->protectRecord($row, false),
            $this->queryMedicalHistory($patientId, $includeEnteredInError)
        );
    }

    public function getPatientMedicalHistoryForUser(int $patientId, array $user, bool $includeEnteredInError = false): array
    {
        if (!$this->permissionService->canViewStructuredMedicalHistory($patientId, $user)) {
            return [];
        }
        return array_map(
            fn (array $row): array => $this->protectRecord($row, false),
            $this->queryMedicalHistory($patientId, $includeEnteredInError)
        );
    }

    public function getMedicalHistoryVersions(int $entryId): array
    {
        return array_map(
            fn (array $row): array => $this->protectVersion($row, false),
            $this->queryMedicalHistoryVersions($entryId)
        );
    }

    public function getMedicalHistoryVersionsForUser(int $entryId, array $user): array
    {
        $entry = $this->getHistoryInternal($entryId);
        if (!$entry || !$this->permissionService->canViewProblemHistory((int)$entry['patient_id'], $user)) {
            return $this->forbidden('You do not have permission to view medical-history versions.');
        }
        $canViewConfidential = $this->permissionService->canViewConfidentialMedicalHistory((int)$entry['patient_id'], $user);
        $versions = array_map(
            fn (array $row): array => $this->protectVersion($row, $canViewConfidential),
            $this->queryMedicalHistoryVersions($entryId)
        );
        if ($canViewConfidential && $this->containsRestrictedVersion($versions)) {
            $audit = $this->recordReadAudit((int)$entry['patient_id'], (int)$user['id'], null, 'CONFIDENTIAL_MEDICAL_HISTORY_VIEWED', 'Viewed confidential medical-history versions.');
            if (!($audit['success'] ?? false)) {
                return $audit;
            }
        }
        return $this->success(['entry' => $this->protectRecord($entry, $canViewConfidential), 'versions' => $versions]);
    }

    public function getProblemSummary(int $patientId, array $user): array
    {
        $rows = $this->getPatientProblemsForUser($patientId, $user, false);
        $confirmed = array_values(array_filter($rows, static fn (array $row): bool => ($row['verification_status'] ?? '') === 'Confirmed'));
        return $this->success([
            'active' => $rows,
            'active_confirmed' => $confirmed,
            'severe_active_confirmed' => array_values(array_filter($confirmed, static fn (array $row): bool => ($row['severity'] ?? '') === 'Severe'))
        ]);
    }

    public function getMedicalHistorySummary(int $patientId, array $user, int $limit = 8): array
    {
        $rows = $this->getPatientMedicalHistoryForUser($patientId, $user, false);
        return $this->success(['entries' => array_slice($rows, 0, max(1, min(50, $limit)))]);
    }

    public function getAllowedProblemCategories(): array
    {
        return $this->settingSubset('problem_list.categories', self::PROBLEM_CATEGORIES);
    }

    public function getAllowedProblemSeverities(): array
    {
        return $this->settingSubset('problem_list.severities', self::PROBLEM_SEVERITIES);
    }

    public function getAllowedHistoryTypes(): array
    {
        return $this->settingSubset('medical_history.types', self::HISTORY_TYPES);
    }

    public function getAllowedConfidentialityLevels(): array
    {
        return $this->settingSubset('medical_history.confidentiality_levels', self::CONFIDENTIALITY_LEVELS);
    }

    /*
    |--------------------------------------------------------------------------
    | Internal Mutations
    |--------------------------------------------------------------------------
    */

    private function updateProblemRecord(int $problemId, array $data, int $expectedVersion, int $actorId, ?int $departmentId, string $action): array
    {
        if (trim((string)($data['reason'] ?? '')) === '') {
            return $this->failure(['A change reason is required.']);
        }
        try {
            $this->pdo->beginTransaction();
            $current = $this->lockProblem($problemId);
            if (!$current) {
                throw new RuntimeException('Problem not found.');
            }
            if ((int)$current['version'] !== $expectedVersion) {
                $this->rollback();
                return $this->conflict('The problem changed. Reload and try again.', (int)$current['version']);
            }
            if (in_array((string)$current['clinical_status'], ['Resolved', 'Entered-in-error'], true)) {
                $this->rollback();
                return $this->failure(['Resolved or entered-in-error problems cannot be edited.']);
            }
            $prepared = $this->prepareProblem(array_merge($current, $data));
            $errors = $this->validateProblem($prepared, $actorId);
            if ($errors !== []) {
                $this->rollback();
                return $this->failure($errors);
            }
            $visitId = $this->nullableInt($data['visit_id'] ?? null);
            $visit = $this->lockVisitForPatient($visitId, (int)$current['patient_id']);
            if ((string)$current['clinical_status'] === 'Active') {
                $this->lockMatchingProblems((int)$current['patient_id'], (string)$prepared['normalized_problem_name'], (string)$prepared['category'], $problemId);
            }
            $reset = (string)$current['verification_status'] === 'Confirmed' && $this->problemMateriallyChanged($current, $prepared);
            $newVersion = $expectedVersion + 1;
            $stmt = $this->pdo->prepare('
                UPDATE patient_problems SET problem_code_system=:problem_code_system,
                    problem_code=:problem_code,problem_name=:problem_name,
                    normalized_problem_name=:normalized_problem_name,category=:category,
                    severity=:severity,confidentiality_level=:confidentiality_level,
                    onset_date=:onset_date,notes=:notes,active_problem_key=:active_problem_key,
                    verification_status=:verification_status,verified_by=:verified_by,
                    verified_at=:verified_at,version=:version
                WHERE id=:id AND version=:expected_version
            ');
            $stmt->execute([
                ':problem_code_system' => $prepared['problem_code_system'], ':problem_code' => $prepared['problem_code'],
                ':problem_name' => $prepared['problem_name'], ':normalized_problem_name' => $prepared['normalized_problem_name'],
                ':category' => $prepared['category'], ':severity' => $prepared['severity'],
                ':confidentiality_level' => $prepared['confidentiality_level'], ':onset_date' => $prepared['onset_date'],
                ':notes' => $prepared['notes'], ':active_problem_key' => (string)$current['clinical_status'] === 'Active' ? $this->problemKey($prepared) : null,
                ':verification_status' => $reset ? 'Unverified' : $current['verification_status'],
                ':verified_by' => $reset ? null : $current['verified_by'], ':verified_at' => $reset ? null : $current['verified_at'],
                ':version' => $newVersion, ':id' => $problemId, ':expected_version' => $expectedVersion
            ]);
            $this->assertAffected($stmt, 'Concurrent problem update detected.');
            $updated = $this->getProblemInternal($problemId);
            $historyAction = $reset ? 'UpdatedVerificationReset' : $action;
            $reason = (string)$prepared['reason'] . ($reset ? ' Verification reset after clinically significant change.' : '');
            $this->recordProblemHistory($updated, $current, $historyAction, $reason, $actorId, $departmentId, $visitId);
            $this->audit($actorId, (int)$current['patient_id'], $visitId, 'PROBLEM_UPDATED', $reset ? 'Updated a problem and reset verification.' : 'Updated a longitudinal problem.');
            if ($reset) {
                $this->encounterEvent($visit, 'PROBLEM_VERIFICATION_RESET', 'Problem Verification Reset', 'Problem verification was reset after a material change.', $actorId);
            }
            $this->pdo->commit();
            return $this->success(['problem_id' => $problemId, 'version' => $newVersion, 'verification_reset' => $reset]);
        } catch (PDOException $exception) {
            $this->rollback();
            return $this->failure([(string)$exception->getCode() === '23000' ? 'An active problem with this name and category already exists.' : 'Unable to update the problem.']);
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure([$this->safeWriteError($exception, 'Unable to update the problem.')]);
        }
    }

    private function transitionProblem(int $problemId, string $operation, string $reason, int $actorId, int $expectedVersion, ?int $visitId, ?int $departmentId, ?string $resolvedDate = null): array
    {
        if (trim($reason) === '') {
            return $this->failure(['A transition reason is required.']);
        }
        try {
            $this->pdo->beginTransaction();
            $current = $this->lockProblem($problemId);
            if (!$current) {
                throw new RuntimeException('Problem not found.');
            }
            if ((int)$current['version'] !== $expectedVersion) {
                $this->rollback();
                return $this->conflict('The problem changed. Reload and try again.', (int)$current['version']);
            }
            $status = (string)$current['clinical_status'];
            $verification = (string)$current['verification_status'];
            if ($status === 'Entered-in-error') {
                $this->rollback();
                return $this->failure(['Entered-in-error problems are terminal.']);
            }
            $allowed = match ($operation) {
                'verify', 'refute', 'resolve', 'deactivate' => $status === 'Active',
                'reactivate' => in_array($status, ['Inactive', 'Resolved'], true),
                'entered_error' => true,
                default => false
            };
            if (!$allowed || ($operation === 'verify' && $verification === 'Confirmed')) {
                $this->rollback();
                return $this->failure(['The requested problem transition is not available.']);
            }
            if ($operation === 'verify'
                && !$this->settingsService->getBoolean('problem_list.allow_self_verification', false)
                && $this->latestProblemAuthorId($problemId, $current) === $actorId
            ) {
                $this->rollback();
                return $this->failure(['The latest problem author cannot verify the same problem.']);
            }
            $resolvedDate = $resolvedDate !== null && trim($resolvedDate) !== '' ? trim($resolvedDate) : date('Y-m-d');
            if ($operation === 'resolve'
                && (!$this->validDate($resolvedDate)
                    || ($current['onset_date'] !== null && $resolvedDate < (string)$current['onset_date']))
            ) {
                $this->rollback();
                return $this->failure(['Resolved date is invalid or precedes onset.']);
            }
            $visit = $this->lockVisitForPatient($visitId, (int)$current['patient_id']);
            if ($operation === 'reactivate') {
                $this->lockMatchingProblems((int)$current['patient_id'], (string)$current['normalized_problem_name'], (string)$current['category'], $problemId);
            }
            $newVersion = $expectedVersion + 1;
            [$setClause, $params, $historyAction, $auditAction, $eventTitle] = match ($operation) {
                'verify' => ["verification_status='Confirmed',verified_by=:actor_id,verified_at=NOW()", [':actor_id' => $actorId], 'Verified', 'PROBLEM_VERIFIED', 'Problem Verified'],
                'refute' => ["verification_status='Refuted',verified_by=:actor_id,verified_at=NOW()", [':actor_id' => $actorId], 'Refuted', 'PROBLEM_REFUTED', null],
                'deactivate' => ["clinical_status='Inactive',active_problem_key=NULL", [], 'Deactivated', 'PROBLEM_DEACTIVATED', null],
                'reactivate' => ["clinical_status='Active',active_problem_key=:active_key,resolved_date=NULL,resolved_by=NULL", [':active_key' => $this->problemKey($current)], 'Reactivated', 'PROBLEM_REACTIVATED', 'Problem Reactivated'],
                'resolve' => ["clinical_status='Resolved',active_problem_key=NULL,resolved_date=:resolved_date,resolved_by=:actor_id", [':resolved_date' => $resolvedDate, ':actor_id' => $actorId], 'Resolved', 'PROBLEM_RESOLVED', 'Problem Resolved'],
                default => ["clinical_status='Entered-in-error',verification_status='Refuted',active_problem_key=NULL,verified_by=:actor_id,verified_at=NOW()", [':actor_id' => $actorId], 'EnteredInError', 'PROBLEM_ENTERED_IN_ERROR', null]
            };
            $stmt = $this->pdo->prepare('UPDATE patient_problems SET ' . $setClause . ',version=:version WHERE id=:id AND version=:expected_version');
            $stmt->execute($params + [':version' => $newVersion, ':id' => $problemId, ':expected_version' => $expectedVersion]);
            $this->assertAffected($stmt, 'Concurrent problem transition detected.');
            $updated = $this->getProblemInternal($problemId);
            $this->recordProblemHistory($updated, $current, $historyAction, $reason, $actorId, $departmentId, $visitId);
            $this->audit($actorId, (int)$current['patient_id'], $visitId, $auditAction, str_replace('_', ' ', $auditAction) . '.');
            if ($eventTitle !== null) {
                $this->encounterEvent($visit, $auditAction, $eventTitle, $eventTitle . ' during this encounter.', $actorId);
            }
            $this->pdo->commit();
            return $this->success(['problem_id' => $problemId, 'version' => $newVersion]);
        } catch (PDOException $exception) {
            $this->rollback();
            return $this->failure([(string)$exception->getCode() === '23000' ? 'An active duplicate prevents this transition.' : 'Unable to change problem status.']);
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure([$this->safeWriteError($exception, 'Unable to change problem status.')]);
        }
    }

    private function updateHistoryRecord(int $entryId, array $data, int $expectedVersion, int $actorId, ?int $departmentId, string $action): array
    {
        if (trim((string)($data['reason'] ?? '')) === '') {
            return $this->failure(['A change reason is required.']);
        }
        try {
            $this->pdo->beginTransaction();
            $current = $this->lockHistoryEntry($entryId);
            if (!$current) {
                throw new RuntimeException('Medical history entry not found.');
            }
            if ((int)$current['version'] !== $expectedVersion) {
                $this->rollback();
                return $this->conflict('The medical history entry changed. Reload and try again.', (int)$current['version']);
            }
            if ((string)$current['status'] === 'Entered-in-error') {
                $this->rollback();
                return $this->failure(['Entered-in-error medical history is terminal.']);
            }
            $prepared = $this->prepareHistory(array_merge($current, $data));
            $errors = $this->validateHistory($prepared, $actorId);
            if ($errors !== []) {
                $this->rollback();
                return $this->failure($errors);
            }
            $visitId = $this->nullableInt($data['visit_id'] ?? null);
            $visit = $this->lockVisitForPatient($visitId, (int)$current['patient_id']);
            $reset = $current['verified_by'] !== null && $this->historyMateriallyChanged($current, $prepared);
            $newVersion = $expectedVersion + 1;
            $stmt = $this->pdo->prepare('
                UPDATE patient_medical_history SET history_type=:history_type,
                    title=:title,normalized_title=:normalized_title,
                    description=:description,event_date=:event_date,
                    date_precision=:date_precision,status=:status,source=:source,
                    confidentiality_level=:confidentiality_level,
                    verified_by=:verified_by,verified_at=:verified_at,version=:version
                WHERE id=:id AND version=:expected_version
            ');
            $stmt->execute([
                ':history_type' => $prepared['history_type'], ':title' => $prepared['title'],
                ':normalized_title' => $prepared['normalized_title'], ':description' => $prepared['description'],
                ':event_date' => $prepared['event_date'], ':date_precision' => $prepared['date_precision'],
                ':status' => $prepared['status'], ':source' => $prepared['source'],
                ':confidentiality_level' => $prepared['confidentiality_level'],
                ':verified_by' => $reset ? null : $current['verified_by'], ':verified_at' => $reset ? null : $current['verified_at'],
                ':version' => $newVersion, ':id' => $entryId, ':expected_version' => $expectedVersion
            ]);
            $this->assertAffected($stmt, 'Concurrent medical-history update detected.');
            $updated = $this->getHistoryInternal($entryId);
            $historyAction = $reset ? $action . 'VerificationReset' : $action;
            $reason = (string)$prepared['reason'] . ($reset ? ' Verification reset after material correction.' : '');
            $this->recordMedicalHistoryVersion($updated, $current, $historyAction, $reason, $actorId, $departmentId, $visitId);
            $auditAction = $action === 'Corrected' ? 'MEDICAL_HISTORY_CORRECTED' : 'MEDICAL_HISTORY_UPDATED';
            $this->audit($actorId, (int)$current['patient_id'], $visitId, $auditAction, $action === 'Corrected' ? 'Corrected structured medical history.' : 'Updated structured medical history.');
            if ($action === 'Corrected') {
                $this->encounterEvent($visit, 'MEDICAL_HISTORY_CORRECTED', 'Medical History Corrected', 'Structured medical history was corrected during this encounter.', $actorId);
            }
            $this->pdo->commit();
            return $this->success(['history_entry_id' => $entryId, 'version' => $newVersion, 'verification_reset' => $reset]);
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure([$this->safeWriteError($exception, 'Unable to update medical history.')]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Validation and Persistence
    |--------------------------------------------------------------------------
    */

    private function prepareProblem(array $data): array
    {
        $name = trim((string)($data['problem_name'] ?? ''));
        return [
            'patient_id' => (int)($data['patient_id'] ?? 0),
            'source_visit_id' => $this->nullableInt($data['source_visit_id'] ?? null),
            'problem_code_system' => $this->nullableText($data['problem_code_system'] ?? null),
            'problem_code' => $this->nullableText($data['problem_code'] ?? null),
            'problem_name' => $name,
            'normalized_problem_name' => $this->normalize($name),
            'category' => trim((string)($data['category'] ?? 'Other')),
            'severity' => trim((string)($data['severity'] ?? 'Unknown')),
            'confidentiality_level' => trim((string)($data['confidentiality_level'] ?? 'Standard')),
            'onset_date' => $this->nullableText($data['onset_date'] ?? null),
            'recorded_date' => trim((string)($data['recorded_date'] ?? date('Y-m-d'))),
            'notes' => $this->nullableText($data['notes'] ?? null),
            'reason' => trim((string)($data['reason'] ?? ''))
        ];
    }

    private function validateProblem(array $data, int $actorId): array
    {
        $errors = [];
        if ($data['patient_id'] <= 0 || $actorId <= 0) {
            $errors[] = 'Patient and authenticated user are required.';
        }
        if ($data['problem_name'] === '' || mb_strlen($data['problem_name']) > 200) {
            $errors[] = 'Problem name is required and must not exceed 200 characters.';
        }
        if (!in_array($data['category'], $this->getAllowedProblemCategories(), true)) {
            $errors[] = 'Select a supported problem category.';
        }
        if (!in_array($data['severity'], $this->getAllowedProblemSeverities(), true)) {
            $errors[] = 'Select a supported problem severity.';
        }
        if (!in_array($data['confidentiality_level'], $this->getAllowedConfidentialityLevels(), true)) {
            $errors[] = 'Select a supported confidentiality level.';
        }
        if (($data['problem_code_system'] === null) !== ($data['problem_code'] === null)) {
            $errors[] = 'Problem code system and code must be supplied together.';
        }
        if ($data['onset_date'] !== null && !$this->validDate($data['onset_date'])) {
            $errors[] = 'Onset date is invalid.';
        }
        if (!$this->validDate($data['recorded_date'])) {
            $errors[] = 'Recorded date is invalid.';
        }
        if ($data['reason'] === '') {
            $errors[] = 'A reason is required.';
        }
        return $errors;
    }

    private function prepareHistory(array $data): array
    {
        $title = trim((string)($data['title'] ?? ''));
        return [
            'patient_id' => (int)($data['patient_id'] ?? 0),
            'source_visit_id' => $this->nullableInt($data['source_visit_id'] ?? null),
            'history_type' => trim((string)($data['history_type'] ?? '')),
            'title' => $title,
            'normalized_title' => $this->normalize($title),
            'description' => trim((string)($data['description'] ?? '')),
            'event_date' => $this->nullableText($data['event_date'] ?? null),
            'date_precision' => trim((string)($data['date_precision'] ?? 'Unknown')),
            'status' => trim((string)($data['status'] ?? 'Historical')),
            'source' => $this->nullableText($data['source'] ?? null),
            'confidentiality_level' => trim((string)($data['confidentiality_level'] ?? 'Standard')),
            'reason' => trim((string)($data['reason'] ?? ''))
        ];
    }

    private function validateHistory(array $data, int $actorId): array
    {
        $errors = [];
        if ($data['patient_id'] <= 0 || $actorId <= 0) {
            $errors[] = 'Patient and authenticated user are required.';
        }
        if (!in_array($data['history_type'], $this->getAllowedHistoryTypes(), true)) {
            $errors[] = 'Select a supported medical-history type.';
        }
        if ($data['title'] === '' || mb_strlen($data['title']) > 200) {
            $errors[] = 'Title is required and must not exceed 200 characters.';
        }
        if ($data['description'] === '' || mb_strlen($data['description']) > 10000) {
            $errors[] = 'Description is required and must not exceed 10,000 characters.';
        }
        if ($data['event_date'] !== null && !$this->validDate($data['event_date'])) {
            $errors[] = 'Event date is invalid.';
        }
        if (!in_array($data['date_precision'], self::DATE_PRECISIONS, true)) {
            $errors[] = 'Select a supported date precision.';
        }
        if (!in_array($data['status'], self::HISTORY_STATUSES, true)) {
            $errors[] = 'Select a supported history status.';
        }
        if (!in_array($data['confidentiality_level'], $this->getAllowedConfidentialityLevels(), true)) {
            $errors[] = 'Select a supported confidentiality level.';
        }
        if ($data['reason'] === '') {
            $errors[] = 'A reason is required.';
        }
        return $errors;
    }

    private function recordProblemHistory(array $current, ?array $previous, string $action, string $reason, int $actorId, ?int $departmentId, ?int $visitId): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO patient_problem_history (problem_id,patient_id,version_no,action,previous_snapshot,new_snapshot,reason,confidentiality_level,changed_by,department_id,visit_id) VALUES (:record_id,:patient_id,:version_no,:action,:previous_snapshot,:new_snapshot,:reason,:confidentiality_level,:actor_id,:department_id,:visit_id)');
        $stmt->execute($this->versionParameters($current, $previous, $action, $reason, $actorId, $departmentId, $visitId));
    }

    private function recordMedicalHistoryVersion(array $current, ?array $previous, string $action, string $reason, int $actorId, ?int $departmentId, ?int $visitId): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO patient_medical_history_versions (history_entry_id,patient_id,version_no,action,previous_snapshot,new_snapshot,reason,confidentiality_level,changed_by,department_id,visit_id) VALUES (:record_id,:patient_id,:version_no,:action,:previous_snapshot,:new_snapshot,:reason,:confidentiality_level,:actor_id,:department_id,:visit_id)');
        $stmt->execute($this->versionParameters($current, $previous, $action, $reason, $actorId, $departmentId, $visitId));
    }

    private function versionParameters(array $current, ?array $previous, string $action, string $reason, int $actorId, ?int $departmentId, ?int $visitId): array
    {
        return [
            ':record_id' => $current['id'], ':patient_id' => $current['patient_id'], ':version_no' => $current['version'],
            ':action' => $action, ':previous_snapshot' => $previous ? json_encode($previous, JSON_THROW_ON_ERROR) : null,
            ':new_snapshot' => json_encode($current, JSON_THROW_ON_ERROR), ':reason' => trim($reason),
            ':confidentiality_level' => $current['confidentiality_level'], ':actor_id' => $actorId,
            ':department_id' => $departmentId, ':visit_id' => $visitId
        ];
    }

    private function audit(int $actorId, int $patientId, ?int $visitId, string $action, string $description): void
    {
        if (!$this->auditService->logPatient($actorId, $patientId, $visitId, 'Medical Records', $action, $description, null, 'INFO', $action)) {
            throw new RuntimeException('Unable to write audit log.');
        }
    }

    private function encounterEvent(?array $visit, string $type, string $title, string $description, int $actorId): void
    {
        if ($visit === null) {
            return;
        }
        $result = $this->eventService->record((int)$visit['id'], $type, $title, $description, isset($visit['department_id']) ? (int)$visit['department_id'] : null, $actorId);
        if (!($result['success'] ?? false)) {
            throw new RuntimeException('Unable to write encounter event.');
        }
    }

    private function authorizeRecord(array $record, array $user, string $kind, bool $auditAccess): array
    {
        $patientId = (int)$record['patient_id'];
        $allowed = $kind === 'problem'
            ? $this->permissionService->canViewProblemList($patientId, $user)
            : $this->permissionService->canViewStructuredMedicalHistory($patientId, $user);
        if (!$allowed) {
            return $this->forbidden('You do not have permission to view this medical record.');
        }
        $canViewConfidential = $this->permissionService->canViewConfidentialMedicalHistory($patientId, $user);
        $protected = $this->protectRecord($record, $canViewConfidential);
        if ($auditAccess && $this->isRestricted($record) && $canViewConfidential) {
            $audit = $this->recordReadAudit($patientId, (int)$user['id'], $this->nullableInt($record['source_visit_id'] ?? null), 'CONFIDENTIAL_MEDICAL_HISTORY_VIEWED', 'Viewed authorized confidential longitudinal medical information.');
            if (!($audit['success'] ?? false)) {
                return $audit;
            }
        }
        return $this->success([$kind === 'problem' ? 'problem' : 'entry' => $protected]);
    }

    private function recordReadAudit(int $patientId, int $actorId, ?int $visitId, string $action, string $description): array
    {
        try {
            $this->pdo->beginTransaction();
            $this->lockPatient($patientId);
            $this->lockVisitForPatient($visitId, $patientId);
            $this->audit($actorId, $patientId, $visitId, $action, $description);
            $this->pdo->commit();
            return $this->success(['patient_id' => $patientId]);
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure(['Unable to record protected medical-record access.']) + ['audit_failed' => true];
        }
    }

    private function queryProblems(int $patientId, bool $includeInactive): array
    {
        $sql = 'SELECT * FROM patient_problems WHERE patient_id=:patient_id';
        if (!$includeInactive) {
            $sql .= " AND clinical_status='Active'";
        }
        $sql .= " ORDER BY FIELD(clinical_status,'Active','Inactive','Resolved','Entered-in-error'),FIELD(severity,'Severe','Moderate','Mild','Unknown'),recorded_date DESC,id DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':patient_id' => $patientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function queryMedicalHistory(int $patientId, bool $includeEnteredInError): array
    {
        $sql = 'SELECT * FROM patient_medical_history WHERE patient_id=:patient_id';
        if (!$includeEnteredInError) {
            $sql .= " AND status<>'Entered-in-error'";
        }
        $sql .= ' ORDER BY history_type,event_date DESC,created_at DESC,id DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':patient_id' => $patientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function queryProblemHistory(int $problemId): array
    {
        $stmt = $this->pdo->prepare('SELECT h.*,CONCAT(u.first_name," ",u.last_name) actor_name,d.department_name FROM patient_problem_history h INNER JOIN users u ON u.id=h.changed_by LEFT JOIN departments d ON d.id=h.department_id WHERE h.problem_id=:id ORDER BY h.version_no DESC');
        $stmt->execute([':id' => $problemId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function queryMedicalHistoryVersions(int $entryId): array
    {
        $stmt = $this->pdo->prepare('SELECT h.*,CONCAT(u.first_name," ",u.last_name) actor_name,d.department_name FROM patient_medical_history_versions h INNER JOIN users u ON u.id=h.changed_by LEFT JOIN departments d ON d.id=h.department_id WHERE h.history_entry_id=:id ORDER BY h.version_no DESC');
        $stmt->execute([':id' => $entryId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function protectRecord(array $record, bool $canViewConfidential): array
    {
        $hidden = $this->isRestricted($record) && !$canViewConfidential;
        $record['confidential_hidden'] = $hidden;
        if ($hidden) {
            foreach (['problem_name', 'problem_code_system', 'problem_code', 'notes', 'title', 'description', 'source'] as $field) {
                if (array_key_exists($field, $record)) {
                    $record[$field] = in_array($field, ['problem_name', 'title'], true) ? 'Confidential medical information' : null;
                }
            }
        }
        return $record;
    }

    private function protectVersion(array $version, bool $canViewConfidential): array
    {
        $hidden = in_array((string)$version['confidentiality_level'], ['Restricted', 'Confidential'], true) && !$canViewConfidential;
        $version['confidential_hidden'] = $hidden;
        if ($hidden) {
            $version['previous_snapshot'] = null;
            $version['new_snapshot'] = null;
            $version['reason'] = 'Confidential version details are hidden.';
        }
        return $version;
    }

    private function containsRestrictedVersion(array $versions): bool
    {
        foreach ($versions as $version) {
            if (in_array((string)($version['confidentiality_level'] ?? 'Standard'), ['Restricted', 'Confidential'], true)) {
                return true;
            }
        }
        return false;
    }

    private function isRestricted(array $record): bool
    {
        return in_array((string)($record['confidentiality_level'] ?? 'Standard'), ['Restricted', 'Confidential'], true);
    }

    private function lockPatient(int $patientId): void
    {
        $stmt = $this->pdo->prepare('SELECT id FROM patients WHERE id=:id FOR UPDATE');
        $stmt->execute([':id' => $patientId]);
        if (!$stmt->fetchColumn()) {
            throw new RuntimeException('Patient not found.');
        }
    }

    private function lockVisitForPatient(?int $visitId, int $patientId): ?array
    {
        if ($visitId === null) {
            return null;
        }
        $stmt = $this->pdo->prepare('SELECT id,patient_id,current_department_id AS department_id FROM visits WHERE id=:id FOR UPDATE');
        $stmt->execute([':id' => $visitId]);
        $visit = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$visit || (int)$visit['patient_id'] !== $patientId) {
            throw new RuntimeException('Encounter does not belong to this patient.');
        }
        return $visit;
    }

    private function lockProblem(int $problemId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM patient_problems WHERE id=:id FOR UPDATE');
        $stmt->execute([':id' => $problemId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function lockHistoryEntry(int $entryId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM patient_medical_history WHERE id=:id FOR UPDATE');
        $stmt->execute([':id' => $entryId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function lockMatchingProblems(int $patientId, string $name, string $category, ?int $excludeId = null): void
    {
        $sql = "SELECT id FROM patient_problems WHERE patient_id=:patient_id AND normalized_problem_name=:name AND category=:category AND clinical_status='Active'";
        $params = [':patient_id' => $patientId, ':name' => $name, ':category' => $category];
        if ($excludeId !== null) {
            $sql .= ' AND id<>:exclude_id';
            $params[':exclude_id'] = $excludeId;
        }
        $sql .= ' ORDER BY id FOR UPDATE';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->fetchColumn()) {
            throw new RuntimeException('Duplicate active problem.');
        }
    }

    private function getProblemInternal(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM patient_problems WHERE id=:id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function getHistoryInternal(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM patient_medical_history WHERE id=:id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function latestProblemAuthorId(int $problemId, array $problem): int
    {
        $stmt = $this->pdo->prepare('SELECT changed_by FROM patient_problem_history WHERE problem_id=:id ORDER BY version_no DESC LIMIT 1 FOR UPDATE');
        $stmt->execute([':id' => $problemId]);
        $id = $stmt->fetchColumn();
        return $id !== false ? (int)$id : (int)$problem['recorded_by'];
    }

    private function latestHistoryAuthorId(int $entryId, array $entry): int
    {
        $stmt = $this->pdo->prepare('SELECT changed_by FROM patient_medical_history_versions WHERE history_entry_id=:id ORDER BY version_no DESC LIMIT 1 FOR UPDATE');
        $stmt->execute([':id' => $entryId]);
        $id = $stmt->fetchColumn();
        return $id !== false ? (int)$id : (int)$entry['recorded_by'];
    }

    private function problemMateriallyChanged(array $current, array $prepared): bool
    {
        foreach (['problem_code_system','problem_code','normalized_problem_name','category','severity','onset_date','notes'] as $field) {
            if ((string)($current[$field] ?? '') !== (string)($prepared[$field] ?? '')) {
                return true;
            }
        }
        return false;
    }

    private function historyMateriallyChanged(array $current, array $prepared): bool
    {
        foreach (['history_type','normalized_title','description','event_date','date_precision','status','source'] as $field) {
            if ((string)($current[$field] ?? '') !== (string)($prepared[$field] ?? '')) {
                return true;
            }
        }
        return false;
    }

    private function problemKey(array $problem): string
    {
        return $problem['patient_id'] . '|' . $problem['category'] . '|' . $problem['normalized_problem_name'];
    }

    private function settingSubset(string $key, array $supported): array
    {
        $configured = $this->settingsService->getArray($key, $supported);
        $allowed = array_values(array_unique(array_intersect($configured, $supported)));
        return $allowed !== [] ? $allowed : $supported;
    }

    private function normalize(string $value): string
    {
        return preg_replace('/\s+/u', ' ', mb_strtolower(trim($value), 'UTF-8')) ?? '';
    }

    private function validDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }

    private function nullableInt(mixed $value): ?int
    {
        $value = (int)$value;
        return $value > 0 ? $value : null;
    }

    private function nullableText(mixed $value): ?string
    {
        $value = trim((string)$value);
        return $value === '' ? null : $value;
    }

    private function assertAffected(PDOStatement $statement, string $message): void
    {
        if ($statement->rowCount() !== 1) {
            throw new RuntimeException($message);
        }
    }

    private function safeWriteError(Throwable $exception, string $fallback): string
    {
        return match ($exception->getMessage()) {
            'Patient not found.' => 'Patient not found.',
            'Encounter does not belong to this patient.' => 'The selected encounter does not belong to this patient.',
            'Duplicate active problem.' => 'An active problem with this name and category already exists.',
            default => $fallback
        };
    }

    private function success(array $data): array
    {
        return ['success' => true, 'data' => $data, 'errors' => []] + $data;
    }

    private function failure(array $errors): array
    {
        return ['success' => false, 'data' => null, 'errors' => $errors];
    }

    private function conflict(string $message, int $version): array
    {
        return ['success' => false, 'data' => null, 'conflict' => true, 'current_version' => $version, 'errors' => [$message]];
    }

    private function forbidden(string $message): array
    {
        return ['success' => false, 'data' => null, 'forbidden' => true, 'errors' => [$message]];
    }

    private function rollback(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }
}
