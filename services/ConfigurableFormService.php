<?php

declare(strict_types=1);

require_once __DIR__ . '/AuditService.php';
require_once __DIR__ . '/PermissionService.php';

class ConfigurableFormService
{
    private PermissionService $permissionService;
    private AuditService $auditService;
    private ?bool $tablesAvailable = null;

    private const FIELD_TYPES = ['text', 'textarea', 'number', 'date', 'select', 'checkbox', 'yes_no'];

    public function __construct(private PDO $pdo, ?PermissionService $permissionService = null, ?AuditService $auditService = null)
    {
        $this->permissionService = $permissionService ?? new PermissionService($pdo);
        $this->auditService = $auditService ?? new AuditService($pdo);
    }

    public function tablesAvailable(): bool
    {
        if ($this->tablesAvailable !== null) {
            return $this->tablesAvailable;
        }

        try {
            $stmt = $this->pdo->query(
                "SELECT COUNT(*) FROM information_schema.tables
                 WHERE table_schema = DATABASE()
                   AND table_name IN ('form_definitions','form_fields','form_responses','form_response_values')"
            );
            $this->tablesAvailable = (int)$stmt->fetchColumn() === 4;
        } catch (Throwable) {
            $this->tablesAvailable = false;
        }

        return $this->tablesAvailable;
    }

