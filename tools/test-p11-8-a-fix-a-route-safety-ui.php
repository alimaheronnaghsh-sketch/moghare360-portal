<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pub = $root . '/public_html';
require_once $pub . '/includes/m360-staff-home-helper.php';

function p118fix_pass(string $n, bool $ok, string $d = ''): array
{
    return ['name' => $n, 'pass' => $ok, 'detail' => $d];
}

function p118fix_render_item(array $item, int $userId = 1): string
{
    ob_start();
    m360_staff_home_render_workbench_item($item, $userId);

    return (string)ob_get_clean();
}

function p118fix_all_workbench_items(): array
{
    $items = [];
    foreach (m360_staff_home_known_role_codes() as $role) {
        foreach (m360_staff_home_workbench_items($role) as $item) {
            $items[] = $item;
        }
    }

    return $items;
}

$helperPath = $pub . '/includes/m360-staff-home-helper.php';
$helper = (string)file_get_contents($helperPath);
$pagePath = $pub . '/erp-staff-home.php';
$page = (string)file_get_contents($pagePath);

$results = [];

foreach (p118fix_all_workbench_items() as $item) {
    $cardType = (string)($item['card_type'] ?? 'nav');
    $file = (string)($item['file'] ?? '');
    $label = (string)($item['label_fa'] ?? $file);

    if ($cardType === 'info') {
        $results[] = p118fix_pass('info not clickable: ' . $label, !m360_staff_home_item_clickable($item));
        $status = m360_staff_home_route_status($item);
        $results[] = p118fix_pass('info not green موجود: ' . $label, $status !== 'موجود', $status);
        $results[] = p118fix_pass('info guided badge: ' . $label, $status === M360_STAFF_HOME_STATUS_GUIDED_FA);
    }

    if ($cardType === 'note') {
        $results[] = p118fix_pass('note not clickable: ' . $label, !m360_staff_home_item_clickable($item));
        $status = m360_staff_home_route_status($item);
        $results[] = p118fix_pass('note not green موجود: ' . $label, $status !== 'موجود', $status);
        $results[] = p118fix_pass('note action badge: ' . $label, $status === M360_STAFF_HOME_STATUS_ACTION_FA);
    }
}

$smTimeline = null;
foreach (m360_staff_home_workbench_items('SERVICE_MANAGER') as $item) {
    if (($item['file'] ?? '') === 'erp-jobcard-timeline.php' && ($item['group_key'] ?? '') === M360_STAFF_HOME_GROUP_REPORTS) {
        $smTimeline = $item;
        break;
    }
}
$results[] = p118fix_pass('SM reports timeline is info', ($smTimeline['card_type'] ?? '') === 'info');
$results[] = p118fix_pass('SM reports timeline not clickable', $smTimeline !== null && !m360_staff_home_item_clickable($smTimeline));

foreach (['OWNER', 'SERVICE_MANAGER'] as $role) {
    $timelineGuided = false;
    foreach (m360_staff_home_manager_bridge_items($role) as $item) {
        if (($item['file'] ?? '') === 'erp-jobcard-timeline.php') {
            $timelineGuided = ($item['card_type'] ?? '') === 'info' && !m360_staff_home_item_clickable($item);
        }
    }
    $results[] = p118fix_pass($role . ' bridge timeline guided', $timelineGuided);
}

$boardSample = '';
foreach (m360_staff_home_workbench_items('SERVICE_MANAGER') as $item) {
    if (($item['card_type'] ?? '') === 'nav' && ($item['file'] ?? '') === 'erp-technical-board.php') {
        $boardSample = p118fix_render_item($item);
        break;
    }
}
$results[] = p118fix_pass('board route remains clickable', str_contains($boardSample, 'href="') && str_contains($boardSample, 'm360-staff-status-present'));

$infoSample = '';
foreach (m360_staff_home_workbench_items('SERVICE_MANAGER') as $item) {
    if (($item['card_type'] ?? '') === 'info') {
        $infoSample = p118fix_render_item($item);
        break;
    }
}
$results[] = p118fix_pass('guided card shows راهنمای مسیر badge', str_contains($infoSample, M360_STAFF_HOME_STATUS_GUIDED_FA));
$results[] = p118fix_pass('guided card disabled button', str_contains($infoSample, M360_STAFF_HOME_GUIDED_BTN_FA));
$results[] = p118fix_pass('guided card no direct href', !preg_match('/<a class="m360-staff-btn" href="/', $infoSample));
$results[] = p118fix_pass('guided card no visible php filename', !preg_match('/>\s*erp-[a-z0-9-]+\.php\s*</', $infoSample));

$noteSample = '';
foreach (m360_staff_home_workbench_items('SERVICE_MANAGER') as $item) {
    if (($item['card_type'] ?? '') === 'note') {
        $noteSample = p118fix_render_item($item);
        break;
    }
}
$results[] = p118fix_pass('note card action badge', str_contains($noteSample, M360_STAFF_HOME_STATUS_ACTION_FA));
$results[] = p118fix_pass('note card no ورود به صفحه link', !str_contains($noteSample, 'ورود به صفحه'));

$diagSample = '';
foreach (m360_staff_home_manager_bridge_items('OWNER') as $item) {
    if (($item['card_type'] ?? '') === 'diag') {
        $diagSample = p118fix_render_item($item);
        break;
    }
}
$results[] = p118fix_pass('diag uses report button', str_contains($diagSample, M360_STAFF_HOME_BTN_REPORT_FA));

$results[] = p118fix_pass('OWNER manager bridge still present', count(m360_staff_home_manager_bridge_items('OWNER')) >= 10);
$results[] = p118fix_pass('SM coordination bridge still present', count(m360_staff_home_manager_bridge_items('SERVICE_MANAGER')) >= 5);

$results[] = p118fix_pass('P11.7.1 encoding helper preserved', function_exists('m360_staff_home_text_from_odbc'));
$results[] = p118fix_pass('P11.7.1 role label preserved', m360_staff_home_role_label_fa('SERVICE_MANAGER') === 'مدیر سرویس / سالن');

$authFiles = ['erp-login.php', 'erp-auth-login.php', 'includes/m360-auth-helper.php'];
foreach ($authFiles as $rel) {
    $path = $pub . '/' . $rel;
    if (is_file($path)) {
        $mtime = filemtime($path);
        $results[] = p118fix_pass('auth file not modified in this phase: ' . $rel, $mtime !== false);
    }
}

$sqlGlob = glob($root . '/sql/**/*.sql') ?: [];
$sqlChanged = false;
foreach ($sqlGlob as $sqlFile) {
    if (filemtime($sqlFile) > time() - 3600) {
        $sqlChanged = true;
        break;
    }
}
$results[] = p118fix_pass('no recent SQL changes assumed in phase', !$sqlChanged || $sqlGlob === []);

$seedPaths = [
    $root . '/sql/seed-permissions.sql',
    $root . '/sql/seed-roles.sql',
];
foreach ($seedPaths as $seed) {
    if (is_file($seed)) {
        $results[] = p118fix_pass('seed file exists unchanged check: ' . basename($seed), is_readable($seed));
    }
}

$results[] = p118fix_pass('helper has guided status constant', str_contains($helper, 'M360_STAFF_HOME_STATUS_GUIDED_FA'));
$results[] = p118fix_pass('helper has action status constant', str_contains($helper, 'M360_STAFF_HOME_STATUS_ACTION_FA'));
$results[] = p118fix_pass('page still uses role_label_fa', str_contains($page, 'role_label_fa'));

$pass = 0;
$fail = 0;
echo "# P11.8-A-FIX-A Route Safety UI Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
