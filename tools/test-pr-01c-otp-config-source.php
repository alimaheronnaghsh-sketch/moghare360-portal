<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/public_html/includes/m360-otp-config-loader.php';
require_once $root . '/public_html/includes/m360-otp-helper.php';

function pr01c_cfg_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$repoPrivate = $root . '/private/m360-otp-config.php';
$xamppPrivate = 'C:/xampp/htdocs/private/m360-otp-config.php';
$report = m360_otp_config_source_report();
$repoFp = m360_otp_config_safe_fingerprint($repoPrivate);
$xamppFp = is_file($xamppPrivate) ? m360_otp_config_safe_fingerprint($xamppPrivate) : '';

$tracked = false;
$gitLs = shell_exec('git -C ' . escapeshellarg($root) . ' ls-files --error-unmatch private/m360-otp-config.php 2>&1') ?? '';
if ($gitLs !== '' && !str_contains($gitLs, 'did not match')) {
    $tracked = true;
}

$results = [];
$results[] = pr01c_cfg_pass('repo private exists', is_file($repoPrivate));
$results[] = pr01c_cfg_pass('repo private readable', is_readable($repoPrivate));
$results[] = pr01c_cfg_pass('config source report exists', function_exists('m360_otp_config_source_report'));
$results[] = pr01c_cfg_pass('loaded private labels present', is_array($report['loaded_private_labels'] ?? null) && ($report['loaded_private_labels'] ?? []) !== []);
$results[] = pr01c_cfg_pass('sms configured', !empty($report['sms_configured']));
$results[] = pr01c_cfg_pass('safe fingerprint function', function_exists('m360_otp_config_safe_fingerprint') && $repoFp !== '');
$results[] = pr01c_cfg_pass('repo/xampp fingerprint match when both exist', !is_file($xamppPrivate) || ($xamppFp !== '' && $repoFp === $xamppFp));
$results[] = pr01c_cfg_pass('private config not git tracked', !$tracked, $tracked ? 'SECRET_RISK_TRACKED_CONFIG' : '');
$results[] = pr01c_cfg_pass('M360_REPO_ROOT candidate in loader', str_contains((string)file_get_contents($root . '/public_html/includes/m360-otp-config-loader.php'), 'M360_REPO_ROOT'));

$pass = 0;
$fail = 0;
echo "# PR-01C OTP Config Source Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
