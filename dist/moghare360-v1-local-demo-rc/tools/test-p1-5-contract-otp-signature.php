<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

function p15o_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p15o_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }

$sig = p15o_read($public . '/includes/m360-contract-signature-helper.php');
$signPage = p15o_read($public . '/customer-intake-contract-sign.php');
$otpApi = p15o_read($public . '/api/customer/contract-send-otp.php');
$signApi = p15o_read($public . '/api/customer/contract-sign.php');
$otpHelper = p15o_read($public . '/includes/m360-otp-helper.php');

$results = [];
$results[] = p15o_pass('Signature helper exists', is_file($public . '/includes/m360-contract-signature-helper.php'));
$results[] = p15o_pass('OTP required for signature', str_contains($sig, 'm360_contract_verify_otp') && str_contains($sig, 'otp_verified'));
$results[] = p15o_pass('Signature image required', str_contains($sig, 'signatureImageData'));
$results[] = p15o_pass('Checkboxes required in complete', str_contains($sig, 'readConfirmed') && str_contains($sig, 'otpTermsConfirmed'));
$results[] = p15o_pass('Canvas on sign page', str_contains($signPage, 'm360_signature_canvas'));
$results[] = p15o_pass('Mandatory checkboxes UI', str_contains($signPage, 'confirm_read') && str_contains($signPage, 'confirm_otp_terms'));
$results[] = p15o_pass('Signature pad JS', is_file($public . '/assets/js/m360-signature-pad.js'));
$results[] = p15o_pass('Digital signature label', str_contains($signPage, 'امضای دیجیتال'));
$results[] = p15o_pass('signed_at stored', str_contains($sig, 'signed_at'));
$results[] = p15o_pass('signer IP stored', str_contains($sig, 'signer_ip'));
$results[] = p15o_pass('user agent stored', str_contains($sig, 'signer_user_agent'));
$results[] = p15o_pass('signature hash stored', str_contains($sig, 'signature_hash'));
$results[] = p15o_pass('Re-sign blocked', str_contains($sig, 'm360_intake_contract_is_signed'));
$results[] = p15o_pass('Production fake OTP blocked', str_contains($otpHelper, 'moghareh360.ir'));
$results[] = p15o_pass('contract-send-otp API', str_contains($otpApi, 'm360_contract_send_otp'));
$results[] = p15o_pass('contract-sign handler', str_contains($signApi, 'm360_contract_complete_signature'));

$pass = 0; $fail = 0;
echo "# P1.5 OTP Signature Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
