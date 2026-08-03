<?php
declare(strict_types=1);

/**
 * Thin guard bootstrap for Central ERP / PeopleOS pages mapped in core_access_route_map.
 */
require_once __DIR__ . '/m360-access-matrix-helper.php';

/**
 * @param string|null $permissionKey Explicit key; null = resolve from SCRIPT_NAME via route map.
 */
function m360_am_guard(?string $permissionKey = null): void
{
    m360_am_require_route_permission($permissionKey);
}

/**
 * Require any one of the listed permission keys (OR).
 *
 * @param list<string> $permissionKeys
 */
function m360_am_guard_any(array $permissionKeys): void
{
    require_once __DIR__ . '/m360-workshop-access-enforcement.php';
    m360_ws_require_any($permissionKeys);
}

/**
 * Explicit action guard (POST/API). Optional jobcard object scope.
 */
function m360_am_guard_action(string $permissionKey, ?int $jobcardId = null): void
{
    require_once __DIR__ . '/m360-workshop-access-enforcement.php';
    m360_ws_require($permissionKey, $jobcardId);
}
