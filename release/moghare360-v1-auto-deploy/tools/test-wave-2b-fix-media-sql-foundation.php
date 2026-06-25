<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — WAVE 2B-FIX Media SQL Foundation Static CLI Test
 *
 * Static file inspection only — does not execute SQL or connect to database.
 */

$root = dirname(__DIR__);
$sqlPath = $root . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'wave_2b_fix_jobcard_media_metadata.sql';

$docs = [
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_2_camera_media' . DIRECTORY_SEPARATOR . 'WAVE_2B_FIX_MEDIA_SQL_FOUNDATION_SCOPE.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_2_camera_media' . DIRECTORY_SEPARATOR . 'WAVE_2B_FIX_MEDIA_SQL_FOUNDATION_TEST_PLAN.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_2_camera_media' . DIRECTORY_SEPARATOR . 'WAVE_2B_FIX_MEDIA_SQL_FOUNDATION_RESULT.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_2_camera_media' . DIRECTORY_SEPARATOR . 'WAVE_2B_FIX_MEDIA_SQL_FOUNDATION_SIGNOFF.md',
];

$sqlContent = is_file($sqlPath) ? (string)file_get_contents($sqlPath) : '';
$sqlUpper = strtoupper($sqlContent);
$sqlWithoutComments = preg_replace('/--.*$/m', '', preg_replace('/\/\*.*?\*\//s', '', $sqlContent) ?? $sqlContent) ?? $sqlContent;

$results = [];

$results[] = ['name' => 'SQL file exists', 'pass' => is_file($sqlPath)];
$results[] = ['name' => 'SQL contains dbo.erp_jobcard_media', 'pass' => str_contains($sqlContent, 'dbo.erp_jobcard_media')];
$results[] = ['name' => 'SQL contains dbo.erp_jobcard_media_history', 'pass' => str_contains($sqlContent, 'dbo.erp_jobcard_media_history')];
$results[] = ['name' => 'SQL contains FK_erp_jobcard_media_jobcard', 'pass' => str_contains($sqlContent, 'FK_erp_jobcard_media_jobcard')];
$results[] = [
    'name' => 'SQL contains CHECK constraints for media_stage and media_type',
    'pass' => str_contains($sqlContent, 'CK_erp_jobcard_media_stage')
        && str_contains($sqlContent, 'CK_erp_jobcard_media_type'),
];
$results[] = ['name' => 'SQL contains CAMERA_ONLY', 'pass' => str_contains($sqlContent, 'CAMERA_ONLY')];
$results[] = ['name' => 'SQL contains BROWSER_CAMERA', 'pass' => str_contains($sqlContent, 'BROWSER_CAMERA')];
$results[] = [
    'name' => 'SQL contains no public URL / external URL field activation',
    'pass' => !preg_match('/\b(public_url|external_url|cdn_url|domain_url)\b/i', $sqlContent)
        && str_contains($sqlContent, "relative_path NOT LIKE N'http://%'")
        && str_contains($sqlContent, "relative_path NOT LIKE N'https://%'"),
];
$results[] = [
    'name' => 'SQL contains no DROP TABLE',
    'pass' => !preg_match('/\bDROP\s+TABLE\b/i', $sqlWithoutComments),
];
$results[] = [
    'name' => 'SQL contains no ALTER DATABASE',
    'pass' => !preg_match('/\bALTER\s+DATABASE\b/i', $sqlUpper),
];
$results[] = [
    'name' => 'SQL contains no auth/config changes',
    'pass' => !preg_match('/\b(staff_users|erp_auth|access_control|config\.php|permission)\b/i', $sqlContent),
];
$results[] = [
    'name' => 'SQL contains jobcard_id INT NOT NULL',
    'pass' => str_contains($sqlContent, 'jobcard_id INT NOT NULL'),
];
$results[] = [
    'name' => 'SQL does not contain jobcard_id BIGINT NOT NULL',
    'pass' => !str_contains($sqlContent, 'jobcard_id BIGINT NOT NULL'),
];
$results[] = [
    'name' => 'SQL contains sys.columns / sys.types preflight check',
    'pass' => str_contains($sqlContent, 'sys.columns')
        && str_contains($sqlContent, 'sys.types')
        && str_contains($sqlContent, "c.name = N'jobcard_id'"),
];
$results[] = [
    'name' => 'SQL contains THROW for jobcard_id type mismatch',
    'pass' => str_contains($sqlContent, 'jobcard_id must be INT for FK compatibility'),
];
$results[] = [
    'name' => 'SQL contains safe erp_jobcards prerequisite guard',
    'pass' => str_contains($sqlContent, "OBJECT_ID(N'dbo.erp_jobcards', N'U') IS NULL")
        && str_contains($sqlContent, 'WAVE 2B-FIX stopped safely'),
];
$results[] = [
    'name' => 'SQL contains readiness status marker',
    'pass' => str_contains($sqlContent, 'WAVE_2B_FIX_MEDIA_SQL_FOUNDATION_READY'),
];

foreach ($docs as $docPath) {
    $results[] = ['name' => 'Doc exists: ' . basename($docPath), 'pass' => is_file($docPath)];
}

$passed = 0;
$failed = 0;

foreach ($results as $row) {
    $label = $row['pass'] ? 'PASS' : 'FAIL';
    echo $label . ' — ' . $row['name'] . PHP_EOL;
    if ($row['pass']) {
        $passed++;
    } else {
        $failed++;
    }
}

echo PHP_EOL;
echo 'Passed: ' . $passed . ' / ' . count($results) . PHP_EOL;

if ($failed > 0) {
    fwrite(STDERR, 'WAVE 2B-FIX MEDIA SQL FOUNDATION TEST FAILED' . PHP_EOL);
    exit(1);
}

echo 'WAVE 2B-FIX MEDIA SQL FOUNDATION TEST PASSED' . PHP_EOL;
exit(0);
