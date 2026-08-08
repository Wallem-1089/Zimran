<?php

declare(strict_types=1);

require_once __DIR__ . '/AuditService.php';

class DepartmentService
{
    private PDO $pdo;

    private AuditService $auditService;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->auditService = new AuditService($pdo);
    }

    public function createDepartment(array $department, int $createdBy): array
    {
        $errors = $this->validate($department);

        if ($errors) {
            return $this->failure($errors);
        }

        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare('
                INSERT INTO departments (
                    department_name, department_code, description, location,
                    contact_extension, department_type, queue_enabled,
                    is_active, display_order
                ) VALUES (
                    :name, :code, :description, :location, :extension,
                    :type, :queue_enabled, 1, :display_order
                )
            ');
            $stmt->execute($this->parameters($department));
            $departmentId = (int)$this->pdo->lastInsertId();

            $this->audit(
                $createdBy,
                'DEPARTMENT_CREATED',
                'Created department #' . $departmentId . '.'
            );
            $this->pdo->commit();

            return [
                'success' => true,
                'department_id' => $departmentId,
                'errors' => []
            ];
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure(['Unable to create department.']);
        }
    }

    public function updateDepartment(
        int $departmentId,
        array $department,
        int $updatedBy
    ): array {
        $errors = $this->validate($department, $departmentId);

        if ($errors) {
            return $this->failure($errors);
        }

        try {
            $this->pdo->beginTransaction();
            if (!$this->lockDepartment($departmentId)) {
                throw new RuntimeException('Department not found.');
            }

            $stmt = $this->pdo->prepare('
                UPDATE departments
                SET department_name = :name,
                    department_code = :code,
                    description = :description,
                    location = :location,
                    contact_extension = :extension,
                    department_type = :type,
                    queue_enabled = :queue_enabled,
                    display_order = :display_order
                WHERE id = :id
            ');
            $parameters = $this->parameters($department);
            $parameters[':id'] = $departmentId;
            $stmt->execute($parameters);

            $this->audit(
                $updatedBy,
                'DEPARTMENT_UPDATED',
                'Updated department #' . $departmentId . '.'
            );
            $this->pdo->commit();

            return [
                'success' => true,
                'department_id' => $departmentId,
                'errors' => []
            ];
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure(['Unable to update department.']);
        }
    }

    public function activateDepartment(int $departmentId, int $updatedBy): array
    {
        return $this->setActive($departmentId, true, $updatedBy);
    }

    public function deactivateDepartment(int $departmentId, int $updatedBy): array
    {
        return $this->setActive($departmentId, false, $updatedBy);
    }

    public function getDepartment(int $departmentId): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT d.*,
                (SELECT COUNT(*) FROM user_departments ud INNER JOIN users u ON u.id = ud.user_id WHERE ud.department_id = d.id AND ud.is_active = 1 AND u.status = \'Active\') AS active_users,
                (SELECT COUNT(*) FROM user_departments ud INNER JOIN users u ON u.id = ud.user_id WHERE ud.department_id = d.id AND ud.is_active = 1 AND u.status = \'Inactive\') AS inactive_users,
                (SELECT COUNT(*) FROM visits v WHERE v.current_department_id = d.id AND v.visit_status NOT IN (\'Completed\', \'Cancelled\')) AS active_encounters
            FROM departments d
            WHERE d.id = :id
            LIMIT 1
        ');
        $stmt->execute([':id' => $departmentId]);
        $department = $stmt->fetch(PDO::FETCH_ASSOC);
        return $department ?: null;
    }

    public function listDepartments(bool $includeInactive = true): array
    {
        $sql = '
            SELECT d.*,
                (SELECT COUNT(*) FROM user_departments ud INNER JOIN users u ON u.id = ud.user_id WHERE ud.department_id = d.id AND ud.is_active = 1 AND u.status = \'Active\') AS active_users,
                (SELECT COUNT(*) FROM user_departments ud INNER JOIN users u ON u.id = ud.user_id WHERE ud.department_id = d.id AND ud.is_active = 1 AND u.status = \'Inactive\') AS inactive_users,
                (SELECT COUNT(*) FROM visits v WHERE v.current_department_id = d.id AND v.visit_status NOT IN (\'Completed\', \'Cancelled\')) AS active_encounters
            FROM departments d
        ';

        if (!$includeInactive) {
            $sql .= ' WHERE d.is_active = 1';
        }

        $sql .= ' ORDER BY d.display_order, d.department_name';
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function searchDepartments(string $search = ''): array
    {
        $stmt = $this->pdo->prepare('
            SELECT d.*,
                (SELECT COUNT(*) FROM user_departments ud INNER JOIN users u ON u.id = ud.user_id WHERE ud.department_id = d.id AND ud.is_active = 1 AND u.status = \'Active\') AS active_users,
                (SELECT COUNT(*) FROM user_departments ud INNER JOIN users u ON u.id = ud.user_id WHERE ud.department_id = d.id AND ud.is_active = 1 AND u.status = \'Inactive\') AS inactive_users,
                (SELECT COUNT(*) FROM visits v WHERE v.current_department_id = d.id AND v.visit_status NOT IN (\'Completed\', \'Cancelled\')) AS active_encounters
            FROM departments d
            WHERE d.department_name LIKE :search_name
               OR d.department_code LIKE :search_code
               OR d.department_type LIKE :search_type
            ORDER BY d.display_order, d.department_name
        ');
        $term = '%' . trim($search) . '%';
        $stmt->execute([
            ':search_name' => $term,
            ':search_code' => $term,
            ':search_type' => $term
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function validate(array $department, ?int $exceptId = null): array
    {
        $errors = [];
        $name = trim((string)($department['department_name'] ?? ''));
        $code = trim((string)($department['department_code'] ?? ''));
        $type = (string)($department['department_type'] ?? 'Support');

        if ($name === '') {
            $errors[] = 'Department name is required.';
        }
        if ($code === '') {
            $errors[] = 'Department code is required.';
        }
        if (!in_array($type, ['Clinical', 'Administrative', 'Diagnostic', 'Support'], true)) {
            $errors[] = 'Invalid department type.';
        }
        if ($this->exists('department_name', $name, $exceptId)) {
            $errors[] = 'Department name already exists.';
        }
        if ($this->exists('department_code', $code, $exceptId)) {
            $errors[] = 'Department code already exists.';
        }
        return $errors;
    }

    private function parameters(array $department): array
    {
        return [
            ':name' => trim((string)$department['department_name']),
            ':code' => trim((string)$department['department_code']),
            ':description' => $this->nullable($department['description'] ?? null),
            ':location' => $this->nullable($department['location'] ?? null),
            ':extension' => $this->nullable($department['contact_extension'] ?? null),
            ':type' => $department['department_type'] ?? 'Support',
            ':queue_enabled' => !empty($department['queue_enabled']) ? 1 : 0,
            ':display_order' => (int)($department['display_order'] ?? 0)
        ];
    }

    private function setActive(int $departmentId, bool $active, int $actorId): array
    {
        try {
            $this->pdo->beginTransaction();
            if (!$this->lockDepartment($departmentId)) {
                throw new RuntimeException('Department not found.');
            }
            $stmt = $this->pdo->prepare(
                'UPDATE departments SET is_active = :active WHERE id = :id'
            );
            $stmt->execute([':active' => $active ? 1 : 0, ':id' => $departmentId]);
            $this->audit(
                $actorId,
                $active ? 'DEPARTMENT_ACTIVATED' : 'DEPARTMENT_DEACTIVATED',
                ($active ? 'Activated' : 'Deactivated') . ' department #' . $departmentId . '.'
            );
            $this->pdo->commit();
            return ['success' => true, 'department_id' => $departmentId, 'errors' => []];
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure(['Unable to update department status.']);
        }
    }

    private function exists(string $column, string $value, ?int $exceptId): bool
    {
        $sql = 'SELECT id FROM departments WHERE ' . $column . ' = :value';
        $parameters = [':value' => $value];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $parameters[':id'] = $exceptId;
        }
        $stmt = $this->pdo->prepare($sql . ' LIMIT 1');
        $stmt->execute($parameters);
        return (bool)$stmt->fetchColumn();
    }

    private function lockDepartment(int $departmentId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM departments WHERE id = :id FOR UPDATE'
        );
        $stmt->execute([':id' => $departmentId]);
        $department = $stmt->fetch(PDO::FETCH_ASSOC);
        return $department ?: null;
    }

    private function audit(int $userId, string $action, string $description): void
    {
        $this->auditService->log($userId, null, 'Administration', $action, $description);
    }

    private function nullable(mixed $value): ?string
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
