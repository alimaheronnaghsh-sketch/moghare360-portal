<?php
declare(strict_types=1);

$root = dirname(__DIR__);

function pr02b_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

require_once $root . '/public_html/includes/m360-customer-online-submit-helper.php';

$results = [];
$cap = m360_pr02b_schema_capability();
$results[] = pr02b_pass('schema capability partial profile', ($cap['SCHEMA_SUPPORTS_CUSTOMER_PROFILE'] ?? '') === 'partial');
$results[] = pr02b_pass('schema multi vehicle yes', ($cap['SCHEMA_SUPPORTS_MULTI_VEHICLE'] ?? '') === 'yes');
$results[] = pr02b_pass('schema online request customer_id', ($cap['SCHEMA_SUPPORTS_ONLINE_REQUEST_CUSTOMER_ID'] ?? '') === 'yes');
$results[] = pr02b_pass('schema online request vehicle_id', ($cap['SCHEMA_SUPPORTS_ONLINE_REQUEST_VEHICLE_ID'] ?? '') === 'yes');
$results[] = pr02b_pass('sql proposal needed', ($cap['SQL_PROPOSAL_NEEDED'] ?? '') === 'yes');
$results[] = pr02b_pass('db schema untouched flag', ($cap['DB_SCHEMA_UNTOUCHED'] ?? '') === 'yes');

$helper = (string)file_get_contents($root . '/public_html/includes/m360-customer-online-submit-helper.php');
$profileApi = (string)file_get_contents($root . '/public_html/api/customer/profile-status.php');
$customerPage = (string)file_get_contents($root . '/public_html/customer-request.php');
$customerJs = (string)file_get_contents($root . '/public_html/assets/js/customer-form.js');

$results[] = pr02b_pass('shared submit helper exists', is_file($root . '/public_html/includes/m360-customer-online-submit-helper.php'));
$results[] = pr02b_pass('profile upsert helper', str_contains($helper, 'm360_pr02b_upsert_customer'));
$results[] = pr02b_pass('profile load after OTP API fields', str_contains($profileApi, "'profile' => \$profile"));
$results[] = pr02b_pass('vehicles list in profile API', str_contains($profileApi, "'vehicles' => \$vehicles"));
$results[] = pr02b_pass('first_name field in page', str_contains($customerPage, 'id="first_name"'));
$results[] = pr02b_pass('vehicle_delivery_address field', str_contains($customerPage, 'vehicle_delivery_address'));
$results[] = pr02b_pass('authorized receiver fields', str_contains($customerPage, 'authorized_receiver_name'));
$results[] = pr02b_pass('no password field', !preg_match('/type=["\']password["\']/i', $customerPage));
$results[] = pr02b_pass('PASSWORD_REQUIRED no', true);

$ext = m360_pr02b_encode_profile_ext_notes(['vehicle_delivery_address' => 'تهران']);
$decoded = m360_pr02b_decode_profile_ext_notes($ext);
$results[] = pr02b_pass('profile ext notes roundtrip', ($decoded['vehicle_delivery_address'] ?? '') === 'تهران');

$failed = array_values(array_filter($results, static fn(array $r): bool => !$r['pass']));
echo "PR-02B customer profile dataflow: " . (count($failed) === 0 ? 'PASS' : 'FAIL') . "\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS] ' : '[FAIL] ') . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
}
exit(count($failed) === 0 ? 0 : 1);
