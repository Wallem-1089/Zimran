<?php

class UserService
{
    /**
     * PDO database connection.
     *
     * @var PDO
     */
    private PDO $db;

    /**
     * Constructor.
     *
     * @param PDO $db
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
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
     * @return void
     */
    public function recordFailedLogin(int $userId): void
    {
        $stmt = $this->db->prepare("
            UPDATE users
            SET
                failed_login_attempts =
                    failed_login_attempts + 1,
                last_failed_login = NOW()
            WHERE id = :id
        ");

        $stmt->execute([
            ':id' => $userId
        ]);
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

        $stmt = $this->db->prepare("
            UPDATE users
            SET
                password = :password,
                password_changed_at = NOW(),
                must_change_password = 0
            WHERE id = :id
        ");

        $stmt->execute([
            ':password' => $hashedPassword,
            ':id' => $userId
        ]);
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
}