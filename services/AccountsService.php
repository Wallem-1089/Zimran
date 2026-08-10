<?php

declare(strict_types=1);

require_once __DIR__ . '/AuditService.php';
require_once __DIR__ . '/PermissionService.php';

class AccountsService
{
    private AuditService $auditService;
    private PermissionService $permissionService;

    public function __construct(
        private PDO $pdo,
        ?AuditService $auditService = null,
        ?PermissionService $permissionService = null
    ) {
        $this->auditService = $auditService ?? new AuditService($pdo);
        $this->permissionService = $permissionService ?? new PermissionService($pdo);
    }

    public function createItem(array $data, array $user): array
    {
        try {
            $this->assertCanCreate($user);
            $payload = $this->normalizePayload($data);
            if ($payload['errors'] !== []) {
                return $this->failure($payload['errors']);
            }

            $this->pdo->beginTransaction();

            if ($this->getItemByCode($payload['data']['item_code']) !== null) {
                $this->rollback();
                return $this->failure(['Item code already exists.']);
            }

            $stmt = $this->pdo->prepare('
                INSERT INTO billable_items (
                    item_code, item_name, item_type, department_id, description,
                    unit_price, unit, is_active, created_by, created_at, updated_at
                ) VALUES (
                    :item_code, :item_name, :item_type, :department_id, :description,
                    :unit_price, :unit, :is_active, :created_by, NOW(), NOW()
                )
            ');
            $stmt->execute([
                ':item_code' => $payload['data']['item_code'],
                ':item_name' => $payload['data']['item_name'],
                ':item_type' => $payload['data']['item_type'],
                ':department_id' => $payload['data']['department_id'],
                ':description' => $payload['data']['description'],
                ':unit_price' => $payload['data']['unit_price'],
                ':unit' => $payload['data']['unit'],
                ':is_active' => $payload['data']['is_active'],
                ':created_by' => (int)$user['id'],
            ]);
            $itemId = (int)$this->pdo->lastInsertId();

            if (!$this->audit((int)$user['id'], (int)($payload['data']['department_id'] ?? 0) ?: null, 'BILLABLE_ITEM_CREATED', 'Created billable item #' . $itemId . '.')) {
                throw new RuntimeException('Unable to audit item creation.');
            }

            $this->pdo->commit();

            return ['success' => true, 'billable_item_id' => $itemId, 'errors' => []];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to create billable item.']);
        }
    }

    public function getItemById(int $itemId, ?array $user = null): ?array
    {
        $item = $this->fetchItemById($itemId);
        if (!$item) {
            return null;
        }

        if ($user !== null && !$this->canViewRow($item, $user)) {
            return null;
        }

        return $item;
    }

    public function getItemByCode(string $itemCode, ?array $user = null): ?array
    {
        $itemCode = strtoupper(trim($itemCode));
        if ($itemCode === '') {
            return null;
        }

        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE bi.item_code = :item_code LIMIT 1');
        $stmt->execute([':item_code' => $itemCode]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            return null;
        }

        $item = $this->decorate($item);
        if ($user !== null && !$this->canViewRow($item, $user)) {
            return null;
        }

        return $item;
    }

    public function listItems(array $filters = [], ?array $user = null): array
    {
        if ($user !== null && !$this->permissionService->canViewBillableItems($user)) {
            return [];
        }

        [$where, $params] = $this->buildFilters($filters);
        $sql = $this->baseSelect() . $where . ' ORDER BY bi.is_active DESC, bi.item_name ASC, bi.id DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return array_map([$this, 'decorate'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function searchItems(array $filters = [], ?array $user = null): array
    {
        return $this->listItems($filters, $user);
    }

    public function updateItem(int $itemId, array $data, array $user): array
    {
        try {
            $this->assertCanEdit($user);
            $this->pdo->beginTransaction();

            $existing = $this->fetchItemByIdForUpdate($itemId);
            if (!$existing) {
                $this->rollback();
                return $this->failure(['Billable item not found.']);
            }

            $payload = $this->normalizePayload($data, $existing);
            if ($payload['errors'] !== []) {
                $this->rollback();
                return $this->failure($payload['errors']);
            }

            if ($payload['data']['item_code'] !== $existing['item_code']
                && $this->getItemByCode($payload['data']['item_code']) !== null
            ) {
                $this->rollback();
                return $this->failure(['Item code already exists.']);
            }

            $stmt = $this->pdo->prepare('
                UPDATE billable_items
                SET item_code = :item_code,
                    item_name = :item_name,
                    item_type = :item_type,
                    department_id = :department_id,
                    description = :description,
                    unit_price = :unit_price,
                    unit = :unit,
                    updated_by = :updated_by,
                    updated_at = NOW()
                WHERE id = :id
            ');
            $stmt->execute([
                ':item_code' => $payload['data']['item_code'],
                ':item_name' => $payload['data']['item_name'],
                ':item_type' => $payload['data']['item_type'],
                ':department_id' => $payload['data']['department_id'],
                ':description' => $payload['data']['description'],
                ':unit_price' => $payload['data']['unit_price'],
                ':unit' => $payload['data']['unit'],
                ':updated_by' => (int)$user['id'],
                ':id' => $itemId,
            ]);

            if (!$this->audit((int)$user['id'], (int)($payload['data']['department_id'] ?? 0) ?: null, 'BILLABLE_ITEM_UPDATED', 'Updated billable item #' . $itemId . '.')) {
                throw new RuntimeException('Unable to audit item update.');
            }

            $this->pdo->commit();

            return ['success' => true, 'billable_item_id' => $itemId, 'errors' => []];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to update billable item.']);
        }
    }

    public function activateItem(int $itemId, array $user): array
    {
        return $this->toggleStatus($itemId, true, $user);
    }

    public function deactivateItem(int $itemId, array $user): array
    {
        return $this->toggleStatus($itemId, false, $user);
    }

    private function toggleStatus(int $itemId, bool $active, array $user): array
    {
        try {
            $this->assertCanManageStatus($user);
            $this->pdo->beginTransaction();

            $existing = $this->fetchItemByIdForUpdate($itemId);
            if (!$existing) {
                $this->rollback();
                return $this->failure(['Billable item not found.']);
            }

            if ((int)$existing['is_active'] === ($active ? 1 : 0)) {
                $this->rollback();
                return $this->failure([$active ? 'Billable item is already active.' : 'Billable item is already inactive.']);
            }

            $stmt = $this->pdo->prepare('
                UPDATE billable_items
                SET is_active = :is_active,
                    updated_by = :updated_by,
                    updated_at = NOW()
                WHERE id = :id
            ');
            $stmt->execute([
                ':is_active' => $active ? 1 : 0,
                ':updated_by' => (int)$user['id'],
                ':id' => $itemId,
            ]);

            if (!$this->audit((int)$user['id'], (int)($existing['department_id'] ?? 0) ?: null, $active ? 'BILLABLE_ITEM_ACTIVATED' : 'BILLABLE_ITEM_DEACTIVATED', ($active ? 'Activated' : 'Deactivated') . ' billable item #' . $itemId . '.')) {
                throw new RuntimeException('Unable to audit item status change.');
            }

            $this->pdo->commit();

            return ['success' => true, 'billable_item_id' => $itemId, 'errors' => []];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to update billable item status.']);
        }
    }

    private function buildFilters(array $filters): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['item_code'])) {
            $where[] = 'bi.item_code LIKE :item_code';
            $params[':item_code'] = '%' . strtoupper(trim((string)$filters['item_code'])) . '%';
        }

        if (!empty($filters['item_name'])) {
            $where[] = 'bi.item_name LIKE :item_name';
            $params[':item_name'] = '%' . trim((string)$filters['item_name']) . '%';
        }

        if (!empty($filters['item_type']) && in_array((string)$filters['item_type'], ['Service', 'Product'], true)) {
            $where[] = 'bi.item_type = :item_type';
            $params[':item_type'] = (string)$filters['item_type'];
        }

        if (!empty($filters['department_id'])) {
            $where[] = 'bi.department_id = :department_id';
            $params[':department_id'] = (int)$filters['department_id'];
        }

        $status = strtolower(trim((string)($filters['status'] ?? 'all')));
        if ($status === 'active') {
            $where[] = 'bi.is_active = 1';
        } elseif ($status === 'inactive') {
            $where[] = 'bi.is_active = 0';
        }

        return [
            $where === [] ? '' : ' WHERE ' . implode(' AND ', $where),
            $params,
        ];
    }

    private function normalizePayload(array $data, ?array $existing = null): array
    {
        $errors = [];

        $itemCode = strtoupper(trim((string)($data['item_code'] ?? ($existing['item_code'] ?? ''))));
        $itemName = trim((string)($data['item_name'] ?? ($existing['item_name'] ?? '')));
        $itemType = trim((string)($data['item_type'] ?? ($existing['item_type'] ?? '')));
        $description = trim((string)($data['description'] ?? ($existing['description'] ?? '')));
        $unit = trim((string)($data['unit'] ?? ($existing['unit'] ?? '')));
        $unitPriceRaw = $data['unit_price'] ?? ($existing['unit_price'] ?? null);
        $departmentId = $data['department_id'] ?? ($existing['department_id'] ?? null);
        $isActive = isset($data['is_active']) ? (int)!!$data['is_active'] : (int)($existing['is_active'] ?? 1);

        if ($itemCode === '') {
            $errors[] = 'Item code is required.';
        }
        if ($itemName === '') {
            $errors[] = 'Item name is required.';
        }
        if (!in_array($itemType, ['Service', 'Product'], true)) {
            $errors[] = 'Valid item type is required.';
        }

        if ($unit !== '' && mb_strlen($unit) > 50) {
            $errors[] = 'Unit must not exceed 50 characters.';
        }

        if (!is_numeric($unitPriceRaw) || (float)$unitPriceRaw < 0) {
            $errors[] = 'Unit price must be zero or greater.';
        }

        $departmentValue = null;
        if ($departmentId !== null && $departmentId !== '') {
            $departmentValue = (int)$departmentId;
            if ($departmentValue <= 0 || !$this->departmentExists($departmentValue)) {
                $errors[] = 'Selected department is invalid.';
            }
        }

        return [
            'errors' => $errors,
            'data' => [
                'item_code' => $itemCode,
                'item_name' => $itemName,
                'item_type' => $itemType,
                'department_id' => $departmentValue,
                'description' => $description === '' ? null : $description,
                'unit_price' => number_format((float)$unitPriceRaw, 2, '.', ''),
                'unit' => $unit === '' ? null : $unit,
                'is_active' => $isActive,
            ],
        ];
    }

    private function canViewRow(array $item, array $user): bool
    {
        return $this->permissionService->canViewBillableItems($user)
            || $this->permissionService->isAdministrator($user);
    }

    private function assertCanCreate(array $user): void
    {
        if (!$this->permissionService->canCreateBillableItems($user)) {
            throw new RuntimeException('You are not allowed to create billable items.');
        }
    }

    private function assertCanEdit(array $user): void
    {
        if (!$this->permissionService->canEditBillableItems($user)) {
            throw new RuntimeException('You are not allowed to edit billable items.');
        }
    }

    private function assertCanManageStatus(array $user): void
    {
        if (!$this->permissionService->canManageBillableItemStatus($user)) {
            throw new RuntimeException('You are not allowed to change billable item status.');
        }
    }

    private function baseSelect(): string
    {
        return '
            SELECT
                bi.*,
                d.department_name,
                CONCAT(created_by.first_name, " ", created_by.last_name) AS created_by_name,
                CONCAT(updated_by.first_name, " ", updated_by.last_name) AS updated_by_name
            FROM billable_items bi
            LEFT JOIN departments d ON d.id = bi.department_id
            LEFT JOIN users created_by ON created_by.id = bi.created_by
            LEFT JOIN users updated_by ON updated_by.id = bi.updated_by
        ';
    }

    private function decorate(array $row): array
    {
        $row['display_price'] = number_format((float)($row['unit_price'] ?? 0), 2);
        return $row;
    }

    private function fetchItemById(int $itemId): ?array
    {
        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE bi.id = :id LIMIT 1');
        $stmt->execute([':id' => $itemId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->decorate($row) : null;
    }

    private function fetchItemByIdForUpdate(int $itemId): ?array
    {
        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE bi.id = :id LIMIT 1 FOR UPDATE');
        $stmt->execute([':id' => $itemId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->decorate($row) : null;
    }

    private function departmentExists(int $departmentId): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM departments WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $departmentId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function audit(int $userId, ?int $departmentId, string $action, string $description): bool
    {
        return $this->auditService->log(
            $userId,
            null,
            'Accounts',
            $action,
            $description,
            $departmentId
        );
    }

    private function rollback(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    private function failure(array $errors): array
    {
        return ['success' => false, 'errors' => $errors];
    }
}
