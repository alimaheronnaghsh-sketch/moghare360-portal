<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$public = $root . '/public_html';

function p113p_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p113p_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$bridge = $public . '/includes/m360-online-intake-bridge-helper.php';
$p1Helper = $public . '/includes/m360-online-request-helper.php';
$p1Migration = $root . '/database/migrations/P1_online_request_intake.sql';

$results = [];
$results[] = p113p_pass('Bridge helper exists', is_file($bridge));
$results[] = p113p_pass('P1 online request helper exists', is_file($p1Helper));
$results[] = p113p_pass('P1 migration exists', is_file($p1Migration));

require_once $bridge;

$results[] = p113p_pass('Persistence available check', function_exists('m360_online_bridge_persistence_available'));
$results[] = p113p_pass('Uses m360_online_req_insert', str_contains(p113p_read($bridge), 'm360_online_req_insert'));
$results[] = p113p_pass('P1 table discovered', m360_online_bridge_persistence_table() === 'erp_customer_online_requests');
$results[] = p113p_pass('No JobCard auto create', !preg_match('/moghare360_jobcard_v2_write|CONVERTED_TO_JOBCARD/', p113p_read($bridge)));
$results[] = p113p_pass('Bridge marks reception review', str_contains(p113p_read($bridge), 'bridge_intake'));
$results[] = p113p_pass('Source channel WEBSITE', str_contains(p113p_read($bridge), 'WEBSITE'));

$mig = p113p_read($p1Migration);
$results[] = p113p_pass('No destructive SQL in P1 migration scan', !preg_match('/\bDROP\b|\bDELETE\b|\bTRUNCATE\b/i', $mig));
$results[] = p113p_pass('No new P11.3 migration schema file required', !is_file($root . '/database/migrations/P11_3_online_bridge.sql'));

$pass = 0; $fail = 0;
echo "# P11.3 Online Intake Persistence Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
