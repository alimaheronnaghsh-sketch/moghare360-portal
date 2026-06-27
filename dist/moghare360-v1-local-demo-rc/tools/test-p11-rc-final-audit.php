<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$public = $root . '/public_html';

function p11a_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p11a_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$auditPage = $public . '/erp-rc-final-audit.php';
$helper = $public . '/includes/m360-rc-final-audit-helper.php';
$lockHelper = $public . '/includes/m360-release-lock-helper.php';

$results = [];
$results[] = p11a_pass('erp-rc-final-audit.php exists', is_file($auditPage));
$results[] = p11a_pass('m360-rc-final-audit-helper exists', is_file($helper));
$results[] = p11a_pass('m360-release-lock-helper exists', is_file($lockHelper));

require_once $lockHelper;
require_once $helper;

$results[] = p11a_pass('m360_rc_final_audit_report exists', function_exists('m360_rc_final_audit_report'));
$results[] = p11a_pass('m360_release_lock_status exists', function_exists('m360_release_lock_status'));

$report = m360_rc_final_audit_report();
$results[] = p11a_pass('Report has categories', count($report['categories'] ?? []) >= 8);
$results[] = p11a_pass('Report has phase_status', is_array($report['phase_status'] ?? null));
$results[] = p11a_pass('P1 phase in audit', (bool)array_filter($report['phase_status'] ?? [], static fn(array $p): bool => ($p['phase'] ?? '') === 'P1'));
$results[] = p11a_pass('P10 phase in audit', (bool)array_filter($report['phase_status'] ?? [], static fn(array $p): bool => ($p['phase'] ?? '') === 'P10'));
$results[] = p11a_pass('P11 phase in audit', (bool)array_filter($report['phase_status'] ?? [], static fn(array $p): bool => ($p['phase'] ?? '') === 'P11'));

$statuses = array_column($report['categories'] ?? [], 'status');
$results[] = p11a_pass('PASS/WARNING/BLOCKED logic', count(array_intersect($statuses, ['PASS', 'WARNING', 'BLOCKED'])) > 0);
$results[] = p11a_pass('Read-only flag', ($report['read_only'] ?? false) === true);
$results[] = p11a_pass('No POST form in audit page', !preg_match('/<form[^>]+method\s*=\s*["\']post/i', p11a_read($auditPage)));
$results[] = p11a_pass('No operational POST handler', !preg_match('/\$_POST\s*\[/', p11a_read($auditPage)));
$results[] = p11a_pass('Shows PASS badge', str_contains(p11a_read($auditPage), 'm360-rcf-badge'));

$pass = 0; $fail = 0;
echo "# P11 RC Final Audit Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
