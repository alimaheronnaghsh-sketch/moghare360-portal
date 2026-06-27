<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pub = $root . '/public_html';

function p114_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p114_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$pages = [
    'erp-access-management.php',
    'erp-access-user-create.php',
    'erp-access-user-edit.php',
    'erp-access-role-assign.php',
    'erp-access-password-reset.php',
    'erp-access-permission-preview.php',
    'erp-access-change-history.php',
];

$results = [];
foreach ($pages as $p) {
    $results[] = p114_pass($p . ' exists', is_file($pub . '/' . $p));
}

$mgmt = p114_read($pub . '/includes/m360-access-management-helper.php');
$results[] = p114_pass('management helper exists', is_file($pub . '/includes/m360-access-management-helper.php'));
$results[] = p114_pass('owner/admin gate function', str_contains($mgmt, 'm360_access_mgmt_require_admin'));
$results[] = p114_pass('CSRF constant', str_contains($mgmt, 'M360_ACCESS_MGMT_CSRF'));

$postPages = ['erp-access-user-create.php', 'erp-access-user-edit.php', 'erp-access-role-assign.php', 'erp-access-password-reset.php'];
foreach ($postPages as $p) {
    $c = p114_read($pub . '/' . $p);
    $results[] = p114_pass($p . ' CSRF on POST', str_contains($c, 'erp_csrf_input') && str_contains($c, 'm360_access_mgmt_require_post_csrf'));
}

$all = implode("\n", array_map(static fn(string $p): string => p114_read($pub . '/' . $p), $pages));
$results[] = p114_pass('no raw SQL editor UI', !preg_match('/raw\s*sql|sql\s*editor/i', $all));
$results[] = p114_pass('main page lists staff', str_contains(p114_read($pub . '/erp-access-management.php'), 'm360_access_user_list_staff'));

$pass = 0; $fail = 0;
echo "# P11.4 Access Management UI Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
