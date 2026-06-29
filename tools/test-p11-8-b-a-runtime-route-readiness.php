<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pub = $root . '/public_html';
require_once $pub . '/includes/m360-staff-home-helper.php';

function p118ba_rt_pass(string $n, bool $ok, string $d = ''): array
{
    return ['name' => $n, 'pass' => $ok, 'detail' => $d];
}

$results = [];

$results[] = p118ba_rt_pass('runtime not ready map defined', defined('M360_STAFF_HOME_RUNTIME_NOT_READY'));
$results[] = p118ba_rt_pass('part-use not runtime ready', !m360_staff_home_is_runtime_ready('erp-jobcard-part-use.php'));
$results[] = p118ba_rt_pass('payment tracking not runtime ready', !m360_staff_home_is_runtime_ready('erp-payment-tracking.php'));
$results[] = p118ba_rt_pass('technical board runtime ready', m360_staff_home_is_runtime_ready('erp-technical-board.php'));

foreach (['PARTS', 'TECHNICIAN', 'FINANCE', 'OWNER', 'SERVICE_MANAGER'] as $role) {
    foreach (m360_staff_home_workbench_items($role) as $item) {
        $file = (string)($item['file'] ?? '');
        if ($file === 'erp-jobcard-part-use.php') {
            $results[] = p118ba_rt_pass($role . ' part-use not clickable', !m360_staff_home_item_clickable($item));
            $results[] = p118ba_rt_pass($role . ' part-use runtime_hold', ($item['card_type'] ?? '') === 'runtime_hold');
        }
        if ($file === 'erp-payment-tracking.php') {
            $results[] = p118ba_rt_pass($role . ' payment not clickable', !m360_staff_home_item_clickable($item));
            $results[] = p118ba_rt_pass($role . ' payment runtime_hold', ($item['card_type'] ?? '') === 'runtime_hold');
        }
    }
}

$techNav = null;
foreach (m360_staff_home_workbench_items('SERVICE_MANAGER') as $item) {
    if (($item['file'] ?? '') === 'erp-technical-board.php' && ($item['card_type'] ?? '') === 'nav') {
        $techNav = $item;
        break;
    }
}
$results[] = p118ba_rt_pass('SM technical board still clickable', $techNav !== null && m360_staff_home_item_clickable($techNav));

ob_start();
foreach (m360_staff_home_workbench_items('PARTS') as $item) {
    if (($item['file'] ?? '') === 'erp-jobcard-part-use.php') {
        m360_staff_home_render_workbench_item($item, 1);
        break;
    }
}
$render = (string)ob_get_clean();
$results[] = p118ba_rt_pass('part-use render disabled button', str_contains($render, 'disabled') && !preg_match('/<a class="m360-staff-btn" href="erp-jobcard-part-use/', $render));

$pass = 0;
$fail = 0;
echo "# P11.8-B-A Runtime Route Readiness Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
