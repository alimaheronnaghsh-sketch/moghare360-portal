<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$indexPath = $root . '/public_html/index.php';

function p119bfixe_lock_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$results = [];
$index = is_file($indexPath) ? (string)file_get_contents($indexPath) : '';

$results[] = p119bfixe_lock_pass('index.php exists', is_file($indexPath));

$oldMarkers = [
    'v1mc_render_head', 'moghare360-v1-master-console-helper',
    'SQL Server SaaS', 'Legacy MySQL portal inactive',
    'Local Master ERP', 'Master ERP Entry', 'v1mc-badge-ready',
];
foreach ($oldMarkers as $m) {
    $results[] = p119bfixe_lock_pass('no old Master ERP: ' . $m, !str_contains($index, $m));
}

$results[] = p119bfixe_lock_pass('no READY badge', !preg_match('/\bREADY\b/', $index));
$results[] = p119bfixe_lock_pass('no CHECK badge', !preg_match('/\bCHECK\b/', $index));
$results[] = p119bfixe_lock_pass('no BLOCKED badge', !preg_match('/\bBLOCKED\b/', $index));

$results[] = p119bfixe_lock_pass('Persian locked title', str_contains($index, 'ورود بخش مدیریت نرم‌افزار'));
$results[] = p119bfixe_lock_pass('owner-login.php present', str_contains($index, 'owner-login.php'));
$results[] = p119bfixe_lock_pass('session guard', str_contains($index, 'erp_auth_context_session_user_id'));
$results[] = p119bfixe_lock_pass('admin gate', str_contains($index, 'if (!$isAdmin)'));

$lockedBlock = '';
if (preg_match('/if\s*\(\s*!\s*\$isAdmin\s*\)\s*\{([\s\S]*?)exit;/', $index, $m)) {
    $lockedBlock = $m[1];
}
$internalInLocked = preg_match(
    '/erp-v1-master-console|erp-access-management|erp-product-home|Master Console|Unit Access Console/i',
    $lockedBlock
) === 1;
$results[] = p119bfixe_lock_pass('locked view has no internal hub links', $lockedBlock !== '' && !$internalInLocked);

$results[] = p119bfixe_lock_pass('Persian admin hub title', str_contains($index, 'کنسول مدیریت مقاره ۳۶۰'));
$results[] = p119bfixe_lock_pass('back link to public root ./', str_contains($index, 'href="./"'));

$pass = 0;
$fail = 0;
echo "# P11.9-B-FIX-E Index Public Lock Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
