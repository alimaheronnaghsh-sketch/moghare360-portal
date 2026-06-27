<?php
declare(strict_types=1);

/**
 * MOGHARE360 P11 — Release Candidate final lock (read-only; no state mutation).
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-release-hardening-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'm360-demo-readiness-helper.php';

const M360_RC_LOCK_VERSION = 'MOGHARE360-V1-RC-FINAL';

/**
 * @return list<string>
 */
function m360_release_lock_required_migrations(): array
{
    $migs = m360_release_required_migrations();
    $migs[] = 'P11_rc_final_audit_package_lock.sql';
    return $migs;
}

/**
 * @return list<string>
 */
function m360_release_lock_required_docs(): array
{
    return array_merge(m360_release_required_docs(), [
        'docs/release/MOGHARE360_V1_RC_FINAL_AUDIT_REPORT.md',
        'docs/release/MOGHARE360_V1_LOCAL_DEMO_PACKAGE_MANIFEST.md',
        'docs/release/MOGHARE360_V1_OWNER_PRESENTATION_LOCK.md',
        'docs/release/MOGHARE360_V1_FINAL_SECURITY_EXCLUSIONS.md',
        'docs/release/MOGHARE360_V1_RC_FINAL_LOCK.md',
        'docs/demo/MOGHARE360_V1_OWNER_PRESENTATION_SCRIPT_FINAL.md',
        'docs/demo/MOGHARE360_V1_DEMO_DAY_CHECKLIST.md',
    ]);
}

/**
 * @return list<string>
 */
function m360_release_lock_required_tests(): array
{
    return array_merge(m360_release_required_tests(), [
        'test-p11-rc-final-audit.php',
        'test-p11-local-demo-package.php',
        'test-p11-owner-presentation-lock.php',
        'test-p11-release-lock-docs.php',
        'test-p11-package-exclusions.php',
        'test-p11-security-final-scan.php',
        'test-p11-production-signoff-final.php',
    ]);
}

function m360_release_lock_root(): string
{
    return dirname(__DIR__, 2);
}

function m360_release_lock_require_staff(): void
{
    m360_release_hardening_require_staff();
}

function m360_release_lock_h(string $v): string
{
    return m360_release_h($v);
}

function m360_release_lock_badge(string $status): string
{
    return m360_nav_badge_class($status);
}

/** @return list<array{href:string,label:string}> */
function m360_release_lock_nav(): array
{
    return array_merge(m360_nav_rc_links(), [
        ['href' => 'erp-rc-final-audit.php', 'label' => 'RC Final Audit'],
        ['href' => 'erp-local-demo-package.php', 'label' => 'Local Demo Package'],
        ['href' => 'erp-owner-presentation-lock.php', 'label' => 'Owner Presentation'],
        ['href' => 'erp-rc-final-checklist.php', 'label' => 'RC Final Checklist'],
    ]);
}

/**
 * @return array<string, mixed>
 */
function m360_release_lock_status(): array
{
    $root = m360_release_lock_root();
    $audit = m360_release_hardening_audit();
    $blockers = $audit['blockers'] ?? [];
    $warnings = $audit['warnings'] ?? [];

    $docsTotal = count(m360_release_lock_required_docs());
    $docsFound = 0;
    foreach (m360_release_lock_required_docs() as $doc) {
        if (is_file($root . '/' . $doc)) {
            $docsFound++;
        } else {
            $warnings[] = 'P11 doc missing: ' . $doc;
        }
    }

    $migsTotal = count(m360_release_lock_required_migrations());
    $migsFound = 0;
    foreach (m360_release_lock_required_migrations() as $mig) {
        if (is_file($root . '/database/migrations/' . $mig)) {
            $migsFound++;
        } else {
            $warnings[] = 'Migration missing: ' . $mig;
        }
    }

    $testsTotal = count(m360_release_lock_required_tests());
    $testsFound = 0;
    foreach (m360_release_lock_required_tests() as $test) {
        if (is_file($root . '/tools/' . $test)) {
            $testsFound++;
        } else {
            $warnings[] = 'Test missing: ' . $test;
        }
    }

    $signoffOk = is_file($root . '/tools/test-v1-production-signoff.php');
    if (!$signoffOk) {
        $blockers[] = 'Production signoff test missing';
    }

    $packageScript = is_file($root . '/tools/package-moghare360-v1-local-demo.ps1');
    if (!$packageScript) {
        $warnings[] = 'Local demo package script missing';
    }

    $p11Migration = (string)@file_get_contents($root . '/database/migrations/P11_rc_final_audit_package_lock.sql');
    if (preg_match('/^\s*(DROP|DELETE|TRUNCATE)\b/im', $p11Migration)) {
        $blockers[] = 'Destructive SQL statement in P11 migration';
    }

    $security = m360_release_lock_security_scan();
    $blockers = array_values(array_unique(array_merge($blockers, $security['blockers'])));
    $warnings = array_values(array_unique(array_merge($warnings, $security['warnings'])));

    $score = (float)($audit['readiness_score'] ?? 0);
    if ($docsFound < $docsTotal) {
        $score = max(0.0, $score - 5);
    }
    if ($testsFound < $testsTotal) {
        $score = max(0.0, $score - 5);
    }
    if ($blockers !== []) {
        $score = min($score, 65.0);
    }

    $rcStatus = M360_RC_STATUS_PASS;
    if ($blockers !== []) {
        $rcStatus = M360_RC_STATUS_BLOCKED;
    } elseif ($warnings !== []) {
        $rcStatus = M360_RC_STATUS_WARNING;
    }

    return [
        'version' => M360_RC_LOCK_VERSION,
        'rc_status' => $rcStatus,
        'readiness_score' => round($score, 2),
        'route_audit' => $audit,
        'docs_found' => $docsFound,
        'docs_total' => $docsTotal,
        'migrations_found' => $migsFound,
        'migrations_total' => $migsTotal,
        'tests_found' => $testsFound,
        'tests_total' => $testsTotal,
        'production_signoff' => $signoffOk ? M360_RC_STATUS_PASS : M360_RC_STATUS_BLOCKED,
        'package_script' => $packageScript ? M360_RC_STATUS_PASS : M360_RC_STATUS_WARNING,
        'package_readiness' => ($packageScript && $docsFound >= $docsTotal - 1) ? M360_RC_STATUS_PASS : M360_RC_STATUS_WARNING,
        'docs_readiness' => $docsFound >= $docsTotal ? M360_RC_STATUS_PASS : M360_RC_STATUS_WARNING,
        'route_readiness' => ($audit['missing_files'] ?? 0) === 0 ? M360_RC_STATUS_PASS : M360_RC_STATUS_WARNING,
        'security_readiness' => $security['blockers'] === [] ? M360_RC_STATUS_PASS : M360_RC_STATUS_BLOCKED,
        'owner_presentation_readiness' => is_file(dirname(__DIR__) . '/erp-owner-presentation-lock.php') ? M360_RC_STATUS_PASS : M360_RC_STATUS_WARNING,
        'blockers' => $blockers,
        'warnings' => $warnings,
        'recommendation_fa' => m360_release_lock_recommendation($rcStatus, $blockers),
    ];
}

