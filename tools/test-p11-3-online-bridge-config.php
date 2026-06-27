<?php
declare(strict_types=1);

$root = dirname(__DIR__);

function p113c_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p113c_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$example = $root . '/private/m360-online-bridge-config.example.php';
$gitignore = p113c_read($root . '/.gitignore');

$results = [];
$results[] = p113c_pass('Example config exists', is_file($example));
$results[] = p113c_pass('Real config gitignored', str_contains($gitignore, 'private/m360-online-bridge-config.php'));
$results[] = p113c_pass('Logs gitignored', str_contains($gitignore, 'private/logs/'));

require_once $root . '/public_html/includes/m360-online-intake-security-helper.php';
$cfg = m360_online_bridge_config_example();
$results[] = p113c_pass('Example readable', $cfg !== []);
$results[] = p113c_pass('Placeholder secret in example', m360_online_bridge_is_placeholder_secret((string)($cfg['bridge_secret'] ?? '')));
$results[] = p113c_pass('bridge_secret key exists', array_key_exists('bridge_secret', $cfg));
$results[] = p113c_pass('allowed_sources exists', is_array($cfg['allowed_sources'] ?? null));
$results[] = p113c_pass('No real secret in example blob', !preg_match('/[a-f0-9]{32,}/i', p113c_read($example)));

$blob = p113c_read($example) . p113c_read($root . '/public_html/includes/m360-online-intake-security-helper.php');
$results[] = p113c_pass('No committed static IP pattern', !preg_match('/\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b/', $blob));

$pass = 0; $fail = 0;
echo "# P11.3 Online Bridge Config Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
