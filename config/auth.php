<?php

declare(strict_types=1);

require_once __DIR__ . '/session.php';

/*
|--------------------------------------------------------------------------
| Authentication Guard
|--------------------------------------------------------------------------
|
| Include this file at the top of any page that requires a logged-in user.
|
*/

/*
|--------------------------------------------------------------------------
| Application Configuration
|--------------------------------------------------------------------------
*/

$config = require __DIR__ . '/app.php';

/*
|--------------------------------------------------------------------------
| Development Mode
|--------------------------------------------------------------------------
|
| Development authentication bypass is disabled unless both the application
| environment and the protected bypass flag are explicitly configured by the
| server process. Missing or invalid environment values resolve to production.
|
*/

if (($config['app']['environment'] ?? 'production') === 'development'
    && ($config['app']['development_auth_bypass'] ?? false) === true
) {

    if (!empty($_SESSION['user'])) {

        $currentUser = $_SESSION['user'];

        return;

    }

    $currentUser = [

        'id' => 1,

        'employee_number' => 'EMP-000001',

        'username' => 'developer',

        'first_name' => 'Walter',

        'last_name' => 'Developer',

        'role_name' => 'Super Administrator',

        'department_name' => 'Super Administrator',

    ];

    return;

}

/*
|--------------------------------------------------------------------------
| Production Authentication
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../services/SessionService.php';

$sessionService = new SessionService();

/*
|--------------------------------------------------------------------------
| Require Authentication
|--------------------------------------------------------------------------
*/

$sessionService->requireLogin();

/*
|--------------------------------------------------------------------------
| Make Current User Available
|--------------------------------------------------------------------------
*/

$currentUser = $sessionService->user();
