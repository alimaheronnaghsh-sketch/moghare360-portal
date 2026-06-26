<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$phpBin = is_file('C:\\xampp\\php\\php.exe') ? 'C:\\xampp\\php\\php.exe' : 'php';
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

function p15f_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p15f_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }
function p15f_lint(string $rel): array {
    global $phpBin, $public;
    $path = $public . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    exec('"' . $phpBin . '" -l ' . escapeshellarg($path) . ' 2>&1', $out, $code);
    return p15f_pass('PHP lint: ' . $rel, $code === 0, implode(' ', $out));
}

$tpl = p15f_read($public . '/includes/m360-contract-template-render.php');
$md = p15f_read($root . '/docs/legal/MOGHARE360_INTAKE_CONTRACT_V1.md');
$helper = p15f_read($public . '/includes/m360-intake-contract-helper.php');

$results = [];
$results[] = p15f_pass('contract-template render exists', is_file($public . '/contract-template-intake.php'));
$results[] = p15f_pass('Legal markdown exists', is_file($root . '/docs/legal/MOGHARE360_INTAKE_CONTRACT_V1.md'));
$results[] = p15f_pass('MOGHARE360-INTAKE-V1 in template', str_contains($tpl, 'MOGHARE360-INTAKE-V1'));
$results[] = p15f_pass('Article 1 in template', str_contains($tpl, 'ماده ۱'));
$results[] = p15f_pass('Article 18 in template', str_contains($tpl, 'ماده ۱۸'));
$results[] = p15f_pass('Online signature article', str_contains($tpl, 'امضای آنلاین'));
$results[] = p15f_pass('Prepayment phrase', str_contains($tpl, 'علی‌الحساب') || str_contains($tpl, 'علیالحساب'));
$results[] = p15f_pass('Purchase limit phrase', str_contains($tpl, 'سقف اختیار خرید'));
$results[] = p15f_pass('Test drive phrase', str_contains($tpl, 'تست رانندگی'));
$results[] = p15f_pass('Body insurance phrase', str_contains($tpl, 'بیمه بدنه'));
$results[] = p15f_pass('Checklist phrase', str_contains($tpl, 'چک‌لیست'));
$results[] = p15f_pass('IP phrase', str_contains($tpl, 'IP'));
$results[] = p15f_pass('Browser phrase', str_contains($tpl, 'مرورگر'));
$results[] = p15f_pass('Company name', str_contains($tpl, 'مجموعه خدمات فنی مهندسی مقاره موتورز'));
$results[] = p15f_pass('erp-intake-contracts.php', is_file($public . '/erp-intake-contracts.php'));
$results[] = p15f_pass('detail/generate/send pages', is_file($public . '/erp-intake-contract-detail.php') && is_file($public . '/erp-intake-contract-generate.php') && is_file($public . '/erp-intake-contract-send.php'));
$results[] = p15f_pass('customer contract pages', is_file($public . '/customer-intake-contract.php') && is_file($public . '/customer-intake-contract-sign.php'));
$results[] = p15f_pass('Token hash logic', str_contains($helper, 'secure_token_hash') && str_contains($helper, 'm360_intake_contract_hash'));
$results[] = p15f_pass('Status workflow constants', str_contains($helper, 'M360_CONTRACT_STATUS_SIGNED'));
$results[] = p15f_pass('Persian RTL customer page', str_contains(p15f_read($public . '/customer-intake-contract.php'), 'dir="rtl"'));
$results[] = p15f_lint('contract-template-intake.php');
$results[] = p15f_lint('includes/m360-contract-template-render.php');

$pass = 0; $fail = 0;
echo "# P1.5 Contract Flow Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
