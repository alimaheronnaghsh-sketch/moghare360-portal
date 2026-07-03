<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pub = $root . '/public_html';

function p119bfixf_sec_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$results = [];

foreach (['staff-login.php', 'owner-login.php'] as $f) {
    $path = $pub . '/' . $f;
    $content = is_file($path) ? (string)file_get_contents($path) : '';
    $results[] = p119bfixf_sec_pass(
        $f . ' login behavior unchanged',
        is_file($path) && str_contains($content, 'mirror_render_head') && !preg_match('/POST|password|session_start/u', $content) === false
    );
}

$crPath = $pub . '/customer-request.php';
$cr = is_file($crPath) ? (string)file_get_contents($crPath) : '';
$results[] = p119bfixf_sec_pass(
    'customer-request.php behavior unchanged',
    is_file($crPath) && str_contains($cr, 'mirror_render_head')
);

foreach (['staff-auth.php', 'access-control.php'] as $f) {
    foreach ([$pub . '/includes/' . $f, $pub . '/' . $f] as $path) {
        if (is_file($path)) {
            $results[] = p119bfixf_sec_pass($f . ' not modified recently', filemtime($path) <= time() - 300);
        }
    }
}

$allowed = [
    'index.html', 'index.php', '.htaccess', 'includes/mirror-layout.php',
    'assets/css/moghare360-v1-luxury-ui.css',
    'customer-login.php', 'customer-profile.php',
];
$gitOut = shell_exec('git -C ' . escapeshellarg($root) . ' status --porcelain public_html 2>nul');
$onlyAllowed = true;
if (is_string($gitOut) && trim($gitOut) !== '') {
    foreach (array_filter(array_map('trim', explode("\n", trim($gitOut)))) as $line) {
        $ok = false;
        foreach ($allowed as $a) {
            if (str_contains(str_replace('\\', '/', $line), 'public_html/' . $a)) {
                $ok = true;
                break;
            }
        }
        if (!$ok) {
            $onlyAllowed = false;
            break;
        }
    }
}
$results[] = p119bfixf_sec_pass('only FIX-F allowed public_html changes', $onlyAllowed);

$sqlRecent = false;
foreach (array_merge(glob($root . '/database/migrations/*.sql') ?: [], glob($root . '/database/dry-run/*.sql') ?: []) as $sql) {
    if (filemtime($sql) > time() - 300) {
        $sqlRecent = true;
        break;
    }
}
$results[] = p119bfixf_sec_pass('no SQL modified recently', !$sqlRecent);

$privateRecent = false;
if (is_dir($root . '/private')) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/private'));
    foreach ($it as $file) {
        if ($file->isFile() && $file->getMTime() > time() - 300) {
            $privateRecent = true;
            break;
        }
    }
}
$results[] = p119bfixf_sec_pass('no private files modified recently', !$privateRecent);

$results[] = p119bfixf_sec_pass(
    'implementation report exists',
    is_file($root . '/docs/audit/MOGHARE360_P11_9_B_FIX_F_PUBLIC_UX_CACHE_REPORT.md')
);

$pass = 0;
$fail = 0;
echo "# P11.9-B-FIX-F Scope Security Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