    public function listDefinitions(?array $user = null): array
    {
        if (!$this->tablesAvailable()) {
            return [];
        }

        if ($user !== null && !$this->permissionService->canManageConfigurableForms($user)) {
            return [];
        }

        $stmt = $this->pdo->query(
            'SELECT fd.id, fd.form_key, fd.form_name, fd.description, fd.is_active,
                    fd.created_by, fd.updated_by, fd.created_at, fd.updated_at,
                    COUNT(ff.id) AS field_count,
                    SUM(CASE WHEN ff.is_active = 1 THEN 1 ELSE 0 END) AS active_field_count
             FROM form_definitions fd
             LEFT JOIN form_fields ff ON ff.form_definition_id = fd.id
             GROUP BY fd.id, fd.form_key, fd.form_name, fd.description, fd.is_active,
                      fd.created_by, fd.updated_by, fd.created_at, fd.updated_at
             ORDER BY fd.form_name'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getDefinition(string $formKey, ?array $user = null): ?array
    {
        if (!$this->tablesAvailable()) {
            return null;
        }

        if ($user !== null && !$this->permissionService->canManageConfigurableForms($user)) {
            return null;
        }

        $stmt = $this->pdo->prepare('SELECT * FROM form_definitions WHERE form_key = :form_key LIMIT 1');
        $stmt->execute([':form_key' => $formKey]);
        $definition = $stmt->fetch(PDO::FETCH_ASSOC);

        return $definition ?: null;
    }

    public function listFields(string $formKey, bool $activeOnly = true): array
    {
        if (!$this->tablesAvailable()) {
            return [];
        }

        $sql = '
            SELECT ff.*
            FROM form_fields ff
            INNER JOIN form_definitions fd ON fd.id = ff.form_definition_id
            WHERE fd.form_key = :form_key
              AND fd.is_active = 1
        ';
        if ($activeOnly) {
            $sql .= ' AND ff.is_active = 1';
        }
        $sql .= ' ORDER BY ff.sort_order, ff.field_label';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':form_key' => $formKey]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function saveFieldConfig(string $formKey, array $data, array $user): array
    {
        if (!$this->permissionService->canManageConfigurableForms($user)) {
            return $this->failure(['Configurable form management is denied.']);
        }

        if (!$this->tablesAvailable()) {
            return $this->failure(['Configurable form tables are not available. Apply Migration 070.']);
        }

        $definition = $this->getDefinition($formKey);
        if (!$definition) {
            return $this->failure(['Configurable form not found.']);
        }

        try {
            $this->pdo->beginTransaction();

            $fields = is_array($data['fields'] ?? null) ? $data['fields'] : [];
            foreach ($fields as $fieldId => $fieldData) {
                $fieldId = (int)$fieldId;
                if ($fieldId <= 0 || !is_array($fieldData)) {
                    continue;
                }

                $label = trim((string)($fieldData['field_label'] ?? ''));
                $type = trim((string)($fieldData['field_type'] ?? 'text'));
                $sortOrder = (int)($fieldData['sort_order'] ?? 0);
                if ($label === '' || !in_array($type, self::FIELD_TYPES, true)) {
                    throw new RuntimeException('Invalid configured field.');
                }

                $stmt = $this->pdo->prepare(
                    'UPDATE form_fields
                     SET field_label = :label,
                         field_type = :type,
                         is_required = :required,
                         sort_order = :sort_order,
                         options_json = :options_json,
                         is_active = :active,
                         updated_by = :updated_by
                     WHERE id = :id AND form_definition_id = :form_definition_id'
                );
                $stmt->execute([
                    ':label' => $label,
                    ':type' => $type,
                    ':required' => !empty($fieldData['is_required']) ? 1 : 0,
                    ':sort_order' => $sortOrder,
                    ':options_json' => $this->normalizeOptions($fieldData['options'] ?? ''),
                    ':active' => !empty($fieldData['is_active']) ? 1 : 0,
                    ':updated_by' => (int)$user['id'],
                    ':id' => $fieldId,
                    ':form_definition_id' => (int)$definition['id'],
                ]);
            }

            $newLabel = trim((string)($data['new_field_label'] ?? ''));
            if ($newLabel !== '') {
                $newKey = $this->makeFieldKey((string)($data['new_field_key'] ?? $newLabel));
                $newType = trim((string)($data['new_field_type'] ?? 'text'));
                if (!in_array($newType, self::FIELD_TYPES, true)) {
                    throw new RuntimeException('Invalid new field type.');
                }

                $stmt = $this->pdo->prepare(
                    'INSERT INTO form_fields (
                        form_definition_id, field_key, field_label, field_type, is_required,
                        sort_order, options_json, is_active, created_by
                     ) VALUES (
                        :form_definition_id, :field_key, :field_label, :field_type, :is_required,
                        :sort_order, :options_json, :is_active, :created_by
                     )'
                );
                $stmt->execute([
                    ':form_definition_id' => (int)$definition['id'],
                    ':field_key' => $newKey,
                    ':field_label' => $newLabel,
                    ':field_type' => $newType,
                    ':is_required' => !empty($data['new_field_required']) ? 1 : 0,
                    ':sort_order' => (int)($data['new_field_sort_order'] ?? 100),
                    ':options_json' => $this->normalizeOptions($data['new_field_options'] ?? ''),
                    ':is_active' => !empty($data['new_field_active']) ? 1 : 0,
                    ':created_by' => (int)$user['id'],
                ]);
            }

            $this->auditService->log(
                (int)$user['id'],
                null,
                'Administration',
                'CONFIGURABLE_FORM_UPDATED',
                'Updated configurable form fields for ' . (string)$definition['form_name'] . '.',
                null,
                'INFO',
                'CONFIGURABLE_FORM_UPDATED'
            );

            $this->pdo->commit();
            return ['success' => true, 'errors' => []];
        } catch (Throwable $throwable) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return $this->failure(['Unable to update configurable form fields. Check for duplicate field keys or invalid values.']);
        }
    }

