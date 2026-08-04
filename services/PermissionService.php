<?php

declare(strict_types=1);

class PermissionService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
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

        $role = (string)($user['role_name'] ?? '');
        $department = (string)($user['department_name'] ?? '');

        return match ($permission) {
            'view_encounter' => true,
            'create_encounter' => $department === 'Reception',
            'transfer_encounter' => $department !== '',
            'receive_encounter' => $department !== '',
            'assign_doctor' => $role === 'Doctor'
                || $department === 'Doctor',
            'change_encounter_status' => $department !== '',
            'edit_encounter' => $department !== '',
            'manage_users' => false,
            default => false
        };
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
            && $this->sameCurrentDepartment($encounter, $user)
            && $this->isEditable($encounter);
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
            && $this->sameCurrentDepartment($encounter, $user)
            && ($encounter['department_name'] ?? '') === 'Doctor'
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

        return $this->hasPermission('view_encounter', $user)
            && $this->sameCurrentDepartment($encounter, $user);
    }

    public function canManageUsers(?array $user = null): bool
    {
        return $this->hasPermission(
            'manage_users',
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

        return (int)($encounter['current_department_id'] ?? 0)
            === (int)($user['department_id'] ?? 0);
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
}
