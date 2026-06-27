<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pub = $root . '/public_html';
$userHelper = is_file($pub . '/includes/m360-access-user-helper.php') ? (string)file_get_contents($pub . '/includes/m360-access-user-helper.php') : '';
$page = is_file($pub . '/erp-access-password-reset.php') ? (string)file_get_contents($pub . '/erp-access-password-reset.php') : '';
$audit = is_file($pub . '/includes/m360-access-audit-helper.php') ? (string)file_get_contents($pub . '/includes/m360-access-audit-helper.php') : '';

function p114pw_pass(string $n, bool $ok): array { return ['name' => $n, 'pass' => $ok]; }

$results = [
    p114pw_pass('reset uses password_hash', str_contains($userHelper, 'm360_access_user_hash_password')),
    p114pw_pass('audit strips password fields', str_contains($audit, "unset(\$details['password']")),
    p114pw_pass('history event on reset', str_contains($userHelper, 'ACCESS_MGMT_PASSWORD_RESET')),
    p114pw_pass('page shows temp password once pattern', str_contains($page, 'فقط یک‌بار')),
    p114pw_pass('no hash in page output path', !preg_match('/password_hash.*echo|echo.*password_hash/i', $page . $userHelper)),
    p114pw_pass('no committed real password in repo', !preg_match('/\$2[ayb]\$\d{2}\$/', $page . $userHelper)),
];

$pass = 0; $fail = 0;
echo "# P11.4 Password Reset Safety Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
