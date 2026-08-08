<?php

declare(strict_types=1);

require_once __DIR__ . '/AuditService.php';

class RoleService
{
    private PDO $pdo;

    private AuditService $auditService;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->auditService = new AuditService($pdo);
    }

    public function createRole(
        string $roleName,
        ?string $description,
        int $createdBy
    ): array {
        $roleName = trim($roleName);

        if ($roleName === '') {
            return $this->failure(['Role name is required.']);
        }

        if ($this->roleNameExists($roleName)) {
            return $this->failure(['Role name already exists.']);
        }

        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare('
                INSERT INTO roles (role_name, description, is_active)
                VALUES (:role_name, :description, 1)
            ');
            $stmt->execute([
                ':role_name' => $roleName,
                ':description' => $this->nullableString($description)
            ]);

            $roleId = (int)$this->pdo->lastInsertId();
            $this->auditService->log(
                $createdBy,
                null,
                'Administration',
                'ROLE_CREATED',
                'Created role #' . $roleId . '.'
            );
            $this->pdo->commit();

            return ['success' => true, 'role_id' => $roleId, 'errors' => []];
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure(['Unable to create role.']);
        }
    }

    public function updateRole(
        int $roleId,
        string $roleName,
        ?string $description,
        int $updatedBy
    ): array {
        $roleName = trim($roleName);

        if ($roleName === '') {
            return $this->failure(['Role name is required.']);
        }

        if ($this->roleNameExists($roleName, $roleId)) {
            return $this->failure(['Role name already exists.']);
        }

        try {
            $this->pdo->beginTransaction();
            if (!$this->lockRole($roleId)) {
                throw new RuntimeException('Role not found.');
            }

            $stmt = $this->pdo->prepare('
                UPDATE roles
                SET role_name = :role_name, description = :description
                WHERE id = :id
            ');
            $stmt->execute([
                ':role_name' => $roleName,
                ':description' => $this->nullableString($description),
                ':id' => $roleId
            ]);

            $this->auditService->log(
                $updatedBy,
                null,
                'Administration',
                'ROLE_UPDATED',
                'Updated role #' . $roleId . '.'
            );
            $this->pdo->commit();

            return ['success' => true, 'role_id' => $roleId, 'errors' => []];
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure(['Unable to update role.']);
        }
    }

    public function activateRole(int $roleId, int $updatedBy): array
    {
        return $this->setActive($roleId, true, $updatedBy);
    }

    public function deactivateRole(int $roleId, int $updatedBy): array
    {
        return $this->setActive($roleId, false, $updatedBy);
    }

    public function getRole(int $roleId): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT id, role_name, description, is_active, created_at, updated_at
            FROM roles
            WHERE id = :id
            LIMIT 1
        ');
        $stmt->execute([':id' => $roleId]);
        $role = $stmt->fetch(PDO::FETCH_ASSOC);

        return $role ?: null;
    }

    public function searchRoles(string $search = ''): array
    {
        $stmt = $this->pdo->prepare('
            SELECT id, role_name, description, is_active, created_at, updated_at
            FROM roles
            WHERE role_name LIKE :search_name
               OR description LIKE :search_description
            ORDER BY role_name
        ');
        $term = '%' . trim($search) . '%';
        $stmt->execute([
            ':search_name' => $term,
            ':search_description' => $term
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listRoles(bool $includeInactive = true): array
    {
        if ($includeInactive) {
            return $this->pdo->query(
                'SELECT id, role_name, description, is_active, created_at, updated_at FROM roles ORDER BY role_name'
            )->fetchAll(PDO::FETCH_ASSOC);
        }

        $stmt = $this->pdo->prepare(
            'SELECT id, role_name, description, is_active, created_at, updated_at FROM roles WHERE is_active = 1 ORDER BY role_name'
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function setActive(int $roleId, bool $active, int $actorId): array
    {
        try {
            $this->pdo->beginTransaction();
            if (!$this->lockRole($roleId)) {
                throw new RuntimeException('Role not found.');
            }

            $stmt = $this->pdo->prepare(
                'UPDATE roles SET is_active = :is_active WHERE id = :id'
            );
            $stmt->execute([
                ':is_active' => $active ? 1 : 0,
                ':id' => $roleId
            ]);

            $this->auditService->log(
                $actorId,
                null,
                'Administration',
                $active ? 'ROLE_ACTIVATED' : 'ROLE_DEACTIVATED',
                ($active ? 'Activated' : 'Deactivated') . ' role #' . $roleId . '.'
            );
            $this->pdo->commit();

            return ['success' => true, 'role_id' => $roleId, 'errors' => []];
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure(['Unable to update role status.']);
        }
    }

    private function roleNameExists(string $roleName, ?int $exceptId = null): bool
    {
        $sql = 'SELECT id FROM roles WHERE role_name = :role_name';
        $parameters = [':role_name' => $roleName];

        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $parameters[':id'] = $exceptId;
        }

        $sql .= ' LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parameters);
        return (bool)$stmt->fetchColumn();
    }

    private function lockRole(int $roleId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM roles WHERE id = :id FOR UPDATE'
        );
        $stmt->execute([':id' => $roleId]);
        $role = $stmt->fetch(PDO::FETCH_ASSOC);
        return $role ?: null;
    }

    private function nullableString(?string $value): ?string
    {
        $value = trim((string)$value);
        return $value === '' ? null : $value;
    }

    private function failure(array $errors): array
    {
        return ['success' => false, 'errors' => $errors];
    }

    private function rollback(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }
}
