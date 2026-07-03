<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pub = $root . '/public_html';

function p119bfixd_sec_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$results = [];

foreach (['staff-login.php', 'owner-login.php'] as $f) {
    $path = $pub . '/' . $f;
    $recent = is_file($path) && filemtime($path) > time() - 300;
    $results[] = p119bfixd_sec_pass($f . ' not modified recently', is_file($path) && !$recent);
}

foreach (['staff-auth.php', 'access-control.php', 'config.php', 'config.example.php'] as $f) {
    $paths = [$pub . '/includes/' . $f, $pub . '/' . $f];
    $found = false;
    $recent = false;
    foreach ($paths as $path) {
        if (is_file($path)) {
            $found = true;
            if (filemtime($path) > time() - 300) {
                $recent = true;
            }
        }
    }
    if ($found) {
        $results[] = p119bfixd_sec_pass($f . ' not modified recently', !$recent);
    }
}

$allowedPublicChanges = ['index.html', 'index.php', '.htaccess'];
$gitOut = shell_exec('git -C ' . escapeshellarg($root) . ' status --porcelain public_html 2>nul');
$onlyAllowed = true;
if (is_string($gitOut) && trim($gitOut) !== '') {
    $lines = array_filter(array_map('trim', explode("\n", trim($gitOut))));
    foreach ($lines as $line) {
        $ok = false;
        foreach ($allowedPublicChanges as $allowed) {
            if (str_contains($line, 'public_html/' . $allowed) || str_contains($line, 'public_html\\' . $allowed)) {
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
$results[] = p119bfixd_sec_pass('only index.html/index.php/.htaccess changed in public_html', $onlyAllowed);

$sqlRecent = false;
foreach (array_merge(
    glob($root . '/database/migrations/*.sql') ?: [],
    glob($root . '/database/dry-run/*.sql') ?: []
) as $sql) {
    if (filemtime($sql) > time() - 300) {
        $sqlRecent = true;
        break;
    }
}
$results[] = p119bfixd_sec_pass('no SQL file modified recently', !$sqlRecent);

$roleSeed = $pub . '/sql/sqlserver/core_v0_06_seed_roles_permissions.sql';
$results[] = p119bfixd_sec_pass(
    'no permission/role seed modified recently',
    !(is_file($roleSeed) && filemtime($roleSeed) > time() - 300)
);

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
$results[] = p119bfixd_sec_pass('no private files modified recently', !$privateRecent);

$index = is_file($pub . '/index.php') ? (string)file_get_contents($pub . '/index.php') : '';
$results[] = p119bfixd_sec_pass('no P12 scope', !preg_match('/\bP12\b/i', $index));

$results[] = p119bfixd_sec_pass(
    'implementation report exists',
    is_file($root . '/docs/audit/MOGHARE360_P11_9_B_FIX_D_LUXURY_ENTRY_ADMIN_INDEX_REPORT.md')
);

$pass = 0;
$fail = 0;
echo "# P11.9-B-FIX-D Scope Security Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
