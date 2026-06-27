<?php
declare(strict_types=1);

/**
 * MOGHARE360 P11 — RC final audit (read-only aggregation).
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-release-lock-helper.php';

/**
 * @return array<string, mixed>
 */
function m360_rc_final_audit_report(): array
{
    $lock = m360_release_lock_status();
    $audit = $lock['route_audit'] ?? m360_release_hardening_audit();

    $categories = [
        ['key' => 'phases_p1_p10', 'title' => 'P1–P10 Phases', 'status' => M360_RC_STATUS_PASS, 'evidence' => 'Navigation registry + workflow pages'],
        ['key' => 'routes', 'title' => 'Route Registry', 'status' => (int)($audit['missing_files'] ?? 0) === 0 ? M360_RC_STATUS_PASS : M360_RC_STATUS_WARNING, 'evidence' => ($audit['existing_files'] ?? 0) . '/' . ($audit['total_routes'] ?? 0) . ' files'],
        ['key' => 'docs', 'title' => 'Release Docs', 'status' => ($lock['docs_found'] ?? 0) >= ($lock['docs_total'] ?? 1) ? M360_RC_STATUS_PASS : M360_RC_STATUS_WARNING, 'evidence' => ($lock['docs_found'] ?? 0) . '/' . ($lock['docs_total'] ?? 0)],
        ['key' => 'migrations', 'title' => 'Migrations P1–P11', 'status' => ($lock['migrations_found'] ?? 0) >= ($lock['migrations_total'] ?? 1) ? M360_RC_STATUS_PASS : M360_RC_STATUS_WARNING, 'evidence' => ($lock['migrations_found'] ?? 0) . '/' . ($lock['migrations_total'] ?? 0)],
        ['key' => 'tests', 'title' => 'Test Suites', 'status' => ($lock['tests_found'] ?? 0) >= ($lock['tests_total'] ?? 1) ? M360_RC_STATUS_PASS : M360_RC_STATUS_WARNING, 'evidence' => ($lock['tests_found'] ?? 0) . '/' . ($lock['tests_total'] ?? 0)],
        ['key' => 'signoff', 'title' => 'Production Signoff', 'status' => (string)($lock['production_signoff'] ?? M360_RC_STATUS_WARNING), 'evidence' => 'test-v1-production-signoff.php'],
        ['key' => 'security', 'title' => 'Security Lock', 'status' => (string)($lock['security_readiness'] ?? M360_RC_STATUS_WARNING), 'evidence' => 'MOGHARE360_V1_FINAL_SECURITY_EXCLUSIONS.md'],
        ['key' => 'package', 'title' => 'Package Exclusions', 'status' => (string)($lock['package_readiness'] ?? M360_RC_STATUS_WARNING), 'evidence' => 'package-moghare360-v1-local-demo.ps1'],
        ['key' => 'owner', 'title' => 'Owner Presentation', 'status' => (string)($lock['owner_presentation_readiness'] ?? M360_RC_STATUS_WARNING), 'evidence' => 'erp-owner-presentation-lock.php'],
    ];

    return [
        'lock' => $lock,
        'categories' => $categories,
        'phase_status' => m360_release_lock_phase_status(),
        'read_only' => true,
    ];
}
