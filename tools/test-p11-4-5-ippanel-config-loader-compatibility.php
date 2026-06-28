<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/public_html/includes/m360-otp-config-loader.php';
require_once $root . '/public_html/includes/m360-otp-helper.php';

function p1145c_pass(string $n, bool $ok, string $d = ''): array
{
    return ['name' => $n, 'pass' => $ok, 'detail' => $d];
}

$results = [];

$tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'm360-p1145-' . getmypid();
@mkdir($tmpDir, 0700, true);
$tmpConfig = $tmpDir . DIRECTORY_SEPARATOR . 'otp-config.php';
file_put_contents($tmpConfig, <<<'PHP'
<?php
declare(strict_types=1);
return [
    'M360_SMS_PROVIDER' => 'ippanel',
    'M360_SMS_API_KEY' => 'test-api-key-abcdef1234567890',
    'M360_SMS_SENDER' => '+983000505',
    'M360_SMS_PATTERN_CODE' => 'pattern-code-xyz99',
    'M360_SMS_PATTERN_VARIABLE' => 'OTP',
];
PHP
);
$loaded = m360_otp_config_load_file($tmpConfig);
$results[] = p1145c_pass('returned-array private config is supported', is_array($loaded) && ($loaded['M360_SMS_PROVIDER'] ?? '') === 'ippanel');

$canonical = m360_otp_config_normalize([
    'M360_SMS_PROVIDER' => 'ippanel',
    'M360_SMS_API_KEY' => 'abc123456789012345678901234567890',
    'M360_SMS_SENDER' => '+983000505',
    'M360_SMS_PATTERN_CODE' => 'pat-001',
    'M360_SMS_PATTERN_VARIABLE' => 'OTP',
]);
$results[] = p1145c_pass('canonical keys preserved after normalize', ($canonical['M360_SMS_API_KEY'] ?? '') !== '' && ($canonical['M360_SMS_PATTERN_CODE'] ?? '') === 'pat-001');

$aliases = m360_otp_config_normalize([
    'M360_SMS_PROVIDER' => 'ippanel',
    'IPPANEL_API_KEY' => 'abc123456789012345678901234567890',
    'IPPANEL_SENDER' => '+983000505',
    'IPPANEL_PATTERN_CODE' => 'pat-alias',
    'IPPANEL_OTP_VARIABLE' => 'OTP',
]);
$results[] = p1145c_pass('IPPANEL_* aliases map to canonical keys', ($aliases['M360_SMS_API_KEY'] ?? '') !== '' && ($aliases['M360_SMS_PATTERN_CODE'] ?? '') === 'pat-alias' && ($aliases['M360_SMS_PATTERN_VARIABLE'] ?? '') === 'OTP');

$wrongCase = m360_otp_config_normalize(['M360_SMS_PATTERN_Code' => 'wrong-case-code']);
$warningsWrong = m360_otp_config_collect_warnings(['M360_SMS_PATTERN_Code' => 'wrong-case-code']);
$results[] = p1145c_pass('wrong-case M360_SMS_PATTERN_Code detected', str_contains(implode(' ', $warningsWrong), 'M360_SMS_PATTERN_Code'));
$results[] = p1145c_pass('wrong-case key maps to M360_SMS_PATTERN_CODE', ($wrongCase['M360_SMS_PATTERN_CODE'] ?? '') === 'wrong-case-code');

$warningsId = m360_otp_config_collect_warnings(['IPPANEL_PATTERN_ID' => 'legacy-id-only']);
$results[] = p1145c_pass('IPPANEL_PATTERN_ID alone produces warning', str_contains(implode(' ', $warningsId), 'IPPANEL_PATTERN_ID'));

$noWarnWithCode = m360_otp_config_collect_warnings([
    'IPPANEL_PATTERN_ID' => 'legacy-id',
    'IPPANEL_PATTERN_CODE' => 'real-pattern',
]);
$results[] = p1145c_pass('IPPANEL_PATTERN_CODE suppresses sole-ID warning', $noWarnWithCode === []);

$results[] = p1145c_pass('placeholder YOUR_REAL rejected', m360_otp_is_placeholder_value('YOUR_REAL_IPPANEL_API_KEY'));
$results[] = p1145c_pass('placeholder My-Api rejected', m360_otp_is_placeholder_value('My-Api'));
$results[] = p1145c_pass('placeholder My-Pattern rejected', m360_otp_is_placeholder_value('My-Pattern'));
$results[] = p1145c_pass('real-looking key not placeholder', !m360_otp_is_placeholder_value('abc123456789012345678901234567890'));

$masked = m360_otp_config_mask_secret('abc123456789012345678901234567890');
$results[] = p1145c_pass('secrets are masked', str_contains($masked, '*') && !str_contains($masked, 'abc123456789012345678901234567890'));
$results[] = p1145c_pass('placeholder masked as (placeholder)', m360_otp_config_mask_secret('YOUR_REAL_IPPANEL_API_KEY') === '(placeholder)');

$example = m360_otp_config_example();
$results[] = p1145c_pass('example has canonical M360_SMS_PATTERN_CODE key', array_key_exists('M360_SMS_PATTERN_CODE', $example));
$results[] = p1145c_pass('example pattern variable defaults to OTP', (string)($example['M360_SMS_PATTERN_VARIABLE'] ?? '') === 'OTP');

@unlink($tmpConfig);
@rmdir($tmpDir);

$pass = 0;
$fail = 0;
echo "# P11.4.5 IPPanel Config Loader Compatibility Test\n\n";
foreach ($results as $r) {
    $line = ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'];
    if ($r['detail'] !== '') {
        $line .= ' — ' . $r['detail'];
    }
    echo $line . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
