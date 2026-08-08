<?php

declare(strict_types=1);

require_once __DIR__ . '/AuditService.php';
require_once __DIR__ . '/EncounterEventService.php';
require_once __DIR__ . '/PermissionService.php';
require_once __DIR__ . '/SettingsService.php';

class ClinicalSafetyService
{
    private PDO $pdo;
    private AuditService $auditService;
    private EncounterEventService $eventService;
    private PermissionService $permissionService;
    private SettingsService $settingsService;

    public function __construct(
        PDO $pdo,
        ?AuditService $auditService = null,
        ?EncounterEventService $eventService = null,
        ?SettingsService $settingsService = null,
        ?PermissionService $permissionService = null
    ) {
        $this->pdo = $pdo;
        $this->auditService = $auditService ?? new AuditService($pdo);
        $this->eventService = $eventService ?? new EncounterEventService($pdo);
        $this->settingsService = $settingsService ?? new SettingsService($pdo);
        $this->permissionService = $permissionService ?? new PermissionService($pdo);
    }

    /*
    |--------------------------------------------------------------------------
    | Allergy Commands
    |--------------------------------------------------------------------------
    */

    public function recordAllergy(array $data, int $actorId): array
    {
        $prepared = $this->prepareAllergy($data);
        $errors = $this->validateAllergy($prepared, $actorId);
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
            $this->lockMatchingAllergies(
                (int)$prepared['patient_id'],
                (string)$prepared['allergy_type'],
                (string)$prepared['normalized_substance']
            );

            $stmt = $this->pdo->prepare('
                INSERT INTO patient_allergies (
                    patient_id, source_visit_id, allergy_type, substance,
                    normalized_substance, active_allergy_key, reaction,
                    severity, clinical_status, verification_status, onset_date,
                    recorded_by, notes
                ) VALUES (
                    :patient_id, :source_visit_id, :allergy_type, :substance,
                    :normalized_substance, :active_allergy_key, :reaction,
                    :severity, \'Active\', \'Unverified\', :onset_date,
                    :recorded_by, :notes
                )
            ');
            $stmt->execute([
                ':patient_id' => $prepared['patient_id'],
                ':source_visit_id' => $prepared['source_visit_id'],
                ':allergy_type' => $prepared['allergy_type'],
                ':substance' => $prepared['substance'],
                ':normalized_substance' => $prepared['normalized_substance'],
                ':active_allergy_key' => $this->allergyKey($prepared),
                ':reaction' => $prepared['reaction'],
                ':severity' => $prepared['severity'],
                ':onset_date' => $prepared['onset_date'],
                ':recorded_by' => $actorId,
                ':notes' => $prepared['notes']
            ]);
            $allergyId = (int)$this->pdo->lastInsertId();
            $created = $this->getAllergyByIdInternal($allergyId);
            $this->recordAllergyHistory(
                $created,
                null,
                'Recorded',
                (string)$prepared['reason'],
                $actorId
            );
            $this->audit(
                $actorId,
                (int)$prepared['patient_id'],
                $prepared['source_visit_id'],
                'ALLERGY_RECORDED',
                'Recorded structured allergy information.'
            );
            $this->recordEncounterEvent(
                $visit,
                'ALLERGY_RECORDED',
                'Allergy Recorded',
                'Structured allergy information was recorded.',
                $actorId
            );
            $this->pdo->commit();

            return $this->success([
                'allergy_id' => $allergyId,
                'patient_id' => (int)$prepared['patient_id']
            ]);
        } catch (PDOException $exception) {
            $this->rollback();
            return $this->failure([
                (string)$exception->getCode() === '23000'
                    ? 'An active allergy to this substance is already recorded.'
                    : 'Unable to record allergy information.'
            ]);
        } catch (RuntimeException $exception) {
            $this->rollback();
            return $this->failure([$this->safeAllergyError($exception)]);
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure(['Unable to record allergy information.']);
        }
    }

    public function updateAllergy(
        int $allergyId,
        array $data,
        int $expectedVersion,
        int $actorId
    ): array {
        if (trim((string)($data['reason'] ?? '')) === '') {
            return $this->failure(['A reason is required.']);
        }

        try {
            $this->pdo->beginTransaction();
            $current = $this->lockAllergy($allergyId);
            if (!$current) {
                throw new RuntimeException('Allergy not found.');
            }
            if ((int)$current['version'] !== $expectedVersion) {
                $this->rollback();
                return $this->conflict('The allergy record changed. Reload and try again.', (int)$current['version']);
            }
            if ((string)$current['clinical_status'] !== 'Active') {
                $this->rollback();
                return $this->failure(['Only active allergy records can be updated.']);
            }

            $prepared = $this->prepareAllergy(array_merge($current, $data));
            $errors = $this->validateAllergy($prepared, $actorId);
            if ($errors !== []) {
                $this->rollback();
                return $this->failure($errors);
            }
            $eventVisitId = $this->nullableInt($data['visit_id'] ?? null);
            $visit = $this->lockVisitForPatient($eventVisitId, (int)$current['patient_id']);
            $this->lockMatchingAllergies(
                (int)$current['patient_id'],
                (string)$prepared['allergy_type'],
                (string)$prepared['normalized_substance'],
                $allergyId
            );
            $newVersion = $expectedVersion + 1;
            $verificationReset = (string)$current['verification_status'] === 'Confirmed'
                && $this->isMaterialAllergyChange($current, $prepared);
            $stmt = $this->pdo->prepare('
                UPDATE patient_allergies SET
                    allergy_type=:allergy_type, substance=:substance,
                    normalized_substance=:normalized_substance,
                    active_allergy_key=:active_allergy_key,
                    reaction=:reaction, severity=:severity,
                    onset_date=:onset_date, notes=:notes,
                    verification_status=:verification_status,
                    verified_by=:verified_by, verified_at=:verified_at,
                    version=:version
                WHERE id=:id AND version=:expected_version
            ');
            $stmt->execute([
                ':allergy_type' => $prepared['allergy_type'],
                ':substance' => $prepared['substance'],
                ':normalized_substance' => $prepared['normalized_substance'],
                ':active_allergy_key' => $this->allergyKey($prepared),
                ':reaction' => $prepared['reaction'],
                ':severity' => $prepared['severity'],
                ':onset_date' => $prepared['onset_date'],
                ':notes' => $prepared['notes'],
                ':verification_status' => $verificationReset
                    ? 'Unverified' : $current['verification_status'],
                ':verified_by' => $verificationReset ? null : $current['verified_by'],
                ':verified_at' => $verificationReset ? null : $current['verified_at'],
                ':version' => $newVersion,
                ':id' => $allergyId,
                ':expected_version' => $expectedVersion
            ]);
            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException('Concurrent allergy update detected.');
            }
            $updated = $this->getAllergyByIdInternal($allergyId);
            $historyAction = $verificationReset ? 'UpdatedVerificationReset' : 'Updated';
            $historyReason = (string)$prepared['reason']
                . ($verificationReset ? ' Verification reset after clinically significant change.' : '');
            $this->recordAllergyHistory($updated, $current, $historyAction, $historyReason, $actorId);
            $this->audit(
                $actorId,
                (int)$current['patient_id'],
                $eventVisitId,
                'ALLERGY_UPDATED',
                $verificationReset
                    ? 'Updated structured allergy information and reset clinical verification.'
                    : 'Updated structured allergy information.'
            );
            if ($verificationReset) {
                $this->recordEncounterEvent(
                    $visit,
                    'ALLERGY_VERIFICATION_RESET',
                    'Allergy Verification Reset',
                    'Allergy verification was reset after a clinically significant update.',
                    $actorId
                );
            }
            $this->pdo->commit();

            return $this->success(['allergy_id' => $allergyId, 'version' => $newVersion]);
        } catch (PDOException $exception) {
            $this->rollback();
            return $this->failure([(string)$exception->getCode() === '23000'
                ? 'An active allergy to this substance is already recorded.'
                : 'Unable to update allergy information.']);
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure(['Unable to update allergy information.']);
        }
    }

