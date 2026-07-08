<?php
declare(strict_types=1);

$root = dirname(__DIR__);

function pr02b_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

function pr02b_hash(string $path): string
{
    return is_file($path) ? hash_file('sha256', $path) : 'MISSING';
}

$otpUntouched = [
    'public_html/includes/m360-otp-helper.php',
    'public_html/api/customer/send-otp.php',
    'public_html/api/customer/verify-otp.php',
];

$forbiddenModified = [
    'public_html/staff-login.php',
    'public_html/staff-auth.php',
    'public_html/access-control.php',
    'public_html/erp-reception-intake-file.php',
];

$results = [];
foreach ($otpUntouched as $rel) {
    $path = $root . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $results[] = pr02b_pass('OTP file exists untouched path: ' . $rel, is_file($path));
}

$helper = (string)file_get_contents($root . '/public_html/includes/m360-customer-online-submit-helper.php');
$api = (string)file_get_contents($root . '/public_html/api/customer/request.php');
$results[] = pr02b_pass('no OTP provider modification in submit helper', !str_contains($helper, 'm360_otp_send') && !str_contains($helper, 'send_otp'));
$results[] = pr02b_pass('bind request entities for linkage', str_contains($helper, 'm360_reception_bind_request_entities'));
$results[] = pr02b_pass('no schema alter in runtime', !preg_match('/ALTER\s+TABLE/i', $helper));
$results[] = pr02b_pass('sql proposal file exists', is_file($root . '/docs/sql-proposals/MOGHARE360_PR_02B_CUSTOMER_PROFILE_SCHEMA_GAP_PROPOSAL.sql'));
$results[] = pr02b_pass('governance lock exists', is_file($root . '/docs/audit/MOGHARE360_PR_02B_GOVERNANCE_DECISION_LOCK.md'));

$receptionHelper = (string)file_get_contents($root . '/public_html/includes/m360-reception-helper.php');
$results[] = pr02b_pass('reception otp_verified filter preserved', str_contains($receptionHelper, 'ISNULL(r.otp_verified, 0) = 1'));
$results[] = pr02b_pass('reception linked customer join', str_contains($receptionHelper, 'erp_customer_name'));
$results[] = pr02b_pass('no JobCard create in PR-02B API', !str_contains($api, 'jobcard'));

foreach ($forbiddenModified as $rel) {
    $results[] = pr02b_pass('forbidden path still present: ' . $rel, is_file($root . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel)));
}

$brandJs = (string)file_get_contents($root . '/public_html/assets/js/vehicle-brand-classes.js');
$results[] = pr02b_pass('no Toyota in brand JS', !preg_match('/تویوتا|Toyota/i', $brandJs));

$failed = array_values(array_filter($results, static fn(array $r): bool => !$r['pass']));
echo "PR-02B scope security: " . (count($failed) === 0 ? 'PASS' : 'FAIL') . "\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS] ' : '[FAIL] ') . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
}
exit(count($failed) === 0 ? 0 : 1);
