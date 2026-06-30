<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pub = $root . '/public_html';

function p119bfixb_sec_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$results = [];

$authFiles = [
    'staff-login.php',
    'owner-login.php',
];
foreach ($authFiles as $f) {
    $path = $pub . '/' . $f;
    $recent = is_file($path) && filemtime($path) > time() - 300;
    $results[] = p119bfixb_sec_pass($f . ' not modified recently', is_file($path) && !$recent);
}

$authGlob = glob($pub . '/includes/*auth*') ?: [];
$authGlob = array_merge($authGlob, glob($pub . '/api/auth/*.php') ?: []);
foreach ($authGlob as $path) {
    if (is_file($path) && filemtime($path) > time() - 300) {
        $results[] = p119bfixb_sec_pass('auth file not recently modified: ' . basename($path), false);
    }
}
if ($authGlob === []) {
    $results[] = p119bfixb_sec_pass('auth glob scan completed', true);
}

$onlyIndexChanged = true;
$gitOut = shell_exec('git -C ' . escapeshellarg($root) . ' status --porcelain public_html 2>nul');
if (is_string($gitOut) && trim($gitOut) !== '') {
    $lines = array_filter(array_map('trim', explode("\n", trim($gitOut))));
    foreach ($lines as $line) {
        if (!preg_match('#public_html/index\.php$#', $line) && !preg_match('#public_html/index\.php#', $line)) {
            $onlyIndexChanged = false;
            break;
        }
    }
}
$results[] = p119bfixb_sec_pass('only index.php changed under public_html (git)', $onlyIndexChanged);

$sqlDir = $root . '/database';
$sqlRecent = false;
foreach (glob($sqlDir . '/migrations/*.sql') ?: [] as $sql) {
    if (filemtime($sql) > time() - 300) {
        $sqlRecent = true;
        break;
    }
}
foreach (glob($sqlDir . '/dry-run/*.sql') ?: [] as $sql) {
    if (filemtime($sql) > time() - 300) {
        $sqlRecent = true;
        break;
    }
}
$results[] = p119bfixb_sec_pass('no SQL migration/dry-run file modified recently', !$sqlRecent);

$roleSeed = $pub . '/sql/sqlserver/core_v0_06_seed_roles_permissions.sql';
$seedRecent = is_file($roleSeed) && filemtime($roleSeed) > time() - 300;
$results[] = p119bfixb_sec_pass('no permission/role seed modified recently', !$seedRecent);

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
$results[] = p119bfixb_sec_pass('no private files modified recently', !$privateRecent);

$index = (string)file_get_contents($pub . '/index.php');
$results[] = p119bfixb_sec_pass('no P12 scope in index.php', !preg_match('/\bP12\b/i', $index));

$workflowHandlers = glob($pub . '/includes/m360-*-action*.php') ?: [];
$handlerRecent = false;
foreach ($workflowHandlers as $h) {
    if (filemtime($h) > time() - 300) {
        $handlerRecent = true;
        break;
    }
}
$results[] = p119bfixb_sec_pass('no workflow action handler modified recently', !$handlerRecent);

$fixReport = $root . '/docs/audit/MOGHARE360_P11_9_B_FIX_B_PUBLIC_HOME_REPORT.md';
$results[] = p119bfixb_sec_pass('implementation report exists', is_file($fixReport));

$pass = 0;
$fail = 0;
echo "# P11.9-B-FIX-B Scope Security Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
