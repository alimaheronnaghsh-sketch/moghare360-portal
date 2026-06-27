<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pub = $root . '/public_html';

function p114c_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p114c_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$userHelper = p114c_read($pub . '/includes/m360-access-user-helper.php');
$createPage = p114c_read($pub . '/erp-access-user-create.php');

$results = [];
$results[] = p114c_pass('user helper exists', is_file($pub . '/includes/m360-access-user-helper.php'));
$results[] = p114c_pass('uses core_users INSERT', str_contains($userHelper, 'INSERT INTO dbo.core_users'));
$results[] = p114c_pass('uses password_hash', str_contains($userHelper, 'password_hash('));
$results[] = p114c_pass('no password hash display', !str_contains($userHelper, 'password_hash') || !preg_match('/echo.*password_hash|password_hash.*echo/i', $userHelper));
$results[] = p114c_pass('validates role_code map', str_contains($userHelper, 'm360_access_mgmt_resolve_role_code'));
$results[] = p114c_pass('validates department', str_contains($userHelper, 'm360_access_user_validate_department'));
$results[] = p114c_pass('validates position', str_contains($userHelper, 'm360_access_user_validate_position'));
$results[] = p114c_pass('writes core_user_roles via role helper', str_contains($userHelper, 'm360_access_role_assign'));
$results[] = p114c_pass('writes erp_company_users', str_contains($userHelper, 'm360_access_role_upsert_company_user'));
$results[] = p114c_pass('no staff_users write', !str_contains($userHelper, 'staff_users'));
$results[] = p114c_pass('create page POST handler', str_contains($createPage, 'm360_access_user_create'));

$pass = 0; $fail = 0;
echo "# P11.4 Access User Create Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
