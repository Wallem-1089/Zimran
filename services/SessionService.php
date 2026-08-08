<?php

declare(strict_types=1);

class SessionService
{
    private const SESSION_TIMEOUT_SECONDS = 1800;

    private ?PDO $pdo;

    private ?int $sessionRecordId = null;

    private ?int $resolvedTimeoutSeconds = null;
    /**
     * Constructor.
     * Starts a session if one is not already active.
     */
    public function __construct(?PDO $pdo = null)
    {
        if ($pdo === null) {
            require_once __DIR__ . '/../config/database.php';
            $pdo = $GLOBALS['pdo'] ?? ($pdo ?? null);
        }

        $this->pdo = $pdo;

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->sessionRecordId = isset($_SESSION['session_record_id'])
            ? (int)$_SESSION['session_record_id']
            : null;
    }

    /**
     * Log a user into the application.
     *
     * @param array $user
     * @return void
     */
    public function login(array $user): void
    {
        session_regenerate_id(true);

        $_SESSION['user'] = [

            'id'              => $user['id'],
            'employee_id'     => $user['employee_id'],
            'first_name'      => $user['first_name'],
            'last_name'       => $user['last_name'],
            'username'        => $user['username'],
            'department_id'   => $user['department_id'],
            'department_name' => $user['department_name'],
            'role_id'         => $user['role_id'],
            'role_name'       => $user['role_name']

        ];

        $_SESSION['active_department_id'] = (int)$user['department_id'];
        $_SESSION['active_department_name'] = $user['department_name'];
        $_SESSION['user']['active_department_id'] = (int)$user['department_id'];
        $_SESSION['user']['active_department_name'] = $user['department_name'];

        $this->registerPersistentSession($user);

        $_SESSION['logged_in'] = true;

        $_SESSION['login_time'] = time();

        $_SESSION['last_activity'] = time();
    }

    /**
     * Check whether a user is authenticated.
     *
     * @return bool
     */
    public function isAuthenticated(): bool
    {
        return isset($_SESSION['logged_in'])
            && $_SESSION['logged_in'] === true
            && isset($_SESSION['user']);
    }

    /**
     * Get the authenticated user.
     *
     * @return array|null
     */
    public function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    /**
     * Get the authenticated user's ID.
     *
     * @return int|null
     */
    public function userId(): ?int
    {
        return $_SESSION['user']['id'] ?? null;
    }

    /**
     * Get the authenticated user's role.
     *
     * @return string|null
     */
    public function role(): ?string
    {
        return $_SESSION['user']['role_name'] ?? null;
    }

    /**
     * Get the authenticated user's department.
     *
     * @return string|null
     */
    public function department(): ?string
    {
        return $_SESSION['active_department_name']
            ?? $_SESSION['user']['department_name']
            ?? null;
    }

    public function activeDepartmentId(): ?int
    {
        $departmentId = (int)($_SESSION['active_department_id'] ?? 0);
        return $departmentId > 0 ? $departmentId : null;
    }

    public function setActiveDepartment(int $departmentId, string $departmentName): void
    {
        $_SESSION['active_department_id'] = $departmentId;
        $_SESSION['active_department_name'] = $departmentName;

        if (isset($_SESSION['user'])) {
            $_SESSION['user']['active_department_id'] = $departmentId;
            $_SESSION['user']['active_department_name'] = $departmentName;
        }
    }

    /**
     * Check whether the user belongs to a role.
     *
     * @param string $role
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        return $this->role() === $role;
    }

    /**
     * Check whether the user belongs to a department.
     *
     * @param string $department
     * @return bool
     */
    public function hasDepartment(string $department): bool
    {
        return $this->department() === $department;
    }

