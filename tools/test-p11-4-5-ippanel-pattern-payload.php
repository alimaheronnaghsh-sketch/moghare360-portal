<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/public_html/includes/m360-otp-helper.php';

function p1145p_pass(string $n, bool $ok, string $d = ''): array
{
    return ['name' => $n, 'pass' => $ok, 'detail' => $d];
}

$results = [];

$settings = [
    'provider' => 'ippanel',
    'api_key' => 'test-key-not-used-in-payload',
    'sender' => '+983000505',
    'pattern_id' => 'pattern-code-abc',
    'otp_variable' => 'OTP',
];

$payload = m360_otp_ippanel_pattern_payload('09121234567', '654321', $settings);

$results[] = p1145p_pass('sending_type = pattern', ($payload['sending_type'] ?? '') === 'pattern');
$results[] = p1145p_pass('from_number mapped from sender', ($payload['from_number'] ?? '') !== '' && str_contains((string)$payload['from_number'], '983000505'));
$results[] = p1145p_pass('code mapped from pattern code', ($payload['code'] ?? '') === 'pattern-code-abc');
$results[] = p1145p_pass('recipients is array', is_array($payload['recipients'] ?? null) && ($payload['recipients'] ?? []) !== []);
$results[] = p1145p_pass('recipient normalized for IPPanel', str_starts_with((string)(($payload['recipients'] ?? [])[0] ?? ''), '+98'));

$params = $payload['params'] ?? [];
$paramKeys = array_keys(is_array($params) ? $params : []);
$results[] = p1145p_pass('params key uses OTP', in_array('OTP', $paramKeys, true));
$results[] = p1145p_pass('no %OTP% key used', !in_array('%OTP%', $paramKeys, true));
$results[] = p1145p_pass('params OTP value is code not masked', ($params['OTP'] ?? '') === '654321');

$customSettings = $settings;
$customSettings['otp_variable'] = 'CODE';
$customPayload = m360_otp_ippanel_pattern_payload('09121234567', '111222', $customSettings);
$customKeys = array_keys($customPayload['params'] ?? []);
$results[] = p1145p_pass('configured variable name used as params key', in_array('CODE', $customKeys, true) && !in_array('%CODE%', $customKeys, true));

$percentSettings = $settings;
$percentSettings['otp_variable'] = '%OTP%';
$percentPayload = m360_otp_ippanel_pattern_payload('09121234567', '999888', $percentSettings);
$percentKeys = array_keys($percentPayload['params'] ?? []);
$results[] = p1145p_pass('%OTP% variable stripped to OTP key', in_array('OTP', $percentKeys, true) && !in_array('%OTP%', $percentKeys, true));

$json = json_encode($payload, JSON_UNESCAPED_UNICODE);
$results[] = p1145p_pass('no utf/debug leak in payload json', !str_contains((string)$json, 'test-key-not-used-in-payload'));
$results[] = p1145p_pass('payload json is valid', is_string($json) && $json !== '');

$pass = 0;
$fail = 0;
echo "# P11.4.5 IPPanel Pattern Payload Test\n\n";
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
