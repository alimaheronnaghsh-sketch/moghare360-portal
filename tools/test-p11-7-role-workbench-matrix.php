<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pub = $root . '/public_html';
require_once $pub . '/includes/m360-staff-home-helper.php';

function p117m_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }

function p117m_files(string $role): array
{
    $m = m360_staff_home_role_routes($role);
    $files = [];
    foreach ($m['allowed_routes'] as $r) {
        $files[] = (string)($r['file'] ?? '');
    }
    return $files;
}

function p117m_has_group(string $role, string $groupKey): bool
{
    $m = m360_staff_home_role_routes($role);
    $groups = $m['workbench_groups'] ?? [];
    return isset($groups[$groupKey]) && count($groups[$groupKey]) > 0;
}

$results = [];
$labels = m360_staff_home_workbench_group_labels();
$results[] = p117m_pass('workbench group labels defined', count($labels) === 7);
$results[] = p117m_pass('today group label', ($labels[M360_STAFF_HOME_GROUP_TODAY] ?? '') === 'کار امروز');
$results[] = p117m_pass('role start questions for RECEPTION', trim(m360_staff_home_role_start_questions()['RECEPTION'] ?? '') !== '');

foreach (['OWNER', 'RECEPTION', 'SERVICE_MANAGER', 'TECHNICIAN', 'PARTS', 'FINANCE', 'QC'] as $role) {
    $m = m360_staff_home_role_routes($role);
    $results[] = p117m_pass($role . ' has workbench_groups', is_array($m['workbench_groups'] ?? null) && ($m['workbench_groups'] ?? []) !== []);
    $results[] = p117m_pass($role . ' has today group', p117m_has_group($role, M360_STAFF_HOME_GROUP_TODAY));
    $results[] = p117m_pass($role . ' has role_start_question', trim((string)($m['role_start_question'] ?? '')) !== '');
}

$reception = p117m_files('RECEPTION');
$results[] = p117m_pass('RECEPTION includes intake contracts', in_array('erp-intake-contracts.php', $reception, true));
$results[] = p117m_pass('RECEPTION includes online requests', in_array('erp-reception-online-requests.php', $reception, true));

$finance = p117m_files('FINANCE');
$results[] = p117m_pass('FINANCE includes estimate board', in_array('erp-estimate-board.php', $finance, true));
$results[] = p117m_pass('FINANCE excludes missing finance-center from allowed', !in_array('erp-finance-center.php', $finance, true));

$parts = p117m_files('PARTS');
$results[] = p117m_pass('PARTS includes part-use', in_array('erp-jobcard-part-use.php', $parts, true));
$results[] = p117m_pass('PARTS excludes missing usage-list from allowed', !in_array('erp-jobcard-part-usage-list.php', $parts, true));

$techItems = m360_staff_home_workbench_items('TECHNICIAN');
$hasFilterBacklog = false;
foreach ($techItems as $item) {
    if (($item['card_type'] ?? '') === 'backlog' && str_contains((string)($item['label_fa'] ?? ''), 'فیلتر')) {
        $hasFilterBacklog = true;
    }
}
$results[] = p117m_pass('TECHNICIAN has assignment filter backlog card', $hasFilterBacklog);

$pass = 0; $fail = 0;
echo "# P11.7 Role Workbench Matrix Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
