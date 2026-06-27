<?php
declare(strict_types=1);

/**
 * MOGHARE360 P10 — Release hardening metrics (read-only).
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-navigation-registry.php';

const M360_RC_STATUS_PASS = 'PASS';
const M360_RC_STATUS_WARNING = 'WARNING';
const M360_RC_STATUS_BLOCKED = 'BLOCKED';

/**
 * @return array<string, mixed>
 */
function m360_release_hardening_audit(): array
{
    $root = dirname(__DIR__, 2);
    $public = m360_nav_public_root();
    $routes = m360_nav_registry();
    $existing = 0;
    $missing = [];
    $apiRoutes = [];
    $postRoutes = [];
    $customerRoutes = [];
    $staffRoutes = [];
    $warnings = [];
    $blockers = [];

    foreach ($routes as $route) {
        $url = (string)$route['url'];
        $exists = m360_nav_file_exists($url);
        if ($exists) {
            $existing++;
        } else {
            $missing[] = ['route_key' => $route['route_key'], 'url' => $url, 'phase' => $route['phase_code']];
            $warnings[] = 'Route file missing: ' . $url . ' (' . $route['route_key'] . ')';
        }
        if (!empty($route['is_api'])) {
            $apiRoutes[] = $route;
        }
        if (strtoupper((string)$route['expected_method']) === 'POST') {
            $postRoutes[] = $route;
        }
        if (!empty($route['is_customer_entry'])) {
            $customerRoutes[] = $route;
        }
        if (!empty($route['is_staff_entry'])) {
            $staffRoutes[] = $route;
        }
    }

    $docs = m360_release_required_docs();
    $docsFound = 0;
    $docsMissing = [];
    foreach ($docs as $doc) {
        $path = $root . '/' . $doc;
        if (is_file($path)) {
            $docsFound++;
        } else {
            $docsMissing[] = $doc;
            $warnings[] = 'Doc missing: ' . $doc;
        }
    }

    $migrations = m360_release_required_migrations();
    $migFound = 0;
    foreach ($migrations as $mig) {
        if (is_file($root . '/database/migrations/' . $mig)) {
            $migFound++;
        } else {
            $warnings[] = 'Migration missing: ' . $mig;
        }
    }

    $tests = m360_release_required_tests();
    $testFound = 0;
    foreach ($tests as $test) {
        if (is_file($root . '/tools/' . $test)) {
            $testFound++;
        } else {
            $warnings[] = 'Test missing: ' . $test;
        }
    }

    $p10Migration = (string)@file_get_contents($root . '/database/migrations/P10_release_hardening_navigation_rc.sql');
    if (preg_match('/\b(DROP|DELETE|TRUNCATE)\b/i', $p10Migration)) {
        $blockers[] = 'Destructive SQL in P10 migration';
    }

    $forbiddenPatterns = m360_release_scan_forbidden_patterns($public, $root);
    $blockers = array_merge($blockers, $forbiddenPatterns['blockers']);
    $warnings = array_merge($warnings, $forbiddenPatterns['warnings']);

    $total = count($routes);
    $score = $total > 0 ? round(($existing / $total) * 100, 2) : 0.0;
    if ($docsFound < count($docs)) {
        $score = max(0.0, $score - 5);
    }
    if ($blockers !== []) {
        $score = min($score, 70.0);
    }

    $rcStatus = M360_RC_STATUS_PASS;
    if ($blockers !== []) {
        $rcStatus = M360_RC_STATUS_BLOCKED;
    } elseif ($missing !== [] || $warnings !== []) {
        $rcStatus = M360_RC_STATUS_WARNING;
    }

    return [
        'total_routes' => $total,
        'existing_files' => $existing,
        'missing_files' => count($missing),
        'missing_routes' => $missing,
        'api_routes' => count($apiRoutes),
        'post_action_routes' => count($postRoutes),
        'customer_routes' => count($customerRoutes),
        'staff_routes' => count($staffRoutes),
        'docs_found' => $docsFound,
        'docs_missing' => $docsMissing,
        'docs_total' => count($docs),
        'migrations_found' => $migFound,
        'migrations_total' => count($migrations),
        'tests_found' => $testFound,
        'tests_total' => count($tests),
        'readiness_score' => $score,
        'warnings' => array_values(array_unique($warnings)),
        'blockers' => array_values(array_unique($blockers)),
        'rc_status' => $rcStatus,
        'version' => M360_NAV_RC_VERSION,
    ];
}

