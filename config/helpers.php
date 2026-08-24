<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Hospital Management System
| Global Helper Functions
|--------------------------------------------------------------------------
*/

/**
 * Escape HTML output.
 *
 * @param mixed $value
 * @return string
 */
function e($value): string
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

/**
 * Resolve hospital/application branding for UI layouts.
 */
function appBranding(?PDO $pdo = null): array
{
    static $cache = [];

    $cacheKey = $pdo ? (string)spl_object_id($pdo) : 'config';
    if (isset($cache[$cacheKey])) {
        return $cache[$cacheKey];
    }

    $config = require __DIR__ . '/app.php';

    $branding = [
        'hospital_name' => (string)($config['hospital']['name'] ?? 'Zimran'),
        'hospital_code' => (string)($config['hospital']['code'] ?? 'Zimran'),
        'product_name' => (string)($config['app']['name'] ?? 'E-HMIS'),
    ];

    if ($pdo instanceof PDO) {
        try {
            require_once __DIR__ . '/../services/SettingsService.php';
            $settings = new SettingsService($pdo);
            $branding['hospital_name'] = (string)$settings->get(
                'hospital.name',
                $branding['hospital_name']
            );
            $branding['hospital_code'] = (string)$settings->get(
                'hospital.code',
                $branding['hospital_code']
            );
            $branding['product_name'] = (string)$settings->get(
                'app.product_name',
                $branding['product_name']
            );
        } catch (Throwable) {
            // Fall back to config branding when settings are unavailable.
        }
    }

    $branding['display_name'] = trim($branding['hospital_name']) !== ''
        ? $branding['hospital_name']
        : $branding['product_name'];

    $branding['full_name'] = trim($branding['product_name']) !== ''
        ? trim($branding['display_name'] . ' ' . $branding['product_name'])
        : $branding['display_name'];

    return $cache[$cacheKey] = $branding;
}

/**
 * Redirect to another page.
 *
 * @param string $url
 * @return never
 */
function redirect(string $url): never
{
    header("Location: {$url}");
    exit;
}

/**
 * Check if a value is present.
 *
 * @param mixed $value
 * @return bool
 */
function filled($value): bool
{
    return isset($value)
        && trim((string)$value) !== '';
}

/**
 * Format date.
 *
 * @param string|null $date
 * @return string
 */
function formatDate(?string $date): string
{
    if (empty($date)) {
        return '-';
    }

    return date('d M Y', strtotime($date));
}

/**
 * Format date and time.
 *
 * @param string|null $datetime
 * @return string
 */
function formatDateTime(?string $datetime): string
{
    if (empty($datetime)) {
        return '-';
    }

    return date('d M Y H:i', strtotime($datetime));
}

/**
 * Calculate age from date of birth.
 *
 * @param string|null $dob
 * @return int|string
 */
function calculateAge(?string $dob)
{
    if (empty($dob)) {
        return '-';
    }

    $birthDate = new DateTime($dob);
    $today = new DateTime();

    return $birthDate->diff($today)->y;
}

/**
 * Display gender with fallback.
 *
 * @param string|null $gender
 * @return string
 */
function gender(?string $gender): string
{
    return $gender ?: '-';
}

/**
 * Generate hospital number.
 *
 * Example:
 * HSP-2026-000001
 *
 * @param int $id
 * @return string
 */
function generateHospitalNumber(int $id): string
{
    return sprintf(

        'HSP-%s-%06d',

        date('Y'),

        $id

    );
}

/**
 * Generate encounter number.
 *
 * Example:
 * ENC-2026-000045
 *
 * @param int $visitId
 * @return string
 */
function generateEncounterNumber(int $visitId): string
{
    return sprintf(

        'ENC-%s-%06d',

        date('Y'),

        $visitId

    );
}

/**
 * Generate employee number.
 *
 * Example:
 * EMP-000123
 *
 * @param int $id
 * @return string
 */
function generateEmployeeNumber(int $id): string
{
    return sprintf(

        'EMP-%06d',

        $id

    );
}

/**
 * Format currency.
 *
 * @param float|int $amount
 * @return string
 */
function money($amount): string
{
    return '₦' . number_format((float)$amount, 2);
}

/**
 * Determine whether the request is POST.
 *
 * @return bool
 */
function isPost(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Determine whether the request is GET.
 *
 * @return bool
 */
function isGet(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}

/**
 * Get client IP address.
 *
 * @return string
 */
function clientIp(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
}

/**
 * Return the current CSRF token, creating it when necessary.
 */
function csrfToken(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return (string)$_SESSION['csrf_token'];
}

/**
 * Render a reusable hidden CSRF form field.
 */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="'
        . e(csrfToken())
        . '">';
}

/**
 * Verify a submitted CSRF token without exposing token details.
 */
function verifyCsrfToken(?string $token = null): bool
{
    $token = $token ?? ($_POST['csrf_token'] ?? '');
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    return is_string($token)
        && is_string($sessionToken)
        && $token !== ''
        && $sessionToken !== ''
        && hash_equals($sessionToken, $token);
}

/**
 * Enforce CSRF validation for state-changing endpoints.
 */
function requireCsrfToken(?int $visitId = null): void
{
    if (!verifyCsrfToken()) {
        securityFailure(
            'Security validation failed. Please submit the form again.',
            $visitId,
            'INVALID_CSRF'
        );
    }
}

/**
 * Rotate the CSRF token after authentication or other trust-boundary changes.
 */
function rotateCsrfToken(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    return (string)$_SESSION['csrf_token'];
}

/**
 * Store and audit a security rejection when an audit service is available.
 */
function securityFailure(
    string $message,
    ?int $visitId = null,
    string $action = 'SECURITY_DENIED'
): never {
    $_SESSION['error_message'] = $message;

    if (isset($GLOBALS['pdo'])) {
        require_once __DIR__ . '/../services/AuditService.php';

        $userId = isset($_SESSION['user']['id'])
            ? (int)$_SESSION['user']['id']
            : null;

        (new AuditService($GLOBALS['pdo']))->log(
            $userId,
            $visitId,
            'Security',
            $action,
            $message
        );
    }

    http_response_code(403);

    exit($message);
}
function field(
    string $name,
    array $patient,
    string $default = ''
): string {

    return e((string)($patient[$name] ?? $default));

}

function selected(
    string $name,
    string $value,
    array $patient
): string {

    return (($patient[$name] ?? '') === $value)
        ? 'selected'
        : '';

}
