<?php

declare(strict_types=1);

$configuredEnvironment = strtolower(trim((string)getenv('HMS_APP_ENV')));
$allowedEnvironments = ['development', 'testing', 'production'];

$applicationEnvironment = in_array(
    $configuredEnvironment,
    $allowedEnvironments,
    true
) ? $configuredEnvironment : 'production';

$developmentBypassValue = strtolower(
    trim((string)getenv('HMS_ENABLE_DEV_AUTH_BYPASS'))
);

$developmentAuthBypass = $applicationEnvironment === 'development'
    && in_array(
        $developmentBypassValue,
        ['1', 'true', 'yes', 'on'],
        true
    );

$configuredDocumentStorageRoot = trim(
    (string)getenv('HMS_DOCUMENT_STORAGE_ROOT')
);
$documentStorageRoot = $configuredDocumentStorageRoot !== ''
    ? $configuredDocumentStorageRoot
    : dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'hms_secure_documents';

return [

    /*
    |--------------------------------------------------------------------------
    | Application
    |--------------------------------------------------------------------------
    */

    'app' => [

        'name' => 'Hospital Management System',

        'version' => '1.0.0',

        'timezone' => 'Africa/Lagos',

        'environment' => $applicationEnvironment,

        'development_auth_bypass' => $developmentAuthBypass,

        'base_url' => '/hospital_management_system'


    ],

    /*
    |--------------------------------------------------------------------------
    | Hospital
    |--------------------------------------------------------------------------
    */

    'hospital' => [

        'code' => 'HMS',

        'name' => 'Hospital Management System',

        'currency' => '₦'

    ],

    /*
    |--------------------------------------------------------------------------
    | Database
    |--------------------------------------------------------------------------
    */

    'database' => [

        'host' => 'localhost',

        'name' => 'hospital_management_system',

        'user' => 'root',

        'pass' => ''

    ],

    'security' => [

        'max_failed_login_attempts' => 10,

        'session_timeout_seconds' => 1800

    ],

    'documents' => [

        'storage_root' => $documentStorageRoot,

        'storage_provider' => 'local'

    ]

];
