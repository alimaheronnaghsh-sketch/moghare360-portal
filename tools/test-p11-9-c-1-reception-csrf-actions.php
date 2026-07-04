<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$detail = $root . '/public_html/erp-reception-online-request-detail.php';
$accept = $root . '/public_html/erp-reception-online-request-accept.php';
$helper = $root . '/public_html/includes/m360-reception-helper.php';

function p119c1_csrf_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$results = [];
$detailSrc = is_file($detail) ? (string)file_get_contents($detail) : '';
$acceptSrc = is_file($accept) ? (string)file_get_contents($accept) : '';
$helperSrc = is_file($helper) ? (string)file_get_contents($helper) : '';

$results[] = p119c1_csrf_pass('detail uses single csrf helper', str_contains($detailSrc, 'm360_reception_csrf_input_html()'));
$results[] = p119c1_csrf_pass('detail no repeated erp_csrf_input calls', substr_count($detailSrc, 'erp_csrf_input(') === 0);
$results[] = p119c1_csrf_pass('detail reuses $csrfInputHtml in forms', substr_count($detailSrc, '$csrfInputHtml') >= 4);
$results[] = p119c1_csrf_pass('helper emits csrf token field', str_contains($helperSrc, 'erp_csrf_input(M360_RECEPTION_CSRF_PURPOSE)'));

$results[] = p119c1_csrf_pass('forms use POST', str_contains($detailSrc, 'method="post"'));
$results[] = p119c1_csrf_pass('forms include request_id', str_contains($detailSrc, 'name="request_id"'));
$results[] = p119c1_csrf_pass('accept POST only guard', str_contains($acceptSrc, "!== 'POST'"));
$results[] = p119c1_csrf_pass('accept validates csrf via helper', str_contains($acceptSrc, 'm360_reception_csrf_is_valid'));
$results[] = p119c1_csrf_pass('accept no erp_csrf_require_valid', !str_contains($acceptSrc, 'erp_csrf_require_valid'));
$results[] = p119c1_csrf_pass('accept no raw security failed string', !str_contains($acceptSrc, 'ERP security validation failed'));
$results[] = p119c1_csrf_pass('helper csrf error page title', str_contains($helperSrc, 'اعتبار امنیتی درخواست نامعتبر یا منقضی شده است'));
$results[] = p119c1_csrf_pass('helper convert gate title', str_contains($helperSrc, 'تبدیل به کارت کار نیازمند تکمیل پیش‌نیاز است'));
$results[] = p119c1_csrf_pass('helper convert gate text', str_contains($helperSrc, 'وضعیت تأیید مشتری و اطلاعات پذیرش تکمیل شود'));
$results[] = p119c1_csrf_pass('accept convert gate redirect', str_contains($acceptSrc, 'gate=convert'));
$results[] = p119c1_csrf_pass('detail convert gate panel', str_contains($detailSrc, 'gate=convert') || str_contains($detailSrc, "gatePanel === 'convert'"));
$results[] = p119c1_csrf_pass('actions: under_review', str_contains($detailSrc, 'value="under_review"'));
$results[] = p119c1_csrf_pass('actions: accept', str_contains($detailSrc, 'value="accept"'));
$results[] = p119c1_csrf_pass('actions: reject', str_contains($detailSrc, 'value="reject"'));
$results[] = p119c1_csrf_pass('actions: convert_to_jobcard', str_contains($detailSrc, 'value="convert_to_jobcard"'));
$results[] = p119c1_csrf_pass('accept no OTP bypass', !preg_match('/otp.*bypass|fake.*otp|skip.*otp/i', $acceptSrc . $helperSrc));

$pass = 0;
$fail = 0;
echo "# P11.9-C-1 Reception CSRF Actions Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
