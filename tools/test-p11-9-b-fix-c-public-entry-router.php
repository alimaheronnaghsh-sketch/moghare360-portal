<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$indexPath = $root . '/public_html/index.php';
$indexHtmlPath = $root . '/public_html/index.html';

function p119bfixc_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$results = [];
$index = is_file($indexPath) ? (string)file_get_contents($indexPath) : '';
$indexLower = mb_strtolower($index);

$results[] = p119bfixc_pass('public_html/index.php exists', is_file($indexPath));

$requiredNav = ['خانه', 'پرسنل', 'مشتری'];
foreach ($requiredNav as $label) {
    $results[] = p119bfixc_pass('contains nav: ' . $label, str_contains($index, $label));
}

$requiredPhrases = [
    'مقاره ۳۶۰',
    'شرکت فنی مهندسی ماهین صنعت ماهران',
    'مجموعه محترم مقاره موتورز',
    'ارتباط مستمر و شفاف با مشتری',
];
foreach ($requiredPhrases as $phrase) {
    $results[] = p119bfixc_pass('contains: ' . $phrase, str_contains($index, $phrase));
}
$results[] = p119bfixc_pass(
    'contains phone',
    str_contains($index, '09131173340') || str_contains($index, '۰۹۱۳۱۱۷۳۳۴۰')
);

$results[] = p119bfixc_pass('links staff-login.php', str_contains($indexLower, 'staff-login.php'));
$results[] = p119bfixc_pass(
    'customer route or placeholder',
    str_contains($index, 'customer-request.php')
        || str_contains($index, 'درگاه مشتری در حال آماده‌سازی است')
);

$forbidden = [
    'Master Console',
    'Unit Access Console',
    'Product Home',
    'Production Signoff',
    'Fix Register',
    'Route Map',
    'Release Readiness',
    'Demo Package',
    'Soft Run Home',
    'Moghare Ready',
    'Owner Login',
    'ورود مدیریتی',
    'Access Management',
    'READY',
    'CHECK',
    'BLOCKED',
    'SQL Server SaaS',
    'Legacy MySQL portal inactive',
    'moghare360-v1-master-console-helper',
];
foreach ($forbidden as $label) {
    $results[] = p119bfixc_pass('does not contain: ' . $label, !str_contains($index, $label));
}

$internalUrls = [
    'erp-v1-master-console.php',
    'erp-v1-unit-access-console.php',
    'owner-login.php',
    'erp-access-management.php',
];
foreach ($internalUrls as $url) {
    $results[] = p119bfixc_pass('no link to: ' . $url, !str_contains($indexLower, mb_strtolower($url)));
}

if (is_file($indexHtmlPath)) {
    $html = (string)file_get_contents($indexHtmlPath);
    $results[] = p119bfixc_pass(
        'index.html has no Master ERP Entry',
        !str_contains($html, 'Master Console') && !str_contains($html, 'v1mc')
    );
} else {
    $results[] = p119bfixc_pass('index.html absent (no override)', true);
}

$results[] = p119bfixc_pass('cache-control headers in index.php', str_contains($index, 'Cache-Control'));
$results[] = p119bfixc_pass('RTL', str_contains($index, 'dir="rtl"'));

$pass = 0;
$fail = 0;
echo "# P11.9-B-FIX-C Public Entry Router Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
