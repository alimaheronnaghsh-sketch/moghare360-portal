<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$indexPath = $root . '/public_html/index.php';

function p119bfixd_admin_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$results = [];
$index = is_file($indexPath) ? (string)file_get_contents($indexPath) : '';

$results[] = p119bfixd_admin_pass('index.php exists', is_file($indexPath));
$results[] = p119bfixd_admin_pass('no old v1mc helper include', !str_contains($index, 'moghare360-v1-master-console-helper'));
$results[] = p119bfixd_admin_pass('no SQL Server SaaS banner', !str_contains($index, 'SQL Server SaaS'));
$results[] = p119bfixd_admin_pass('no Legacy MySQL banner', !str_contains($index, 'Legacy MySQL portal inactive'));
$results[] = p119bfixd_admin_pass('no READY badge text', !preg_match('/\bREADY\b/', $index));
$results[] = p119bfixd_admin_pass('no CHECK badge text', !preg_match('/\bCHECK\b/', $index));
$results[] = p119bfixd_admin_pass('no BLOCKED badge text', !preg_match('/\bBLOCKED\b/', $index));

$results[] = p119bfixd_admin_pass('Persian locked title', str_contains($index, 'ورود بخش مدیریت نرم‌افزار'));
$results[] = p119bfixd_admin_pass('Persian locked message', str_contains($index, 'مدیران مجاز نرم‌افزار'));
$results[] = p119bfixd_admin_pass('owner-login.php in locked view', str_contains($index, 'owner-login.php'));
$results[] = p119bfixd_admin_pass('ورود مدیر سیستم button', str_contains($index, 'ورود مدیر سیستم'));
$results[] = p119bfixd_admin_pass('back link to public home', str_contains($index, 'index.html'));

$results[] = p119bfixd_admin_pass('session guard uses session user id', str_contains($index, 'erp_auth_context_session_user_id'));
$results[] = p119bfixd_admin_pass('admin check before hub', str_contains($index, 'if (!$isAdmin)'));

$lockedBlock = '';
if (preg_match('/if\s*\(\s*!\s*\$isAdmin\s*\)\s*\{([\s\S]*?)exit;/', $index, $m)) {
    $lockedBlock = $m[1];
}
$internalInLocked = preg_match('/erp-v1-master-console|erp-access-management|erp-product-home|erp-route-map/i', $lockedBlock) === 1;
$results[] = p119bfixd_admin_pass('locked block has no internal hub links', $lockedBlock !== '' && !$internalInLocked);

$results[] = p119bfixd_admin_pass('Persian admin hub title', str_contains($index, 'کنسول مدیریت مقاره ۳۶۰'));
$results[] = p119bfixd_admin_pass('Persian admin hub subtitle', str_contains($index, 'دسترسی مدیریتی محافظت‌شده'));
$results[] = p119bfixd_admin_pass('hub link management', str_contains($index, 'مدیریت کاربران و دسترسی'));
$results[] = p119bfixd_admin_pass('hub uses existing file check', str_contains($index, 'm360_admin_index_page_exists'));

$pass = 0;
$fail = 0;
echo "# P11.9-B-FIX-D Admin Index Guard Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
