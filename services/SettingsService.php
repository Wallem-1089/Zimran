<?php

declare(strict_types=1);

require_once __DIR__ . '/AuditService.php';

class SettingsService
{
    private const TYPES = ['string', 'integer', 'boolean', 'float', 'array'];

    private PDO $pdo;

    private AuditService $auditService;

    private string $cacheNamespace;

    private array $customValidators = [];

    private static array $rowCache = [];

    private static array $groupCache = [];

    private static array $groupListCache = [];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->auditService = new AuditService($pdo);
        $this->cacheNamespace = (string)spl_object_id($pdo);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $row = $this->settingRow($key);

        if (!$row) {
            return $default;
        }

        $stored = $row['setting_value'];

        if ($stored === null || $stored === '') {
            $stored = $row['default_value'];
        }

        if ($stored === null || $stored === '') {
            return $default;
        }

        try {
            return $this->deserializeValue((string)$stored, (string)$row['setting_type']);
        } catch (Throwable $exception) {
            return $default;
        }
    }

    public function set(
        string $key,
        mixed $value,
        array $metadata = [],
        ?int $actorId = null
    ): array {
        $key = trim($key);

        if ($this->exists($key)) {
            return $this->update($key, $value, $actorId);
        }

        $metadata = $this->normalizeMetadata($key, $metadata);
        $errors = $this->validateDefinition($key, $metadata);

        if ($errors !== []) {
            return $this->failure($errors);
        }

        $validation = $this->validateValue(
            $value,
            $metadata['setting_type'],
            $metadata['validation_rules']
        );

        if (!$validation['success']) {
            return $validation;
        }

        try {
            $this->pdo->beginTransaction();
            $serializedValue = $this->serializeValue(
                $validation['data'],
                $metadata['setting_type']
            );
            $defaultValidation = $this->validateValue(
                $metadata['default_value'],
                $metadata['setting_type'],
                $metadata['validation_rules'],
                true
            );

            if (!$defaultValidation['success']) {
                throw new RuntimeException($defaultValidation['errors'][0]);
            }

            $stmt = $this->pdo->prepare('
                INSERT INTO system_settings (
                    setting_key, setting_value, setting_type, setting_group,
                    description, default_value, validation_rules, is_public,
                    is_editable, is_system, is_sensitive, is_encrypted,
                    sort_order, created_by, updated_by
                ) VALUES (
                    :setting_key, :setting_value, :setting_type, :setting_group,
                    :description, :default_value, :validation_rules, :is_public,
                    :is_editable, :is_system, :is_sensitive, :is_encrypted,
                    :sort_order, :created_by, :updated_by
                )
            ');
            $stmt->execute([
                ':setting_key' => $key,
                ':setting_value' => $serializedValue,
                ':setting_type' => $metadata['setting_type'],
                ':setting_group' => $metadata['setting_group'],
                ':description' => $metadata['description'],
                ':default_value' => $this->serializeValue(
                    $defaultValidation['data'],
                    $metadata['setting_type']
                ),
                ':validation_rules' => $this->encodeRules($metadata['validation_rules']),
                ':is_public' => $metadata['is_public'] ? 1 : 0,
                ':is_editable' => $metadata['is_editable'] ? 1 : 0,
                ':is_system' => $metadata['is_system'] ? 1 : 0,
                ':is_sensitive' => $metadata['is_sensitive'] ? 1 : 0,
                ':is_encrypted' => $metadata['is_encrypted'] ? 1 : 0,
                ':sort_order' => $metadata['sort_order'],
                ':created_by' => $actorId,
                ':updated_by' => $actorId
            ]);

            $settingId = (int)$this->pdo->lastInsertId();
            $this->recordHistory(
                $settingId,
                $key,
                $metadata['setting_group'],
                'SETTING_CREATED',
                null,
                $serializedValue,
                $metadata['is_sensitive'],
                $actorId
            );
            $this->auditChange(
                $actorId,
                'SETTING_CREATED',
                $key,
                $metadata['setting_group'],
                null,
                $serializedValue,
                $metadata['is_sensitive']
            );
            $this->pdo->commit();
            $this->clearCache();

            return $this->success([
                'setting_id' => $settingId,
                'setting_key' => $key
            ], ['setting_id' => $settingId, 'setting_key' => $key]);
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure([$this->safeError($exception, 'Unable to create setting.')]);
        }
    }

    public function update(
        string $key,
        mixed $value,
        ?int $actorId = null
    ): array {
        try {
            $this->pdo->beginTransaction();
            $result = $this->updateWithinTransaction($key, $value, $actorId);
            $this->pdo->commit();
            $this->clearCache();

            return $result;
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure([$this->safeError($exception, 'Unable to update setting.')]);
        }
    }

    public function updateMany(array $settings, ?int $actorId = null): array
    {
        if ($settings === []) {
            return $this->failure(['At least one setting is required.']);
        }

        try {
            $this->pdo->beginTransaction();
            $updated = [];

            foreach ($settings as $key => $value) {
                if (!is_string($key) || trim($key) === '') {
                    throw new RuntimeException('Every bulk setting requires a valid key.');
                }

                $result = $this->updateWithinTransaction($key, $value, $actorId);
                $updated[] = $result['setting_key'];
            }

            $this->pdo->commit();
            $this->clearCache();

            return $this->success(['updated_keys' => $updated]);
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure([$this->safeError($exception, 'Unable to update settings.')]);
        }
    }

    public function delete(string $key, ?int $actorId = null): array
    {
        try {
            $this->pdo->beginTransaction();
            $row = $this->lockSetting($key);

            if (!$row) {
                throw new RuntimeException('Setting not found.');
            }

            if (!empty($row['is_system'])) {
                throw new RuntimeException('System settings cannot be deleted.');
            }

            if (empty($row['is_editable'])) {
                throw new RuntimeException('This setting is not editable.');
            }

            $this->recordHistory(
                (int)$row['id'],
                (string)$row['setting_key'],
                (string)$row['setting_group'],
                'SETTING_DELETED',
                $row['setting_value'],
                null,
                !empty($row['is_sensitive']),
                $actorId
            );
            $this->auditChange(
                $actorId,
                'SETTING_DELETED',
                (string)$row['setting_key'],
                (string)$row['setting_group'],
                $row['setting_value'],
                null,
                !empty($row['is_sensitive'])
            );
            $stmt = $this->pdo->prepare(
                'DELETE FROM system_settings WHERE id = :id'
            );
            $stmt->execute([':id' => $row['id']]);
            $this->pdo->commit();
            $this->clearCache();

            return $this->success(
                ['setting_key' => $key],
                ['setting_key' => $key]
            );
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure([$this->safeError($exception, 'Unable to delete setting.')]);
        }
    }

    public function reset(string $key, ?int $actorId = null): array
    {
        try {
            $this->pdo->beginTransaction();
            $row = $this->lockSetting($key);

            if (!$row) {
                throw new RuntimeException('Setting not found.');
            }

            if (empty($row['is_editable'])) {
                throw new RuntimeException('This setting is not editable.');
            }

            $value = $row['default_value'];
            $validation = $this->validateValue(
                $value,
                (string)$row['setting_type'],
                $this->decodeRules($row['validation_rules']),
                true
            );

            if (!$validation['success']) {
                throw new RuntimeException($validation['errors'][0]);
            }

            $stmt = $this->pdo->prepare('
                UPDATE system_settings
                SET setting_value = default_value, updated_by = :updated_by
                WHERE id = :id
            ');
            $stmt->execute([':updated_by' => $actorId, ':id' => $row['id']]);
            $this->recordHistory(
                (int)$row['id'],
                (string)$row['setting_key'],
                (string)$row['setting_group'],
                'SETTING_RESET',
                $row['setting_value'],
                $row['default_value'],
                !empty($row['is_sensitive']),
                $actorId
            );
            $this->auditChange(
                $actorId,
                'SETTING_RESET',
                (string)$row['setting_key'],
                (string)$row['setting_group'],
                $row['setting_value'],
                $row['default_value'],
                !empty($row['is_sensitive'])
            );
            $this->pdo->commit();
            $this->clearCache();

            return $this->success(['setting_key' => $key], ['setting_key' => $key]);
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure([$this->safeError($exception, 'Unable to reset setting.')]);
        }
    }

    public function exists(string $key): bool
    {
        return $this->settingRow(trim($key)) !== null;
    }

    public function getSettingDefinition(string $key): ?array
    {
        $row = $this->settingRow(trim($key));

        if (!$row) {
            return null;
        }

        return $this->hydrateRows([$row])[0];
    }

    public function getGroup(string $group): array
    {
        $group = trim($group);
        $cacheKey = strtolower($group);

        if (isset(self::$groupCache[$this->cacheNamespace][$cacheKey])) {
            return self::$groupCache[$this->cacheNamespace][$cacheKey];
        }

        $stmt = $this->pdo->prepare('
            SELECT * FROM system_settings
            WHERE setting_group = :setting_group
            ORDER BY sort_order, setting_key
        ');
        $stmt->execute([':setting_group' => $group]);
        $rows = $this->hydrateRows($stmt->fetchAll(PDO::FETCH_ASSOC));
        self::$groupCache[$this->cacheNamespace][$cacheKey] = $rows;

        return $rows;
    }

    public function listGroups(): array
    {
        if (isset(self::$groupListCache[$this->cacheNamespace])) {
            return self::$groupListCache[$this->cacheNamespace];
        }

        $stmt = $this->pdo->query('
            SELECT setting_group,
                   COUNT(*) AS setting_count,
                   SUM(is_editable = 1) AS editable_count,
                   MIN(sort_order) AS first_sort_order
            FROM system_settings
            GROUP BY setting_group
            ORDER BY first_sort_order, setting_group
        ');
        $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        self::$groupListCache[$this->cacheNamespace] = $groups;

        return $groups;
    }

    public function getPublicSettings(): array
    {
        $stmt = $this->pdo->query('
            SELECT * FROM system_settings
            WHERE is_public = 1
            ORDER BY setting_group, sort_order, setting_key
        ');

        return $this->valuesByKey($this->hydrateRows($stmt->fetchAll(PDO::FETCH_ASSOC)));
    }

    public function getSystemSettings(): array
    {
        $stmt = $this->pdo->query('
            SELECT * FROM system_settings
            WHERE is_system = 1
            ORDER BY setting_group, sort_order, setting_key
        ');

        return $this->hydrateRows($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function exportSettings(?string $group = null): array
    {
        $rows = $group === null || trim($group) === ''
            ? $this->search()
            : $this->getGroup(trim($group));
        $export = [];

        foreach ($rows as $row) {
            $export[] = [
                'setting_key' => $row['setting_key'],
                'setting_value' => !empty($row['is_sensitive'])
                    ? '[REDACTED]'
                    : $row['typed_value'],
                'setting_type' => $row['setting_type'],
                'setting_group' => $row['setting_group'],
                'description' => $row['description'],
                'default_value' => !empty($row['is_sensitive'])
                    ? '[REDACTED]'
                    : $this->deserializeNullable(
                        $row['default_value'],
                        (string)$row['setting_type']
                    ),
                'validation_rules' => $row['rules'],
                'is_public' => (bool)$row['is_public'],
                'is_editable' => (bool)$row['is_editable'],
                'is_system' => (bool)$row['is_system'],
                'is_sensitive' => (bool)$row['is_sensitive'],
                'sort_order' => (int)$row['sort_order']
            ];
        }

        return $export;
    }

    public function search(string $search = '', ?string $group = null): array
    {
        $where = [];
        $parameters = [];

        if (trim($search) !== '') {
            $where[] = '(setting_key LIKE :search_key OR description LIKE :search_description)';
            $term = '%' . trim($search) . '%';
            $parameters[':search_key'] = $term;
            $parameters[':search_description'] = $term;
        }

        if ($group !== null && trim($group) !== '') {
            $where[] = 'setting_group = :setting_group';
            $parameters[':setting_group'] = trim($group);
        }

        $sql = 'SELECT * FROM system_settings';
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY setting_group, sort_order, setting_key';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parameters);

        return $this->hydrateRows($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getHistory(
        ?string $key = null,
        ?string $group = null,
        int $page = 1,
        int $perPage = 50
    ): array {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $where = [];
        $parameters = [];

        if ($key !== null && trim($key) !== '') {
            $where[] = 'h.setting_key = :setting_key';
            $parameters[':setting_key'] = trim($key);
        }

        if ($group !== null && trim($group) !== '') {
            $where[] = 'h.setting_group = :setting_group';
            $parameters[':setting_group'] = trim($group);
        }

        $condition = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);
        $count = $this->pdo->prepare(
            'SELECT COUNT(*) FROM system_setting_history h' . $condition
        );
        $count->execute($parameters);

        $stmt = $this->pdo->prepare('
            SELECT h.*, u.username, u.first_name, u.last_name
            FROM system_setting_history h
            LEFT JOIN users u ON u.id = h.changed_by
            ' . $condition . '
            ORDER BY h.created_at DESC, h.id DESC
            LIMIT :limit OFFSET :offset
        ');
        foreach ($parameters as $parameter => $value) {
            $stmt->bindValue($parameter, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'success' => true,
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => (int)$count->fetchColumn(),
            'page' => $page,
            'per_page' => $perPage,
            'errors' => []
        ];
    }

    public function getString(string $key, string $default = ''): string
    {
        $value = $this->get($key, $default);
        return is_scalar($value) ? (string)$value : $default;
    }

    public function getInteger(string $key, int $default = 0): int
    {
        $value = $this->get($key, $default);
        return is_numeric($value) ? (int)$value : $default;
    }

    public function getBoolean(string $key, bool $default = false): bool
    {
        $value = $this->get($key, $default);

        if (is_bool($value)) {
            return $value;
        }

        $normalized = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return $normalized ?? $default;
    }

    public function getFloat(string $key, float $default = 0.0): float
    {
        $value = $this->get($key, $default);
        return is_numeric($value) ? (float)$value : $default;
    }

    public function getArray(string $key, array $default = []): array
    {
        $value = $this->get($key, $default);
        return is_array($value) ? $value : $default;
    }

    public function registerValidator(string $name, callable $validator): void
    {
        $this->customValidators[trim($name)] = $validator;
    }

    public function clearCache(?string $key = null): void
    {
        if ($key === null) {
            unset(
                self::$rowCache[$this->cacheNamespace],
                self::$groupCache[$this->cacheNamespace],
                self::$groupListCache[$this->cacheNamespace]
            );
            return;
        }

        unset(self::$rowCache[$this->cacheNamespace][$key]);
        unset(
            self::$groupCache[$this->cacheNamespace],
            self::$groupListCache[$this->cacheNamespace]
        );
    }

    public function recordExport(?int $actorId, ?string $group = null): bool
    {
        return $this->auditService->log(
            $actorId,
            null,
            'Administration',
            'SETTING_EXPORTED',
            'Exported system settings' . ($group ? ' for category ' . $group : '') . '.',
            null,
            'INFO',
            'SETTING_EXPORTED'
        );
    }

    private function updateWithinTransaction(
        string $key,
        mixed $value,
        ?int $actorId
    ): array {
        $row = $this->lockSetting(trim($key));

        if (!$row) {
            throw new RuntimeException('Setting not found: ' . trim($key) . '.');
        }

        if (empty($row['is_editable'])) {
            throw new RuntimeException('Setting is not editable: ' . trim($key) . '.');
        }

        $validation = $this->validateValue(
            $value,
            (string)$row['setting_type'],
            $this->decodeRules($row['validation_rules'])
        );

        if (!$validation['success']) {
            throw new RuntimeException(
                trim($key) . ': ' . implode(' ', $validation['errors'])
            );
        }

        $newValue = $this->serializeValue(
            $validation['data'],
            (string)$row['setting_type']
        );
        $oldValue = $row['setting_value'];

        $stmt = $this->pdo->prepare('
            UPDATE system_settings
            SET setting_value = :setting_value, updated_by = :updated_by
            WHERE id = :id
        ');
        $stmt->execute([
            ':setting_value' => $newValue,
            ':updated_by' => $actorId,
            ':id' => $row['id']
        ]);

        $this->recordHistory(
            (int)$row['id'],
            (string)$row['setting_key'],
            (string)$row['setting_group'],
            'SETTING_UPDATED',
            $oldValue,
            $newValue,
            !empty($row['is_sensitive']),
            $actorId
        );
        $this->auditChange(
            $actorId,
            'SETTING_UPDATED',
            (string)$row['setting_key'],
            (string)$row['setting_group'],
            $oldValue,
            $newValue,
            !empty($row['is_sensitive'])
        );

        return $this->success(
            ['setting_id' => (int)$row['id'], 'setting_key' => (string)$row['setting_key']],
            ['setting_id' => (int)$row['id'], 'setting_key' => (string)$row['setting_key']]
        );
    }

    private function validateValue(
        mixed $value,
        string $type,
        array $rules,
        bool $allowEmptyDefault = false
    ): array {
        if (!in_array($type, self::TYPES, true)) {
            return $this->failure(['Unsupported setting type.']);
        }

        $isEmpty = $value === null || $value === '';

        if ($isEmpty && !empty($rules['required']) && !$allowEmptyDefault) {
            return $this->failure(['A value is required.']);
        }

        if ($isEmpty) {
            return $this->success(null);
        }

        try {
            $typed = match ($type) {
                'string' => (string)$value,
                'integer' => $this->toInteger($value),
                'boolean' => $this->toBoolean($value),
                'float' => $this->toFloat($value),
                'array' => $this->toArray($value)
            };
        } catch (Throwable $exception) {
            return $this->failure([$exception->getMessage()]);
        }

        $errors = [];

        if (isset($rules['allowed']) && is_array($rules['allowed'])
            && !in_array($typed, $rules['allowed'], true)
        ) {
            $errors[] = 'Value is not in the allowed list.';
        }

        if (is_numeric($typed)) {
            if (isset($rules['min']) && $typed < $rules['min']) {
                $errors[] = 'Value must be at least ' . $rules['min'] . '.';
            }
            if (isset($rules['max']) && $typed > $rules['max']) {
                $errors[] = 'Value must not exceed ' . $rules['max'] . '.';
            }
        }

        if (is_string($typed)) {
            if (isset($rules['min_length']) && mb_strlen($typed) < (int)$rules['min_length']) {
                $errors[] = 'Value is shorter than the minimum length.';
            }
            if (isset($rules['max_length']) && mb_strlen($typed) > (int)$rules['max_length']) {
                $errors[] = 'Value exceeds the maximum length.';
            }
            if (!empty($rules['regex'])) {
                $pattern = '~' . str_replace('~', '\\~', (string)$rules['regex']) . '~u';
                if (@preg_match($pattern, $typed) !== 1) {
                    $errors[] = 'Value does not match the required format.';
                }
            }
            if (($rules['format'] ?? null) === 'email'
                && filter_var($typed, FILTER_VALIDATE_EMAIL) === false
            ) {
                $errors[] = 'Value must be a valid email address.';
            }
            if (($rules['format'] ?? null) === 'timezone'
                && !in_array($typed, timezone_identifiers_list(), true)
            ) {
                $errors[] = 'Value must be a valid timezone.';
            }
        }

        if (is_array($typed) && isset($rules['schema_values'])
            && is_array($rules['schema_values'])
        ) {
            if ($typed === [] && !empty($rules['required'])) {
                $errors[] = 'At least one supported value is required.';
            }

            if (count($typed) !== count(array_unique($typed, SORT_REGULAR))) {
                $errors[] = 'Configured values must not contain duplicates.';
            }

            foreach ($typed as $configuredValue) {
                if (!is_string($configuredValue)
                    || !in_array($configuredValue, $rules['schema_values'], true)
                ) {
                    $errors[] = 'Configured values contain an option unsupported by the database schema.';
                    break;
                }
            }
        }

        if (!empty($rules['callback'])) {
            $name = (string)$rules['callback'];
            if (!isset($this->customValidators[$name])) {
                $errors[] = 'Required custom validator is unavailable.';
            } else {
                $customResult = ($this->customValidators[$name])($typed, $rules);
                if ($customResult !== true) {
                    $errors[] = is_string($customResult)
                        ? $customResult
                        : 'Custom validation failed.';
                }
            }
        }

        return $errors === []
            ? $this->success($typed)
            : $this->failure($errors);
    }

    private function settingRow(string $key): ?array
    {
        if (array_key_exists($key, self::$rowCache[$this->cacheNamespace] ?? [])) {
            return self::$rowCache[$this->cacheNamespace][$key];
        }

        try {
            $stmt = $this->pdo->prepare(
                'SELECT * FROM system_settings WHERE setting_key = :setting_key LIMIT 1'
            );
            $stmt->execute([':setting_key' => $key]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Throwable $exception) {
            // Compatibility fallback for installations still applying the
            // versioned settings migration. Public getters retain defaults.
            $row = null;
        }
        self::$rowCache[$this->cacheNamespace][$key] = $row;

        return $row;
    }

    private function lockSetting(string $key): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM system_settings WHERE setting_key = :setting_key FOR UPDATE'
        );
        $stmt->execute([':setting_key' => $key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    private function hydrateRows(array $rows): array
    {
        foreach ($rows as &$row) {
            $row['typed_value'] = $this->deserializeNullable(
                $row['setting_value'] !== null && $row['setting_value'] !== ''
                    ? $row['setting_value']
                    : $row['default_value'],
                (string)$row['setting_type']
            );
            $row['rules'] = $this->decodeRules($row['validation_rules']);
        }
        unset($row);

        return $rows;
    }

    private function valuesByKey(array $rows): array
    {
        $values = [];
        foreach ($rows as $row) {
            $values[$row['setting_key']] = $row['typed_value'];
        }
        return $values;
    }

    private function normalizeMetadata(string $key, array $metadata): array
    {
        return [
            'setting_type' => strtolower(trim((string)($metadata['setting_type'] ?? 'string'))),
            'setting_group' => trim((string)($metadata['setting_group'] ?? ucfirst(strtok($key, '.')))),
            'description' => $this->nullableString($metadata['description'] ?? null),
            'default_value' => $metadata['default_value'] ?? null,
            'validation_rules' => $this->normalizeRules($metadata['validation_rules'] ?? []),
            'is_public' => !empty($metadata['is_public']),
            'is_editable' => !array_key_exists('is_editable', $metadata) || !empty($metadata['is_editable']),
            'is_system' => !empty($metadata['is_system']),
            'is_sensitive' => !empty($metadata['is_sensitive']),
            'is_encrypted' => !empty($metadata['is_encrypted']),
            'sort_order' => (int)($metadata['sort_order'] ?? 0)
        ];
    }

    private function validateDefinition(string $key, array $metadata): array
    {
        $errors = [];
        if (preg_match('/^[a-z][a-z0-9_.-]{1,190}$/', $key) !== 1) {
            $errors[] = 'Setting key must use lowercase letters, numbers, dots, underscores, or hyphens.';
        }
        if (!in_array($metadata['setting_type'], self::TYPES, true)) {
            $errors[] = 'Unsupported setting type.';
        }
        if ($metadata['setting_group'] === '') {
            $errors[] = 'Setting category is required.';
        }
        if ($metadata['is_encrypted']) {
            $errors[] = 'Encrypted setting storage is reserved for a future encryption provider.';
        }
        return $errors;
    }

    private function normalizeRules(mixed $rules): array
    {
        if (is_array($rules)) {
            return $rules;
        }
        if (!is_string($rules) || trim($rules) === '') {
            return [];
        }
        $decoded = json_decode($rules, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function decodeRules(mixed $rules): array
    {
        return $this->normalizeRules($rules);
    }

    private function encodeRules(array $rules): ?string
    {
        return $rules === []
            ? null
            : json_encode($rules, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    private function serializeValue(mixed $value, string $type): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean' => $value ? '1' : '0',
            'array' => json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
            default => (string)$value
        };
    }

    private function deserializeValue(string $value, string $type): mixed
    {
        return match ($type) {
            'integer' => (int)$value,
            'boolean' => in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true),
            'float' => (float)$value,
            'array' => $this->toArray($value),
            default => $value
        };
    }

    private function deserializeNullable(mixed $value, string $type): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }
        return $this->deserializeValue((string)$value, $type);
    }

    private function toInteger(mixed $value): int
    {
        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException('Value must be an integer.');
        }
        return (int)$value;
    }

    private function toBoolean(mixed $value): bool
    {
        $boolean = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($boolean === null) {
            throw new InvalidArgumentException('Value must be boolean.');
        }
        return $boolean;
    }

    private function toFloat(mixed $value): float
    {
        if (!is_numeric($value)) {
            throw new InvalidArgumentException('Value must be numeric.');
        }
        return (float)$value;
    }

    private function toArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (!is_string($value)) {
            throw new InvalidArgumentException('Value must be an array or JSON array.');
        }
        $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
            throw new InvalidArgumentException('Value must be a JSON array.');
        }
        return $decoded;
    }

    private function recordHistory(
        ?int $settingId,
        string $key,
        string $group,
        string $action,
        mixed $oldValue,
        mixed $newValue,
        bool $sensitive,
        ?int $actorId
    ): void {
        $stmt = $this->pdo->prepare('
            INSERT INTO system_setting_history (
                setting_id, setting_key, setting_group, action,
                old_value, new_value, is_sensitive, changed_by
            ) VALUES (
                :setting_id, :setting_key, :setting_group, :action,
                :old_value, :new_value, :is_sensitive, :changed_by
            )
        ');
        $stmt->execute([
            ':setting_id' => $settingId,
            ':setting_key' => $key,
            ':setting_group' => $group,
            ':action' => $action,
            ':old_value' => $sensitive && $oldValue !== null ? '[REDACTED]' : $oldValue,
            ':new_value' => $sensitive && $newValue !== null ? '[REDACTED]' : $newValue,
            ':is_sensitive' => $sensitive ? 1 : 0,
            ':changed_by' => $actorId
        ]);
    }

    private function auditChange(
        ?int $actorId,
        string $action,
        string $key,
        string $group,
        mixed $oldValue,
        mixed $newValue,
        bool $sensitive
    ): void {
        $old = $sensitive ? '[REDACTED]' : $this->auditValue($oldValue);
        $new = $sensitive ? '[REDACTED]' : $this->auditValue($newValue);
        $description = sprintf(
            '%s setting %s in %s. Old value: %s; New value: %s.',
            str_replace('_', ' ', $action),
            $key,
            $group,
            $old,
            $new
        );

        if (!$this->auditService->log(
            $actorId,
            null,
            'Administration',
            $action,
            $description,
            null,
            'INFO',
            $action
        )) {
            throw new RuntimeException('Unable to record settings audit event.');
        }
    }

    private function auditValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '[EMPTY]';
        }
        $value = is_scalar($value) ? (string)$value : json_encode($value);
        return mb_substr((string)$value, 0, 200);
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string)$value);
        return $value === '' ? null : $value;
    }

    private function success(mixed $data, array $additional = []): array
    {
        return array_merge([
            'success' => true,
            'data' => $data,
            'errors' => []
        ], $additional);
    }

    private function failure(array $errors): array
    {
        return [
            'success' => false,
            'data' => null,
            'errors' => array_values($errors)
        ];
    }

    private function safeError(Throwable $exception, string $fallback): string
    {
        if ($exception instanceof RuntimeException
            || $exception instanceof InvalidArgumentException
        ) {
            return $exception->getMessage();
        }
        error_log($exception->getMessage());
        return $fallback;
    }

    private function rollback(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }
}
