<?php

declare(strict_types=1);

require_once __DIR__ . '/AuditService.php';
require_once __DIR__ . '/SettingsService.php';

class PatientIdentifierService
{
    private PDO $pdo;
    private AuditService $auditService;
    private SettingsService $settingsService;

    public function __construct(
        PDO $pdo,
        ?AuditService $auditService = null,
        ?SettingsService $settingsService = null
    ) {
        $this->pdo = $pdo;
        $this->auditService = $auditService ?? new AuditService($pdo);
        $this->settingsService = $settingsService ?? new SettingsService($pdo);
    }

    /*
    |--------------------------------------------------------------------------
    | Identifier Commands
    |--------------------------------------------------------------------------
    */

    public function addIdentifier(array $data, int $actorId): array
    {
        $prepared = $this->prepare($data);
        $errors = $this->validate($prepared, $actorId);

        if ($errors !== []) {
            return $this->failure($errors);
        }

        try {
            $this->pdo->beginTransaction();
            $this->lockPatient((int)$prepared['patient_id']);

            if ($prepared['is_primary']) {
                $this->lockTypeIdentifiers(
                    (int)$prepared['patient_id'],
                    (string)$prepared['identifier_type']
                );
                $this->clearPrimary(
                    (int)$prepared['patient_id'],
                    (string)$prepared['identifier_type'],
                    $actorId,
                    (string)$prepared['reason']
                );
            }

            $stmt = $this->pdo->prepare('
                INSERT INTO patient_identifiers (
                    patient_id, identifier_type, identifier_value,
                    normalized_value, issuing_authority,
                    issuing_authority_key, uniqueness_scope, uniqueness_key,
                    issue_date, expiry_date, is_primary, primary_key_value,
                    created_by, updated_by
                ) VALUES (
                    :patient_id, :identifier_type, :identifier_value,
                    :normalized_value, :issuing_authority,
                    :issuing_authority_key, :uniqueness_scope, :uniqueness_key,
                    :issue_date, :expiry_date, :is_primary, :primary_key_value,
                    :created_by, :updated_by
                )
            ');
            $stmt->execute($this->writeParameters($prepared, $actorId));
            $identifierId = (int)$this->pdo->lastInsertId();
            $created = $this->getIdentifierByIdInternal($identifierId);

            $this->recordHistory(
                $created,
                null,
                'Created',
                (string)$prepared['reason'],
                $actorId
            );
            $this->audit(
                $actorId,
                (int)$prepared['patient_id'],
                'IDENTIFIER_CREATED',
                'Added a ' . $prepared['identifier_type'] . ' ending '
                    . $this->lastFour((string)$prepared['identifier_value']) . '.'
            );
            $this->pdo->commit();

            return $this->success([
                'identifier_id' => $identifierId,
                'patient_id' => (int)$prepared['patient_id']
            ]);
        } catch (PDOException $exception) {
            $this->rollback();

            if ((string)$exception->getCode() === '23000') {
                return $this->failure([
                    'This identifier is already assigned within its uniqueness scope.'
                ]);
            }

            return $this->failure(['Unable to add patient identifier.']);
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure(['Unable to add patient identifier.']);
        }
    }

    public function updateIdentifier(
        int $identifierId,
        array $data,
        int $expectedVersion,
        int $actorId
    ): array {
        try {
            $this->pdo->beginTransaction();
            $current = $this->lockIdentifier($identifierId);

            if (!$current) {
                throw new RuntimeException('Identifier not found.');
            }

            if ((int)$current['version'] !== $expectedVersion) {
                $this->rollback();
                return $this->failure([
                    'This identifier was updated by another user. Reload and try again.'
                ], ['conflict' => true, 'current_version' => (int)$current['version']]);
            }

            $prepared = $this->prepare(array_merge($current, $data));
            $errors = $this->validate($prepared, $actorId);
            if ($errors !== []) {
                $this->rollback();
                return $this->failure($errors);
            }

            $newVersion = $expectedVersion + 1;
            $stmt = $this->pdo->prepare('
                UPDATE patient_identifiers SET
                    identifier_type = :identifier_type,
                    identifier_value = :identifier_value,
                    normalized_value = :normalized_value,
                    issuing_authority = :issuing_authority,
                    issuing_authority_key = :issuing_authority_key,
                    uniqueness_scope = :uniqueness_scope,
                    uniqueness_key = :uniqueness_key,
                    issue_date = :issue_date,
                    expiry_date = :expiry_date,
                    updated_by = :updated_by,
                    version = :version
                WHERE id = :id
            ');
            $stmt->execute([
                ':identifier_type' => $prepared['identifier_type'],
                ':identifier_value' => $prepared['identifier_value'],
                ':normalized_value' => $prepared['normalized_value'],
                ':issuing_authority' => $prepared['issuing_authority'],
                ':issuing_authority_key' => $prepared['issuing_authority_key'],
                ':uniqueness_scope' => $prepared['uniqueness_scope'],
                ':uniqueness_key' => $prepared['uniqueness_key'],
                ':issue_date' => $prepared['issue_date'],
                ':expiry_date' => $prepared['expiry_date'],
                ':updated_by' => $actorId,
                ':version' => $newVersion,
                ':id' => $identifierId
            ]);
            $updated = $this->getIdentifierByIdInternal($identifierId);
            $this->recordHistory(
                $updated,
                $current,
                'Updated',
                (string)$prepared['reason'],
                $actorId
            );
            $this->audit(
                $actorId,
                (int)$current['patient_id'],
                'IDENTIFIER_UPDATED',
                'Updated a masked ' . $prepared['identifier_type'] . ' identifier.'
            );
            $this->pdo->commit();

            return $this->success([
                'identifier_id' => $identifierId,
                'version' => $newVersion
            ]);
        } catch (PDOException $exception) {
            $this->rollback();
            return $this->failure([
                (string)$exception->getCode() === '23000'
                    ? 'This identifier is already assigned within its uniqueness scope.'
                    : 'Unable to update patient identifier.'
            ]);
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure(['Unable to update patient identifier.']);
        }
    }

    public function deactivateIdentifier(
        int $identifierId,
        string $reason,
        int $actorId
    ): array {
        return $this->changeState(
            $identifierId,
            'Deactivated',
            $reason,
            $actorId,
            'is_active = 0, is_primary = 0, primary_key_value = NULL',
            'IDENTIFIER_DEACTIVATED'
        );
    }

    public function verifyIdentifier(
        int $identifierId,
        string $reason,
        int $actorId
    ): array {
        return $this->changeState(
            $identifierId,
            'Verified',
            $reason,
            $actorId,
            "verification_status = 'Verified', verified_by = :actor_id, verified_at = NOW()",
            'IDENTIFIER_VERIFIED'
        );
    }

    public function setPrimaryIdentifier(
        int $identifierId,
        string $reason,
        int $actorId
    ): array {
        try {
            $this->pdo->beginTransaction();
            $target = $this->lockIdentifier($identifierId);
            if (!$target || !(bool)$target['is_active']) {
                throw new RuntimeException('Active identifier not found.');
            }

            $patientId = (int)$target['patient_id'];
            $type = (string)$target['identifier_type'];
            $this->lockTypeIdentifiers($patientId, $type);
            if ((bool)$target['is_primary']) {
                $this->pdo->commit();
                return $this->success(['identifier_id' => $identifierId]);
            }
            $this->clearPrimary($patientId, $type, $actorId, $reason);

            $stmt = $this->pdo->prepare('
                UPDATE patient_identifiers
                SET is_primary = 1,
                    primary_key_value = :primary_key,
                    updated_by = :actor_id,
                    version = version + 1
                WHERE id = :id
            ');
            $stmt->execute([
                ':primary_key' => $patientId . '|' . $type,
                ':actor_id' => $actorId,
                ':id' => $identifierId
            ]);
            $updated = $this->getIdentifierByIdInternal($identifierId);
            $this->recordHistory($updated, $target, 'PrimaryChanged', $reason, $actorId);
            $this->audit(
                $actorId,
                $patientId,
                'PRIMARY_IDENTIFIER_CHANGED',
                'Changed the primary ' . $type . ' identifier.'
            );
            $this->pdo->commit();
            return $this->success(['identifier_id' => $identifierId]);
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure(['Unable to change the primary identifier.']);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Identifier Queries
    |--------------------------------------------------------------------------
    */

    public function getIdentifierById(int $identifierId): ?array
    {
        return $this->getIdentifierByIdInternal($identifierId);
    }

    public function getPatientIdentifiers(
        int $patientId,
        bool $includeInactive = false
    ): array {
        $sql = 'SELECT * FROM patient_identifiers WHERE patient_id = :patient_id';
        if (!$includeInactive) {
            $sql .= ' AND is_active = 1';
        }
        $sql .= ' ORDER BY is_primary DESC, identifier_type, created_at';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':patient_id' => $patientId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $row['masked_value'] = $this->maskIdentifier(
                (string)$row['identifier_type'],
                (string)$row['identifier_value']
            );
        }
        unset($row);

        return $rows;
    }

    public function listIdentifiers(int $patientId, bool $includeInactive = false): array
    {
        return $this->getPatientIdentifiers($patientId, $includeInactive);
    }

    public function getIdentifierHistory(int $identifierId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT h.*, CONCAT(u.first_name, " ", u.last_name) AS actor_name
            FROM patient_identifier_history h
            INNER JOIN users u ON u.id = h.changed_by
            WHERE h.identifier_id = :identifier_id
            ORDER BY h.version_no DESC
        ');
        $stmt->execute([':identifier_id' => $identifierId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findPatientByIdentifier(string $type, string $value): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT p.* FROM patient_identifiers i
            INNER JOIN patients p ON p.id = i.patient_id
            WHERE i.identifier_type = :type
              AND i.normalized_value = :value
              AND i.is_active = 1
            ORDER BY i.is_primary DESC LIMIT 1
        ');
        $stmt->execute([
            ':type' => trim($type),
            ':value' => $this->normalizeIdentifier($type, $value)
        ]);
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);
        return $patient ?: null;
    }

    public function searchIdentifiers(string $query, int $limit = 25): array
    {
        $normalized = $this->normalizeIdentifier('', $query);
        $stmt = $this->pdo->prepare('
            SELECT i.*, p.hospital_number, p.first_name, p.last_name
            FROM patient_identifiers i
            INNER JOIN patients p ON p.id = i.patient_id
            WHERE i.normalized_value = :exact
               OR i.normalized_value LIKE :prefix
            ORDER BY (i.normalized_value = :exact_order) DESC, i.created_at DESC
            LIMIT :limit
        ');
        $stmt->bindValue(':exact', $normalized);
        $stmt->bindValue(':prefix', $normalized . '%');
        $stmt->bindValue(':exact_order', $normalized);
        $stmt->bindValue(':limit', max(1, min(100, $limit)), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function searchIdentifier(string $query, int $limit = 25): array
    {
        return $this->searchIdentifiers($query, $limit);
    }

    public function normalizeIdentifier(string $type, string $value): string
    {
        $value = strtoupper(trim($value));
        return preg_replace('/[^A-Z0-9]/', '', $value) ?? '';
    }

    public function maskIdentifier(string $type, string $value): string
    {
        $maskTypes = $this->settingsService->getArray(
            'mpi.mask_identifier_types',
            ['National Identification Number', 'Insurance Number', 'Passport Number']
        );
        if (!in_array($type, $maskTypes, true) || strlen($value) <= 4) {
            return $value;
        }
        return str_repeat('*', max(4, strlen($value) - 4)) . substr($value, -4);
    }

    /*
    |--------------------------------------------------------------------------
    | Internal Operations
    |--------------------------------------------------------------------------
    */

    private function prepare(array $data): array
    {
        $type = trim((string)($data['identifier_type'] ?? ''));
        $value = trim((string)($data['identifier_value'] ?? ''));
        $authority = trim((string)($data['issuing_authority'] ?? ''));
        $normalized = $this->normalizeIdentifier($type, $value);
        $global = $this->settingsService->getArray('mpi.global_unique_types', []);
        $authorityTypes = $this->settingsService->getArray('mpi.authority_unique_types', []);
        $scope = in_array($type, $global, true)
            ? 'Global'
            : (in_array($type, $authorityTypes, true) ? 'Authority' : 'Patient');
        $patientId = (int)($data['patient_id'] ?? 0);
        $authorityKey = strtolower($authority);

        $uniquenessKey = match ($scope) {
            'Global' => $type . '|G|' . $normalized,
            'Authority' => $type . '|A|' . $authorityKey . '|' . $normalized,
            'Patient' => $type . '|P|' . $patientId . '|' . $normalized,
            default => null
        };

        return [
            'patient_id' => $patientId,
            'identifier_type' => $type,
            'identifier_value' => $value,
            'normalized_value' => $normalized,
            'issuing_authority' => $authority === '' ? null : $authority,
            'issuing_authority_key' => $authorityKey,
            'uniqueness_scope' => $scope,
            'uniqueness_key' => $uniquenessKey,
            'issue_date' => $this->nullable((string)($data['issue_date'] ?? '')),
            'expiry_date' => $this->nullable((string)($data['expiry_date'] ?? '')),
            'is_primary' => !empty($data['is_primary']),
            'primary_key_value' => !empty($data['is_primary'])
                ? $patientId . '|' . $type
                : null,
            'reason' => trim((string)($data['reason'] ?? ''))
        ];
    }

    private function validate(array $data, int $actorId): array
    {
        $errors = [];
        $defaultTypes = [
            'National Identification Number',
            'Insurance Number',
            'Passport Number',
            'External Hospital Number',
            'Legacy Medical Record Number'
        ];
        $types = $this->settingsService->exists('mpi.enabled_identifier_types')
            ? $this->settingsService->getArray('mpi.enabled_identifier_types', $defaultTypes)
            : $this->settingsService->getArray('mpi.identifier_definitions', $defaultTypes);

        if ($data['patient_id'] <= 0 || $actorId <= 0) {
            $errors[] = 'Patient and user are required.';
        }
        if (!in_array($data['identifier_type'], $types, true)) {
            $errors[] = 'Select a supported identifier type.';
        }
        if ($data['normalized_value'] === '' || strlen($data['normalized_value']) < 4) {
            $errors[] = 'Identifier value must contain at least four letters or numbers.';
        }
        if ($data['uniqueness_scope'] === 'Authority' && !$data['issuing_authority']) {
            $errors[] = 'Issuing authority is required for this identifier type.';
        }
        if ($data['reason'] === '') {
            $errors[] = 'A reason is required.';
        }
        if ($data['issue_date'] && !$this->validDate($data['issue_date'])) {
            $errors[] = 'Issue date is invalid.';
        }
        if ($data['expiry_date'] && !$this->validDate($data['expiry_date'])) {
            $errors[] = 'Expiry date is invalid.';
        }
        if ($data['issue_date'] && $data['expiry_date']
            && $data['expiry_date'] < $data['issue_date']
        ) {
            $errors[] = 'Expiry date cannot be before the issue date.';
        }
        return $errors;
    }

    private function changeState(
        int $identifierId,
        string $historyAction,
        string $reason,
        int $actorId,
        string $setClause,
        string $auditAction
    ): array {
        if (trim($reason) === '') {
            return $this->failure(['A reason is required.']);
        }
        try {
            $this->pdo->beginTransaction();
            $current = $this->lockIdentifier($identifierId);
            if (!$current) {
                throw new RuntimeException('Identifier not found.');
            }
            $stmt = $this->pdo->prepare(
                'UPDATE patient_identifiers SET ' . $setClause
                . ', updated_by = :updated_by, version = version + 1 WHERE id = :id'
            );
            $params = [':updated_by' => $actorId, ':id' => $identifierId];
            if (str_contains($setClause, ':actor_id')) {
                $params[':actor_id'] = $actorId;
            }
            $stmt->execute($params);
            $updated = $this->getIdentifierByIdInternal($identifierId);
            $this->recordHistory($updated, $current, $historyAction, $reason, $actorId);
            $this->audit(
                $actorId,
                (int)$current['patient_id'],
                $auditAction,
                $historyAction . ' a masked ' . $current['identifier_type'] . ' identifier.'
            );
            $this->pdo->commit();
            return $this->success(['identifier_id' => $identifierId]);
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure(['Unable to update identifier status.']);
        }
    }

    private function clearPrimary(
        int $patientId,
        string $type,
        int $actorId,
        string $reason
    ): void
    {
        $before = $this->pdo->prepare('
            SELECT * FROM patient_identifiers
            WHERE patient_id = :patient_id AND identifier_type = :type
              AND is_primary = 1
            ORDER BY id
        ');
        $before->execute([':patient_id' => $patientId, ':type' => $type]);
        $previousPrimary = $before->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $this->pdo->prepare('
            UPDATE patient_identifiers
            SET is_primary = 0, primary_key_value = NULL,
                updated_by = :actor_id, version = version + 1
            WHERE patient_id = :patient_id AND identifier_type = :type
              AND is_primary = 1
        ');
        $stmt->execute([
            ':actor_id' => $actorId,
            ':patient_id' => $patientId,
            ':type' => $type
        ]);

        foreach ($previousPrimary as $previous) {
            $current = $this->getIdentifierByIdInternal((int)$previous['id']);
            if ($current !== null) {
                $this->recordHistory(
                    $current,
                    $previous,
                    'PrimaryCleared',
                    $reason,
                    $actorId
                );
            }
        }
    }

    private function recordHistory(
        array $current,
        ?array $previous,
        string $action,
        string $reason,
        int $actorId
    ): void {
        $stmt = $this->pdo->prepare('
            INSERT INTO patient_identifier_history (
                identifier_id, patient_id, version_no, action,
                previous_snapshot, new_snapshot, reason, changed_by
            ) VALUES (
                :identifier_id, :patient_id, :version_no, :action,
                :previous_snapshot, :new_snapshot, :reason, :changed_by
            )
        ');
        $stmt->execute([
            ':identifier_id' => $current['id'],
            ':patient_id' => $current['patient_id'],
            ':version_no' => $current['version'],
            ':action' => $action,
            ':previous_snapshot' => $previous
                ? json_encode($previous, JSON_THROW_ON_ERROR)
                : null,
            ':new_snapshot' => json_encode($current, JSON_THROW_ON_ERROR),
            ':reason' => trim($reason),
            ':changed_by' => $actorId
        ]);
    }

    private function audit(int $actorId, int $patientId, string $action, string $description): void
    {
        if (!$this->auditService->logPatient(
            $actorId,
            $patientId,
            null,
            'Medical Records',
            $action,
            $description,
            null,
            'INFO',
            $action
        )) {
            throw new RuntimeException('Unable to record audit event.');
        }
    }

    private function lockPatient(int $patientId): void
    {
        $stmt = $this->pdo->prepare('SELECT id FROM patients WHERE id = :id FOR UPDATE');
        $stmt->execute([':id' => $patientId]);
        if (!$stmt->fetchColumn()) {
            throw new RuntimeException('Patient not found.');
        }
    }

    private function lockIdentifier(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM patient_identifiers WHERE id = :id FOR UPDATE');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function lockTypeIdentifiers(int $patientId, string $type): void
    {
        $stmt = $this->pdo->prepare('
            SELECT id FROM patient_identifiers
            WHERE patient_id = :patient_id AND identifier_type = :type
            ORDER BY id FOR UPDATE
        ');
        $stmt->execute([':patient_id' => $patientId, ':type' => $type]);
        $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function getIdentifierByIdInternal(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM patient_identifiers WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function writeParameters(array $data, int $actorId): array
    {
        return [
            ':patient_id' => $data['patient_id'],
            ':identifier_type' => $data['identifier_type'],
            ':identifier_value' => $data['identifier_value'],
            ':normalized_value' => $data['normalized_value'],
            ':issuing_authority' => $data['issuing_authority'],
            ':issuing_authority_key' => $data['issuing_authority_key'],
            ':uniqueness_scope' => $data['uniqueness_scope'],
            ':uniqueness_key' => $data['uniqueness_key'],
            ':issue_date' => $data['issue_date'],
            ':expiry_date' => $data['expiry_date'],
            ':is_primary' => $data['is_primary'] ? 1 : 0,
            ':primary_key_value' => $data['primary_key_value'],
            ':created_by' => $actorId,
            ':updated_by' => $actorId
        ];
    }

    private function success(array $data): array
    {
        return ['success' => true, 'data' => $data, 'errors' => []] + $data;
    }

    private function failure(array $errors, array $extra = []): array
    {
        return ['success' => false, 'data' => null, 'errors' => $errors] + $extra;
    }

    private function rollback(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    private function nullable(string $value): ?string
    {
        $value = trim($value);
        return $value === '' ? null : $value;
    }

    private function validDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value;
    }

    private function lastFour(string $value): string
    {
        return substr($value, -4) ?: '****';
    }
}
