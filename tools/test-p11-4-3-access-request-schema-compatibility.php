<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pub = $root . '/public_html';

function p1143a_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p1143a_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$audit = p1143a_read($pub . '/includes/m360-access-audit-helper.php');
$role = p1143a_read($pub . '/includes/m360-access-role-helper.php');
$blob = $audit . $role;

$results = [];
$results[] = p1143a_pass('uses subject_user_id in access request insert', str_contains($audit, 'subject_user_id'));
$results[] = p1143a_pass('uses requested_by_user_id in access request insert', str_contains($audit, 'requested_by_user_id'));
$results[] = p1143a_pass('uses applied_by_user_id in access request insert', str_contains($audit, 'applied_by_user_id'));
$results[] = p1143a_pass('no requested_for_user_id in role grant/audit files', !str_contains($blob, 'requested_for_user_id'));
$results[] = p1143a_pass('no created_by_user_id in core_access_requests insert', !preg_match('/INSERT\s+INTO\s+dbo\.core_access_requests[\s\S]*created_by_user_id/i', $audit));
$results[] = p1143a_pass('history uses user_id column', preg_match('/INSERT\s+INTO\s+dbo\.core_access_change_history[\s\S]*\buser_id\b/i', $audit) === 1);
$results[] = p1143a_pass('no target_user_id in role grant/audit files', !str_contains($blob, 'target_user_id'));
$results[] = p1143a_pass('history uses changed_by_user_id', str_contains($audit, 'changed_by_user_id'));
$results[] = p1143a_pass('core_user_roles insert uses granted_by_request_id', str_contains($role, 'granted_by_request_id'));
$results[] = p1143a_pass('fallback fetch request_id by request_number', str_contains($audit, 'm360_access_audit_fetch_request_id'));
$results[] = p1143a_pass('resolve insert id helper exists', str_contains($audit, 'm360_access_audit_resolve_insert_id'));
$results[] = p1143a_pass('migration_source ACCESS_MGMT_UI', str_contains($audit, 'M360_ACCESS_MGMT_MIGRATION_SOURCE'));
$results[] = p1143a_pass('request_number ARM prefix', str_contains($audit, "'ARM-'"));

$pass = 0; $fail = 0;
echo "# P11.4.3 Access Request Schema Compatibility Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
