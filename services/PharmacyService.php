<?php

declare(strict_types=1);

require_once __DIR__ . '/AuditService.php';
require_once __DIR__ . '/ClinicalSafetyService.php';
require_once __DIR__ . '/EncounterEventService.php';
require_once __DIR__ . '/PermissionService.php';
require_once __DIR__ . '/StoreService.php';
require_once __DIR__ . '/VisitService.php';

class PharmacyService
{
    private AuditService $auditService;

    private ClinicalSafetyService $clinicalSafetyService;

    private EncounterEventService $eventService;

    private PermissionService $permissionService;

    private StoreService $storeService;

    private VisitService $visitService;

    private ?int $pharmacyDepartmentId = null;

    public function __construct(
        private PDO $pdo,
        ?StoreService $storeService = null,
        ?ClinicalSafetyService $clinicalSafetyService = null,
        ?AuditService $auditService = null,
        ?EncounterEventService $eventService = null,
        ?PermissionService $permissionService = null,
        ?VisitService $visitService = null
    ) {
        $this->storeService = $storeService ?? new StoreService($pdo);
        $this->clinicalSafetyService = $clinicalSafetyService ?? new ClinicalSafetyService($pdo);
        $this->auditService = $auditService ?? new AuditService($pdo);
        $this->eventService = $eventService ?? new EncounterEventService($pdo);
        $this->permissionService = $permissionService ?? new PermissionService($pdo);
        $this->visitService = $visitService ?? new VisitService($pdo);
    }

