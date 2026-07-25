<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Permission Guard
|--------------------------------------------------------------------------
|
| This file provides helper functions for checking the currently
| authenticated user's role and department.
|
*/

require_once __DIR__ . '/auth.php';

/**
 * Require one of the specified roles.
 *
 * @param array $roles
 * @return void
 */
function requireRole(array $roles): void
{
    global $currentUser;

    if (!$currentUser) {

        http_response_code(403);

        exit('Access Denied.');

    }

    if (!in_array($currentUser['role_name'], $roles, true)) {

        http_response_code(403);

        exit('You do not have permission to access this page.');

    }
}

/**
 * Require one of the specified departments.
 *
 * @param array $departments
 * @return void
 */
function requireDepartment(array $departments): void
{
    global $currentUser;

    if (!$currentUser) {

        http_response_code(403);

        exit('Access Denied.');

    }

    if (!in_array($currentUser['department_name'], $departments, true)) {

        http_response_code(403);

        exit('You do not have permission to access this page.');

    }
}

/**
 * Check if the current user has a role.
 *
 * @param string $role
 * @return bool
 */
function hasRole(string $role): bool
{
    global $currentUser;

    return $currentUser !== null
        && $currentUser['role_name'] === $role;
}

/**
 * Check if the current user belongs to a department.
 *
 * @param string $department
 * @return bool
 */
function hasDepartment(string $department): bool
{
    global $currentUser;

    return $currentUser !== null
        && $currentUser['department_name'] === $department;
}