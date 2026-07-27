<?php
declare(strict_types=1);

$root = dirname(__DIR__);

function nav_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$layout = (string)file_get_contents($root . '/public_html/includes/mirror-layout.php');
$luxury = (string)file_get_contents($root . '/public_html/assets/css/moghare360-v1-luxury-ui.css');
$mirror = (string)file_get_contents($root . '/public_html/assets/css/mirror.css');
$js = (string)file_get_contents($root . '/public_html/assets/js/customer-form.js');
$customer = (string)file_get_contents($root . '/public_html/customer-request.php');
$staffLogin = (string)file_get_contents($root . '/public_html/staff-login.php');

$results = [];
$results[] = nav_pass('mirror layout PHP nav order customer before staff', strpos($layout, "'customer' => ['customer-request.php', 'مشتری']") < strpos($layout, "'staff' => ['staff-login.php', 'پرسنل']"));
$results[] = nav_pass('CSS nav order customer order 2', str_contains($luxury, '.m360-public-nav__link[href="customer-request.php"]') && str_contains($luxury, 'order: 2'));
$results[] = nav_pass('CSS nav order staff order 3', str_contains($luxury, '.m360-public-nav__link[href="staff-login.php"]') && str_contains($luxury, 'order: 3'));
$results[] = nav_pass('nav links excluded from busy CSS trap', str_contains($luxury, '.m360-public-nav__link.m360-btn-is-loading') && str_contains($luxury, 'pointer-events: auto'));
$results[] = nav_pass('busy spinner scoped to buttons only', str_contains($mirror, 'button.m360-btn-is-loading') && !preg_match('/^\.m360-btn-is-loading\s*\{/m', $mirror));
$results[] = nav_pass('JS blocks busy on nav links', str_contains($js, 'isBusyBlocked') && str_contains($js, 'data-m360-nav-link'));
$results[] = nav_pass('JS clears nav on pageshow', str_contains($js, 'clearNavLoadingState') && str_contains($js, "addEventListener('pageshow', clearAllBusyButtons"));
$results[] = nav_pass('JS visibilitychange cleanup', str_contains($js, 'visibilitychange'));
$results[] = nav_pass('customer form scoped init', str_contains($js, "querySelector('form.m360-customer-form')"));
$results[] = nav_pass('staff login does not load customer-form.js', !str_contains($staffLogin, 'customer-form.js'));
$results[] = nav_pass('AUTH_LOGIN_FILES_UNTOUCHED staff-auth', !is_file($root . '/public_html/staff-auth.php') || !str_contains($js, 'staff-auth'));
$results[] = nav_pass('customer page PR02B repair-4 marker', str_contains($customer, 'PR-02B-UAT-REPAIR-4'));

$failed = array_values(array_filter($results, static fn(array $r): bool => !$r['pass']));
echo "PR-02B navigation spinner regression: " . (count($failed) === 0 ? 'PASS' : 'FAIL') . "\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS] ' : '[FAIL] ') . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
}
exit(count($failed) === 0 ? 0 : 1);
