<?php

/*
|--------------------------------------------------------------------------
| One-Time Administrator Account Creator
|--------------------------------------------------------------------------
| Run this file ONCE.
| Afterwards DELETE the entire setup folder.
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../config/database.php';

$employeeId = "EMP-000001";

$firstName = "System";

$lastName = "Administrator";

$gender = "Male";

$phone = "08000000000";

$email = "admin@hospital.local";

$username = "admin";

/*
|--------------------------------------------------------------------------
| CHANGE THIS PASSWORD
|--------------------------------------------------------------------------
*/

$plainPassword = "Admin@123";

/*
|--------------------------------------------------------------------------
| Password Hash
|--------------------------------------------------------------------------
*/

$password = password_hash($plainPassword, PASSWORD_DEFAULT);

/*
|--------------------------------------------------------------------------
| Department and Role
|--------------------------------------------------------------------------
| Administrator department = 1
| System Administrator role = 1
|--------------------------------------------------------------------------
*/

$departmentId = 1;

$roleId = 1;

$status = "Active";

try {

    /*
    |--------------------------------------------------------------------------
    | Check if user already exists
    |--------------------------------------------------------------------------
    */

    $check = $pdo->prepare("
        SELECT id
        FROM users
        WHERE username = ?
           OR employee_id = ?
        LIMIT 1
    ");

    $check->execute([$username, $employeeId]);

    if ($check->fetch()) {

        die("Administrator already exists.");

    }

    /*
    |--------------------------------------------------------------------------
    | Insert Administrator
    |--------------------------------------------------------------------------
    */

    $sql = "

        INSERT INTO users(

            employee_id,

            first_name,

            last_name,

            gender,

            phone,

            email,

            username,

            password,

            department_id,

            role_id,

            status,

            password_changed_at,

            must_change_password

        )

        VALUES(

            ?,?,?,?,?,?,?,?,?,?,?,NOW(),1

        )

    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([

        $employeeId,

        $firstName,

        $lastName,

        $gender,

        $phone,

        $email,

        $username,

        $password,

        $departmentId,

        $roleId,

        $status

    ]);

    echo "<h2>Administrator Created Successfully</h2>";

    echo "<hr>";

    echo "<strong>Username:</strong> admin<br>";

    echo "<strong>Employee ID:</strong> EMP-000001<br>";

    echo "<strong>Password:</strong> Admin@123<br><br>";

    echo "<h3>Delete the setup folder now.</h3>";

} catch(PDOException $e){

    die($e->getMessage());

}