<?php

declare(strict_types=1);

require_once __DIR__ . '/SettingsService.php';

class PermissionService
{
    private PDO $pdo;
    private SettingsService $settingsService;

    public function __construct(PDO $pdo, ?SettingsService $settingsService = null)
    {
        $this->pdo = $pdo;
        $this->settingsService = $settingsService ?? new SettingsService($pdo);
    }

    public function hasPermission(
        string $permission,
        ?array $user = null
    ): bool {
        $user = $user ?? $this->currentUser();

        if (!$user) {
            return false;
        }

        if ($this->isAdministrator($user)) {
            return true;
        }

        $databasePermission = $this->databasePermissionResult(
            $permission,
            $user
        );

        if ($databasePermission !== null) {
            return $databasePermission;
        }

        $role = (string)($user['role_name'] ?? '');
        $department = (string)($user['department_name'] ?? '');

        return match ($permission) {
            'view_encounter' => true,
            'create_encounter' => in_array(
                $role,
                ['Receptionist', 'Nurse'],
                true
            ) || in_array(
                $department,
                ['Reception', 'Nursing'],
                true
            ),
            'transfer_encounter' => $department !== '',
            'receive_encounter' => $department !== '',
            'reopen_encounter' => $role === 'Records Officer',
            'assign_doctor' => $role === 'Doctor'
                || $department === 'Doctor',
            'change_encounter_status' => $department !== '',
            'edit_encounter' => $department !== '',
            'manage_users' => false,
            'view_medical_record' => in_array(
                $role,
                ['Records Officer', 'Doctor', 'Nurse', 'Laboratory Scientist', 'Radiographer', 'Physiotherapist', 'Theatre Staff', 'Pharmacist', 'Receptionist'],
                true
            ) || in_array($department, ['Records', 'Reception', 'Doctor', 'Nursing', 'Laboratory', 'X-Ray', 'Radiology', 'Physiotherapy', 'Theatre', 'Pharmacy'], true),
            'view_patient_identifiers' => in_array(
                $role,
                ['Records Officer', 'Doctor', 'Nurse', 'Receptionist'],
                true
            ) || in_array($department, ['Records', 'Reception'], true),
            'manage_patient_identifiers' => $role === 'Records Officer'
                || in_array($department, ['Records', 'Reception'], true),
            'verify_patient_identifiers' => $role === 'Records Officer'
                || $department === 'Records',
            'view_duplicate_candidates' => $role === 'Records Officer'
                || in_array($department, ['Records', 'Reception'], true),
            'review_duplicate_candidates' => $role === 'Records Officer'
                || $department === 'Records',
            'view_clinical_safety' => in_array(
                $role,
                ['Records Officer','Receptionist','Doctor','Nurse','Laboratory Scientist','Pharmacist','Physiotherapist','Radiographer','Theatre Staff'],
                true
            ),
            'record_allergies', 'update_allergies',
            'verify_allergies', 'manage_clinical_alerts' => in_array(
                $role,
                ['Doctor', 'Nurse'],
                true
            ),
            'resolve_allergies', 'view_confidential_alerts' => $role === 'Doctor',
            'view_clinical_safety_history' => in_array(
                $role,
                ['Records Officer', 'Doctor', 'Nurse'],
                true
            ),
            'view_clinical_notes', 'create_patient_notes',
            'create_encounter_notes', 'edit_own_note_drafts',
            'view_note_history' => in_array(
                $role,
                ['Records Officer', 'Doctor', 'Nurse'],
                true
            ),
            'edit_any_note_draft', 'approve_note_amendments' => $role === 'Records Officer',
            'sign_clinical_notes' => $role === 'Doctor',
            'amend_signed_notes', 'mark_note_entered_in_error',
            'view_confidential_notes' => in_array($role, ['Records Officer', 'Doctor'], true),
            'view_vital_signs' => in_array(
                $role,
                ['Records Officer', 'Doctor', 'Nurse', 'Laboratory Scientist', 'Radiographer', 'Physiotherapist', 'Theatre Staff', 'Pharmacist'],
                true
            ) || in_array($department, ['Records', 'Doctor', 'Nursing', 'Laboratory', 'X-Ray', 'Radiology', 'Physiotherapy', 'Theatre', 'Pharmacy'], true),
            'create_vital_signs', 'edit_vital_signs' => in_array(
                $role,
                ['Doctor', 'Nurse'],
                true
            ),
            'view_nursing' => in_array(
                $role,
                ['Records Officer', 'Doctor', 'Nurse', 'Laboratory Scientist', 'Radiographer', 'Physiotherapist', 'Theatre Staff', 'Pharmacist'],
                true
            ) || in_array($department, ['Records', 'Doctor', 'Nursing', 'Laboratory', 'X-Ray', 'Radiology', 'Physiotherapy', 'Theatre', 'Pharmacy'], true),
            'create_nursing', 'edit_nursing', 'complete_nursing' => $role === 'Nurse',
            'view_laboratory' => in_array(
                $role,
                ['Records Officer', 'Doctor', 'Nurse', 'Laboratory Scientist', 'Radiographer', 'Physiotherapist', 'Theatre Staff', 'Pharmacist'],
                true
            ) || in_array($department, ['Records', 'Doctor', 'Nursing', 'Laboratory', 'X-Ray', 'Radiology', 'Physiotherapy', 'Theatre', 'Pharmacy'], true),
            'create_laboratory_request' => in_array(
                $role,
                ['Doctor', 'Laboratory Scientist'],
                true
            ),
            'process_laboratory_request', 'enter_laboratory_result',
            'edit_laboratory_result', 'complete_laboratory_request' => $role === 'Laboratory Scientist',
            'view_radiology' => in_array(
                $role,
                ['Records Officer', 'Doctor', 'Nurse', 'Laboratory Scientist', 'Radiographer', 'Physiotherapist', 'Theatre Staff', 'Pharmacist'],
                true
            ) || in_array($department, ['Records', 'Doctor', 'Nursing', 'Laboratory', 'X-Ray', 'Radiology', 'Physiotherapy', 'Theatre', 'Pharmacy'], true),
            'create_radiology_request' => in_array(
                $role,
                ['Doctor', 'Radiographer'],
                true
            ),
            'process_radiology_request', 'enter_radiology_report',
            'edit_radiology_report', 'complete_radiology_request' => $role === 'Radiographer',
            'view_physiotherapy' => in_array(
                $role,
                ['Records Officer', 'Doctor', 'Nurse', 'Laboratory Scientist', 'Radiographer', 'Physiotherapist', 'Theatre Staff', 'Pharmacist'],
                true
            ) || in_array($department, ['Records', 'Doctor', 'Nursing', 'Laboratory', 'X-Ray', 'Radiology', 'Physiotherapy', 'Physio', 'Rehabilitation', 'Theatre', 'Pharmacy'], true),
            'view_theatre' => in_array(
                $role,
                ['Records Officer', 'Doctor', 'Nurse', 'Laboratory Scientist', 'Radiographer', 'Physiotherapist', 'Theatre Staff', 'Pharmacist'],
                true
            ) || in_array($department, ['Records', 'Doctor', 'Nursing', 'Laboratory', 'X-Ray', 'Radiology', 'Physiotherapy', 'Theatre', 'Pharmacy'], true),
            'create_theatre', 'edit_theatre', 'complete_theatre' => in_array(
                $role,
                ['Doctor', 'Theatre Staff'],
                true
            ) || in_array($department, ['Doctor', 'Theatre'], true),
            'view_pharmacy' => in_array(
                $role,
                ['Records Officer', 'Doctor', 'Nurse', 'Laboratory Scientist', 'Radiographer', 'Physiotherapist', 'Theatre Staff', 'Pharmacist'],
                true
            ) || in_array($department, ['Records', 'Doctor', 'Nursing', 'Laboratory', 'X-Ray', 'Radiology', 'Physiotherapy', 'Theatre', 'Pharmacy'], true),
            'create_prescription', 'edit_prescription' => in_array(
                $role,
                ['Doctor', 'Pharmacist'],
                true
            ) || in_array($department, ['Doctor', 'Pharmacy'], true),
            'dispense_prescription' => $role === 'Pharmacist'
                || $department === 'Pharmacy',
            'view_billing' => in_array(
                $role,
                [
                    'Accounts',
                    'Accountant',
                    'Receptionist',
                    'Records Officer',
                    'Doctor',
                    'Nurse',
                    'Laboratory Scientist',
                    'Radiographer',
                    'Physiotherapist',
                    'Theatre Staff',
                    'Pharmacist',
                    'Store Officer',
                ],
                true
            ) || in_array($department, ['Accounts', 'Reception', 'Records'], true),
            'create_patient_charge', 'cancel_patient_charge',
            'create_invoice', 'record_payment' => in_array(
                $role,
                ['Accounts', 'Accountant'],
                true
            ) || $department === 'Accounts',
            'create_billing_request' => in_array(
                $role,
                [
                    'Doctor',
                    'Nurse',
                    'Laboratory Scientist',
                    'Radiographer',
                    'Physiotherapist',
                    'Theatre Staff',
                    'Pharmacist',
                ],
                true
            ) || in_array(
                $department,
                ['Doctor', 'Nursing', 'Laboratory', 'X-Ray', 'Radiology', 'Physiotherapy', 'Theatre', 'Pharmacy'],
                true
            ),
            'view_billing_requests', 'review_billing_request',
            'cancel_billing_request' => in_array(
                $role,
                ['Accounts', 'Accountant'],
                true
            ) || $department === 'Accounts',
            'view_receipts' => in_array(
                $role,
                ['Accounts', 'Accountant', 'Receptionist', 'Records Officer'],
                true
            ) || in_array($department, ['Accounts', 'Reception', 'Records'], true),
            'view_billable_items' => in_array(
                $role,
                [
                    'Accountant',
                    'Accounts',
                    'Receptionist',
                    'Records Officer',
                    'Doctor',
                    'Nurse',
                    'Laboratory Scientist',
                    'Radiographer',
                    'Physiotherapist',
                    'Theatre Staff',
                    'Pharmacist',
                    'Store Officer'
                ],
                true
            ) || in_array(
                $department,
                [
                    'Accounts',
                    'Reception',
                    'Records',
                    'Doctor',
                    'Nursing',
                    'Laboratory',
                    'Radiology',
                    'Physiotherapy',
                    'Theatre',
                    'Pharmacy',
                    'Store'
                ],
                true
            ),
            'create_billable_items', 'edit_billable_items',
            'manage_billable_item_status' => in_array(
                $role,
                ['Accountant', 'Accounts'],
                true
            ) || $department === 'Accounts',
            'view_inventory' => in_array(
                $role,
                [
                    'Store Officer',
                    'Accountant',
                    'Doctor',
                    'Nurse',
                    'Laboratory Scientist',
                    'Radiographer',
                    'Physiotherapist',
                    'Theatre Staff',
                    'Pharmacist',
                    'Receptionist',
                    'Records Officer'
                ],
                true
            ) || in_array(
                $department,
                [
                    'Store',
                    'Accounts',
                    'Doctor',
                    'Nursing',
                    'Laboratory',
                    'Radiology',
                    'Physiotherapy',
                    'Theatre',
                    'Pharmacy',
                    'Reception',
                    'Records'
                ],
                true
            ),
            'manage_inventory_items', 'receive_stock', 'issue_stock',
            'return_stock', 'adjust_stock', 'view_stock_ledger' => in_array(
                $role,
                ['Store Officer'],
                true
            ) || $department === 'Store',
            'view_reports' => in_array(
                $role,
                ['System Administrator', 'Accountant', 'Accounts', 'Store Officer', 'Doctor', 'Nurse', 'Laboratory Scientist', 'Radiographer', 'Physiotherapist', 'Theatre Staff', 'Pharmacist', 'Records Officer'],
                true
            ) || in_array($department, ['Administrator', 'Accounts', 'Store', 'Doctor', 'Nursing', 'Laboratory', 'Radiology', 'Physiotherapy', 'Theatre', 'Pharmacy', 'Records'], true),
            'view_financial_reports' => in_array(
                $role,
                ['Accountant', 'Accounts'],
                true
            ) || $department === 'Accounts',
            'view_inventory_reports' => $role === 'Store Officer'
                || $department === 'Store',
            'view_clinical_reports' => in_array(
                $role,
                ['Doctor', 'Nurse', 'Laboratory Scientist', 'Radiographer', 'Physiotherapist', 'Theatre Staff', 'Pharmacist', 'Records Officer'],
                true
            ) || in_array($department, ['Doctor', 'Nursing', 'Laboratory', 'Radiology', 'Physiotherapy', 'Theatre', 'Pharmacy', 'Records'], true),
            'view_admissions' => in_array(
                $role,
                ['Receptionist', 'Records Officer', 'Doctor', 'Nurse'],
                true
            ) || in_array($department, ['Reception', 'Records', 'Doctor', 'Nursing'], true),
            'create_admission' => in_array(
                $role,
                ['Receptionist', 'Records Officer', 'Doctor', 'Nurse'],
                true
            ) || in_array($department, ['Reception', 'Records', 'Doctor', 'Nursing'], true),
            'transfer_admission', 'discharge_admission', 'manage_wards_beds' => in_array(
                $role,
                ['Records Officer', 'Nurse'],
                true
            ) || in_array($department, ['Records', 'Nursing'], true),
            'view_consultation' => in_array(
                $role,
                ['Records Officer', 'Doctor', 'Nurse', 'Laboratory Scientist', 'Radiographer', 'Physiotherapist', 'Theatre Staff', 'Pharmacist'],
                true
            ) || in_array($department, ['Records', 'Doctor', 'Nursing', 'Laboratory', 'X-Ray', 'Radiology', 'Physiotherapy', 'Theatre', 'Pharmacy'], true),
            'create_consultation', 'edit_consultation', 'complete_consultation' => $role === 'Doctor',
            default => false
        };
    }