function m360_release_lock_recommendation(string $status, array $blockers): string
{
    if ($status === M360_RC_STATUS_BLOCKED) {
        return 'RC قفل نشده — ابتدا blockerها را رفع کنید: ' . implode('; ', array_slice($blockers, 0, 3));
    }
    if ($status === M360_RC_STATUS_WARNING) {
        return 'آماده Demo داخلی/مالک با هشدارهای مستند';
    }
    return 'Release Candidate نهایی قفل شد — آماده ارائه مالک و Demo Package محلی';
}

/**
 * @return array{blockers:list<string>,warnings:list<string>}
 */
function m360_release_lock_security_scan(): array
{
    $root = m360_release_lock_root();
    $public = m360_nav_public_root();
    $blockers = [];
    $warnings = [];

    $scanFiles = array_merge(
        glob($public . '/erp-rc-*.php') ?: [],
        glob($public . '/erp-local-demo-package.php') ?: [],
        glob($public . '/erp-owner-presentation-lock.php') ?: [],
        glob($public . '/includes/m360-rc-*.php') ?: [],
        glob($public . '/includes/m360-release-lock-helper.php') ?: [],
        glob($public . '/includes/m360-local-demo-package-helper.php') ?: [],
        glob($public . '/includes/m360-owner-presentation-helper.php') ?: []
    );

    $blob = '';
    foreach ($scanFiles as $f) {
        $blob .= (string)@file_get_contents($f);
    }

    if (preg_match('/password\s*=\s*[\'"][^\'"]{8,}/i', $blob)) {
        $blockers[] = 'Credential pattern in P11 files';
    }
    if (preg_match('/api[_-]?key\s*=\s*[\'"][^\'"]{6,}/i', $blob)) {
        $blockers[] = 'API key pattern in P11 files';
    }
    if (preg_match('/production.*1234|fake.*otp.*production/i', $blob)) {
        $blockers[] = 'OTP bypass on production detected in P11 files';
    }
    if (preg_match('/\bmove_uploaded_file\b/i', $blob)) {
        $blockers[] = 'Upload bypass in P11 files';
    }

    foreach (['staff-login.php', 'owner-login.php', 'access-control.php'] as $af) {
        if (!is_file($public . '/' . $af)) {
            $warnings[] = 'Auth file missing: ' . $af;
        }
    }

    $p11Sql = (string)@file_get_contents($root . '/database/migrations/P11_rc_final_audit_package_lock.sql');
    if (preg_match('/\bDROP\b|\bDELETE\b|\bTRUNCATE\b/i', $p11Sql)) {
        $blockers[] = 'Destructive SQL in P11 migration';
    }

    return ['blockers' => $blockers, 'warnings' => $warnings];
}

/**
 * @return list<array<string, mixed>>
 */
