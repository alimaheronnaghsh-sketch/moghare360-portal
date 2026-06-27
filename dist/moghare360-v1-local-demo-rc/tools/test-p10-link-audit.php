<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$phpBin = is_file('C:\\xampp\\php\\php.exe') ? 'C:\\xampp\\php\\php.exe' : 'php';
$public = $root . '/public_html';

function p10l_pass(string $n, bool $ok, string $d = ''): array { return ['name' => $n, 'pass' => $ok, 'detail' => $d]; }
function p10l_read(string $p): string { return is_file($p) ? (string)file_get_contents($p) : ''; }
function p10l_lint(string $rel): array {
    global $phpBin, $public;
    $path = $public . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $out = []; $code = 1;
    exec('"' . $phpBin . '" -l ' . escapeshellarg($path) . ' 2>&1', $out, $code);
    return p10l_pass('PHP lint: ' . $rel, $code === 0, implode(' ', $out));
}

$linkAudit = $public . '/erp-link-audit.php';
$helper = $public . '/includes/m360-route-audit-helper.php';
$linkContent = p10l_read($linkAudit);
$helperContent = p10l_read($helper);
$blob = $linkContent . $helperContent;

$results = [];
$results[] = p10l_pass('erp-link-audit.php exists', is_file($linkAudit));
$results[] = p10l_lint('erp-link-audit.php');
$results[] = p10l_lint('includes/m360-route-audit-helper.php');
$results[] = p10l_pass('Uses m360_route_audit_summary', str_contains($linkContent, 'm360_route_audit_summary'));
$results[] = p10l_pass('Uses m360_nav_file_exists', str_contains($helperContent, 'm360_nav_file_exists'));
$results[] = p10l_pass('Audit uses file_exists only', str_contains($helperContent, 'm360_nav_file_exists') && !preg_match('/\bcurl_init\b/i', $helperContent));
$results[] = p10l_pass('No curl in link audit', !preg_match('/\bcurl_(init|exec|_setopt)\b/i', $blob));
$results[] = p10l_pass('No file_get_contents http', !preg_match('/file_get_contents\s*\(\s*[\'"]https?:/i', $blob));
$results[] = p10l_pass('No POST form on link audit page', !preg_match('/method\s*=\s*[\'"]post[\'"]/i', $linkContent));
$results[] = p10l_pass('No POST execution handler', !preg_match('/\$_SERVER\s*\[\s*[\'"]REQUEST_METHOD[\'"]\s*\]\s*===?\s*[\'"]POST[\'"]/i', $blob));
$results[] = p10l_pass('Staff gate on link audit', str_contains($linkContent, 'm360_release_hardening_require_staff'));
$results[] = p10l_pass('Shows file_exists note', str_contains($linkContent, 'file_exists') || str_contains($linkContent, 'Read-only'));
$results[] = p10l_pass('Lists required docs', str_contains($linkContent, 'm360_release_required_docs'));

require_once $helper;
$summary = m360_route_audit_summary();
$results[] = p10l_pass('Audit summary has total', isset($summary['total']) && (int)$summary['total'] > 0, 'total=' . ($summary['total'] ?? 0));
$results[] = p10l_pass('Audit rows use file_exists flag', isset($summary['rows'][0]['file_exists']));

$pass = 0; $fail = 0;
echo "# P10 Link Audit Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
