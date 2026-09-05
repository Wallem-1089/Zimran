<?php

declare(strict_types=1);

require_once __DIR__ . '/AccountsService.php';
require_once __DIR__ . '/AuditService.php';
require_once __DIR__ . '/PermissionService.php';

class BillingService
{
    private AccountsService $accountsService;
    private AuditService $auditService;
    private PermissionService $permissionService;

    public function __construct(
        private PDO $pdo,
        ?AccountsService $accountsService = null,
        ?AuditService $auditService = null,
        ?PermissionService $permissionService = null
    ) {
        $this->accountsService = $accountsService ?? new AccountsService($pdo);
        $this->auditService = $auditService ?? new AuditService($pdo);
        $this->permissionService = $permissionService ?? new PermissionService($pdo);
    }

    public function createCharge(array $data, array $user): array
    {
        $billableItemId = (int)($data['billable_item_id'] ?? 0);
        $quantity = (float)($data['quantity'] ?? 0);
        $sourceModule = trim((string)($data['source_module'] ?? 'Billing'));
        $sourceRecordId = isset($data['source_record_id']) && $data['source_record_id'] !== ''
            ? (int)$data['source_record_id']
            : null;
        $description = trim((string)($data['description'] ?? ''));
        $visitId = (int)($data['visit_id'] ?? 0);

        return $this->createChargeFromBillableItem(
            $visitId,
            $billableItemId,
            $quantity,
            $sourceModule === '' ? 'Billing' : $sourceModule,
            $sourceRecordId,
            $description === '' ? null : $description,
            $user
        );
    }

