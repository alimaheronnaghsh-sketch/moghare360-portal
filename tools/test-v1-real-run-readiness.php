<?php
declare(strict_types=1);

/**
 * MOGHARE360 V1 — Real production run readiness (templates, gitignore, lock regression).
 */

$root = dirname(__DIR__);
$phpBin = is_file('C:\\xampp\\php\\php.exe') ? 'C:\\xampp\\php\\php.exe' : 'php';

function rr_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

function rr_run_test_script(string $phpBin, string $path): array
{
    if (!is_file($path)) {
        return ['ok' => false, 'detail' => 'missing'];
    }
    $cmd = '"' . $phpBin . '" ' . escapeshellarg($path) . ' 2>&1';
    exec($cmd, $out, $code);
    return ['ok' => $code === 0, 'detail' => 'exit=' . $code];
}

$results = [];

$docs = [
    'docs/release/MOGHARE360_V1_REAL_RUN_PREPARATION.md',
    'docs/release/MOGHARE360_V1_PRODUCTION_USER_ACCESS_PLAN.md',
    'docs/release/MOGHARE360_V1_FIRST_REAL_CUSTOMER_RUN.md',
];
foreach ($docs as $rel) {
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $results[] = rr_pass('Doc: ' . basename($rel), is_file($path));
}

$templates = [
    'private/templates/production-users.template.json',
    'private/templates/production-site-config.template.json',
];
foreach ($templates as $rel) {
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $ok = is_file($path);
    $results[] = rr_pass('Template exists: ' . basename($rel), $ok);
    if ($ok) {
        $json = json_decode((string)file_get_contents($path), true);
        $results[] = rr_pass('Template valid JSON: ' . basename($rel), is_array($json));
    }
}

$usersTemplate = $root . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'production-users.template.json';
if (is_file($usersTemplate)) {
    $users = json_decode((string)file_get_contents($usersTemplate), true);
    $roles = is_array($users) ? ($users['allowed_role_codes'] ?? []) : [];
    $requiredRoles = ['OWNER', 'SYSTEM_ADMIN', 'RECEPTION', 'TECHNICIAN', 'INVENTORY', 'FINANCE', 'QC', 'CRM', 'COMPANY_OWNER_VIEWER'];
    foreach ($requiredRoles as $role) {
        $results[] = rr_pass('Template role covered: ' . $role, in_array($role, $roles, true));
    }
    $userRows = is_array($users) ? ($users['users'] ?? []) : [];
    $results[] = rr_pass('Template users are placeholders only', count($userRows) >= 9);
    foreach ($userRows as $row) {
        $secret = (string)($row['temporary_password_or_hash_placeholder'] ?? '');
        $results[] = rr_pass(
            'Template user has no real secret: ' . ($row['username'] ?? '?'),
            preg_match('/(?i)REPLACE_WITH|PLACEHOLDER|CHANGE_ME/', $secret) === 1
        );
    }
}

$siteTemplate = $root . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'production-site-config.template.json';
if (is_file($siteTemplate)) {
    $site = json_decode((string)file_get_contents($siteTemplate), true);
    $fields = ['domain', 'company_code', 'company_display_name', 'support_phone', 'reception_phone', 'address', 'working_hours', 'storage_path', 'ssl_expected', 'master_server_base_url', 'mirror_base_url'];
    foreach ($fields as $field) {
        $results[] = rr_pass('Site template field: ' . $field, is_array($site) && array_key_exists($field, $site));
    }
}

$gitignorePath = $root . DIRECTORY_SEPARATOR . '.gitignore';
$gitignore = is_file($gitignorePath) ? (string)file_get_contents($gitignorePath) : '';
foreach (['private/production-users.json', 'private/production-site-config.json', 'runtime/'] as $needle) {
    $results[] = rr_pass('.gitignore contains ' . $needle, str_contains($gitignore, $needle));
}

$scripts = [
    'tools/production/CREATE_PRODUCTION_USERS_FROM_PRIVATE_JSON.ps1',
    'tools/production/VERIFY_PRODUCTION_RUNTIME_CONFIG.ps1',
];
foreach ($scripts as $rel) {
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $results[] = rr_pass('Script exists: ' . basename($rel), is_file($path));
}

