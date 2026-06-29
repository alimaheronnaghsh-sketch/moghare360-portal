<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pub = $root . '/public_html';
require_once $pub . '/includes/m360-staff-home-helper.php';

function p118a_pass(string $n, bool $ok, string $d = ''): array
{
    return ['name' => $n, 'pass' => $ok, 'detail' => $d];
}

function p118a_group_items(string $role, string $groupKey): array
{
    $m = m360_staff_home_role_routes($role);
    return $m['workbench_groups'][$groupKey] ?? [];
}

function p118a_bridge_files(string $role): array
{
    $files = [];
    foreach (m360_staff_home_manager_bridge_items($role) as $item) {
        $files[] = (string)($item['file'] ?? '');
    }

    return $files;
}

$results = [];
$labels = m360_staff_home_workbench_group_labels();

$results[] = p118a_pass('manager ref group label defined', ($labels[M360_STAFF_HOME_GROUP_MANAGER_REF] ?? '') === 'مرجع عملیاتی One-Day Run');
$results[] = p118a_pass('coordination group label defined', ($labels[M360_STAFF_HOME_GROUP_COORDINATION_REF] ?? '') === 'مرجع هماهنگی سالن');
$results[] = p118a_pass('OWNER has manager bridge', m360_staff_home_has_manager_bridge('OWNER'));
$results[] = p118a_pass('SYSTEM_ADMIN has manager bridge', m360_staff_home_has_manager_bridge('SYSTEM_ADMIN'));
$results[] = p118a_pass('OWNER manager_ref group items', count(p118a_group_items('OWNER', M360_STAFF_HOME_GROUP_MANAGER_REF)) >= 10);
$results[] = p118a_pass('SERVICE_MANAGER coordination group', count(p118a_group_items('SERVICE_MANAGER', M360_STAFF_HOME_GROUP_COORDINATION_REF)) >= 5);
$results[] = p118a_pass('RECEPTION no manager bridge', !m360_staff_home_has_manager_bridge('RECEPTION'));
$results[] = p118a_pass('TECHNICIAN no manager bridge', !m360_staff_home_has_manager_bridge('TECHNICIAN'));

$ownerBridge = p118a_group_items('OWNER', M360_STAFF_HOME_GROUP_MANAGER_REF);
$results[] = p118a_pass('OWNER bridge includes online requests', p118a_has_label($ownerBridge, 'درخواست‌های آنلاین'));
$results[] = p118a_pass('OWNER bridge includes permission preview diag', p118a_has_file_type($ownerBridge, 'erp-access-permission-preview.php', 'diag'));

$smCoord = p118a_group_items('SERVICE_MANAGER', M360_STAFF_HOME_GROUP_COORDINATION_REF);
$results[] = p118a_pass('SM coordination excludes access management', !p118a_has_file($smCoord, 'erp-access-management.php'));
$results[] = p118a_pass('SM coordination excludes permission preview', !p118a_has_file($smCoord, 'erp-access-permission-preview.php'));

$ownerBacklog = p118a_group_items('OWNER', M360_STAFF_HOME_GROUP_BACKLOG);
$results[] = p118a_pass('OWNER impersonation backlog', p118a_backlog_has($ownerBacklog, 'Impersonation'));
$results[] = p118a_pass('OWNER override engine backlog', p118a_backlog_has($ownerBacklog, 'Override'));

function p118a_has_label(array $items, string $needle): bool
{
    foreach ($items as $item) {
        if (str_contains((string)($item['label_fa'] ?? ''), $needle)) {
            return true;
        }
    }

    return false;
}

function p118a_has_file(array $items, string $file): bool
{
    foreach ($items as $item) {
        if (($item['file'] ?? '') === $file) {
            return true;
        }
    }

    return false;
}

function p118a_has_file_type(array $items, string $file, string $type): bool
{
    foreach ($items as $item) {
        if (($item['file'] ?? '') === $file && ($item['card_type'] ?? '') === $type) {
            return true;
        }
    }

    return false;
}

function p118a_backlog_has(array $items, string $needle): bool
{
    foreach ($items as $item) {
        if (($item['card_type'] ?? '') === 'backlog' && str_contains((string)($item['label_fa'] ?? ''), $needle)) {
            return true;
        }
    }

    return false;
}

$pass = 0;
$fail = 0;
echo "# P11.8-A Manager Reference Bridge Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
