<?php
/**
 * MOGHARE360 ERP CSRF Helper Local Test
 *
 * CLI-only local test.
 * Safe output only.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/erp-csrf-helper.php';

$checks = [];

function add_check(array &$checks, string $code, string $name, bool $ok): void
{
    $checks[] = [
        'code' => $code,
        'name' => $name,
        'result' => $ok ? 'OK' : 'FAIL',
    ];
}

if (session_status() === PHP_SESSION_ACTIVE) {
    session_unset();
    session_destroy();
}

session_id('moghare360_csrf_test_' . bin2hex(random_bytes(8)));

erp_csrf_start();

add_check($checks, 'C01', 'CSRF helper loaded', function_exists('erp_csrf_generate'));
add_check($checks, 'C02', 'CSRF session storage initialized', isset($_SESSION['erp_csrf_tokens']) && is_array($_SESSION['erp_csrf_tokens']));

$purpose = 'access_request_create';
$token = erp_csrf_generate($purpose);

add_check($checks, 'C03', 'Token generated', is_string($token) && strlen($token) >= 64);
add_check($checks, 'C04', 'Token stored by purpose', isset($_SESSION['erp_csrf_tokens'][$purpose]));
add_check($checks, 'C05', 'Valid token passes', erp_csrf_validate($purpose, $token) === true);
add_check($checks, 'C06', 'Missing token fails', erp_csrf_validate($purpose, null) === false);
add_check($checks, 'C07', 'Empty token fails', erp_csrf_validate($purpose, '') === false);
add_check($checks, 'C08', 'Invalid token fails', erp_csrf_validate($purpose, 'invalid-token') === false);
add_check($checks, 'C09', 'Empty purpose fails', erp_csrf_validate('', $token) === false);

$inputHtml = erp_csrf_input('test_form');

add_check($checks, 'C10', 'Hidden input rendered', is_string($inputHtml) && strpos($inputHtml, 'type="hidden"') !== false);
add_check($checks, 'C11', 'Hidden input name is correct', strpos($inputHtml, 'name="erp_csrf_token"') !== false);
add_check($checks, 'C12', 'Hidden input does not expose session key name', strpos($inputHtml, 'erp_csrf_tokens') === false);

$clearPurpose = 'clear_test';
$clearToken = erp_csrf_generate($clearPurpose);

add_check($checks, 'C13', 'Clear test token valid before clear', erp_csrf_validate($clearPurpose, $clearToken) === true);

erp_csrf_clear($clearPurpose);

add_check($checks, 'C14', 'Clear removes purpose token', erp_csrf_validate($clearPurpose, $clearToken) === false);

$allOk = true;

echo "MOGHARE360 ERP CSRF Helper Local Test\n";
echo "=====================================\n";

foreach ($checks as $check) {
    if ($check['result'] !== 'OK') {
        $allOk = false;
    }

    echo $check['code'] . "\t" . $check['name'] . "\t" . $check['result'] . PHP_EOL;
}

echo "=====================================\n";
echo "Overall Status\t" . ($allOk ? 'OK' : 'FAIL') . PHP_EOL;

exit($allOk ? 0 : 1);
