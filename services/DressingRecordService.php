<?php

declare(strict_types=1);

require_once __DIR__ . '/AuditService.php';
require_once __DIR__ . '/PatientStockUsageService.php';
require_once __DIR__ . '/PermissionService.php';

class DressingRecordService
{
    private AuditService $auditService;
    private PermissionService $permissionService;
    private PatientStockUsageService $patientStockUsageService;

    public function __construct(
        private PDO $pdo,
        ?AuditService $auditService = null,
        ?PermissionService $permissionService = null,
        ?PatientStockUsageService $patientStockUsageService = null
    ) {
        $this->auditService = $auditService ?? new AuditService($pdo);
        $this->permissionService = $permissionService ?? new PermissionService($pdo);
        $this->patientStockUsageService = $patientStockUsageService ?? new PatientStockUsageService(
            $pdo,
            null,
            null,
            $this->auditService,
            $this->permissionService
        );
    }

    public function create(array $data, array $user): array
    {
        try {
            $this->pdo->beginTransaction();

            $visit = $this->lockVisit((int)($data['visit_id'] ?? 0));
            $errors = $this->validateMutation($visit, $user, 'create_nursing');
            if (isset($data['patient_id']) && (int)$data['patient_id'] !== (int)$visit['patient_id']) {
                $errors[] = 'Patient and encounter do not match.';
            }

            $payload = $this->preparePayload($data);
            $errors = array_merge($errors, $payload['errors']);

            if ($errors !== []) {
                $this->rollback();
                return $this->failure($errors);
            }

            $stmt = $this->pdo->prepare('
                INSERT INTO dressing_records (
                    visit_id, patient_id, wound_site, wound_condition,
                    dressing_done, supplies_used, next_dressing_date, recorded_by
                ) VALUES (
                    :visit_id, :patient_id, :wound_site, :wound_condition,
                    :dressing_done, :supplies_used, :next_dressing_date, :recorded_by
                )
            ');
            $stmt->execute([
                ':visit_id' => (int)$visit['id'],
                ':patient_id' => (int)$visit['patient_id'],
                ':wound_site' => $payload['data']['wound_site'],
                ':wound_condition' => $payload['data']['wound_condition'],
                ':dressing_done' => $payload['data']['dressing_done'],
                ':supplies_used' => $payload['data']['supplies_used'],
                ':next_dressing_date' => $payload['data']['next_dressing_date'],
                ':recorded_by' => (int)$user['id'],
            ]);

            $recordId = (int)$this->pdo->lastInsertId();
            $stockUsageRows = $this->prepareStockUsageRows($data, $payload['data']['wound_site']);
            if ($stockUsageRows['errors'] !== []) {
                $this->rollback();
                return $this->failure($stockUsageRows['errors']);
            }

            $stockUsageIds = [];
            foreach ($stockUsageRows['data'] as $stockUsageRow) {
                $usageResult = $this->patientStockUsageService->createUsage([
                    'visit_id' => (int)$visit['id'],
                    'patient_id' => (int)$visit['patient_id'],
                    'department_id' => $stockUsageRow['department_id'] ?? null,
                    'inventory_item_id' => $stockUsageRow['inventory_item_id'],
                    'quantity' => $stockUsageRow['quantity'],
                    'usage_reason' => $stockUsageRow['usage_reason'],
                    'source_module' => 'Dressing',
                    'source_record_id' => $recordId,
                    'request_billing' => !empty($stockUsageRow['request_billing']) ? '1' : '',
                ], $user);

                if (!($usageResult['success'] ?? false)) {
                    $this->rollback();
                    return $this->failure($usageResult['errors'] ?? ['Unable to record dressing stock used.']);
                }
                $stockUsageIds[] = (int)$usageResult['patient_stock_usage_id'];
            }

            if (!$this->audit('DRESSING_RECORD_CREATED', $visit, $user, 'Created dressing record #' . $recordId . '.')) {
                throw new RuntimeException('Unable to audit dressing record creation.');
            }

            $this->pdo->commit();

            return [
                'success' => true,
                'dressing_record_id' => $recordId,
                'patient_stock_usage_ids' => $stockUsageIds,
                'visit_id' => (int)$visit['id'],
                'patient_id' => (int)$visit['patient_id'],
                'errors' => [],
            ];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to save dressing record.']);
        }
    }

    public function update(int $recordId, array $data, array $user): array
    {
        try {
            $this->pdo->beginTransaction();

            $record = $this->lockRecord($recordId);
            if (!$record) {
                $this->rollback();
                return $this->failure(['Dressing record not found.']);
            }

            $visit = $this->lockVisit((int)$record['visit_id']);
            $errors = $this->validateMutation($visit, $user, 'edit_nursing');
            if (isset($data['patient_id']) && (int)$data['patient_id'] !== (int)$visit['patient_id']) {
                $errors[] = 'Patient and encounter do not match.';
            }

            $payload = $this->preparePayload($data);
            $errors = array_merge($errors, $payload['errors']);

            if ($errors !== []) {
                $this->rollback();
                return $this->failure($errors);
            }

            $stmt = $this->pdo->prepare('
                UPDATE dressing_records
                SET wound_site = :wound_site,
                    wound_condition = :wound_condition,
                    dressing_done = :dressing_done,
                    supplies_used = :supplies_used,
                    next_dressing_date = :next_dressing_date
                WHERE id = :id
            ');
            $stmt->execute([
                ':wound_site' => $payload['data']['wound_site'],
                ':wound_condition' => $payload['data']['wound_condition'],
                ':dressing_done' => $payload['data']['dressing_done'],
                ':supplies_used' => $payload['data']['supplies_used'],
                ':next_dressing_date' => $payload['data']['next_dressing_date'],
                ':id' => $recordId,
            ]);

            if (!$this->audit('DRESSING_RECORD_UPDATED', $visit, $user, 'Updated dressing record #' . $recordId . '.')) {
                throw new RuntimeException('Unable to audit dressing record update.');
            }

            $this->pdo->commit();

            return [
                'success' => true,
                'dressing_record_id' => $recordId,
                'visit_id' => (int)$visit['id'],
                'patient_id' => (int)$visit['patient_id'],
                'errors' => [],
            ];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to update dressing record.']);
        }
    }

    public function getById(int $recordId, ?array $user = null): ?array
    {
        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE dr.id = :id LIMIT 1');
        $stmt->execute([':id' => $recordId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || ($user !== null && !$this->canViewRow($row, $user))) {
            return null;
        }

        return $this->decorateRow($row);
    }

    public function listByVisit(int $visitId, ?array $user = null): array
    {
        $visit = $this->visitById($visitId);
        if (!$visit || ($user !== null && !$this->canViewRow($visit, $user))) {
            return [];
        }

        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE dr.visit_id = :visit_id ORDER BY dr.created_at DESC, dr.id DESC');
        $stmt->execute([':visit_id' => $visitId]);
        return array_map([$this, 'decorateRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function listByPatient(int $patientId, ?array $user = null): array
    {
        if ($patientId <= 0 || ($user !== null && !$this->permissionService->canViewNursing($patientId, $user))) {
            return [];
        }

        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE dr.patient_id = :patient_id ORDER BY dr.created_at DESC, dr.id DESC');
        $stmt->execute([':patient_id' => $patientId]);
        return array_map([$this, 'decorateRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    private function preparePayload(array $data): array
    {
        $errors = [];
        $woundSite = trim((string)($data['wound_site'] ?? ''));
        if ($woundSite === '') {
            $errors[] = 'Wound site is required.';
        } elseif ($this->textLength($woundSite) > 255) {
            $errors[] = 'Wound site is too long.';
        }

        $payload = ['wound_site' => $woundSite];
        foreach (['wound_condition', 'dressing_done', 'supplies_used'] as $field) {
            $payload[$field] = $this->nullableText($data[$field] ?? null);
            if ($payload[$field] !== null && $this->textLength($payload[$field]) > 5000) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is too long.';
            }
        }

        $nextDate = trim((string)($data['next_dressing_date'] ?? ''));
        if ($nextDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $nextDate)) {
            $errors[] = 'Next dressing date is invalid.';
        }
        $payload['next_dressing_date'] = $nextDate !== '' ? $nextDate : null;

        return ['errors' => $errors, 'data' => $payload];
    }

    private function prepareStockUsageRows(array $data, string $woundSite): array
    {
        $errors = [];
        $itemIds = (array)($data['stock_inventory_item_id'] ?? []);
        $quantities = (array)($data['stock_quantity'] ?? []);
        $reasons = (array)($data['stock_usage_reason'] ?? []);
        $departmentId = (int)($data['stock_department_id'] ?? 0);
        $requestBilling = !empty($data['stock_request_billing']);
        $rows = [];

        foreach ($itemIds as $index => $itemIdRaw) {
            $itemId = (int)$itemIdRaw;
            $quantityRaw = $quantities[$index] ?? '';
            if ($itemId <= 0 && ($quantityRaw === '' || (float)$quantityRaw <= 0)) {
                continue;
            }
            if ($itemId <= 0) {
                $errors[] = 'Inventory item is required for each stock-used row.';
                continue;
            }
            if (!is_numeric($quantityRaw) || (float)$quantityRaw <= 0) {
                $errors[] = 'Quantity used must be greater than zero for each stock-used row.';
                continue;
            }

            $reason = trim((string)($reasons[$index] ?? ''));
            if ($reason === '') {
                $reason = 'Dressing care: ' . $woundSite;
            }

            $rows[] = [
                'department_id' => $departmentId > 0 ? $departmentId : null,
                'inventory_item_id' => $itemId,
                'quantity' => number_format((float)$quantityRaw, 2, '.', ''),
                'usage_reason' => $reason,
                'request_billing' => $requestBilling,
            ];
        }

        return ['errors' => array_values(array_unique($errors)), 'data' => $rows];
    }

    private function validateMutation(array $visit, array $user, string $permission): array
    {
        $errors = [];
        if (!$this->permissionService->hasPermission($permission, $user)) {
            $errors[] = 'You are not allowed to perform this dressing action.';
        }
        if (!$this->permissionService->canViewEncounter($visit, $user)) {
            $errors[] = 'You cannot access this encounter.';
        }
        if (!$this->permissionService->isAdministrator($user) && in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true)) {
            $errors[] = 'Completed or cancelled encounters are read-only.';
        }
        if (!$this->permissionService->isAdministrator($user) && (string)($user['role_name'] ?? '') !== 'Nurse') {
            $errors[] = 'Only nurses may record dressing book entries.';
        }
        return $errors;
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

    private function lockRecord(int $recordId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM dressing_records WHERE id = :id FOR UPDATE');
        $stmt->execute([':id' => $recordId]);
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

    private function decorateRow(array $row): array
    {
        $row['summary'] = $this->summarize($row);
        return $row;
    }

    private function summarize(array $row): string
    {
        foreach (['wound_condition', 'dressing_done', 'supplies_used'] as $field) {
            $text = trim((string)($row[$field] ?? ''));
            if ($text !== '') {
                return $this->textLength($text) > 180 ? substr($text, 0, 177) . '...' : $text;
            }
        }

        return 'Dressing record for ' . (string)($row['wound_site'] ?? 'wound care') . '.';
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

    private function baseSelect(): string
    {
        return '
            SELECT dr.*,
                   v.visit_number,
                   v.visit_status,
                   p.hospital_number,
                   p.first_name,
                   p.last_name,
                   CONCAT(recorded.first_name, " ", recorded.last_name) AS recorded_by_name
            FROM dressing_records dr
            INNER JOIN visits v ON v.id = dr.visit_id
            INNER JOIN patients p ON p.id = dr.patient_id
            LEFT JOIN users recorded ON recorded.id = dr.recorded_by
        ';
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
