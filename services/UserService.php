<?php

declare(strict_types=1);

require_once __DIR__ . '/AuditService.php';

class UserService
{
    private const PROTECTED_ADMIN_USERNAMES = ['admin', 'walter'];

    /**
     * PDO database connection.
     *
     * @var PDO
     */
    private PDO $db;

    private AuditService $auditService;

    /**
     * Constructor.
     *
     * @param PDO $db
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->auditService = new AuditService($db);
    }

    /**
     * Find a user by username or employee ID.
     *
     * @param string $login
     * @return array|null
     */
    public function findByLogin(string $login): ?array
    {
        $sql = "
            SELECT
                u.*,
                d.department_name,
                r.role_name
            FROM users u
            INNER JOIN departments d
                ON u.department_id = d.id
            INNER JOIN roles r
                ON u.role_id = r.id
            WHERE
                u.employee_id = :employee_id
                OR u.username = :username
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':employee_id' => trim($login),
            ':username'    => trim($login)
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    /**
     * Find a user by ID.
     *
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                u.*,
                d.department_name,
                r.role_name
            FROM users u
            INNER JOIN departments d
                ON u.department_id = d.id
            INNER JOIN roles r
                ON u.role_id = r.id
            WHERE u.id = :id
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $id
        ]);

        $user = $stmt->fetch();

        return $user ?: null;
    }

    /**
     * Update the last successful login.
     *
     * @param int $userId
     * @return void
     */
    public function updateLastLogin(int $userId): void
    {
        $stmt = $this->db->prepare("
            UPDATE users
            SET
                last_login = NOW(),
                failed_login_attempts = 0,
                last_failed_login = NULL
            WHERE id = :id
        ");

        $stmt->execute([
            ':id' => $userId
        ]);
    }

    /**
     * Record a failed login attempt.
     *
     * @param int $userId
     * @return array{attempts:int,remaining:int,locked:bool}
     */
    public function recordFailedLogin(int $userId, int $maxAttempts = 10): array
    {
        $maxAttempts = max(1, $maxAttempts);

        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare(
                'SELECT failed_login_attempts FROM users WHERE id = :id FOR UPDATE'
            );
            $stmt->execute([':id' => $userId]);
            $attempts = $stmt->fetchColumn();

            if ($attempts === false) {
                throw new RuntimeException('User not found.');
            }

            $attempts = (int)$attempts + 1;
            $locked = $attempts >= $maxAttempts;
            $remaining = max(0, $maxAttempts - $attempts);
            $update = $this->db->prepare('
                UPDATE users
                SET failed_login_attempts = :attempts,
                    last_failed_login = NOW(),
                    locked_at = CASE WHEN :locked_at_flag = 1 THEN NOW() ELSE locked_at END,
                    lock_reason = CASE WHEN :locked_reason_flag = 1 THEN :reason ELSE lock_reason END
                WHERE id = :id
            ');
            $update->execute([
                ':attempts' => $attempts,
                ':locked_at_flag' => $locked ? 1 : 0,
                ':locked_reason_flag' => $locked ? 1 : 0,
                ':reason' => 'Automatic lockout after repeated failed login attempts.',
                ':id' => $userId
            ]);

            if ($locked) {
                $this->auditService->log(
                    $userId,
                    null,
                    'Security',
                    'ACCOUNT_LOCKED',
                    'Account automatically locked after repeated failed login attempts.',
                    null,
                    'WARNING',
                    'ACCOUNT_LOCKED'
                );
            }

            $this->db->commit();
            return [
                'attempts' => $attempts,
                'remaining' => $remaining,
                'locked' => $locked,
            ];
        } catch (Throwable $exception) {
            $this->rollback();
            return [
                'attempts' => 0,
                'remaining' => 0,
                'locked' => false,
            ];
        }
    }

    /**
     * Change a user's password.
     *
     * @param int $userId
     * @param string $hashedPassword
     * @return void
     */
    public function updatePassword(
        int $userId,
        string $hashedPassword
    ): void {

        try {
            $this->db->beginTransaction();
            $lock = $this->db->prepare('SELECT id FROM users WHERE id = :id FOR UPDATE');
            $lock->execute([':id' => $userId]);
            if (!$lock->fetchColumn()) {
                throw new RuntimeException('User not found.');
            }

            $stmt = $this->db->prepare('
                UPDATE users
                SET password = :password,
                    password_changed_at = NOW(),
                    must_change_password = 0
                WHERE id = :id
            ');
            $stmt->execute([':password' => $hashedPassword, ':id' => $userId]);

            $history = $this->db->prepare('
                INSERT INTO password_history (user_id, password_hash, change_type, changed_by)
                VALUES (:user_id, :password_hash, \'Changed\', :changed_by)
            ');
            $history->execute([
                ':user_id' => $userId,
                ':password_hash' => $hashedPassword,
                ':changed_by' => $userId
            ]);
            $this->auditService->log(
                $userId,
                null,
                'Security',
                'PASSWORD_CHANGED',
                'User password changed.',
                null,
                'INFO',
                'PASSWORD_CHANGED'
            );
            $this->db->commit();
        } catch (Throwable $exception) {
            $this->rollback();
        }
    }

    /**
     * Return all active users.
     *
     * @return array
     */
    public function getActiveUsers(): array
    {
        $stmt = $this->db->query("
            SELECT
                u.id,
                u.employee_id,
                u.first_name,
                u.last_name,
                u.username,
                d.department_name,
                r.role_name
            FROM users u
            INNER JOIN departments d
                ON u.department_id = d.id
            INNER JOIN roles r
                ON u.role_id = r.id
            WHERE u.status = 'Active'
            ORDER BY
                u.first_name,
                u.last_name
        ");

        return $stmt->fetchAll();
    }

    /**
     * Check whether a username already exists.
     *
     * @param string $username
     * @return bool
     */
    public function usernameExists(string $username): bool
    {
        $stmt = $this->db->prepare("
            SELECT id
            FROM users
            WHERE username = :username
            LIMIT 1
        ");

        $stmt->execute([
            ':username' => trim($username)
        ]);

        return (bool) $stmt->fetch();
    }

    /**
     * Check whether an employee ID already exists.
     *
     * @param string $employeeId
     * @return bool
     */
    public function employeeIdExists(string $employeeId): bool
    {
        $stmt = $this->db->prepare("
            SELECT id
            FROM users
            WHERE employee_id = :employee_id
            LIMIT 1
        ");

        $stmt->execute([
            ':employee_id' => trim($employeeId)
        ]);

        return (bool) $stmt->fetch();
    }

    /**
     * Return users for administration screens.
     */
    public function getUsers(array $filters = []): array
    {
        $where = [];
        $parameters = [];

        if (!empty($filters['search'])) {
            $where[] = '(
                u.employee_id LIKE :search_employee
                OR u.username LIKE :search_username
                OR u.first_name LIKE :search_first
                OR u.last_name LIKE :search_last
            )';
            $search = '%' . trim((string)$filters['search']) . '%';
            $parameters[':search_employee'] = $search;
            $parameters[':search_username'] = $search;
            $parameters[':search_first'] = $search;
            $parameters[':search_last'] = $search;
        }

        if (!empty($filters['status'])) {
            $where[] = 'u.status = :status';
            $parameters[':status'] = $filters['status'];
        }

        $sql = '
            SELECT
                u.id,
                u.employee_id,
                u.first_name,
                u.last_name,
                u.gender,
                u.phone,
                u.email,
                u.username,
                u.department_id,
                u.role_id,
                u.status,
                u.failed_login_attempts,
                u.last_failed_login,
                u.locked_at,
                u.lock_reason,
                u.last_login,
                u.must_change_password,
                u.created_at,
                d.department_name,
                r.role_name
            FROM users u
            INNER JOIN departments d ON d.id = u.department_id
            INNER JOIN roles r ON r.id = u.role_id
        ';

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY u.first_name, u.last_name, u.id';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($parameters);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRoles(): array
    {
        return $this->db->query(
            'SELECT id, role_name, description FROM roles ORDER BY role_name'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDepartments(): array
    {
        return $this->db->query(
            'SELECT id, department_name, description
             FROM departments
             WHERE is_active = 1
             ORDER BY department_name'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Backward-compatible descriptive alias for findById().
     */
    public function getUserById(int $userId): ?array
    {
        return $this->findById($userId);
    }

    public function getPasswordHistory(int $userId): array
    {
        $stmt = $this->db->prepare('
            SELECT ph.id, ph.user_id, ph.change_type, ph.changed_by,
                   ph.created_at, u.username AS changed_by_username
            FROM password_history ph
            LEFT JOIN users u ON u.id = ph.changed_by
            WHERE ph.user_id = :user_id
            ORDER BY ph.created_at DESC, ph.id DESC
        ');
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create a user and record the administrative audit event atomically.
     */
    public function createUser(array $user, int $createdBy): array
    {
        $errors = $this->validateUserInput($user, true);

        if ($this->roleHasName((int)($user['role_id'] ?? 0), 'Super Administrator')
            && !$this->actorIsSuperAdministrator($createdBy)
        ) {
            $errors[] = 'Only a Super Administrator can create another Super Administrator account.';
        }

        if ($errors) {
            return $this->failure($errors);
        }

        if ($this->usernameExists((string)$user['username'])) {
            $errors[] = 'Username already exists.';
        }

        if ($this->employeeIdExists((string)$user['employee_id'])) {
            $errors[] = 'Employee ID already exists.';
        }

        if ($errors) {
            return $this->failure($errors);
        }

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare('
                INSERT INTO users (
                    employee_id, first_name, last_name, gender, phone, email,
                    username, password, department_id, role_id, status,
                    must_change_password
                ) VALUES (
                    :employee_id, :first_name, :last_name, :gender, :phone,
                    :email, :username, :password, :department_id, :role_id,
                    :status, :must_change_password
                )
            ');

            $stmt->execute([
                ':employee_id' => trim((string)$user['employee_id']),
                ':first_name' => trim((string)$user['first_name']),
                ':last_name' => trim((string)$user['last_name']),
                ':gender' => $this->nullableString($user['gender'] ?? null),
                ':phone' => $this->nullableString($user['phone'] ?? null),
                ':email' => $this->nullableString($user['email'] ?? null),
                ':username' => trim((string)$user['username']),
                ':password' => password_hash(
                    (string)$user['password'],
                    PASSWORD_DEFAULT
                ),
                ':department_id' => (int)$user['department_id'],
                ':role_id' => (int)$user['role_id'],
                ':status' => $user['status'] ?? 'Active',
                ':must_change_password' => !empty($user['must_change_password']) ? 1 : 0
            ]);

            $userId = (int)$this->db->lastInsertId();

            $this->synchronizePrimaryDepartment(
                $userId,
                (int)$user['department_id'],
                $createdBy
            );

            $this->auditService->log(
                $createdBy,
                null,
                'Administration',
                'USER_CREATED',
                'Created user account #' . $userId . '.'
            );

            $this->db->commit();

            return [
                'success' => true,
                'user_id' => $userId,
                'errors' => []
            ];
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure(['Unable to create user.']);
        }
    }

    /**
     * Update user identity, role, department and contact information.
     */
    public function updateUser(
        int $userId,
        array $user,
        int $updatedBy
    ): array {
        $errors = $this->validateUserInput($user, false);

        if ($this->roleHasName((int)($user['role_id'] ?? 0), 'Super Administrator')
            && !$this->actorIsSuperAdministrator($updatedBy)
        ) {
            $errors[] = 'Only a Super Administrator can assign the Super Administrator role.';
        }

        if ($errors) {
            return $this->failure($errors);
        }

        if ($this->usernameExistsForOtherUser((string)$user['username'], $userId)) {
            $errors[] = 'Username already exists.';
        }

        if ($this->employeeIdExistsForOtherUser((string)$user['employee_id'], $userId)) {
            $errors[] = 'Employee ID already exists.';
        }

        if ($errors) {
            return $this->failure($errors);
        }

        try {
            $this->db->beginTransaction();

            $locked = $this->lockUserRow($userId);

            if (!$locked) {
                throw new RuntimeException('User not found.');
            }

            if ($this->roleHasName((int)$locked['role_id'], 'Super Administrator')
                && !$this->actorIsSuperAdministrator($updatedBy)
            ) {
                $this->rollback();
                return $this->failure(['Only a Super Administrator can modify a Super Administrator account.']);
            }

            if ($this->isProtectedAdminUser($locked)) {
                $protectedErrors = $this->validateProtectedAdminUpdate($locked, $user);
                if ($protectedErrors !== []) {
                    $this->rollback();
                    return $this->failure($protectedErrors);
                }
            }

            $stmt = $this->db->prepare('
                UPDATE users
                SET employee_id = :employee_id,
                    first_name = :first_name,
                    last_name = :last_name,
                    gender = :gender,
                    phone = :phone,
                    email = :email,
                    username = :username,
                    department_id = :department_id,
                    role_id = :role_id,
                    status = :status,
                    must_change_password = :must_change_password
                WHERE id = :id
            ');

            $stmt->execute([
                ':employee_id' => trim((string)$user['employee_id']),
                ':first_name' => trim((string)$user['first_name']),
                ':last_name' => trim((string)$user['last_name']),
                ':gender' => $this->nullableString($user['gender'] ?? null),
                ':phone' => $this->nullableString($user['phone'] ?? null),
                ':email' => $this->nullableString($user['email'] ?? null),
                ':username' => trim((string)$user['username']),
                ':department_id' => (int)$user['department_id'],
                ':role_id' => (int)$user['role_id'],
                ':status' => $user['status'] ?? 'Active',
                ':must_change_password' => !empty($user['must_change_password']) ? 1 : 0,
                ':id' => $userId
            ]);

            $this->synchronizePrimaryDepartment(
                $userId,
                (int)$user['department_id'],
                $updatedBy
            );

            $this->auditService->log(
                $updatedBy,
                null,
                'Administration',
                'USER_UPDATED',
                'Updated user account #' . $userId . '.'
            );

            $this->db->commit();

            return [
                'success' => true,
                'user_id' => $userId,
                'errors' => []
            ];
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure(['Unable to update user.']);
        }
    }

    public function activateUser(int $userId, int $updatedBy): array
    {
        return $this->setStatus($userId, 'Active', $updatedBy);
    }

    public function deactivateUser(int $userId, int $updatedBy): array
    {
        return $this->setStatus($userId, 'Inactive', $updatedBy);
    }

    public function lockUser(
        int $userId,
        int $lockedBy,
        ?string $reason = null
    ): array {
        return $this->setLockState($userId, true, $lockedBy, $reason);
    }

    public function unlockUser(int $userId, int $unlockedBy): array
    {
        return $this->setLockState($userId, false, $unlockedBy, null);
    }

    public function resetPassword(
        int $userId,
        string $password,
        int $resetBy
    ): array {
        if (strlen($password) < 8) {
            return $this->failure(['Password must contain at least 8 characters.']);
        }

        try {
            $this->db->beginTransaction();
            if (!$this->lockUserRow($userId)) {
                throw new RuntimeException('User not found.');
            }

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $this->db->prepare('
                UPDATE users
                SET password = :password,
                    password_changed_at = NOW(),
                    must_change_password = 1
                WHERE id = :id
            ');
            $stmt->execute([
                ':password' => $hashedPassword,
                ':id' => $userId
            ]);

            $history = $this->db->prepare('
                INSERT INTO password_history (
                    user_id, password_hash, change_type, changed_by
                ) VALUES (
                    :user_id, :password_hash, \'Reset\', :changed_by
                )
            ');
            $history->execute([
                ':user_id' => $userId,
                ':password_hash' => $hashedPassword,
                ':changed_by' => $resetBy
            ]);

            $this->auditService->log(
                $resetBy,
                null,
                'Administration',
                'PASSWORD_RESET',
                'Reset password for user account #' . $userId . '.',
                null,
                'WARNING',
                'PASSWORD_RESET'
            );
            $this->db->commit();

            return ['success' => true, 'user_id' => $userId, 'errors' => []];
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure(['Unable to reset password.']);
        }
    }

    public function forcePasswordChange(int $userId, int $updatedBy): array
    {
        try {
            $this->db->beginTransaction();
            $locked = $this->lockUserRow($userId);
            if (!$locked) {
                throw new RuntimeException('User not found.');
            }

            if ($this->isProtectedAdminUser($locked)) {
                $this->rollback();
                return $this->failure(['Protected administrator accounts cannot be forced to change password.']);
            }

            $stmt = $this->db->prepare(
                'UPDATE users SET must_change_password = 1 WHERE id = :id'
            );
            $stmt->execute([':id' => $userId]);

            $this->auditService->log(
                $updatedBy,
                null,
                'Administration',
                'PASSWORD_FORCE_CHANGE',
                'Forced password change for user account #' . $userId . '.',
                null,
                'WARNING',
                'PASSWORD_FORCE_CHANGE'
            );
            $this->db->commit();

            return ['success' => true, 'user_id' => $userId, 'errors' => []];
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure(['Unable to force password change.']);
        }
    }

    private function setStatus(int $userId, string $status, int $updatedBy): array
    {
        try {
            $this->db->beginTransaction();
            $locked = $this->lockUserRow($userId);
            if (!$locked) {
                throw new RuntimeException('User not found.');
            }

            if ($status !== 'Active' && $this->isProtectedAdminUser($locked)) {
                $this->rollback();
                return $this->failure(['Protected administrator accounts cannot be deactivated.']);
            }

            $stmt = $this->db->prepare(
                'UPDATE users SET status = :status WHERE id = :id'
            );
            $stmt->execute([':status' => $status, ':id' => $userId]);

            $this->auditService->log(
                $updatedBy,
                null,
                'Administration',
                $status === 'Active' ? 'USER_ACTIVATED' : 'USER_DEACTIVATED',
                'Changed status for user account #' . $userId . ' to ' . $status . '.'
            );
            $this->db->commit();

            return ['success' => true, 'user_id' => $userId, 'errors' => []];
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure(['Unable to update user status.']);
        }
    }

    private function setLockState(
        int $userId,
        bool $locked,
        int $actorId,
        ?string $reason
    ): array {
        try {
            $this->db->beginTransaction();
            $lockedUser = $this->lockUserRow($userId);
            if (!$lockedUser) {
                throw new RuntimeException('User not found.');
            }

            if ($locked && $this->isProtectedAdminUser($lockedUser)) {
                $this->rollback();
                return $this->failure(['Protected administrator accounts cannot be locked.']);
            }

            $stmt = $this->db->prepare($locked
                ? 'UPDATE users SET locked_at = NOW(), locked_by = :actor, lock_reason = :reason WHERE id = :id'
                : 'UPDATE users SET locked_at = NULL, locked_by = NULL, lock_reason = NULL WHERE id = :id'
            );

            $parameters = [':id' => $userId];
            if ($locked) {
                $parameters[':actor'] = $actorId;
                $parameters[':reason'] = $this->nullableString($reason);
            }
            $stmt->execute($parameters);

            $this->auditService->log(
                $actorId,
                null,
                'Administration',
                $locked ? 'ACCOUNT_LOCKED' : 'ACCOUNT_UNLOCKED',
                ($locked ? 'Locked' : 'Unlocked') . ' user account #' . $userId . '.',
                null,
                'WARNING',
                $locked ? 'ACCOUNT_LOCKED' : 'ACCOUNT_UNLOCKED'
            );
            $this->db->commit();

            return ['success' => true, 'user_id' => $userId, 'errors' => []];
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure(['Unable to update account lock state.']);
        }
    }

    private function validateUserInput(array $user, bool $passwordRequired): array
    {
        $errors = [];
        foreach (['employee_id', 'first_name', 'last_name', 'username'] as $field) {
            if (trim((string)($user[$field] ?? '')) === '') {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
            }
        }

        if ($passwordRequired && strlen((string)($user['password'] ?? '')) < 8) {
            $errors[] = 'Password must contain at least 8 characters.';
        }

        if ((int)($user['department_id'] ?? 0) <= 0) {
            $errors[] = 'A valid department is required.';
        }

        if ((int)($user['role_id'] ?? 0) <= 0) {
            $errors[] = 'A valid role is required.';
        }

        if (!in_array($user['status'] ?? 'Active', ['Active', 'Inactive'], true)) {
            $errors[] = 'Invalid account status.';
        }

        return $errors;
    }

    private function usernameExistsForOtherUser(string $username, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT id FROM users WHERE username = :username AND id <> :id LIMIT 1'
        );
        $stmt->execute([':username' => trim($username), ':id' => $userId]);
        return (bool)$stmt->fetchColumn();
    }

    private function employeeIdExistsForOtherUser(string $employeeId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT id FROM users WHERE employee_id = :employee_id AND id <> :id LIMIT 1'
        );
        $stmt->execute([':employee_id' => trim($employeeId), ':id' => $userId]);
        return (bool)$stmt->fetchColumn();
    }

    private function lockUserRow(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, username, role_id, status FROM users WHERE id = :id FOR UPDATE'
        );
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function isProtectedAdminUser(array $user): bool
    {
        return in_array(
            strtolower((string)($user['username'] ?? '')),
            self::PROTECTED_ADMIN_USERNAMES,
            true
        );
    }

    private function validateProtectedAdminUpdate(array $existingUser, array $newUser): array
    {
        $errors = [];

        if (strtolower(trim((string)($newUser['username'] ?? ''))) !== strtolower((string)$existingUser['username'])) {
            $errors[] = 'Protected administrator usernames cannot be changed.';
        }

        if (($newUser['status'] ?? 'Active') !== 'Active') {
            $errors[] = 'Protected administrator accounts must remain active.';
        }

        $newRoleId = (int)($newUser['role_id'] ?? 0);
        $requiredRole = strtolower((string)$existingUser['username']) === 'walter'
            ? 'Super Administrator'
            : 'System Administrator';

        if ($newRoleId !== (int)$existingUser['role_id'] || !$this->roleHasName($newRoleId, $requiredRole)) {
            $errors[] = 'Protected administrator accounts must keep the ' . $requiredRole . ' role.';
        }

        return $errors;
    }

    private function roleHasName(int $roleId, string $roleName): bool
    {
        if ($roleId <= 0) {
            return false;
        }

        $stmt = $this->db->prepare(
            'SELECT role_name FROM roles WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $roleId]);

        return (string)$stmt->fetchColumn() === $roleName;
    }

    private function actorIsSuperAdministrator(int $actorId): bool
    {
        if ($actorId <= 0) {
            return false;
        }

        $stmt = $this->db->prepare('
            SELECT 1
            FROM users u
            INNER JOIN roles r ON r.id = u.role_id
            LEFT JOIN departments d ON d.id = u.department_id
            WHERE u.id = :id
              AND u.status = \'Active\'
              AND (
                  r.role_name = \'Super Administrator\'
                  OR d.department_name = \'Super Administrator\'
              )
            LIMIT 1
        ');
        $stmt->execute([':id' => $actorId]);

        return (bool)$stmt->fetchColumn();
    }

    /**
     * Keep the legacy primary department column and membership table aligned.
     *
     * The caller owns the transaction.
     */
    private function synchronizePrimaryDepartment(
        int $userId,
        int $departmentId,
        int $assignedBy
    ): void {
        $this->db->prepare('
            UPDATE user_departments
            SET is_primary = 0
            WHERE user_id = :user_id
        ')->execute([':user_id' => $userId]);

        $this->db->prepare('
            INSERT INTO user_departments (
                user_id, department_id, is_primary, is_active, assigned_by
            ) VALUES (
                :user_id, :department_id, 1, 1, :assigned_by
            )
            ON DUPLICATE KEY UPDATE
                is_primary = 1,
                is_active = 1,
                assigned_at = NOW(),
                assigned_by = VALUES(assigned_by)
        ')->execute([
            ':user_id' => $userId,
            ':department_id' => $departmentId,
            ':assigned_by' => $assignedBy
        ]);
    }

    private function nullableString(mixed $value): ?string
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
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
    }
}
