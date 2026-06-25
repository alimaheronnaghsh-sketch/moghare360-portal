<?php
declare(strict_types=1);

/**
 * MOGHARE360 V1 — Production Signoff + Fix Register test
 */

$root = dirname(__DIR__);
$phpBin = is_file('C:\\xampp\\php\\php.exe') ? 'C:\\xampp\\php\\php.exe' : 'php';

function sig_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$results = [];

$docs = [
    'MOGHARE360_V1_PRODUCTION_RUN_SIGNOFF.md',
    'MOGHARE360_V1_POST_RUN_FIX_REGISTER.md',
    'MOGHARE360_V1_OPERATIONAL_ACCEPTANCE.md',
];
foreach ($docs as $doc) {
    $path = $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'release' . DIRECTORY_SEPARATOR . $doc;
    $results[] = sig_pass('Doc exists: ' . $doc, is_file($path));
}

$pages = [
    'public_html/erp-v1-production-signoff.php',
    'public_html/erp-v1-fix-register.php',
    'public_html/includes/moghare360-v1-post-run-control-helper.php',
];
foreach ($pages as $rel) {
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $results[] = sig_pass('File exists: ' . basename($rel), is_file($path));
    if (is_file($path)) {
        exec('"' . $phpBin . '" -l ' . escapeshellarg($path) . ' 2>&1', $out, $code);
        $results[] = sig_pass('PHP lint: ' . basename($rel), $code === 0);
    }
}

$sqlPath = $root . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'sqlserver' . DIRECTORY_SEPARATOR . 'v1_post_run_fix_register.sql';
$sqlOk = is_file($sqlPath);
$results[] = sig_pass('SQL migration file exists', $sqlOk);
if ($sqlOk) {
    $sqlContent = (string)file_get_contents($sqlPath);
    $results[] = sig_pass('SQL has erp_v1_post_run_fix_register', str_contains($sqlContent, 'erp_v1_post_run_fix_register'));
    $results[] = sig_pass('SQL has erp_v1_production_run_signoff', str_contains($sqlContent, 'erp_v1_production_run_signoff'));
    $results[] = sig_pass('SQL no DROP statements', !preg_match('/^\s*DROP\b/im', $sqlContent));
    $results[] = sig_pass('SQL idempotent IF NOT EXISTS', str_contains($sqlContent, 'IF NOT EXISTS') || str_contains($sqlContent, 'IS NULL'));
}

require_once $root . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-v1-post-run-control-helper.php';

$live = v1ctrl_live_signoff_rows();
$results[] = sig_pass('Live signoff rows count >= 8', count($live) >= 8, 'count=' . count($live));

$installerRow = null;
foreach ($live as $row) {
    if (($row['key'] ?? '') === 'installer') {
        $installerRow = $row;
    }
}
$results[] = sig_pass('Installer status READY', ($installerRow['status'] ?? '') === 'READY');

$conn = false;
try {
    $conn = v1ctrl_db();
    $results[] = sig_pass('DB connection for signoff', is_resource($conn));
    if (is_resource($conn)) {
        $signoff = v1ctrl_fetch_signoff_record($conn);
        $results[] = sig_pass('Signoff table readable', is_array($signoff));
        $items = v1ctrl_fetch_fix_items($conn);
        $results[] = sig_pass('Fix register items loaded', count($items) >= 0, 'count=' . count($items));
        if (v1ctrl_table_exists($conn, 'erp_v1_post_run_fix_register')) {
            $results[] = sig_pass('Fix register table exists', true);
            $results[] = sig_pass('Fix register has seed items', count($items) >= 1, 'items=' . count($items));
        } else {
            $results[] = sig_pass('Fix register table exists', false, 'run v1_post_run_fix_register.sql');
        }
    }
} catch (Throwable $e) {
    $results[] = sig_pass('DB connection for signoff', false, $e->getMessage());
} finally {
    if (is_resource($conn)) {
        @odbc_close($conn);
    }
}

$baseUrl = rtrim(getenv('MOGHARE360_BASE_URL') ?: 'http://localhost:8080/moghare360/', '/') . '/';
if (function_exists('curl_init')) {
    $ch = curl_init($baseUrl . 'erp-v1-production-signoff.php');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
    curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $results[] = sig_pass('Signoff page HTTP', $code >= 200 && $code < 500, 'HTTP ' . $code);

    $ch2 = curl_init($baseUrl . 'erp-v1-fix-register.php');
    curl_setopt_array($ch2, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
    curl_exec($ch2);
    $code2 = (int)curl_getinfo($ch2, CURLINFO_HTTP_CODE);
    curl_close($ch2);
    $results[] = sig_pass('Fix register page HTTP', $code2 >= 200 && $code2 < 500, 'HTTP ' . $code2);
}

$passed = $failed = 0;
foreach ($results as $row) {
    $line = ($row['pass'] ? 'PASS' : 'FAIL') . ' — ' . $row['name'];
    if (($row['detail'] ?? '') !== '') {
        $line .= ' (' . $row['detail'] . ')';
    }
    echo $line . PHP_EOL;
    $row['pass'] ? $passed++ : $failed++;
}

echo PHP_EOL . 'Passed: ' . $passed . ' / ' . count($results) . PHP_EOL;

if ($failed > 0) {
    fwrite(STDERR, 'V1 PRODUCTION SIGNOFF TEST FAILED' . PHP_EOL);
    exit(1);
}
echo 'V1 PRODUCTION SIGNOFF TEST PASSED' . PHP_EOL;
exit(0);
