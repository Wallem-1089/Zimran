<?php

declare(strict_types=1);

require_once __DIR__ . '/AuditService.php';
require_once __DIR__ . '/PermissionService.php';

class StoreService
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
            $this->assertCanManageItems($user);
            $payload = $this->normalizeItemPayload($data);
            if ($payload['errors'] !== []) {
                return $this->failure($payload['errors']);
            }

            $ownsTransaction = !$this->pdo->inTransaction();
            if ($ownsTransaction) {
                $this->pdo->beginTransaction();
            }

            if ($this->getItemByCodeInternal($payload['data']['item_code']) !== null) {
                $this->rollback();
                return $this->failure(['Item code already exists.']);
            }

            if ($payload['data']['billable_item_id'] !== null
                && $this->billableItemDoesNotExist($payload['data']['billable_item_id'])
            ) {
                $this->rollback();
                return $this->failure(['Selected billable item is invalid.']);
            }

            $stmt = $this->pdo->prepare('
                INSERT INTO inventory_items (
                    item_code, item_name, category, unit, description,
                    billable_item_id, is_active, created_by, created_at, updated_at
                ) VALUES (
                    :item_code, :item_name, :category, :unit, :description,
                    :billable_item_id, :is_active, :created_by, NOW(), NOW()
                )
            ');
            $stmt->execute([
                ':item_code' => $payload['data']['item_code'],
                ':item_name' => $payload['data']['item_name'],
                ':category' => $payload['data']['category'],
                ':unit' => $payload['data']['unit'],
                ':description' => $payload['data']['description'],
                ':billable_item_id' => $payload['data']['billable_item_id'],
                ':is_active' => $payload['data']['is_active'],
                ':created_by' => (int)$user['id'],
            ]);
            $itemId = (int)$this->pdo->lastInsertId();

            if (!$this->audit((int)$user['id'], null, 'INVENTORY_ITEM_CREATED', 'Created inventory item #' . $itemId . '.')) {
                throw new RuntimeException('Unable to audit inventory item creation.');
            }

            $this->pdo->commit();

            return ['success' => true, 'inventory_item_id' => $itemId, 'errors' => []];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to create inventory item.']);
        }
    }

    public function getItemById(int $itemId, ?array $user = null): ?array
    {
        $item = $this->fetchItemById($itemId);
        if (!$item) {
            return null;
        }

        if ($user !== null && !$this->permissionService->canViewInventory($user)) {
            return null;
        }

        return $item;
    }

    public function getItemByCode(string $itemCode, ?array $user = null): ?array
    {
        $item = $this->getItemByCodeInternal($itemCode);
        if (!$item) {
            return null;
        }

        if ($user !== null && !$this->permissionService->canViewInventory($user)) {
            return null;
        }

        return $item;
    }

    public function listItems(array $filters = [], ?array $user = null): array
    {
        if ($user !== null && !$this->permissionService->canViewInventory($user)) {
            return [];
        }

        [$where, $params] = $this->buildItemFilters($filters);
        $stmt = $this->pdo->prepare($this->baseItemSelect() . $where . ' ORDER BY ii.is_active DESC, ii.item_name ASC, ii.id DESC');
        $stmt->execute($params);

        return array_map([$this, 'decorateItem'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function searchItems(array $filters = [], ?array $user = null): array
    {
        return $this->listItems($filters, $user);
    }

    public function updateItem(int $itemId, array $data, array $user): array
    {
        try {
            $this->assertCanManageItems($user);
            $this->pdo->beginTransaction();

            $existing = $this->fetchItemByIdForUpdate($itemId);
            if (!$existing) {
                $this->rollback();
                return $this->failure(['Inventory item not found.']);
            }

            $payload = $this->normalizeItemPayload($data, $existing);
            if ($payload['errors'] !== []) {
                $this->rollback();
                return $this->failure($payload['errors']);
            }

            if ($payload['data']['item_code'] !== $existing['item_code']
                && $this->getItemByCodeInternal($payload['data']['item_code']) !== null
            ) {
                $this->rollback();
                return $this->failure(['Item code already exists.']);
            }

            if ($payload['data']['billable_item_id'] !== null
                && $this->billableItemDoesNotExist($payload['data']['billable_item_id'])
            ) {
                $this->rollback();
                return $this->failure(['Selected billable item is invalid.']);
            }

            $stmt = $this->pdo->prepare('
                UPDATE inventory_items
                SET item_code = :item_code,
                    item_name = :item_name,
                    category = :category,
                    unit = :unit,
                    description = :description,
                    billable_item_id = :billable_item_id,
                    updated_by = :updated_by,
                    updated_at = NOW()
                WHERE id = :id
            ');
            $stmt->execute([
                ':item_code' => $payload['data']['item_code'],
                ':item_name' => $payload['data']['item_name'],
                ':category' => $payload['data']['category'],
                ':unit' => $payload['data']['unit'],
                ':description' => $payload['data']['description'],
                ':billable_item_id' => $payload['data']['billable_item_id'],
                ':updated_by' => (int)$user['id'],
                ':id' => $itemId,
            ]);

            if (!$this->audit((int)$user['id'], null, 'INVENTORY_ITEM_UPDATED', 'Updated inventory item #' . $itemId . '.')) {
                throw new RuntimeException('Unable to audit inventory item update.');
            }

            $this->pdo->commit();

            return ['success' => true, 'inventory_item_id' => $itemId, 'errors' => []];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to update inventory item.']);
        }
    }

    public function activateItem(int $itemId, array $user): array
    {
        return $this->toggleItemStatus($itemId, true, $user);
    }

    public function deactivateItem(int $itemId, array $user): array
    {
        return $this->toggleItemStatus($itemId, false, $user);
    }

    public function receiveStock(array $data, array $user): array
    {
        return $this->recordMovement('Receipt', $data, $user, 'STOCK_RECEIVED');
    }

    public function issueStock(array $data, array $user): array
    {
        return $this->recordMovement('Issue', $data, $user, 'STOCK_ISSUED');
    }

    public function returnStock(array $data, array $user): array
    {
        return $this->recordMovement('Return', $data, $user, 'STOCK_RETURNED');
    }

    public function adjustStock(array $data, array $user): array
    {
        return $this->recordMovement('Adjustment', $data, $user, 'STOCK_ADJUSTED');
    }

    public function consumeDepartmentStock(array $data, array $user): array
    {
        try {
            $this->assertCanViewItemForMovement($user);
            $payload = $this->normalizeConsumptionPayload($data);
            if ($payload['errors'] !== []) {
                return $this->failure($payload['errors']);
            }

            $ownsTransaction = !$this->pdo->inTransaction();
            if ($ownsTransaction) {
                $this->pdo->beginTransaction();
            }

            $item = $this->fetchItemByIdForUpdate($payload['data']['inventory_item_id']);
            if (!$item) {
                $this->rollback();
                return $this->failure(['Inventory item not found.']);
            }

            if ((int)$item['is_active'] !== 1) {
                $this->rollback();
                return $this->failure(['Inventory item is inactive.']);
            }

            $this->ensureBalanceRow($item['id'], $payload['data']['department_id']);
            $this->lockBalanceRow($item['id'], $payload['data']['department_id']);

            $balance = $this->fetchBalance($item['id'], $payload['data']['department_id']);
            if (!$balance || (float)$balance['quantity'] < (float)$payload['data']['quantity']) {
                $this->rollback();
                return $this->failure(['Insufficient stock in the selected department.']);
            }

            $stmt = $this->pdo->prepare('
                INSERT INTO stock_transactions (
                    inventory_item_id,
                    transaction_type,
                    quantity,
                    from_department_id,
                    to_department_id,
                    reference,
                    remarks,
                    performed_by,
                    created_at
                ) VALUES (
                    :inventory_item_id,
                    :transaction_type,
                    :quantity,
                    :from_department_id,
                    NULL,
                    :reference,
                    :remarks,
                    :performed_by,
                    NOW()
                )
            ');
            $stmt->execute([
                ':inventory_item_id' => $item['id'],
                ':transaction_type' => 'Consumption',
                ':quantity' => $payload['data']['quantity'],
                ':from_department_id' => $payload['data']['department_id'],
                ':reference' => $payload['data']['reference'],
                ':remarks' => $payload['data']['remarks'],
                ':performed_by' => (int)$user['id'],
            ]);
            $transactionId = (int)$this->pdo->lastInsertId();

            $this->changeBalance($item['id'], $payload['data']['department_id'], 0 - (float)$payload['data']['quantity']);

            if (!$this->audit((int)$user['id'], null, 'STOCK_CONSUMED', 'Consumed department stock. Item #' . $item['id'] . '.')) {
                throw new RuntimeException('Unable to audit stock consumption.');
            }

            if ($ownsTransaction) {
                $this->pdo->commit();
            }

            return [
                'success' => true,
                'stock_transaction_id' => $transactionId,
                'inventory_item_id' => (int)$item['id'],
                'errors' => [],
            ];
        } catch (Throwable) {
            if ($this->pdo->inTransaction()) {
                $this->rollback();
            }
            return $this->failure(['Unable to consume stock from the department.']);
        }
    }

    public function createExternalSale(array $data, array $user): array
    {
        try {
            $this->assertCanCreateExternalSale($user);

            $payload = $this->normalizeExternalSalePayload($data);
            if ($payload['errors'] !== []) {
                return $this->failure($payload['errors']);
            }

            $storeDepartmentId = $this->getStoreDepartmentId();
            if ($storeDepartmentId === null) {
                return $this->failure(['Store department is not configured.']);
            }

            $this->pdo->beginTransaction();

            $item = $this->fetchSaleableItemForUpdate(
                $payload['data']['inventory_item_id']
            );

            if (!$item) {
                $this->rollback();
                return $this->failure(['Selected item is not available for external sale.']);
            }

            $quantity = (float)$payload['data']['quantity'];
            $unitPrice = (float)$item['unit_price'];
            $amount = round($quantity * $unitPrice, 2);
            $saleNumber = $this->generateExternalSaleNumber();

            $stmt = $this->pdo->prepare('
                INSERT INTO external_sales (
                    sale_number,
                    customer_name,
                    customer_phone,
                    total_amount,
                    payment_method,
                    reference,
                    sold_by,
                    status,
                    created_at
                ) VALUES (
                    :sale_number,
                    :customer_name,
                    :customer_phone,
                    :total_amount,
                    :payment_method,
                    :reference,
                    :sold_by,
                    "Completed",
                    NOW()
                )
            ');
            $stmt->execute([
                ':sale_number' => $saleNumber,
                ':customer_name' => $payload['data']['customer_name'],
                ':customer_phone' => $payload['data']['customer_phone'],
                ':total_amount' => number_format($amount, 2, '.', ''),
                ':payment_method' => $payload['data']['payment_method'],
                ':reference' => $payload['data']['reference'],
                ':sold_by' => (int)$user['id'],
            ]);
            $saleId = (int)$this->pdo->lastInsertId();

            $stmt = $this->pdo->prepare('
                INSERT INTO external_sale_items (
                    external_sale_id,
                    inventory_item_id,
                    billable_item_id,
                    item_name,
                    quantity,
                    unit_price,
                    amount,
                    created_at
                ) VALUES (
                    :external_sale_id,
                    :inventory_item_id,
                    :billable_item_id,
                    :item_name,
                    :quantity,
                    :unit_price,
                    :amount,
                    NOW()
                )
            ');
            $stmt->execute([
                ':external_sale_id' => $saleId,
                ':inventory_item_id' => (int)$item['inventory_item_id'],
                ':billable_item_id' => (int)$item['billable_item_id'],
                ':item_name' => (string)$item['item_name'],
                ':quantity' => number_format($quantity, 2, '.', ''),
                ':unit_price' => number_format($unitPrice, 2, '.', ''),
                ':amount' => number_format($amount, 2, '.', ''),
            ]);

            $stockResult = $this->consumeDepartmentStock([
                'inventory_item_id' => (int)$item['inventory_item_id'],
                'department_id' => $storeDepartmentId,
                'quantity' => number_format($quantity, 2, '.', ''),
                'reference' => 'External Sale ' . $saleNumber,
                'remarks' => 'External sale #' . $saleId,
            ], $user);

            if (!($stockResult['success'] ?? false)) {
                throw new RuntimeException(
                    $stockResult['errors'][0]
                    ?? 'Unable to reduce store stock.'
                );
            }

            if (!$this->audit((int)$user['id'], null, 'EXTERNAL_SALE_CREATED', 'Created external sale ' . $saleNumber . '.')) {
                throw new RuntimeException('Unable to audit external sale.');
            }

            $this->pdo->commit();

            return [
                'success' => true,
                'external_sale_id' => $saleId,
                'sale_number' => $saleNumber,
                'errors' => [],
            ];
        } catch (Throwable $e) {
            $this->rollback();
            return $this->failure([$e->getMessage() ?: 'Unable to create external sale.']);
        }
    }

    public function listExternalSales(array $filters = [], ?array $user = null): array
    {
        if ($user !== null && !$this->permissionService->canViewExternalSales($user)) {
            return [];
        }

        $where = [];
        $params = [];

        $status = trim((string)($filters['status'] ?? ''));
        if (in_array($status, ['Completed', 'Cancelled'], true)) {
            $where[] = 'es.status = :status';
            $params[':status'] = $status;
        }

        $search = trim((string)($filters['search'] ?? ''));
        if ($search !== '') {
            $where[] = '(es.sale_number LIKE :search OR es.customer_name LIKE :search OR es.customer_phone LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        $sql = $this->externalSaleSelect()
            . ($where === [] ? '' : ' WHERE ' . implode(' AND ', $where))
            . ' ORDER BY es.created_at DESC, es.id DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return array_map([$this, 'decorateExternalSale'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getExternalSaleById(int $saleId, ?array $user = null): ?array
    {
        if ($saleId <= 0) {
            return null;
        }

        if ($user !== null
            && !$this->permissionService->canViewExternalSales($user)
            && !$this->permissionService->canViewExternalSaleReceipts($user)
        ) {
            return null;
        }

        $stmt = $this->pdo->prepare($this->externalSaleSelect() . ' WHERE es.id = :id LIMIT 1');
        $stmt->execute([':id' => $saleId]);
        $sale = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$sale) {
            return null;
        }

        $sale = $this->decorateExternalSale($sale);
        $sale['items'] = $this->listExternalSaleItems($saleId);

        return $sale;
    }

    public function cancelExternalSale(int $saleId, string $reason, array $user): array
    {
        try {
            if (!$this->permissionService->canCancelExternalSale($user)) {
                return $this->failure(['You are not allowed to cancel external sales.']);
            }

            $reason = trim($reason);
            if ($reason === '') {
                return $this->failure(['Cancellation reason is required.']);
            }

            if (mb_strlen($reason) > 2000) {
                return $this->failure(['Cancellation reason must not exceed 2000 characters.']);
            }

            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare('
                SELECT *
                FROM external_sales
                WHERE id = :id
                LIMIT 1
                FOR UPDATE
            ');
            $stmt->execute([':id' => $saleId]);
            $sale = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$sale) {
                $this->rollback();
                return $this->failure(['External sale not found.']);
            }

            if ((string)$sale['status'] === 'Cancelled') {
                $this->rollback();
                return $this->failure(['External sale is already cancelled.']);
            }

            $stmt = $this->pdo->prepare('
                UPDATE external_sales
                SET status = "Cancelled",
                    cancelled_by = :cancelled_by,
                    cancelled_at = NOW(),
                    cancel_reason = :cancel_reason
                WHERE id = :id
            ');
            $stmt->execute([
                ':cancelled_by' => (int)$user['id'],
                ':cancel_reason' => $reason,
                ':id' => $saleId,
            ]);

            if (!$this->audit((int)$user['id'], null, 'EXTERNAL_SALE_CANCELLED', 'Cancelled external sale ' . $sale['sale_number'] . '.')) {
                throw new RuntimeException('Unable to audit external sale cancellation.');
            }

            $this->pdo->commit();

            return [
                'success' => true,
                'external_sale_id' => $saleId,
                'errors' => [],
            ];
        } catch (Throwable $e) {
            $this->rollback();
            return $this->failure([$e->getMessage() ?: 'Unable to cancel external sale.']);
        }
    }

    public function getDepartmentBalance(int $itemId, int $departmentId, ?array $user = null): ?array
    {
        if ($user !== null && !$this->permissionService->canViewInventory($user)) {
            return null;
        }

        $stmt = $this->pdo->prepare('
            SELECT
                dsb.inventory_item_id,
                dsb.department_id,
                dsb.quantity,
                dsb.updated_at,
                ii.item_code,
                ii.item_name,
                ii.category,
                ii.unit,
                ii.is_active,
                d.department_name
            FROM department_stock_balances dsb
            INNER JOIN inventory_items ii ON ii.id = dsb.inventory_item_id
            LEFT JOIN departments d ON d.id = dsb.department_id
            WHERE dsb.inventory_item_id = :item_id
              AND dsb.department_id = :department_id
            LIMIT 1
        ');
        $stmt->execute([
            ':item_id' => $itemId,
            ':department_id' => $departmentId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $row;
        }

        $item = $this->fetchItemById($itemId);
        if (!$item) {
            return null;
        }

        $departmentName = $this->departmentNameById($departmentId);
        if ($departmentName === null) {
            return null;
        }

        return [
            'inventory_item_id' => $itemId,
            'department_id' => $departmentId,
            'quantity' => '0.00',
            'updated_at' => null,
            'item_code' => $item['item_code'],
            'item_name' => $item['item_name'],
            'category' => $item['category'],
            'unit' => $item['unit'],
            'is_active' => $item['is_active'],
            'department_name' => $departmentName,
        ];
    }

    public function listDepartmentStock(?int $departmentId = null, ?array $user = null): array
    {
        if ($user !== null && !$this->permissionService->canViewInventory($user)) {
            return [];
        }

        $sql = '
            SELECT
                dsb.inventory_item_id,
                dsb.department_id,
                dsb.quantity,
                dsb.updated_at,
                ii.item_code,
                ii.item_name,
                ii.category,
                ii.unit,
                ii.is_active,
                bi.item_code AS billable_item_code,
                bi.item_name AS billable_item_name,
                bi.unit_price AS billable_item_price,
                d.department_name
            FROM department_stock_balances dsb
            INNER JOIN inventory_items ii ON ii.id = dsb.inventory_item_id
            LEFT JOIN billable_items bi ON bi.id = ii.billable_item_id
            LEFT JOIN departments d ON d.id = dsb.department_id
        ';

        $params = [];
        if ($departmentId !== null && $departmentId > 0) {
            $sql .= ' WHERE dsb.department_id = :department_id';
            $params[':department_id'] = $departmentId;
        }

        $sql .= ' ORDER BY d.department_name ASC, ii.item_name ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return array_map([$this, 'decorateBalanceRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getItemLedger(int $itemId, ?array $user = null): array
    {
        if ($user !== null && !$this->permissionService->canViewInventory($user)) {
            return [];
        }

        $stmt = $this->pdo->prepare('
            SELECT
                st.*,
                ii.item_code,
                ii.item_name,
                CONCAT(performed_by.first_name, " ", performed_by.last_name) AS performed_by_name,
                fd.department_name AS from_department_name,
                td.department_name AS to_department_name
            FROM stock_transactions st
            INNER JOIN inventory_items ii ON ii.id = st.inventory_item_id
            LEFT JOIN users performed_by ON performed_by.id = st.performed_by
            LEFT JOIN departments fd ON fd.id = st.from_department_id
            LEFT JOIN departments td ON td.id = st.to_department_id
            WHERE st.inventory_item_id = :item_id
            ORDER BY st.created_at DESC, st.id DESC
        ');
        $stmt->execute([':item_id' => $itemId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listDepartmentLedger(int $departmentId, ?array $user = null, int $limit = 50): array
    {
        if ($departmentId <= 0 || ($user !== null && !$this->permissionService->canViewInventory($user))) {
            return [];
        }

        $limit = max(1, min(200, $limit));

        $stmt = $this->pdo->prepare('
            SELECT
                st.*,
                ii.item_code,
                ii.item_name,
                ii.unit,
                CONCAT(performed_by.first_name, " ", performed_by.last_name) AS performed_by_name,
                fd.department_name AS from_department_name,
                td.department_name AS to_department_name
            FROM stock_transactions st
            INNER JOIN inventory_items ii ON ii.id = st.inventory_item_id
            LEFT JOIN users performed_by ON performed_by.id = st.performed_by
            LEFT JOIN departments fd ON fd.id = st.from_department_id
            LEFT JOIN departments td ON td.id = st.to_department_id
            WHERE st.from_department_id = :from_department_id
               OR st.to_department_id = :to_department_id
            ORDER BY st.created_at DESC, st.id DESC
            LIMIT ' . $limit
        );
        $stmt->execute([
            ':from_department_id' => $departmentId,
            ':to_department_id' => $departmentId,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function recordMovement(
        string $transactionType,
        array $data,
        array $user,
        string $auditAction
    ): array {
        try {
            $this->assertMovementPermission($transactionType, $user);
            $payload = $this->normalizeMovementPayload($transactionType, $data);
            if ($payload['errors'] !== []) {
                return $this->failure($payload['errors']);
            }

            $this->pdo->beginTransaction();

            $item = $this->fetchItemByIdForUpdate($payload['data']['inventory_item_id']);
            if (!$item) {
                $this->rollback();
                return $this->failure(['Inventory item not found.']);
            }

            if ((int)$item['is_active'] !== 1) {
                $this->rollback();
                return $this->failure(['Inventory item is inactive.']);
            }

            $storeDepartmentId = $this->getStoreDepartmentId();
            if ($storeDepartmentId === null) {
                $this->rollback();
                return $this->failure(['Store department is not available.']);
            }

            $movement = $this->buildMovementContext($transactionType, $payload['data'], $storeDepartmentId);
            if ($movement['errors'] !== []) {
                $this->rollback();
                return $this->failure($movement['errors']);
            }

            $affectedDepartments = $movement['data']['affected_departments'];
            foreach ($affectedDepartments as $departmentId) {
                $this->ensureBalanceRow($item['id'], $departmentId);
            }

            foreach ($affectedDepartments as $departmentId) {
                $this->lockBalanceRow($item['id'], $departmentId);
            }

            $fromBalance = $movement['data']['from_department_id'] !== null
                ? $this->fetchBalance($item['id'], $movement['data']['from_department_id'])
                : null;

            if ($movement['data']['requires_source_balance']
                && $fromBalance !== null
                && (float)$fromBalance['quantity'] < (float)$movement['data']['quantity']
            ) {
                $this->rollback();
                return $this->failure([$this->insufficientStockMessage($transactionType)]);
            }

            $stmt = $this->pdo->prepare('
                INSERT INTO stock_transactions (
                    inventory_item_id,
                    transaction_type,
                    quantity,
                    from_department_id,
                    to_department_id,
                    reference,
                    remarks,
                    performed_by,
                    created_at
                ) VALUES (
                    :inventory_item_id,
                    :transaction_type,
                    :quantity,
                    :from_department_id,
                    :to_department_id,
                    :reference,
                    :remarks,
                    :performed_by,
                    NOW()
                )
            ');
            $stmt->execute([
                ':inventory_item_id' => $item['id'],
                ':transaction_type' => $transactionType,
                ':quantity' => $movement['data']['quantity'],
                ':from_department_id' => $movement['data']['from_department_id'],
                ':to_department_id' => $movement['data']['to_department_id'],
                ':reference' => $movement['data']['reference'],
                ':remarks' => $movement['data']['remarks'],
                ':performed_by' => (int)$user['id'],
            ]);
            $transactionId = (int)$this->pdo->lastInsertId();

            foreach ($movement['data']['balance_changes'] as $departmentId => $delta) {
                $this->changeBalance($item['id'], (int)$departmentId, (float)$delta);
            }

            if (!$this->audit(
                (int)$user['id'],
                null,
                $movement['data']['audit_action'],
                $movement['data']['audit_description'] . ' Item #' . $item['id'] . '.'
            )) {
                throw new RuntimeException('Unable to audit stock movement.');
            }

            $this->pdo->commit();

            return [
                'success' => true,
                'stock_transaction_id' => $transactionId,
                'inventory_item_id' => (int)$item['id'],
                'errors' => [],
            ];
        } catch (Throwable) {
            if ($this->pdo->inTransaction()) {
                $this->rollback();
            }
            return $this->failure(['Unable to complete the stock movement.']);
        }
    }

    private function buildMovementContext(string $transactionType, array $payload, int $storeDepartmentId): array
    {
        $quantity = (float)$payload['quantity'];
        $fromDepartmentId = null;
        $toDepartmentId = null;
        $balanceChanges = [];
        $requiresSourceBalance = false;
        $auditAction = 'STOCK_RECEIVED';
        $auditDescription = match ($transactionType) {
            'Receipt' => 'Received stock movement.',
            'Issue' => 'Issued stock movement.',
            'Return' => 'Returned stock movement.',
            'Adjustment' => 'Adjusted stock movement.',
            default => 'Processed stock movement.',
        };

        switch ($transactionType) {
            case 'Receipt':
                $auditAction = 'STOCK_RECEIVED';
                $toDepartmentId = $storeDepartmentId;
                $balanceChanges[$storeDepartmentId] = $quantity;
                break;
            case 'Issue':
                $auditAction = 'STOCK_ISSUED';
                $toDepartmentId = $payload['department_id'];
                $fromDepartmentId = $storeDepartmentId;
                $balanceChanges[$storeDepartmentId] = -$quantity;
                $balanceChanges[$toDepartmentId] = $quantity;
                $requiresSourceBalance = true;
                break;
            case 'Return':
                $auditAction = 'STOCK_RETURNED';
                $fromDepartmentId = $payload['department_id'];
                $toDepartmentId = $storeDepartmentId;
                $balanceChanges[$fromDepartmentId] = -$quantity;
                $balanceChanges[$toDepartmentId] = $quantity;
                $requiresSourceBalance = true;
                break;
            case 'Adjustment':
                $auditAction = 'STOCK_ADJUSTED';
                if (($payload['adjustment_mode'] ?? '') === 'Decrease') {
                    $fromDepartmentId = $payload['department_id'];
                    $balanceChanges[$fromDepartmentId] = -$quantity;
                    $requiresSourceBalance = true;
                } else {
                    $toDepartmentId = $payload['department_id'];
                    $balanceChanges[$toDepartmentId] = $quantity;
                }
                break;
        }

        return [
            'errors' => [],
            'data' => [
                'quantity' => $quantity,
                'from_department_id' => $fromDepartmentId,
                'to_department_id' => $toDepartmentId,
                'balance_changes' => $balanceChanges,
                'affected_departments' => array_values(array_unique(array_filter([
                    $fromDepartmentId,
                    $toDepartmentId,
                    $storeDepartmentId,
                ], static fn ($value): bool => $value !== null && (int)$value > 0))),
                'requires_source_balance' => $requiresSourceBalance,
                'reference' => $payload['reference'],
                'remarks' => $payload['remarks'],
                'audit_action' => $auditAction,
                'audit_description' => $auditDescription,
            ],
        ];
    }

    private function insufficientStockMessage(string $transactionType): string
    {
        return match ($transactionType) {
            'Issue' => 'Insufficient Central Store stock to issue this quantity.',
            'Return' => 'Insufficient stock in the returning department.',
            'Adjustment' => 'Insufficient stock in the selected department to decrease this quantity.',
            default => 'Insufficient stock for this movement.',
        };
    }

    private function normalizeMovementPayload(string $transactionType, array $data): array
    {
        $errors = [];
        $itemId = (int)($data['inventory_item_id'] ?? 0);
        $quantityRaw = $data['quantity'] ?? null;
        $reference = trim((string)($data['reference'] ?? ''));
        $remarks = trim((string)($data['remarks'] ?? ''));
        $departmentId = (int)($data['department_id'] ?? 0);
        $adjustmentMode = trim((string)($data['adjustment_mode'] ?? 'Increase'));

        if ($itemId <= 0) {
            $errors[] = 'Inventory item is required.';
        }
        if (!is_numeric($quantityRaw) || (float)$quantityRaw <= 0) {
            $errors[] = 'Quantity must be greater than zero.';
        }

        if ($transactionType !== 'Receipt' && $departmentId <= 0) {
            $errors[] = 'Department is required.';
        }

        if ($transactionType === 'Adjustment' && !in_array($adjustmentMode, ['Increase', 'Decrease'], true)) {
            $errors[] = 'Adjustment mode is invalid.';
        }

        if (mb_strlen($reference) > 255) {
            $errors[] = 'Reference must not exceed 255 characters.';
        }
        if (mb_strlen($remarks) > 2000) {
            $errors[] = 'Remarks must not exceed 2000 characters.';
        }

        if ($transactionType !== 'Receipt' && $departmentId > 0 && !$this->departmentExists($departmentId)) {
            $errors[] = 'Selected department is invalid.';
        }

        return [
            'errors' => $errors,
            'data' => [
                'inventory_item_id' => $itemId,
                'quantity' => number_format((float)$quantityRaw, 2, '.', ''),
                'department_id' => $departmentId > 0 ? $departmentId : null,
                'reference' => $reference === '' ? null : $reference,
                'remarks' => $remarks === '' ? null : $remarks,
                'adjustment_mode' => $adjustmentMode,
            ],
        ];
    }

    private function normalizeItemPayload(array $data, ?array $existing = null): array
    {
        $errors = [];

        $itemCode = strtoupper(trim((string)($data['item_code'] ?? ($existing['item_code'] ?? ''))));
        $itemName = trim((string)($data['item_name'] ?? ($existing['item_name'] ?? '')));
        $category = trim((string)($data['category'] ?? ($existing['category'] ?? '')));
        $unit = trim((string)($data['unit'] ?? ($existing['unit'] ?? '')));
        $description = trim((string)($data['description'] ?? ($existing['description'] ?? '')));
        $billableItemIdRaw = $data['billable_item_id'] ?? ($existing['billable_item_id'] ?? null);
        $isActive = isset($data['is_active']) ? (int)!!$data['is_active'] : (int)($existing['is_active'] ?? 1);

        if ($itemCode === '') {
            $errors[] = 'Item code is required.';
        }
        if ($itemName === '') {
            $errors[] = 'Item name is required.';
        }
        if ($category === '') {
            $errors[] = 'Category is required.';
        }
        if ($unit === '') {
            $errors[] = 'Unit is required.';
        }
        if (mb_strlen($itemCode) > 30) {
            $errors[] = 'Item code must not exceed 30 characters.';
        }
        if (mb_strlen($itemName) > 255) {
            $errors[] = 'Item name must not exceed 255 characters.';
        }
        if (mb_strlen($category) > 100) {
            $errors[] = 'Category must not exceed 100 characters.';
        }
        if (mb_strlen($unit) > 50) {
            $errors[] = 'Unit must not exceed 50 characters.';
        }
        if (mb_strlen($description) > 2000) {
            $errors[] = 'Description must not exceed 2000 characters.';
        }

        $billableItemId = null;
        if ($billableItemIdRaw !== null && $billableItemIdRaw !== '') {
            $billableItemId = (int)$billableItemIdRaw;
            if ($billableItemId <= 0) {
                $errors[] = 'Billable item is invalid.';
            }
        }

        return [
            'errors' => $errors,
            'data' => [
                'item_code' => $itemCode,
                'item_name' => $itemName,
                'category' => $category,
                'unit' => $unit,
                'description' => $description === '' ? null : $description,
                'billable_item_id' => $billableItemId,
                'is_active' => $isActive ? 1 : 0,
            ],
        ];
    }

    private function buildItemFilters(array $filters): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['item_code'])) {
            $where[] = 'ii.item_code LIKE :item_code';
            $params[':item_code'] = '%' . strtoupper(trim((string)$filters['item_code'])) . '%';
        }
        if (!empty($filters['item_name'])) {
            $where[] = 'ii.item_name LIKE :item_name';
            $params[':item_name'] = '%' . trim((string)$filters['item_name']) . '%';
        }
        if (!empty($filters['category'])) {
            $where[] = 'ii.category LIKE :category';
            $params[':category'] = '%' . trim((string)$filters['category']) . '%';
        }
        if (!empty($filters['billable_item_id'])) {
            $where[] = 'ii.billable_item_id = :billable_item_id';
            $params[':billable_item_id'] = (int)$filters['billable_item_id'];
        }

        $status = strtolower(trim((string)($filters['status'] ?? 'all')));
        if ($status === 'active') {
            $where[] = 'ii.is_active = 1';
        } elseif ($status === 'inactive') {
            $where[] = 'ii.is_active = 0';
        }

        return [
            $where === [] ? '' : ' WHERE ' . implode(' AND ', $where),
            $params,
        ];
    }

    private function toggleItemStatus(int $itemId, bool $active, array $user): array
    {
        try {
            $this->assertCanManageItems($user);
            $this->pdo->beginTransaction();

            $existing = $this->fetchItemByIdForUpdate($itemId);
            if (!$existing) {
                $this->rollback();
                return $this->failure(['Inventory item not found.']);
            }

            if ((int)$existing['is_active'] === ($active ? 1 : 0)) {
                $this->rollback();
                return $this->failure([$active ? 'Inventory item is already active.' : 'Inventory item is already inactive.']);
            }

            $stmt = $this->pdo->prepare('
                UPDATE inventory_items
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

            if (!$this->audit((int)$user['id'], null, $active ? 'INVENTORY_ITEM_ACTIVATED' : 'INVENTORY_ITEM_DEACTIVATED', ($active ? 'Activated' : 'Deactivated') . ' inventory item #' . $itemId . '.')) {
                throw new RuntimeException('Unable to audit item status change.');
            }

            $this->pdo->commit();

            return ['success' => true, 'inventory_item_id' => $itemId, 'errors' => []];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to update inventory item status.']);
        }
    }

    private function getItemByCodeInternal(string $itemCode): ?array
    {
        $itemCode = strtoupper(trim($itemCode));
        if ($itemCode === '') {
            return null;
        }

        $stmt = $this->pdo->prepare($this->baseItemSelect() . ' WHERE ii.item_code = :item_code LIMIT 1');
        $stmt->execute([':item_code' => $itemCode]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->decorateItem($row) : null;
    }

    private function fetchItemById(int $itemId): ?array
    {
        $stmt = $this->pdo->prepare($this->baseItemSelect() . ' WHERE ii.id = :id LIMIT 1');
        $stmt->execute([':id' => $itemId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->decorateItem($row) : null;
    }

    private function fetchItemByIdForUpdate(int $itemId): ?array
    {
        $stmt = $this->pdo->prepare($this->baseItemSelect() . ' WHERE ii.id = :id LIMIT 1 FOR UPDATE');
        $stmt->execute([':id' => $itemId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->decorateItem($row) : null;
    }

    private function baseItemSelect(): string
    {
        return '
            SELECT
                ii.*,
                bi.item_code AS billable_item_code,
                bi.item_name AS billable_item_name,
                bi.item_type AS billable_item_type,
                bi.unit_price AS billable_item_price,
                bi.unit AS billable_item_unit,
                CONCAT(created_by.first_name, " ", created_by.last_name) AS created_by_name,
                CONCAT(updated_by.first_name, " ", updated_by.last_name) AS updated_by_name
            FROM inventory_items ii
            LEFT JOIN billable_items bi ON bi.id = ii.billable_item_id
            LEFT JOIN users created_by ON created_by.id = ii.created_by
            LEFT JOIN users updated_by ON updated_by.id = ii.updated_by
        ';
    }

    private function decorateItem(array $row): array
    {
        $row['billable_item_price_display'] = isset($row['billable_item_price'])
            ? number_format((float)$row['billable_item_price'], 2)
            : null;
        return $row;
    }

    private function decorateBalanceRow(array $row): array
    {
        $row['quantity_display'] = number_format((float)($row['quantity'] ?? 0), 2);
        $row['billable_item_price_display'] = isset($row['billable_item_price'])
            ? number_format((float)$row['billable_item_price'], 2)
            : null;
        return $row;
    }

    private function normalizeExternalSalePayload(array $data): array
    {
        $errors = [];
        $itemId = (int)($data['inventory_item_id'] ?? 0);
        $quantityRaw = $data['quantity'] ?? null;
        $customerName = trim((string)($data['customer_name'] ?? ''));
        $customerPhone = trim((string)($data['customer_phone'] ?? ''));
        $paymentMethod = trim((string)($data['payment_method'] ?? 'Cash'));
        $reference = trim((string)($data['reference'] ?? ''));

        if ($itemId <= 0) {
            $errors[] = 'Inventory item is required.';
        }

        if (!is_numeric($quantityRaw) || (float)$quantityRaw <= 0) {
            $errors[] = 'Quantity must be greater than zero.';
        }

        if (!in_array($paymentMethod, ['Cash', 'Card', 'Transfer', 'Other'], true)) {
            $errors[] = 'Invalid payment method.';
        }

        if (mb_strlen($customerName) > 150) {
            $errors[] = 'Customer name must not exceed 150 characters.';
        }

        if (mb_strlen($customerPhone) > 50) {
            $errors[] = 'Customer phone must not exceed 50 characters.';
        }

        if (mb_strlen($reference) > 255) {
            $errors[] = 'Reference must not exceed 255 characters.';
        }

        return [
            'errors' => $errors,
            'data' => [
                'inventory_item_id' => $itemId,
                'quantity' => number_format((float)$quantityRaw, 2, '.', ''),
                'customer_name' => $customerName === '' ? null : $customerName,
                'customer_phone' => $customerPhone === '' ? null : $customerPhone,
                'payment_method' => $paymentMethod,
                'reference' => $reference === '' ? null : $reference,
            ],
        ];
    }

    private function fetchSaleableItemForUpdate(int $itemId): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT
                ii.id AS inventory_item_id,
                ii.item_name,
                ii.unit,
                ii.is_active,
                bi.id AS billable_item_id,
                bi.unit_price,
                bi.is_active AS billable_is_active
            FROM inventory_items ii
            INNER JOIN billable_items bi ON bi.id = ii.billable_item_id
            WHERE ii.id = :id
              AND ii.is_active = 1
              AND bi.is_active = 1
            LIMIT 1
            FOR UPDATE
        ');
        $stmt->execute([':id' => $itemId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    private function generateExternalSaleNumber(): string
    {
        $prefix = 'EXT';
        $year = date('Y');

        $stmt = $this->pdo->prepare("
            SELECT sale_number
            FROM external_sales
            WHERE sale_number LIKE :prefix
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute([':prefix' => $prefix . '-' . $year . '-%']);
        $last = (string)($stmt->fetchColumn() ?: '');

        $next = 1;
        if (preg_match('/-(\d{6})$/', $last, $matches)) {
            $next = ((int)$matches[1]) + 1;
        }

        return sprintf('%s-%s-%06d', $prefix, $year, $next);
    }

    private function externalSaleSelect(): string
    {
        return '
            SELECT
                es.*,
                CONCAT(sold_by.first_name, " ", sold_by.last_name) AS sold_by_name,
                CONCAT(cancelled_by.first_name, " ", cancelled_by.last_name) AS cancelled_by_name
            FROM external_sales es
            LEFT JOIN users sold_by ON sold_by.id = es.sold_by
            LEFT JOIN users cancelled_by ON cancelled_by.id = es.cancelled_by
        ';
    }

    private function listExternalSaleItems(int $saleId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT
                esi.*,
                ii.item_code,
                ii.unit,
                bi.item_code AS billable_item_code
            FROM external_sale_items esi
            LEFT JOIN inventory_items ii ON ii.id = esi.inventory_item_id
            LEFT JOIN billable_items bi ON bi.id = esi.billable_item_id
            WHERE esi.external_sale_id = :sale_id
            ORDER BY esi.id ASC
        ');
        $stmt->execute([':sale_id' => $saleId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function decorateExternalSale(array $row): array
    {
        $row['total_amount_display'] = number_format(
            (float)($row['total_amount'] ?? 0),
            2
        );

        return $row;
    }

    private function ensureBalanceRow(int $itemId, int $departmentId): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO department_stock_balances (
                inventory_item_id, department_id, quantity, updated_at
            ) VALUES (
                :inventory_item_id, :department_id, 0, NOW()
            )
            ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at)
        ');
        $stmt->execute([
            ':inventory_item_id' => $itemId,
            ':department_id' => $departmentId,
        ]);
    }

    private function lockBalanceRow(int $itemId, int $departmentId): void
    {
        $stmt = $this->pdo->prepare('
            SELECT quantity
            FROM department_stock_balances
            WHERE inventory_item_id = :inventory_item_id
              AND department_id = :department_id
            LIMIT 1
            FOR UPDATE
        ');
        $stmt->execute([
            ':inventory_item_id' => $itemId,
            ':department_id' => $departmentId,
        ]);
    }

    private function fetchBalance(int $itemId, int $departmentId): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT *
            FROM department_stock_balances
            WHERE inventory_item_id = :inventory_item_id
              AND department_id = :department_id
            LIMIT 1
        ');
        $stmt->execute([
            ':inventory_item_id' => $itemId,
            ':department_id' => $departmentId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function changeBalance(int $itemId, int $departmentId, float $delta): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE department_stock_balances
            SET quantity = quantity + :delta,
                updated_at = NOW()
            WHERE inventory_item_id = :inventory_item_id
              AND department_id = :department_id
        ');
        $stmt->execute([
            ':delta' => number_format($delta, 2, '.', ''),
            ':inventory_item_id' => $itemId,
            ':department_id' => $departmentId,
        ]);
    }

    private function getStoreDepartmentId(): ?int
    {
        $stmt = $this->pdo->query("
            SELECT id
            FROM departments
            WHERE department_name = 'Store'
            LIMIT 1
        ");
        $id = $stmt->fetchColumn();
        return $id ? (int)$id : null;
    }

    private function departmentNameById(int $departmentId): ?string
    {
        $stmt = $this->pdo->prepare('SELECT department_name FROM departments WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $departmentId]);
        $name = $stmt->fetchColumn();
        return $name ? (string)$name : null;
    }

    private function departmentExists(int $departmentId): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM departments WHERE id = :id');
        $stmt->execute([':id' => $departmentId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function billableItemDoesNotExist(int $billableItemId): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM billable_items WHERE id = :id');
        $stmt->execute([':id' => $billableItemId]);
        return (int)$stmt->fetchColumn() === 0;
    }

    private function assertCanManageItems(array $user): void
    {
        if (!$this->permissionService->canManageInventoryItems($user)) {
            throw new RuntimeException('You are not allowed to manage inventory items.');
        }
    }

    private function assertMovementPermission(string $transactionType, array $user): void
    {
        $allowed = match ($transactionType) {
            'Receipt' => $this->permissionService->canReceiveStock($user),
            'Issue' => $this->permissionService->canIssueStock($user),
            'Return' => $this->permissionService->canReturnStock($user),
            'Adjustment' => $this->permissionService->canAdjustStock($user),
            default => false,
        };

        if (!$allowed) {
            throw new RuntimeException('You are not allowed to perform this stock movement.');
        }
    }

    private function assertCanViewItemForMovement(array $user): void
    {
        if (!$this->permissionService->canViewInventory($user)
            && !$this->permissionService->canRecordPatientStockUsage($user)
        ) {
            throw new RuntimeException('You are not allowed to access inventory items.');
        }
    }

    private function assertCanCreateExternalSale(array $user): void
    {
        if (!$this->permissionService->canCreateExternalSale($user)) {
            throw new RuntimeException('You are not allowed to create external sales.');
        }
    }

    private function normalizeConsumptionPayload(array $data): array
    {
        $errors = [];
        $itemId = (int)($data['inventory_item_id'] ?? 0);
        $departmentId = (int)($data['department_id'] ?? 0);
        $quantityRaw = $data['quantity'] ?? null;
        $reference = trim((string)($data['reference'] ?? ''));
        $remarks = trim((string)($data['remarks'] ?? ''));

        if ($itemId <= 0) {
            $errors[] = 'Inventory item is required.';
        }
        if ($departmentId <= 0 || !$this->departmentExists($departmentId)) {
            $errors[] = 'Department is invalid.';
        }
        if (!is_numeric($quantityRaw) || (float)$quantityRaw <= 0) {
            $errors[] = 'Quantity must be greater than zero.';
        }
        if (mb_strlen($reference) > 255) {
            $errors[] = 'Reference must not exceed 255 characters.';
        }
        if (mb_strlen($remarks) > 2000) {
            $errors[] = 'Remarks must not exceed 2000 characters.';
        }

        return [
            'errors' => $errors,
            'data' => [
                'inventory_item_id' => $itemId,
                'department_id' => $departmentId,
                'quantity' => number_format((float)$quantityRaw, 2, '.', ''),
                'reference' => $reference === '' ? null : $reference,
                'remarks' => $remarks === '' ? null : $remarks,
            ],
        ];
    }

    private function audit(int $userId, ?int $visitId, string $action, string $description): bool
    {
        return $this->auditService->log(
            $userId,
            $visitId,
            'Store',
            $action,
            $description,
            null
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
