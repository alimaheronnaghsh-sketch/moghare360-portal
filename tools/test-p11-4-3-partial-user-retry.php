<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pub = $root . '/public_html';

function p1143r_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p1143r_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$rolePage = p1143r_read($pub . '/erp-access-role-assign.php');
$userHelper = p1143r_read($pub . '/includes/m360-access-user-helper.php');
$roleHelper = p1143r_read($pub . '/includes/m360-access-role-helper.php');
$mgmt = p1143r_read($pub . '/includes/m360-access-management-helper.php');

$results = [];
$results[] = p1143r_pass('role assign page calls m360_access_role_assign', str_contains($rolePage, 'm360_access_role_assign('));
$results[] = p1143r_pass('create staff calls same m360_access_role_assign', str_contains($userHelper, 'm360_access_role_assign($conn, $actorUserId, $userId'));
$results[] = p1143r_pass('Persian role grant request failure constant', str_contains($mgmt, 'M360_ACCESS_ROLE_GRANT_REQUEST_FAILED_FA'));
$results[] = p1143r_pass('role helper throws Persian grant failure', str_contains($roleHelper, 'M360_ACCESS_ROLE_GRANT_REQUEST_FAILED_FA'));
$results[] = p1143r_pass('create uses transaction begin', str_contains($userHelper, 'm360_access_mgmt_tx_begin'));
$results[] = p1143r_pass('create rolls back on failure', str_contains($userHelper, 'm360_access_mgmt_tx_rollback'));
$results[] = p1143r_pass('create commits on success', str_contains($userHelper, 'm360_access_mgmt_tx_commit'));
$results[] = p1143r_pass('role assign supports assign action', str_contains($rolePage, "action === 'assign'"));

$pass = 0; $fail = 0;
echo "# P11.4.3 Partial User Retry Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
