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