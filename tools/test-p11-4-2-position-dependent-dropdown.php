<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pub = $root . '/public_html';

function p1142d_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p1142d_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$create = p1142d_read($pub . '/erp-access-user-create.php');
$edit = p1142d_read($pub . '/erp-access-user-edit.php');
$helper = p1142d_read($pub . '/includes/m360-access-user-helper.php');
$js = p1142d_read($pub . '/assets/js/m360-access-position-filter.js');

$results = [];

$results[] = p1142d_pass('position filter JS exists', is_file($pub . '/assets/js/m360-access-position-filter.js'));
$results[] = p1142d_pass('create does not load flat m360_access_user_positions($conn)', !preg_match('/m360_access_user_positions\s*\(\s*\$conn\s*\)/', $create));
$results[] = p1142d_pass('create uses render_department_position_fields', str_contains($create, 'm360_access_user_render_department_position_fields'));
$results[] = p1142d_pass('create uses positions JSON helper', str_contains($create, 'm360_access_user_positions_json_for_form'));
$results[] = p1142d_pass('create hides executive on routine form', str_contains($create, 'm360_access_user_departments_for_staff_form($conn, true)'));

$results[] = p1142d_pass('edit uses render_department_position_fields', str_contains($edit, 'm360_access_user_render_department_position_fields'));
$results[] = p1142d_pass('edit uses positions JSON helper', str_contains($edit, 'm360_access_user_positions_json_for_form'));
$results[] = p1142d_pass('edit shows all departments', str_contains($edit, 'm360_access_user_departments_for_staff_form($conn, false)'));

$results[] = p1142d_pass('helper embeds JSON script id', str_contains($helper, 'm360-access-positions-by-dept'));
$results[] = p1142d_pass('helper disables position until dept', str_contains($helper, 'disabled') && str_contains($helper, 'position_id'));
$results[] = p1142d_pass('helper Persian help text', str_contains($helper, 'ابتدا واحد را انتخاب کنید'));
$results[] = p1142d_pass('helper groups by department map', str_contains($helper, 'm360_access_user_positions_by_department_map'));
$results[] = p1142d_pass('JSON UTF-8 safe encode', str_contains($helper, 'JSON_UNESCAPED_UNICODE'));

$results[] = p1142d_pass('JS listens to department_id change', str_contains($js, 'department_id') && str_contains($js, 'change'));
$results[] = p1142d_pass('JS reads embedded map', str_contains($js, 'm360-access-positions-by-dept'));
$results[] = p1142d_pass('JS disables position when no dept', str_contains($js, 'posEl.disabled = true'));

$results[] = p1142d_pass('uses dept_name not department_name', str_contains($helper, 'dept_name') && !preg_match('/\bdepartment_name\b/', $helper));
$results[] = p1142d_pass('uses position_name not title', str_contains($helper, 'position_name') && !preg_match('/\bposition_title\b/', $helper));
$results[] = p1142d_pass('uses m360_access_mgmt_h for output', str_contains($helper, 'm360_access_mgmt_h('));

$pass = 0; $fail = 0;
echo "# P11.4.2 Position Dependent Dropdown Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
