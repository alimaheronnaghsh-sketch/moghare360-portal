<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$intake = $root . '/public_html/erp-reception-intake-file.php';
$helper = $root . '/public_html/includes/m360-reception-workbench-helper.php';
$staffHome = $root . '/public_html/erp-staff-home.php';
$list = $root . '/public_html/erp-reception-online-requests.php';
$detail = $root . '/public_html/erp-reception-online-request-detail.php';

function c2b_int_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$results = [];
$results[] = c2b_int_pass('erp-reception-intake-file.php exists', is_file($intake));

$content = is_file($intake) ? (string)file_get_contents($intake) : '';
$phrases = [
    'online_request_id',
    'پرونده پذیرش',
    'اطلاعات مشتری',
    'اطلاعات خودرو',
    'اطلاعات تکمیلی فرم آنلاین',
    'چک‌لیست تکمیل پرونده',
    'وضعیت تبدیل به کارت کار',
    'موارد ناقص',
    'VIN',
    'شاسی',
    'سطح سوخت',
    'آسیب',
    'دیاگ',
    'قرارداد',
    'توافق هزینه',
];
foreach ($phrases as $p) {
    $results[] = c2b_int_pass('intake shell contains: ' . $p, str_contains($content, $p));
}

$results[] = c2b_int_pass('gate status display', str_contains($content, 'gate') || str_contains($content, 'وضعیت تبدیل'));
$results[] = c2b_int_pass('no automatic convert on page load', !preg_match('/m360_reception_convert_to_jobcard\s*\(/', $content));
$results[] = c2b_int_pass('convert only via accept.php form', str_contains($content, 'erp-reception-online-request-accept.php'));
$results[] = c2b_int_pass('no fake OTP', !str_contains($content, 'otp_verified') || str_contains($content, 'm360_online_req_payload_otp_verified'));
$results[] = c2b_int_pass('staff-home links workbench', is_file($staffHome) && str_contains((string)file_get_contents($staffHome), 'erp-reception-workbench.php'));
$results[] = c2b_int_pass('online list links intake shell', is_file($list) && str_contains((string)file_get_contents($list), 'erp-reception-intake-file.php'));
$results[] = c2b_int_pass('online detail links intake shell', is_file($detail) && str_contains((string)file_get_contents($detail), 'erp-reception-intake-file.php'));

require_once $helper;
$gate = m360_rw_build_gate(
    ['customer_name' => 'Test', 'mobile' => '0912', 'vehicle_plate' => '12ب34567', 'request_status' => 'NEW', 'request_payload_json' => '{"otp_verified":1}'],
    ['otp_verified' => 1, 'service_note' => 'noise'],
    null,
    null,
    null,
    [],
    [],
    [],
    null
);
$results[] = c2b_int_pass('gate logic returns status', isset($gate['status'], $gate['missing'], $gate['checks']));
$gNoOtp = m360_rw_build_gate(
    ['customer_name' => 'T', 'mobile' => '09', 'vehicle_plate' => 'P', 'request_status' => 'NEW', 'request_payload_json' => '{}'],
    [],
    null, null, null, [], [], [], null
);
$results[] = c2b_int_pass('gate blocks without otp in logic', ($gNoOtp['status'] ?? '') === 'otp_required');

$gNoService = m360_rw_build_gate(
    ['customer_name' => 'T', 'mobile' => '0912', 'vehicle_plate' => '12ب34567', 'service_note' => 'x', 'request_status' => 'NEW', 'request_payload_json' => '{"otp_verified":1}'],
    ['otp_verified' => 1, 'service_note' => 'x'],
    null, null, null, [], [], [], null
);
$results[] = c2b_int_pass('missing service class blocks convert', ($gNoService['can_show_convert'] ?? true) === false);

$sqlMigrations = glob($root . '/database/migrations/*.sql') ?: [];
$recentSql = false;
foreach ($sqlMigrations as $sql) {
    if (filemtime($sql) > time() - 300) {
        $recentSql = true;
        break;
    }
}
$results[] = c2b_int_pass('no new SQL migration in phase', !$recentSql);

$pass = 0;
$fail = 0;
echo "# P11.9-C-2B Intake Completion Shell Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