    public function listPermissions(bool $includeInactive = true): array
    {
        $sql = '
            SELECT id, permission_key, permission_name, module,
                   description, is_active, created_at, updated_at
            FROM permissions
        ';

        if (!$includeInactive) {
            $sql .= ' WHERE is_active = 1';
        }

        $sql .= ' ORDER BY module, permission_name';

        try {
            return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $exception) {
            return [];
        }
    }

    public function createPermission(
        string $permissionKey,
        string $permissionName,
        string $module,
        ?string $description,
        int $createdBy
    ): array {
        $permissionKey = trim($permissionKey);
        $permissionName = trim($permissionName);
        $module = trim($module);

        if ($permissionKey === '' || $permissionName === '' || $module === '') {
            return ['success' => false, 'errors' => ['Permission key, name and module are required.']];
        }

        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare('
                INSERT INTO permissions (
                    permission_key, permission_name, module, description
                ) VALUES (:permission_key, :permission_name, :module, :description)
            ');
            $stmt->execute([
                ':permission_key' => $permissionKey,
                ':permission_name' => $permissionName,
                ':module' => $module,
                ':description' => $description === null ? null : trim($description)
            ]);
            $permissionId = (int)$this->pdo->lastInsertId();

            $this->audit(
                $createdBy,
                'PERMISSION_CREATED',
                'Created permission #' . $permissionId . '.'
            );
            $this->pdo->commit();

            return ['success' => true, 'permission_id' => $permissionId, 'errors' => []];
        } catch (Throwable $exception) {
            $this->rollback();
            return ['success' => false, 'errors' => ['Unable to create permission.']];
        }
    }

    public function updatePermission(
        int $permissionId,
        string $permissionKey,
        string $permissionName,
        string $module,
        ?string $description,
        int $updatedBy
    ): array {
        if (trim($permissionKey) === '' || trim($permissionName) === '' || trim($module) === '') {
            return ['success' => false, 'errors' => ['Permission key, name and module are required.']];
        }

        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare('
                UPDATE permissions
                SET permission_key = :permission_key,
                    permission_name = :permission_name,
                    module = :module,
                    description = :description
                WHERE id = :id
            ');
            $stmt->execute([
                ':permission_key' => trim($permissionKey),
                ':permission_name' => trim($permissionName),
                ':module' => trim($module),
                ':description' => $description === null ? null : trim($description),
                ':id' => $permissionId
            ]);

            if ($stmt->rowCount() === 0) {
                throw new RuntimeException('Permission not found.');
            }

            $this->audit(
                $updatedBy,
                'PERMISSION_UPDATED',
                'Updated permission #' . $permissionId . '.'
            );
            $this->pdo->commit();

            return ['success' => true, 'permission_id' => $permissionId, 'errors' => []];
        } catch (Throwable $exception) {
            $this->rollback();
            return ['success' => false, 'errors' => ['Unable to update permission.']];
        }
    }

