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
    public const FAMILY_CARD_REGISTRATION_FEE = 50000.00;
    public const FAMILY_CARD_RENEWAL_FEE = 20000.00;

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
        $registrationPlan = trim((string)($data['registration_plan'] ?? 'Normal'));
        if (!in_array($registrationPlan, ['Normal', 'FamilyCard', 'ExistingFamilyCard', 'Legacy'], true)) {
            $registrationPlan = 'Normal';
        }
        if ($registrationType === 'Emergency') {
            $registrationPlan = 'Normal';
        }
        $patientRegistrationType = $registrationPlan === 'Legacy' ? 'Legacy' : $registrationType;
        $fee = $registrationPlan === 'Legacy'
            ? 0.00
            : ($registrationPlan === 'FamilyCard'
            ? self::FAMILY_CARD_REGISTRATION_FEE
            : ($registrationType === 'Emergency'
            ? self::EMERGENCY_REGISTRATION_FEE
            : self::NORMAL_REGISTRATION_FEE));

        $errors = [];
        if ($registrationType === 'Emergency') {
            if (!$this->permissionService->canRegisterEmergencyPatient($user)) {
                $errors[] = 'You are not allowed to create emergency registration billing requests.';
            }
        } elseif (!$this->permissionService->canRegisterPatient($user)) {
            $errors[] = 'You are not allowed to create normal registration billing requests.';
        }
        if ($firstName === '') {
            $errors[] = 'First name is required.';
        }
        if ($lastName === '') {
            $errors[] = 'Last name is required.';
        }
        if ($phone === '') {
            $errors[] = 'Phone number is required.';
        }
        if ($registrationPlan === 'ExistingFamilyCard') {
            $familyCardNumber = trim((string)($data['family_card_number'] ?? ''));
            if ($familyCardNumber === '') {
                $errors[] = 'Family Card number is required.';
            } elseif (!$this->getActiveFamilyCardByNumber($familyCardNumber)) {
                $errors[] = 'Family Card was not found or is not currently active.';
            }
        }
        if ($registrationPlan === 'Legacy') {
            $legacyHospitalNumber = trim((string)($data['legacy_hospital_number'] ?? ''));
            $legacyNotes = trim((string)($data['legacy_notes'] ?? ''));
            $legacyFirstSeenDate = trim((string)($data['legacy_first_seen_date'] ?? ''));
            if ($legacyHospitalNumber === '' && $legacyNotes === '') {
                $errors[] = 'Enter the old hospital/card number or a legacy registration note.';
            }
            if ($legacyFirstSeenDate !== '') {
                $date = DateTimeImmutable::createFromFormat('!Y-m-d', $legacyFirstSeenDate);
                if (!$date || $date->format('Y-m-d') !== $legacyFirstSeenDate || $date > new DateTimeImmutable('today')) {
                    $errors[] = 'Select a valid legacy first-seen date.';
                }
            }
        }
        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare('
                INSERT INTO patients (
                    hospital_number, registration_status, registration_type, registration_fee,
                    legacy_hospital_number, legacy_first_seen_date, legacy_notes,
                    first_name, normalized_first_name, middle_name, normalized_middle_name,
                    last_name, normalized_last_name, gender, date_of_birth, phone,
                    normalized_phone, registered_by
                ) VALUES (
                    NULL, \'PendingPayment\', :registration_type, :registration_fee,
                    :legacy_hospital_number, :legacy_first_seen_date, :legacy_notes,
                    :first_name, :normalized_first_name, :middle_name, :normalized_middle_name,
                    :last_name, :normalized_last_name, NULL, NULL, :phone,
                    :normalized_phone, :registered_by
                )
            ');
            $stmt->execute([
                ':registration_type' => $patientRegistrationType,
                ':registration_fee' => number_format($fee, 2, '.', ''),
                ':legacy_hospital_number' => $registrationPlan === 'Legacy' ? (trim((string)($data['legacy_hospital_number'] ?? '')) ?: null) : null,
                ':legacy_first_seen_date' => $registrationPlan === 'Legacy' ? (trim((string)($data['legacy_first_seen_date'] ?? '')) ?: null) : null,
                ':legacy_notes' => $registrationPlan === 'Legacy' ? (trim((string)($data['legacy_notes'] ?? '')) ?: null) : null,
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

            if ($registrationPlan === 'Legacy') {
                $activateResult = $this->patientService->activateLegacyRegistration(
                    $patientId,
                    (int)$user['id']
                );
                if (empty($activateResult['success'])) {
                    throw new RuntimeException('Unable to activate legacy patient registration.');
                }

                $this->auditService->logPatient(
                    (int)$user['id'],
                    $patientId,
                    null,
                    'Patients',
                    'LEGACY_PATIENT_IMPORTED',
                    'Imported existing hospital patient without initial registration billing.',
                    null,
                    'INFO',
                    'LEGACY_PATIENT_IMPORTED'
                );

                $this->pdo->commit();

                return [
                    'success' => true,
                    'patient_id' => $patientId,
                    'billing_request_id' => 0,
                    'amount' => 0.00,
                    'legacy_import' => true,
                    'errors' => [],
                ];
            }

            if ($registrationPlan === 'ExistingFamilyCard') {
                $familyCard = $this->getActiveFamilyCardByNumber(trim((string)($data['family_card_number'] ?? '')));
                if (!$familyCard) {
                    throw new RuntimeException('Family Card was not found or is not currently active.');
                }

                $activateResult = $this->patientService->activateRegistrationUnderFamilyCard(
                    $patientId,
                    (int)$user['id']
                );
                if (empty($activateResult['success'])) {
                    throw new RuntimeException('Unable to activate patient under Family Card.');
                }

                $this->addFamilyCardMember(
                    (int)$familyCard['id'],
                    $patientId,
                    (int)$user['id'],
                    trim((string)($data['family_relationship'] ?? 'Dependent')),
                    trim((string)($data['family_member_type'] ?? 'Dependent')) ?: 'Dependent'
                );

                $this->auditService->logPatient(
                    (int)$user['id'],
                    $patientId,
                    null,
                    'Patients',
                    'PATIENT_ADDED_TO_FAMILY_CARD',
                    'Registered patient under existing Family Card ' . (string)$familyCard['card_number'] . '.',
                    null,
                    'INFO',
                    'PATIENT_ADDED_TO_FAMILY_CARD'
                );

                $this->pdo->commit();

                return [
                    'success' => true,
                    'patient_id' => $patientId,
                    'billing_request_id' => 0,
                    'amount' => 0.00,
                    'family_card_id' => (int)$familyCard['id'],
                    'errors' => [],
                ];
            }

            $familyCardId = null;
            if ($registrationPlan === 'FamilyCard') {
                $familyCardId = $this->createPendingFamilyCard(
                    $patientId,
                    $firstName . ' ' . $lastName,
                    $phone,
                    (int)$user['id']
                );
            }

            $requestId = $this->insertRequest(
                $patientId,
                $registrationPlan === 'FamilyCard' ? 'FamilyCardActivation' : 'InitialRegistration',
                $registrationPlan === 'FamilyCard' ? null : $registrationType,
                $fee,
                (int)$user['id'],
                $familyCardId
            );

            $this->auditService->logPatient(
                (int)$user['id'],
                $patientId,
                null,
                'Patients',
                'PATIENT_REGISTRATION_BILLING_REQUESTED',
                'Created ' . ($registrationPlan === 'FamilyCard' ? 'Family Card' : $registrationType) . ' registration billing request #' . $requestId . '.',
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

    public function ensureFamilyCardRenewalRequest(int $familyCardId, array $user): array
    {
        if ($familyCardId <= 0) {
            return ['success' => false, 'request' => null, 'created' => false, 'errors' => ['Family Card was not selected.']];
        }

        $existing = $this->latestOpenFamilyCardRequest($familyCardId, 'FamilyCardRenewal');
        if ($existing) {
            return ['success' => true, 'request' => $existing, 'created' => false, 'errors' => []];
        }

        try {
            $patientId = $this->familyCardPrincipalPatientId($familyCardId);
            $requestId = $this->insertRequest(
                $patientId,
                'FamilyCardRenewal',
                null,
                self::FAMILY_CARD_RENEWAL_FEE,
                (int)$user['id'],
                $familyCardId
            );

            return [
                'success' => true,
                'request' => $this->getRequestById($requestId),
                'created' => true,
                'errors' => [],
            ];
        } catch (Throwable) {
            return ['success' => false, 'request' => null, 'created' => false, 'errors' => ['Unable to create Family Card renewal billing request.']];
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
        if ($billingType !== '' && in_array($billingType, ['InitialRegistration', 'MonthlyRenewal', 'FamilyCardActivation', 'FamilyCardRenewal'], true)) {
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
            } elseif ((string)$request['billing_type'] === 'MonthlyRenewal') {
                $result = $this->patientService->renewRegistrationAfterPayment(
                    (int)$request['patient_id'],
                    (int)$user['id']
                );
            } elseif ((string)$request['billing_type'] === 'FamilyCardActivation') {
                $result = $this->activateFamilyCardAfterPayment($request, (int)$user['id']);
            } else {
                $result = $this->renewFamilyCardAfterPayment($request, (int)$user['id']);
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
        int $requestedBy,
        ?int $familyCardId = null
    ): int {
        $stmt = $this->pdo->prepare('
            INSERT INTO patient_registration_billing_requests (
                patient_id, family_card_id, billing_type, registration_type, amount, status, requested_by, requested_at
            ) VALUES (
                :patient_id, :family_card_id, :billing_type, :registration_type, :amount, \'Pending\', :requested_by, NOW()
            )
        ');
        $stmt->execute([
            ':patient_id' => $patientId,
            ':family_card_id' => $familyCardId,
            ':billing_type' => $billingType,
            ':registration_type' => $registrationType,
            ':amount' => number_format($amount, 2, '.', ''),
            ':requested_by' => $requestedBy,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    private function createPendingFamilyCard(int $patientId, string $contactName, string $phone, int $createdBy): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO family_cards (
                card_number, principal_patient_id, primary_contact_name, phone,
                status, activation_fee, renewal_fee, created_by, created_at, updated_at
            ) VALUES (
                :card_number, :patient_id, :contact_name, :phone,
                \'PendingPayment\', :activation_fee, :renewal_fee, :created_by, NOW(), NOW()
            )
        ');
        $stmt->execute([
            ':card_number' => $this->generateFamilyCardNumber(),
            ':patient_id' => $patientId,
            ':contact_name' => trim($contactName),
            ':phone' => $phone,
            ':activation_fee' => number_format(self::FAMILY_CARD_REGISTRATION_FEE, 2, '.', ''),
            ':renewal_fee' => number_format(self::FAMILY_CARD_RENEWAL_FEE, 2, '.', ''),
            ':created_by' => $createdBy,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    private function generateFamilyCardNumber(): string
    {
        $prefix = 'FC-' . date('Y') . '-';
        $stmt = $this->pdo->prepare('
            SELECT card_number
            FROM family_cards
            WHERE card_number LIKE :prefix
            ORDER BY id DESC
            LIMIT 1
        ');
        $stmt->execute([':prefix' => $prefix . '%']);
        $last = (string)($stmt->fetchColumn() ?: '');
        $next = 1;
        if (preg_match('/(\d+)$/', $last, $matches) === 1) {
            $next = (int)$matches[1] + 1;
        }
        return $prefix . str_pad((string)$next, 6, '0', STR_PAD_LEFT);
    }

    private function addFamilyCardMember(
        int $familyCardId,
        int $patientId,
        int $addedBy,
        string $relationship,
        string $memberType
    ): void {
        $allowedTypes = ['Principal', 'Spouse', 'Child', 'Dependent', 'Other'];
        if (!in_array($memberType, $allowedTypes, true)) {
            $memberType = 'Dependent';
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO family_card_members (
                family_card_id, patient_id, relationship, member_type, status, added_by, added_at
            ) VALUES (
                :family_card_id, :patient_id, :relationship, :member_type, \'Active\', :added_by, NOW()
            )
            ON DUPLICATE KEY UPDATE
                family_card_id = VALUES(family_card_id),
                relationship = VALUES(relationship),
                member_type = VALUES(member_type),
                status = \'Active\',
                added_by = VALUES(added_by),
                added_at = NOW(),
                removed_by = NULL,
                removed_at = NULL
        ');
        $stmt->execute([
            ':family_card_id' => $familyCardId,
            ':patient_id' => $patientId,
            ':relationship' => $relationship === '' ? null : $relationship,
            ':member_type' => $memberType,
            ':added_by' => $addedBy,
        ]);
    }

    private function getActiveFamilyCardByNumber(string $cardNumber): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT *
            FROM family_cards
            WHERE card_number = :card_number
              AND status = \'Active\'
              AND valid_until >= CURDATE()
            LIMIT 1
        ');
        $stmt->execute([':card_number' => trim($cardNumber)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function activateFamilyCardAfterPayment(array $request, int $clearedBy): array
    {
        $familyCardId = (int)($request['family_card_id'] ?? 0);
        if ($familyCardId <= 0) {
            return ['success' => false, 'errors' => ['Family Card was not linked to this request.']];
        }

        $patientResult = $this->patientService->activateRegistrationUnderFamilyCard((int)$request['patient_id'], $clearedBy);
        if (empty($patientResult['success'])) {
            return $patientResult;
        }

        $stmt = $this->pdo->prepare('
            UPDATE family_cards
            SET status = \'Active\',
                activated_at = COALESCE(activated_at, NOW()),
                valid_until = DATE_ADD(CURDATE(), INTERVAL 30 DAY),
                updated_by = :updated_by,
                updated_at = NOW()
            WHERE id = :id
        ');
        $stmt->execute([
            ':updated_by' => $clearedBy,
            ':id' => $familyCardId,
        ]);

        $this->addFamilyCardMember($familyCardId, (int)$request['patient_id'], $clearedBy, 'Principal', 'Principal');

        return ['success' => true, 'errors' => []];
    }

    private function renewFamilyCardAfterPayment(array $request, int $clearedBy): array
    {
        $familyCardId = (int)($request['family_card_id'] ?? 0);
        if ($familyCardId <= 0) {
            return ['success' => false, 'errors' => ['Family Card was not linked to this request.']];
        }

        $stmt = $this->pdo->prepare('
            UPDATE family_cards
            SET status = \'Active\',
                valid_until = DATE_ADD(
                    GREATEST(COALESCE(valid_until, CURDATE()), CURDATE()),
                    INTERVAL 30 DAY
                ),
                updated_by = :updated_by,
                updated_at = NOW()
            WHERE id = :id
        ');
        $stmt->execute([
            ':updated_by' => $clearedBy,
            ':id' => $familyCardId,
        ]);

        return ['success' => true, 'errors' => []];
    }

    private function familyCardPrincipalPatientId(int $familyCardId): int
    {
        $stmt = $this->pdo->prepare('SELECT COALESCE(principal_patient_id, 0) FROM family_cards WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $familyCardId]);
        return (int)$stmt->fetchColumn();
    }

    private function latestOpenFamilyCardRequest(int $familyCardId, string $billingType): ?array
    {
        $stmt = $this->pdo->prepare($this->baseSelect() . '
            WHERE r.family_card_id = :family_card_id
              AND r.billing_type = :billing_type
              AND r.status = \'Pending\'
            ORDER BY r.id DESC
            LIMIT 1
        ');
        $stmt->execute([':family_card_id' => $familyCardId, ':billing_type' => $billingType]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->decorate($row) : null;
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
                   r.family_card_id,
                   fc.card_number,
                   fc.valid_until AS family_card_valid_until,
                   fc.status AS family_card_status,
                   CONCAT(p.first_name, " ", p.last_name) AS patient_name,
                   CONCAT(requested_by.first_name, " ", requested_by.last_name) AS requested_by_name,
                   CONCAT(cleared_by.first_name, " ", cleared_by.last_name) AS cleared_by_name
            FROM patient_registration_billing_requests r
            INNER JOIN patients p ON p.id = r.patient_id
            LEFT JOIN family_cards fc ON fc.id = r.family_card_id
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
