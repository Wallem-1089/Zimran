<?php

declare(strict_types=1);

$configuredEnvironment = strtolower(trim((string)getenv('HMS_APP_ENV')));
$allowedEnvironments = ['development', 'testing', 'production'];

$applicationEnvironment = in_array(
    $configuredEnvironment,
    $allowedEnvironments,
    true
) ? $configuredEnvironment : 'production';

error_reporting(E_ALL);
ini_set('log_errors', '1');

$configuredPhpErrorLog = trim((string)getenv('HMS_PHP_ERROR_LOG'));
$phpErrorLog = $configuredPhpErrorLog !== ''
    ? $configuredPhpErrorLog
    : dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage'
        . DIRECTORY_SEPARATOR . 'logs'
        . DIRECTORY_SEPARATOR . 'php_errors.log';

$phpErrorLogDirectory = dirname($phpErrorLog);
if (is_dir($phpErrorLogDirectory) && is_writable($phpErrorLogDirectory)) {
    ini_set('error_log', $phpErrorLog);
}

if ($applicationEnvironment === 'production') {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
} else {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
}

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

$configuredBaseUrl = trim((string)getenv('HMS_BASE_URL'));

$normalizeBaseUrl = static function (string $value): string {
    $value = trim($value);
    if ($value === '') {
        return '/';
    }

    // Accept either a path (/zimran) or an absolute application URL
    // (https://example.test/zimran). Only the URL path belongs in links.
    if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $value) === 1) {
        $parsedPath = parse_url($value, PHP_URL_PATH);
        $value = is_string($parsedPath) ? $parsedPath : '';
    }

    $value = trim($value, "/ \t\n\r\0\x0B");
    return $value === '' ? '/' : '/' . $value;
};

$detectBaseUrl = static function (): string {
    $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    foreach (['/authentication/', '/modules/', '/dashboard/', '/admin/'] as $knownPath) {
        $position = strpos($scriptName, $knownPath);
        if ($position !== false) {
            $basePath = substr($scriptName, 0, $position);
            return $basePath === '' ? '/' : '/' . trim($basePath, '/');
        }
    }

    $documentRoot = realpath((string)($_SERVER['DOCUMENT_ROOT'] ?? ''));
    $projectRoot = realpath(dirname(__DIR__));

    if ($documentRoot !== false && $projectRoot !== false) {
        $normalizedDocumentRoot = rtrim(str_replace('\\', '/', $documentRoot), '/');
        $normalizedProjectRoot = rtrim(str_replace('\\', '/', $projectRoot), '/');

        if ($normalizedDocumentRoot !== '' && str_starts_with($normalizedProjectRoot, $normalizedDocumentRoot)) {
            $relativePath = trim(substr($normalizedProjectRoot, strlen($normalizedDocumentRoot)), '/');
            return $relativePath === '' ? '/' : '/' . $relativePath;
        }
    }

    // Do not fall back to a historical folder name. If the web server does
    // not expose enough information to detect an install path, root-relative
    // links are safer than redirecting users to a stale project URL.
    return '/';
};

if ($configuredBaseUrl !== '') {
    $baseUrl = $normalizeBaseUrl($configuredBaseUrl);
} else {
    $baseUrl = $detectBaseUrl();
}

return [

    /*
    |--------------------------------------------------------------------------
    | Application
    |--------------------------------------------------------------------------
    */

    'app' => [

        'name' => 'E-HMIS',

        'version' => '1.2',

        'timezone' => 'Africa/Lagos',

        'environment' => $applicationEnvironment,

        'development_auth_bypass' => $developmentAuthBypass,

        'base_url' => $baseUrl,

        'php_error_log' => $phpErrorLog


    ],

    /*
    |--------------------------------------------------------------------------
    | Hospital
    |--------------------------------------------------------------------------
    */

    'hospital' => [

        'code' => 'Zimran',

        'name' => 'Zimran',

        'currency' => '₦'

    ],

    /*
    |--------------------------------------------------------------------------
    | Database
    |--------------------------------------------------------------------------
    */

    'database' => [

        'host' => getenv('HMS_DB_HOST') ?: 'localhost',

        'name' => getenv('HMS_DB_NAME') ?: 'hospital_management_system',

        'user' => getenv('HMS_DB_USER') ?: 'root',

        'pass' => getenv('HMS_DB_PASS') ?: ''

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