    public function createChargeFromBillableItem(
        int $visitId,
        int $billableItemId,
        float $quantity,
        string $sourceModule,
        ?int $sourceRecordId,
        ?string $description,
        array $user
    ): array {
        try {
            $this->assertCanCreateCharge($user);

            $visit = $this->loadVisit($visitId);
            if (!$visit) {
                return $this->failure(['Encounter not found.']);
            }

            if (!$this->canMutateBilling($visit)) {
                return $this->failure(['This encounter is closed to charge creation.']);
            }

            if ($billableItemId <= 0) {
                return $this->failure(['A valid billable item is required.']);
            }

            if ($quantity <= 0) {
                return $this->failure(['Quantity must be greater than zero.']);
            }

            $billableItem = $this->accountsService->getItemById($billableItemId);
            if (!$billableItem || empty($billableItem['is_active'])) {
                return $this->failure(['Selected billable item is unavailable.']);
            }

            $transactionStarted = $this->beginTransactionIfNeeded();

            if ($sourceRecordId !== null) {
                $existing = $this->findChargeBySource($sourceModule, $sourceRecordId);
                if ($existing) {
                    $invoice = $this->getInvoiceByVisit($visitId, $user);
                    if ($transactionStarted) {
                        $this->pdo->commit();
                    }

                    return [
                        'success' => true,
                        'patient_charge_id' => (int)$existing['id'],
                        'invoice_id' => (int)($invoice['id'] ?? 0),
                        'errors' => [],
                    ];
                }
            }

            $unitPrice = (float)$billableItem['unit_price'];
            $amount = round($quantity * $unitPrice, 2);
            $description = $description !== null && trim($description) !== ''
                ? trim($description)
                : (string)$billableItem['item_name'];

            $stmt = $this->pdo->prepare('
                INSERT INTO patient_charges (
                    visit_id, patient_id, billable_item_id, quantity, unit_price, amount,
                    source_module, source_record_id, description, status,
                    created_by, created_at
                ) VALUES (
                    :visit_id, :patient_id, :billable_item_id, :quantity, :unit_price, :amount,
                    :source_module, :source_record_id, :description, \'Active\',
                    :created_by, NOW()
                )
            ');
            $stmt->execute([
                ':visit_id' => $visitId,
                ':patient_id' => (int)$visit['patient_id'],
                ':billable_item_id' => $billableItemId,
                ':quantity' => number_format($quantity, 2, '.', ''),
                ':unit_price' => number_format($unitPrice, 2, '.', ''),
                ':amount' => number_format($amount, 2, '.', ''),
                ':source_module' => $sourceModule === '' ? 'Billing' : $sourceModule,
                ':source_record_id' => $sourceRecordId,
                ':description' => $description,
                ':created_by' => (int)$user['id'],
            ]);
            $chargeId = (int)$this->pdo->lastInsertId();

            $invoiceId = $this->ensureInvoice($visitId, (int)$visit['patient_id'], (int)$user['id']);
            $this->refreshInvoiceTotals($visitId, $user);

            if (!$this->audit(
                (int)$user['id'],
                (int)$visit['patient_id'],
                $visitId,
                'PATIENT_CHARGE_CREATED',
                'Created patient charge #' . $chargeId . '.',
                (int)($visit['current_department_id'] ?? 0) ?: null
            )) {
                throw new RuntimeException('Unable to audit patient charge creation.');
            }

            if ($transactionStarted) {
                $this->pdo->commit();
            }

            return [
                'success' => true,
                'patient_charge_id' => $chargeId,
                'invoice_id' => $invoiceId,
                'errors' => [],
            ];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to create patient charge.']);
        }
    }

    public function listChargesByVisit(int $visitId, ?array $user = null): array
    {
        if ($user !== null && !$this->permissionService->canViewBilling($user)) {
            return [];
        }

        $stmt = $this->pdo->prepare($this->chargeBaseSelect() . ' WHERE pc.visit_id = :visit_id ORDER BY pc.created_at DESC, pc.id DESC');
        $stmt->execute([':visit_id' => $visitId]);

        return array_map([$this, 'decorateCharge'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function cancelCharge(int $chargeId, array $user): array
    {
        try {
            $this->assertCanCancelCharge($user);
            $transactionStarted = $this->beginTransactionIfNeeded();

            $charge = $this->lockCharge($chargeId);
            if (!$charge) {
                $this->rollback();
                return $this->failure(['Patient charge not found.']);
            }

            if ((string)$charge['status'] === 'Cancelled') {
                $this->rollback();
                return $this->failure(['Patient charge is already cancelled.']);
            }

            $visit = $this->loadVisit((int)$charge['visit_id']);
            if (!$visit) {
                $this->rollback();
                return $this->failure(['Encounter not found.']);
            }

            if (!$this->canMutateBilling($visit)) {
                $this->rollback();
                return $this->failure(['This encounter is closed to charge cancellation.']);
            }

            $invoice = $this->getInvoiceByVisit((int)$charge['visit_id']);
            if ($invoice) {
                $projectedTotal = max(0.00, (float)$invoice['total_amount'] - (float)$charge['amount']);
                if ((float)$invoice['amount_paid'] > $projectedTotal) {
                    $this->rollback();
                    return $this->failure(['This charge cannot be cancelled because payments already exceed the projected balance.']);
                }
            }

            $stmt = $this->pdo->prepare('
                UPDATE patient_charges
                SET status = \'Cancelled\',
                    cancelled_by = :cancelled_by,
                    cancelled_at = NOW()
                WHERE id = :id
            ');
            $stmt->execute([
                ':cancelled_by' => (int)$user['id'],
                ':id' => $chargeId,
            ]);

            $this->refreshInvoiceTotals((int)$charge['visit_id'], $user);

            if (!$this->audit(
                (int)$user['id'],
                (int)$charge['patient_id'],
                (int)$charge['visit_id'],
                'PATIENT_CHARGE_CANCELLED',
                'Cancelled patient charge #' . $chargeId . '.',
                (int)($visit['current_department_id'] ?? 0) ?: null
            )) {
                throw new RuntimeException('Unable to audit patient charge cancellation.');
            }

            if ($transactionStarted) {
                $this->pdo->commit();
            }

            return ['success' => true, 'patient_charge_id' => $chargeId, 'errors' => []];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to cancel patient charge.']);
        }
    }

    public function getEncounterBalance(int $visitId, ?array $user = null): array
    {
        if ($user !== null && !$this->permissionService->canViewBilling($user)) {
            return ['success' => false, 'errors' => ['You are not allowed to view billing.']];
        }

        $invoice = $this->getInvoiceByVisit($visitId, $user);
        $chargesTotal = $this->sumActiveCharges($visitId);
        $paymentsTotal = $this->sumPaymentsByVisit($visitId);

        return [
            'success' => true,
            'invoice' => $invoice,
            'total_charges' => $chargesTotal,
            'amount_paid' => $paymentsTotal,
            'balance_due' => max(0.0, $chargesTotal - $paymentsTotal),
            'status' => $invoice['status'] ?? ($chargesTotal > 0 ? 'Unpaid' : 'Unbilled'),
            'errors' => [],
        ];
    }

    public function createInvoice(int $visitId, array $user): array
    {
        try {
            $this->assertCanCreateInvoice($user);
            $transactionStarted = $this->beginTransactionIfNeeded();

            $visit = $this->loadVisit($visitId);
            if (!$visit) {
                $this->rollback();
                return $this->failure(['Encounter not found.']);
            }

            if ((string)($visit['visit_status'] ?? '') === 'Cancelled') {
                $this->rollback();
                return $this->failure(['Cancelled encounters cannot be invoiced.']);
            }

            $invoice = $this->getInvoiceByVisit($visitId);
            if ($invoice) {
                $this->refreshInvoiceTotals($visitId, $user);
                if ($transactionStarted) {
                    $this->pdo->commit();
                }

                return [
                    'success' => true,
                    'invoice_id' => (int)$invoice['id'],
                    'invoice_number' => $invoice['invoice_number'],
                    'errors' => [],
                ];
            }

            $invoiceNumber = $this->generateInvoiceNumber();
            $stmt = $this->pdo->prepare('
                INSERT INTO invoices (
                    invoice_number, visit_id, patient_id, total_amount,
                    amount_paid, balance_due, status, created_by, created_at, updated_at
                ) VALUES (
                    :invoice_number, :visit_id, :patient_id, 0.00,
                    0.00, 0.00, \'Unpaid\', :created_by, NOW(), NOW()
                )
            ');
            $stmt->execute([
                ':invoice_number' => $invoiceNumber,
                ':visit_id' => $visitId,
                ':patient_id' => (int)$visit['patient_id'],
                ':created_by' => (int)$user['id'],
            ]);
            $invoiceId = (int)$this->pdo->lastInsertId();

            $this->refreshInvoiceTotals($visitId, $user);

            if (!$this->audit(
                (int)$user['id'],
                (int)$visit['patient_id'],
                $visitId,
                'INVOICE_CREATED',
                'Created invoice #' . $invoiceId . '.',
                (int)($visit['current_department_id'] ?? 0) ?: null
            )) {
                throw new RuntimeException('Unable to audit invoice creation.');
            }

            if ($transactionStarted) {
                $this->pdo->commit();
            }

            return [
                'success' => true,
                'invoice_id' => $invoiceId,
                'invoice_number' => $invoiceNumber,
                'errors' => [],
            ];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to create invoice.']);
        }
    }

    public function getInvoiceById(int $invoiceId, ?array $user = null): ?array
    {
        $stmt = $this->pdo->prepare($this->invoiceBaseSelect() . ' WHERE i.id = :id LIMIT 1');
        $stmt->execute([':id' => $invoiceId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        if ($user !== null && !$this->permissionService->canViewBilling($user)) {
            return null;
        }

        return $this->decorateInvoice($row);
    }

    public function getInvoiceByVisit(int $visitId, ?array $user = null): ?array
    {
        $stmt = $this->pdo->prepare($this->invoiceBaseSelect() . ' WHERE i.visit_id = :visit_id LIMIT 1');
        $stmt->execute([':visit_id' => $visitId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        if ($user !== null && !$this->permissionService->canViewBilling($user)) {
            return null;
        }

        return $this->decorateInvoice($row);
    }

    public function listInvoices(array $filters = [], ?array $user = null): array
    {
        if ($user !== null && !$this->permissionService->canViewBilling($user)) {
            return [];
        }

        [$where, $params] = $this->buildInvoiceFilters($filters);
        $stmt = $this->pdo->prepare($this->invoiceBaseSelect() . $where . ' ORDER BY i.created_at DESC, i.id DESC');
        $stmt->execute($params);

        return array_map([$this, 'decorateInvoice'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function refreshInvoiceTotals(int $visitId, array $user): array
    {
        try {
            $this->assertCanViewBilling($user);
            $transactionStarted = $this->beginTransactionIfNeeded();

            $invoice = $this->lockInvoiceByVisit($visitId);
            if (!$invoice) {
                $this->rollback();
                return $this->failure(['Invoice not found.']);
            }

            $chargesTotal = $this->sumActiveCharges($visitId);
            $paymentsTotal = $this->sumPaymentsByVisit($visitId);
            $balanceDue = max(0.00, $chargesTotal - $paymentsTotal);
            $status = $this->deriveInvoiceStatus($chargesTotal, $paymentsTotal);

            $stmt = $this->pdo->prepare('
                UPDATE invoices
                SET total_amount = :total_amount,
                    amount_paid = :amount_paid,
                    balance_due = :balance_due,
                    status = :status,
                    updated_at = NOW()
                WHERE id = :id
            ');
            $stmt->execute([
                ':total_amount' => number_format($chargesTotal, 2, '.', ''),
                ':amount_paid' => number_format($paymentsTotal, 2, '.', ''),
                ':balance_due' => number_format($balanceDue, 2, '.', ''),
                ':status' => $status,
                ':id' => (int)$invoice['id'],
            ]);

            if ($transactionStarted) {
                $this->pdo->commit();
            }

            return [
                'success' => true,
                'invoice_id' => (int)$invoice['id'],
                'invoice_number' => (string)$invoice['invoice_number'],
                'total_amount' => $chargesTotal,
                'amount_paid' => $paymentsTotal,
                'balance_due' => $balanceDue,
                'status' => $status,
                'errors' => [],
            ];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to refresh invoice totals.']);
        }
    }

    public function recordPayment(array $data, array $user): array
    {
        try {
            $this->assertCanRecordPayment($user);
            $transactionStarted = $this->beginTransactionIfNeeded();

            $invoiceId = (int)($data['invoice_id'] ?? 0);
            $invoice = $this->lockInvoice($invoiceId);
            if (!$invoice) {
                $this->rollback();
                return $this->failure(['Invoice not found.']);
            }

            $visit = $this->loadVisit((int)$invoice['visit_id']);
            if (!$visit) {
                $this->rollback();
                return $this->failure(['Encounter not found.']);
            }

            if (!$this->canMutateBillingForPayment($visit)) {
                $this->rollback();
                return $this->failure(['Cancelled encounters cannot accept payments.']);
            }

            $amount = (float)($data['amount'] ?? 0);
            $paymentMethod = $this->normalizePaymentMethod((string)($data['payment_method'] ?? ''));
            $reference = trim((string)($data['reference'] ?? ''));
            $notes = trim((string)($data['notes'] ?? ''));

            if ($amount <= 0) {
                $this->rollback();
                return $this->failure(['Payment amount must be greater than zero.']);
            }

            if ($amount > (float)$invoice['balance_due']) {
                $this->rollback();
                return $this->failure(['Payment exceeds the outstanding balance.']);
            }

            $stmt = $this->pdo->prepare('
                INSERT INTO payments (
                    invoice_id, visit_id, patient_id, amount, payment_method,
                    reference, notes, received_by, created_at
                ) VALUES (
                    :invoice_id, :visit_id, :patient_id, :amount, :payment_method,
                    :reference, :notes, :received_by, NOW()
                )
            ');
            $stmt->execute([
                ':invoice_id' => $invoiceId,
                ':visit_id' => (int)$invoice['visit_id'],
                ':patient_id' => (int)$invoice['patient_id'],
                ':amount' => number_format($amount, 2, '.', ''),
                ':payment_method' => $paymentMethod,
                ':reference' => $reference === '' ? null : $reference,
                ':notes' => $notes === '' ? null : $notes,
                ':received_by' => (int)$user['id'],
            ]);
            $paymentId = (int)$this->pdo->lastInsertId();

            $this->refreshInvoiceTotals((int)$invoice['visit_id'], $user);

            if (!$this->audit(
                (int)$user['id'],
                (int)$invoice['patient_id'],
                (int)$invoice['visit_id'],
                'PAYMENT_RECORDED',
                'Recorded payment #' . $paymentId . '.',
                (int)($visit['current_department_id'] ?? 0) ?: null
            )) {
                throw new RuntimeException('Unable to audit payment.');
            }

            if ($transactionStarted) {
                $this->pdo->commit();
            }

            return [
                'success' => true,
                'payment_id' => $paymentId,
                'invoice_id' => $invoiceId,
                'errors' => [],
            ];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to record payment.']);
        }
    }

    public function listPayments(?int $visitId = null, ?array $user = null): array
    {
        if ($user !== null && !$this->permissionService->canViewBilling($user)) {
            return [];
        }

        $sql = $this->paymentBaseSelect();
        $params = [];

        if ($visitId !== null && $visitId > 0) {
            $sql .= ' WHERE p.visit_id = :visit_id';
            $params[':visit_id'] = $visitId;
        }

        $sql .= ' ORDER BY p.created_at DESC, p.id DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return array_map([$this, 'decoratePayment'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function listPaymentsFiltered(array $filters = [], ?array $user = null, int $limit = 0): array
    {
        if ($user !== null && !$this->permissionService->canViewBilling($user)) {
            return [];
        }

        [$where, $params] = $this->buildPaymentFilters($filters);
        $limitSql = '';
        if ($limit > 0) {
            $limitSql = ' LIMIT ' . min($limit, 500);
        }

        $stmt = $this->pdo->prepare($this->paymentBaseSelect() . $where . ' ORDER BY p.created_at DESC, p.id DESC' . $limitSql);
        $stmt->execute($params);

        return array_map([$this, 'decoratePayment'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getReceiptData(int $paymentId, ?array $user = null): ?array
    {
        $stmt = $this->pdo->prepare($this->paymentBaseSelect() . ' WHERE p.id = :id LIMIT 1');
        $stmt->execute([':id' => $paymentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        if ($user !== null && !$this->permissionService->canViewReceipts($user)) {
            return null;
        }

        return $this->decoratePayment($row);
    }

    public function listReceipts(?int $visitId = null, ?array $user = null): array
    {
        return $this->listPayments($visitId, $user);
    }

    public function searchEncountersForBilling(array $filters, ?array $user = null): array
    {
        if ($user !== null && !$this->permissionService->canViewBilling($user)) {
            return [];
        }

        [$where, $params] = $this->buildEncounterBillingFilters($filters);

        $stmt = $this->pdo->prepare('
            SELECT
                v.id AS visit_id,
                v.visit_number,
                v.visit_status,
                v.visit_date,
                p.id AS patient_id,
                p.hospital_number,
                CONCAT(p.first_name, " ", p.last_name) AS patient_name,
                d.department_name,
                COALESCE(i.invoice_number, "-") AS invoice_number,
                COALESCE(i.status, "Unbilled") AS billing_status,
                COALESCE(i.total_amount, 0.00) AS total_amount,
                COALESCE(i.amount_paid, 0.00) AS amount_paid,
                COALESCE(i.balance_due, 0.00) AS balance_due
            FROM visits v
            INNER JOIN patients p ON p.id = v.patient_id
            LEFT JOIN departments d ON d.id = v.current_department_id
            LEFT JOIN invoices i ON i.visit_id = v.id
            ' . $where . '
            ORDER BY v.visit_date DESC, v.id DESC
            LIMIT 50
        ');
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function createBillingRequest(array $data, array $user): array
    {
        try {
            $this->assertCanCreateBillingRequest($user);

            $visitId = (int)($data['visit_id'] ?? 0);
            $transactionStarted = $this->beginTransactionIfNeeded();
            $visit = $this->lockVisit($visitId);
            if (!$visit) {
                $this->rollback();
                return $this->failure(['Encounter not found.']);
            }

            if (!$this->canMutateBilling($visit)) {
                $this->rollback();
                return $this->failure(['This encounter is closed to new billing requests.']);
            }

            $description = trim((string)($data['description'] ?? ''));
            if ($description === '') {
                $this->rollback();
                return $this->failure(['Billing request description is required.']);
            }
            if (mb_strlen($description) > 2000) {
                $this->rollback();
                return $this->failure(['Billing request description is too long.']);
            }

            $quantity = (float)($data['quantity'] ?? 1);
            if ($quantity <= 0) {
                $this->rollback();
                return $this->failure(['Quantity must be greater than zero.']);
            }

            $sourceModule = trim((string)($data['source_module'] ?? 'General'));
            if ($sourceModule === '') {
                $sourceModule = 'General';
            }
            if (mb_strlen($sourceModule) > 100) {
                $this->rollback();
                return $this->failure(['Source module is too long.']);
            }

            $sourceRecordId = isset($data['source_record_id']) && $data['source_record_id'] !== ''
                ? (int)$data['source_record_id']
                : null;
            if ($sourceRecordId !== null && $sourceRecordId <= 0) {
                $this->rollback();
                return $this->failure(['Source record is invalid.']);
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
                $this->rollback();
                return $this->failure(['A valid department is required for the billing request.']);
            }

            $suggestedBillableItemId = isset($data['suggested_billable_item_id']) && $data['suggested_billable_item_id'] !== ''
                ? (int)$data['suggested_billable_item_id']
                : null;
            if ($suggestedBillableItemId !== null) {
                $suggestedItem = $this->accountsService->getItemById($suggestedBillableItemId);
                if (!$suggestedItem || empty($suggestedItem['is_active'])) {
                    $this->rollback();
                    return $this->failure(['Suggested billable item is unavailable.']);
                }
            }

            $stmt = $this->pdo->prepare('
                INSERT INTO billing_requests (
                    visit_id, patient_id, department_id, source_module, source_record_id,
                    requested_by, description, suggested_billable_item_id, quantity, status,
                    created_at, updated_at
                ) VALUES (
                    :visit_id, :patient_id, :department_id, :source_module, :source_record_id,
                    :requested_by, :description, :suggested_billable_item_id, :quantity, \'Pending\',
                    NOW(), NOW()
                )
            ');
            $stmt->execute([
                ':visit_id' => $visitId,
                ':patient_id' => (int)$visit['patient_id'],
                ':department_id' => $departmentId,
                ':source_module' => $sourceModule,
                ':source_record_id' => $sourceRecordId,
                ':requested_by' => (int)$user['id'],
                ':description' => $description,
                ':suggested_billable_item_id' => $suggestedBillableItemId,
                ':quantity' => number_format($quantity, 2, '.', ''),
            ]);
            $requestId = (int)$this->pdo->lastInsertId();

            if (!$this->audit(
                (int)$user['id'],
                (int)$visit['patient_id'],
                $visitId,
                'BILLING_REQUEST_CREATED',
                'Created billing request #' . $requestId . '.',
                $departmentId
            )) {
                throw new RuntimeException('Unable to audit billing request creation.');
            }

            if ($transactionStarted) {
                $this->pdo->commit();
            }

            return ['success' => true, 'billing_request_id' => $requestId, 'errors' => []];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to create billing request.']);
        }
    }

    public function getBillingRequestById(int $requestId, ?array $user = null): ?array
    {
        $stmt = $this->pdo->prepare($this->billingRequestBaseSelect() . ' WHERE br.id = :id LIMIT 1');
        $stmt->execute([':id' => $requestId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        if ($user !== null && !$this->canViewBillingRequestRow($row, $user)) {
            return null;
        }

        return $this->decorateBillingRequest($row);
    }

    public function listBillingRequests(array $filters = [], ?array $user = null): array
    {
        $visitId = (int)($filters['visit_id'] ?? 0);
        if ($user !== null && !$this->permissionService->canViewBillingRequests($user)) {
            if ($visitId <= 0 || !$this->permissionService->canCreateBillingRequest($user)) {
                return [];
            }
        }

        [$where, $params] = $this->buildBillingRequestFilters($filters);
        $limit = (int)($filters['limit'] ?? 100);
        $limitSql = '';
        if ($limit > 0) {
            $limitSql = ' LIMIT ' . min($limit, 500);
        }

        $stmt = $this->pdo->prepare($this->billingRequestBaseSelect() . $where . ' ORDER BY br.created_at DESC, br.id DESC' . $limitSql);
        $stmt->execute($params);

        $rows = array_map([$this, 'decorateBillingRequest'], $stmt->fetchAll(PDO::FETCH_ASSOC));
        if ($user !== null && !$this->permissionService->canViewBillingRequests($user)) {
            return array_values(array_filter(
                $rows,
                fn (array $row): bool => $this->canViewBillingRequestRow($row, $user)
            ));
        }

        return $rows;
    }

    public function chargeBillingRequest(array $data, array $user): array
    {
        try {
            $this->assertCanReviewBillingRequest($user);
            $requestId = (int)($data['billing_request_id'] ?? 0);
            $billableItemId = (int)($data['billable_item_id'] ?? 0);
            $quantity = (float)($data['quantity'] ?? 0);
            $notes = trim((string)($data['notes'] ?? ''));

            $transactionStarted = $this->beginTransactionIfNeeded();
            $request = $this->lockBillingRequest($requestId);
            if (!$request) {
                $this->rollback();
                return $this->failure(['Billing request not found.']);
            }

            if ((string)$request['status'] !== 'Pending') {
                $this->rollback();
                return $this->failure(['Only pending billing requests can be charged.']);
            }

            $chargeResult = $this->createChargeFromBillableItem(
                (int)$request['visit_id'],
                $billableItemId,
                $quantity > 0 ? $quantity : (float)$request['quantity'],
                'BillingRequest',
                $requestId,
                trim((string)($data['description'] ?? '')) ?: (string)$request['description'],
                $user
            );

            if (empty($chargeResult['success'])) {
                $this->rollback();
                return $chargeResult;
            }

            $patientChargeId = (int)($chargeResult['patient_charge_id'] ?? 0);
            $stmt = $this->pdo->prepare('
                UPDATE billing_requests
                SET status = \'Charged\',
                    reviewed_by = :reviewed_by,
                    reviewed_at = NOW(),
                    patient_charge_id = :patient_charge_id,
                    notes = :notes,
                    updated_at = NOW()
                WHERE id = :id
                  AND status = \'Pending\'
            ');
            $stmt->execute([
                ':reviewed_by' => (int)$user['id'],
                ':patient_charge_id' => $patientChargeId > 0 ? $patientChargeId : null,
                ':notes' => $notes === '' ? null : $notes,
                ':id' => $requestId,
            ]);

            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException('Unable to update billing request status.');
            }

            if (!$this->audit(
                (int)$user['id'],
                (int)$request['patient_id'],
                (int)$request['visit_id'],
                'BILLING_REQUEST_CHARGED',
                'Converted billing request #' . $requestId . ' to patient charge #' . $patientChargeId . '.',
                (int)($request['department_id'] ?? 0) ?: null
            )) {
                throw new RuntimeException('Unable to audit billing request charge.');
            }

            if ($transactionStarted) {
                $this->pdo->commit();
            }

            return [
                'success' => true,
                'billing_request_id' => $requestId,
                'patient_charge_id' => $patientChargeId,
                'errors' => [],
            ];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to create charge from billing request.']);
        }
    }

    public function cancelBillingRequest(int $requestId, string $reason, array $user): array
    {
        try {
            $this->assertCanCancelBillingRequest($user);
            $reason = trim($reason);
            if ($reason === '') {
                return $this->failure(['Cancellation reason is required.']);
            }

            $transactionStarted = $this->beginTransactionIfNeeded();
            $request = $this->lockBillingRequest($requestId);
            if (!$request) {
                $this->rollback();
                return $this->failure(['Billing request not found.']);
            }

            if ((string)$request['status'] !== 'Pending') {
                $this->rollback();
                return $this->failure(['Only pending billing requests can be cancelled.']);
            }

            $stmt = $this->pdo->prepare('
                UPDATE billing_requests
                SET status = \'Cancelled\',
                    reviewed_by = :reviewed_by,
                    reviewed_at = NOW(),
                    notes = :notes,
                    updated_at = NOW()
                WHERE id = :id
                  AND status = \'Pending\'
            ');
            $stmt->execute([
                ':reviewed_by' => (int)$user['id'],
                ':notes' => $reason,
                ':id' => $requestId,
            ]);

            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException('Unable to cancel billing request.');
            }

            if (!$this->audit(
                (int)$user['id'],
                (int)$request['patient_id'],
                (int)$request['visit_id'],
                'BILLING_REQUEST_CANCELLED',
                'Cancelled billing request #' . $requestId . '.',
                (int)($request['department_id'] ?? 0) ?: null
            )) {
                throw new RuntimeException('Unable to audit billing request cancellation.');
            }

            if ($transactionStarted) {
                $this->pdo->commit();
            }

            return ['success' => true, 'billing_request_id' => $requestId, 'errors' => []];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to cancel billing request.']);
        }
    }

    private function canMutateBilling(array $visit): bool
    {
        return !in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true);
    }

    private function canMutateBillingForPayment(array $visit): bool
    {
        return (string)($visit['visit_status'] ?? '') !== 'Cancelled';
    }

    private function assertCanCreateCharge(array $user): void
    {
        if (!$this->permissionService->canCreatePatientCharge($user)) {
            throw new RuntimeException('You are not allowed to create patient charges.');
        }
    }

    private function assertCanCancelCharge(array $user): void
    {
        if (!$this->permissionService->canCancelPatientCharge($user)) {
            throw new RuntimeException('You are not allowed to cancel patient charges.');
        }
    }

    private function assertCanCreateBillingRequest(array $user): void
    {
        if (!$this->permissionService->canCreateBillingRequest($user)) {
            throw new RuntimeException('You are not allowed to create billing requests.');
        }
    }

    private function assertCanReviewBillingRequest(array $user): void
    {
        if (!$this->permissionService->canReviewBillingRequest($user)) {
            throw new RuntimeException('You are not allowed to review billing requests.');
        }
    }

    private function assertCanCancelBillingRequest(array $user): void
    {
        if (!$this->permissionService->canCancelBillingRequest($user)) {
            throw new RuntimeException('You are not allowed to cancel billing requests.');
        }
    }

    private function assertCanCreateInvoice(array $user): void
    {
        if (!$this->permissionService->canCreateInvoice($user)) {
            throw new RuntimeException('You are not allowed to create invoices.');
        }
    }

    private function assertCanRecordPayment(array $user): void
    {
        if (!$this->permissionService->canRecordPayment($user)) {
            throw new RuntimeException('You are not allowed to record payments.');
        }
    }

    private function assertCanViewBilling(array $user): void
    {
        if (!$this->permissionService->canViewBilling($user)) {
            throw new RuntimeException('You are not allowed to view billing.');
        }
    }

    private function loadVisit(int $visitId): ?array
    {
        if ($visitId <= 0) {
            return null;
        }

        $stmt = $this->pdo->prepare('
            SELECT
                v.*,
                p.hospital_number,
                CONCAT(p.first_name, " ", p.last_name) AS patient_name,
                d.department_name
            FROM visits v
            INNER JOIN patients p ON p.id = v.patient_id
            LEFT JOIN departments d ON d.id = v.current_department_id
            WHERE v.id = :id
            LIMIT 1
        ');
        $stmt->execute([':id' => $visitId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function lockVisit(int $visitId): ?array
    {
        if ($visitId <= 0) {
            return null;
        }

        $stmt = $this->pdo->prepare('
            SELECT
                v.*,
                p.hospital_number,
                CONCAT(p.first_name, " ", p.last_name) AS patient_name,
                d.department_name
            FROM visits v
            INNER JOIN patients p ON p.id = v.patient_id
            LEFT JOIN departments d ON d.id = v.current_department_id
            WHERE v.id = :id
            LIMIT 1
            FOR UPDATE
        ');
        $stmt->execute([':id' => $visitId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function lockInvoice(int $invoiceId): ?array
    {
        if ($invoiceId <= 0) {
            return null;
        }

        $stmt = $this->pdo->prepare($this->invoiceBaseSelect() . ' WHERE i.id = :id LIMIT 1 FOR UPDATE');
        $stmt->execute([':id' => $invoiceId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->decorateInvoice($row) : null;
    }

    private function lockInvoiceByVisit(int $visitId): ?array
    {
        if ($visitId <= 0) {
            return null;
        }

        $stmt = $this->pdo->prepare($this->invoiceBaseSelect() . ' WHERE i.visit_id = :visit_id LIMIT 1 FOR UPDATE');
        $stmt->execute([':visit_id' => $visitId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->decorateInvoice($row) : null;
    }

    private function lockCharge(int $chargeId): ?array
    {
        if ($chargeId <= 0) {
            return null;
        }

        $stmt = $this->pdo->prepare($this->chargeBaseSelect() . ' WHERE pc.id = :id LIMIT 1 FOR UPDATE');
        $stmt->execute([':id' => $chargeId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->decorateCharge($row) : null;
    }

    private function findChargeBySource(string $sourceModule, int $sourceRecordId): ?array
    {
        $stmt = $this->pdo->prepare($this->chargeBaseSelect() . ' WHERE pc.source_module = :source_module AND pc.source_record_id = :source_record_id LIMIT 1');
        $stmt->execute([
            ':source_module' => $sourceModule,
            ':source_record_id' => $sourceRecordId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->decorateCharge($row) : null;
    }

    private function ensureInvoice(int $visitId, int $patientId, int $userId): int
    {
        $invoice = $this->getInvoiceByVisit($visitId);
        if ($invoice) {
            return (int)$invoice['id'];
        }

        $invoiceNumber = $this->generateInvoiceNumber();
        $stmt = $this->pdo->prepare('
            INSERT INTO invoices (
                invoice_number, visit_id, patient_id, total_amount,
                amount_paid, balance_due, status, created_by, created_at, updated_at
            ) VALUES (
                :invoice_number, :visit_id, :patient_id, 0.00,
                0.00, 0.00, \'Unpaid\', :created_by, NOW(), NOW()
            )
        ');
        $stmt->execute([
            ':invoice_number' => $invoiceNumber,
            ':visit_id' => $visitId,
            ':patient_id' => $patientId,
            ':created_by' => $userId,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    private function generateInvoiceNumber(): string
    {
        return sprintf('INV-%s-%s', date('Ymd'), strtoupper(bin2hex(random_bytes(3))));
    }

    private function sumActiveCharges(int $visitId): float
    {
        $stmt = $this->pdo->prepare('
            SELECT COALESCE(SUM(amount), 0)
            FROM patient_charges
            WHERE visit_id = :visit_id
              AND status = \'Active\'
        ');
        $stmt->execute([':visit_id' => $visitId]);
        return (float)$stmt->fetchColumn();
    }

    private function sumPaymentsByVisit(int $visitId): float
    {
        $stmt = $this->pdo->prepare('
            SELECT COALESCE(SUM(amount), 0)
            FROM payments
            WHERE visit_id = :visit_id
        ');
        $stmt->execute([':visit_id' => $visitId]);
        return (float)$stmt->fetchColumn();
    }

    private function deriveInvoiceStatus(float $chargesTotal, float $paymentsTotal): string
    {
        if ($chargesTotal <= 0 && $paymentsTotal <= 0) {
            return 'Unpaid';
        }

        if ($paymentsTotal <= 0) {
            return 'Unpaid';
        }

        if ($paymentsTotal >= $chargesTotal) {
            return 'Paid';
        }

        return 'Partially Paid';
    }

    private function buildInvoiceFilters(array $filters): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['invoice_number'])) {
            $where[] = 'i.invoice_number LIKE :invoice_number';
            $params[':invoice_number'] = '%' . trim((string)$filters['invoice_number']) . '%';
        }

        $visitId = (int)($filters['visit_id'] ?? $filters['encounter_id'] ?? 0);
        if ($visitId > 0) {
            $where[] = 'i.visit_id = :visit_id';
            $params[':visit_id'] = $visitId;
        }

        if (!empty($filters['visit_number'])) {
            $where[] = 'v.visit_number LIKE :visit_number';
            $params[':visit_number'] = '%' . trim((string)$filters['visit_number']) . '%';
        }

        if (!empty($filters['hospital_number'])) {
            $where[] = 'p.hospital_number LIKE :hospital_number';
            $params[':hospital_number'] = '%' . trim((string)$filters['hospital_number']) . '%';
        }

        if (!empty($filters['patient_name'])) {
            $where[] = '(CONCAT(p.first_name, " ", p.last_name) LIKE :patient_name OR p.first_name LIKE :patient_name OR p.last_name LIKE :patient_name)';
            $params[':patient_name'] = '%' . trim((string)$filters['patient_name']) . '%';
        }

        $status = trim((string)($filters['status'] ?? ''));
        if ($status !== '' && in_array($status, ['Unpaid', 'Partially Paid', 'Paid', 'Cancelled'], true)) {
            $where[] = 'i.status = :status';
            $params[':status'] = $status;
        }

        if (!empty($filters['payment_reference'])) {
            $where[] = 'EXISTS (
                SELECT 1
                FROM payments payment_filter
                WHERE payment_filter.invoice_id = i.id
                  AND payment_filter.reference LIKE :payment_reference
            )';
            $params[':payment_reference'] = '%' . trim((string)$filters['payment_reference']) . '%';
        }

        return [
            $where === [] ? '' : ' WHERE ' . implode(' AND ', $where),
            $params,
        ];
    }

    private function buildEncounterBillingFilters(array $filters): array
    {
        $where = [];
        $params = [];

        $visitId = (int)($filters['visit_id'] ?? $filters['encounter_id'] ?? 0);
        if ($visitId > 0) {
            $where[] = 'v.id = :visit_id';
            $params[':visit_id'] = $visitId;
        }

        if (!empty($filters['visit_number'])) {
            $where[] = 'v.visit_number LIKE :visit_number';
            $params[':visit_number'] = '%' . trim((string)$filters['visit_number']) . '%';
        }

        if (!empty($filters['hospital_number'])) {
            $where[] = 'p.hospital_number LIKE :hospital_number';
            $params[':hospital_number'] = '%' . trim((string)$filters['hospital_number']) . '%';
        }

        if (!empty($filters['patient_name'])) {
            $where[] = '(CONCAT(p.first_name, " ", p.last_name) LIKE :patient_name OR p.first_name LIKE :patient_name OR p.last_name LIKE :patient_name)';
            $params[':patient_name'] = '%' . trim((string)$filters['patient_name']) . '%';
        }

        if (!empty($filters['payment_reference'])) {
            $where[] = 'EXISTS (
                SELECT 1
                FROM payments payment_filter
                WHERE payment_filter.visit_id = v.id
                  AND payment_filter.reference LIKE :payment_reference
            )';
            $params[':payment_reference'] = '%' . trim((string)$filters['payment_reference']) . '%';
        }

        $status = trim((string)($filters['visit_status'] ?? ''));
        if ($status !== '' && in_array($status, ['Waiting', 'Reception', 'Records', 'Nursing', 'Doctor', 'Laboratory', 'Radiology', 'X-Ray', 'ECG', 'POP', 'Pharmacy', 'Physiotherapy', 'Theatre', 'Accounts', 'Store', 'Orderly', 'Completed', 'Cancelled'], true)) {
            $where[] = 'v.visit_status = :visit_status';
            $params[':visit_status'] = $status;
        }

        return [
            $where === [] ? '' : ' WHERE ' . implode(' AND ', $where),
            $params,
        ];
    }

    private function buildBillingRequestFilters(array $filters): array
    {
        $where = [];
        $params = [];

        $visitId = (int)($filters['visit_id'] ?? 0);
        if ($visitId > 0) {
            $where[] = 'br.visit_id = :visit_id';
            $params[':visit_id'] = $visitId;
        }

        $encounterId = (int)($filters['encounter_id'] ?? 0);
        if ($encounterId > 0 && $visitId <= 0) {
            $where[] = 'br.visit_id = :encounter_id';
            $params[':encounter_id'] = $encounterId;
        }

        $departmentId = (int)($filters['department_id'] ?? 0);
        if ($departmentId > 0) {
            $where[] = 'br.department_id = :department_id';
            $params[':department_id'] = $departmentId;
        }

        $status = trim((string)($filters['status'] ?? ''));
        if ($status !== '' && in_array($status, ['Pending', 'Charged', 'Cancelled'], true)) {
            $where[] = 'br.status = :status';
            $params[':status'] = $status;
        }

        $sourceModule = trim((string)($filters['source_module'] ?? ''));
        if ($sourceModule !== '') {
            $where[] = 'br.source_module = :source_module';
            $params[':source_module'] = $sourceModule;
        }

        if (!empty($filters['patient_name'])) {
            $where[] = '(CONCAT(p.first_name, " ", p.last_name) LIKE :patient_name OR p.first_name LIKE :patient_name OR p.last_name LIKE :patient_name)';
            $params[':patient_name'] = '%' . trim((string)$filters['patient_name']) . '%';
        }

        if (!empty($filters['hospital_number'])) {
            $where[] = 'p.hospital_number LIKE :hospital_number';
            $params[':hospital_number'] = '%' . trim((string)$filters['hospital_number']) . '%';
        }

        if (!empty($filters['visit_number'])) {
            $where[] = 'v.visit_number LIKE :visit_number';
            $params[':visit_number'] = '%' . trim((string)$filters['visit_number']) . '%';
        }

        return [
            $where === [] ? '' : ' WHERE ' . implode(' AND ', $where),
            $params,
        ];
    }

    private function buildPaymentFilters(array $filters): array
    {
        $where = [];
        $params = [];

        $visitId = (int)($filters['visit_id'] ?? $filters['encounter_id'] ?? 0);
        if ($visitId > 0) {
            $where[] = 'p.visit_id = :visit_id';
            $params[':visit_id'] = $visitId;
        }

        if (!empty($filters['invoice_number'])) {
            $where[] = 'i.invoice_number LIKE :invoice_number';
            $params[':invoice_number'] = '%' . trim((string)$filters['invoice_number']) . '%';
        }

        if (!empty($filters['visit_number'])) {
            $where[] = 'v.visit_number LIKE :visit_number';
            $params[':visit_number'] = '%' . trim((string)$filters['visit_number']) . '%';
        }

        if (!empty($filters['hospital_number'])) {
            $where[] = 'patient.hospital_number LIKE :hospital_number';
            $params[':hospital_number'] = '%' . trim((string)$filters['hospital_number']) . '%';
        }

        if (!empty($filters['patient_name'])) {
            $where[] = '(CONCAT(patient.first_name, " ", patient.last_name) LIKE :patient_name OR patient.first_name LIKE :patient_name OR patient.last_name LIKE :patient_name)';
            $params[':patient_name'] = '%' . trim((string)$filters['patient_name']) . '%';
        }

        if (!empty($filters['payment_reference'])) {
            $where[] = 'p.reference LIKE :payment_reference';
            $params[':payment_reference'] = '%' . trim((string)$filters['payment_reference']) . '%';
        }

        return [
            $where === [] ? '' : ' WHERE ' . implode(' AND ', $where),
            $params,
        ];
    }

    private function paymentMethodAllowlist(): array
    {
        return ['Cash', 'Card', 'Transfer', 'Other'];
    }

    private function normalizePaymentMethod(string $paymentMethod): string
    {
        $paymentMethod = trim($paymentMethod);
        if (!in_array($paymentMethod, $this->paymentMethodAllowlist(), true)) {
            throw new RuntimeException('Invalid payment method.');
        }

        return $paymentMethod;
    }

    private function chargeBaseSelect(): string
    {
        return '
            SELECT
                pc.*,
                bi.item_code,
                bi.item_name,
                bi.item_type,
                bi.unit,
                CONCAT(created_by.first_name, " ", created_by.last_name) AS created_by_name,
                CONCAT(cancelled_by.first_name, " ", cancelled_by.last_name) AS cancelled_by_name
            FROM patient_charges pc
            INNER JOIN billable_items bi ON bi.id = pc.billable_item_id
            LEFT JOIN users created_by ON created_by.id = pc.created_by
            LEFT JOIN users cancelled_by ON cancelled_by.id = pc.cancelled_by
        ';
    }

    private function invoiceBaseSelect(): string
    {
        return '
            SELECT
                i.*,
                p.hospital_number,
                CONCAT(p.first_name, " ", p.last_name) AS patient_name,
                v.visit_number,
                d.department_name,
                CONCAT(created_by.first_name, " ", created_by.last_name) AS created_by_name
            FROM invoices i
            INNER JOIN visits v ON v.id = i.visit_id
            INNER JOIN patients p ON p.id = i.patient_id
            LEFT JOIN departments d ON d.id = v.current_department_id
            LEFT JOIN users created_by ON created_by.id = i.created_by
        ';
    }

    private function paymentBaseSelect(): string
    {
        return '
            SELECT
                p.*,
                i.invoice_number,
                i.status AS invoice_status,
                i.total_amount AS invoice_total_amount,
                i.amount_paid AS invoice_amount_paid,
                i.balance_due AS invoice_balance_due,
                CONCAT(received_by.first_name, " ", received_by.last_name) AS received_by_name,
                CONCAT(patient.first_name, " ", patient.last_name) AS patient_name,
                patient.hospital_number,
                v.visit_number
            FROM payments p
            INNER JOIN invoices i ON i.id = p.invoice_id
            INNER JOIN patients patient ON patient.id = p.patient_id
            INNER JOIN visits v ON v.id = p.visit_id
            LEFT JOIN users received_by ON received_by.id = p.received_by
        ';
    }

    private function billingRequestBaseSelect(): string
    {
        return '
            SELECT
                br.*,
                v.visit_number,
                v.visit_status,
                p.hospital_number,
                CONCAT(p.first_name, " ", p.last_name) AS patient_name,
                d.department_name,
                bi.item_code AS suggested_item_code,
                bi.item_name AS suggested_item_name,
                bi.unit_price AS suggested_unit_price,
                charged_item.item_name AS charged_item_name,
                CONCAT(requested_by.first_name, " ", requested_by.last_name) AS requested_by_name,
                CONCAT(reviewed_by.first_name, " ", reviewed_by.last_name) AS reviewed_by_name
            FROM billing_requests br
            INNER JOIN visits v ON v.id = br.visit_id
            INNER JOIN patients p ON p.id = br.patient_id
            INNER JOIN departments d ON d.id = br.department_id
            LEFT JOIN billable_items bi ON bi.id = br.suggested_billable_item_id
            LEFT JOIN patient_charges pc ON pc.id = br.patient_charge_id
            LEFT JOIN billable_items charged_item ON charged_item.id = pc.billable_item_id
            LEFT JOIN users requested_by ON requested_by.id = br.requested_by
            LEFT JOIN users reviewed_by ON reviewed_by.id = br.reviewed_by
        ';
    }

    private function decorateCharge(array $row): array
    {
        $row['display_unit_price'] = number_format((float)($row['unit_price'] ?? 0), 2);
        $row['display_amount'] = number_format((float)($row['amount'] ?? 0), 2);
        $row['display_quantity'] = rtrim(rtrim(number_format((float)($row['quantity'] ?? 0), 2, '.', ''), '0'), '.');
        return $row;
    }

    private function decorateInvoice(array $row): array
    {
        $row['display_total_amount'] = number_format((float)($row['total_amount'] ?? 0), 2);
        $row['display_amount_paid'] = number_format((float)($row['amount_paid'] ?? 0), 2);
        $row['display_balance_due'] = number_format((float)($row['balance_due'] ?? 0), 2);
        return $row;
    }

    private function decoratePayment(array $row): array
    {
        $row['display_amount'] = number_format((float)($row['amount'] ?? 0), 2);
        return $row;
    }

    private function decorateBillingRequest(array $row): array
    {
        $row['display_quantity'] = rtrim(rtrim(number_format((float)($row['quantity'] ?? 0), 2, '.', ''), '0'), '.');
        $row['display_suggested_unit_price'] = isset($row['suggested_unit_price'])
            ? number_format((float)$row['suggested_unit_price'], 2)
            : null;
        return $row;
    }

    private function lockBillingRequest(int $requestId): ?array
    {
        if ($requestId <= 0) {
            return null;
        }

        $stmt = $this->pdo->prepare($this->billingRequestBaseSelect() . ' WHERE br.id = :id LIMIT 1 FOR UPDATE');
        $stmt->execute([':id' => $requestId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->decorateBillingRequest($row) : null;
    }

    private function canViewBillingRequestRow(array $row, array $user): bool
    {
        if ($this->permissionService->canViewBillingRequests($user)) {
            return true;
        }

        if (!$this->permissionService->canCreateBillingRequest($user)) {
            return false;
        }

        $userDepartmentId = (int)(
            $user['active_department_id']
            ?? $_SESSION['active_department_id']
            ?? $user['department_id']
            ?? 0
        );

        return (int)($row['requested_by'] ?? 0) === (int)($user['id'] ?? 0)
            || ($userDepartmentId > 0 && (int)($row['department_id'] ?? 0) === $userDepartmentId);
    }

    private function departmentExists(int $departmentId): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM departments WHERE id = :id');
        $stmt->execute([':id' => $departmentId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function audit(
        int $userId,
        int $patientId,
        int $visitId,
        string $action,
        string $description,
        ?int $departmentId = null
    ): bool {
        return $this->auditService->logPatient(
            $userId,
            $patientId,
            $visitId,
            'Billing',
            $action,
            $description,
            $departmentId,
            'INFO',
            $action
        );
    }

    private function beginTransactionIfNeeded(): bool
    {
        if ($this->pdo->inTransaction()) {
            return false;
        }

        $this->pdo->beginTransaction();
        return true;
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
