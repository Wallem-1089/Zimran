<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Bootstrap
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

require_once __DIR__ . '/../services/UserService.php';
require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../services/SessionService.php';
require_once __DIR__ . '/../services/AuditService.php';

/*
|--------------------------------------------------------------------------
| Services
|--------------------------------------------------------------------------
*/

$authService = new AuthService($pdo);
$sessionService = new SessionService();
$auditService = new AuditService($pdo);

/*
|--------------------------------------------------------------------------
| Allow POST Only
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header('Location: ../authentication/login.php');
    exit;

}

/*
|--------------------------------------------------------------------------
| Retrieve Form Data
|--------------------------------------------------------------------------
*/

$login = trim($_POST['login'] ?? '');
$password = $_POST['password'] ?? '';

$errors = [];

/*
|--------------------------------------------------------------------------
| Validate Input
|--------------------------------------------------------------------------
*/

if ($login === '') {

    $errors[] = 'Employee ID or Username is required.';

}

if ($password === '') {

    $errors[] = 'Password is required.';

}

if (!empty($errors)) {

    $_SESSION['login_errors'] = $errors;

    $_SESSION['old_login'] = $login;

    header('Location: ../authentication/login.php');

    exit;

}

/*
|--------------------------------------------------------------------------
| Authenticate User
|--------------------------------------------------------------------------
*/

$result = $authService->login($login, $password);

if (!$result['success']) {

    $auditService->loginFailed($login);

    $_SESSION['login_errors'] = [$result['message']];

    $_SESSION['old_login'] = $login;

    header('Location: ../authentication/login.php');

    exit;

}

/*
|--------------------------------------------------------------------------
| Login Successful
|--------------------------------------------------------------------------
*/

$user = $result['user'];

$sessionService->login($user);

$auditService->loginSuccess(
    (int)$user['id']
);

/*
|--------------------------------------------------------------------------
| Force Password Change
|--------------------------------------------------------------------------
*/

if ($authService->mustChangePassword($user)) {

    $_SESSION['warning'] =
        'You must change your password before continuing.';

    header(
        'Location: ../authentication/change_password.php'
    );

    exit;

}

/*
|--------------------------------------------------------------------------
| Success Message
|--------------------------------------------------------------------------
*/

$_SESSION['success_message'] =
    'Welcome back, ' .
    $user['first_name'] .
    '!';

/*
|--------------------------------------------------------------------------
| Redirect
|--------------------------------------------------------------------------
*/

header(
    'Location: ../dashboard/index.php'
);

exit;