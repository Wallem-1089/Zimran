<?php

declare(strict_types=1);

require_once __DIR__ . '/AuditService.php';
require_once __DIR__ . '/PatientService.php';
require_once __DIR__ . '/PermissionService.php';

class PatientRegistrationBillingService
{
    public const NORMAL_REGISTRATION_FEE = 30000.00;
    public const EMERGENCY_REGISTRATION_FEE = 50000.00;
    public const MONTHLY_RENEWAL_FEE = 20000.00;

    private AuditService $auditService;
    private PatientService $patientService;
    private PermissionService $permissionService;

    public function __construct(private PDO $pdo)
    {
        $this->auditService = new AuditService($pdo);
        $this->patientService = new PatientService($pdo);
        $this->permissionService = new PermissionService($pdo);
    }

    public function createInitialRegistration(array $data, array $user): array
    {
        $firstName = trim((string)($data['first_name'] ?? ''));
        $middleName = trim((string)($data['middle_name'] ?? ''));
        $lastName = trim((string)($data['last_name'] ?? ''));
        $phone = trim((string)($data['phone'] ?? ''));
        $registrationType = strcasecmp((string)($data['registration_type'] ?? ''), 'Emergency') === 0
            ? 'Emergency'
            : 'Normal';
        $fee = $registrationType === 'Emergency'
            ? self::EMERGENCY_REGISTRATION_FEE
            : self::NORMAL_REGISTRATION_FEE;

        $errors = [];
        if ($firstName === '') {
            $errors[] = 'First name is required.';
        }
        if ($lastName === '') {
            $errors[] = 'Last name is required.';
        }
        if ($phone === '') {
            $errors[] = 'Phone number is required.';
        }
        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare('
                INSERT INTO patients (
                    hospital_number, registration_status, registration_type, registration_fee,
                    first_name, normalized_first_name, middle_name, normalized_middle_name,
                    last_name, normalized_last_name, gender, date_of_birth, phone,
                    normalized_phone, registered_by
                ) VALUES (
                    NULL, \'PendingPayment\', :registration_type, :registration_fee,
                    :first_name, :normalized_first_name, :middle_name, :normalized_middle_name,
                    :last_name, :normalized_last_name, NULL, NULL, :phone,
                    :normalized_phone, :registered_by
                )
            ');
            $stmt->execute([
                ':registration_type' => $registrationType,
                ':registration_fee' => number_format($fee, 2, '.', ''),
                ':first_name' => $firstName,
                ':normalized_first_name' => $this->normalizeName($firstName),
                ':middle_name' => $middleName === '' ? null : $middleName,
                ':normalized_middle_name' => $this->normalizeName($middleName),
                ':last_name' => $lastName,
                ':normalized_last_name' => $this->normalizeName($lastName),
                ':phone' => $phone,
                ':normalized_phone' => $this->normalizePhone($phone),
                ':registered_by' => (int)$user['id'],
            ]);
            $patientId = (int)$this->pdo->lastInsertId();

            $requestId = $this->insertRequest(
                $patientId,
                'InitialRegistration',
                $registrationType,
                $fee,
                (int)$user['id']
            );

            $this->auditService->logPatient(
                (int)$user['id'],
                $patientId,
                null,
                'Patients',
                'PATIENT_REGISTRATION_BILLING_REQUESTED',
                'Created ' . $registrationType . ' registration billing request #' . $requestId . '.',
                null,
                'INFO',
                'PATIENT_REGISTRATION_BILLING_REQUESTED'
            );

            $this->pdo->commit();

            return [
                'success' => true,
                'patient_id' => $patientId,
                'billing_request_id' => $requestId,
                'amount' => $fee,
                'errors' => [],
            ];
        } catch (Throwable) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['success' => false, 'errors' => ['Unable to create registration billing request.']];
        }
    }

    public function ensureRenewalRequest(int $patientId, array $user): array
    {
        $existing = $this->latestOpenRequest($patientId, 'MonthlyRenewal');
        if ($existing) {
            return ['success' => true, 'request' => $existing, 'created' => false, 'errors' => []];
        }

        try {
            $requestId = $this->insertRequest(
                $patientId,
                'MonthlyRenewal',
                null,
                self::MONTHLY_RENEWAL_FEE,
                (int)$user['id']
            );

            return [
                'success' => true,
                'request' => $this->getRequestById($requestId),
                'created' => true,
                'errors' => [],
            ];
        } catch (Throwable) {
            return ['success' => false, 'request' => null, 'created' => false, 'errors' => ['Unable to create renewal billing request.']];
        }
    }

    public function listRequests(array $filters = []): array
    {
        $where = ['1 = 1'];
        $params = [];
        $patientId = (int)($filters['patient_id'] ?? 0);
        if ($patientId > 0) {
            $where[] = 'r.patient_id = :patient_id';
            $params[':patient_id'] = $patientId;
        }
        $status = trim((string)($filters['status'] ?? 'Pending'));
        if ($status !== '' && in_array($status, ['Pending', 'Paid', 'Cancelled'], true)) {
            $where[] = 'r.status = :status';
            $params[':status'] = $status;
        }
        $billingType = trim((string)($filters['billing_type'] ?? ''));
        if ($billingType !== '' && in_array($billingType, ['InitialRegistration', 'MonthlyRenewal'], true)) {
            $where[] = 'r.billing_type = :billing_type';
            $params[':billing_type'] = $billingType;
        }
        $registrationType = trim((string)($filters['registration_type'] ?? ''));
        if ($registrationType !== '' && in_array($registrationType, ['Normal', 'Emergency'], true)) {
            $where[] = 'r.registration_type = :registration_type';
            $params[':registration_type'] = $registrationType;
        }
        $name = trim((string)($filters['patient_name'] ?? ''));
        if ($name !== '') {
            $where[] = "CONCAT(p.first_name, ' ', p.last_name) LIKE :patient_name";
            $params[':patient_name'] = '%' . $name . '%';
        }
        $hospitalNumber = trim((string)($filters['hospital_number'] ?? ''));
        if ($hospitalNumber !== '') {
            $where[] = 'p.hospital_number LIKE :hospital_number';
            $params[':hospital_number'] = '%' . $hospitalNumber . '%';
        }
        $paymentReference = trim((string)($filters['payment_reference'] ?? ''));
        if ($paymentReference !== '') {
            $where[] = 'r.notes LIKE :payment_reference';
            $params[':payment_reference'] = '%' . $paymentReference . '%';
        }

        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY r.created_at DESC, r.id DESC LIMIT 200');
        $stmt->execute($params);
        return array_map([$this, 'decorate'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function listRegistrationPaymentsFiltered(array $filters = [], ?array $user = null, int $limit = 0): array
    {
        if ($user !== null && !$this->permissionService->canViewBilling($user)) {
            return [];
        }

        if ((int)($filters['visit_id'] ?? $filters['encounter_id'] ?? 0) > 0
            || trim((string)($filters['visit_number'] ?? '')) !== ''
            || trim((string)($filters['invoice_number'] ?? '')) !== ''
        ) {
            return [];
        }

        $status = trim((string)($filters['status'] ?? ''));
        if ($status !== '' && $status !== 'Paid') {
            return [];
        }

        $where = ['r.status = \'Paid\''];
        $params = [];

        $name = trim((string)($filters['patient_name'] ?? ''));
        if ($name !== '') {
            $where[] = "(CONCAT(p.first_name, ' ', p.last_name) LIKE :patient_name_full OR p.first_name LIKE :patient_name_first OR p.last_name LIKE :patient_name_last)";
            $params[':patient_name_full'] = '%' . $name . '%';
            $params[':patient_name_first'] = '%' . $name . '%';
            $params[':patient_name_last'] = '%' . $name . '%';
        }

        $hospitalNumber = trim((string)($filters['hospital_number'] ?? ''));
        if ($hospitalNumber !== '') {
            $where[] = 'p.hospital_number LIKE :hospital_number';
            $params[':hospital_number'] = '%' . $hospitalNumber . '%';
        }

        $paymentReference = trim((string)($filters['payment_reference'] ?? ''));
        if ($paymentReference !== '') {
            $where[] = 'r.notes LIKE :payment_reference';
            $params[':payment_reference'] = '%' . $paymentReference . '%';
        }

        $limitSql = '';
        if ($limit > 0) {
            $limitSql = ' LIMIT ' . min($limit, 500);
        }

        $stmt = $this->pdo->prepare(
            $this->baseSelect()
            . ' WHERE ' . implode(' AND ', $where)
            . ' ORDER BY r.cleared_at DESC, r.id DESC'
            . $limitSql
        );
        $stmt->execute($params);

        return array_map([$this, 'decorate'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getRequestById(int $requestId): ?array
    {
        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE r.id = :id LIMIT 1');
        $stmt->execute([':id' => $requestId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->decorate($row) : null;
    }

    public function markPaid(int $requestId, array $user, string $notes = ''): array
    {
        if (!$this->permissionService->canReviewBillingRequest($user)
            && !$this->permissionService->canRecordPayment($user)
        ) {
            return ['success' => false, 'errors' => ['You are not allowed to clear registration payments.']];
        }

        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare('SELECT * FROM patient_registration_billing_requests WHERE id = :id FOR UPDATE');
            $stmt->execute([':id' => $requestId]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$request) {
                $this->pdo->rollBack();
                return ['success' => false, 'errors' => ['Registration billing request not found.']];
            }
            if ((string)$request['status'] !== 'Pending') {
                $this->pdo->rollBack();
                return ['success' => false, 'errors' => ['Only pending registration billing requests can be cleared.']];
            }

            $update = $this->pdo->prepare('
                UPDATE patient_registration_billing_requests
                SET status = \'Paid\',
                    cleared_by = :cleared_by,
                    cleared_at = NOW(),
                    notes = :notes,
                    updated_at = NOW()
                WHERE id = :id
                  AND status = \'Pending\'
            ');
            $update->execute([
                ':cleared_by' => (int)$user['id'],
                ':notes' => trim($notes) === '' ? null : trim($notes),
                ':id' => $requestId,
            ]);

            if ((string)$request['billing_type'] === 'InitialRegistration') {
                $result = $this->patientService->activateRegistrationAfterPayment(
                    (int)$request['patient_id'],
                    (int)$user['id']
                );
            } else {
                $result = $this->patientService->renewRegistrationAfterPayment(
                    (int)$request['patient_id'],
                    (int)$user['id']
                );
            }
            if (empty($result['success'])) {
                throw new RuntimeException('Unable to activate registration.');
            }

            $this->pdo->commit();
            return ['success' => true, 'patient_id' => (int)$request['patient_id'], 'errors' => []];
        } catch (Throwable) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['success' => false, 'errors' => ['Unable to clear registration payment.']];
        }
    }

    private function insertRequest(
        int $patientId,
        string $billingType,
        ?string $registrationType,
        float $amount,
        int $requestedBy
    ): int {
        $stmt = $this->pdo->prepare('
            INSERT INTO patient_registration_billing_requests (
                patient_id, billing_type, registration_type, amount, status, requested_by, requested_at
            ) VALUES (
                :patient_id, :billing_type, :registration_type, :amount, \'Pending\', :requested_by, NOW()
            )
        ');
        $stmt->execute([
            ':patient_id' => $patientId,
            ':billing_type' => $billingType,
            ':registration_type' => $registrationType,
            ':amount' => number_format($amount, 2, '.', ''),
            ':requested_by' => $requestedBy,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    private function latestOpenRequest(int $patientId, string $billingType): ?array
    {
        $stmt = $this->pdo->prepare($this->baseSelect() . '
            WHERE r.patient_id = :patient_id
              AND r.billing_type = :billing_type
              AND r.status = \'Pending\'
            ORDER BY r.id DESC
            LIMIT 1
        ');
        $stmt->execute([':patient_id' => $patientId, ':billing_type' => $billingType]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->decorate($row) : null;
    }

    private function baseSelect(): string
    {
        return '
            SELECT r.*,
                   p.hospital_number,
                   p.first_name,
                   p.middle_name,
                   p.last_name,
                   p.phone,
                   p.registration_status,
                   p.registration_valid_until,
                   CONCAT(p.first_name, " ", p.last_name) AS patient_name,
                   CONCAT(requested_by.first_name, " ", requested_by.last_name) AS requested_by_name,
                   CONCAT(cleared_by.first_name, " ", cleared_by.last_name) AS cleared_by_name
            FROM patient_registration_billing_requests r
            INNER JOIN patients p ON p.id = r.patient_id
            LEFT JOIN users requested_by ON requested_by.id = r.requested_by
            LEFT JOIN users cleared_by ON cleared_by.id = r.cleared_by
        ';
    }

    private function decorate(array $row): array
    {
        $row['display_amount'] = number_format((float)($row['amount'] ?? 0), 2);
        return $row;
    }

    private function normalizeName(string $value): string
    {
        return preg_replace('/\s+/', ' ', mb_strtolower(trim($value))) ?? '';
    }

    private function normalizePhone(string $value): string
    {
        return preg_replace('/\D+/', '', trim($value)) ?? '';
    }
}