    public function getResponseValues(string $formKey, string $sourceModule, int $sourceRecordId): array
    {
        if (!$this->tablesAvailable() || $sourceRecordId <= 0) {
            return [];
        }

        $stmt = $this->pdo->prepare(
            'SELECT ff.field_key, ff.field_label, ff.field_type, frv.value_text, ff.sort_order
             FROM form_responses fr
             INNER JOIN form_response_values frv ON frv.form_response_id = fr.id
             INNER JOIN form_fields ff ON ff.id = frv.form_field_id
             WHERE fr.form_key = :form_key
               AND fr.source_module = :source_module
               AND fr.source_record_id = :source_record_id
             ORDER BY ff.sort_order, ff.field_label'
        );
        $stmt->execute([
            ':form_key' => $formKey,
            ':source_module' => $sourceModule,
            ':source_record_id' => $sourceRecordId,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getResponseValueMap(string $formKey, string $sourceModule, int $sourceRecordId): array
    {
        $rows = $this->getResponseValues($formKey, $sourceModule, $sourceRecordId);
        $map = [];
        foreach ($rows as $row) {
            $map[(string)$row['field_key']] = (string)($row['value_text'] ?? '');
        }

        return $map;
    }

    public function saveResponse(
        string $formKey,
        int $patientId,
        ?int $visitId,
        string $sourceModule,
        int $sourceRecordId,
        array $values,
        array $user
    ): array {
        if (!$this->tablesAvailable()) {
            return ['success' => true, 'skipped' => true, 'errors' => []];
        }

        $fields = $this->listFields($formKey, true);
        if ($fields === []) {
            return ['success' => true, 'skipped' => true, 'errors' => []];
        }

        $posted = is_array($values['configured_fields'] ?? null) ? $values['configured_fields'] : [];
        $errors = [];
        foreach ($fields as $field) {
            $fieldKey = (string)$field['field_key'];
            $value = $this->normalizeValue((string)$field['field_type'], $posted[$fieldKey] ?? null);
            if (!empty($field['is_required']) && trim($value) === '') {
                $errors[] = (string)$field['field_label'] . ' is required.';
            }
            if ($this->textLength($value) > 20000) {
                $errors[] = (string)$field['field_label'] . ' is too long.';
            }
        }
        if ($errors !== []) {
            return $this->failure($errors);
        }

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare(
                'INSERT INTO form_responses (
                    form_key, patient_id, visit_id, source_module, source_record_id, created_by, updated_by
                 ) VALUES (
                    :form_key, :patient_id, :visit_id, :source_module, :source_record_id, :created_by, :updated_by
                 )
                 ON DUPLICATE KEY UPDATE
                    patient_id = VALUES(patient_id),
                    visit_id = VALUES(visit_id),
                    updated_by = VALUES(updated_by)'
            );
            $stmt->execute([
                ':form_key' => $formKey,
                ':patient_id' => $patientId,
                ':visit_id' => $visitId,
                ':source_module' => $sourceModule,
                ':source_record_id' => $sourceRecordId,
                ':created_by' => (int)$user['id'],
                ':updated_by' => (int)$user['id'],
            ]);

            $stmt = $this->pdo->prepare(
                'SELECT id FROM form_responses
                 WHERE form_key = :form_key AND source_module = :source_module AND source_record_id = :source_record_id
                 LIMIT 1'
            );
            $stmt->execute([
                ':form_key' => $formKey,
                ':source_module' => $sourceModule,
                ':source_record_id' => $sourceRecordId,
            ]);
            $responseId = (int)$stmt->fetchColumn();

            $valueStmt = $this->pdo->prepare(
                'INSERT INTO form_response_values (
                    form_response_id, form_field_id, field_key, value_text
                 ) VALUES (
                    :response_id, :field_id, :field_key, :value_text
                 )
                 ON DUPLICATE KEY UPDATE
                    value_text = VALUES(value_text)'
            );

            foreach ($fields as $field) {
                $fieldKey = (string)$field['field_key'];
                $valueStmt->execute([
                    ':response_id' => $responseId,
                    ':field_id' => (int)$field['id'],
                    ':field_key' => $fieldKey,
                    ':value_text' => $this->normalizeValue((string)$field['field_type'], $posted[$fieldKey] ?? null),
                ]);
            }

            $this->pdo->commit();
            return ['success' => true, 'form_response_id' => $responseId, 'errors' => []];
        } catch (Throwable) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return $this->failure(['Unable to save configured form fields.']);
        }
    }

    private function normalizeValue(string $type, mixed $value): string
    {
        if ($type === 'checkbox') {
            return !empty($value) ? 'Yes' : 'No';
        }

        return trim((string)$value);
    }

    private function normalizeOptions(mixed $options): ?string
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', (string)$options) ?: [])));
        if ($lines === []) {
            return null;
        }

        return json_encode($lines, JSON_THROW_ON_ERROR);
    }

    private function makeFieldKey(string $value): string
    {
        $key = strtolower(trim($value));
        $key = preg_replace('/[^a-z0-9]+/', '_', $key) ?: '';
        $key = trim($key, '_');
        if ($key === '') {
            throw new InvalidArgumentException('Field key is required.');
        }

        return substr($key, 0, 100);
    }

    private function textLength(string $value): int
    {
        return function_exists('mb_strlen') ? (int)mb_strlen($value) : strlen($value);
    }

    private function failure(array $errors): array
    {
        return ['success' => false, 'errors' => $errors];
    }
}
