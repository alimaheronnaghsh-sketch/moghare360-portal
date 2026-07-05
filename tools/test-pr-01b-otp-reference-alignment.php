<?php
declare(strict_types=1);

$root = dirname(__DIR__);

function pr01b_align_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$helperSrc = (string)file_get_contents($root . '/public_html/includes/m360-otp-helper.php');
$loaderSrc = (string)file_get_contents($root . '/public_html/includes/m360-otp-config-loader.php');
$js = (string)file_get_contents($root . '/public_html/assets/js/customer-form.js');
$diagSrc = (string)file_get_contents($root . '/tools/diagnose-pr-01b-otp-reference-reconcile.php');

$referenceFound = false;
foreach ([
    'C:' . DIRECTORY_SEPARATOR . 'xampp' . DIRECTORY_SEPARATOR . 'htdocs' . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'm360-otp-config.php',
    $root . '/private/m360-otp-config.php',
] as $path) {
    if (!is_file($path)) {
        continue;
    }
    require_once $root . '/public_html/includes/m360-otp-config-loader.php';
    $cfg = m360_otp_config_load_file($path);
    $api = trim((string)($cfg['M360_SMS_API_KEY'] ?? $cfg['ippanelApiKey'] ?? ''));
    if ($api !== '' && !m360_otp_is_placeholder_value($api)) {
        $referenceFound = true;
        break;
    }
}

$results = [];
$results[] = pr01b_align_pass('known-working reference detected', $referenceFound, $referenceFound ? '' : 'KNOWN_WORKING_REFERENCE_NOT_FOUND');
$results[] = pr01b_align_pass('diagnose pr01b tool exists', is_file($root . '/tools/diagnose-pr-01b-otp-reference-reconcile.php'));
$results[] = pr01b_align_pass('diagnose has probe A and B', str_contains($diagSrc, 'PROBE_A') && str_contains($diagSrc, 'PROBE_B'));
$results[] = pr01b_align_pass('diagnose no secret print markers', !str_contains($diagSrc, 'api_key') || str_contains($diagSrc, 'No OTP code'));
$results[] = pr01b_align_pass('canonical host edge.ippanel.com', str_contains($helperSrc, 'edge.ippanel.com'));
$results[] = pr01b_align_pass('canonical endpoint /v1/api/send', str_contains($helperSrc, '/v1/api/send'));
$results[] = pr01b_align_pass('canonical pattern payload code key', str_contains($helperSrc, "'code' => (string)\$settings['pattern_id']"));
$results[] = pr01b_align_pass('canonical auth header mode support', str_contains($helperSrc, 'm360_otp_ippanel_auth_header_mode'));
$results[] = pr01b_align_pass('loader apache htdocs private path', str_contains($loaderSrc, 'm360_otp_config_apache_htdocs_private_path'));
$results[] = pr01b_align_pass('loader htdocs private candidates', str_contains($loaderSrc, 'htdocs_private'));
$results[] = pr01b_align_pass('customer-form canonical send endpoint', str_contains($js, "api/customer/send-otp.php"));
$results[] = pr01b_align_pass('no contract otp in customer-form', !str_contains($js, 'contract-otp'));
$results[] = pr01b_align_pass('no legacy root send in customer-form', !preg_match("/fetchJson\\(['\"]send-otp\\.php/", $js));
$results[] = pr01b_align_pass('connect timeout in canonical send', str_contains($helperSrc, 'CURLOPT_CONNECTTIMEOUT'));

$pass = 0;
$fail = 0;
echo "# PR-01B OTP Reference Alignment Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
