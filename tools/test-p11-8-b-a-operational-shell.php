<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pub = $root . '/public_html';
require_once $pub . '/includes/m360-operational-shell-helper.php';

function p118ba_pass(string $n, bool $ok, string $d = ''): array
{
    return ['name' => $n, 'pass' => $ok, 'detail' => $d];
}

$results = [];
$helperPath = $pub . '/includes/m360-operational-shell-helper.php';
$cssPath = $pub . '/assets/css/m360-operational-shell.css';

$results[] = p118ba_pass('operational shell helper exists', is_file($helperPath));
$results[] = p118ba_pass('operational shell css exists', is_file($cssPath));
$results[] = p118ba_pass('render top nav function', function_exists('m360_operational_shell_render_top_nav'));
$results[] = p118ba_pass('render breadcrumb function', function_exists('m360_operational_shell_render_breadcrumb'));
$results[] = p118ba_pass('render responsibility strip function', function_exists('m360_operational_shell_render_responsibility_strip'));
$results[] = p118ba_pass('resolve user name function', function_exists('m360_operational_shell_resolve_user_name'));
$results[] = p118ba_pass('status label function', function_exists('m360_operational_shell_status_label'));
$results[] = p118ba_pass('next action label function', function_exists('m360_operational_shell_next_action_label'));

ob_start();
m360_operational_shell_render_top_nav(m360_operational_shell_board_context('technical_board'));
$nav = (string)ob_get_clean();
$results[] = p118ba_pass('nav has بازگشت', str_contains($nav, 'بازگشت'));
$results[] = p118ba_pass('nav has میز کار من', str_contains($nav, 'میز کار من'));
$results[] = p118ba_pass('nav has صفحه اصلی محصول', str_contains($nav, 'صفحه اصلی محصول'));
$results[] = p118ba_pass('nav links to staff home', str_contains($nav, M360_OPS_SHELL_STAFF_HOME));
$results[] = p118ba_pass('nav no localhost', !str_contains($nav, 'localhost'));

$boardPages = [
    'erp-reception-jobcards.php',
    'erp-intake-contracts.php',
    'erp-technical-board.php',
    'erp-work-execution-board.php',
    'erp-qc-board.php',
    'erp-estimate-board.php',
    'erp-final-invoice-board.php',
];
foreach ($boardPages as $page) {
    $src = (string)file_get_contents($pub . '/' . $page);
    $results[] = p118ba_pass('board includes shell: ' . $page, str_contains($src, 'm360-operational-shell-helper.php') && str_contains($src, 'm360_operational_shell_render_board'));
}

$detailPages = [
    'erp-reception-jobcard-detail.php',
    'erp-technical-jobcard-detail.php',
    'erp-work-execution-detail.php',
    'erp-estimate-detail.php',
    'erp-final-invoice-detail.php',
    'erp-qc-detail.php',
    'erp-settlement-detail.php',
];
foreach ($detailPages as $page) {
    $src = (string)file_get_contents($pub . '/' . $page);
    $results[] = p118ba_pass('detail includes shell: ' . $page, str_contains($src, 'm360_operational_shell_render_detail'));
}

$pass = 0;
$fail = 0;
echo "# P11.8-B-A Operational Shell Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
