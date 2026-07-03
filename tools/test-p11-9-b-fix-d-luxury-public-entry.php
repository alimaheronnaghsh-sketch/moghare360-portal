<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$htmlPath = $root . '/public_html/index.html';
$indexPath = $root . '/public_html/index.php';

function p119bfixd_pub_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$results = [];
$html = is_file($htmlPath) ? (string)file_get_contents($htmlPath) : '';
$htmlLower = mb_strtolower($html);

$results[] = p119bfixd_pub_pass('public root landing index.html exists', is_file($htmlPath));

$required = [
    'مقاره ۳۶۰', 'خانه', 'پرسنل', 'مشتری', 'ورود پرسنل',
    'شرکت فنی مهندسی ماهین صنعت ماهران', 'مجموعه محترم مقاره موتورز',
    'ارتباط مستمر و شفاف با مشتری',
];
foreach ($required as $phrase) {
    $results[] = p119bfixd_pub_pass('public contains: ' . $phrase, str_contains($html, $phrase));
}
$results[] = p119bfixd_pub_pass(
    'public contains phone',
    str_contains($html, '09131173340') || str_contains($html, '۰۹۱۳۱۱۷۳۳۴۰')
);
$results[] = p119bfixd_pub_pass('public links staff-login.php', str_contains($htmlLower, 'staff-login.php'));
$results[] = p119bfixd_pub_pass('public links customer-request.php', str_contains($htmlLower, 'customer-request.php'));

$forbidden = [
    'Master Console', 'Unit Access Console', 'Product Home', 'Production Signoff',
    'Fix Register', 'Route Map', 'Release Readiness', 'Demo Package', 'Soft Run Home',
    'Moghare Ready', 'Owner Login', 'ورود مدیریتی', 'Access Management',
    'READY', 'CHECK', 'BLOCKED', 'SQL Server SaaS', 'Legacy MySQL portal inactive',
    'owner-login.php', 'erp-v1-master-console.php', 'erp-access-management.php',
];
foreach ($forbidden as $label) {
    $results[] = p119bfixd_pub_pass('public does not contain: ' . $label, !str_contains($html, $label));
}

$results[] = p119bfixd_pub_pass('public RTL', str_contains($html, 'dir="rtl"'));
$results[] = p119bfixd_pub_pass('public luxury styling', str_contains($html, 'lux-hero'));

$htaccess = $root . '/public_html/.htaccess';
$ht = is_file($htaccess) ? (string)file_get_contents($htaccess) : '';
$results[] = p119bfixd_pub_pass('.htaccess DirectoryIndex index.html first', preg_match('/DirectoryIndex\s+index\.html\s+index\.php/i', $ht) === 1);

$pass = 0;
$fail = 0;
echo "# P11.9-B-FIX-D Luxury Public Entry Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
