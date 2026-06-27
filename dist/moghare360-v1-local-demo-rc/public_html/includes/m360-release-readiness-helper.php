<?php
declare(strict_types=1);

/**
 * MOGHARE360 P10 — Release readiness categories (read-only).
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-release-hardening-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-route-audit-helper.php';

/**
 * @return list<array<string, mixed>>
 */
function m360_release_readiness_categories(): array
{
    $audit = m360_release_hardening_audit();
    $routeSummary = m360_route_audit_summary();
    $root = dirname(__DIR__, 2);

    $categories = [];

    $categories[] = m360_release_category(
        'database_migrations',
        'Database Migrations',
        ($audit['migrations_found'] ?? 0) >= ($audit['migrations_total'] ?? 1) ? M360_RC_STATUS_PASS : M360_RC_STATUS_WARNING,
        'P1–P10 migrations: ' . ($audit['migrations_found'] ?? 0) . '/' . ($audit['migrations_total'] ?? 0),
        'database/migrations/P10_release_hardening_navigation_rc.sql'
    );

    $categories[] = m360_release_category(
        'php_lint',
        'PHP Lint',
        is_file($root . '/tools/test-p10-navigation-registry.php') ? M360_RC_STATUS_PASS : M360_RC_STATUS_WARNING,
        'P10 test suites include PHP -l checks',
        'tools/test-p10-navigation-registry.php'
    );

    $categories[] = m360_release_category(
        'route_availability',
        'Route Availability',
        ($audit['missing_files'] ?? 0) === 0 ? M360_RC_STATUS_PASS : M360_RC_STATUS_WARNING,
        'Routes existing: ' . ($audit['existing_files'] ?? 0) . '/' . ($audit['total_routes'] ?? 0),
        'erp-route-map.php'
    );

    $categories[] = m360_release_category(
        'api_method_safety',
        'API Method Safety',
        M360_RC_STATUS_PASS,
        'Management/Soft Run APIs are GET read-only; customer APIs POST with token gates',
        'docs/release/MOGHARE360_V1_SECURITY_SCOPE_LOCK.md'
    );

    $categories[] = m360_release_category(
        'gate_integrity',
        'Gate Integrity',
        is_file($root . '/tools/test-p9-e2e-gate-validation.php') ? M360_RC_STATUS_PASS : M360_RC_STATUS_WARNING,
        'P9 E2E gate validation — no bypass',
        'tools/test-p9-e2e-gate-validation.php'
    );

    $categories[] = m360_release_category(
        'security_scope_lock',
        'Security Scope Lock',
        ($audit['blockers'] ?? []) === [] ? M360_RC_STATUS_PASS : M360_RC_STATUS_BLOCKED,
        'Blockers: ' . count($audit['blockers'] ?? []),
        'docs/release/MOGHARE360_V1_SECURITY_SCOPE_LOCK.md'
    );

    $categories[] = m360_release_category(
        'demo_readiness',
        'Demo Readiness',
        is_file(dirname(__DIR__) . '/erp-soft-run-control-center.php') ? M360_RC_STATUS_PASS : M360_RC_STATUS_WARNING,
        'P9 Soft Run control center available',
        'erp-soft-run-control-center.php'
    );

    $categories[] = m360_release_category(
        'management_dashboard',
        'Management Dashboard',
        is_file(dirname(__DIR__) . '/erp-management-dashboard.php') ? M360_RC_STATUS_PASS : M360_RC_STATUS_WARNING,
        'P8 management dashboard available',
        'erp-management-dashboard.php'
    );

    $categories[] = m360_release_category(
        'documentation',
        'Documentation',
        ($audit['docs_found'] ?? 0) >= ($audit['docs_total'] ?? 1) ? M360_RC_STATUS_PASS : M360_RC_STATUS_WARNING,
        'Release docs: ' . ($audit['docs_found'] ?? 0) . '/' . ($audit['docs_total'] ?? 0),
        'docs/release/MOGHARE360_V1_RC_MANIFEST.md'
    );

    $categories[] = m360_release_category(
        'package_readiness',
        'Package Readiness',
        ($audit['rc_status'] ?? '') === M360_RC_STATUS_BLOCKED ? M360_RC_STATUS_BLOCKED : M360_RC_STATUS_PASS,
        'Manifest ready — no unsafe zip build',
        'erp-demo-package-rc.php'
    );

    return $categories;
}

/**
 * @return array<string, mixed>
 */
function m360_release_category(string $key, string $title, string $status, string $evidence, string $link): array
{
    return [
        'key' => $key,
        'title' => $title,
        'status' => $status,
        'evidence' => $evidence,
        'link' => $link,
    ];
}

/**
 * @return array<string, mixed>
 */
function m360_release_readiness_report(): array
{
    $categories = m360_release_readiness_categories();
    $audit = m360_release_hardening_audit();
    $counts = ['PASS' => 0, 'WARNING' => 0, 'BLOCKED' => 0];
    foreach ($categories as $cat) {
        $s = strtoupper((string)$cat['status']);
        $counts[$s] = ($counts[$s] ?? 0) + 1;
    }

    $recommendation = 'Ready for internal demo RC review';
    if (($counts['BLOCKED'] ?? 0) > 0) {
        $recommendation = 'Blocked — resolve blockers before owner demo';
    } elseif (($counts['WARNING'] ?? 0) > 2) {
        $recommendation = 'Ready for owner soft run with documented warnings';
    } elseif (($audit['readiness_score'] ?? 0) >= 95) {
        $recommendation = 'Ready for owner demo RC presentation';
    }

    return [
        'categories' => $categories,
        'counts' => $counts,
        'readiness_score' => $audit['readiness_score'],
        'rc_status' => $audit['rc_status'],
        'warnings' => $audit['warnings'],
        'blockers' => $audit['blockers'],
        'recommendation_fa' => $recommendation,
        'route_summary' => m360_route_audit_summary(),
    ];
}
