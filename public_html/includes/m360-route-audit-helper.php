<?php
declare(strict_types=1);

/**
 * MOGHARE360 P10 — Route / link audit (read-only file_exists; no HTTP calls).
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-navigation-registry.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-release-hardening-helper.php';

/**
 * @return list<array<string, mixed>>
 */
function m360_route_audit_rows(): array
{
    $rows = [];
    foreach (m360_nav_registry() as $route) {
        $url = (string)$route['url'];
        $exists = m360_nav_file_exists($url);
        $status = $exists ? M360_RC_STATUS_PASS : M360_RC_STATUS_WARNING;
        $rows[] = array_merge($route, [
            'file_exists' => $exists,
            'audit_status' => $status,
            'resolved_path' => m360_nav_resolve_file_path($url),
        ]);
    }
    return $rows;
}

/**
 * @return array<string, mixed>
 */
function m360_route_audit_summary(): array
{
    $rows = m360_route_audit_rows();
    $missing = array_values(array_filter($rows, static fn(array $r): bool => empty($r['file_exists'])));
    $apiMissing = array_values(array_filter($missing, static fn(array $r): bool => !empty($r['is_api'])));
    $actionMissing = array_values(array_filter($missing, static fn(array $r): bool => strtoupper((string)$r['expected_method']) === 'POST'));
    $customerMissing = array_values(array_filter($missing, static fn(array $r): bool => !empty($r['is_customer_entry'])));

    return [
        'total' => count($rows),
        'existing' => count($rows) - count($missing),
        'missing' => count($missing),
        'missing_api' => count($apiMissing),
        'missing_post_actions' => count($actionMissing),
        'missing_customer' => count($customerMissing),
        'rows' => $rows,
        'missing_rows' => $missing,
    ];
}
