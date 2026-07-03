<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$htmlPath = $root . '/public_html/index.html';
$layoutPath = $root . '/public_html/includes/mirror-layout.php';

function p119bfixe_shell_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$results = [];
$html = is_file($htmlPath) ? (string)file_get_contents($htmlPath) : '';
$htmlLower = mb_strtolower($html);
$layout = is_file($layoutPath) ? (string)file_get_contents($layoutPath) : '';

$results[] = p119bfixe_shell_pass('public root index.html exists', is_file($htmlPath));

$required = [
    'مقاره ۳۶۰', 'خانه', 'پرسنل', 'مشتری', 'ورود پرسنل',
    'ورود مشتری', 'شرکت فنی مهندسی ماهین صنعت ماهران',
    'مجموعه محترم مقاره موتورز', 'ارتباط مستمر و شفاف با مشتری',
];
foreach ($required as $phrase) {
    $results[] = p119bfixe_shell_pass('public contains: ' . $phrase, str_contains($html, $phrase));
}
$results[] = p119bfixe_shell_pass(
    'public contains phone',
    str_contains($html, '09131173340') || str_contains($html, '۰۹۱۳۱۱۷۳۳۴۰')
);

$results[] = p119bfixe_shell_pass('staff links to staff-login.php', str_contains($htmlLower, 'staff-login.php'));
$results[] = p119bfixe_shell_pass('customer links to customer-request.php', str_contains($htmlLower, 'customer-request.php'));

$results[] = p119bfixe_shell_pass('uses mirror.css', str_contains($html, 'assets/css/mirror.css'));
$results[] = p119bfixe_shell_pass('uses luxury-ui.css', str_contains($html, 'moghare360-v1-luxury-ui.css'));
$results[] = p119bfixe_shell_pass('uses m360-public-shell', str_contains($html, 'm360-public-shell'));
$results[] = p119bfixe_shell_pass('home nav points to ./', str_contains($html, 'href="./"'));
$results[] = p119bfixe_shell_pass('no isolated lux-bronze theme', !str_contains($html, '--lux-gold'));

$forbidden = [
    'Master Console', 'Unit Access Console', 'Product Home', 'Production Signoff',
    'Fix Register', 'Route Map', 'Release Readiness', 'Demo Package', 'Soft Run Home',
    'Moghare Ready', 'Owner Login', 'ورود مدیریتی', 'Access Management',
    'READY', 'CHECK', 'BLOCKED', 'SQL Server SaaS', 'Legacy MySQL portal inactive',
    'Local Master ERP Entry', 'Master ERP', 'owner-login.php',
    'erp-v1-master-console.php',
];
foreach ($forbidden as $label) {
    $results[] = p119bfixe_shell_pass('public does not contain: ' . $label, !str_contains($html, $label));
}

$results[] = p119bfixe_shell_pass('mirror-layout home uses ./', str_contains($layout, "'index' => ['./', 'خانه']"));
$results[] = p119bfixe_shell_pass('mirror-layout brand uses ./', str_contains($layout, 'href="./" class="m360-public-brand"'));
$results[] = p119bfixe_shell_pass('mirror-layout no index.php home', !str_contains($layout, "'index.php', 'خانه'"));

$checkedPages = [
    'staff-login.php' => $root . '/public_html/staff-login.php',
    'owner-login.php' => $root . '/public_html/owner-login.php',
    'customer-request.php' => $root . '/public_html/customer-request.php',
    'user-access-request.php' => $root . '/public_html/user-access-request.php',
    'mirror-layout.php' => $layoutPath,
];
foreach ($checkedPages as $name => $path) {
    if (!is_file($path)) {
        continue;
    }
    $content = (string)file_get_contents($path);
    $results[] = p119bfixe_shell_pass(
        $name . ' no خانه link to index.php',
        !preg_match('/[\'"]index\.php[\'"].*خانه|خانه.*index\.php|href=[\'"]index\.php[\'"]/u', $content)
    );
}

$pass = 0;
$fail = 0;
echo "# P11.9-B-FIX-E Entry Shell Home Links Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
