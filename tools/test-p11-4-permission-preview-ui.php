<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pub = $root . '/public_html';
$preview = is_file($pub . '/includes/m360-access-permission-preview-helper.php') ? (string)file_get_contents($pub . '/includes/m360-access-permission-preview-helper.php') : '';
$page = is_file($pub . '/erp-access-permission-preview.php') ? (string)file_get_contents($pub . '/erp-access-permission-preview.php') : '';

function p114p_pass(string $n, bool $ok): array { return ['name' => $n, 'pass' => $ok]; }

$results = [
    p114p_pass('preview helper exists', $preview !== ''),
    p114p_pass('reads erp_auth_current_permissions', str_contains($preview, 'erp_auth_current_permissions')),
    p114p_pass('reads navigation registry', str_contains($preview, 'm360_nav_registry')),
    p114p_pass('warns unmapped routes', str_contains($preview, 'warnings')),
    p114p_pass('preview page read-only', !preg_match('/<form[^>]+method\s*=\s*["\']post/i', $page)),
    p114p_pass('preview page no POST handler', !str_contains($page, 'REQUEST_METHOD')),
];

$pass = 0; $fail = 0;
echo "# P11.4 Permission Preview UI Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
