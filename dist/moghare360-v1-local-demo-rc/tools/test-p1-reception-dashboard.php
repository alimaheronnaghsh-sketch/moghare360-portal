<?php
declare(strict_types=1);

/**
 * MOGHARE360 P1 — Reception dashboard tests.
 */

$root = dirname(__DIR__);
$phpBin = is_file('C:\\xampp\\php\\php.exe') ? 'C:\\xampp\\php\\php.exe' : 'php';
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

function p1d_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

function p1d_read(string $path): string
{
    return is_file($path) ? (string)file_get_contents($path) : '';
}

function p1d_lint(string $rel): array
{
    global $phpBin, $public;
    $path = $public . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (!is_file($path)) {
        return p1d_pass('PHP lint: ' . $rel, false, 'missing');
    }
    $out = [];
    $code = 1;
    exec('"' . $phpBin . '" -l ' . escapeshellarg($path) . ' 2>&1', $out, $code);
    return p1d_pass('PHP lint: ' . $rel, $code === 0, implode(' ', $out));
}

$listPage = p1d_read($public . DIRECTORY_SEPARATOR . 'erp-reception-online-requests.php');
$detailPage = p1d_read($public . DIRECTORY_SEPARATOR . 'erp-reception-online-request-detail.php');
$acceptPage = p1d_read($public . DIRECTORY_SEPARATOR . 'erp-reception-online-request-accept.php');
$receptionHelper = p1d_read($public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-reception-helper.php');

$results = [];

$results[] = p1d_pass('List page exists', is_file($public . DIRECTORY_SEPARATOR . 'erp-reception-online-requests.php'));
$results[] = p1d_pass('Detail page exists', is_file($public . DIRECTORY_SEPARATOR . 'erp-reception-online-request-detail.php'));
$results[] = p1d_pass('Accept page exists', is_file($public . DIRECTORY_SEPARATOR . 'erp-reception-online-request-accept.php'));
$results[] = p1d_pass('Reception helper exists', is_file($public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-reception-helper.php'));
$results[] = p1d_pass('List uses prepared query helper', str_contains($receptionHelper, 'm360_reception_list_requests') && str_contains($receptionHelper, 'odbc_prepare'));
$results[] = p1d_pass('Staff auth required', str_contains($receptionHelper, 'm360_reception_require_staff') && str_contains($receptionHelper, 'erp_auth_current_user_id'));
$results[] = p1d_pass('Accept is POST only', str_contains($acceptPage, "!== 'POST'") && str_contains($acceptPage, 'Location: erp-reception-online-requests.php'));
$results[] = p1d_pass('Detail actions POST to accept', str_contains($detailPage, 'method="post"') && str_contains($detailPage, 'erp-reception-online-request-accept.php'));
$results[] = p1d_pass('GET detail does not mutate state', !preg_match('/\$_GET[\s\S]*UPDATE\s+dbo/i', $detailPage));
$results[] = p1d_pass('CSRF on reception actions', str_contains($detailPage, 'erp_csrf_input') && str_contains($acceptPage, 'erp_csrf_require_valid'));
$results[] = p1d_pass('Persian RTL list page', str_contains($listPage, 'lang="fa"') && str_contains($listPage, 'dir="rtl"'));
$results[] = p1d_pass('Persian RTL detail page', str_contains($detailPage, 'lang="fa"') && str_contains($detailPage, 'dir="rtl"'));
$results[] = p1d_pass('Status filter on list', str_contains($listPage, 'm360_online_req_filter_statuses'));
$results[] = p1d_pass('No raw credentials in reception pages', !preg_match('/password\s*=\s*[\'"][^\'"]{6,}/i', $listPage . $detailPage . $acceptPage));
$results[] = p1d_pass('Reception does not redefine auth core', !preg_match('/function\s+erp_auth_/', $receptionHelper));
$results[] = p1d_lint('erp-reception-online-requests.php');
$results[] = p1d_lint('erp-reception-online-request-detail.php');
$results[] = p1d_lint('erp-reception-online-request-accept.php');
$results[] = p1d_lint('includes/m360-reception-helper.php');

$pass = 0;
$fail = 0;
echo "# MOGHARE360 P1 Reception Dashboard Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'];
    if ($r['detail'] !== '') {
        echo ' — ' . $r['detail'];
    }
    echo "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
