<?php
declare(strict_types=1);

$root = dirname(__DIR__);

function p119a_sql_pass(string $name, bool $ok, string $detail = ''): array
{
    return ['name' => $name, 'pass' => $ok, 'detail' => $detail];
}

$results = [];
$preflight = $root . '/database/dry-run/P11_9_A_READONLY_PREFLIGHT_CHECK.sql';
$seed = $root . '/database/dry-run/P11_9_A_M360_DEMO_JOBCARD_SEED_TEMPLATE.sql';
$notGenerated = $root . '/database/dry-run/P11_9_A_M360_DEMO_JOBCARD_SEED_TEMPLATE_NOT_GENERATED.md';

$results[] = p119a_sql_pass('preflight file exists', is_file($preflight));

$preflightSql = is_file($preflight) ? (string)file_get_contents($preflight) : '';
$results[] = p119a_sql_pass('preflight USE MOGHARE360_ERP', str_contains($preflightSql, 'USE MOGHARE360_ERP'));

$forbidden = ['INSERT', 'UPDATE', 'DELETE', 'MERGE', 'CREATE TABLE', 'ALTER TABLE', 'DROP ', 'TRUNCATE'];
foreach ($forbidden as $kw) {
    $found = stripos($preflightSql, $kw) !== false;
    $results[] = p119a_sql_pass('preflight no ' . trim($kw), !$found);
}

$results[] = p119a_sql_pass('preflight checks M360-DEMO', str_contains($preflightSql, 'M360-DEMO'));
$results[] = p119a_sql_pass('preflight marks JobCard 1 not recommended', str_contains($preflightSql, 'NOT RECOMMENDED'));

if (is_file($seed)) {
    $seedSql = (string)file_get_contents($seed);
    $results[] = p119a_sql_pass('seed template exists', true);
    $results[] = p119a_sql_pass('seed has confirmation guard', str_contains($seedSql, '@CONFIRM_CREATE_M360_DEMO') && str_contains($seedSql, 'CREATE_M360_DEMO'));
    $results[] = p119a_sql_pass('seed requires OPERATOR_USER_ID', str_contains($seedSql, '@OPERATOR_USER_ID'));
    $results[] = p119a_sql_pass('seed duplicate prevention', str_contains($seedSql, 'already exists'));
    $results[] = p119a_sql_pass('seed no staff user insert', !preg_match('/INSERT\s+INTO\s+dbo\.core_users/i', $seedSql));
    $results[] = p119a_sql_pass('seed no permission insert', !preg_match('/INSERT\s+INTO\s+dbo\.(core_roles|core_permissions|erp_company_users)/i', $seedSql));
    $results[] = p119a_sql_pass('seed uses M360-DEMO-001', str_contains($seedSql, 'M360-DEMO-001'));
    $results[] = p119a_sql_pass('NOT_GENERATED md absent when seed present', !is_file($notGenerated));
} else {
    $results[] = p119a_sql_pass('seed or NOT_GENERATED doc', is_file($notGenerated), is_file($notGenerated) ? 'NOT_GENERATED explains absence' : 'missing both');
}

$pass = 0;
$fail = 0;
echo "# P11.9-A SQL Safety Test\n\n";
foreach ($results as $r) {
    echo ($r['pass'] ? '[PASS]' : '[FAIL]') . ' ' . $r['name'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\nTotal: " . count($results) . " | PASS: $pass | FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);
