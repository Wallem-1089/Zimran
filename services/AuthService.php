<?php

declare(strict_types=1);

require_once __DIR__ . '/UserService.php';
require_once __DIR__ . '/SettingsService.php';

class AuthService
{
    /**
     * User service instance.
     *
     * @var UserService
     */
    private UserService $userService;

    private array $config;

    private SettingsService $settingsService;

    /**
     * Constructor.
     *
     * @param PDO $db
     */
    public function __construct(PDO $db)
    {
        $this->userService = new UserService($db);
        $this->config = require __DIR__ . '/../config/app.php';
        $this->settingsService = new SettingsService($db);
    }

    /**
     * Authenticate a user.
     *
     * @param string $login
     * @param string $password
     * @return array
     */
    public function login(string $login, string $password): array
    {
        $user = $this->userService->findByLogin($login);

        if (!$user) {

            return [

            'success' => false,

            'status' => 'FAILED',

            'code' => 401,

            'message' => 'Invalid username, employee ID or password.',

            'user' => null,

            'user_id' => null,

            'errors' => [
                'login' => 'Invalid credentials.'
            ]

        ];

        }

        /*
        |--------------------------------------------------------------------------
        | Account Status
        |--------------------------------------------------------------------------
        */

        if ($user['status'] !== 'Active') {

            return [

            'success' => false,

            'status' => 'FAILED',

            'code' => 403,

            'message' => 'Your account has been deactivated.',

            'user' => null,

            'user_id' => (int)$user['id'],

            'errors' => [
                'account' => 'Account inactive.'
            ]

        ];

        }

        if (!empty($user['locked_at'])) {

            return [

                'success' => false,

                'status' => 'FAILED',

                'code' => 423,

                'message' => 'Your account is locked. Contact an administrator.',

                'user' => null,

                'user_id' => (int)$user['id'],

                'errors' => [
                    'account' => 'Account locked.'
                ]

            ];

        }

        /*
        |--------------------------------------------------------------------------
        | Verify Password
        |--------------------------------------------------------------------------
        */

        if (!password_verify($password, $user['password'])) {

            $this->userService->recordFailedLogin(
                (int)$user['id'],
                $this->lockoutThreshold()
            );

            return [

            'success' => false,

            'status' => 'FAILED',

            'code' => 401,

            'message' => 'Invalid username, employee ID or password.',

            'user' => null,

            'user_id' => (int)$user['id'],

            'errors' => [
                'password' => 'Incorrect password.'
            ]

        ];

        }

        /*
        |--------------------------------------------------------------------------
        | Successful Login
        |--------------------------------------------------------------------------
        */

        $this->userService->updateLastLogin($user['id']);

        /*
        |--------------------------------------------------------------------------
        | Remove Password Before Returning
        |--------------------------------------------------------------------------
        */

        unset($user['password']);

        return [

        'success' => true,

        'status' => 'SUCCESS',

        'code' => 200,

        'message' => 'Login successful.',

            'user' => $user,

            'user_id' => (int)$user['id'],

        'errors' => []

    ];
        }

    /**
     * Determine whether a password change is required.
     *
     * @param array $user
     * @return bool
     */
    public function mustChangePassword(array $user): bool
    {
        return !empty($user['must_change_password']);
    }

    /**
     * Hash a password.
     *
     * @param string $password
     * @return string
     */
    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verify a password.
     *
     * @param string $plainPassword
     * @param string $hashedPassword
     * @return bool
     */
    public function verifyPassword(
        string $plainPassword,
        string $hashedPassword
    ): bool {

        return password_verify(
            $plainPassword,
            $hashedPassword
        );

    }

    private function lockoutThreshold(): int
    {
        $fallback = (int)(
            $this->config['security']['max_failed_login_attempts']
            ?? 5
        );

        try {
            return max(
                1,
                $this->settingsService->getInteger(
                    'security.lockout_threshold',
                    $fallback
                )
            );
        } catch (Throwable $exception) {
            return max(1, $fallback);
        }
    }
}