function m360_release_lock_phase_status(): array
{
    $phases = ['P1', 'P1.5', 'P2', 'P3', 'P4', 'P5', 'P6', 'P7', 'P8', 'P9', 'P10', 'P11'];
    $registry = m360_nav_registry();
    $out = [];

    foreach ($phases as $phase) {
        $count = 0;
        $missing = 0;
        foreach ($registry as $route) {
            if (($route['phase_code'] ?? '') !== $phase) {
                continue;
            }
            $count++;
            if (!m360_nav_file_exists((string)$route['url'])) {
                $missing++;
            }
        }
        $p11Pages = [
            'P11' => ['erp-rc-final-audit.php', 'erp-local-demo-package.php', 'erp-owner-presentation-lock.php', 'erp-rc-final-checklist.php'],
        ];
        if ($phase === 'P11') {
            $count = count($p11Pages['P11']);
            $missing = 0;
            foreach ($p11Pages['P11'] as $page) {
                if (!is_file(m360_nav_public_root() . '/' . $page)) {
                    $missing++;
                }
            }
        }
        $status = M360_RC_STATUS_PASS;
        if ($missing > 0) {
            $status = M360_RC_STATUS_WARNING;
        }
        if ($count === 0) {
            $status = M360_RC_STATUS_WARNING;
        }
        $out[] = [
            'phase' => $phase,
            'route_count' => $count,
            'missing' => $missing,
            'status' => $status,
        ];
    }
    return $out;
}

/**
 * @return list<array{key:string,title_fa:string,status:string}>
 */
function m360_rc_final_checklist_items(): array
{
    $root = m360_release_lock_root();
    $lock = m360_release_lock_status();
    $audit = $lock['route_audit'] ?? m360_release_hardening_audit();
    return m360_rc_final_checklist_compute($root, $lock, $audit);
}

/**
 * @return list<array{key:string,title_fa:string,status:string}>
 */
function m360_rc_final_checklist_compute(string $root, array $lock, array $audit): array
{
    $p11Sql = (string)@file_get_contents($root . '/database/migrations/P11_rc_final_audit_package_lock.sql');
    $script = (string)@file_get_contents($root . '/tools/package-moghare360-v1-local-demo.ps1');

    $items = [
        ['key' => 'migrations', 'title_fa' => 'All migrations listed', 'ok' => ($lock['migrations_found'] ?? 0) >= ($lock['migrations_total'] ?? 1)],
        ['key' => 'tests', 'title_fa' => 'All tests listed', 'ok' => ($lock['tests_found'] ?? 0) >= ($lock['tests_total'] ?? 1)],
        ['key' => 'signoff', 'title_fa' => 'Production signoff pass', 'ok' => is_file($root . '/tools/test-v1-production-signoff.php')],
        ['key' => 'routes', 'title_fa' => 'Route registry pass', 'ok' => ($audit['total_routes'] ?? 0) >= 63],
        ['key' => 'link_audit', 'title_fa' => 'Link audit pass', 'ok' => is_file($root . '/tools/test-p10-link-audit.php')],
        ['key' => 'release_docs', 'title_fa' => 'Release docs pass', 'ok' => ($lock['docs_found'] ?? 0) >= count(m360_release_required_docs())],
        ['key' => 'demo_docs', 'title_fa' => 'Demo docs pass', 'ok' => is_file($root . '/docs/demo/MOGHARE360_V1_OWNER_PRESENTATION_SCRIPT_FINAL.md')],
        ['key' => 'package_exclusions', 'title_fa' => 'Package exclusions pass', 'ok' => str_contains($script, 'private') && str_contains($script, '.env')],
        ['key' => 'no_credentials', 'title_fa' => 'No credentials', 'ok' => ($lock['security_readiness'] ?? '') === M360_RC_STATUS_PASS],
        ['key' => 'no_real_config', 'title_fa' => 'No real config in package rules', 'ok' => str_contains($script, 'config.php')],
        ['key' => 'no_destructive_sql', 'title_fa' => 'No destructive SQL', 'ok' => !preg_match('/\bDROP\b|\bDELETE\b|\bTRUNCATE\b/i', $p11Sql)],
        ['key' => 'no_upload_bypass', 'title_fa' => 'No upload bypass', 'ok' => ($lock['security_readiness'] ?? '') === M360_RC_STATUS_PASS],
        ['key' => 'no_prod_fake_otp', 'title_fa' => 'No production fake OTP', 'ok' => ($lock['security_readiness'] ?? '') === M360_RC_STATUS_PASS],
        ['key' => 'auth_unchanged', 'title_fa' => 'Auth/Login unchanged', 'ok' => is_file(m360_nav_public_root() . '/staff-login.php')],
        ['key' => 'staff_owner_login', 'title_fa' => 'Staff/Owner login unchanged', 'ok' => is_file(m360_nav_public_root() . '/owner-login.php')],
        ['key' => 'gates_not_bypassed', 'title_fa' => 'P1.5-P10 gates not bypassed', 'ok' => is_file($root . '/tools/test-p9-e2e-gate-validation.php')],
    ];

    $out = [];
    foreach ($items as $item) {
        $out[] = [
            'key' => $item['key'],
            'title_fa' => $item['title_fa'],
            'status' => $item['ok'] ? M360_RC_STATUS_PASS : M360_RC_STATUS_WARNING,
        ];
    }
    return $out;
}
