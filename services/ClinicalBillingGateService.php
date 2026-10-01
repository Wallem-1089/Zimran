<?php

declare(strict_types=1);

require_once __DIR__ . '/BillingService.php';

class ClinicalBillingGateService
{
    private BillingService $billingService;

    public function __construct(private PDO $pdo, ?BillingService $billingService = null)
    {
        $this->billingService = $billingService ?? new BillingService($pdo);
    }

    public function ensureBillingRequest(
        string $sourceModule,
        int $sourceRecordId,
        int $visitId,
        int $patientId,
        int $requestedBy,
        string $description,
        ?int $departmentId = null,
        int|array|null $suggestedBillableItemId = null
    ): array {
        if (!$this->tableExists('billing_requests') || $sourceRecordId <= 0 || $visitId <= 0 || $patientId <= 0) {
            return ['success' => true, 'skipped' => true, 'errors' => []];
        }

        $suggestedBillableItemIds = self::normalizeBillableItemIds($suggestedBillableItemId);
        $primaryBillableItemId = $suggestedBillableItemIds[0] ?? null;
        $additionalBillableItemIds = array_slice($suggestedBillableItemIds, 1);

        $existing = $this->latestBillingRequest($sourceModule, $sourceRecordId, $visitId, false);
        if ($existing !== null) {
            return ['success' => true, 'billing_request_id' => (int)$existing['id'], 'errors' => []];
        }

        try {
            $stmt = $this->pdo->prepare('
                INSERT INTO billing_requests (
                    visit_id, patient_id, department_id, source_module, source_record_id,
                    requested_by, description, suggested_billable_item_id, quantity, status, created_at, updated_at
                ) VALUES (
                    :visit_id, :patient_id, :department_id, :source_module, :source_record_id,
                    :requested_by, :description, :suggested_billable_item_id, 1.00, \'Pending\', NOW(), NOW()
                )
            ');
            $stmt->execute([
                ':visit_id' => $visitId,
                ':patient_id' => $patientId,
                ':department_id' => $departmentId,
                ':source_module' => $sourceModule,
                ':source_record_id' => $sourceRecordId,
                ':requested_by' => $requestedBy,
                ':description' => $description !== '' ? mb_substr($description, 0, 2000) : $sourceModule . ' request',
                ':suggested_billable_item_id' => $primaryBillableItemId !== null && $primaryBillableItemId > 0 ? $primaryBillableItemId : null,
            ]);
            $requestId = (int)$this->pdo->lastInsertId();
            $autoCharge = $this->billingService->autoChargeBillingRequest($requestId, ['id' => $requestedBy]);
            if (($autoCharge['success'] ?? false) !== true) {
                $message = implode(' ', (array)($autoCharge['errors'] ?? []));
                if (stripos($message, 'closed to charge creation') !== false) {
                    return [
                        'success' => true,
                        'billing_request_id' => $requestId,
                        'patient_charge_id' => 0,
                        'auto_charged' => false,
                        'auto_charge_skipped' => true,
                        'errors' => [],
                    ];
                }

                return $autoCharge;
            }

            $patientChargeIds = [];
            $primaryChargeId = (int)($autoCharge['patient_charge_id'] ?? 0);
            if ($primaryChargeId > 0) {
                $patientChargeIds[] = $primaryChargeId;
            }

            if (!empty($autoCharge['auto_charged']) && $additionalBillableItemIds !== []) {
                foreach ($additionalBillableItemIds as $additionalItemId) {
                    $additionalCharge = $this->billingService->createChargeFromBillableItem(
                        $visitId,
                        $additionalItemId,
                        1.0,
                        'BillingRequestItem',
                        null,
                        ($description !== '' ? $description : $sourceModule . ' request') . ' - additional billable item',
                        ['id' => $requestedBy],
                        false
                    );
                    if (($additionalCharge['success'] ?? false) !== true) {
                        return $additionalCharge;
                    }

                    $additionalChargeId = (int)($additionalCharge['patient_charge_id'] ?? 0);
                    if ($additionalChargeId > 0) {
                        $patientChargeIds[] = $additionalChargeId;
                    }
                }

                $stmt = $this->pdo->prepare('
                    UPDATE billing_requests
                    SET notes = CONCAT(COALESCE(notes, \'\'), :suffix),
                        updated_at = NOW()
                    WHERE id = :id
                ');
                $stmt->execute([
                    ':suffix' => ' Additional billable items charged: ' . count($additionalBillableItemIds) . '.',
                    ':id' => $requestId,
                ]);
            }

            return [
                'success' => true,
                'billing_request_id' => $requestId,
                'patient_charge_id' => $primaryChargeId,
                'patient_charge_ids' => $patientChargeIds,
                'auto_charged' => !empty($autoCharge['auto_charged']),
                'auto_charge_skipped' => !empty($autoCharge['skipped']),
                'errors' => [],
            ];
        } catch (Throwable) {
            return ['success' => false, 'errors' => ['Unable to create Accounts billing task for this clinical request.']];
        }
    }

    public static function normalizeBillableItemIds(mixed $data): array
    {
        if (is_array($data) && (array_key_exists('suggested_billable_item_ids', $data) || array_key_exists('suggested_billable_item_id', $data))) {
            $values = $data['suggested_billable_item_ids'] ?? [];
            if (!is_array($values)) {
                $values = [$values];
            }
            if (isset($data['suggested_billable_item_id']) && (int)$data['suggested_billable_item_id'] > 0) {
                array_unshift($values, $data['suggested_billable_item_id']);
            }
        } elseif (is_array($data)) {
            $values = $data;
        } else {
            $values = [$data];
        }

        $ids = [];
        foreach ($values as $value) {
            $id = (int)$value;
            if ($id > 0 && !in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    public function status(string $sourceModule, int $sourceRecordId, int $visitId, ?array $user = null): array
    {
        if ($this->isSuperAdministrator($user)) {
            return $this->response(true, 'Super Admin override', true);
        }

        if ($this->isEmergencyEncounter($visitId)) {
            return $this->response(true, 'Emergency override - treat before payment', true);
        }

        if (!$this->tableExists('billing_requests')) {
            return $this->response(true, 'Billing request workflow is not installed.', false);
        }

        $request = $this->latestBillingRequest($sourceModule, $sourceRecordId, $visitId, false);
        if ($request === null) {
            return $this->response(false, 'Awaiting Accounts billing task.', false, null);
        }

        $status = (string)($request['status'] ?? '');
        if ($status === 'Pending') {
            return $this->response(false, 'Awaiting Accounts billing.', false, $request);
        }

        if ($status === 'Cancelled') {
            return $this->response(false, 'Billing request was cancelled by Accounts.', false, $request);
        }

        if (in_array($status, ['Paid', 'Waived', 'Exempted', 'Cleared'], true)) {
            return $this->response(true, $status, false, $request);
        }

        if ($status !== 'Charged') {
            return $this->response(false, 'Awaiting Accounts clearance.', false, $request);
        }

        $balance = $this->billingService->getEncounterBalance($visitId);
        if (($balance['success'] ?? false) !== true) {
            return $this->response(false, 'Unable to verify payment status.', false, $request);
        }

        $balanceDue = (float)($balance['balance_due'] ?? 0.0);
        if ($balanceDue <= 0.00001) {
            return $this->response(true, 'Paid', false, $request, $balance);
        }

        return $this->response(false, 'Awaiting patient payment.', false, $request, $balance);
    }

    public function processingErrors(string $sourceModule, int $sourceRecordId, int $visitId, ?array $user = null): array
    {
        $status = $this->status($sourceModule, $sourceRecordId, $visitId, $user);
        if (($status['cleared'] ?? false) === true) {
            return [];
        }

        return ['Accounts clearance is required before this department can work on the request. Current billing status: ' . (string)$status['label'] . '.'];
    }

    public function cancellationErrors(string $sourceModule, int $sourceRecordId, int $visitId, ?array $user = null): array
    {
        if ($this->isSuperAdministrator($user)) {
            return [];
        }

        if (!$this->tableExists('billing_requests')) {
            return [];
        }

        $request = $this->latestBillingRequest($sourceModule, $sourceRecordId, $visitId, false);
        if ($request === null) {
            return [];
        }

        $status = (string)($request['status'] ?? '');
        if (in_array($status, ['Paid', 'Waived', 'Exempted', 'Cleared'], true)) {
            return ['This clinical request has already been cleared by Accounts and cannot be cancelled except by Super Admin.'];
        }

        if ($status !== 'Charged') {
            return [];
        }

        $balance = $this->billingService->getEncounterBalance($visitId);
        if (($balance['success'] ?? false) !== true) {
            return [];
        }

        return (float)($balance['balance_due'] ?? 0.0) <= 0.00001
            ? ['This clinical request has already been paid for and cannot be cancelled except by Super Admin.']
            : [];
    }

    private function response(bool $cleared, string $label, bool $bypass, ?array $request = null, ?array $balance = null): array
    {
        return [
            'required' => true,
            'cleared' => $cleared,
            'bypass' => $bypass,
            'label' => $label,
            'billing_request' => $request,
            'balance' => $balance,
            'balance_due' => $balance !== null ? (float)($balance['balance_due'] ?? 0.0) : null,
        ];
    }

    private function latestBillingRequest(string $sourceModule, int $sourceRecordId, int $visitId, bool $includeCancelled): ?array
    {
        $where = '
            WHERE br.visit_id = :visit_id
              AND br.source_module = :source_module
              AND br.source_record_id = :source_record_id
        ';
        if (!$includeCancelled) {
            $where .= " AND br.status <> 'Cancelled'";
        }

        $stmt = $this->pdo->prepare('
            SELECT br.*
            FROM billing_requests br
            ' . $where . '
            ORDER BY br.id DESC
            LIMIT 1
        ');
        $stmt->execute([
            ':visit_id' => $visitId,
            ':source_module' => $sourceModule,
            ':source_record_id' => $sourceRecordId,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function isSuperAdministrator(?array $user): bool
    {
        if ($user === null) {
            return false;
        }

        return in_array((string)($user['role_name'] ?? ''), ['Super Administrator', 'Super Admin'], true)
            || in_array((string)($user['department_name'] ?? ''), ['Super Administrator', 'Super Admin'], true);
    }

    private function isEmergencyEncounter(int $visitId): bool
    {
        if ($visitId <= 0) {
            return false;
        }

        try {
            $stmt = $this->pdo->prepare('
                SELECT v.visit_type, v.visit_status, d.department_name
                FROM visits v
                LEFT JOIN departments d ON d.id = v.current_department_id
                WHERE v.id = :visit_id
                LIMIT 1
            ');
            $stmt->execute([':visit_id' => $visitId]);
            $visit = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$visit) {
                return false;
            }

            return in_array('Emergency', [
                (string)($visit['visit_type'] ?? ''),
                (string)($visit['visit_status'] ?? ''),
                (string)($visit['department_name'] ?? ''),
            ], true);
        } catch (Throwable) {
            return false;
        }
    }

    private function tableExists(string $table): bool
    {
        try {
            $stmt = $this->pdo->prepare('
                SELECT COUNT(*)
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = :table
            ');
            $stmt->execute([':table' => $table]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (Throwable) {
            return false;
        }
    }
}
