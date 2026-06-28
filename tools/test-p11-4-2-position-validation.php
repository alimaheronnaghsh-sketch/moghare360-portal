<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pub = $root . '/public_html';

function p1142v_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p1142v_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$helper = p1142v_read($pub . '/includes/m360-access-user-helper.php');
$create = p1142v_read($pub . '/erp-access-user-create.php');
$edit = p1142v_read($pub . '/erp-access-user-edit.php');

$results = [];

$results[] = p1142v_pass('mismatch constant defined', str_contains($helper, "M360_ACCESS_POSITION_DEPT_MISMATCH_FA"));
$results[] = p1142v_pass('Persian mismatch message', str_contains($helper, 'سمت انتخاب‌شده با واحد انتخاب‌شده همخوانی ندارد'));
$results[] = p1142v_pass('require_department_position_pair exists', str_contains($helper, 'm360_access_user_require_department_position_pair'));
$results[] = p1142v_pass('validates department active', str_contains($helper, 'm360_access_user_validate_department'));
$results[] = p1142v_pass('validates position with department_id', str_contains($helper, 'm360_access_user_validate_position($conn, $positionId, $departmentId)'));
$results[] = p1142v_pass('position query uses department_id', preg_match('/core_positions.*department_id/s', $helper) === 1);

$results[] = p1142v_pass('create calls require pair', preg_match('/function m360_access_user_create[\s\S]*?m360_access_user_require_department_position_pair/', $helper) === 1);
$results[] = p1142v_pass('update calls require pair', preg_match('/function m360_access_user_update[\s\S]*?m360_access_user_require_department_position_pair/', $helper) === 1);

$results[] = p1142v_pass('create page passes department_id on POST', str_contains($create, "'department_id' => \$selectedDepartmentId"));
$results[] = p1142v_pass('edit page passes department_id on POST', str_contains($edit, "'department_id' => \$selectedDepartmentId"));
$results[] = p1142v_pass('create repopulates on error', str_contains($create, '$selectedDepartmentId = (int)m360_access_mgmt_post_string'));
$results[] = p1142v_pass('edit repopulates on error', str_contains($edit, '$selectedDepartmentId = (int)m360_access_mgmt_post_string'));

$pass = 0; $fail = 0;
echo "# P11.4.2 Position Validation Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
