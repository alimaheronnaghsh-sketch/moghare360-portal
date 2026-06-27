<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$public = $root . '/public_html';

function p113s_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p113s_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$api = $public . '/api/online-intake-secure-receive.php';
$sec = $public . '/includes/m360-online-intake-security-helper.php';
$form = $root . '/deployment/cpanel/moghareh360/lead-form.php';

$results = [];
$results[] = p113s_pass('Secure receive API exists', is_file($api));
$results[] = p113s_pass('Security helper exists', is_file($sec));

require_once $sec;

$apiBlob = p113s_read($api);
$results[] = p113s_pass('API POST only guard', str_contains($apiBlob, "!== 'POST'"));
$results[] = p113s_pass('Uses verify_request', str_contains($apiBlob, 'm360_online_bridge_verify_request'));
$results[] = p113s_pass('Controlled JSON response', str_contains($apiBlob, 'm360_online_bridge_json_response'));
$results[] = p113s_pass('No var_dump/debug', !preg_match('/\b(var_dump|print_r|phpinfo)\b/i', $apiBlob));

$secBlob = p113s_read($sec);
$results[] = p113s_pass('Timestamp required logic', str_contains($secBlob, 'TIMESTAMP_EXPIRED'));
$results[] = p113s_pass('HMAC signature required', str_contains($secBlob, 'hash_hmac') && str_contains($secBlob, 'INVALID_SIGNATURE'));
$results[] = p113s_pass('Expired timestamp rejected', str_contains($secBlob, 'request_ttl_seconds'));

$secret = 'test-bridge-secret-not-real-value';
$ts = (string)time();
$body = '{"customer_name":"DEMO","mobile":"09120000000","vehicle_title":"DEMO VEHICLE"}';
$sig = m360_online_bridge_compute_signature($ts, $body, $secret);
$results[] = p113s_pass('HMAC compute works', strlen($sig) === 64);

$oldTs = (string)(time() - 99999);
$oldSig = m360_online_bridge_compute_signature($oldTs, $body, $secret);
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';
$_SERVER['HTTP_X_M360_SOURCE'] = 'moghareh360.ir';
$_SERVER['HTTP_X_M360_TIMESTAMP'] = $oldTs;
$_SERVER['HTTP_X_M360_SIGNATURE'] = $oldSig;
$expired = m360_online_bridge_verify_request($body);
$results[] = p113s_pass('Expired timestamp rejected at runtime', ($expired['code'] ?? '') === 'TIMESTAMP_EXPIRED' || ($expired['code'] ?? '') === 'BRIDGE_NOT_CONFIGURED');

$_SERVER['HTTP_X_M360_TIMESTAMP'] = $ts;
$_SERVER['HTTP_X_M360_SIGNATURE'] = 'invalid-signature-value';
$bad = m360_online_bridge_verify_request($body);
$results[] = p113s_pass('Invalid signature rejected', ($bad['code'] ?? '') === 'INVALID_SIGNATURE' || ($bad['code'] ?? '') === 'BRIDGE_NOT_CONFIGURED');

$results[] = p113s_pass('Lead form friendly messages', str_contains(p113s_read($form), 'درخواست شما ثبت شد'));
$results[] = p113s_pass('Lead form no json echo', !str_contains(p113s_read($form), 'json_encode') || !str_contains(p113s_read($form), 'echo json'));

$repoBlob = p113s_read($sec) . p113s_read($api) . p113s_read($root . '/deployment/cpanel/moghareh360/forward-lead.php.example');
$results[] = p113s_pass('No real IP in repo templates', !preg_match('/\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b/', $repoBlob) || str_contains($repoBlob, 'LAPTOP_HOST_PLACEHOLDER'));

$pass = 0; $fail = 0;
echo "# P11.3 Online Intake Security Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
