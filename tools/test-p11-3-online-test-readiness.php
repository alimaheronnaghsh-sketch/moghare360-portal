<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$public = $root . '/public_html';

function p113r_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p113r_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$page = $public . '/erp-online-test-readiness.php';
$bridge = $public . '/includes/m360-online-intake-bridge-helper.php';

$results = [];
$results[] = p113r_pass('Readiness page exists', is_file($page));
$results[] = p113r_pass('Bridge helper exists', is_file($bridge));

require_once $bridge;

$report = m360_online_bridge_readiness_report();
$results[] = p113r_pass('PASS/WARNING/BLOCKED logic', in_array((string)($report['status'] ?? ''), ['PASS', 'WARNING', 'BLOCKED'], true));
$results[] = p113r_pass('Secret masked field', isset($report['secret_masked']) && !str_contains((string)$report['secret_masked'], 'PUT_LONG_RANDOM_SECRET_HERE'));
$results[] = p113r_pass('Staff gate on page', str_contains(p113r_read($page), 'm360_release_lock_require_staff'));
$results[] = p113r_pass('No raw secret output', !preg_match('/bridge_secret\s*[=:]/i', p113r_read($page)));
$results[] = p113r_pass('No raw IP output', !preg_match('/\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b/', p113r_read($page)));
$results[] = p113r_pass('Public debug disabled flag', ($report['public_debug_disabled'] ?? false) === true);

$pass = 0; $fail = 0;
echo "# P11.3 Online Test Readiness Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