/**
 * @return list<string>
 */
function m360_release_required_migrations(): array
{
    return [
        'P1_online_request_intake.sql',
        'P1_5_intake_contract_signature.sql',
        'P2_reception_jobcard_workflow.sql',
        'P3_technical_operation_workflow.sql',
        'P4_estimate_approval_parts_finance_gate.sql',
        'P5_work_execution_parts_consumption.sql',
        'P6_qc_final_inspection_delivery_readiness.sql',
        'P7_final_invoice_settlement_customer_delivery.sql',
        'P8_management_dashboard_owner_control.sql',
        'P9_end_to_end_soft_run.sql',
        'P10_release_hardening_navigation_rc.sql',
    ];
}

/**
 * @return list<string>
 */
function m360_release_required_tests(): array
{
    return [
        'test-p10-navigation-registry.php',
        'test-p10-route-map.php',
        'test-p10-link-audit.php',
        'test-p10-release-hardening.php',
        'test-p10-demo-package-rc.php',
        'test-p10-security-scope-control.php',
        'test-p10-production-signoff-integration.php',
        'test-v1-production-signoff.php',
    ];
}

/**
 * @return list<string>
 */
function m360_release_required_docs(): array
{
    return [
        'docs/release/MOGHARE360_V1_RC_MANIFEST.md',
        'docs/release/MOGHARE360_V1_DEMO_PACKAGE_RC.md',
        'docs/release/MOGHARE360_V1_RELEASE_READINESS_REPORT.md',
        'docs/release/MOGHARE360_V1_ROUTE_MAP.md',
        'docs/release/MOGHARE360_V1_SECURITY_SCOPE_LOCK.md',
        'docs/demo/MOGHARE360_V1_OWNER_DEMO_RUNBOOK.md',
    ];
}

/**
 * @return array{blockers:list<string>,warnings:list<string>}
 */
function m360_release_scan_forbidden_patterns(string $public, string $root): array
{
    $blockers = [];
    $warnings = [];

    $p10Files = glob($public . '/erp-product-home.php') ?: [];
    $p10Files = array_merge($p10Files, glob($public . '/erp-demo-package-rc.php') ?: []);
    $p10Files = array_merge($p10Files, glob($public . '/erp-release-readiness.php') ?: []);
    $p10Files = array_merge($p10Files, glob($public . '/erp-route-map.php') ?: []);
    $p10Files = array_merge($p10Files, glob($public . '/erp-link-audit.php') ?: []);
    $p10Files = array_merge($p10Files, glob($public . '/includes/m360-*release*.php') ?: []);
    $p10Files = array_merge($p10Files, glob($public . '/includes/m360-navigation-registry.php') ?: []);

    $blob = '';
    foreach ($p10Files as $f) {
        $blob .= (string)@file_get_contents($f);
    }

    if (preg_match('/password\s*=\s*[\'"][^\'"]{8,}/i', $blob)) {
        $blockers[] = 'Credential pattern in P10 files';
    }
    if (preg_match('/production.*1234|fake.*otp.*production/i', $blob)) {
        $blockers[] = 'Production fake OTP in P10 files';
    }
    if (preg_match('/\bmove_uploaded_file\b/i', $blob)) {
        $blockers[] = 'Upload bypass in P10 files';
    }
    if (preg_match('/\b(INSERT INTO|UPDATE)\s+dbo\.erp_(jobcards|final_invoices|payments)\b/i', $blob)) {
        $blockers[] = 'Operational table mutation in P10 read-only pages';
    }

    $authFiles = ['staff-login.php', 'owner-login.php', 'access-control.php'];
    foreach ($authFiles as $af) {
        if (!is_file($public . '/' . $af)) {
            $warnings[] = 'Auth file missing: ' . $af;
        }
    }

    return ['blockers' => $blockers, 'warnings' => $warnings];
}

function m360_release_hardening_require_staff(): void
{
    m360_nav_require_staff();
}

function m360_release_h(string $v): string
{
    return m360_nav_h($v);
}