$importScript = $root . DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR . 'production' . DIRECTORY_SEPARATOR . 'CREATE_PRODUCTION_USERS_FROM_PRIVATE_JSON.ps1';
if (is_file($importScript)) {
    $importContent = (string)file_get_contents($importScript);
    $results[] = rr_pass('Import script reads private JSON only', str_contains($importContent, 'private\\production-users.json'));
    $results[] = rr_pass('Import script references template as guide', str_contains($importContent, 'production-users.template.json'));
    $results[] = rr_pass('Import script does not log password', str_contains($importContent, 'passwords are never written'));
}

$verifyScript = $root . DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR . 'production' . DIRECTORY_SEPARATOR . 'VERIFY_PRODUCTION_RUNTIME_CONFIG.ps1';
if (is_file($verifyScript)) {
    $verifyContent = (string)file_get_contents($verifyScript);
    $results[] = rr_pass('Verify script uses TEST customer marker', str_contains($verifyContent, 'TEST_V1_REAL_RUN_READINESS_DO_NOT_USE'));
}

$apiPath = $root . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'customer' . DIRECTORY_SEPARATOR . 'request.php';
$apiContent = is_file($apiPath) ? (string)file_get_contents($apiPath) : '';
$results[] = rr_pass('API customer request SQL Server target', str_contains($apiContent, 'mogh_tenant_db_connect') && str_contains($apiContent, 'erp_customer_online_requests'));
$results[] = rr_pass('API no legacy MySQL', !preg_match('/\b(mysqli_|mysql_connect|submit-customer\.php)\b/', $apiContent));

$legacyMysql = glob($root . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . '*.sql') ?: [];
$legacyActive = false;
foreach ($legacyMysql as $path) {
    if (str_contains((string)file_get_contents($path), 'CREATE TABLE IF NOT EXISTS')) {
        $legacyActive = true;
        break;
    }
}
$results[] = rr_pass('Legacy MySQL SQL not canonical path', $legacyActive, 'reference-only under public_html/sql');

$trackedSensitive = false;
$scanDetail = '';
if (is_dir($root . DIRECTORY_SEPARATOR . '.git')) {
    exec('git -C ' . escapeshellarg($root) . ' ls-files', $tracked);
    $denyPaths = ['private/production-users.json', 'private/production-site-config.json', 'private/erp-config.php', 'runtime/'];
    foreach ($denyPaths as $deny) {
        foreach ($tracked as $file) {
            if (str_replace('\\', '/', $file) === $deny || str_starts_with(str_replace('\\', '/', $file), rtrim($deny, '/') . '/')) {
                $trackedSensitive = true;
                $scanDetail = 'tracked:' . $file;
                break 2;
            }
        }
    }
    if (!$trackedSensitive) {
        foreach ($tracked as $file) {
            if (!str_starts_with($file, 'private/templates/')) {
                continue;
            }
            $full = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file);
            if (!is_file($full)) {
                continue;
            }
            $content = (string)file_get_contents($full);
            if (preg_match('/^\$2[ayb]\$.{50,}$/m', $content)) {
                $trackedSensitive = true;
                $scanDetail = 'bcrypt in template file: ' . $file;
                break;
            }
        }
    }
}
$results[] = rr_pass('No private credential files tracked in git', !$trackedSensitive, $scanDetail);

$canonical = rr_run_test_script($phpBin, $root . DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR . 'test-v1-canonical-database.php');
$results[] = rr_pass('Canonical database regression', $canonical['ok'], $canonical['detail']);

$signoff = rr_run_test_script($phpBin, $root . DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR . 'test-v1-production-signoff.php');
$results[] = rr_pass('Production signoff regression', $signoff['ok'], $signoff['detail']);

$failed = array_filter($results, static fn(array $r): bool => !$r['pass']);
$passed = count($results) - count($failed);

echo "MOGHARE360 V1 Real Run Readiness Test\n";
echo str_repeat('-', 60) . "\n";
foreach ($results as $r) {
    $mark = $r['pass'] ? 'PASS' : 'FAIL';
    $detail = $r['detail'] !== '' ? ' — ' . $r['detail'] : '';
    echo "[{$mark}] {$r['name']}{$detail}\n";
}
echo str_repeat('-', 60) . "\n";
echo "Result: {$passed}/" . count($results) . " PASS\n";
exit(count($failed) > 0 ? 1 : 0);
