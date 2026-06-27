<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pub = $root . '/public_html';

function p1141_pass(string $n, bool $ok, string $d = ''): array
{
    return ['name' => $n, 'pass' => $ok, 'detail' => $d];
}

function p1141_read(string $p): string
{
    return is_file($p) ? (string)file_get_contents($p) : '';
}

$accessFiles = [
    $pub . '/includes/m360-access-management-helper.php',
    $pub . '/includes/m360-access-user-helper.php',
    $pub . '/includes/m360-access-role-helper.php',
    $pub . '/includes/m360-access-audit-helper.php',
    $pub . '/includes/m360-access-permission-preview-helper.php',
    $pub . '/erp-access-management.php',
    $pub . '/erp-access-user-create.php',
    $pub . '/erp-access-user-edit.php',
    $pub . '/erp-access-role-assign.php',
    $pub . '/erp-access-permission-preview.php',
    $pub . '/erp-access-password-reset.php',
    $pub . '/erp-access-change-history.php',
];

$blob = '';
foreach ($accessFiles as $file) {
    $blob .= p1141_read($file) . "\n";
}

$results = [];

$wrongColumns = ['department_code', 'department_name_fa', 'department_name', 'position_code', 'position_name_fa'];
foreach ($wrongColumns as $col) {
    $results[] = p1141_pass('no ' . $col . ' in P11.4 access files', !str_contains($blob, $col));
}

$results[] = p1141_pass('uses dept_key', str_contains($blob, 'dept_key'));
$results[] = p1141_pass('uses dept_name', str_contains($blob, 'dept_name'));
$results[] = p1141_pass('uses position_key', str_contains($blob, 'position_key'));
$results[] = p1141_pass('uses position_name', str_contains($blob, 'position_name'));
$results[] = p1141_pass('department ORDER BY sort_order, dept_name', str_contains($blob, 'ORDER BY sort_order, dept_name'));
$results[] = p1141_pass('position ORDER BY department_id, sort_order, position_name', str_contains($blob, 'ORDER BY department_id, sort_order, position_name'));

$mgmt = p1141_read($pub . '/includes/m360-access-management-helper.php');
$results[] = p1141_pass('m360_access_h helper exists', str_contains($mgmt, 'function m360_access_h'));
$results[] = p1141_pass('m360_access_fetch_rows exists', str_contains($mgmt, 'function m360_access_fetch_rows'));
$results[] = p1141_pass('m360_access_text_from_odbc exists', str_contains($mgmt, 'function m360_access_text_from_odbc'));
$results[] = p1141_pass('htmlspecialchars UTF-8 in m360_access_h', str_contains($mgmt, "ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'"));
$results[] = p1141_pass('no utf8_encode in access files', !preg_match('/\butf8_encode\s*\(/i', $blob));
$results[] = p1141_pass('no utf8_decode in access files', !preg_match('/\butf8_decode\s*\(/i', $blob));

$pageHeaders = [
    'erp-access-management.php',
    'erp-access-user-create.php',
    'erp-access-user-edit.php',
    'erp-access-role-assign.php',
    'erp-access-permission-preview.php',
];
foreach ($pageHeaders as $page) {
    $content = p1141_read($pub . '/' . $page);
    $results[] = p1141_pass($page . ' Content-Type UTF-8', str_contains($content, "charset=UTF-8"));
}

$results[] = p1141_pass('shared shell meta charset UTF-8', str_contains($mgmt, '<meta charset="UTF-8">'));

$frozen = [
    'staff-login.php',
    'owner-login.php',
    'staff-auth.php',
    'access-control.php',
    'api/auth/staff-login.php',
    'api/auth/owner-login.php',
];
foreach ($frozen as $f) {
    $results[] = p1141_pass($f . ' exists (unchanged path)', is_file($pub . '/' . str_replace('/', DIRECTORY_SEPARATOR, $f)));
}

$results[] = p1141_pass('no core_permissions UPDATE in access helpers', !preg_match('/UPDATE\s+dbo\.core_permissions/i', $blob));
$results[] = p1141_pass('no core_role_permissions UPDATE', !preg_match('/UPDATE\s+dbo\.core_role_permissions/i', $blob));
$results[] = p1141_pass('no DROP/TRUNCATE/DELETE', !preg_match('/\b(DROP|TRUNCATE|DELETE\s+FROM|ALTER\s+TABLE)\b/i', $blob));
$results[] = p1141_pass('no P12 taxonomy', !preg_match('/\bP12\b/', $blob));
$results[] = p1141_pass('user helper uses m360_access_fetch_rows', str_contains(p1141_read($pub . '/includes/m360-access-user-helper.php'), 'm360_access_fetch_rows'));

$pass = 0;
$fail = 0;
echo "# P11.4.1 Persian Access UI Schema + Encoding Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
