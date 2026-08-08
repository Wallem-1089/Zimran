<?php

declare(strict_types=1);

require_once __DIR__ . '/AuditService.php';
require_once __DIR__ . '/SessionService.php';

class UserDepartmentService
{
    private PDO $pdo;

    private AuditService $auditService;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->auditService = new AuditService($pdo);
    }

    public function assignDepartment(
        int $userId,
        int $departmentId,
        int $assignedBy,
        bool $primary = false
    ): array {
        try {
            $this->pdo->beginTransaction();
            if (!$this->lockUser($userId)) {
                throw new RuntimeException('User not found.');
            }
            if (!$this->lockActiveDepartment($departmentId)) {
                throw new RuntimeException('Department is inactive or unavailable.');
            }

            $existing = $this->membership($userId, $departmentId);
            if ($existing && !empty($existing['is_active'])) {
                throw new RuntimeException('Department is already assigned.');
            }

            if ($existing) {
                $stmt = $this->pdo->prepare('
                    UPDATE user_departments
                    SET is_active = 1, assigned_at = NOW(), assigned_by = :assigned_by
                    WHERE id = :id
                ');
                $stmt->execute([':assigned_by' => $assignedBy, ':id' => $existing['id']]);
            } else {
                $stmt = $this->pdo->prepare('
                    INSERT INTO user_departments (
                        user_id, department_id, is_primary, is_active, assigned_by
                    ) VALUES (:user_id, :department_id, :is_primary, 1, :assigned_by)
                ');
                $stmt->execute([
                    ':user_id' => $userId,
                    ':department_id' => $departmentId,
                    ':is_primary' => $primary ? 1 : 0,
                    ':assigned_by' => $assignedBy
                ]);
            }

            if ($primary) {
                $this->setPrimaryWithinTransaction($userId, $departmentId);
            }

            $this->auditService->log(
                $assignedBy,
                null,
                'Administration',
                'USER_DEPARTMENT_ASSIGNED',
                'Assigned department #' . $departmentId . ' to user #' . $userId . '.'
            );
            $this->pdo->commit();

            return [
                'success' => true,
                'user_id' => $userId,
                'department_id' => $departmentId,
                'errors' => []
            ];
        } catch (Throwable $exception) {
            $this->rollback();
            return ['success' => false, 'errors' => [$exception->getMessage()]];
        }
    }

    public function removeDepartment(
        int $userId,
        int $departmentId,
        int $removedBy
    ): array {
        try {
            $this->pdo->beginTransaction();
            $user = $this->lockUser($userId);
            if (!$user) {
                throw new RuntimeException('User not found.');
            }
            if ((int)$user['department_id'] === $departmentId) {
                throw new RuntimeException('The primary department cannot be removed.');
            }

            $membership = $this->membership($userId, $departmentId);
            if (!$membership || empty($membership['is_active'])) {
                throw new RuntimeException('Department assignment not found.');
            }

            $stmt = $this->pdo->prepare(
                'UPDATE user_departments SET is_active = 0, is_primary = 0 WHERE id = :id'
            );
            $stmt->execute([':id' => $membership['id']]);
            $this->auditService->log(
                $removedBy,
                null,
                'Administration',
                'USER_DEPARTMENT_REMOVED',
                'Removed department #' . $departmentId . ' from user #' . $userId . '.'
            );
            $this->pdo->commit();

            return ['success' => true, 'user_id' => $userId, 'department_id' => $departmentId, 'errors' => []];
        } catch (Throwable $exception) {
            $this->rollback();
            return ['success' => false, 'errors' => [$exception->getMessage()]];
        }
    }

    public function setPrimaryDepartment(
        int $userId,
        int $departmentId,
        int $changedBy
    ): array {
        try {
            $this->pdo->beginTransaction();
            if (!$this->lockUser($userId)) {
                throw new RuntimeException('User not found.');
            }
            if (!$this->lockActiveDepartment($departmentId)) {
                throw new RuntimeException('Department is inactive or unavailable.');
            }
            $membership = $this->membership($userId, $departmentId);
            if (!$membership || empty($membership['is_active'])) {
                throw new RuntimeException('Department must be assigned before becoming primary.');
            }

            $this->setPrimaryWithinTransaction($userId, $departmentId);
            $this->auditService->log(
                $changedBy,
                null,
                'Administration',
                'PRIMARY_DEPARTMENT_CHANGED',
                'Changed primary department for user #' . $userId . ' to #' . $departmentId . '.'
            );
            $this->pdo->commit();

            return ['success' => true, 'user_id' => $userId, 'department_id' => $departmentId, 'errors' => []];
        } catch (Throwable $exception) {
            $this->rollback();
            return ['success' => false, 'errors' => [$exception->getMessage()]];
        }
    }

    public function listUserDepartments(int $userId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT ud.*, d.department_name, d.department_code,
                   d.department_type, d.is_active AS department_is_active
            FROM user_departments ud
            INNER JOIN departments d ON d.id = ud.department_id
            WHERE ud.user_id = :user_id
            ORDER BY ud.is_primary DESC, d.display_order, d.department_name
        ');
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function switchDepartment(
        int $userId,
        int $departmentId,
        int $switchedBy
    ): array {
        $user = $this->user($userId);
        $actor = $this->user($switchedBy);

        if (!$user || !$actor) {
            return ['success' => false, 'errors' => ['User not found.']];
        }

        if ($userId !== $switchedBy && ($actor['role_name'] ?? '') !== 'System Administrator') {
            return ['success' => false, 'errors' => ['You cannot switch another user department.']];
        }

        $membership = $this->membership($userId, $departmentId);
        if (!$membership || empty($membership['is_active'])) {
            return ['success' => false, 'errors' => ['Department is not assigned to this user.']];
        }

        $department = $this->department($departmentId);
        if (!$department || empty($department['is_active'])) {
            return ['success' => false, 'errors' => ['Department is inactive or unavailable.']];
        }

        try {
            $this->pdo->beginTransaction();
            $this->auditService->log(
                $switchedBy,
                null,
                'Administration',
                'ACTIVE_DEPARTMENT_SWITCHED',
                'Switched user #' . $userId . ' to department #' . $departmentId . '.'
            );
            $this->pdo->commit();
        } catch (Throwable $exception) {
            $this->rollback();
            return ['success' => false, 'errors' => ['Unable to switch active department.']];
        }

        $session = new SessionService($this->pdo);
        $session->setActiveDepartment($departmentId, $department['department_name']);

        return [
            'success' => true,
            'user_id' => $userId,
            'department_id' => $departmentId,
            'department_name' => $department['department_name'],
            'errors' => []
        ];
    }

    public function getCurrentDepartment(?int $userId = null): ?array
    {
        $userId ??= (int)($_SESSION['user']['id'] ?? 0);
        $activeId = (int)($_SESSION['active_department_id'] ?? 0);

        if ($activeId > 0) {
            $department = $this->department($activeId);
            if ($department && $this->isAssigned($userId, $activeId)) {
                return $department;
            }
        }

        $user = $this->user($userId);
        return $user ? $this->department((int)$user['department_id']) : null;
    }

    private function setPrimaryWithinTransaction(int $userId, int $departmentId): void
    {
        $this->pdo->prepare(
            'UPDATE user_departments SET is_primary = 0 WHERE user_id = :user_id'
        )->execute([':user_id' => $userId]);
        $this->pdo->prepare(
            'UPDATE user_departments SET is_primary = 1, is_active = 1 WHERE user_id = :user_id AND department_id = :department_id'
        )->execute([':user_id' => $userId, ':department_id' => $departmentId]);
        $this->pdo->prepare(
            'UPDATE users SET department_id = :department_id WHERE id = :user_id'
        )->execute([':department_id' => $departmentId, ':user_id' => $userId]);
    }

    private function lockUser(int $userId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, department_id FROM users WHERE id = :id FOR UPDATE');
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    private function lockActiveDepartment(int $departmentId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, department_name FROM departments WHERE id = :id AND is_active = 1 FOR UPDATE'
        );
        $stmt->execute([':id' => $departmentId]);
        $department = $stmt->fetch(PDO::FETCH_ASSOC);
        return $department ?: null;
    }

    private function membership(int $userId, int $departmentId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM user_departments WHERE user_id = :user_id AND department_id = :department_id LIMIT 1'
        );
        $stmt->execute([':user_id' => $userId, ':department_id' => $departmentId]);
        $membership = $stmt->fetch(PDO::FETCH_ASSOC);
        return $membership ?: null;
    }

    private function isAssigned(int $userId, int $departmentId): bool
    {
        $membership = $this->membership($userId, $departmentId);
        return $membership !== null && !empty($membership['is_active']);
    }

    private function user(int $userId): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT u.id, u.department_id, r.role_name
            FROM users u INNER JOIN roles r ON r.id = u.role_id
            WHERE u.id = :id LIMIT 1
        ');
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    private function department(int $departmentId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM departments WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $departmentId]);
        $department = $stmt->fetch(PDO::FETCH_ASSOC);
        return $department ?: null;
    }

    private function rollback(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }
}
