<?php
/**
 * MOGHARE360 ERP — Phase 13 Security & Access Hardening CLI Test
 */

declare(strict_types=1);

const P13_BUILT = [
    'public_html/includes/moghare360-security-audit-helper.php',
    'public_html/erp-security-hardening-dashboard.php',
    'public_html/erp-write-route-audit.php',
    'public_html/erp-csrf-audit.php',
    'public_html/erp-role-access-matrix.php',
    'public_html/erp-error-handling-audit.php',
    'public_html/erp-sensitive-boundary-report.php',
    'public_html/assets/moghare360-ui/moghare360-security-hardening.css',
];

const P13_PHP_SYNTAX = [
    'public_html/includes/moghare360-security-audit-helper.php',
    'public_html/erp-security-hardening-dashboard.php',
    'public_html/erp-write-route-audit.php',
    'public_html/erp-csrf-audit.php',
    'public_html/erp-role-access-matrix.php',
    'public_html/erp-error-handling-audit.php',
    'public_html/erp-sensitive-boundary-report.php',
];

const P13_AUDIT_PAGES = [
    'public_html/erp-security-hardening-dashboard.php',
    'public_html/erp-write-route-audit.php',
    'public_html/erp-csrf-audit.php',
    'public_html/erp-role-access-matrix.php',
    'public_html/erp-error-handling-audit.php',
    'public_html/erp-sensitive-boundary-report.php',
];

const P13_BOUNDARY_PHRASES = [
    'Not Production',
    'Not SaaS',
    'Not Customer Portal',
    'Not Official Accounting',
];

const P13_MISSION_DOCS = [
    'docs/missions/phase_13_security_access_hardening/PHASE_13_00_INDEX.md',
    'docs/missions/phase_13_security_access_hardening/PHASE_13_01_SCOPE.md',
    'docs/missions/phase_13_security_access_hardening/PHASE_13_02_WRITE_ROUTE_AUDIT.md',
    'docs/missions/phase_13_security_access_hardening/PHASE_13_03_CSRF_AUDIT.md',
    'docs/missions/phase_13_security_access_hardening/PHASE_13_04_ROLE_ACCESS_MATRIX.md',
    'docs/missions/phase_13_security_access_hardening/PHASE_13_05_ERROR_HANDLING_AUDIT.md',
    'docs/missions/phase_13_security_access_hardening/PHASE_13_06_SENSITIVE_BOUNDARY_REPORT.md',
    'docs/missions/phase_13_security_access_hardening/PHASE_13_90_TEST_RESULT.md',
    'docs/missions/phase_13_security_access_hardening/PHASE_13_99_SIGNOFF.md',
];

const P13_FORBIDDEN = [
    'staff-auth.php', 'access-control.php', 'staff-login.php', 'config.php', 'config.example.php',
    'private/erp-config.php', 'private/erp-config.example.php',
];

function p13_root(): string { return dirname(__DIR__); }
function p13_line(string $l, string $s): void { echo str_pad($l, 52, '.') . ' ' . $s . PHP_EOL; }
function p13_php(): string {
    foreach ([getenv('PHP_BINARY') ?: '', 'C:\\xampp\\php\\php.exe', 'php'] as $c) {
        if ($c === '') continue;
        if ($c === 'php' || is_file($c)) return $c;
    }
    return 'php';
}

$root = p13_root();
$ok = true;
$fail = [];

echo 'PHASE 13 SECURITY HARDENING TEST' . PHP_EOL . str_repeat('=', 52) . PHP_EOL;

$sqlPath = $root . '/public_html/sql/sqlserver/phase_13_security_access_hardening.sql';
p13_line('SQL phase_13 file', is_file($sqlPath) ? 'FOUND (optional)' : 'NOT REQUIRED');

foreach (P13_BUILT as $rel) {
    $fp = $root . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $pass = is_file($fp);
    p13_line('Built ' . basename($rel), $pass ? 'PASSED' : 'FAILED');
    if (!$pass) { $ok = false; $fail[] = $rel; }
}

