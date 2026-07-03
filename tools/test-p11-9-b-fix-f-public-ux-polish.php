<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$htmlPath = $root . '/public_html/index.html';
$cssPath = $root . '/public_html/assets/css/moghare360-v1-luxury-ui.css';

function p119bfixf_ux_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$results = [];
$html = is_file($htmlPath) ? (string)file_get_contents($htmlPath) : '';
$css = is_file($cssPath) ? (string)file_get_contents($cssPath) : '';
$htmlLower = mb_strtolower($html);

$results[] = p119bfixf_ux_pass('public root index.html exists', is_file($htmlPath));

$required = [
    'مقاره ۳۶۰', 'MOGHARE360', 'خانه', 'پرسنل', 'مشتری', 'ورود پرسنل',
    'ورود مشتری', 'شرکت فنی مهندسی ماهین صنعت ماهران',
    'مجموعه محترم مقاره موتورز', 'ارتباط مستمر و شفاف با مشتری',
];
foreach ($required as $phrase) {
    $ok = str_contains($html, $phrase);
    if ($phrase === 'ارتباط مستمر و شفاف با مشتری') {
        $ok = $ok || str_contains($html, 'ارتباط مستمر با مشتری');
    }
    $results[] = p119bfixf_ux_pass('public contains: ' . $phrase, $ok);
}
$results[] = p119bfixf_ux_pass(
    'public contains phone',
    str_contains($html, '09131173340') || str_contains($html, '۰۹۱۳۱۱۷۳۳۴۰')
);

$sections = ['معرفی سامانه', 'ارزش‌های اجرایی', 'ارتباط مستمر با مشتری', 'مالکیت و پشتیبانی'];
foreach ($sections as $section) {
    $results[] = p119bfixf_ux_pass('section: ' . $section, str_contains($html, $section));
}

$forbidden = [
    'Local Master ERP Entry', 'SQL Server SaaS', 'Legacy MySQL portal inactive', 'Master ERP',
    'Master Console', 'Unit Access Console', 'Product Home', 'Production Signoff', 'Fix Register',
    'Route Map', 'Release Readiness', 'Demo Package', 'Soft Run Home', 'Moghare Ready',
    'Owner Login', 'ورود مدیریتی', 'Access Management', 'READY', 'CHECK', 'BLOCKED',
];
foreach ($forbidden as $label) {
    $results[] = p119bfixf_ux_pass('public does not contain: ' . $label, !str_contains($html, $label));
}

$results[] = p119bfixf_ux_pass('staff links to staff-login.php', str_contains($htmlLower, 'staff-login.php'));
$results[] = p119bfixf_ux_pass('customer links to customer-request.php', str_contains($htmlLower, 'customer-request.php'));
$results[] = p119bfixf_ux_pass('premium entry cards class', str_contains($html, 'm360-home-entry-card'));
$results[] = p119bfixf_ux_pass('hero panel class', str_contains($html, 'm360-home-hero'));
$results[] = p119bfixf_ux_pass('brand title uses m360-home-title', str_contains($html, 'm360-home-title'));
$results[] = p119bfixf_ux_pass('Persian font stack in CSS', str_contains($css, 'Vazirmatn'));
$results[] = p119bfixf_ux_pass('no letter-spacing on Persian title rule', str_contains($css, '.m360-home-title') && str_contains($css, 'letter-spacing: 0'));
$results[] = p119bfixf_ux_pass('two-column entry grid CSS', str_contains($css, 'm360-home-entry-grid'));
$results[] = p119bfixf_ux_pass('cache-busted CSS version', preg_match('/fix-f-v\d+/', $html) === 1);
$results[] = p119bfixf_ux_pass('no-cache meta on index.html', str_contains($html, 'no-cache, no-store, must-revalidate'));

$pass = 0;
$fail = 0;
echo "# P11.9-B-FIX-F Public UX Polish Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
