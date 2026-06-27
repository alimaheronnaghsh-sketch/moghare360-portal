<?php
declare(strict_types=1);

/**
 * MOGHARE360 P10 — Demo Package RC manifest (read-only; no zip build unless safe tool exists).
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-release-hardening-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-navigation-registry.php';

/**
 * @return array<string, mixed>
 */
function m360_demo_package_manifest(): array
{
    $audit = m360_release_hardening_audit();
    $demoEntries = m360_nav_demo_entries();
    $ownerEntries = m360_nav_owner_entries();

    $packageScriptSafe = false;
    $packageScriptPath = dirname(__DIR__, 2) . '/tools/test-phase-v1-final-delivery-package.php';
    if (is_file($packageScriptPath)) {
        $script = (string)file_get_contents($packageScriptPath);
        $packageScriptSafe = !preg_match('/\b(shell_exec|exec\(|system\(|passthru)\b/i', $script)
            || str_contains($script, 'read-only');
    }

    $exclusions = [
        'no_accounting_voucher',
        'no_ledger',
        'no_payment_gateway',
        'no_bank_integration',
        'no_tax_official_integration',
        'no_saas_tenant',
        'no_production_deploy',
        'no_real_customer_data',
        'no_credentials_in_repo',
    ];

    return [
        'rc_version' => M360_NAV_RC_VERSION,
        'rc_status' => $audit['rc_status'],
        'readiness_score' => $audit['readiness_score'],
        'demo_entry_points' => array_map(static fn(array $r): array => [
            'title_fa' => $r['title_fa'],
            'url' => $r['url'],
            'phase' => $r['phase_code'],
        ], $demoEntries),
        'owner_entry_points' => array_map(static fn(array $r): array => [
            'title_fa' => $r['title_fa'],
            'url' => $r['url'],
        ], $ownerEntries),
        'owner_demo_order' => [
            'erp-product-home.php',
            'erp-soft-run-control-center.php',
            'erp-end-to-end-demo-scenario.php',
            'erp-management-dashboard.php',
            'erp-owner-control-center.php',
            'erp-demo-package-rc.php',
        ],
        'migrations' => m360_release_required_migrations(),
        'tests' => m360_release_required_tests(),
        'exclusions' => $exclusions,
        'package_zip_available' => false,
        'package_script_safe_detected' => $packageScriptSafe,
        'package_build_note' => 'P10 does not build zip packages — manifest only unless pre-approved safe tool exists.',
    ];
}
