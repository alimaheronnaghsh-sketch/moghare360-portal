<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pub = $root . '/public_html';
require_once $pub . '/includes/m360-staff-home-helper.php';

function p117e_pass(string $n, bool $ok, string $d = ''): array
{
    return ['name' => $n, 'pass' => $ok, 'detail' => $d];
}

$results = [];
$helper = is_file($pub . '/includes/m360-staff-home-helper.php')
    ? (string)file_get_contents($pub . '/includes/m360-staff-home-helper.php')
    : '';

$results[] = p117e_pass('m360_staff_home_text_from_odbc exists', function_exists('m360_staff_home_text_from_odbc'));
$results[] = p117e_pass('m360_staff_home_h_db exists', function_exists('m360_staff_home_h_db'));
$results[] = p117e_pass('load_context applies text_from_odbc to dept/position', str_contains($helper, 'm360_staff_home_text_from_odbc((string)($profileRows[0][\'dept_name\']'));
$results[] = p117e_pass('no access-management-helper dependency', !str_contains($helper, 'm360-access-management-helper.php'));

$persianUtf8 = 'واحد پذیرش';
$results[] = p117e_pass('valid UTF-8 Persian preserved', m360_staff_home_text_from_odbc($persianUtf8) === $persianUtf8);
$results[] = p117e_pass('double normalize idempotent', m360_staff_home_text_from_odbc(m360_staff_home_text_from_odbc($persianUtf8)) === $persianUtf8);

// CP1256 cannot encode Persian Yeh (U+06CC); use a round-trippable department label.
$cp1256Source = 'واحد خدمات';
$cp1256 = @iconv('UTF-8', 'Windows-1256//IGNORE', $cp1256Source);
if (is_string($cp1256) && $cp1256 !== '') {
    $recovered = m360_staff_home_text_from_odbc($cp1256);
    $results[] = p117e_pass('CP1256 ODBC bytes recover to Persian', $recovered === $cp1256Source);
    $results[] = p117e_pass('recovered text not question marks', !preg_match('/^\?+$/', $recovered));
} else {
    $results[] = p117e_pass('CP1256 ODBC bytes recover to Persian', false, 'iconv unavailable');
    $results[] = p117e_pass('recovered text not question marks', false, 'iconv unavailable');
}

$results[] = p117e_pass('English role code unchanged', m360_staff_home_h('SERVICE_MANAGER') === 'SERVICE_MANAGER');
$results[] = p117e_pass('username ASCII unchanged', m360_staff_home_h_db('reception01') === 'reception01');

$escaped = m360_staff_home_h_db('<script>"\'</script>');
$results[] = p117e_pass('HTML escaping safe', str_contains($escaped, '&lt;script&gt;') && !str_contains($escaped, '<script>'));

$empty = m360_staff_home_text_from_odbc('');
$results[] = p117e_pass('empty stays empty not fake Persian', $empty === '');

$matrix = m360_staff_home_role_routes('RECEPTION');
$results[] = p117e_pass('workbench matrix still valid', is_array($matrix['workbench_groups'] ?? null) && ($matrix['allowed_routes'] ?? []) !== []);

$results[] = p117e_pass('no Auth login logic in helper', !preg_match('/password_verify\s*\(/', $helper));
$results[] = p117e_pass('no ALTER TABLE in helper', !preg_match('/\bALTER\s+TABLE\b/i', $helper));
$results[] = p117e_pass('staff home page uses h_db for identity', str_contains((string)file_get_contents($pub . '/erp-staff-home.php'), 'm360_staff_home_h_db'));

$pass = 0;
$fail = 0;
echo "# P11.7 Staff Home Persian Encoding Test\n\n";
foreach ($results as $r) {
    $line = ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'];
    if ($r['detail'] !== '') {
        $line .= ' — ' . $r['detail'];
    }
    echo $line . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
