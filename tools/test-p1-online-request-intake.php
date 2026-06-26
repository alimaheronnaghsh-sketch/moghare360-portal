<?php
declare(strict_types=1);

/**
 * MOGHARE360 P1 — Online request intake tests.
 */

$root = dirname(__DIR__);
$phpBin = is_file('C:\\xampp\\php\\php.exe') ? 'C:\\xampp\\php\\php.exe' : 'php';
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

function p1i_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

function p1i_read(string $path): string
{
    return is_file($path) ? (string)file_get_contents($path) : '';
}

function p1i_lint(string $rel): array
{
    global $phpBin, $public;
    $path = $public . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (!is_file($path)) {
        return p1i_pass('PHP lint: ' . $rel, false, 'missing');
    }
    $out = [];
    $code = 1;
    exec('"' . $phpBin . '" -l ' . escapeshellarg($path) . ' 2>&1', $out, $code);
    return p1i_pass('PHP lint: ' . $rel, $code === 0, implode(' ', $out));
}

$requestApi = p1i_read($public . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'customer' . DIRECTORY_SEPARATOR . 'request.php');
$profileApi = p1i_read($public . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'customer' . DIRECTORY_SEPARATOR . 'profile-status.php');
$helper = p1i_read($public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-online-request-helper.php');
$otpHelper = p1i_read($public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-otp-helper.php');
$migration = p1i_read($root . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR . 'P1_online_request_intake.sql');

$results = [];

$results[] = p1i_pass('Online request helper exists', is_file($public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-online-request-helper.php'));
$results[] = p1i_pass('request.php uses online request helper', str_contains($requestApi, 'm360-online-request-helper.php') && str_contains($requestApi, 'm360_online_req_insert'));
$results[] = p1i_pass('request.php OTP gate', str_contains($requestApi, 'm360_otp_is_verified') && str_contains($requestApi, 'شماره موبایل تأیید نشده است'));
$results[] = p1i_pass('request.php customer/vehicle resolve path', str_contains($helper, 'm360_online_req_resolve_customer_id') && str_contains($helper, 'm360_online_req_resolve_vehicle_id'));
$results[] = p1i_pass('Initial status NEW', str_contains($helper, "M360_ONLINE_REQ_STATUS_NEW") && str_contains($helper, "return M360_ONLINE_REQ_STATUS_NEW"));
$results[] = p1i_pass('Legacy PENDING maps to NEW filter', str_contains($helper, 'm360_online_req_canonical_status'));
$results[] = p1i_pass('Source PUBLIC_SITE', str_contains($helper, 'PUBLIC_SITE') && str_contains($requestApi, 'M360_ONLINE_REQ_SOURCE_PUBLIC'));
$results[] = p1i_pass('OTP verified in payload', str_contains($helper, "'otp_verified' => 1") || str_contains($helper, "'otp_verified'] = 1"));
$results[] = p1i_pass('profile_required in intake response', str_contains($requestApi, 'profile_required'));
$results[] = p1i_pass('profile-status returns customer_id', str_contains($profileApi, 'customer_id') && str_contains($profileApi, 'm360-online-request-helper.php'));
$results[] = p1i_pass('No fake OTP on production in helper', str_contains($otpHelper, 'moghareh360.ir'));
$results[] = p1i_pass('P1 migration is non-destructive', str_contains($migration, 'IF COL_LENGTH') && !preg_match('/\bDROP\b/i', $migration));
$results[] = p1i_pass('Migration adds converted_jobcard_id', str_contains($migration, 'converted_jobcard_id'));
$results[] = p1i_pass('Migration adds history table', str_contains($migration, 'erp_customer_online_request_history'));
$results[] = p1i_pass('No credentials in request API', !preg_match('/api[_-]?key|password\s*=\s*[\'"][^\'"]{6,}/i', $requestApi));
$results[] = p1i_lint('api/customer/request.php');
$results[] = p1i_lint('api/customer/profile-status.php');
$results[] = p1i_lint('includes/m360-online-request-helper.php');

require_once $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-online-request-helper.php';
$results[] = p1i_pass('Canonical status maps PENDING to NEW', m360_online_req_canonical_status('PENDING') === M360_ONLINE_REQ_STATUS_NEW);
$results[] = p1i_pass('Initial status constant is NEW', m360_online_req_initial_status() === M360_ONLINE_REQ_STATUS_NEW);

$pass = 0;
$fail = 0;
echo "# MOGHARE360 P1 Online Request Intake Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'];
    if ($r['detail'] !== '') {
        echo ' — ' . $r['detail'];
    }
    echo "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