    public function getRolePermissions(int $roleId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT p.id, p.permission_key, p.permission_name, p.module,
                   p.description, p.is_active
            FROM permissions p
            INNER JOIN role_permissions rp ON rp.permission_id = p.id
            WHERE rp.role_id = :role_id
            ORDER BY p.module, p.permission_name
        ');
        $stmt->execute([':role_id' => $roleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function assignPermissions(
        int $roleId,
        array $permissionIds,
        int $assignedBy
    ): array {
        $permissionIds = array_values(array_unique(array_filter(
            array_map('intval', $permissionIds),
            static fn (int $id): bool => $id > 0
        )));

        try {
            $this->pdo->beginTransaction();

            $roleStmt = $this->pdo->prepare(
                'SELECT id FROM roles WHERE id = :id FOR UPDATE'
            );
            $roleStmt->execute([':id' => $roleId]);
            if (!$roleStmt->fetchColumn()) {
                throw new RuntimeException('Role not found.');
            }

            $oldStmt = $this->pdo->prepare(
                'SELECT permission_id FROM role_permissions WHERE role_id = :role_id'
            );
            $oldStmt->execute([':role_id' => $roleId]);
            $oldIds = array_map('intval', $oldStmt->fetchAll(PDO::FETCH_COLUMN));

            $this->pdo->prepare(
                'DELETE FROM role_permissions WHERE role_id = :role_id'
            )->execute([':role_id' => $roleId]);

            $insert = $this->pdo->prepare('
                INSERT INTO role_permissions (role_id, permission_id, assigned_by)
                SELECT :role_id, id, :assigned_by
                FROM permissions
                WHERE id = :permission_id AND is_active = 1
            ');

            foreach ($permissionIds as $permissionId) {
                $insert->execute([
                    ':role_id' => $roleId,
                    ':assigned_by' => $assignedBy,
                    ':permission_id' => $permissionId
                ]);
            }

            foreach (array_diff($permissionIds, $oldIds) as $permissionId) {
                $this->audit(
                    $assignedBy,
                    'PERMISSION_ASSIGNED',
                    'Assigned permission #' . $permissionId . ' to role #' . $roleId . '.'
                );
            }

            foreach (array_diff($oldIds, $permissionIds) as $permissionId) {
                $this->audit(
                    $assignedBy,
                    'PERMISSION_REMOVED',
                    'Removed permission #' . $permissionId . ' from role #' . $roleId . '.'
                );
            }

            $this->audit(
                $assignedBy,
                'ROLE_PERMISSION_UPDATED',
                'Updated permissions for role #' . $roleId . '.'
            );
            $this->pdo->commit();

            return [
                'success' => true,
                'role_id' => $roleId,
                'permission_ids' => $permissionIds,
                'errors' => []
            ];
        } catch (Throwable $exception) {
            $this->rollback();
            return ['success' => false, 'errors' => ['Unable to update role permissions.']];
        }
    }

    public function removePermission(
        int $roleId,
        int $permissionId,
        int $removedBy
    ): array {
        $current = $this->getRolePermissions($roleId);
        $ids = array_map('intval', array_column($current, 'id'));
        $ids = array_values(array_diff($ids, [$permissionId]));

        $result = $this->assignPermissions($roleId, $ids, $removedBy);

        return $result;
    }

    /*
    |--------------------------------------------------------------------------
    | Medical Records Authorization
    |--------------------------------------------------------------------------
    */

    public function canViewMedicalRecord(
        int $patientId,
        ?array $user = null
    ): bool {
        $user = $user ?? $this->currentUser();

        if (!$user || $patientId <= 0) {
            return false;
        }

        if ($this->isAdministrator($user)) {
            return true;
        }

        if (!$this->hasPermission('view_medical_record', $user)) {
            return false;
        }

        $role = (string)($user['role_name'] ?? '');
        $department = (string)($user['department_name'] ?? '');

        if ($role === 'Records Officer'
            || in_array($department, ['Records', 'Reception'], true)
        ) {
            return true;
        }

        if ($this->isClinicalCrossViewRole($user)) {
            return true;
        }

        return $this->hasTreatmentRelationship($patientId, $user);
    }

    public function canEditPatientDemographics(
        int $patientId,
        ?array $user = null
    ): bool {
        $user = $user ?? $this->currentUser();

        if (!$user || $patientId <= 0) {
            return false;
        }

        if ($this->isAdministrator($user)) {
            return true;
        }

        if (!$this->hasPermission('edit_patient_demographics', $user)) {
            return false;
        }

        return ($user['role_name'] ?? '') === 'Records Officer'
            || in_array(
                (string)($user['department_name'] ?? ''),
                ['Records', 'Reception'],
                true
            );
    }

    public function canViewPatientAuditHistory(
        int $patientId,
        ?array $user = null
    ): bool {
        $user = $user ?? $this->currentUser();

        if (!$user || $patientId <= 0) {
            return false;
        }

        if ($this->isAdministrator($user)) {
            return true;
        }

        return $this->hasPermission('view_patient_audit_history', $user)
            && (($user['role_name'] ?? '') === 'Records Officer'
                || ($user['department_name'] ?? '') === 'Records');
    }

    public function canViewPatientIdentifiers(
        int $patientId,
        ?array $user = null
    ): bool {
        $user = $user ?? $this->currentUser();
        return $this->isAdministrator($user)
            || ($this->hasPermission('view_patient_identifiers', $user)
                && $this->canViewMedicalRecord($patientId, $user));
    }

    public function canManagePatientIdentifiers(
        int $patientId,
        ?string $identifierType = null,
        ?array $user = null
    ): bool {
        $user = $user ?? $this->currentUser();
        if ($this->isAdministrator($user)) {
            return true;
        }
        if (!$this->hasPermission('manage_patient_identifiers', $user)
            || !$this->canViewMedicalRecord($patientId, $user)
        ) {
            return false;
        }
        if ($this->roleMatches($user, ['Accounts', 'Accountant'])) {
            return $identifierType === 'Insurance Number';
        }
        return in_array(
            (string)($user['department_name'] ?? ''),
            ['Records', 'Reception'],
            true
        ) || ($user['role_name'] ?? '') === 'Records Officer';
    }

    public function canVerifyPatientIdentifiers(?array $user = null): bool
    {
        $user = $user ?? $this->currentUser();
        return $this->isAdministrator($user)
            || ($this->hasPermission('verify_patient_identifiers', $user)
                && (($user['role_name'] ?? '') === 'Records Officer'
                    || ($user['department_name'] ?? '') === 'Records'));
    }

    public function canViewDuplicateCandidates(?array $user = null): bool
    {
        $user = $user ?? $this->currentUser();
        return $this->isAdministrator($user)
            || $this->hasPermission('view_duplicate_candidates', $user);
    }

    public function canReviewDuplicateCandidates(?array $user = null): bool
    {
        $user = $user ?? $this->currentUser();
        return $this->isAdministrator($user)
            || ($this->hasPermission('review_duplicate_candidates', $user)
                && (($user['role_name'] ?? '') === 'Records Officer'
                    || ($user['department_name'] ?? '') === 'Records'));
    }

    public function canViewClinicalSafety(int $patientId, ?array $user = null): bool
    {
        $user = $user ?? $this->currentUser();
        return $this->isAdministrator($user)
            || ($this->hasPermission('view_clinical_safety', $user)
                && $this->canViewMedicalRecord($patientId, $user));
    }

    public function canRecordAllergies(int $patientId, ?array $user = null): bool
    {
        return $this->canPerformClinicalSafetyAction(
            'record_allergies',
            $patientId,
            ['Doctor', 'Nurse'],
            $user
        );
    }

    public function canUpdateAllergies(int $patientId, ?array $user = null): bool
    {
        return $this->canPerformClinicalSafetyAction(
            'update_allergies',
            $patientId,
            ['Doctor', 'Nurse'],
            $user
        );
    }

    public function canVerifyAllergies(int $patientId, ?array $user = null): bool
    {
        $user = $user ?? $this->currentUser();
        if ($this->isAdministrator($user)) {
            return true;
        }
        if (!$this->canPerformClinicalSafetyAction(
            'verify_allergies',
            $patientId,
            ['Doctor', 'Nurse'],
            $user
        )) {
            return false;
        }
        return ($user['role_name'] ?? '') === 'Doctor'
            || (($user['role_name'] ?? '') === 'Nurse'
                && $this->settingsService->getBoolean(
                    'clinical_safety.nurse_may_verify_allergies',
                    false
                ));
    }

    public function canResolveAllergies(int $patientId, ?array $user = null): bool
    {
        return $this->canPerformClinicalSafetyAction(
            'resolve_allergies',
            $patientId,
            ['Doctor'],
            $user
        );
    }

    public function canDeactivateAllergies(int $patientId, ?array $user = null): bool
    {
        return $this->canResolveAllergies($patientId, $user);
    }

    public function canReactivateAllergies(int $patientId, ?array $user = null): bool
    {
        return $this->canResolveAllergies($patientId, $user);
    }

    public function canManageClinicalAlerts(int $patientId, ?array $user = null): bool
    {
        return $this->canPerformClinicalSafetyAction(
            'manage_clinical_alerts',
            $patientId,
            ['Doctor', 'Nurse'],
            $user
        );
    }

    public function canViewConfidentialAlerts(int $patientId, ?array $user = null): bool
    {
        return $this->canPerformClinicalSafetyAction(
            'view_confidential_alerts',
            $patientId,
            ['Doctor'],
            $user
        );
    }

    public function canViewClinicalSafetyHistory(int $patientId, ?array $user = null): bool
    {
        return $this->canPerformClinicalSafetyAction(
            'view_clinical_safety_history',
            $patientId,
            ['Records Officer', 'Doctor', 'Nurse'],
            $user
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Longitudinal Problem List and Medical History Authorization
    |--------------------------------------------------------------------------
    */

    public function canViewProblemList(int $patientId, ?array $user = null): bool
    {
        $user = $user ?? $this->currentUser();
        return $this->isAdministrator($user)
            || ($this->hasPermission('view_problem_list', $user)
                && $this->canViewMedicalRecord($patientId, $user));
    }

    public function canManageProblemList(int $patientId, ?array $user = null): bool
    {
        $roles = ['Doctor'];
        if ($this->settingsService->getBoolean('problem_list.nurse_may_manage', false)) {
            $roles[] = 'Nurse';
        }
        return $this->canPerformLongitudinalAction(
            'manage_problem_list',
            $patientId,
            $roles,
            $user
        );
    }

    public function canVerifyProblemList(int $patientId, ?array $user = null): bool
    {
        return $this->canPerformLongitudinalAction(
            'verify_problem_list',
            $patientId,
            ['Doctor'],
            $user
        );
    }

    public function canResolveProblemList(int $patientId, ?array $user = null): bool
    {
        return $this->canPerformLongitudinalAction(
            'resolve_problem_list',
            $patientId,
            ['Doctor'],
            $user
        );
    }

    public function canViewStructuredMedicalHistory(int $patientId, ?array $user = null): bool
    {
        $user = $user ?? $this->currentUser();
        return $this->isAdministrator($user)
            || ($this->hasPermission('view_medical_history', $user)
                && $this->canViewMedicalRecord($patientId, $user));
    }

    public function canManageStructuredMedicalHistory(int $patientId, ?array $user = null): bool
    {
        return $this->canPerformLongitudinalAction(
            'manage_medical_history',
            $patientId,
            ['Doctor', 'Nurse'],
            $user
        );
    }

    public function canVerifyStructuredMedicalHistory(int $patientId, ?array $user = null): bool
    {
        return $this->canPerformLongitudinalAction(
            'verify_medical_history',
            $patientId,
            ['Doctor'],
            $user
        );
    }

    public function canViewConfidentialMedicalHistory(int $patientId, ?array $user = null): bool
    {
        return $this->canPerformLongitudinalAction(
            'view_confidential_medical_history',
            $patientId,
            ['Doctor'],
            $user
        );
    }

    public function canViewProblemHistory(int $patientId, ?array $user = null): bool
    {
        return $this->canPerformLongitudinalAction(
            'view_problem_history',
            $patientId,
            ['Records Officer', 'Doctor', 'Nurse'],
            $user
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Medical Document Authorization
    |--------------------------------------------------------------------------
    */

    public function canViewMedicalDocuments(int $patientId, ?array $user = null): bool
    {
        $user = $user ?? $this->currentUser();
        return $this->isAdministrator($user)
            || ($this->hasPermission('view_medical_documents', $user)
                && $this->canViewMedicalRecord($patientId, $user));
    }

    public function canUploadMedicalDocuments(
        int $patientId,
        ?string $documentType = null,
        ?array $user = null
    ): bool {
        $user = $user ?? $this->currentUser();
        if ($this->isAdministrator($user)) {
            return true;
        }
        if (!$this->hasPermission('upload_medical_documents', $user)
            || !$this->canViewMedicalRecord($patientId, $user)
        ) {
            return false;
        }
        if ($documentType === null) {
            return true;
        }
        if ($this->roleMatches($user, ['Receptionist'])) {
            return in_array($documentType, [
                'referral_letter', 'identity_document', 'insurance_document',
                'consent_form', 'correspondence', 'other'
            ], true);
        }
        if ($this->roleMatches($user, ['Accountant', 'Accounts'])) {
            return in_array($documentType, [
                'insurance_document', 'correspondence', 'other'
            ], true);
        }
        return true;
    }

    public function canReplaceMedicalDocuments(int $patientId, ?array $user = null): bool
    {
        return $this->canPerformDocumentAction(
            'replace_medical_documents',
            $patientId,
            ['Records Officer', 'Doctor'],
            $user
        );
    }

    public function canArchiveMedicalDocuments(int $patientId, ?array $user = null): bool
    {
        return $this->canPerformDocumentAction(
            'archive_medical_documents',
            $patientId,
            ['Records Officer'],
            $user
        );
    }

    public function canDownloadMedicalDocuments(int $patientId, ?array $user = null): bool
    {
        return $this->canPerformDocumentAction(
            'download_medical_documents',
            $patientId,
            [
                'Records Officer', 'Doctor', 'Nurse', 'Laboratory Scientist',
                'Pharmacist', 'Physiotherapist', 'Radiographer', 'Theatre Staff',
                'Receptionist', 'Accountant', 'Accounts'
            ],
            $user
        );
    }

    public function canViewConfidentialDocuments(int $patientId, ?array $user = null): bool
    {
        return $this->canPerformDocumentAction(
            'view_confidential_documents',
            $patientId,
            ['Records Officer', 'Doctor'],
            $user
        );
    }

    public function canViewDocumentHistory(int $patientId, ?array $user = null): bool
    {
        return $this->canPerformDocumentAction(
            'view_document_history',
            $patientId,
            ['Records Officer', 'Doctor'],
            $user
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Clinical Note Authorization
    |--------------------------------------------------------------------------
    */

    public function canViewClinicalNotes(int $patientId, ?array $user = null): bool
    {
        $user = $user ?? $this->currentUser();
        return $this->isAdministrator($user)
            || ($this->hasPermission('view_clinical_notes', $user)
                && $this->canViewMedicalRecord($patientId, $user));
    }

    public function canCreateClinicalNote(
        int $patientId,
        bool $encounterLinked,
        ?string $noteType = null,
        ?array $user = null
    ): bool {
        $user = $user ?? $this->currentUser();
        if ($this->isAdministrator($user)) {
            return true;
        }
        $permission = $encounterLinked ? 'create_encounter_notes' : 'create_patient_notes';
        if (!$this->hasPermission($permission, $user)
            || !$this->canViewMedicalRecord($patientId, $user)
            || !$this->roleMatches($user, ['Records Officer', 'Doctor', 'Nurse'])
        ) {
            return false;
        }
        if ($noteType === null || $noteType === '') {
            return true;
        }
        if ($this->roleMatches($user, ['Records Officer'])) {
            return in_array($noteType, [
                'medical_records_note', 'care_coordination_note',
                'patient_communication_note', 'administrative_clinical_note',
                'external_record_summary', 'other'
            ], true);
        }
        return true;
    }

    public function canEditOwnNoteDraft(int $patientId, ?array $user = null): bool
    {
        return $this->canPerformLongitudinalAction(
            'edit_own_note_drafts',
            $patientId,
            ['Records Officer', 'Doctor', 'Nurse'],
            $user
        );
    }

    public function canEditAnyNoteDraft(int $patientId, ?array $user = null): bool
    {
        return $this->canPerformLongitudinalAction(
            'edit_any_note_draft',
            $patientId,
            ['Records Officer'],
            $user
        );
    }

    public function canSignClinicalNotes(int $patientId, ?array $user = null): bool
    {
        $user = $user ?? $this->currentUser();
        if (!$user || !$this->roleMatches($user, ['Doctor'])) {
            return false;
        }
        return $this->hasPermission('sign_clinical_notes', $user)
            && $this->canViewMedicalRecord($patientId, $user);
    }

    public function canAmendSignedNotes(int $patientId, ?array $user = null): bool
    {
        return $this->canPerformLongitudinalAction(
            'amend_signed_notes',
            $patientId,
            ['Records Officer', 'Doctor'],
            $user
        );
    }

    public function canApproveNoteAmendments(int $patientId, ?array $user = null): bool
    {
        return $this->canPerformLongitudinalAction(
            'approve_note_amendments',
            $patientId,
            ['Records Officer'],
            $user
        );
    }

    public function canMarkNoteEnteredInError(int $patientId, ?array $user = null): bool
    {
        return $this->canPerformLongitudinalAction(
            'mark_note_entered_in_error',
            $patientId,
            ['Records Officer', 'Doctor'],
            $user
        );
    }

    public function canViewConfidentialNotes(int $patientId, ?array $user = null): bool
    {
        return $this->canPerformLongitudinalAction(
            'view_confidential_notes',
            $patientId,
            ['Records Officer', 'Doctor'],
            $user
        );
    }

    public function canViewNoteHistory(int $patientId, ?array $user = null): bool
    {
        return $this->canPerformLongitudinalAction(
            'view_note_history',
            $patientId,
            ['Records Officer', 'Doctor', 'Nurse'],
            $user
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Vital Signs Authorization
    |--------------------------------------------------------------------------
    */

    public function canViewVitalSigns(int $patientId, ?array $user = null): bool
    {
        return $this->canPerformLongitudinalAction(
            'view_vital_signs',
            $patientId,
            ['Records Officer', 'Doctor', 'Nurse', 'Laboratory Scientist', 'Radiographer', 'Physiotherapist', 'Theatre Staff', 'Pharmacist'],
            $user
        );
    }

    public function canCreateVitalSigns(array $encounter, ?array $user = null): bool
    {
        return $this->canMutateVitalSigns('create_vital_signs', $encounter, $user);
    }

    public function canEditVitalSigns(array $encounter, ?array $user = null): bool
    {
        return $this->canMutateVitalSigns('edit_vital_signs', $encounter, $user);
    }

    public function canViewNursing(int $patientId, ?array $user = null): bool
    {
        return $this->canPerformLongitudinalAction(
            'view_nursing',
            $patientId,
            ['Records Officer', 'Doctor', 'Nurse', 'Laboratory Scientist', 'Radiographer', 'Physiotherapist', 'Theatre Staff', 'Pharmacist'],
            $user
        );
    }

    public function canCreateNursing(array $encounter, ?array $user = null): bool
    {
        return $this->canMutateNursing('create_nursing', $encounter, $user);
    }

    public function canEditNursing(array $encounter, ?array $user = null): bool
    {
        return $this->canMutateNursing('edit_nursing', $encounter, $user);
    }

    public function canCompleteNursing(array $encounter, ?array $user = null): bool
    {
        return $this->canMutateNursing('complete_nursing', $encounter, $user);
    }

    /*
    |--------------------------------------------------------------------------
    | Laboratory Authorization
    |--------------------------------------------------------------------------
    */

    public function canViewLaboratory(int $patientId, ?array $user = null): bool
    {
        return $this->canViewClinicalContext('view_laboratory', $patientId, $user);
    }

    public function canCreateLaboratoryRequest(
        array $encounter,
        ?array $user = null,
        string $requestSource = 'Clinical'
    ): bool {
        return $this->canMutateLaboratory(
            'create_laboratory_request',
            $encounter,
            $user,
            $requestSource
        );
    }

    public function canProcessLaboratoryRequest(
        array $encounter,
        ?array $user = null
    ): bool {
        return $this->canMutateLaboratory(
            'process_laboratory_request',
            $encounter,
            $user
        );
    }

    public function canEnterLaboratoryResult(
        array $encounter,
        ?array $user = null
    ): bool {
        return $this->canMutateLaboratory(
            'enter_laboratory_result',
            $encounter,
            $user
        );
    }

    public function canEditLaboratoryResult(
        array $encounter,
        ?array $user = null
    ): bool {
        return $this->canMutateLaboratory(
            'edit_laboratory_result',
            $encounter,
            $user
        );
    }

    public function canCompleteLaboratoryRequest(
        array $encounter,
        ?array $user = null
    ): bool {
        return $this->canMutateLaboratory(
            'complete_laboratory_request',
            $encounter,
            $user
        );
    }

    public function canViewRadiology(int $patientId, ?array $user = null): bool
    {
        return $this->canViewClinicalContext('view_radiology', $patientId, $user);
    }

    public function canCreateRadiologyRequest(
        array $encounter,
        ?array $user = null,
        string $requestSource = 'Clinical'
    ): bool {
        return $this->canMutateRadiology('create_radiology_request', $encounter, $user, $requestSource);
    }

    public function canProcessRadiologyRequest(array $encounter, ?array $user = null): bool
    {
        return $this->canMutateRadiology('process_radiology_request', $encounter, $user);
    }

    public function canEnterRadiologyReport(array $encounter, ?array $user = null): bool
    {
        return $this->canMutateRadiology('enter_radiology_report', $encounter, $user);
    }

    public function canEnterRadiologyResult(array $encounter, ?array $user = null): bool
    {
        return $this->canEnterRadiologyReport($encounter, $user);
    }

    public function canEditRadiologyReport(array $encounter, ?array $user = null): bool
    {
        return $this->canMutateRadiology('edit_radiology_report', $encounter, $user);
    }

    public function canEditRadiologyResult(array $encounter, ?array $user = null): bool
    {
        return $this->canEditRadiologyReport($encounter, $user);
    }

    public function canCompleteRadiologyRequest(array $encounter, ?array $user = null): bool
    {
        return $this->canMutateRadiology('complete_radiology_request', $encounter, $user);
    }

    /*
    |--------------------------------------------------------------------------
    | Physiotherapy Authorization
    |--------------------------------------------------------------------------
    */

    public function canViewPhysiotherapy(int $patientId, ?array $user = null): bool
    {
        return $this->canViewClinicalContext('view_physiotherapy', $patientId, $user);
    }

    public function canCreatePhysiotherapy(
        array $encounter,
        ?array $user = null,
        string $recordSource = 'Clinical'
    ): bool {
        return $this->canMutatePhysiotherapy('create_physiotherapy', $encounter, $user, $recordSource);
    }

    public function canCreatePhysiotherapyRequest(
        array $encounter,
        ?array $user = null,
        string $recordSource = 'Clinical'
    ): bool {
        return $this->canCreatePhysiotherapy($encounter, $user, $recordSource);
    }

    public function canEditPhysiotherapy(array $encounter, ?array $user = null): bool
    {
        return $this->canMutatePhysiotherapy('edit_physiotherapy', $encounter, $user);
    }

    public function canEditPhysiotherapyResult(array $encounter, ?array $user = null): bool
    {
        return $this->canEditPhysiotherapy($encounter, $user);
    }

    public function canManagePhysiotherapySessions(array $encounter, ?array $user = null): bool
    {
        return $this->canMutatePhysiotherapy('manage_physiotherapy_sessions', $encounter, $user);
    }

    public function canProcessPhysiotherapyRequest(array $encounter, ?array $user = null): bool
    {
        return $this->canManagePhysiotherapySessions($encounter, $user);
    }

    public function canEnterPhysiotherapyResult(array $encounter, ?array $user = null): bool
    {
        return $this->canManagePhysiotherapySessions($encounter, $user);
    }

    public function canCompletePhysiotherapy(array $encounter, ?array $user = null): bool
    {
        return $this->canMutatePhysiotherapy('complete_physiotherapy', $encounter, $user);
    }

    public function canCompletePhysiotherapyRequest(array $encounter, ?array $user = null): bool
    {
        return $this->canCompletePhysiotherapy($encounter, $user);
    }

    /*
    |--------------------------------------------------------------------------
    | Theatre Authorization
    |--------------------------------------------------------------------------
    */

    public function canViewTheatre(array $encounter, ?array $user = null): bool
    {
        $user = $user ?? $this->currentUser();
        return $this->isAdministrator($user)
            || ($this->hasPermission('view_theatre', $user)
                && $this->canViewEncounter($encounter, $user));
    }

    public function canCreateTheatre(array $encounter, ?array $user = null): bool
    {
        return $this->canMutateTheatre('create_theatre', $encounter, $user);
    }

    public function canEditTheatre(array $encounter, ?array $user = null): bool
    {
        return $this->canMutateTheatre('edit_theatre', $encounter, $user);
    }

    public function canCompleteTheatre(array $encounter, ?array $user = null): bool
    {
        return $this->canMutateTheatre('complete_theatre', $encounter, $user);
    }

    public function canViewPharmacy(int $patientId, ?array $user = null): bool
    {
        return $this->canViewClinicalContext('view_pharmacy', $patientId, $user);
    }

    public function canViewLaboratoryWorklist(?array $user = null): bool
    {
        return $this->canViewDepartmentWorklist(
            $user,
            ['Laboratory Scientist'],
            ['Laboratory']
        );
    }

    public function canViewRadiologyWorklist(?array $user = null): bool
    {
        return $this->canViewDepartmentWorklist(
            $user,
            ['Radiographer'],
            ['Radiology', 'X-Ray']
        );
    }

    public function canViewPhysiotherapyWorklist(?array $user = null): bool
    {
        return $this->canViewDepartmentWorklist(
            $user,
            ['Physiotherapist'],
            ['Physiotherapy', 'Physio', 'Rehabilitation']
        );
    }

    public function canViewPharmacyWorklist(?array $user = null): bool
    {
        return $this->canViewDepartmentWorklist(
            $user,
            ['Pharmacist'],
            ['Pharmacy']
        );
    }

    public function canCreatePrescription(
        array $encounter,
        ?array $user = null,
        string $source = 'Clinical'
    ): bool {
        return $this->canMutatePharmacy('create_prescription', $encounter, $user, $source);
    }

    public function canEditPrescription(
        array $encounter,
        ?array $user = null,
        string $source = 'Clinical'
    ): bool {
        return $this->canMutatePharmacy('edit_prescription', $encounter, $user, $source);
    }

    public function canDispensePrescription(
        array $encounter,
        ?array $user = null
    ): bool {
        return $this->canMutatePharmacy('dispense_prescription', $encounter, $user);
    }

    public function canViewBillableItems(?array $user = null): bool
    {
        $user = $user ?? $this->currentUser();
        return $this->isAdministrator($user)
            || $this->hasPermission('view_billable_items', $user);
    }

    public function canViewBilling(?array $user = null): bool
    {
        $user = $user ?? $this->currentUser();
        return $this->isAdministrator($user)
            || $this->hasPermission('view_billing', $user);
    }

    public function canCreatePatientCharge(?array $user = null): bool
    {
        $user = $user ?? $this->currentUser();
        return $this->isAdministrator($user)
            || $this->hasPermission('create_patient_charge', $user);
    }

    public function canCancelPatientCharge(?array $user = null): bool
    {
        $user = $user ?? $this->currentUser();
        return $this->isAdministrator($user)
            || $this->hasPermission('cancel_patient_charge', $user);
    }

    public function canCreateBillingRequest(?array $user = null): bool
    {
        $user = $user ?? $this->currentUser();
        return $this->isAdministrator($user)
            || $this->hasPermission('create_billing_request', $user);
    }

    public function canViewBillingRequests(?array $user = null): bool
    {
        $user = $user ?? $this->currentUser();
        return $this->isAdministrator($user)
            || $this->hasPermission('view_billing_requests', $user);
    }

    public function canReviewBillingRequest(?array $user = null): bool
    {
        $user = $user ?? $this->currentUser();
        return $this->isAdministrator($user)
            || $this->hasPermission('review_billing_request', $user);
    }

    public function canCancelBillingRequest(?array $user = null): bool
    {
        $user = $user ?? $this->currentUser();
        return $this->isAdministrator($user)
            || $this->hasPermission('cancel_billing_request', $user);
    }

    public function canCreateInvoice(?array $user = null): bool
    {
        $user = $user ?? $this->currentUser();
        return $this->isAdministrator($user)
            || $this->hasPermission('create_invoice', $user);
    }

    public function canRecordPayment(?array $user = null): bool
    {
        $user = $user ?? $this->currentUser();
        return $this->isAdministrator($user)
            || $this->hasPermission('record_payment', $user);
    }

    public function canViewReceipts(?array $user = null): bool
    {
        $user = $user ?? $this->currentUser();
        return $this->isAdministrator($user)
            || $this->hasPermission('view_receipts', $user);
    }

    public function canCreateBillableItems(?array $user = null): bool
    {
        $user = $user ?? $this->currentUser();
        return $this->isAdministrator($user)
            || $this->hasPermission('create_billable_items', $user);
    }

    public function canEditBillableItems(?array $user = null): bool
    {
        $user = $user ?? $this->currentUser();
        return $this->isAdministrator($user)
            || $this->hasPermission('edit_billable_items', $user);
    }

    public function canManageBillableItemStatus(?array $user = null): bool
    {
        $user = $user ?? $this->currentUser();
        return $this->isAdministrator($user)
            || $this->hasPermission('manage_billable_item_status', $user);
    }

    public function canViewInventory(?array $user = null): bool
    {
        $user = $user ?? $this->currentUser();
        return $this->isAdministrator($user)
            || $this->hasPermission('view_inventory', $user);
    }

    public function canManageInventoryItems(?array $user = null): bool
    {
        $user = $user ?? $this->currentUser();
        return $this->isAdministrator($user)
            || $this->hasPermission('manage_inventory_items', $user);
    }

    public function canReceiveStock(?array $user = null): bool
    {
        $user = $user ?? $this->currentUser();
        return $this->isAdministrator($user)
            || $this->hasPermission('receive_stock', $user);
    }

    public function canIssueStock(?array $user = null): bool
    {
        $user = $user ?? $this->currentUser();
        return $this->isAdministrator($user)
            || $this->hasPermission('issue_stock', $user);
    }

    public function canReturnStock(?array $user = null): bool
    {
        $user = $user ?? $this->currentUser();
        return $this->isAdministrator($user)
            || $this->hasPermission('return_stock', $user);
    }

    public function canAdjustStock(?array $user = null): bool
    {
        $user = $user ?? $this->currentUser();
        return $this->isAdministrator($user)
            || $this->hasPermission('adjust_stock', $user);
    }

    public function canViewStockLedger(?array $user = null): bool
    {
        $user = $user ?? $this->currentUser();
        return $this->isAdministrator($user)
            || $this->hasPermission('view_stock_ledger', $user);
    }

    public function canViewExternalSales(?array $user = null): bool
    {
        $user = $user ?? $this->currentUser();
        return $this->isAdministrator($user)
            || $this->hasPermission('view_external_sales', $user);
    }

    public function canCreateExternalSale(?array $user = null): bool
    {
        $user = $user ?? $this->currentUser();
        return $this->isAdministrator($user)
            || $this->hasPermission('create_external_sale', $user);
    }

    public function canCancelExternalSale(?array $user = null): bool
    {
        $user = $user ?? $this->currentUser();
        return $this->isAdministrator($user)
            || $this->hasPermission('cancel_external_sale', $user);
    }

    public function canViewExternalSaleReceipts(?array $user = null): bool
    {
        $user = $user ?? $this->currentUser();
        return $this->isAdministrator($user)
            || $this->hasPermission('view_external_sale_receipts', $user);
    }

    public function canViewReports(?array $user = null): bool
    {
        $user = $user ?? $this->currentUser();
        return $this->isAdministrator($user)
            || $this->hasPermission('view_reports', $user);
    }

    public function canViewFinancialReports(?array $user = null): bool
    {
        $user = $user ?? $this->currentUser();
        return $this->isAdministrator($user)
            || $this->hasPermission('view_financial_reports', $user);
    }

    public function canViewInventoryReports(?array $user = null): bool
    {
        $user = $user ?? $this->currentUser();
        return $this->isAdministrator($user)
            || $this->hasPermission('view_inventory_reports', $user);
    }

    public function canViewClinicalReports(?array $user = null): bool
    {
        $user = $user ?? $this->currentUser();
        return $this->isAdministrator($user)
            || $this->hasPermission('view_clinical_reports', $user);
    }

    public function canViewAdmissions(?array $user = null): bool
    {
        $user = $user ?? $this->currentUser();
        return $this->isAdministrator($user)
            || $this->hasPermission('view_admissions', $user);
    }

    public function canCreateAdmission(array $encounter, ?array $user = null): bool
    {
        return $this->canMutateAdmission('create_admission', $encounter, $user);
    }

    public function canTransferAdmission(array $encounter, ?array $user = null): bool
    {
        return $this->canMutateAdmission('transfer_admission', $encounter, $user);
    }

    public function canDischargeAdmission(array $encounter, ?array $user = null): bool
    {
        return $this->canMutateAdmission('discharge_admission', $encounter, $user);
    }

    public function canManageWardsBeds(?array $user = null): bool
    {
        $user = $user ?? $this->currentUser();
        return $this->isAdministrator($user)
            || $this->hasPermission('manage_wards_beds', $user);
    }

    /*
    |--------------------------------------------------------------------------
    | Consultation Authorization
    |--------------------------------------------------------------------------
    */

    public function canViewConsultation(array $encounter, ?array $user = null): bool
    {
        $user = $user ?? $this->currentUser();
        return $this->isAdministrator($user)
            || ($this->hasPermission('view_consultation', $user)
                && $this->canViewEncounter($encounter, $user));
    }

    public function canCreateConsultation(array $encounter, ?array $user = null): bool
    {
        return $this->canMutateConsultation('create_consultation', $encounter, $user);
    }

    public function canEditConsultation(array $encounter, ?array $user = null): bool
    {
        return $this->canMutateConsultation('edit_consultation', $encounter, $user);
    }

    public function canCompleteConsultation(array $encounter, ?array $user = null): bool
    {
        return $this->canMutateConsultation('complete_consultation', $encounter, $user);
    }

    public function logPatientDenied(
        ?int $userId,
        int $patientId,
        string $action,
        string $description
    ): void {
        require_once __DIR__ . '/AuditService.php';

        (new AuditService($this->pdo))->logPatient(
            $userId,
            $patientId,
            null,
            'Security',
            $action,
            $description,
            null,
            'WARNING',
            $action
        );
    }

    public function canAccessDepartment(
        int $departmentId,
        ?array $user = null
    ): bool {
        $user = $user ?? $this->currentUser();

        if (!$user || $departmentId <= 0) {
            return false;
        }

        if ($this->isAdministrator($user)) {
            return true;
        }

        if ($this->hasActiveDepartmentAssignment($departmentId, $user)) {
            return true;
        }

        return (int)($user['department_id'] ?? 0) === $departmentId;
    }

    public function canAccessEncounter(
        array $encounter,
        ?array $user = null
    ): bool {
        return $this->canViewEncounter($encounter, $user);
    }

    public function canTransferEncounter(
        array $encounter,
        ?array $user = null
    ): bool {
        $user = $user ?? $this->currentUser();

        return $this->hasPermission('transfer_encounter', $user)
            && ($encounter['current_department_received_status'] ?? '')
                === 'Received'
            && $this->isEditable($encounter);
    }

    public function canCancelEncounter(
        array $encounter,
        ?array $user = null
    ): bool {
        $user = $user ?? $this->currentUser();

        if (!$user || !$this->isEditable($encounter)) {
            return false;
        }

        if ($this->isAdministrator($user)) {
            return true;
        }

        return $this->roleMatches($user, ['Receptionist', 'Records Officer', 'Doctor']);
    }

    public function canReopenEncounter(
        array $encounter,
        ?array $user = null
    ): bool {
        $user = $user ?? $this->currentUser();

        if (!$user || (string)($encounter['visit_status'] ?? '') !== 'Completed') {
            return false;
        }

        if ($this->isAdministrator($user)) {
            return true;
        }

        if (!$this->hasPermission('reopen_encounter', $user)) {
            return false;
        }

        if ($this->roleMatches($user, ['Records Officer'])) {
            return true;
        }

        return $this->roleMatches($user, ['Doctor'])
            && (int)($encounter['attending_doctor_id'] ?? 0) > 0
            && (int)($encounter['attending_doctor_id'] ?? 0)
                === (int)($user['id'] ?? 0);
    }

    public function canReceiveEncounter(
        array $encounter,
        ?array $pendingTransfer = null,
        ?array $user = null
    ): bool {
        $user = $user ?? $this->currentUser();

        if (!$this->hasPermission('receive_encounter', $user)
            || !$this->isEditable($encounter)
            || !$this->sameCurrentDepartment($encounter, $user)
        ) {
            return false;
        }

        return $pendingTransfer !== null
            && (int)$pendingTransfer['to_department_id']
                === (int)$encounter['current_department_id'];
    }

    public function canAssignDoctor(
        array $encounter,
        ?array $user = null
    ): bool {
        $user = $user ?? $this->currentUser();

        return $this->hasPermission('assign_doctor', $user)
            && ($encounter['current_department_received_status'] ?? '')
                === 'Received'
            && $this->isEditable($encounter);
    }

    public function canChangeEncounterStatus(
        array $encounter,
        ?array $user = null
    ): bool {
        $user = $user ?? $this->currentUser();

        return $this->hasPermission(
            'change_encounter_status',
            $user
        )
            && $this->sameCurrentDepartment($encounter, $user)
            && $this->isEditable($encounter);
    }

    public function canEditEncounter(
        array $encounter,
        ?array $user = null
    ): bool {
        $user = $user ?? $this->currentUser();

        return $this->hasPermission('edit_encounter', $user)
            && $this->sameCurrentDepartment($encounter, $user)
            && $this->isEditable($encounter);
    }

    public function canViewEncounter(
        array $encounter,
        ?array $user = null
    ): bool {
        $user = $user ?? $this->currentUser();

        return $user !== null;
    }

    public function canManageUsers(?array $user = null): bool
    {
        return $this->hasPermission(
            'manage_users',
            $user ?? $this->currentUser()
        );
    }

    public function canManageSettings(?array $user = null): bool
    {
        return $this->hasPermission(
            'manage_settings',
            $user ?? $this->currentUser()
        );
    }

    public function isAdministrator(?array $user = null): bool
    {
        $user = $user ?? $this->currentUser();

        return ($user['role_name'] ?? '') === 'System Administrator';
    }

    public function logDenied(
        ?int $userId,
        ?int $visitId,
        string $action,
        string $description
    ): void {
        require_once __DIR__ . '/AuditService.php';

        (new AuditService($this->pdo))->log(
            $userId,
            $visitId,
            'Security',
            $action,
            $description
        );
    }

    private function canPerformClinicalSafetyAction(
        string $permission,
        int $patientId,
        array $allowedRoles,
        ?array $user
    ): bool {
        $user = $user ?? $this->currentUser();
        if ($this->isAdministrator($user)) {
            return true;
        }
        return $patientId > 0
            && $user !== null
            && in_array((string)($user['role_name'] ?? ''), $allowedRoles, true)
            && $this->hasPermission($permission, $user)
            && $this->canViewClinicalSafety($patientId, $user);
    }

    private function sameCurrentDepartment(
        array $encounter,
        ?array $user
    ): bool {
        if (!$user) {
            return false;
        }

        if ($this->isAdministrator($user)) {
            return true;
        }

        $departmentId = (int)($encounter['current_department_id'] ?? 0);

        if ($this->hasActiveDepartmentAssignment($departmentId, $user)) {
            return true;
        }

        return $departmentId === (int)($user['department_id'] ?? 0);
    }

    private function hasTreatmentRelationship(
        int $patientId,
        array $user
    ): bool {
        $userId = (int)($user['id'] ?? 0);
        $departmentId = $this->activeDepartmentId($user);

        if ($userId <= 0 || $departmentId <= 0) {
            return false;
        }

        try {
            $stmt = $this->pdo->prepare('
                SELECT 1
                FROM visits v
                WHERE v.patient_id = :patient_id
                  AND v.visit_status NOT IN (\'Completed\', \'Cancelled\')
                  AND (
                      v.current_department_id = :department_id
                      OR v.attending_doctor_id = :user_id
                  )
                LIMIT 1
            ');
            $stmt->execute([
                ':patient_id' => $patientId,
                ':department_id' => $departmentId,
                ':user_id' => $userId
            ]);

            return (bool)$stmt->fetchColumn();
        } catch (Throwable $exception) {
            return false;
        }
    }

    private function canMutateTheatre(
        string $permission,
        array $encounter,
        ?array $user
    ): bool {
        $user = $user ?? $this->currentUser();
        return $this->isAdministrator($user)
            || ($this->hasPermission($permission, $user)
                && $this->canViewEncounter($encounter, $user)
                && $this->isEditable($encounter));
    }

    private function activeDepartmentId(array $user): int
    {
        $activeDepartmentId = (int)(
            $user['active_department_id']
            ?? $_SESSION['active_department_id']
            ?? 0
        );

        if ($activeDepartmentId > 0
            && $this->hasActiveDepartmentAssignment($activeDepartmentId, $user)
        ) {
            return $activeDepartmentId;
        }

        return (int)($user['department_id'] ?? 0);
    }

    private function activeDepartmentName(array $user): string
    {
        return (string)(
            $user['active_department_name']
            ?? $_SESSION['active_department_name']
            ?? $user['department_name']
            ?? ''
        );
    }

    private function canViewDepartmentWorklist(
        ?array $user,
        array $ownerRoles,
        array $ownerDepartments
    ): bool {
        $user = $user ?? $this->currentUser();
        if (!$user) {
            return false;
        }

        if ($this->isAdministrator($user)) {
            return true;
        }

        return $this->roleMatches($user, $ownerRoles)
            || in_array($this->activeDepartmentName($user), $ownerDepartments, true);
    }

    private function isEditable(array $encounter): bool
    {
        return !in_array(
            $encounter['visit_status'] ?? null,
            ['Completed', 'Cancelled'],
            true
        );
    }

    private function currentUser(): ?array
    {
        if (!empty($_SESSION['user'])) {
            return $_SESSION['user'];
        }

        $config = require __DIR__ . '/../config/app.php';

        if (($config['app']['environment'] ?? 'production') === 'development') {
            return [
                'id' => 1,
                'role_name' => 'System Administrator',
                'department_name' => 'Administrator',
                'department_id' => 1
            ];
        }

        return null;
    }

    private function databasePermissionResult(
        string $permission,
        array $user
    ): ?bool {
        $roleId = (int)($user['role_id'] ?? 0);

        if ($roleId <= 0) {
            return null;
        }

        try {
            $stmt = $this->pdo->prepare('
                SELECT p.id, rp.id AS role_permission_id, r.is_active AS role_active
                FROM permissions p
                INNER JOIN roles r ON r.id = :role_id
                LEFT JOIN role_permissions rp
                    ON rp.permission_id = p.id
                   AND rp.role_id = r.id
                WHERE p.permission_key = :permission_key
                LIMIT 1
            ');
            $stmt->execute([
                ':role_id' => $roleId,
                ':permission_key' => $permission
            ]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                return null;
            }

            return !empty($row['role_permission_id'])
                && (bool)$row['role_active'];
        } catch (Throwable $exception) {
            return null;
        }
    }

    private function hasActiveDepartmentAssignment(
        int $departmentId,
        array $user
    ): bool {
        $activeDepartmentId = (int)(
            $user['active_department_id']
            ?? $_SESSION['active_department_id']
            ?? 0
        );

        if ($activeDepartmentId <= 0 || $activeDepartmentId !== $departmentId) {
            return false;
        }

        $userId = (int)($user['id'] ?? 0);

        if ($userId <= 0) {
            return false;
        }

        try {
            $stmt = $this->pdo->prepare('
                SELECT 1
                FROM user_departments ud
                INNER JOIN departments d ON d.id = ud.department_id
                WHERE ud.user_id = :user_id
                  AND ud.department_id = :department_id
                  AND ud.is_active = 1
                  AND d.is_active = 1
                LIMIT 1
            ');
            $stmt->execute([
                ':user_id' => $userId,
                ':department_id' => $departmentId
            ]);
            return (bool)$stmt->fetchColumn();
        } catch (Throwable $exception) {
            return false;
        }
    }

    private function roleMatches(array $user, array $canonicalNames): bool
    {
        $roleName = trim((string)($user['role_name'] ?? ''));
        return in_array($roleName, $canonicalNames, true);
    }

    private function isClinicalCrossViewRole(array $user): bool
    {
        return $this->roleMatches($user, [
            'Doctor',
            'Nurse',
            'Laboratory Scientist',
            'Radiographer',
            'Physiotherapist',
            'Theatre Staff',
            'Pharmacist',
            'Records Officer',
        ]);
    }

    private function canViewClinicalContext(
        string $permission,
        int $patientId,
        ?array $user = null
    ): bool {
        $user = $user ?? $this->currentUser();
        if (!$user || $patientId <= 0) {
            return false;
        }

        if ($this->isAdministrator($user)) {
            return true;
        }

        if (!$this->hasPermission($permission, $user)) {
            return false;
        }

        return $this->canViewMedicalRecord($patientId, $user);
    }

    private function canPerformLongitudinalAction(
        string $permission,
        int $patientId,
        array $allowedRoles,
        ?array $user = null
    ): bool {
        $user = $user ?? $this->currentUser();
        if (!$user || $patientId <= 0) {
            return false;
        }
        if ($this->isAdministrator($user)) {
            return true;
        }
        return $this->hasPermission($permission, $user)
            && $this->roleMatches($user, $allowedRoles)
            && $this->canViewMedicalRecord($patientId, $user);
    }

    private function canPerformDocumentAction(
        string $permission,
        int $patientId,
        array $allowedRoles,
        ?array $user = null
    ): bool {
        $user = $user ?? $this->currentUser();
        if (!$user || $patientId <= 0) {
            return false;
        }
        if ($this->isAdministrator($user)) {
            return true;
        }
        return $this->hasPermission($permission, $user)
            && $this->roleMatches($user, $allowedRoles)
            && $this->canViewMedicalRecord($patientId, $user);
    }

    private function canMutateConsultation(
        string $permission,
        array $encounter,
        ?array $user = null
    ): bool {
        $user = $user ?? $this->currentUser();
        if (!$user) {
            return false;
        }

        if (in_array((string)($encounter['visit_status'] ?? ''), ['Completed', 'Cancelled'], true)) {
            return false;
        }

        if ($this->isAdministrator($user)) {
            return true;
        }

        return $this->hasPermission($permission, $user)
            && $this->roleMatches($user, ['Doctor'])
            && $this->canViewEncounter($encounter, $user);
    }

    private function canMutateNursing(
        string $permission,
        array $encounter,
        ?array $user = null
    ): bool {
        $user = $user ?? $this->currentUser();
        if (!$user) {
            return false;
        }

        if ($this->isAdministrator($user)) {
            return true;
        }

        if (in_array((string)($encounter['visit_status'] ?? ''), ['Completed', 'Cancelled'], true)) {
            return false;
        }

        return $this->hasPermission($permission, $user)
            && $this->roleMatches($user, ['Nurse'])
            && $this->canViewEncounter($encounter, $user);
    }

    private function canMutateLaboratory(
        string $permission,
        array $encounter,
        ?array $user = null,
        string $requestSource = 'Clinical'
    ): bool {
        $user = $user ?? $this->currentUser();
        if (!$user) {
            return false;
        }

        if ($this->isAdministrator($user)) {
            return true;
        }

        if (in_array((string)($encounter['visit_status'] ?? ''), ['Completed', 'Cancelled'], true)) {
            return false;
        }

        if (!$this->hasPermission($permission, $user)) {
            return false;
        }

        $source = strtoupper(trim($requestSource));

        return match ($permission) {
            'create_laboratory_request' => $source === 'DIRECT'
                ? $this->roleMatches($user, ['Laboratory Scientist'])
                : $this->roleMatches($user, ['Doctor'])
                    && $this->canViewEncounter($encounter, $user),
            'process_laboratory_request',
            'enter_laboratory_result',
            'edit_laboratory_result',
            'complete_laboratory_request' => $this->roleMatches($user, ['Laboratory Scientist'])
                && $this->canViewLaboratory((int)($encounter['patient_id'] ?? 0), $user),
            default => false
        };
    }

    private function canMutateRadiology(
        string $permission,
        array $encounter,
        ?array $user = null,
        string $requestSource = 'Clinical'
    ): bool {
        $user = $user ?? $this->currentUser();
        if (!$user) {
            return false;
        }

        if ($this->isAdministrator($user)) {
            return true;
        }

        if (in_array((string)($encounter['visit_status'] ?? ''), ['Completed', 'Cancelled'], true)) {
            return false;
        }

        if (!$this->hasPermission($permission, $user)) {
            return false;
        }

        $source = strtoupper(trim($requestSource));

        return match ($permission) {
            'create_radiology_request' => $source === 'DIRECT'
                ? $this->roleMatches($user, ['Radiographer'])
                : $this->roleMatches($user, ['Doctor'])
                    && $this->canViewEncounter($encounter, $user),
            'process_radiology_request',
            'enter_radiology_report',
            'edit_radiology_report',
            'complete_radiology_request' => $this->roleMatches($user, ['Radiographer'])
                && $this->canViewRadiology((int)($encounter['patient_id'] ?? 0), $user),
            default => false
        };
    }

    private function canMutatePharmacy(
        string $permission,
        array $encounter,
        ?array $user = null,
        string $source = 'Clinical'
    ): bool {
        $user = $user ?? $this->currentUser();
        if (!$user) {
            return false;
        }

        if ($this->isAdministrator($user)) {
            return true;
        }

        if (in_array((string)($encounter['visit_status'] ?? ''), ['Completed', 'Cancelled'], true)) {
            return false;
        }

        if (!$this->hasPermission($permission, $user)) {
            return false;
        }

        $source = strtoupper(trim($source));

        return match ($permission) {
            'create_prescription' => $source === 'DIRECT'
                ? $this->roleMatches($user, ['Pharmacist'])
                : $this->roleMatches($user, ['Doctor'])
                    && $this->canViewEncounter($encounter, $user),
            'edit_prescription' => $source === 'DIRECT'
                ? $this->roleMatches($user, ['Pharmacist'])
                : $this->roleMatches($user, ['Doctor'])
                    && $this->canViewEncounter($encounter, $user),
            'dispense_prescription' => $this->roleMatches($user, ['Pharmacist'])
                && $this->canViewPharmacy((int)($encounter['patient_id'] ?? 0), $user),
            default => false
        };
    }

    private function canMutatePhysiotherapy(
        string $permission,
        array $encounter,
        ?array $user = null,
        string $recordSource = 'Clinical'
    ): bool {
        $user = $user ?? $this->currentUser();
        if (!$user) {
            return false;
        }

        if ($this->isAdministrator($user)) {
            return true;
        }

        if (in_array((string)($encounter['visit_status'] ?? ''), ['Completed', 'Cancelled'], true)) {
            return false;
        }

        if (!$this->hasPermission($permission, $user)) {
            return false;
        }

        $source = strtoupper(trim($recordSource));

        return match ($permission) {
            'create_physiotherapy' => $source === 'DIRECT'
                ? $this->roleMatches($user, ['Physiotherapist'])
                : $this->roleMatches($user, ['Doctor'])
                    && $this->canViewEncounter($encounter, $user),
            'edit_physiotherapy',
            'manage_physiotherapy_sessions',
            'complete_physiotherapy' => $this->roleMatches($user, ['Physiotherapist'])
                && $this->canViewPhysiotherapy((int)($encounter['patient_id'] ?? 0), $user),
            default => false
        };
    }

    private function canMutateVitalSigns(
        string $permission,
        array $encounter,
        ?array $user = null
    ): bool {
        $user = $user ?? $this->currentUser();
        if (!$user) {
            return false;
        }

        if (in_array((string)($encounter['visit_status'] ?? ''), ['Completed', 'Cancelled'], true)) {
            return false;
        }

        if ($this->isAdministrator($user)) {
            return true;
        }

        return $this->hasPermission($permission, $user)
            && $this->roleMatches($user, ['Doctor', 'Nurse'])
            && $this->canViewEncounter($encounter, $user);
    }

    private function canMutateAdmission(
        string $permission,
        array $encounter,
        ?array $user = null
    ): bool {
        $user = $user ?? $this->currentUser();
        if (!$user) {
            return false;
        }

        if (in_array((string)($encounter['visit_status'] ?? ''), ['Completed', 'Cancelled'], true)) {
            return false;
        }

        if ($this->isAdministrator($user)) {
            return true;
        }

        return $this->hasPermission($permission, $user)
            && $this->canViewEncounter($encounter, $user);
    }

    private function audit(int $userId, string $action, string $description): void
    {
        require_once __DIR__ . '/AuditService.php';
        (new AuditService($this->pdo))->log(
            $userId,
            null,
            'Administration',
            $action,
            $description
        );
    }

    private function rollback(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }
}
