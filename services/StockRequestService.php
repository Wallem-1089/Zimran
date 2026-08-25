<?php

declare(strict_types=1);

require_once __DIR__ . '/AuditService.php';
require_once __DIR__ . '/PermissionService.php';
require_once __DIR__ . '/StoreService.php';

class StockRequestService
{
    private AuditService $auditService;
    private PermissionService $permissionService;
    private StoreService $storeService;

    public function __construct(
        private PDO $pdo,
        ?AuditService $auditService = null,
        ?PermissionService $permissionService = null,
        ?StoreService $storeService = null
    ) {
        $this->auditService = $auditService ?? new AuditService($pdo);
        $this->permissionService = $permissionService ?? new PermissionService($pdo);
        $this->storeService = $storeService ?? new StoreService($pdo, $this->auditService, $this->permissionService);
    }

    public function createRequest(array $data, array $user): array
    {
        try {
            if (!$this->permissionService->canCreateStockRequest($user)) {
                return $this->failure(['You are not allowed to create stock requests.']);
            }

            $payload = $this->normalizeRequestPayload($data, $user);
            if ($payload['errors'] !== []) {
                return $this->failure($payload['errors']);
            }

            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare('
                INSERT INTO stock_requests (
                    requesting_department_id, requested_by, status, reason, created_at, updated_at
                ) VALUES (
                    :department_id, :requested_by, "Pending", :reason, NOW(), NOW()
                )
            ');
            $stmt->execute([
                ':department_id' => $payload['data']['requesting_department_id'],
                ':requested_by' => (int)$user['id'],
                ':reason' => $payload['data']['reason'],
            ]);
            $requestId = (int)$this->pdo->lastInsertId();

            $itemStmt = $this->pdo->prepare('
                INSERT INTO stock_request_items (
                    stock_request_id, inventory_item_id, quantity_requested, notes, created_at, updated_at
                ) VALUES (
                    :stock_request_id, :inventory_item_id, :quantity_requested, :notes, NOW(), NOW()
                )
            ');
            foreach ($payload['data']['items'] as $item) {
                $itemStmt->execute([
                    ':stock_request_id' => $requestId,
                    ':inventory_item_id' => $item['inventory_item_id'],
                    ':quantity_requested' => $item['quantity_requested'],
                    ':notes' => $item['notes'],
                ]);
            }

            if (!$this->audit((int)$user['id'], 'STOCK_REQUEST_CREATED', 'Created stock request #' . $requestId . '.')) {
                throw new RuntimeException('Unable to audit stock request creation.');
            }

            $this->pdo->commit();

            return ['success' => true, 'stock_request_id' => $requestId, 'errors' => []];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to create stock request.']);
        }
    }

    public function listRequests(array $filters = [], ?array $user = null): array
    {
        if ($user !== null && !$this->permissionService->canViewStockRequests($user)) {
            return [];
        }

        $where = [];
        $params = [];

        if (!$this->canSeeAllRequests($user)) {
            $departmentId = $this->activeDepartmentId($user);
            if ($departmentId <= 0) {
                return [];
            }
            $where[] = 'sr.requesting_department_id = :scope_department_id';
            $params[':scope_department_id'] = $departmentId;
        }

        $status = trim((string)($filters['status'] ?? ''));
        if (in_array($status, ['Pending','Approved','Issued','Partially Issued','Cancelled'], true)) {
            $where[] = 'sr.status = :status';
            $params[':status'] = $status;
        }

        $sql = $this->baseRequestSelect()
            . ($where === [] ? '' : ' WHERE ' . implode(' AND ', $where))
            . ' ORDER BY sr.created_at DESC, sr.id DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return array_map([$this, 'decorateRequest'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getRequestById(int $requestId, ?array $user = null): ?array
    {
        $stmt = $this->pdo->prepare($this->baseRequestSelect() . ' WHERE sr.id = :id LIMIT 1');
        $stmt->execute([':id' => $requestId]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$request) {
            return null;
        }
        if ($user !== null && !$this->canViewRequestRow($request, $user)) {
            return null;
        }

        $request = $this->decorateRequest($request);
        $request['items'] = $this->listItems($requestId);
        return $request;
    }

    public function approveRequest(int $requestId, array $user): array
    {
        return $this->reviewRequest($requestId, $user, 'Approved');
    }

    public function cancelRequest(int $requestId, string $reason, array $user): array
    {
        try {
            if (!$this->permissionService->canCancelStockRequest($user)) {
                return $this->failure(['You are not allowed to cancel stock requests.']);
            }
            $reason = trim($reason);
            if ($reason === '') {
                return $this->failure(['Cancellation reason is required.']);
            }
            if (mb_strlen($reason) > 2000) {
                return $this->failure(['Cancellation reason is too long.']);
            }

            $this->pdo->beginTransaction();
            $request = $this->lockRequest($requestId);
            if (!$request) {
                $this->rollback();
                return $this->failure(['Stock request not found.']);
            }
            if (!$this->canViewRequestRow($request, $user)) {
                $this->rollback();
                return $this->failure(['You cannot access this stock request.']);
            }
            if (!in_array((string)$request['status'], ['Pending','Approved','Partially Issued'], true)) {
                $this->rollback();
                return $this->failure(['Only pending, approved, or partially issued requests can be cancelled.']);
            }

            $stmt = $this->pdo->prepare('
                UPDATE stock_requests
                SET status = "Cancelled",
                    cancelled_by = :cancelled_by,
                    cancelled_at = NOW(),
                    cancel_reason = :cancel_reason,
                    updated_at = NOW()
                WHERE id = :id
            ');
            $stmt->execute([
                ':cancelled_by' => (int)$user['id'],
                ':cancel_reason' => $reason,
                ':id' => $requestId,
            ]);

            if (!$this->audit((int)$user['id'], 'STOCK_REQUEST_CANCELLED', 'Cancelled stock request #' . $requestId . '.')) {
                throw new RuntimeException('Unable to audit stock request cancellation.');
            }

            $this->pdo->commit();
            return ['success' => true, 'stock_request_id' => $requestId, 'errors' => []];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to cancel stock request.']);
        }
    }

    public function issueRequest(int $requestId, array $quantities, array $user): array
    {
        try {
            if (!$this->permissionService->canIssueStockRequest($user)) {
                return $this->failure(['You are not allowed to issue stock requests.']);
            }

            $this->pdo->beginTransaction();
            $request = $this->lockRequest($requestId);
            if (!$request) {
                $this->rollback();
                return $this->failure(['Stock request not found.']);
            }
            if (!in_array((string)$request['status'], ['Pending','Approved','Partially Issued'], true)) {
                $this->rollback();
                return $this->failure(['This stock request cannot be issued.']);
            }

            $items = $this->lockItems($requestId);
            $issuedAny = false;
            $errors = [];
            foreach ($items as $item) {
                $itemId = (int)$item['id'];
                $issueRaw = $quantities[$itemId] ?? 0;
                $issueQty = is_numeric($issueRaw) ? (float)$issueRaw : 0.0;
                if ($issueQty <= 0) {
                    continue;
                }

                $remaining = (float)$item['quantity_requested'] - (float)$item['quantity_issued'];
                if ($issueQty > $remaining) {
                    $errors[] = 'Issue quantity exceeds remaining request for ' . $item['item_name'] . '.';
                    continue;
                }

                $stockResult = $this->storeService->issueStock([
                    'inventory_item_id' => (int)$item['inventory_item_id'],
                    'department_id' => (int)$request['requesting_department_id'],
                    'quantity' => number_format($issueQty, 2, '.', ''),
                    'reference' => 'Stock Request #' . $requestId,
                    'remarks' => 'Issued against stock request item #' . $itemId,
                ], $user);

                if (!($stockResult['success'] ?? false)) {
                    $errors = array_merge($errors, $stockResult['errors'] ?? ['Unable to issue stock.']);
                    continue;
                }

                $stmt = $this->pdo->prepare('
                    UPDATE stock_request_items
                    SET quantity_issued = quantity_issued + :quantity,
                        updated_at = NOW()
                    WHERE id = :id
                ');
                $stmt->execute([
                    ':quantity' => number_format($issueQty, 2, '.', ''),
                    ':id' => $itemId,
                ]);
                $issuedAny = true;
            }

            if ($errors !== []) {
                $this->rollback();
                return $this->failure($errors);
            }
            if (!$issuedAny) {
                $this->rollback();
                return $this->failure(['Enter at least one quantity to issue.']);
            }

            $newStatus = $this->calculateIssueStatus($requestId);
            $stmt = $this->pdo->prepare('
                UPDATE stock_requests
                SET status = :status,
                    reviewed_by = COALESCE(reviewed_by, :reviewed_by),
                    reviewed_at = COALESCE(reviewed_at, NOW()),
                    updated_at = NOW()
                WHERE id = :id
            ');
            $stmt->execute([
                ':status' => $newStatus,
                ':reviewed_by' => (int)$user['id'],
                ':id' => $requestId,
            ]);

            if (!$this->audit((int)$user['id'], 'STOCK_REQUEST_ISSUED', 'Issued stock request #' . $requestId . ' (' . $newStatus . ').')) {
                throw new RuntimeException('Unable to audit stock request issue.');
            }

            $this->pdo->commit();
            return ['success' => true, 'stock_request_id' => $requestId, 'status' => $newStatus, 'errors' => []];
        } catch (Throwable $e) {
            $this->rollback();
            return $this->failure([$e->getMessage() ?: 'Unable to issue stock request.']);
        }
    }

    private function reviewRequest(int $requestId, array $user, string $status): array
    {
        try {
            if (!$this->permissionService->canReviewStockRequest($user)) {
                return $this->failure(['You are not allowed to review stock requests.']);
            }

            $this->pdo->beginTransaction();
            $request = $this->lockRequest($requestId);
            if (!$request) {
                $this->rollback();
                return $this->failure(['Stock request not found.']);
            }
            if ((string)$request['status'] !== 'Pending') {
                $this->rollback();
                return $this->failure(['Only pending stock requests can be approved.']);
            }

            $stmt = $this->pdo->prepare('
                UPDATE stock_requests
                SET status = :status,
                    reviewed_by = :reviewed_by,
                    reviewed_at = NOW(),
                    updated_at = NOW()
                WHERE id = :id
            ');
            $stmt->execute([
                ':status' => $status,
                ':reviewed_by' => (int)$user['id'],
                ':id' => $requestId,
            ]);

            if (!$this->audit((int)$user['id'], 'STOCK_REQUEST_APPROVED', 'Approved stock request #' . $requestId . '.')) {
                throw new RuntimeException('Unable to audit stock request approval.');
            }

            $this->pdo->commit();
            return ['success' => true, 'stock_request_id' => $requestId, 'errors' => []];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to approve stock request.']);
        }
    }

    private function normalizeRequestPayload(array $data, array $user): array
    {
        $errors = [];
        $departmentId = (int)($data['requesting_department_id'] ?? 0);
        if (!$this->canSeeAllRequests($user)) {
            $departmentId = $this->activeDepartmentId($user);
        }
        if ($departmentId <= 0 || !$this->departmentExists($departmentId)) {
            $errors[] = 'Requesting department is required.';
        }

        $reason = trim((string)($data['reason'] ?? ''));
        if (mb_strlen($reason) > 2000) {
            $errors[] = 'Reason is too long.';
        }

        $itemIds = (array)($data['inventory_item_id'] ?? []);
        $quantities = (array)($data['quantity_requested'] ?? []);
        $notes = (array)($data['notes'] ?? []);
        $items = [];
        foreach ($itemIds as $index => $itemIdRaw) {
            $itemId = (int)$itemIdRaw;
            $quantityRaw = $quantities[$index] ?? null;
            if ($itemId <= 0 && ($quantityRaw === null || $quantityRaw === '' || (float)$quantityRaw <= 0)) {
                continue;
            }
            if ($itemId <= 0) {
                $errors[] = 'Inventory item is required for each request line.';
                continue;
            }
            if (!$this->activeItemExists($itemId)) {
                $errors[] = 'Selected inventory item is invalid or inactive.';
                continue;
            }
            if (!is_numeric($quantityRaw) || (float)$quantityRaw <= 0) {
                $errors[] = 'Quantity must be greater than zero for each request line.';
                continue;
            }
            $note = trim((string)($notes[$index] ?? ''));
            if (mb_strlen($note) > 1000) {
                $errors[] = 'Item notes are too long.';
            }
            $items[] = [
                'inventory_item_id' => $itemId,
                'quantity_requested' => number_format((float)$quantityRaw, 2, '.', ''),
                'notes' => $note === '' ? null : $note,
            ];
        }
        if ($items === []) {
            $errors[] = 'At least one requested item is required.';
        }

        return [
            'errors' => array_values(array_unique($errors)),
            'data' => [
                'requesting_department_id' => $departmentId,
                'reason' => $reason === '' ? null : $reason,
                'items' => $items,
            ],
        ];
    }

    private function baseRequestSelect(): string
    {
        return '
            SELECT
                sr.*,
                d.department_name AS requesting_department_name,
                CONCAT(requested.first_name, " ", requested.last_name) AS requested_by_name,
                CONCAT(reviewed.first_name, " ", reviewed.last_name) AS reviewed_by_name,
                CONCAT(cancelled.first_name, " ", cancelled.last_name) AS cancelled_by_name
            FROM stock_requests sr
            INNER JOIN departments d ON d.id = sr.requesting_department_id
            LEFT JOIN users requested ON requested.id = sr.requested_by
            LEFT JOIN users reviewed ON reviewed.id = sr.reviewed_by
            LEFT JOIN users cancelled ON cancelled.id = sr.cancelled_by
        ';
    }

    private function listItems(int $requestId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT
                sri.*,
                ii.item_code,
                ii.item_name,
                ii.unit,
                ii.category
            FROM stock_request_items sri
            INNER JOIN inventory_items ii ON ii.id = sri.inventory_item_id
            WHERE sri.stock_request_id = :request_id
            ORDER BY sri.id ASC
        ');
        $stmt->execute([':request_id' => $requestId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function lockRequest(int $requestId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM stock_requests WHERE id = :id LIMIT 1 FOR UPDATE');
        $stmt->execute([':id' => $requestId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function lockItems(int $requestId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT sri.*, ii.item_name
            FROM stock_request_items sri
            INNER JOIN inventory_items ii ON ii.id = sri.inventory_item_id
            WHERE sri.stock_request_id = :request_id
            ORDER BY sri.id ASC
            FOR UPDATE
        ');
        $stmt->execute([':request_id' => $requestId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function calculateIssueStatus(int $requestId): string
    {
        $stmt = $this->pdo->prepare('
            SELECT
                SUM(quantity_issued >= quantity_requested) AS full_lines,
                COUNT(*) AS total_lines,
                SUM(quantity_issued > 0) AS issued_lines
            FROM stock_request_items
            WHERE stock_request_id = :request_id
        ');
        $stmt->execute([':request_id' => $requestId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        if ((int)($row['total_lines'] ?? 0) > 0 && (int)$row['full_lines'] === (int)$row['total_lines']) {
            return 'Issued';
        }
        return ((int)($row['issued_lines'] ?? 0) > 0) ? 'Partially Issued' : 'Approved';
    }

    private function canViewRequestRow(array $request, ?array $user): bool
    {
        if (!$user || !$this->permissionService->canViewStockRequests($user)) {
            return false;
        }
        return $this->canSeeAllRequests($user)
            || (int)$request['requesting_department_id'] === $this->activeDepartmentId($user);
    }

    private function canSeeAllRequests(?array $user): bool
    {
        return $user !== null
            && (
                $this->permissionService->isAdministrator($user)
                || $this->permissionService->canReviewStockRequest($user)
                || $this->permissionService->canIssueStockRequest($user)
                || (string)($user['active_department_name'] ?? $user['department_name'] ?? '') === 'Store'
            );
    }

    private function activeDepartmentId(?array $user): int
    {
        return (int)($user['active_department_id'] ?? $user['department_id'] ?? 0);
    }

    private function departmentExists(int $departmentId): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM departments WHERE id = :id AND is_active = 1');
        $stmt->execute([':id' => $departmentId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function activeItemExists(int $itemId): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM inventory_items WHERE id = :id AND is_active = 1');
        $stmt->execute([':id' => $itemId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function decorateRequest(array $row): array
    {
        $row['status_badge'] = strtolower(str_replace(' ', '-', (string)$row['status']));
        return $row;
    }

    private function audit(int $userId, string $action, string $description): bool
    {
        return $this->auditService->log($userId, null, 'Store', $action, $description);
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
