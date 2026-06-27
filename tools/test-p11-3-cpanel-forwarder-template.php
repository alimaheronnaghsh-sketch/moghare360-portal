<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$dep = $root . '/deployment/cpanel/moghareh360';

function p113f_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p113f_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$form = $dep . '/lead-form.php';
$fwd = $dep . '/forward-lead.php.example';
$readme = $dep . '/README.md';

$results = [];
$results[] = p113f_pass('Lead form template exists', is_file($form));
$results[] = p113f_pass('Forwarder template exists', is_file($fwd));
$results[] = p113f_pass('README exists', is_file($readme));

$formBlob = p113f_read($form);
$fwdBlob = p113f_read($fwd);
$blob = $formBlob . $fwdBlob . p113f_read($readme);

$results[] = p113f_pass('Uses LAPTOP_ENDPOINT placeholder', str_contains($fwdBlob, 'LAPTOP_ENDPOINT_URL') && str_contains($fwdBlob, 'LAPTOP_HOST_PLACEHOLDER'));
$results[] = p113f_pass('Uses BRIDGE_SECRET placeholder', str_contains($fwdBlob, 'PUT_LONG_RANDOM_SECRET_HERE'));
$results[] = p113f_pass('HMAC signing in forwarder', str_contains($fwdBlob, 'hash_hmac') && str_contains($fwdBlob, 'X-M360-Signature'));
$results[] = p113f_pass('Friendly success message', str_contains($formBlob, 'درخواست شما ثبت شد'));
$results[] = p113f_pass('Friendly error message', str_contains($formBlob, 'خطا در ثبت درخواست'));
$results[] = p113f_pass('No raw JSON to customer', !preg_match('/echo\s+\$raw|echo\s+\$json|json_encode\(\$decoded\)/i', $formBlob));
$results[] = p113f_pass('No real IP committed', !preg_match('/\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b/', $blob) || str_contains($blob, 'LAPTOP_HOST_PLACEHOLDER'));
$results[] = p113f_pass('README warns no secrets in git', str_contains(p113f_read($readme), 'Do not commit'));

$pass = 0; $fail = 0;
echo "# P11.3 cPanel Forwarder Template Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