    public function createPrescription(array $data, array $user): array
    {
        try {
            $prepared = $this->preparePrescription($data);
            if ($prepared['errors'] !== []) {
                return $this->failure($prepared['errors']);
            }

            $visit = $this->requireVisit($prepared['data']['visit_id']);
            if (!$visit) {
                return $this->failure(['Encounter not found.']);
            }

            if ((int)$visit['patient_id'] !== (int)$prepared['data']['patient_id']) {
                return $this->failure(['Patient and encounter do not match.']);
            }

            if (!$this->permissionService->canCreatePrescription($visit, $user, $prepared['data']['prescription_source'])) {
                return $this->failure(['You do not have permission to create this prescription.']);
            }

            $inventoryItem = $this->resolveInventoryItemForPrescription(
                $prepared['data']['inventory_item_id'],
                $prepared['data']['medication_name'],
                $user
            );

            if ((int)($prepared['data']['inventory_item_id'] ?? 0) > 0 && $inventoryItem === null) {
                return $this->failure(['Selected inventory item is invalid or inactive.']);
            }

            if ($inventoryItem !== null) {
                $prepared['data']['inventory_item_id'] = (int)$inventoryItem['id'];
                if ($prepared['data']['medication_name'] === '') {
                    $prepared['data']['medication_name'] = (string)$inventoryItem['item_name'];
                }
            }

            if ($prepared['data']['medication_name'] === '') {
                return $this->failure(['Medication is required.']);
            }

            $departmentId = $this->pharmacyDepartmentId();
            if ($departmentId <= 0) {
                return $this->failure(['Pharmacy department is not configured.']);
            }

            $prescribedBy = null;
            if ($prepared['data']['prescription_source'] === 'Clinical' && ($user['role_name'] ?? '') === 'Doctor') {
                $prescribedBy = (int)$user['id'];
            }

            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare('
                INSERT INTO prescriptions (
                    visit_id,
                    patient_id,
                    prescribed_by,
                    department_id,
                    prescription_source,
                    inventory_item_id,
                    medication_name,
                    dosage,
                    frequency,
                    duration,
                    quantity,
                    instructions,
                    status,
                    created_by,
                    updated_by,
                    created_at,
                    updated_at,
                    dispensed_at
                ) VALUES (
                    :visit_id,
                    :patient_id,
                    :prescribed_by,
                    :department_id,
                    :prescription_source,
                    :inventory_item_id,
                    :medication_name,
                    :dosage,
                    :frequency,
                    :duration,
                    :quantity,
                    :instructions,
                    \'Prescribed\',
                    :created_by,
                    NULL,
                    NOW(),
                    NOW(),
                    NULL
                )
            ');
            $stmt->execute([
                ':visit_id' => (int)$prepared['data']['visit_id'],
                ':patient_id' => (int)$prepared['data']['patient_id'],
                ':prescribed_by' => $prescribedBy,
                ':department_id' => $departmentId,
                ':prescription_source' => $prepared['data']['prescription_source'],
                ':inventory_item_id' => $prepared['data']['inventory_item_id'],
                ':medication_name' => $prepared['data']['medication_name'],
                ':dosage' => $prepared['data']['dosage'],
                ':frequency' => $prepared['data']['frequency'],
                ':duration' => $prepared['data']['duration'],
                ':quantity' => $prepared['data']['quantity'],
                ':instructions' => $prepared['data']['instructions'],
                ':created_by' => (int)$user['id'],
            ]);
            $prescriptionId = (int)$this->pdo->lastInsertId();

            $this->audit(
                (int)$user['id'],
                (int)$prepared['data']['patient_id'],
                (int)$prepared['data']['visit_id'],
                'PRESCRIPTION_CREATED',
                'Created prescription #' . $prescriptionId . '.',
                $departmentId
            );

            $this->recordEvent(
                (int)$prepared['data']['visit_id'],
                'PRESCRIPTION_CREATED',
                'Prescription Created',
                'A prescription was created.',
                $departmentId,
                (int)$user['id']
            );

            $this->pdo->commit();

            return [
                'success' => true,
                'prescription_id' => $prescriptionId,
                'errors' => [],
            ];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to create prescription.']);
        }
    }

    public function getPrescriptionById(int $prescriptionId, ?array $user = null): ?array
    {
        $row = $this->fetchPrescriptionRow($prescriptionId);
        if (!$row) {
            return null;
        }

        if ($user !== null && !$this->permissionService->canViewPharmacy((int)$row['patient_id'], $user)) {
            return null;
        }

        return $row;
    }

    public function listByVisit(int $visitId, ?array $user = null): array
    {
        if ($visitId <= 0) {
            return [];
        }

        $visit = $this->requireVisit($visitId);
        if (!$visit) {
            return [];
        }

        if ($user !== null && !$this->permissionService->canViewPharmacy((int)$visit['patient_id'], $user)) {
            return [];
        }

        $stmt = $this->pdo->prepare($this->buildBaseSelect() . '
            WHERE p.visit_id = :visit_id
            ORDER BY p.created_at DESC, p.id DESC
        ');
        $stmt->execute([
            ':visit_id' => $visitId,
            ':pharmacy_department_id' => $this->pharmacyDepartmentId(),
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listByPatient(int $patientId, ?array $user = null): array
    {
        if ($patientId <= 0) {
            return [];
        }

        if ($user !== null && !$this->permissionService->canViewPharmacy($patientId, $user)) {
            return [];
        }

        $stmt = $this->pdo->prepare($this->buildBaseSelect() . '
            WHERE p.patient_id = :patient_id
            ORDER BY p.created_at DESC, p.id DESC
        ');
        $stmt->execute([
            ':patient_id' => $patientId,
            ':pharmacy_department_id' => $this->pharmacyDepartmentId(),
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listWorklist(?array $user = null, array $filters = []): array
    {
        if ($user !== null && !$this->permissionService->canViewPharmacyWorklist($user)) {
            return [];
        }

        $status = trim((string)($filters['status'] ?? 'Prescribed'));
        $where = 'WHERE p.status <> \'Cancelled\'';
        $params = [
            ':pharmacy_department_id' => $this->pharmacyDepartmentId(),
        ];

        if ($status !== '') {
            $where .= ' AND p.status = :status';
            $params[':status'] = $status;
        }

        $stmt = $this->pdo->prepare($this->buildBaseSelect() . ' ' . $where . ' ORDER BY p.created_at DESC, p.id DESC');
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updatePrescription(int $prescriptionId, array $data, array $user): array
    {
        try {
            $prepared = $this->preparePrescription($data, true);
            if ($prepared['errors'] !== []) {
                return $this->failure($prepared['errors']);
            }

            $this->pdo->beginTransaction();
            $current = $this->fetchPrescriptionForUpdate($prescriptionId);
            if (!$current) {
                $this->rollback();
                return $this->failure(['Prescription not found.']);
            }

            if ((string)$current['status'] !== 'Prescribed') {
                $this->rollback();
                return $this->failure(['Dispensed or cancelled prescriptions are read-only.']);
            }

            $visit = $this->requireVisit((int)$current['visit_id']);
            if (!$visit) {
                $this->rollback();
                return $this->failure(['Encounter not found.']);
            }

            if (!$this->permissionService->canEditPrescription($visit, $user, (string)$current['prescription_source'])) {
                $this->rollback();
                return $this->failure(['You do not have permission to edit this prescription.']);
            }

            if ((int)$visit['patient_id'] !== (int)$current['patient_id']) {
                $this->rollback();
                return $this->failure(['Patient and encounter do not match.']);
            }

            $inventoryItem = $this->resolveInventoryItemForPrescription(
                $prepared['data']['inventory_item_id'],
                $prepared['data']['medication_name'],
                $user
            );

            if ((int)($prepared['data']['inventory_item_id'] ?? 0) > 0 && $inventoryItem === null) {
                $this->rollback();
                return $this->failure(['Selected inventory item is invalid or inactive.']);
            }

            if ($inventoryItem !== null) {
                $prepared['data']['inventory_item_id'] = (int)$inventoryItem['id'];
                if ($prepared['data']['medication_name'] === '') {
                    $prepared['data']['medication_name'] = (string)$inventoryItem['item_name'];
                }
            }

            if ($prepared['data']['medication_name'] === '') {
                $this->rollback();
                return $this->failure(['Medication is required.']);
            }

            $stmt = $this->pdo->prepare('
                UPDATE prescriptions
                SET inventory_item_id = :inventory_item_id,
                    medication_name = :medication_name,
                    dosage = :dosage,
                    frequency = :frequency,
                    duration = :duration,
                    quantity = :quantity,
                    instructions = :instructions,
                    updated_by = :updated_by,
                    updated_at = NOW()
                WHERE id = :id
            ');
            $stmt->execute([
                ':inventory_item_id' => $prepared['data']['inventory_item_id'],
                ':medication_name' => $prepared['data']['medication_name'],
                ':dosage' => $prepared['data']['dosage'],
                ':frequency' => $prepared['data']['frequency'],
                ':duration' => $prepared['data']['duration'],
                ':quantity' => $prepared['data']['quantity'],
                ':instructions' => $prepared['data']['instructions'],
                ':updated_by' => (int)$user['id'],
                ':id' => $prescriptionId,
            ]);

            $this->audit(
                (int)$user['id'],
                (int)$current['patient_id'],
                (int)$current['visit_id'],
                'PRESCRIPTION_UPDATED',
                'Updated prescription #' . $prescriptionId . '.',
                $this->pharmacyDepartmentId()
            );

            $this->pdo->commit();

            return [
                'success' => true,
                'prescription_id' => $prescriptionId,
                'errors' => [],
            ];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to update prescription.']);
        }
    }

    public function dispense(int $prescriptionId, array $data, array $user): array
    {
        try {
            $quantity = (float)($data['quantity_dispensed'] ?? 0);
            $notes = trim((string)($data['dispensing_notes'] ?? ''));

            $this->pdo->beginTransaction();
            $current = $this->fetchPrescriptionForUpdate($prescriptionId);
            if (!$current) {
                $this->rollback();
                return $this->failure(['Prescription not found.']);
            }

            if ((string)$current['status'] !== 'Prescribed') {
                $this->rollback();
                return $this->failure(['Only prescribed medications can be dispensed.']);
            }

            $visit = $this->requireVisit((int)$current['visit_id']);
            if (!$visit) {
                $this->rollback();
                return $this->failure(['Encounter not found.']);
            }

            if ((int)$visit['patient_id'] !== (int)$current['patient_id']) {
                $this->rollback();
                return $this->failure(['Patient and encounter do not match.']);
            }

            if (!$this->permissionService->canDispensePrescription($visit, $user)) {
                $this->rollback();
                return $this->failure(['You do not have permission to dispense this prescription.']);
            }

            if ((string)($visit['visit_status'] ?? '') === 'Completed'
                || (string)($visit['visit_status'] ?? '') === 'Cancelled'
            ) {
                $this->rollback();
                return $this->failure(['Completed or cancelled encounters are read-only.']);
            }

            $inventoryItem = $this->resolveInventoryItemForPrescription(
                (int)($current['inventory_item_id'] ?? 0),
                (string)$current['medication_name'],
                $user
            );
            if ($inventoryItem === null) {
                $this->rollback();
                return $this->failure(['A linked inventory item is required before dispensing.']);
            }

            $prescribedQuantity = (float)$current['quantity'];
            if ($quantity <= 0) {
                $quantity = $prescribedQuantity;
            }

            if (abs($quantity - $prescribedQuantity) > 0.00001) {
                $this->rollback();
                return $this->failure(['Partial dispensing is not supported yet.']);
            }

            $consume = $this->storeService->consumeDepartmentStock([
                'inventory_item_id' => (int)$inventoryItem['id'],
                'department_id' => $this->pharmacyDepartmentId(),
                'quantity' => $quantity,
                'reference' => 'PRESCRIPTION #' . $prescriptionId,
                'remarks' => $notes !== '' ? $notes : 'Dispensed prescription #' . $prescriptionId . '.',
            ], $user);

            if (($consume['success'] ?? false) !== true) {
                $this->rollback();
                return [
                    'success' => false,
                    'errors' => $consume['errors'] ?? ['Unable to dispense prescription.'],
                ];
            }

            $stmt = $this->pdo->prepare('
                INSERT INTO pharmacy_dispensing (
                    prescription_id,
                    visit_id,
                    patient_id,
                    inventory_item_id,
                    quantity_dispensed,
                    dispensing_notes,
                    dispensed_by,
                    created_at
                ) VALUES (
                    :prescription_id,
                    :visit_id,
                    :patient_id,
                    :inventory_item_id,
                    :quantity_dispensed,
                    :dispensing_notes,
                    :dispensed_by,
                    NOW()
                )
            ');
            $stmt->execute([
                ':prescription_id' => $prescriptionId,
                ':visit_id' => (int)$current['visit_id'],
                ':patient_id' => (int)$current['patient_id'],
                ':inventory_item_id' => (int)$inventoryItem['id'],
                ':quantity_dispensed' => $quantity,
                ':dispensing_notes' => $notes === '' ? null : $notes,
                ':dispensed_by' => (int)$user['id'],
            ]);
            $dispensingId = (int)$this->pdo->lastInsertId();

            $stmt = $this->pdo->prepare('
                UPDATE prescriptions
                SET status = \'Dispensed\',
                    updated_by = :updated_by,
                    updated_at = NOW(),
                    dispensed_at = NOW()
                WHERE id = :id
            ');
            $stmt->execute([
                ':updated_by' => (int)$user['id'],
                ':id' => $prescriptionId,
            ]);

            $this->audit(
                (int)$user['id'],
                (int)$current['patient_id'],
                (int)$current['visit_id'],
                'PRESCRIPTION_DISPENSED',
                'Dispensed prescription #' . $prescriptionId . '.',
                $this->pharmacyDepartmentId()
            );

            $this->recordEvent(
                (int)$current['visit_id'],
                'PRESCRIPTION_DISPENSED',
                'Prescription Dispensed',
                'A prescription was dispensed.',
                $this->pharmacyDepartmentId(),
                (int)$user['id']
            );

            $this->pdo->commit();

            return [
                'success' => true,
                'dispensing_id' => $dispensingId,
                'prescription_id' => $prescriptionId,
                'errors' => [],
            ];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to dispense prescription.']);
        }
    }

    public function cancelPrescription(int $prescriptionId, array $user, ?string $reason = null): array
    {
        try {
            $this->pdo->beginTransaction();
            $current = $this->fetchPrescriptionForUpdate($prescriptionId);
            if (!$current) {
                $this->rollback();
                return $this->failure(['Prescription not found.']);
            }

            if ((string)$current['status'] === 'Dispensed') {
                $this->rollback();
                return $this->failure(['Dispensed prescriptions cannot be cancelled.']);
            }

            $visit = $this->requireVisit((int)$current['visit_id']);
            if (!$visit) {
                $this->rollback();
                return $this->failure(['Encounter not found.']);
            }

            if (!$this->permissionService->canEditPrescription($visit, $user, (string)$current['prescription_source'])) {
                $this->rollback();
                return $this->failure(['You do not have permission to cancel this prescription.']);
            }

            $stmt = $this->pdo->prepare('
                UPDATE prescriptions
                SET status = \'Cancelled\',
                    updated_by = :updated_by,
                    updated_at = NOW()
                WHERE id = :id
            ');
            $stmt->execute([
                ':updated_by' => (int)$user['id'],
                ':id' => $prescriptionId,
            ]);

            $this->audit(
                (int)$user['id'],
                (int)$current['patient_id'],
                (int)$current['visit_id'],
                'PRESCRIPTION_CANCELLED',
                'Cancelled prescription #' . $prescriptionId . '.',
                $this->pharmacyDepartmentId()
            );

            $this->pdo->commit();

            return [
                'success' => true,
                'prescription_id' => $prescriptionId,
                'errors' => [],
            ];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to cancel prescription.']);
        }
    }

    public function getDispensingByPrescription(int $prescriptionId, ?array $user = null): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT
                pd.*,
                CONCAT(u.first_name, \' \', u.last_name) AS dispensed_by_name
            FROM pharmacy_dispensing pd
            LEFT JOIN users u ON u.id = pd.dispensed_by
            WHERE pd.prescription_id = :prescription_id
            LIMIT 1
        ');
        $stmt->execute([':prescription_id' => $prescriptionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return $row;
    }

    public function canAcceptEncounterUpload(array $visit): bool
    {
        return !in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true);
    }

    private function preparePrescription(array $data, bool $forUpdate = false): array
    {
        $errors = [];

        $visitId = (int)($data['visit_id'] ?? 0);
        $patientId = (int)($data['patient_id'] ?? 0);
        $source = strtoupper(trim((string)($data['prescription_source'] ?? 'Clinical')));
        if (!in_array($source, ['CLINICAL', 'DIRECT'], true)) {
            $source = 'CLINICAL';
        }

        $medicationName = trim((string)($data['medication_name'] ?? ''));
        $dosage = trim((string)($data['dosage'] ?? ''));
        $frequency = trim((string)($data['frequency'] ?? ''));
        $duration = trim((string)($data['duration'] ?? ''));
        $instructions = trim((string)($data['instructions'] ?? ''));
        $quantity = (float)($data['quantity'] ?? 0);
        $inventoryItemId = (int)($data['inventory_item_id'] ?? 0);

        if ($visitId <= 0) {
            $errors[] = 'Encounter is required.';
        }

        if ($patientId <= 0) {
            $errors[] = 'Patient is required.';
        }

        if ($quantity <= 0) {
            $errors[] = 'Quantity must be greater than zero.';
        }

        if ($medicationName === '' && $inventoryItemId <= 0) {
            $errors[] = 'Medication is required.';
        }

        if (!$forUpdate && !in_array($source, ['CLINICAL', 'DIRECT'], true)) {
            $errors[] = 'Invalid prescription source.';
        }

        if (mb_strlen($medicationName) > 255) {
            $errors[] = 'Medication name is too long.';
        }

        if (mb_strlen($dosage) > 255 || mb_strlen($frequency) > 255 || mb_strlen($duration) > 255) {
            $errors[] = 'Prescription details are too long.';
        }

        return [
            'errors' => $errors,
            'data' => [
                'visit_id' => $visitId,
                'patient_id' => $patientId,
                'prescription_source' => $source === 'DIRECT' ? 'Direct' : 'Clinical',
                'inventory_item_id' => $inventoryItemId > 0 ? $inventoryItemId : null,
                'medication_name' => $medicationName,
                'dosage' => $dosage === '' ? null : $dosage,
                'frequency' => $frequency === '' ? null : $frequency,
                'duration' => $duration === '' ? null : $duration,
                'quantity' => $quantity,
                'instructions' => $instructions === '' ? null : $instructions,
            ]
        ];
    }

    private function requireVisit(int $visitId): ?array
    {
        return $this->visitService->getVisitById($visitId);
    }

    private function resolveInventoryItemForPrescription(int $itemId, string $medicationName, array $user): ?array
    {
        if ($itemId > 0) {
            $row = $this->storeService->getItemById($itemId, $user);
            if (!$row || (int)($row['is_active'] ?? 0) !== 1) {
                return null;
            }

            return $row;
        }

        $medicationName = trim($medicationName);
        if ($medicationName === '') {
            return null;
        }

        $stmt = $this->pdo->prepare('
            SELECT ii.*
            FROM inventory_items ii
            WHERE ii.is_active = 1
              AND (ii.item_code = :item_code OR ii.item_name = :item_name)
            LIMIT 1
        ');
        $stmt->execute([
            ':item_code' => $medicationName,
            ':item_name' => $medicationName,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || (int)($row['is_active'] ?? 0) !== 1) {
            return null;
        }

        return $row;
    }

    private function fetchPrescriptionRow(int $prescriptionId): ?array
    {
        $stmt = $this->pdo->prepare($this->buildBaseSelect() . '
            WHERE p.id = :id
            LIMIT 1
        ');
        $stmt->execute([
            ':id' => $prescriptionId,
            ':pharmacy_department_id' => $this->pharmacyDepartmentId(),
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function fetchPrescriptionForUpdate(int $prescriptionId): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT *
            FROM prescriptions
            WHERE id = :id
            LIMIT 1
            FOR UPDATE
        ');
        $stmt->execute([':id' => $prescriptionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function buildBaseSelect(): string
    {
        return '
            SELECT
                p.id,
                p.visit_id,
                p.patient_id,
                p.prescribed_by,
                p.department_id,
                p.prescription_source,
                p.inventory_item_id,
                p.medication_name,
                p.dosage,
                p.frequency,
                p.duration,
                p.quantity,
                p.instructions,
                p.status,
                p.created_by,
                p.updated_by,
                p.created_at,
                p.updated_at,
                p.dispensed_at,
                CONCAT(pt.first_name, \' \', pt.last_name) AS patient_name,
                pt.hospital_number,
                v.visit_number,
                v.visit_status,
                CONCAT(pr.first_name, \' \', pr.last_name) AS prescribed_by_name,
                CONCAT(cu.first_name, \' \', cu.last_name) AS created_by_name,
                CONCAT(uu.first_name, \' \', uu.last_name) AS updated_by_name,
                d.department_name,
                ii.item_code,
                ii.item_name AS inventory_item_name,
                ii.unit,
                bi.unit_price,
                bi.item_type,
                COALESCE(dsb.quantity, 0) AS pharmacy_stock_available,
                disp.id AS dispensing_id,
                disp.quantity_dispensed,
                disp.dispensing_notes,
                disp.created_at AS dispensed_recorded_at,
                CONCAT(di.first_name, \' \', di.last_name) AS dispensed_by_name
            FROM prescriptions p
            INNER JOIN visits v ON v.id = p.visit_id
            INNER JOIN patients pt ON pt.id = p.patient_id
            LEFT JOIN users pr ON pr.id = p.prescribed_by
            LEFT JOIN users cu ON cu.id = p.created_by
            LEFT JOIN users uu ON uu.id = p.updated_by
            LEFT JOIN departments d ON d.id = p.department_id
            LEFT JOIN inventory_items ii ON ii.id = p.inventory_item_id
            LEFT JOIN billable_items bi ON bi.id = ii.billable_item_id
            LEFT JOIN department_stock_balances dsb
                ON dsb.inventory_item_id = p.inventory_item_id
               AND dsb.department_id = :pharmacy_department_id
            LEFT JOIN pharmacy_dispensing disp ON disp.prescription_id = p.id
            LEFT JOIN users di ON di.id = disp.dispensed_by
        ';
    }

    private function pharmacyDepartmentId(): int
    {
        if ($this->pharmacyDepartmentId !== null) {
            return $this->pharmacyDepartmentId;
        }

        $stmt = $this->pdo->prepare('
            SELECT id
            FROM departments
            WHERE department_name = \'Pharmacy\'
            LIMIT 1
        ');
        $stmt->execute();
        $this->pharmacyDepartmentId = (int)($stmt->fetchColumn() ?: 0);

        return $this->pharmacyDepartmentId;
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
            'Pharmacy',
            $action,
            $description,
            $departmentId,
            'INFO',
            $action
        );
    }

    private function recordEvent(
        int $visitId,
        string $eventType,
        string $eventTitle,
        string $eventDescription,
        ?int $departmentId,
        ?int $performedBy
    ): void {
        $this->eventService->record(
            $visitId,
            $eventType,
            $eventTitle,
            $eventDescription,
            $departmentId,
            $performedBy
        );
    }

    private function failure(array $errors): array
    {
        return [
            'success' => false,
            'errors' => $errors,
        ];
    }

    private function rollback(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }
}
