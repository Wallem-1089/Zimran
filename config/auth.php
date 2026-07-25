<?php

declare(strict_types=1);

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
| During development, authentication can be bypassed so that
| application modules can be built and tested without logging in.
|
| Change 'development' to 'production' in config/app.php when
| authentication is complete.
|
*/

if (($config['app']['environment'] ?? 'production') === 'development') {

    $currentUser = [

        'id' => 1,

        'employee_number' => 'EMP-000001',

        'username' => 'developer',

        'first_name' => 'Walter',

        'last_name' => 'Developer',

        'role_name' => 'System Administrator',

        'department_name' => 'Administrator',

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