echo str_repeat('-', 52) . PHP_EOL . 'PHP syntax:' . PHP_EOL;
$phpBin = p13_php();
foreach (P13_PHP_SYNTAX as $rel) {
    $fp = $root . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (!is_file($fp)) {
        p13_line('Syntax ' . basename($rel), 'FAILED (missing)');
        $ok = false;
        continue;
    }
    $out = [];
    $code = 0;
    exec('"' . $phpBin . '" -l "' . $fp . '" 2>&1', $out, $code);
    p13_line('Syntax ' . basename($rel), $code === 0 ? 'PASSED' : 'FAILED');
    if ($code !== 0) { $ok = false; $fail[] = 'syntax:' . $rel; }
}

echo str_repeat('-', 52) . PHP_EOL . 'Mission docs:' . PHP_EOL;
foreach (P13_MISSION_DOCS as $rel) {
    $fp = $root . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $pass = is_file($fp);
    p13_line('Doc ' . basename($rel), $pass ? 'PASSED' : 'FAILED');
    if (!$pass) { $ok = false; $fail[] = $rel; }
}

echo str_repeat('-', 52) . PHP_EOL . 'Audit pages:' . PHP_EOL;
foreach (P13_AUDIT_PAGES as $rel) {
    p13_line('Page ' . basename($rel), is_file($root . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel)) ? 'PASSED' : 'FAILED');
}

echo str_repeat('-', 52) . PHP_EOL . 'Boundary phrases in audit pages:' . PHP_EOL;
foreach (P13_AUDIT_PAGES as $rel) {
    $content = @file_get_contents($root . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel));
    if ($content === false) {
        p13_line('Boundary ' . basename($rel), 'FAILED');
        $ok = false;
        continue;
    }
    $missing = [];
    foreach (P13_BOUNDARY_PHRASES as $phrase) {
        if (!str_contains($content, $phrase)) {
            $missing[] = $phrase;
        }
    }
    p13_line('Boundary ' . basename($rel), $missing === [] ? 'PASSED' : 'FAILED');
    if ($missing !== []) { $ok = false; $fail[] = 'boundary:' . $rel; }
}

echo str_repeat('-', 52) . PHP_EOL . 'Helper load:' . PHP_EOL;
try {
    require_once $root . '/public_html/includes/moghare360-security-audit-helper.php';
    $writes = security_audit_write_routes();
    $matrix = security_audit_role_matrix();
    p13_line('Helper write routes', count($writes) >= 17 ? 'PASSED' : 'FAILED');
    p13_line('Helper role matrix', count($matrix) >= 10 ? 'PASSED' : 'FAILED');
    if (count($writes) < 17 || count($matrix) < 10) { $ok = false; }
} catch (Throwable $e) {
    p13_line('Helper load', 'FAILED: ' . $e->getMessage());
    $ok = false;
}

echo str_repeat('-', 52) . PHP_EOL . 'Forbidden files:' . PHP_EOL;
foreach (P13_FORBIDDEN as $rel) {
    $fp = $root . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (!is_file($fp)) {
        p13_line('Forbidden ' . $rel, 'SKIP');
        continue;
    }
    $gitOut = [];
    exec('git -C "' . $root . '" status --porcelain -- "' . str_replace('\\', '/', $rel) . '" 2>&1', $gitOut);
    $modified = trim(implode('', $gitOut)) !== '';
    p13_line('Forbidden ' . $rel, $modified ? 'FAILED (modified)' : 'OK');
    if ($modified) { $ok = false; $fail[] = 'forbidden:' . $rel; }
}

echo str_repeat('=', 52) . PHP_EOL;
echo 'RESULT: ' . ($ok ? 'PASSED' : 'FAILED') . PHP_EOL;
if (!$ok && $fail !== []) {
    echo 'Failures: ' . implode(', ', $fail) . PHP_EOL;
}
echo 'PHASE 13 SECURITY HARDENING TEST COMPLETE' . PHP_EOL;
exit($ok ? 0 : 1);
