<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/public_html/includes/m360-otp-config-loader.php';

function pr01_cfg_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$loaderSrc = (string)file_get_contents($root . '/public_html/includes/m360-otp-config-loader.php');
$example = m360_otp_config_example();
$report = m360_otp_config_diagnostics_report();
$reportJson = json_encode($report, JSON_UNESCAPED_UNICODE);

$results = [];
$results[] = pr01_cfg_pass('loader htdocs private candidates', str_contains($loaderSrc, 'm360_otp_config_private_candidate_paths'));
$results[] = pr01_cfg_pass('loader loaded private labels', str_contains($loaderSrc, 'm360_otp_config_loaded_private_labels'));
$results[] = pr01_cfg_pass('diagnostics report has no raw api key', !preg_match('/"ippanelApiKey"\s*:\s*"[A-Za-z0-9]{20,}/', $reportJson));
$results[] = pr01_cfg_pass('example config api key is placeholder', m360_otp_is_placeholder_value((string)($example['M360_SMS_API_KEY'] ?? '')));
$results[] = pr01_cfg_pass('example config sender is placeholder', m360_otp_is_placeholder_value((string)($example['M360_SMS_SENDER'] ?? '')));
$results[] = pr01_cfg_pass('example config pattern is placeholder', m360_otp_is_placeholder_value((string)($example['M360_SMS_PATTERN_CODE'] ?? '')));
$results[] = pr01_cfg_pass('api_key masked in report', (string)($report['api_key']['masked'] ?? '') !== '' && !str_contains((string)($report['api_key']['masked'] ?? ''), 'YOUR_REAL'));
$results[] = pr01_cfg_pass('mask helper exists', function_exists('m360_otp_config_mask_secret'));

$pass = 0;
$fail = 0;
echo "# PR-01 OTP Config Loader Safety Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
