<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pub = $root . '/public_html';
require_once $pub . '/includes/m360-staff-home-helper.php';

function p117b_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }

function p117b_clickable_files(string $role): array
{
    $files = [];
    foreach (m360_staff_home_workbench_items($role) as $item) {
        if (m360_staff_home_item_clickable($item)) {
            $files[] = (string)($item['file'] ?? '');
        }
    }
    return $files;
}

$results = [];

$partsClick = p117b_clickable_files('PARTS');
$results[] = p117b_pass('PARTS links part-use not missing usage-list', in_array('erp-jobcard-part-use.php', $partsClick, true));
$results[] = p117b_pass('PARTS does not clickable link usage-list', !in_array('erp-jobcard-part-usage-list.php', $partsClick, true));

$financeClick = p117b_clickable_files('FINANCE');
$results[] = p117b_pass('FINANCE does not clickable link finance-center', !in_array('erp-finance-center.php', $financeClick, true));
$results[] = p117b_pass('FINANCE links payment tracking', in_array('erp-payment-tracking.php', $financeClick, true));
$results[] = p117b_pass('FINANCE links final invoice board', in_array('erp-final-invoice-board.php', $financeClick, true));

$partsItems = m360_staff_home_workbench_items('PARTS');
$legacyBacklog = false;
$financeBacklog = false;
foreach ($partsItems as $item) {
    if (($item['file'] ?? '') === 'erp-jobcard-part-usage-list.php' && ($item['card_type'] ?? '') === 'backlog') {
        $legacyBacklog = true;
    }
}
foreach (m360_staff_home_workbench_items('FINANCE') as $item) {
    if (($item['file'] ?? '') === 'erp-finance-center.php' && ($item['card_type'] ?? '') === 'backlog') {
        $financeBacklog = true;
    }
}
$results[] = p117b_pass('legacy usage-list is backlog card', $legacyBacklog);
$results[] = p117b_pass('finance-center is backlog card', $financeBacklog);

$results[] = p117b_pass('part-use file exists on disk', m360_staff_home_route_exists('erp-jobcard-part-use.php'));
$results[] = p117b_pass('finance-center missing on disk', !m360_staff_home_route_exists('erp-finance-center.php'));

$pass = 0; $fail = 0;
echo "# P11.7 Broken Link Fixes Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
