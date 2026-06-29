<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pub = $root . '/public_html';
require_once $pub . '/includes/m360-operational-shell-helper.php';

function p118ba_strip_pass(string $n, bool $ok, string $d = ''): array
{
    return ['name' => $n, 'pass' => $ok, 'detail' => $d];
}

$results = [];

$emptyJobcard = ['jobcard_id' => 0, 'customer_name' => ''];
$stripEmpty = m360_operational_shell_build_jobcard_strip(false, $emptyJobcard, 'reception', '', []);
$results[] = p118ba_strip_pass('missing requester shows ثبت نشده', ($stripEmpty['requester_fa'] ?? '') === M360_OPS_SHELL_MISSING_FA);
$results[] = p118ba_strip_pass('missing creator shows ثبت نشده', ($stripEmpty['creator_fa'] ?? '') === M360_OPS_SHELL_MISSING_FA);
$results[] = p118ba_strip_pass('missing responsible shows ثبت نشده', ($stripEmpty['responsible_fa'] ?? '') === M360_OPS_SHELL_MISSING_FA);

$partial = [
    'jobcard_id' => 42,
    'customer_name' => 'علی رضایی',
    'created_by_user_id' => 0,
    'assigned_reception_user_id' => 0,
];
$stripPartial = m360_operational_shell_build_jobcard_strip(false, $partial, 'reception', 'VEHICLE_ARRIVED', []);
$results[] = p118ba_strip_pass('requester from customer name', ($stripPartial['requester_fa'] ?? '') === 'علی رضایی');
$results[] = p118ba_strip_pass('does not invent creator', ($stripPartial['creator_fa'] ?? '') === M360_OPS_SHELL_MISSING_FA);

ob_start();
m360_operational_shell_render_responsibility_strip($stripPartial);
$html = (string)ob_get_clean();
$results[] = p118ba_strip_pass('strip renders درخواست‌کننده label', str_contains($html, 'درخواست‌کننده'));
$results[] = p118ba_strip_pass('strip renders اقدام بعدی', str_contains($html, 'اقدام بعدی'));
$results[] = p118ba_strip_pass('strip no raw erp- filename', !preg_match('/>\s*erp-[a-z0-9-]+\.php\s*</', $html));
$results[] = p118ba_strip_pass('strip no submit button', !str_contains($html, 'type="submit"'));

$next = m360_operational_shell_next_action_label('qc', 'REWORK_REQUIRED', []);
$results[] = p118ba_strip_pass('next action read-only label qc rework', str_contains($next, 'بازکاری') || str_contains($next, 'نامشخص'));

$detailSrc = (string)file_get_contents($pub . '/erp-qc-detail.php');
$results[] = p118ba_strip_pass('qc detail builds strip', str_contains($detailSrc, 'm360_operational_shell_build_jobcard_strip'));

$pass = 0;
$fail = 0;
echo "# P11.8-B-A Responsibility Strip Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
