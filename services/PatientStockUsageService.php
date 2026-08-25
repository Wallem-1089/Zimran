<?php

declare(strict_types=1);

require_once __DIR__ . '/AuditService.php';
require_once __DIR__ . '/BillingService.php';
require_once __DIR__ . '/PermissionService.php';
require_once __DIR__ . '/StoreService.php';

class PatientStockUsageService
{
    private AuditService $auditService;
    private PermissionService $permissionService;
    private StoreService $storeService;
    private BillingService $billingService;

    public function __construct(
        private PDO $pdo,
        ?StoreService $storeService = null,
        ?BillingService $billingService = null,
        ?AuditService $auditService = null,
        ?PermissionService $permissionService = null
    ) {
        $this->permissionService = $permissionService ?? new PermissionService($pdo);
        $this->auditService = $auditService ?? new AuditService($pdo);
        $this->storeService = $storeService ?? new StoreService($pdo, null, $this->permissionService);
        $this->billingService = $billingService ?? new BillingService($pdo);
    }

    public function createUsage(array $data, array $user): array
    {
        try {
            if (!$this->permissionService->canRecordPatientStockUsage($user)) {
                return $this->failure(['You are not allowed to record patient stock usage.']);
            }

            $this->pdo->beginTransaction();

            $visit = $this->lockVisit((int)($data['visit_id'] ?? 0));
            if (!$visit) {
                $this->rollback();
                return $this->failure(['Encounter not found.']);
            }

            $errors = $this->validateUsage($data, $visit, $user);
            if ($errors !== []) {
                $this->rollback();
                return $this->failure($errors);
            }

            $departmentId = (int)(
                $data['department_id']
                ?? $user['active_department_id']
                ?? $_SESSION['active_department_id']
                ?? $user['department_id']
                ?? $visit['current_department_id']
                ?? 0
            );
            $itemId = (int)$data['inventory_item_id'];
            $quantity = number_format((float)$data['quantity'], 2, '.', '');
            $reason = $this->nullableText($data['usage_reason'] ?? null);
            $sourceModule = $this->nullableText($data['source_module'] ?? null);
            $sourceRecordId = isset($data['source_record_id']) && $data['source_record_id'] !== ''
                ? (int)$data['source_record_id']
                : null;

            $stmt = $this->pdo->prepare('
                INSERT INTO patient_stock_usage (
                    visit_id, patient_id, department_id, inventory_item_id,
                    quantity, usage_reason, source_module, source_record_id,
                    recorded_by, created_at
                ) VALUES (
                    :visit_id, :patient_id, :department_id, :inventory_item_id,
                    :quantity, :usage_reason, :source_module, :source_record_id,
                    :recorded_by, NOW()
                )
            ');
            $stmt->execute([
                ':visit_id' => (int)$visit['id'],
                ':patient_id' => (int)$visit['patient_id'],
                ':department_id' => $departmentId,
                ':inventory_item_id' => $itemId,
                ':quantity' => $quantity,
                ':usage_reason' => $reason,
                ':source_module' => $sourceModule,
                ':source_record_id' => $sourceRecordId,
                ':recorded_by' => (int)$user['id'],
            ]);
            $usageId = (int)$this->pdo->lastInsertId();

            $stock = $this->storeService->consumeDepartmentStock([
                'inventory_item_id' => $itemId,
                'department_id' => $departmentId,
                'quantity' => $quantity,
                'reference' => 'Patient stock usage #' . $usageId,
                'remarks' => $reason ?? 'Patient stock usage.',
            ], $user);
            if (!($stock['success'] ?? false)) {
                $this->rollback();
                return $this->failure($stock['errors'] ?? ['Unable to consume department stock.']);
            }

            $billingRequestId = null;
            if (!empty($data['request_billing'])) {
                $item = $this->inventoryItem($itemId);
                $billing = $this->billingService->createBillingRequest([
                    'visit_id' => (int)$visit['id'],
                    'department_id' => $departmentId,
                    'source_module' => 'Patient Stock Usage',
                    'source_record_id' => $usageId,
                    'description' => $this->billingDescription($item, $quantity, $visit),
                    'suggested_billable_item_id' => $item['billable_item_id'] ?? null,
                    'quantity' => $quantity,
                ], $user);
                if (!($billing['success'] ?? false)) {
                    $this->rollback();
                    return $this->failure($billing['errors'] ?? ['Unable to create billing request.']);
                }
                $billingRequestId = (int)$billing['billing_request_id'];
            }

            $update = $this->pdo->prepare('
                UPDATE patient_stock_usage
                SET stock_transaction_id = :stock_transaction_id,
                    billing_request_id = :billing_request_id
                WHERE id = :id
            ');
            $update->execute([
                ':stock_transaction_id' => (int)$stock['stock_transaction_id'],
                ':billing_request_id' => $billingRequestId,
                ':id' => $usageId,
            ]);

            if (!$this->auditService->logPatient(
                (int)$user['id'],
                (int)$visit['patient_id'],
                (int)$visit['id'],
                'Store',
                'PATIENT_STOCK_USAGE_RECORDED',
                'Recorded patient stock usage #' . $usageId . '.',
                $departmentId,
                'INFO',
                'PATIENT_STOCK_USAGE_RECORDED'
            )) {
                throw new RuntimeException('Unable to audit patient stock usage.');
            }

            $this->pdo->commit();

            return [
                'success' => true,
                'patient_stock_usage_id' => $usageId,
                'stock_transaction_id' => (int)$stock['stock_transaction_id'],
                'billing_request_id' => $billingRequestId,
                'visit_id' => (int)$visit['id'],
                'patient_id' => (int)$visit['patient_id'],
                'errors' => [],
            ];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to record patient stock usage.']);
        }
    }

    public function getById(int $usageId, ?array $user = null): ?array
    {
        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE psu.id = :id LIMIT 1');
        $stmt->execute([':id' => $usageId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || ($user !== null && !$this->canViewRow($row, $user))) {
            return null;
        }
        return $row;
    }

    public function listByVisit(int $visitId, ?array $user = null): array
    {
        $visit = $this->visitById($visitId);
        if (!$visit || ($user !== null && !$this->permissionService->canViewEncounter($visit, $user))) {
            return [];
        }
        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE psu.visit_id = :visit_id ORDER BY psu.created_at DESC, psu.id DESC');
        $stmt->execute([':visit_id' => $visitId]);
        return array_values(array_filter(
            $stmt->fetchAll(PDO::FETCH_ASSOC),
            fn (array $row): bool => $user === null || $this->canViewRow($row, $user)
        ));
    }

    public function listByPatient(int $patientId, ?array $user = null): array
    {
        if ($patientId <= 0 || ($user !== null && !$this->permissionService->canViewPatientStockUsage($user))) {
            return [];
        }
        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE psu.patient_id = :patient_id ORDER BY psu.created_at DESC, psu.id DESC');
        $stmt->execute([':patient_id' => $patientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listByDepartment(int $departmentId, ?array $user = null): array
    {
        if ($departmentId <= 0 || ($user !== null && !$this->permissionService->canViewPatientStockUsage($user))) {
            return [];
        }
        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE psu.department_id = :department_id ORDER BY psu.created_at DESC, psu.id DESC');
        $stmt->execute([':department_id' => $departmentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listAvailableDepartmentStock(int $departmentId, ?array $user = null): array
    {
        if ($departmentId <= 0 || ($user !== null && !$this->permissionService->canRecordPatientStockUsage($user))) {
            return [];
        }

        $stmt = $this->pdo->prepare('
            SELECT
                dsb.inventory_item_id,
                dsb.department_id,
                dsb.quantity,
                ii.item_code,
                ii.item_name,
                ii.unit,
                ii.billable_item_id,
                bi.item_name AS billable_item_name,
                bi.unit_price AS billable_item_price
            FROM department_stock_balances dsb
            INNER JOIN inventory_items ii ON ii.id = dsb.inventory_item_id
            LEFT JOIN billable_items bi ON bi.id = ii.billable_item_id
            WHERE dsb.department_id = :department_id
              AND dsb.quantity > 0
              AND ii.is_active = 1
            ORDER BY ii.item_name ASC
        ');
        $stmt->execute([':department_id' => $departmentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function validateUsage(array $data, array $visit, array $user): array
    {
        $errors = [];
        if (!$this->permissionService->canViewEncounter($visit, $user)) {
            $errors[] = 'You cannot access this encounter.';
        }
        if (!$this->permissionService->isAdministrator($user)
            && in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true)
        ) {
            $errors[] = 'Completed or cancelled encounters cannot accept new stock usage.';
        }
        if (isset($data['patient_id']) && (int)$data['patient_id'] !== (int)$visit['patient_id']) {
            $errors[] = 'Patient and encounter do not match.';
        }

        $departmentId = (int)(
            $data['department_id']
            ?? $user['active_department_id']
            ?? $_SESSION['active_department_id']
            ?? $user['department_id']
            ?? $visit['current_department_id']
            ?? 0
        );
        if ($departmentId <= 0 || !$this->departmentExists($departmentId)) {
            $errors[] = 'A valid department is required.';
        }
        if (!$this->permissionService->isAdministrator($user)) {
            $allowedDepartmentIds = array_filter(array_map('intval', [
                $user['active_department_id'] ?? null,
                $_SESSION['active_department_id'] ?? null,
                $user['department_id'] ?? null,
            ]));
            if ($allowedDepartmentIds !== [] && !in_array($departmentId, $allowedDepartmentIds, true)) {
                $errors[] = 'You can only record stock usage from your own department.';
            }
        }

        $itemId = (int)($data['inventory_item_id'] ?? 0);
        if ($itemId <= 0 || !$this->inventoryItem($itemId)) {
            $errors[] = 'Inventory item is required.';
        }

        if (!is_numeric($data['quantity'] ?? null) || (float)$data['quantity'] <= 0) {
            $errors[] = 'Quantity must be greater than zero.';
        }

        $reason = trim((string)($data['usage_reason'] ?? ''));
        if (mb_strlen($reason) > 2000) {
            $errors[] = 'Usage reason is too long.';
        }

        return $errors;
    }

    private function lockVisit(int $visitId): ?array
    {
        if ($visitId <= 0) {
            return null;
        }
        $stmt = $this->pdo->prepare('SELECT * FROM visits WHERE id = :id FOR UPDATE');
        $stmt->execute([':id' => $visitId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function visitById(int $visitId): ?array
    {
        if ($visitId <= 0) {
            return null;
        }
        $stmt = $this->pdo->prepare('SELECT * FROM visits WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $visitId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function inventoryItem(int $itemId): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT ii.*, bi.item_name AS billable_item_name, bi.unit_price AS billable_item_price
            FROM inventory_items ii
            LEFT JOIN billable_items bi ON bi.id = ii.billable_item_id
            WHERE ii.id = :id
            LIMIT 1
        ');
        $stmt->execute([':id' => $itemId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function departmentExists(int $departmentId): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM departments WHERE id = :id AND is_active = 1');
        $stmt->execute([':id' => $departmentId]);
        return (int)$stmt->fetchColumn() === 1;
    }

    private function canViewRow(array $row, array $user): bool
    {
        if (!$this->permissionService->canViewPatientStockUsage($user)) {
            return false;
        }
        if ($this->permissionService->isAdministrator($user)) {
            return true;
        }
        $role = (string)($user['role_name'] ?? '');
        if (in_array($role, ['Store Officer', 'Accounts', 'Accountant', 'Records Officer'], true)) {
            return true;
        }
        return $this->permissionService->canViewEncounter($row, $user);
    }

    private function billingDescription(array $item, string $quantity, array $visit): string
    {
        $unit = trim((string)($item['unit'] ?? ''));
        $name = trim((string)($item['item_name'] ?? 'Inventory item'));
        $visitNumber = trim((string)($visit['visit_number'] ?? ('#' . (int)$visit['id'])));
        return sprintf(
            '%s x %s%s used for patient encounter %s.',
            $name,
            $quantity,
            $unit !== '' ? ' ' . $unit : '',
            $visitNumber
        );
    }

    private function baseSelect(): string
    {
        return '
            SELECT
                psu.*,
                v.visit_number,
                v.visit_status,
                p.hospital_number,
                CONCAT(p.first_name, " ", p.last_name) AS patient_name,
                d.department_name,
                ii.item_code,
                ii.item_name,
                ii.unit,
                ii.billable_item_id,
                bi.item_name AS billable_item_name,
                bi.unit_price AS billable_item_price,
                br.status AS billing_request_status,
                CONCAT(u.first_name, " ", u.last_name) AS recorded_by_name
            FROM patient_stock_usage psu
            INNER JOIN visits v ON v.id = psu.visit_id
            INNER JOIN patients p ON p.id = psu.patient_id
            INNER JOIN departments d ON d.id = psu.department_id
            INNER JOIN inventory_items ii ON ii.id = psu.inventory_item_id
            LEFT JOIN billable_items bi ON bi.id = ii.billable_item_id
            LEFT JOIN billing_requests br ON br.id = psu.billing_request_id
            LEFT JOIN users u ON u.id = psu.recorded_by
        ';
    }

    private function nullableText(mixed $value): ?string
    {
        $text = trim((string)$value);
        return $text === '' ? null : $text;
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
