<?php

require_once __DIR__ . '/UserService.php';

class AuthService
{
    /**
     * User service instance.
     *
     * @var UserService
     */
    private UserService $userService;

    /**
     * Constructor.
     *
     * @param PDO $db
     */
    public function __construct(PDO $db)
    {
        $this->userService = new UserService($db);
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

            'errors' => [
                'account' => 'Account inactive.'
            ]

        ];

        }

        /*
        |--------------------------------------------------------------------------
        | Verify Password
        |--------------------------------------------------------------------------
        */

        if (!password_verify($password, $user['password'])) {

            $this->userService->recordFailedLogin($user['id']);

            return [

            'success' => false,

            'status' => 'FAILED',

            'code' => 401,

            'message' => 'Invalid username, employee ID or password.',

            'user' => null,

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
}