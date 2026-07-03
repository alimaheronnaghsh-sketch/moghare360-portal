<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pub = $root . '/public_html';

function p119bfixe_sec_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$results = [];

foreach (['staff-login.php', 'owner-login.php'] as $f) {
    $path = $pub . '/' . $f;
    $content = is_file($path) ? (string)file_get_contents($path) : '';
    $recent = is_file($path) && filemtime($path) > time() - 300;
    $onlyHomeLink = !str_contains($content, 'index.php') || str_contains($content, 'mirror_render_head');
    $results[] = p119bfixe_sec_pass($f . ' not modified except safe home via layout', is_file($path) && (!$recent || $onlyHomeLink));
}

$crPath = $pub . '/customer-request.php';
$cr = is_file($crPath) ? (string)file_get_contents($crPath) : '';
$results[] = p119bfixe_sec_pass(
    'customer-request.php behavior unchanged',
    is_file($crPath) && str_contains($cr, 'mirror_render_head') && !preg_match('/^\s*<\?php[\s\S]*index\.php/u', $cr)
);

foreach (['staff-auth.php', 'access-control.php'] as $f) {
    foreach ([$pub . '/includes/' . $f, $pub . '/' . $f] as $path) {
        if (is_file($path)) {
            $results[] = p119bfixe_sec_pass($f . ' not modified recently', filemtime($path) <= time() - 300);
        }
    }
}

$allowed = ['index.html', 'index.php', '.htaccess', 'includes/mirror-layout.php', 'customer-login.php', 'customer-profile.php'];
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
$results[] = p119bfixe_sec_pass('only FIX-E allowed public_html changes', $onlyAllowed);

$sqlRecent = false;
foreach (array_merge(glob($root . '/database/migrations/*.sql') ?: [], glob($root . '/database/dry-run/*.sql') ?: []) as $sql) {
    if (filemtime($sql) > time() - 300) {
        $sqlRecent = true;
        break;
    }
}
$results[] = p119bfixe_sec_pass('no SQL modified recently', !$sqlRecent);

$roleSeed = $pub . '/sql/sqlserver/core_v0_06_seed_roles_permissions.sql';
$results[] = p119bfixe_sec_pass('no role seed modified recently', !(is_file($roleSeed) && filemtime($roleSeed) > time() - 300));

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
$results[] = p119bfixe_sec_pass('no private files modified recently', !$privateRecent);

$results[] = p119bfixe_sec_pass(
    'implementation report exists',
    is_file($root . '/docs/audit/MOGHARE360_P11_9_B_FIX_E_ENTRY_SHELL_HOME_LINK_REPORT.md')
);

$pass = 0;
$fail = 0;
echo "# P11.9-B-FIX-E Scope Security Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
