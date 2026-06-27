<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-access-management-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-access-role-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-access-user-helper.php';

/**
 * @return array{user:array<string,string>,roles:list<array<string,string>>,permissions:list<string>,permission_objects:list<array<string,string>>}
 */
function m360_access_preview_load($conn, int $userId): array
{
    $user = m360_access_user_get($conn, $userId);
    if ($user === null) {
        throw new RuntimeException('User not found.');
    }

    $rolesResult = erp_auth_current_roles($conn, $userId);
    $permResult = erp_auth_current_permissions($conn, $userId);

    $roleObjects = $rolesResult['role_objects'] ?? [];
    foreach ($roleObjects as $idx => $role) {
        if (!is_array($role)) {
            continue;
        }
        if (isset($role['role_name'])) {
            $roleObjects[$idx]['role_name'] = m360_access_text_from_odbc((string)$role['role_name']);
        }
        if (isset($role['role_key'])) {
            $roleObjects[$idx]['role_key'] = m360_access_text_from_odbc((string)$role['role_key']);
        }
    }

    $user['full_name'] = m360_access_text_from_odbc((string)($user['full_name'] ?? ''));
    $user['dept_name'] = m360_access_text_from_odbc((string)($user['dept_name'] ?? ''));
    $user['position_name'] = m360_access_text_from_odbc((string)($user['position_name'] ?? ''));

    return [
        'user' => $user,
        'roles' => $roleObjects,
        'permissions' => $permResult['permission_keys'] ?? [],
        'permission_objects' => $permResult['permission_objects'] ?? [],
    ];
}

/**
 * @return array{routes:list<array<string,mixed>>,warnings:list<string>,mapped_count:int,unmapped_count:int}
 */
function m360_access_preview_routes($conn, int $userId): array
{
    $permResult = erp_auth_current_permissions($conn, $userId);
    $permissionKeys = array_fill_keys($permResult['permission_keys'] ?? [], true);

    $roles = erp_auth_current_roles($conn, $userId);
    $roleKeys = array_fill_keys($roles['role_keys'] ?? [], true);
    $isOwnerLike = !empty($roleKeys['owner']) || !empty($roleKeys['system_admin']) || erp_auth_is_system_owner($conn, $userId);

    $routes = [];
    $warnings = [];
    $mapped = 0;
    $unmapped = 0;

    foreach (m360_nav_registry() as $route) {
        $accessType = (string)($route['access_type'] ?? '');
        if ($accessType === 'public' || $accessType === 'customer') {
            continue;
        }

        $isStaffRoute = $accessType === 'staff' || !empty($route['is_staff_entry']);
        if (!$isStaffRoute) {
            continue;
        }

        $requiresOwner = !empty($route['is_owner_entry']);
        $accessible = false;
        $mappingNote = 'Route catalog only — no explicit permission_key on registry entry';

        if ($requiresOwner && !$isOwnerLike) {
            $accessible = false;
            $mappingNote = 'Owner-flagged route; user lacks owner/system_admin role';
        } elseif ($isOwnerLike) {
            $accessible = true;
            $mappingNote = 'Owner/system_admin effective access (catalog heuristic)';
            $mapped++;
        } else {
            $accessible = count($permissionKeys) > 0;
            $mappingNote = 'Staff route — effective if user has any role permission; module mapping not explicit';
            if ($accessible) {
                $mapped++;
            } else {
                $unmapped++;
            }
        }

        if ($mappingNote !== '' && str_contains($mappingNote, 'not explicit')) {
            $warnings[] = (string)($route['route_key'] ?? '') . ': ' . $mappingNote;
        }

        $routes[] = [
            'route_key' => (string)($route['route_key'] ?? ''),
            'title_fa' => (string)($route['title_fa'] ?? ''),
            'url' => (string)($route['url'] ?? ''),
            'phase_code' => (string)($route['phase_code'] ?? ''),
            'access_type' => $accessType,
            'requires_owner' => $requiresOwner,
            'accessible_heuristic' => $accessible,
            'mapping_note' => $mappingNote,
        ];
    }

    if ($unmapped > 0) {
        $warnings[] = 'Navigation registry lacks per-route permission_key mapping — preview uses heuristic only.';
    }

    return [
        'routes' => $routes,
        'warnings' => array_values(array_unique($warnings)),
        'mapped_count' => $mapped,
        'unmapped_count' => $unmapped,
    ];
}

/**
 * @return array{status:string,checks:list<array<string,string>>}
 */
function m360_access_readiness_report($conn): array
{
    $checks = [];
    $blocked = 0;
    $warnings = 0;

    $staffCount = m360_access_user_count_non_owner_staff($conn);
    $enabledStaff = m360_access_user_count_login_enabled_staff($conn);

    $checks[] = [
        'code' => 'STAFF_USERS_EXIST',
        'title' => 'Staff users created (20001+)',
        'expected' => '>= 1 for one-day run',
        'actual' => (string)$staffCount,
        'status' => $staffCount > 0 ? 'PASS' : 'BLOCKED',
    ];
    if ($staffCount <= 0) {
        $blocked++;
    }

    $checks[] = [
        'code' => 'LOGIN_ENABLED_STAFF',
        'title' => 'Active login-enabled staff',
        'expected' => '>= 1',
        'actual' => (string)$enabledStaff,
        'status' => $enabledStaff > 0 ? 'PASS' : ($staffCount > 0 ? 'WARNING' : 'BLOCKED'),
    ];
    if ($enabledStaff <= 0) {
        $staffCount > 0 ? $warnings++ : $blocked++;
    }

    $sharedOwnerRisk = $enabledStaff <= 0;
    $checks[] = [
        'code' => 'OWNER_SHARED_LOGIN_RISK',
        'title' => 'Owner shared login risk',
        'expected' => 'Staff logins enabled — avoid everyone on owner login',
        'actual' => $sharedOwnerRisk ? 'All staff may still use owner login' : 'Dedicated staff logins available',
        'status' => $sharedOwnerRisk ? 'WARNING' : 'PASS',
    ];
    if ($sharedOwnerRisk) {
        $warnings++;
    }

    foreach (m360_access_mgmt_first_wave_role_codes() as $code) {
        $mapped = m360_access_mgmt_resolve_role_code($code);
        $roleOk = false;
        if ($conn !== false && $mapped !== null) {
            $roleOk = m360_access_role_fetch_by_key($conn, $mapped['role_key']) !== null;
        }
        $checks[] = [
            'code' => 'ROLE_' . $code,
            'title' => 'First-wave role mapping ' . $code,
            'expected' => $mapped['role_key'] ?? 'mapped',
            'actual' => $roleOk ? 'core_roles present' : 'missing in DB',
            'status' => $roleOk ? 'PASS' : 'WARNING',
        ];
        if (!$roleOk) {
            $warnings++;
        }
    }

    $status = M360_SOFT_RUN_STATUS_PASS;
    if ($blocked > 0) {
        $status = M360_SOFT_RUN_STATUS_BLOCKED;
    } elseif ($warnings > 0) {
        $status = M360_SOFT_RUN_STATUS_WARNING;
    }

    return ['status' => $status, 'checks' => $checks];
}

if (!defined('M360_SOFT_RUN_STATUS_PASS')) {
    define('M360_SOFT_RUN_STATUS_PASS', 'PASS');
    define('M360_SOFT_RUN_STATUS_WARNING', 'WARNING');
    define('M360_SOFT_RUN_STATUS_BLOCKED', 'BLOCKED');
}
