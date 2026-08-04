<?php

class SessionService
{
    private const SESSION_TIMEOUT_SECONDS = 1800;
    /**
     * Constructor.
     * Starts a session if one is not already active.
     */
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
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
        return $_SESSION['user']['department_name'] ?? null;
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

            header('Location: ../authentication/login.php');

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

        if (time() - $lastActivity <= self::SESSION_TIMEOUT_SECONDS) {
            $_SESSION['last_activity'] = time();

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
                'User session expired due to inactivity.'
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
}
