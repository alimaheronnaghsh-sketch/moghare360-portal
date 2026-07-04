<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$phpBin = is_file('C:\\xampp\\php\\php.exe') ? 'C:\\xampp\\php\\php.exe' : 'php';
require_once $root . '/public_html/includes/m360-reception-workbench-helper.php';

function fixb_uat_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$results = [];
$intakeSrc = (string)file_get_contents($root . '/public_html/erp-reception-intake-file.php');

$checks = [
    'mobile correction section' => str_contains($intakeSrc, 'شماره موبایل و تأیید مشتری'),
    'structured plate component' => str_contains($intakeSrc, 'm360_rw_intake_render_plate_widget'),
    'camera/photo section' => str_contains($intakeSrc, 'عکس‌های پذیرش خودرو') || str_contains($intakeSrc, 'section-camera-photo'),
    'diagnostic PDF section' => str_contains($intakeSrc, 'فایل دیاگ اولیه'),
    'contract section' => str_contains($intakeSrc, 'قرارداد پذیرش و تأیید مشتری'),
    'referral/team section' => str_contains($intakeSrc, 'ارجاع کارشناسی / تیم مسئول'),
    'long business text not main visible' => !preg_match('/m360-rw-muted">\s*<\?= m360_rw_h\(M360_RW_SERVICE_CLASS_BUSINESS_PURPOSE_FA\)/', $intakeSrc),
    'concise service classification UI' => str_contains($intakeSrc, 'دسته‌بندی داخلی پذیرش'),
    'intake JS loaded' => str_contains($intakeSrc, 'm360-reception-intake.js'),
    'section lock helper used' => str_contains($intakeSrc, 'm360_rw_intake_section_ui_state'),
];

foreach ($checks as $name => $ok) {
    $results[] = fixb_uat_pass($name, $ok);
}

$childTests = [
    'test-p11-9-c-2c-fix-b-plate-ui.php',
    'test-p11-9-c-2c-fix-b-otp-mobile.php',
    'test-p11-9-c-2c-fix-b-section-lock.php',
    'test-p11-9-c-2c-fix-b-contract-diagnostic-camera.php',
    'test-p11-9-c-2c-fix-b-referral-team.php',
    'test-p11-9-c-2c-fix-b-scope-security.php',
];
foreach ($childTests as $test) {
    exec('"' . $phpBin . '" ' . escapeshellarg($root . '/tools/' . $test) . ' 2>&1', $out, $code);
    $results[] = fixb_uat_pass($test, $code === 0, $code !== 0 ? implode("\n", array_slice($out, -3)) : '');
}

$pass = 0;
$fail = 0;
echo "# P11.9-C-2C-FIX-B Operational UAT Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