    public function verifyAllergy(
        int $allergyId,
        string $reason,
        int $actorId,
        int $expectedVersion,
        ?int $visitId = null
    ): array {
        return $this->transitionAllergy(
            $allergyId,
            $reason,
            $actorId,
            $expectedVersion,
            $visitId,
            'Verified',
            "verification_status='Confirmed', verified_by=:actor_id, verified_at=NOW()",
            'ALLERGY_VERIFIED',
            'Allergy Verified'
        );
    }

    public function resolveAllergy(
        int $allergyId,
        string $reason,
        int $actorId,
        int $expectedVersion,
        ?int $visitId = null
    ): array {
        return $this->transitionAllergy(
            $allergyId,
            $reason,
            $actorId,
            $expectedVersion,
            $visitId,
            'Resolved',
            "clinical_status='Resolved', active_allergy_key=NULL, resolved_by=:actor_id, resolved_at=NOW()",
            'ALLERGY_RESOLVED',
            'Allergy Resolved'
        );
    }

    public function markAllergyEnteredInError(
        int $allergyId,
        string $reason,
        int $actorId,
        int $expectedVersion,
        ?int $visitId = null
    ): array {
        return $this->transitionAllergy(
            $allergyId,
            $reason,
            $actorId,
            $expectedVersion,
            $visitId,
            'EnteredInError',
            "clinical_status='Entered-in-error', verification_status='Refuted', active_allergy_key=NULL, resolved_by=:actor_id, resolved_at=NOW()",
            'ALLERGY_ENTERED_IN_ERROR',
            null
        );
    }

    public function deactivateAllergy(
        int $allergyId,
        string $reason,
        int $actorId,
        int $expectedVersion,
        ?int $visitId = null
    ): array {
        return $this->changeAllergyActivity(
            $allergyId,
            $reason,
            $actorId,
            $expectedVersion,
            $visitId,
            false
        );
    }

