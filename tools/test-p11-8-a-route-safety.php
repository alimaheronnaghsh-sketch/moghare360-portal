<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pub = $root . '/public_html';
require_once $pub . '/includes/m360-staff-home-helper.php';

function p118r_pass(string $n, bool $ok, string $d = ''): array
{
    return ['name' => $n, 'pass' => $ok, 'detail' => $d];
}

function p118r_bridge_clickable_files(string $role): array
{
    $files = [];
    foreach (m360_staff_home_manager_bridge_items($role) as $item) {
        if (m360_staff_home_item_clickable($item)) {
            $files[] = (string)($item['file'] ?? '');
        }
    }

    return $files;
}

function p118r_bridge_all_items(string $role): array
{
    return m360_staff_home_manager_bridge_items($role);
}

$results = [];

foreach (['OWNER', 'SYSTEM_ADMIN', 'SERVICE_MANAGER'] as $role) {
    foreach (p118r_bridge_clickable_files($role) as $file) {
        $results[] = p118r_pass($role . ' clickable not action: ' . $file, !m360_staff_home_is_action_endpoint($file));
        $results[] = p118r_pass($role . ' clickable not detail-only pattern: ' . $file, !str_ends_with($file, '-detail.php'));
    }
}

foreach (['OWNER', 'SERVICE_MANAGER'] as $role) {
    $timelineInfo = false;
    $settlementInfo = false;
    foreach (p118r_bridge_all_items($role) as $item) {
        if (($item['file'] ?? '') === 'erp-jobcard-timeline.php') {
            $timelineInfo = ($item['card_type'] ?? '') === 'info' && !m360_staff_home_item_clickable($item);
        }
        if (($item['file'] ?? '') === 'erp-settlement-detail.php') {
            $settlementInfo = ($item['card_type'] ?? '') === 'info' && !m360_staff_home_item_clickable($item);
        }
    }
    if ($role === 'OWNER') {
        $results[] = p118r_pass('OWNER timeline is guided info', $timelineInfo);
        $results[] = p118r_pass('OWNER settlement detail is guided info', $settlementInfo);
    } else {
        $results[] = p118r_pass('SM timeline is guided info', $timelineInfo);
    }
}

$render = '';
ob_start();
foreach (m360_staff_home_manager_bridge_items('OWNER') as $item) {
    if (($item['card_type'] ?? '') === 'ref') {
        m360_staff_home_render_workbench_item($item, 1);
        $render = (string)ob_get_clean();
        break;
    }
}
if ($render === '') {
    ob_end_clean();
}
$results[] = p118r_pass('render no visible erp- filename text', $render === '' || !preg_match('/>\s*erp-[a-z0-9-]+\.php\s*</', $render));
$results[] = p118r_pass('render uses board button label', $render === '' || str_contains($render, M360_STAFF_HOME_BTN_BOARD_FA));

$pass = 0;
$fail = 0;
echo "# P11.8-A Route Safety Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