    /**
     * Store a flash message.
     *
     * @param string $type
     * @param string $message
     * @return void
     */
    public function flash(string $type, string $message): void
    {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message
        ];
    }

    /**
     * Retrieve and remove the current flash message.
     *
     * @return array|null
     */
    public function getFlash(): ?array
    {
        if (!isset($_SESSION['flash'])) {
            return null;
        }

        $flash = $_SESSION['flash'];

        unset($_SESSION['flash']);

        return $flash;
    }

    /**
     * Log the current user out.
     *
     * @return void
     */
    public function logout(): void
    {
        $this->closeCurrentSession('User logout.', 'SESSION_TERMINATED');

        $_SESSION = [];
        $_SESSION['last_activity'] = time();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';

        if (ini_get('session.use_cookies')) {

            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    public function listActiveSessions(?int $userId = null): array
    {
        $userId ??= $this->userId();
        $stmt = $this->pdo->prepare('
            SELECT s.*, u.first_name, u.last_name, u.username,
                   d.department_name
            FROM active_sessions s
            INNER JOIN users u ON u.id = s.user_id
            LEFT JOIN departments d ON d.id = s.active_department_id
            WHERE s.status = \'Active\' AND s.user_id = :user_id
            ORDER BY s.last_activity DESC
        ');
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllActiveSessions(): array
    {
        $stmt = $this->pdo->query('
            SELECT s.*, u.first_name, u.last_name, u.username,
                   d.department_name
            FROM active_sessions s
            INNER JOIN users u ON u.id = s.user_id
            LEFT JOIN departments d ON d.id = s.active_department_id
            WHERE s.status = \'Active\'
            ORDER BY s.last_activity DESC
        ');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function terminateSession(
        int $sessionId,
        int $terminatedBy,
        ?string $reason = null
    ): array {
        try {
            $this->pdo->beginTransaction();
            $session = $this->lockSession($sessionId);
            if (!$session) {
                throw new RuntimeException('Session not found.');
            }

            if (!$this->isAdministrator($terminatedBy)
                && (int)$session['user_id'] !== $terminatedBy
            ) {
                throw new RuntimeException('You cannot terminate this session.');
            }

            $adminAction = (int)$session['user_id'] !== $terminatedBy;
            $stmt = $this->pdo->prepare('
                UPDATE active_sessions
                SET status = \'Terminated\', terminated_at = NOW(),
                    terminated_by = :terminated_by,
                    termination_reason = :reason
                WHERE id = :id AND status = \'Active\'
            ');
            $stmt->execute([
                ':terminated_by' => $terminatedBy,
                ':reason' => $reason ?: ($adminAction ? 'Terminated by administrator.' : 'Session terminated.'),
                ':id' => $sessionId
            ]);
            $this->audit(
                $terminatedBy,
                $adminAction ? 'SESSION_TERMINATED_BY_ADMIN' : 'SESSION_TERMINATED',
                ($adminAction ? 'Administrator terminated' : 'Terminated') . ' session #' . $sessionId . '.',
                $adminAction ? 'WARNING' : 'INFO'
            );
            $this->pdo->commit();
            return ['success' => true, 'session_id' => $sessionId, 'errors' => []];
        } catch (Throwable $exception) {
            $this->rollback();
            return ['success' => false, 'errors' => [$exception->getMessage()]];
        }
    }

    public function terminateAllSessionsForUser(int $userId, int $terminatedBy): array
    {
        if (!$this->isAdministrator($terminatedBy) && $userId !== $terminatedBy) {
            return ['success' => false, 'errors' => ['You cannot terminate another user sessions.']];
        }

        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare('
                UPDATE active_sessions
                SET status = \'Terminated\', terminated_at = NOW(),
                    terminated_by = :terminated_by,
                    termination_reason = :reason
                WHERE user_id = :user_id AND status = \'Active\'
            ');
            $stmt->execute([
                ':terminated_by' => $terminatedBy,
                ':reason' => 'All sessions terminated.',
                ':user_id' => $userId
            ]);
            $this->audit(
                $terminatedBy,
                $userId === $terminatedBy ? 'SESSION_TERMINATED' : 'SESSION_TERMINATED_BY_ADMIN',
                'Terminated all active sessions for user #' . $userId . '.',
                'WARNING'
            );
            $this->pdo->commit();
            return ['success' => true, 'user_id' => $userId, 'errors' => []];
        } catch (Throwable $exception) {
            $this->rollback();
            return ['success' => false, 'errors' => ['Unable to terminate sessions.']];
        }
    }

    public function terminateExpiredSessions(): array
    {
        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare('
                UPDATE active_sessions
                SET status = \'Expired\', terminated_at = NOW(),
                    termination_reason = \'Session expired.\'
                WHERE status = \'Active\' AND expires_at < NOW()
            ');
            $stmt->execute();
            $count = $stmt->rowCount();
            $this->pdo->commit();
            return ['success' => true, 'expired_count' => $count, 'errors' => []];
        } catch (Throwable $exception) {
            $this->rollback();
            return ['success' => false, 'errors' => ['Unable to expire sessions.']];
        }
    }

    public function getSessionHistory(?int $userId = null): array
    {
        $userId ??= $this->userId();
        $stmt = $this->pdo->prepare('
            SELECT s.*, u.first_name, u.last_name, u.username,
                   d.department_name
            FROM active_sessions s
            INNER JOIN users u ON u.id = s.user_id
            LEFT JOIN departments d ON d.id = s.active_department_id
            WHERE s.user_id = :user_id
            ORDER BY s.login_at DESC
        ');
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function requireAuthentication(): void
    {
        if (!$this->isAuthenticated()) {

            header('Location: /hospital_management_system/authentication/login.php');

            exit;

        }

        $this->refreshOrExpire();

        if (!empty($_SESSION['must_change_password'])) {

            header('Location: /hospital_management_system/authentication/change_password.php');

            exit;

        }
    }

    /**
     * Require authentication to continue.
     *
     * @return void
     */
    public function requireLogin(): void
    {
        if (!$this->isAuthenticated()) {

            header('Location: /hospital_management_system/authentication/login.php');

            exit;
        }

        $this->refreshOrExpire();
    }

    private function refreshOrExpire(): void
    {
        $lastActivity = (int)(
            $_SESSION['last_activity']
            ?? $_SESSION['login_time']
            ?? time()
        );

        if (time() - $lastActivity <= $this->sessionTimeoutSeconds()) {
            $_SESSION['last_activity'] = time();

            $this->refreshPersistentSession();

            return;
        }

        $userId = isset($_SESSION['user']['id'])
            ? (int)$_SESSION['user']['id']
            : null;

        try {
            require_once __DIR__ . '/../config/database.php';
            require_once __DIR__ . '/AuditService.php';

            (new AuditService($pdo))->log(
                $userId,
                null,
                'Security',
                'SESSION_TIMEOUT',
                'User session expired due to inactivity.',
                null,
                'WARNING',
                'SESSION_TIMEOUT'
            );
        } catch (Throwable $e) {
            // Expiration must complete even if audit storage is unavailable.
        }

        $this->logout();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['login_errors'] = [
            'Your session has expired. Please sign in again.'
        ];

        header('Location: /hospital_management_system/authentication/login.php');

        exit;
    }

    private function registerPersistentSession(array $user): void
    {
        if (!$this->pdo) {
            return;
        }

        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare('
                INSERT INTO active_sessions (
                    session_id, user_id, login_at, last_activity, expires_at,
                    ip_address, user_agent, active_department_id, status
                ) VALUES (
                    :session_id, :user_id, NOW(), NOW(),
                    :expires_at, :ip, :agent,
                    :department_id, \'Active\'
                )
            ');
            $stmt->execute([
                ':session_id' => session_id(),
                ':user_id' => (int)$user['id'],
                ':expires_at' => date(
                    'Y-m-d H:i:s',
                    time() + $this->sessionTimeoutSeconds()
                ),
                ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                ':agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                ':department_id' => (int)$user['department_id']
            ]);
            $this->sessionRecordId = (int)$this->pdo->lastInsertId();
            $_SESSION['session_record_id'] = $this->sessionRecordId;
            $this->audit(
                (int)$user['id'],
                'SESSION_CREATED',
                'Created authenticated session.',
                'INFO'
            );
            $this->pdo->commit();
        } catch (Throwable $exception) {
            $this->rollback();
        }
    }

    private function refreshPersistentSession(): void
    {
        if (!$this->pdo || !$this->sessionRecordId) {
            return;
        }

        $stmt = $this->pdo->prepare('
            UPDATE active_sessions
            SET last_activity = NOW(), expires_at = :expires_at,
                active_department_id = :department_id
            WHERE id = :id AND status = \'Active\'
        ');
        $stmt->execute([
            ':expires_at' => date(
                'Y-m-d H:i:s',
                time() + $this->sessionTimeoutSeconds()
            ),
            ':department_id' => $_SESSION['active_department_id'] ?? null,
            ':id' => $this->sessionRecordId
        ]);
    }

    private function closeCurrentSession(string $reason, string $action): void
    {
        if (!$this->pdo || !$this->sessionRecordId) {
            return;
        }

        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare('
                UPDATE active_sessions
                SET status = \'Terminated\', terminated_at = NOW(),
                    termination_reason = :reason
                WHERE id = :id AND status = \'Active\'
            ');
            $stmt->execute([':reason' => $reason, ':id' => $this->sessionRecordId]);
            $this->audit(
                $this->userId(),
                $action,
                $reason,
                'INFO'
            );
            $this->pdo->commit();
        } catch (Throwable $exception) {
            $this->rollback();
        }
    }

    private function sessionTimeoutSeconds(): int
    {
        if ($this->resolvedTimeoutSeconds !== null) {
            return $this->resolvedTimeoutSeconds;
        }

        $config = require __DIR__ . '/../config/app.php';
        $fallback = max(
            60,
            (int)($config['security']['session_timeout_seconds']
                ?? self::SESSION_TIMEOUT_SECONDS)
        );

        if (!$this->pdo) {
            return $this->resolvedTimeoutSeconds = $fallback;
        }

        try {
            require_once __DIR__ . '/SettingsService.php';
            $minutes = (new SettingsService($this->pdo))->getInteger(
                'security.session_timeout_minutes',
                (int)ceil($fallback / 60)
            );

            return $this->resolvedTimeoutSeconds = max(60, $minutes * 60);
        } catch (Throwable $exception) {
            return $this->resolvedTimeoutSeconds = $fallback;
        }
    }

    private function lockSession(int $sessionId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM active_sessions WHERE id = :id FOR UPDATE'
        );
        $stmt->execute([':id' => $sessionId]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);
        return $session ?: null;
    }

    private function isAdministrator(int $userId): bool
    {
        $stmt = $this->pdo->prepare('
            SELECT r.role_name FROM users u INNER JOIN roles r ON r.id = u.role_id WHERE u.id = :id
        ');
        $stmt->execute([':id' => $userId]);
        return $stmt->fetchColumn() === 'System Administrator';
    }

    private function audit(
        ?int $userId,
        string $action,
        string $description,
        string $severity
    ): void {
        require_once __DIR__ . '/AuditService.php';
        (new AuditService($this->pdo))->log(
            $userId,
            null,
            'Security',
            $action,
            $description,
            $_SESSION['active_department_id'] ?? null,
            $severity,
            $action
        );
    }

    private function rollback(): void
    {
        if ($this->pdo && $this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }
}
