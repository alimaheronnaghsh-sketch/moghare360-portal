<?php
declare(strict_types=1);

$root = dirname(__DIR__);

function pr03_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$save = (string)file_get_contents($root . '/public_html/erp-reception-intake-save.php');
$helper = (string)file_get_contents($root . '/public_html/includes/m360-reception-workbench-helper.php');
$jsCustomer = (string)file_get_contents($root . '/public_html/assets/js/customer-form.js');
$jsReception = (string)file_get_contents($root . '/public_html/assets/js/m360-reception-intake.js');
$css = (string)file_get_contents($root . '/public_html/assets/css/mirror.css');
$layout = (string)file_get_contents($root . '/public_html/includes/mirror-layout.php');

$results = [];
$results[] = pr03_pass('complete_reception_intake in save file', str_contains($save, 'complete_reception_intake'));
$results[] = pr03_pass('save validates allowed actions', str_contains($save, 'm360_rw_intake_allowed_actions'));
$results[] = pr03_pass('complete action in helper allowed', str_contains($helper, "'complete_reception_intake'"));
$results[] = pr03_pass('contract not documents missing field', !preg_match('/function m360_rw_intake_documents_step_state[\s\S]*missing\[\] = \'contract_status\'/', $helper));
$results[] = pr03_pass('gate skips contract c2c_only missing', str_contains($helper, "['contract', 'final_confirm', 'cost', 'diag']"));
$results[] = pr03_pass('customer setButtonBusy helper', str_contains($jsCustomer, 'function setButtonBusy'));
$results[] = pr03_pass('customer loading cleared in finally', str_contains($jsCustomer, '.finally(function () { setButtonBusy(sendBtn, false);'));
$results[] = pr03_pass('customer pageshow clears busy', str_contains($jsCustomer, "window.addEventListener('pageshow', clearAllBusyButtons)"));
$results[] = pr03_pass('reception form loading bind', str_contains($jsReception, 'bindReceptionFormLoading'));
$results[] = pr03_pass('reception pageshow clears busy', str_contains($jsReception, "window.addEventListener('pageshow', clearAllBusyButtons)"));
$results[] = pr03_pass('spinner CSS marker', str_contains($css, 'button.m360-btn-is-loading'));
$results[] = pr03_pass('nav links excluded from PR-02B busy handler', str_contains($layout, 'm360-public-nav__link')
    && str_contains($layout, 'customer-request.php')
    && str_contains($jsCustomer, 'isBusyBlocked')
    && str_contains($jsCustomer, 'clearNavLoadingState'));
$results[] = pr03_pass('OTP provider config untouched', !str_contains($save, 'm360-otp-config.php'));
$results[] = pr03_pass('no JobCard in complete action', !preg_match('/case \'complete_reception_intake\'[\s\S]{0,2000}jobcard/i', $helper));

$failed = array_values(array_filter($results, static fn(array $r): bool => !$r['pass']));
echo "PR-02B UAT repair 3 runtime/spinner: " . (count($failed) === 0 ? 'PASS' : 'FAIL') . "\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS] ' : '[FAIL] ') . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
}
exit(count($failed) === 0 ? 0 : 1);
