<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$indexPath = $root . '/public_html/index.php';

function p119bfixb_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$results = [];
$index = is_file($indexPath) ? (string)file_get_contents($indexPath) : '';
$indexLower = mb_strtolower($index);

$results[] = p119bfixb_pass('public_html/index.php exists', is_file($indexPath));

$requiredPhrases = [
    'مقاره ۳۶۰',
    'شرکت فنی مهندسی ماهین صنعت ماهران',
    'مجموعه محترم مقاره موتورز',
    '09131173340',
    'ارتباط مستمر و شفاف با مشتری',
];
foreach ($requiredPhrases as $phrase) {
    $found = str_contains($index, $phrase)
        || ($phrase === '09131173340' && str_contains($index, '۰۹۱۳۱۱۷۳۳۴۰'));
    $results[] = p119bfixb_pass('contains required: ' . $phrase, $found);
}

$forbiddenVisible = [
    'Product Home',
    'Master Console',
    'Unit Access Console',
    'Production Signoff',
    'Fix Register',
    'Route Map',
    'Release Readiness',
    'Demo Package',
    'Soft Run Home',
    'Owner Login',
    'ورود مدیریتی',
    'READY',
    'CHECK',
    'BLOCKED',
];
foreach ($forbiddenVisible as $label) {
    $results[] = p119bfixb_pass('does not contain: ' . $label, !str_contains($index, $label));
}

$internalUrls = [
    'erp-v1-master-console.php',
    'erp-v1-unit-access-console.php',
    'erp-product-home.php',
    'erp-v1-production-signoff.php',
    'erp-v1-fix-register.php',
    'erp-access-management.php',
    'owner-login.php',
    'erp-route-map.php',
    'erp-soft-run-home.php',
    'erp-moghare-ready.php',
    'staff-login.php',
];
foreach ($internalUrls as $url) {
    $results[] = p119bfixb_pass('no visible link to: ' . $url, !str_contains($indexLower, mb_strtolower($url)));
}

$results[] = p119bfixb_pass('RTL dir=rtl', str_contains($index, 'dir="rtl"'));
$results[] = p119bfixb_pass('Persian lang=fa', str_contains($index, 'lang="fa"'));
$results[] = p119bfixb_pass('MOGHARE360 brand allowed', str_contains($index, 'MOGHARE360'));
$results[] = p119bfixb_pass('no v1mc master console helper include', !str_contains($index, 'moghare360-v1-master-console-helper.php'));

$pass = 0;
$fail = 0;
echo "# P11.9-B-FIX-B Public Home Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