    public function reactivateAllergy(
        int $allergyId,
        string $reason,
        int $actorId,
        int $expectedVersion,
        ?int $visitId = null
    ): array {
        return $this->changeAllergyActivity(
            $allergyId,
            $reason,
            $actorId,
            $expectedVersion,
            $visitId,
            true
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Alert Commands
    |--------------------------------------------------------------------------
    */

    public function createAlert(array $data, int $actorId): array
    {
        $prepared = $this->prepareAlert($data);
        $errors = $this->validateAlert($prepared, $actorId);
        if ($errors !== []) {
            return $this->failure($errors);
        }

        try {
            $this->pdo->beginTransaction();
            $this->lockPatient((int)$prepared['patient_id']);
            $visit = $this->lockVisitForPatient($prepared['visit_id'], (int)$prepared['patient_id']);
            $this->lockMatchingAlerts((int)$prepared['patient_id'], (string)$prepared['alert_type'], (string)$prepared['normalized_title']);
            $stmt = $this->pdo->prepare('
                INSERT INTO patient_alerts (
                    patient_id, visit_id, alert_type, title, normalized_title,
                    active_alert_key, reason, priority, confidentiality_level,
                    starts_at, expires_at, created_by
                ) VALUES (
                    :patient_id,:visit_id,:alert_type,:title,:normalized_title,
                    :active_alert_key,:reason,:priority,:confidentiality_level,
                    :starts_at,:expires_at,:created_by
                )
            ');
            $stmt->execute([
                ':patient_id' => $prepared['patient_id'],
                ':visit_id' => $prepared['visit_id'],
                ':alert_type' => $prepared['alert_type'],
                ':title' => $prepared['title'],
                ':normalized_title' => $prepared['normalized_title'],
                ':active_alert_key' => $this->alertKey($prepared),
                ':reason' => $prepared['reason'],
                ':priority' => $prepared['priority'],
                ':confidentiality_level' => $prepared['confidentiality_level'],
                ':starts_at' => $prepared['starts_at'],
                ':expires_at' => $prepared['expires_at'],
                ':created_by' => $actorId
            ]);
            $alertId = (int)$this->pdo->lastInsertId();
            $created = $this->getAlertByIdInternal($alertId);
            $this->recordAlertHistory($created, null, 'Created', (string)$prepared['change_reason'], $actorId);
            $this->audit($actorId, (int)$prepared['patient_id'], $prepared['visit_id'], 'CLINICAL_ALERT_CREATED', 'Created a clinical safety alert.');
            $this->recordEncounterEvent($visit, 'CLINICAL_ALERT_CREATED', 'Clinical Alert Created', 'A clinical safety alert was created.', $actorId);
            $this->pdo->commit();

            return $this->success(['alert_id' => $alertId, 'patient_id' => (int)$prepared['patient_id']]);
        } catch (PDOException $exception) {
            $this->rollback();
            return $this->failure([(string)$exception->getCode() === '23000'
                ? 'An active alert with this type and title already exists.'
                : 'Unable to create clinical alert.']);
        } catch (RuntimeException $exception) {
            $this->rollback();
            return $this->failure([$this->safeAlertError($exception)]);
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure(['Unable to create clinical alert.']);
        }
    }

    public function updateAlert(
        int $alertId,
        array $data,
        int $expectedVersion,
        int $actorId
    ): array {
        if (trim((string)($data['change_reason'] ?? '')) === '') {
            return $this->failure(['A change reason is required.']);
        }
        try {
            $this->pdo->beginTransaction();
            $current = $this->lockAlert($alertId);
            if (!$current) {
                throw new RuntimeException('Alert not found.');
            }
            if ((int)$current['version'] !== $expectedVersion) {
                $this->rollback();
                return $this->conflict('The alert changed. Reload and try again.', (int)$current['version']);
            }
            if (!(bool)$current['is_active']) {
                $this->rollback();
                return $this->failure(['Only active alerts can be updated.']);
            }
            $prepared = $this->prepareAlert(array_merge($current, $data));
            $errors = $this->validateAlert($prepared, $actorId);
            if ($errors !== []) {
                $this->rollback();
                return $this->failure($errors);
            }
            $eventVisitId = $this->nullableInt($data['event_visit_id'] ?? null);
            $this->lockVisitForPatient($eventVisitId, (int)$current['patient_id']);
            $this->lockMatchingAlerts((int)$current['patient_id'], (string)$prepared['alert_type'], (string)$prepared['normalized_title'], $alertId);
            $newVersion = $expectedVersion + 1;
            $stmt = $this->pdo->prepare('
                UPDATE patient_alerts SET alert_type=:alert_type,title=:title,
                    normalized_title=:normalized_title,active_alert_key=:active_alert_key,
                    reason=:reason,priority=:priority,
                    confidentiality_level=:confidentiality_level,
                    starts_at=:starts_at,expires_at=:expires_at,version=:version
                WHERE id=:id AND version=:expected_version
            ');
            $stmt->execute([
                ':alert_type' => $prepared['alert_type'],
                ':title' => $prepared['title'],
                ':normalized_title' => $prepared['normalized_title'],
                ':active_alert_key' => $this->alertKey($prepared),
                ':reason' => $prepared['reason'],
                ':priority' => $prepared['priority'],
                ':confidentiality_level' => $prepared['confidentiality_level'],
                ':starts_at' => $prepared['starts_at'],
                ':expires_at' => $prepared['expires_at'],
                ':version' => $newVersion,
                ':id' => $alertId,
                ':expected_version' => $expectedVersion
            ]);
            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException('Concurrent alert update detected.');
            }
            $updated = $this->getAlertByIdInternal($alertId);
            $this->recordAlertHistory($updated, $current, 'Updated', (string)$prepared['change_reason'], $actorId);
            $this->audit($actorId, (int)$current['patient_id'], $eventVisitId, 'CLINICAL_ALERT_UPDATED', 'Updated a clinical safety alert.');
            $this->pdo->commit();
            return $this->success(['alert_id' => $alertId, 'version' => $newVersion]);
        } catch (PDOException $exception) {
            $this->rollback();
            return $this->failure([(string)$exception->getCode() === '23000'
                ? 'An active alert with this type and title already exists.'
                : 'Unable to update clinical alert.']);
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure(['Unable to update clinical alert.']);
        }
    }

    public function closeAlert(
        int $alertId,
        string $reason,
        int $actorId,
        int $expectedVersion,
        ?int $visitId = null
    ): array {
        return $this->transitionAlert($alertId, $reason, $actorId, $expectedVersion, $visitId, false);
    }

    public function reactivateAlert(
        int $alertId,
        string $reason,
        int $actorId,
        int $expectedVersion,
        ?int $visitId = null
    ): array {
        return $this->transitionAlert($alertId, $reason, $actorId, $expectedVersion, $visitId, true);
    }

    /*
    |--------------------------------------------------------------------------
    | Queries and Banner
    |--------------------------------------------------------------------------
    */

    public function getAllergyById(int $allergyId): ?array
    {
        return $this->getAllergyByIdInternal($allergyId);
    }

    public function getPatientAllergies(int $patientId, bool $includeInactive = true): array
    {
        $sql = 'SELECT * FROM patient_allergies WHERE patient_id=:patient_id';
        if (!$includeInactive) {
            $sql .= " AND clinical_status='Active'";
        }
        $sql .= " ORDER BY FIELD(severity,'Life-threatening','Severe','Moderate','Mild','Unknown'), created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':patient_id' => $patientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllergyHistory(int $allergyId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT h.*,CONCAT(u.first_name," ",u.last_name) AS actor_name
            FROM patient_allergy_history h
            INNER JOIN users u ON u.id=h.changed_by
            WHERE h.allergy_id=:id ORDER BY h.version_no DESC
        ');
        $stmt->execute([':id' => $allergyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAlertById(int $alertId, bool $canViewConfidential = false): ?array
    {
        $alert = $this->getAlertByIdInternal($alertId);
        return $alert ? $this->protectAlert($alert, false) : null;
    }

    public function getAlertByIdForUser(
        int $alertId,
        array $user,
        bool $auditAccess = true
    ): array {
        $alert = $this->getAlertByIdInternal($alertId);
        if (!$alert) {
            return $this->failure(['Clinical alert not found.']);
        }

        $patientId = (int)$alert['patient_id'];
        if (!$this->permissionService->canViewClinicalSafety($patientId, $user)) {
            $this->permissionService->logPatientDenied(
                (int)($user['id'] ?? 0),
                $patientId,
                'CLINICAL_SAFETY_ACCESS_DENIED',
                'Clinical alert access was denied.'
            );
            return $this->forbidden('You do not have permission to view this clinical alert.');
        }

        $canViewConfidential = $this->permissionService->canViewConfidentialAlerts(
            $patientId,
            $user
        );
        $protected = $this->protectAlert($alert, $canViewConfidential);

        if ($auditAccess) {
            $auditResult = $this->recordSafetyAccess(
                $patientId,
                (int)($user['id'] ?? 0),
                $this->nullableInt($alert['visit_id'] ?? null),
                !empty($protected['confidential_hidden'])
                    ? 'CLINICAL_SAFETY_VIEWED'
                    : ($this->isRestrictedAlert($alert)
                        ? 'CONFIDENTIAL_ALERT_VIEWED'
                        : 'CLINICAL_SAFETY_VIEWED'),
                $this->isRestrictedAlert($alert)
                    ? 'Viewed an authorized confidential clinical alert.'
                    : 'Viewed a clinical safety alert.'
            );
            if (!($auditResult['success'] ?? false)) {
                return $auditResult;
            }
        }

        return $this->success(['alert' => $protected]);
    }

    public function getPatientAlerts(
        int $patientId,
        bool $includeInactive = true,
        bool $canViewConfidential = false
    ): array {
        $sql = 'SELECT * FROM patient_alerts WHERE patient_id=:patient_id';
        if (!$includeInactive) {
            $sql .= ' AND is_active=1'
                . ' AND (starts_at IS NULL OR starts_at<=NOW())'
                . ' AND (expires_at IS NULL OR expires_at>NOW())';
        }
        $sql .= " ORDER BY FIELD(priority,'Critical','High','Medium','Low'),created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':patient_id' => $patientId]);
        return array_map(
            fn (array $alert): array => $this->protectAlert($alert, false),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function getPatientAlertsForUser(
        int $patientId,
        array $user,
        bool $includeInactive = true
    ): array {
        if (!$this->permissionService->canViewClinicalSafety($patientId, $user)) {
            return [];
        }
        $sql = 'SELECT * FROM patient_alerts WHERE patient_id=:patient_id';
        if (!$includeInactive) {
            $sql .= ' AND is_active=1'
                . ' AND (starts_at IS NULL OR starts_at<=NOW())'
                . ' AND (expires_at IS NULL OR expires_at>NOW())';
        }
        $sql .= " ORDER BY FIELD(priority,'Critical','High','Medium','Low'),created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':patient_id' => $patientId]);
        $canViewConfidential = $this->permissionService->canViewConfidentialAlerts(
            $patientId,
            $user
        );
        return array_map(
            fn (array $alert): array => $this->protectAlert($alert, $canViewConfidential),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function getAlertHistory(int $alertId): array
    {
        return array_map(
            fn (array $entry): array => $this->protectAlertHistory($entry, false),
            $this->getAlertHistoryInternal($alertId)
        );
    }

    public function getAlertHistoryForUser(int $alertId, array $user): array
    {
        $alert = $this->getAlertByIdInternal($alertId);
        if (!$alert) {
            return $this->failure(['Clinical alert not found.']);
        }
        $patientId = (int)$alert['patient_id'];
        if (!$this->permissionService->canViewClinicalSafetyHistory($patientId, $user)) {
            $this->permissionService->logPatientDenied(
                (int)($user['id'] ?? 0),
                $patientId,
                'CLINICAL_SAFETY_ACCESS_DENIED',
                'Clinical alert history access was denied.'
            );
            return $this->forbidden('You do not have permission to view clinical alert history.');
        }
        $canViewConfidential = $this->permissionService->canViewConfidentialAlerts(
            $patientId,
            $user
        );
        $history = array_map(
            fn (array $entry): array => $this->protectAlertHistory(
                $entry,
                $canViewConfidential
            ),
            $this->getAlertHistoryInternal($alertId)
        );
        $containsConfidential = array_filter(
            $history,
            static fn (array $entry): bool => !empty($entry['requires_confidential_access'])
        ) !== [];
        $auditResult = $this->recordSafetyAccess(
            $patientId,
            (int)($user['id'] ?? 0),
            $this->nullableInt($alert['visit_id'] ?? null),
            $containsConfidential && $canViewConfidential
                ? 'CONFIDENTIAL_ALERT_HISTORY_VIEWED'
                : 'CLINICAL_SAFETY_VIEWED',
            $containsConfidential && $canViewConfidential
                ? 'Viewed authorized confidential clinical alert history.'
                : 'Viewed clinical alert history.'
        );
        if (!($auditResult['success'] ?? false)) {
            return $auditResult;
        }

        return $this->success(['history' => $history, 'alert' => $this->protectAlert($alert, $canViewConfidential)]);
    }

    private function getAlertHistoryInternal(int $alertId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT h.*,CONCAT(u.first_name," ",u.last_name) AS actor_name
            FROM patient_alert_history h
            INNER JOIN users u ON u.id=h.changed_by
            WHERE h.alert_id=:id ORDER BY h.version_no DESC
        ');
        $stmt->execute([':id' => $alertId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSafetyBanner(int $patientId, bool $canViewConfidential = false): array
    {
        return $this->buildSafetyBanner($patientId, false);
    }

    public function getSafetyBannerForUser(
        int $patientId,
        array $user,
        ?int $visitId = null
    ): array {
        if (!$this->permissionService->canViewClinicalSafety($patientId, $user)) {
            $this->permissionService->logPatientDenied(
                (int)($user['id'] ?? 0),
                $patientId,
                'CLINICAL_SAFETY_ACCESS_DENIED',
                'Clinical Safety banner access was denied.'
            );
            return $this->forbidden('You do not have permission to view Clinical Safety information.');
        }

        $result = $this->buildSafetyBanner(
            $patientId,
            $this->permissionService->canViewConfidentialAlerts($patientId, $user)
        );
        if (!($result['success'] ?? false)) {
            return $result;
        }
        $auditResult = $this->recordSafetyAccess(
            $patientId,
            (int)($user['id'] ?? 0),
            $visitId,
            'CLINICAL_SAFETY_VIEWED',
            'Viewed longitudinal clinical safety information.'
        );
        return ($auditResult['success'] ?? false) ? $result : $auditResult;
    }

    private function buildSafetyBanner(int $patientId, bool $canViewConfidential): array
    {
        $patient = $this->pdo->prepare('SELECT id,allergies FROM patients WHERE id=:id');
        $patient->execute([':id' => $patientId]);
        $patientRow = $patient->fetch(PDO::FETCH_ASSOC);
        if (!$patientRow) {
            return $this->failure(['Patient not found.']);
        }

        $allergies = $this->getPatientAllergies($patientId, false);
        $alertsStmt = $this->pdo->prepare('
            SELECT * FROM patient_alerts
            WHERE patient_id=:patient_id AND is_active=1
              AND (starts_at IS NULL OR starts_at<=NOW())
              AND (expires_at IS NULL OR expires_at>NOW())
            ORDER BY FIELD(priority,"Critical","High","Medium","Low"),created_at DESC
        ');
        $alertsStmt->execute([':patient_id' => $patientId]);
        $alerts = array_map(
            fn (array $alert): array => $this->protectAlert($alert, $canViewConfidential),
            $alertsStmt->fetchAll(PDO::FETCH_ASSOC)
        );
        $legacy = $this->settingsService->getBoolean('clinical_safety.legacy_allergy_warning', true)
            ? trim((string)($patientRow['allergies'] ?? ''))
            : '';
        $items = [];

        foreach ($alerts as $alert) {
            $weight = match ((string)$alert['priority']) {
                'Critical' => 500,
                'High' => 330,
                'Medium' => 250,
                default => 150
            };
            $items[] = [
                'kind' => 'alert',
                'weight' => $weight,
                'level' => strtolower((string)$alert['priority']),
                'title' => (string)$alert['title'],
                'detail' => (string)($alert['reason'] ?? ''),
                'confidential_hidden' => (bool)($alert['confidential_hidden'] ?? false)
            ];
        }
        foreach ($allergies as $allergy) {
            $confirmed = (string)$allergy['verification_status'] === 'Confirmed';
            $weight = match ((string)$allergy['severity']) {
                'Life-threatening' => 450,
                'Severe' => 400,
                default => $confirmed ? 300 : 200
            };
            if (!$confirmed && $weight > 200) {
                $weight = 200;
            }
            $items[] = [
                'kind' => 'allergy',
                'weight' => $weight,
                'level' => strtolower(str_replace('-','',(string)$allergy['severity'])),
                'title' => 'Allergy: ' . (string)$allergy['substance'],
                'detail' => trim((string)($allergy['reaction'] ?? ''))
                    . ($confirmed ? ' (Confirmed)' : ' (Unverified)'),
                'confidential_hidden' => false
            ];
        }
        if ($legacy !== '') {
            $items[] = [
                'kind' => 'legacy',
                'weight' => 100,
                'level' => 'legacy',
                'title' => 'Legacy unstructured allergy information',
                'detail' => $legacy,
                'confidential_hidden' => false
            ];
        }
        usort($items, static fn (array $a, array $b): int => $b['weight'] <=> $a['weight']);

        return $this->success([
            'patient_id' => $patientId,
            'items' => $items,
            'active_allergies' => $allergies,
            'active_alerts' => $alerts,
            'legacy_allergy_text' => $legacy,
            'has_safety_information' => $items !== []
        ]);
    }

    public function getActiveSafetySummary(int $patientId, bool $canViewConfidential = false): array
    {
        return $this->getSafetyBanner($patientId, $canViewConfidential);
    }

    public function recordSafetyView(int $patientId, int $actorId, ?int $visitId = null): array
    {
        return $this->recordSafetyAccess(
            $patientId,
            $actorId,
            $visitId,
            'CLINICAL_SAFETY_VIEWED',
            'Viewed longitudinal clinical safety information.'
        );
    }

    public function getAllowedAllergyTypes(): array
    {
        return $this->schemaControlledValues(
            'clinical_safety.allergy_types',
            ['Drug', 'Food', 'Environmental', 'Biological', 'Other']
        );
    }

    public function getAllowedSeverityValues(): array
    {
        return $this->schemaControlledValues(
            'clinical_safety.severity_values',
            ['Mild', 'Moderate', 'Severe', 'Life-threatening', 'Unknown']
        );
    }

    public function getAllowedAlertTypes(): array
    {
        return $this->schemaControlledValues(
            'clinical_safety.alert_types',
            ['Clinical Risk', 'Infection Control', 'Fall Risk', 'Communication Need', 'Safeguarding', 'Special Handling', 'Other']
        );
    }

    public function getAllowedAlertPriorities(): array
    {
        return $this->schemaControlledValues(
            'clinical_safety.alert_priorities',
            ['Low', 'Medium', 'High', 'Critical']
        );
    }

    public function getAllowedConfidentialityLevels(): array
    {
        return $this->schemaControlledValues(
            'clinical_safety.confidentiality_levels',
            ['Standard', 'Restricted', 'Confidential']
        );
    }

    private function recordSafetyAccess(
        int $patientId,
        int $actorId,
        ?int $visitId,
        string $action,
        string $description
    ): array {
        try {
            $this->pdo->beginTransaction();
            $this->lockPatient($patientId);
            $this->lockVisitForPatient($visitId, $patientId);
            $this->audit($actorId, $patientId, $visitId, $action, $description);
            $this->pdo->commit();
            return $this->success(['patient_id' => $patientId]);
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure(['Unable to record clinical safety access.'])
                + ['audit_failed' => true];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Internal Transitions
    |--------------------------------------------------------------------------
    */

    private function transitionAllergy(
        int $allergyId,
        string $reason,
        int $actorId,
        int $expectedVersion,
        ?int $visitId,
        string $historyAction,
        string $setClause,
        string $auditAction,
        ?string $eventTitle
    ): array {
        if (trim($reason) === '') {
            return $this->failure(['A reason is required.']);
        }
        try {
            $this->pdo->beginTransaction();
            $current = $this->lockAllergy($allergyId);
            if (!$current) {
                throw new RuntimeException('Allergy not found.');
            }
            if ((int)$current['version'] !== $expectedVersion) {
                $this->rollback();
                return $this->conflict('The allergy record changed. Reload and try again.', (int)$current['version']);
            }
            if ((string)$current['clinical_status'] !== 'Active') {
                $this->rollback();
                return $this->failure(['The requested allergy transition is not available.']);
            }
            if ($historyAction === 'Verified'
                && (string)$current['verification_status'] === 'Confirmed'
            ) {
                $this->rollback();
                return $this->failure(['This allergy is already verified.']);
            }
            if ($historyAction === 'Verified'
                && !$this->settingsService->getBoolean(
                    'clinical_safety.allow_self_allergy_verification',
                    false
                )
                && $this->latestAllergyAuthorId($allergyId, $current) === $actorId
            ) {
                $this->rollback();
                return $this->failure([
                    'The user who recorded or most recently updated this allergy cannot verify it.'
                ]);
            }
            $visit = $this->lockVisitForPatient($visitId, (int)$current['patient_id']);
            $newVersion = $expectedVersion + 1;
            $stmt = $this->pdo->prepare(
                'UPDATE patient_allergies SET ' . $setClause
                . ',version=:version WHERE id=:id AND version=:expected_version'
            );
            $stmt->execute([
                ':actor_id' => $actorId,
                ':version' => $newVersion,
                ':id' => $allergyId,
                ':expected_version' => $expectedVersion
            ]);
            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException('Concurrent allergy transition detected.');
            }
            $updated = $this->getAllergyByIdInternal($allergyId);
            $this->recordAllergyHistory($updated, $current, $historyAction, $reason, $actorId);
            $this->audit($actorId, (int)$current['patient_id'], $visitId, $auditAction, str_replace('_',' ',ucwords(strtolower($auditAction),'_')) . '.');
            if ($eventTitle !== null) {
                $this->recordEncounterEvent($visit, $auditAction, $eventTitle, $eventTitle . ' during this encounter.', $actorId);
            }
            $this->pdo->commit();
            return $this->success(['allergy_id' => $allergyId, 'version' => $newVersion]);
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure(['Unable to update allergy status.']);
        }
    }

    private function changeAllergyActivity(
        int $allergyId,
        string $reason,
        int $actorId,
        int $expectedVersion,
        ?int $visitId,
        bool $reactivate
    ): array {
        if (trim($reason) === '') {
            return $this->failure(['A reason is required.']);
        }

        try {
            $this->pdo->beginTransaction();
            $current = $this->lockAllergy($allergyId);
            if (!$current) {
                throw new RuntimeException('Allergy not found.');
            }
            if ((int)$current['version'] !== $expectedVersion) {
                $this->rollback();
                return $this->conflict(
                    'The allergy record changed. Reload and try again.',
                    (int)$current['version']
                );
            }

            $expectedStatus = $reactivate ? 'Inactive' : 'Active';
            if ((string)$current['clinical_status'] !== $expectedStatus) {
                $this->rollback();
                return $this->failure([
                    $reactivate
                        ? 'Only inactive allergies can be reactivated.'
                        : 'Only active allergies can be deactivated.'
                ]);
            }

            $visit = $this->lockVisitForPatient($visitId, (int)$current['patient_id']);
            if ($reactivate) {
                $this->lockMatchingAllergies(
                    (int)$current['patient_id'],
                    (string)$current['allergy_type'],
                    (string)$current['normalized_substance'],
                    $allergyId
                );
            }
            $newVersion = $expectedVersion + 1;
            $stmt = $this->pdo->prepare($reactivate
                ? "UPDATE patient_allergies SET clinical_status='Active',active_allergy_key=:active_key,version=:version WHERE id=:id AND version=:expected_version"
                : "UPDATE patient_allergies SET clinical_status='Inactive',active_allergy_key=NULL,version=:version WHERE id=:id AND version=:expected_version");
            $params = [
                ':version' => $newVersion,
                ':id' => $allergyId,
                ':expected_version' => $expectedVersion
            ];
            if ($reactivate) {
                $params[':active_key'] = $this->allergyKey($current);
            }
            $stmt->execute($params);
            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException('Concurrent allergy transition detected.');
            }

            $updated = $this->getAllergyByIdInternal($allergyId);
            $action = $reactivate ? 'Reactivated' : 'Deactivated';
            $auditAction = $reactivate ? 'ALLERGY_REACTIVATED' : 'ALLERGY_DEACTIVATED';
            $this->recordAllergyHistory($updated, $current, $action, $reason, $actorId);
            $this->audit(
                $actorId,
                (int)$current['patient_id'],
                $visitId,
                $auditAction,
                $reactivate
                    ? 'Reactivated structured allergy information.'
                    : 'Deactivated structured allergy information.'
            );
            $this->recordEncounterEvent(
                $visit,
                $auditAction,
                $reactivate ? 'Allergy Reactivated' : 'Allergy Deactivated',
                $reactivate
                    ? 'An allergy was reactivated during this encounter.'
                    : 'An allergy was deactivated during this encounter.',
                $actorId
            );
            $this->pdo->commit();

            return $this->success(['allergy_id' => $allergyId, 'version' => $newVersion]);
        } catch (PDOException $exception) {
            $this->rollback();
            return $this->failure([(string)$exception->getCode() === '23000'
                ? 'An active allergy to this substance is already recorded.'
                : 'Unable to update allergy status.']);
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure(['Unable to update allergy status.']);
        }
    }

    private function transitionAlert(
        int $alertId,
        string $reason,
        int $actorId,
        int $expectedVersion,
        ?int $visitId,
        bool $reactivate
    ): array {
        if (trim($reason) === '') {
            return $this->failure(['A reason is required.']);
        }
        try {
            $this->pdo->beginTransaction();
            $current = $this->lockAlert($alertId);
            if (!$current) {
                throw new RuntimeException('Alert not found.');
            }
            if ((int)$current['version'] !== $expectedVersion) {
                $this->rollback();
                return $this->conflict('The alert changed. Reload and try again.', (int)$current['version']);
            }
            if ((bool)$current['is_active'] === $reactivate) {
                $this->rollback();
                return $this->failure([$reactivate ? 'This alert is already active.' : 'This alert is already closed.']);
            }
            $visit = $this->lockVisitForPatient($visitId, (int)$current['patient_id']);
            if ($reactivate) {
                $this->lockMatchingAlerts((int)$current['patient_id'], (string)$current['alert_type'], (string)$current['normalized_title'], $alertId);
            }
            $newVersion = $expectedVersion + 1;
            $stmt = $this->pdo->prepare($reactivate
                ? 'UPDATE patient_alerts SET is_active=1,active_alert_key=:active_key,closed_by=NULL,closed_at=NULL,closure_reason=NULL,expires_at=CASE WHEN expires_at IS NOT NULL AND expires_at<=NOW() THEN NULL ELSE expires_at END,version=:version WHERE id=:id AND version=:expected_version'
                : 'UPDATE patient_alerts SET is_active=0,active_alert_key=NULL,closed_by=:actor_id,closed_at=NOW(),closure_reason=:reason,version=:version WHERE id=:id AND version=:expected_version');
            $params = [
                ':version' => $newVersion,
                ':id' => $alertId,
                ':expected_version' => $expectedVersion
            ];
            if ($reactivate) {
                $params[':active_key'] = $this->alertKey($current);
            } else {
                $params[':actor_id'] = $actorId;
                $params[':reason'] = trim($reason);
            }
            $stmt->execute($params);
            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException('Concurrent alert transition detected.');
            }
            $updated = $this->getAlertByIdInternal($alertId);
            $action = $reactivate ? 'Reactivated' : 'Closed';
            $auditAction = $reactivate ? 'CLINICAL_ALERT_REACTIVATED' : 'CLINICAL_ALERT_CLOSED';
            $this->recordAlertHistory($updated, $current, $action, $reason, $actorId);
            $this->audit($actorId, (int)$current['patient_id'], $visitId, $auditAction, $reactivate ? 'Reactivated a clinical safety alert.' : 'Closed a clinical safety alert.');
            $this->recordEncounterEvent($visit, $auditAction, $reactivate ? 'Clinical Alert Reactivated' : 'Clinical Alert Closed', $reactivate ? 'A clinical alert was reactivated.' : 'A clinical alert was closed.', $actorId);
            $this->pdo->commit();
            return $this->success(['alert_id' => $alertId, 'version' => $newVersion]);
        } catch (PDOException $exception) {
            $this->rollback();
            return $this->failure([(string)$exception->getCode() === '23000'
                ? 'An active alert with this type and title already exists.'
                : 'Unable to update alert status.']);
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure(['Unable to update alert status.']);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Validation and Persistence Helpers
    |--------------------------------------------------------------------------
    */

    private function prepareAllergy(array $data): array
    {
        $substance = trim((string)($data['substance'] ?? ''));
        return [
            'patient_id' => (int)($data['patient_id'] ?? 0),
            'source_visit_id' => $this->nullableInt($data['source_visit_id'] ?? null),
            'allergy_type' => trim((string)($data['allergy_type'] ?? '')),
            'substance' => $substance,
            'normalized_substance' => $this->normalize($substance),
            'reaction' => $this->nullableText($data['reaction'] ?? null),
            'severity' => trim((string)($data['severity'] ?? 'Unknown')),
            'onset_date' => $this->nullableText($data['onset_date'] ?? null),
            'notes' => $this->nullableText($data['notes'] ?? null),
            'reason' => trim((string)($data['reason'] ?? ''))
        ];
    }

    private function validateAllergy(array $data, int $actorId): array
    {
        $errors = [];
        $types = $this->getAllowedAllergyTypes();
        $severities = $this->getAllowedSeverityValues();
        if ($data['patient_id'] <= 0 || $actorId <= 0) {
            $errors[] = 'Patient and user are required.';
        }
        if (!in_array($data['allergy_type'], $types, true)) {
            $errors[] = 'Select a supported allergy type.';
        }
        if ($data['substance'] === '' || mb_strlen($data['substance']) > 150) {
            $errors[] = 'Substance is required and must not exceed 150 characters.';
        }
        if (!in_array($data['severity'], $severities, true)) {
            $errors[] = 'Select a supported allergy severity.';
        }
        if ($data['reaction'] !== null && mb_strlen($data['reaction']) > 500) {
            $errors[] = 'Reaction must not exceed 500 characters.';
        }
        if ($data['onset_date'] !== null && !$this->validDate($data['onset_date'])) {
            $errors[] = 'Onset date is invalid.';
        }
        if ($data['reason'] === '') {
            $errors[] = 'A reason is required.';
        }
        return $errors;
    }

    private function prepareAlert(array $data): array
    {
        $startsAt = $this->normalizeDateTime($data['starts_at'] ?? null);
        $expiresAt = $this->normalizeDateTime($data['expires_at'] ?? null);
        if ($expiresAt === null) {
            $days = $this->settingsService->getInteger('clinical_safety.default_alert_expiry_days', 0);
            if ($days > 0) {
                $expiresAt = (new DateTimeImmutable())->modify('+' . $days . ' days')->format('Y-m-d H:i:s');
            }
        }
        $title = trim((string)($data['title'] ?? ''));
        return [
            'patient_id' => (int)($data['patient_id'] ?? 0),
            'visit_id' => $this->nullableInt($data['visit_id'] ?? null),
            'alert_type' => trim((string)($data['alert_type'] ?? '')),
            'title' => $title,
            'normalized_title' => $this->normalize($title),
            'reason' => trim((string)($data['reason'] ?? '')),
            'priority' => trim((string)($data['priority'] ?? 'Medium')),
            'confidentiality_level' => trim((string)($data['confidentiality_level'] ?? 'Standard')),
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
            'change_reason' => trim((string)($data['change_reason'] ?? ($data['reason'] ?? '')))
        ];
    }

    private function validateAlert(array $data, int $actorId): array
    {
        $errors = [];
        $types = $this->getAllowedAlertTypes();
        $priorities = $this->getAllowedAlertPriorities();
        $levels = $this->getAllowedConfidentialityLevels();
        if ($data['patient_id'] <= 0 || $actorId <= 0) {
            $errors[] = 'Patient and user are required.';
        }
        if (!in_array($data['alert_type'], $types, true)) {
            $errors[] = 'Select a supported alert type.';
        }
        if ($data['title'] === '' || mb_strlen($data['title']) > 150) {
            $errors[] = 'Alert title is required and must not exceed 150 characters.';
        }
        if ($data['reason'] === '') {
            $errors[] = 'Alert reason is required.';
        }
        if (!in_array($data['priority'], $priorities, true)) {
            $errors[] = 'Select a supported alert priority.';
        }
        if (!in_array($data['confidentiality_level'], $levels, true)) {
            $errors[] = 'Select a supported confidentiality level.';
        }
        if ($data['starts_at'] === '__INVALID__'
            || $data['expires_at'] === '__INVALID__'
        ) {
            $errors[] = 'Alert start or expiry date is invalid.';
        }
        if ($data['starts_at'] !== null && $data['expires_at'] !== null
            && $data['starts_at'] !== '__INVALID__'
            && $data['expires_at'] !== '__INVALID__'
            && $data['expires_at'] <= $data['starts_at']
        ) {
            $errors[] = 'Alert expiry must be after its start.';
        }
        if ($data['change_reason'] === '') {
            $errors[] = 'A change reason is required.';
        }
        return $errors;
    }

    private function recordAllergyHistory(array $current, ?array $previous, string $action, string $reason, int $actorId): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO patient_allergy_history (allergy_id,patient_id,version_no,action,previous_snapshot,new_snapshot,reason,changed_by) VALUES (:record_id,:patient_id,:version_no,:action,:previous_snapshot,:new_snapshot,:reason,:actor_id)');
        $stmt->execute($this->historyParameters($current, $previous, $action, $reason, $actorId));
    }

    private function recordAlertHistory(array $current, ?array $previous, string $action, string $reason, int $actorId): void
    {
        $params = $this->historyParameters($current, $previous, $action, $reason, $actorId);
        $stmt = $this->pdo->prepare('INSERT INTO patient_alert_history (alert_id,patient_id,version_no,action,previous_snapshot,new_snapshot,reason,changed_by) VALUES (:record_id,:patient_id,:version_no,:action,:previous_snapshot,:new_snapshot,:reason,:actor_id)');
        $stmt->execute($params);
    }

    private function historyParameters(array $current, ?array $previous, string $action, string $reason, int $actorId): array
    {
        return [
            ':record_id' => $current['id'],
            ':patient_id' => $current['patient_id'],
            ':version_no' => $current['version'],
            ':action' => $action,
            ':previous_snapshot' => $previous ? json_encode($previous, JSON_THROW_ON_ERROR) : null,
            ':new_snapshot' => json_encode($current, JSON_THROW_ON_ERROR),
            ':reason' => trim($reason),
            ':actor_id' => $actorId
        ];
    }

    private function audit(int $actorId, int $patientId, ?int $visitId, string $action, string $description): void
    {
        if (!$this->auditService->logPatient($actorId, $patientId, $visitId, 'Medical Records', $action, $description, null, 'INFO', $action)) {
            throw new RuntimeException('Unable to record clinical safety audit.');
        }
    }

    private function recordEncounterEvent(?array $visit, string $type, string $title, string $description, int $actorId): void
    {
        if ($visit === null) {
            return;
        }
        $result = $this->eventService->record((int)$visit['id'], $type, $title, $description, isset($visit['department_id']) ? (int)$visit['department_id'] : null, $actorId);
        if (!($result['success'] ?? false)) {
            throw new RuntimeException('Unable to record encounter event.');
        }
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
        $stmt = $this->pdo->prepare('
            SELECT id,patient_id,current_department_id AS department_id
            FROM visits WHERE id=:id FOR UPDATE
        ');
        $stmt->execute([':id' => $visitId]);
        $visit = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$visit || (int)$visit['patient_id'] !== $patientId) {
            throw new RuntimeException('Encounter does not belong to this patient.');
        }
        return $visit;
    }

    private function lockAllergy(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM patient_allergies WHERE id=:id FOR UPDATE');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function lockAlert(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM patient_alerts WHERE id=:id FOR UPDATE');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function lockMatchingAllergies(int $patientId, string $type, string $substance, ?int $excludeId = null): void
    {
        $sql = "SELECT id FROM patient_allergies WHERE patient_id=:patient_id AND allergy_type=:type AND normalized_substance=:substance AND clinical_status='Active'";
        $params = [':patient_id' => $patientId, ':type' => $type, ':substance' => $substance];
        if ($excludeId !== null) {
            $sql .= ' AND id<>:exclude_id';
            $params[':exclude_id'] = $excludeId;
        }
        $sql .= ' ORDER BY id FOR UPDATE';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->fetchColumn()) {
            throw new RuntimeException('Duplicate active allergy.');
        }
    }

    private function lockMatchingAlerts(int $patientId, string $type, string $title, ?int $excludeId = null): void
    {
        $sql = 'SELECT id FROM patient_alerts WHERE patient_id=:patient_id AND alert_type=:type AND normalized_title=:title AND is_active=1';
        $params = [':patient_id' => $patientId, ':type' => $type, ':title' => $title];
        if ($excludeId !== null) {
            $sql .= ' AND id<>:exclude_id';
            $params[':exclude_id'] = $excludeId;
        }
        $sql .= ' ORDER BY id FOR UPDATE';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->fetchColumn()) {
            throw new RuntimeException('Duplicate active alert.');
        }
    }

    private function getAllergyByIdInternal(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM patient_allergies WHERE id=:id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function getAlertByIdInternal(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM patient_alerts WHERE id=:id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function protectAlert(array $alert, bool $canViewConfidential): array
    {
        $restricted = in_array((string)$alert['confidentiality_level'], ['Restricted','Confidential'], true);
        $alert['confidential_hidden'] = $restricted && !$canViewConfidential;
        if ($alert['confidential_hidden']) {
            $alert['title'] = 'Confidential clinical safety alert';
            $alert['reason'] = null;
            $alert['closure_reason'] = null;
        }
        $now = date('Y-m-d H:i:s');
        $alert['is_expired'] = !empty($alert['is_active'])
            && $alert['expires_at'] !== null
            && (string)$alert['expires_at'] <= $now;
        $alert['effective_status'] = empty($alert['is_active'])
            ? 'Closed'
            : ($alert['is_expired']
                ? 'Expired'
                : ($alert['starts_at'] !== null && (string)$alert['starts_at'] > $now
                    ? 'Scheduled'
                    : 'Active'));
        return $alert;
    }

    private function protectAlertHistory(array $entry, bool $canViewConfidential): array
    {
        $previous = $this->decodeSnapshot($entry['previous_snapshot'] ?? null);
        $current = $this->decodeSnapshot($entry['new_snapshot'] ?? null);
        $requiresConfidential = $this->snapshotIsRestricted($previous)
            || $this->snapshotIsRestricted($current);
        $entry['requires_confidential_access'] = $requiresConfidential;
        $entry['confidential_hidden'] = $requiresConfidential && !$canViewConfidential;
        if ($entry['confidential_hidden']) {
            $entry['previous_snapshot'] = null;
            $entry['new_snapshot'] = null;
            $entry['reason'] = 'Confidential history details are hidden.';
        }
        return $entry;
    }

    private function decodeSnapshot(mixed $snapshot): array
    {
        if (!is_string($snapshot) || trim($snapshot) === '') {
            return [];
        }
        $decoded = json_decode($snapshot, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function snapshotIsRestricted(array $snapshot): bool
    {
        return in_array(
            (string)($snapshot['confidentiality_level'] ?? 'Standard'),
            ['Restricted', 'Confidential'],
            true
        );
    }

    private function isRestrictedAlert(array $alert): bool
    {
        return $this->snapshotIsRestricted($alert);
    }

    private function latestAllergyAuthorId(int $allergyId, array $allergy): int
    {
        $stmt = $this->pdo->prepare('
            SELECT changed_by
            FROM patient_allergy_history
            WHERE allergy_id=:allergy_id
            ORDER BY version_no DESC
            LIMIT 1
            FOR UPDATE
        ');
        $stmt->execute([':allergy_id' => $allergyId]);
        $actorId = $stmt->fetchColumn();
        return $actorId !== false ? (int)$actorId : (int)$allergy['recorded_by'];
    }

    private function isMaterialAllergyChange(array $current, array $prepared): bool
    {
        $comparisons = [
            [(string)$current['allergy_type'], (string)$prepared['allergy_type']],
            [(string)$current['normalized_substance'], (string)$prepared['normalized_substance']],
            [trim((string)($current['reaction'] ?? '')), trim((string)($prepared['reaction'] ?? ''))],
            [(string)$current['severity'], (string)$prepared['severity']],
            [(string)($current['onset_date'] ?? ''), (string)($prepared['onset_date'] ?? '')],
            [trim((string)($current['notes'] ?? '')), trim((string)($prepared['notes'] ?? ''))]
        ];
        foreach ($comparisons as [$before, $after]) {
            if ($before !== $after) {
                return true;
            }
        }
        return false;
    }

    private function schemaControlledValues(string $key, array $supported): array
    {
        $configured = $this->settingsService->getArray($key, $supported);
        $allowed = array_values(array_unique(array_intersect($configured, $supported)));
        return $allowed !== [] ? $allowed : $supported;
    }

    private function allergyKey(array $data): string
    {
        return $data['patient_id'] . '|' . $data['allergy_type'] . '|' . $data['normalized_substance'];
    }

    private function alertKey(array $data): string
    {
        return $data['patient_id'] . '|' . $data['alert_type'] . '|' . $data['normalized_title'];
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');
        return preg_replace('/\s+/u', ' ', $value) ?? '';
    }

    private function normalizeDateTime(mixed $value): ?string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }
        $value = str_replace('T', ' ', $value);
        foreach (['!Y-m-d H:i:s', '!Y-m-d H:i'] as $format) {
            $date = DateTimeImmutable::createFromFormat($format, $value);
            if ($date !== false) {
                return $date->format('Y-m-d H:i:s');
            }
        }
        return '__INVALID__';
    }

    private function validDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }

    private function nullableInt(mixed $value): ?int
    {
        $int = (int)$value;
        return $int > 0 ? $int : null;
    }

    private function nullableText(mixed $value): ?string
    {
        $value = trim((string)$value);
        return $value === '' ? null : $value;
    }

    private function success(array $data): array
    {
        return ['success' => true, 'data' => $data, 'errors' => []] + $data;
    }

    private function failure(array $errors): array
    {
        return ['success' => false, 'data' => null, 'errors' => $errors];
    }

    private function forbidden(string $message): array
    {
        return [
            'success' => false,
            'data' => null,
            'forbidden' => true,
            'errors' => [$message]
        ];
    }

    private function conflict(string $message, int $version): array
    {
        return ['success' => false, 'data' => null, 'conflict' => true, 'current_version' => $version, 'errors' => [$message]];
    }

    private function rollback(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    private function safeAllergyError(RuntimeException $exception): string
    {
        return match ($exception->getMessage()) {
            'Patient not found.' => 'Patient not found.',
            'Encounter does not belong to this patient.' =>
                'The selected encounter does not belong to this patient.',
            'Duplicate active allergy.' =>
                'An active allergy to this substance is already recorded.',
            default => 'Unable to record allergy information.'
        };
    }

    private function safeAlertError(RuntimeException $exception): string
    {
        return match ($exception->getMessage()) {
            'Patient not found.' => 'Patient not found.',
            'Encounter does not belong to this patient.' =>
                'The selected encounter does not belong to this patient.',
            'Duplicate active alert.' =>
                'An active alert with this type and title already exists.',
            default => 'Unable to create clinical alert.'
        };
    }
}
