<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/public_html/includes/m360-reception-workbench-helper.php';
$files = [
    'workbench' => $root . '/public_html/erp-reception-workbench.php',
    'intake' => $root . '/public_html/erp-reception-intake-file.php',
    'list' => $root . '/public_html/erp-reception-online-requests.php',
    'detail' => $root . '/public_html/erp-reception-online-request-detail.php',
];
$wb = (string)file_get_contents($files['workbench']);
$intake = (string)file_get_contents($files['intake']);

function rf_ux_pass(string $name, bool $ok): array
{
    return ['name' => $name, 'pass' => $ok];
}

$results = [];
foreach ($files as $label => $path) {
    $c = (string)file_get_contents($path);
    $results[] = rf_ux_pass("{$label}: luxury css", str_contains($c, 'moghare360-v1-luxury-ui.css'));
    $results[] = rf_ux_pass("{$label}: Persian RTL", str_contains($c, 'lang="fa"') && str_contains($c, 'dir="rtl"'));
    $results[] = rf_ux_pass("{$label}: m360-rw-page shell", str_contains($c, 'm360-rw-page'));
}
$results[] = rf_ux_pass('intake: 5 section structure', str_contains($intake, 'sec-gate') && str_contains($intake, 'sec-service-class') && str_contains($intake, 'sec-next'));
$results[] = rf_ux_pass('walk-in: operational placeholder', str_contains($wb, 'M360_RW_WALKIN_PLACEHOLDER_FA') || str_contains($wb, 'فرم عملیاتی پذیرش حضوری'));
$results[] = rf_ux_pass('walk-in: mock in details not primary', str_contains($wb, 'm360-rw-mock-guides') && (str_contains($wb, 'M360_RW_MOCK_UX_LABEL_FA') || str_contains($wb, 'راهنمای نمایشی')));
$results[] = rf_ux_pass('walk-in: no primary mock jobcard link', !preg_match('/class="m360-rw-btn"[^>]*href="erp-jobcard-create-ux/', $wb));
$results[] = rf_ux_pass('list: no legacy soft-run css', !str_contains((string)file_get_contents($files['list']), 'moghare360-soft-run-release.css'));
$results[] = rf_ux_pass('detail: no legacy soft-run css', !str_contains((string)file_get_contents($files['detail']), 'moghare360-soft-run-release.css'));

$pass = 0;
$fail = 0;
echo "# P11.9-C-2B-REWORK-FINAL UX Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
