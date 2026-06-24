<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — WAVE 2C-FIX Diagnostic PDF MIME Static CLI Test
 *
 * Static file inspection only — does not execute SQL or connect to database.
 */

$root = dirname(__DIR__);
$sqlPath = $root . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'wave_2c_fix_diagnostic_pdf_mime_constraint.sql';

$docs = [
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_2_camera_media' . DIRECTORY_SEPARATOR . 'WAVE_2C_FIX_DIAGNOSTIC_PDF_MIME_SCOPE.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_2_camera_media' . DIRECTORY_SEPARATOR . 'WAVE_2C_FIX_DIAGNOSTIC_PDF_MIME_TEST_PLAN.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_2_camera_media' . DIRECTORY_SEPARATOR . 'WAVE_2C_FIX_DIAGNOSTIC_PDF_MIME_RESULT.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_2_camera_media' . DIRECTORY_SEPARATOR . 'WAVE_2C_FIX_DIAGNOSTIC_PDF_MIME_SIGNOFF.md',
];

$sqlContent = is_file($sqlPath) ? (string)file_get_contents($sqlPath) : '';
$sqlWithoutComments = preg_replace('/--.*$/m', '', preg_replace('/\/\*.*?\*\//s', '', $sqlContent) ?? $sqlContent) ?? $sqlContent;

$results = [];

$results[] = ['name' => 'SQL file exists', 'pass' => is_file($sqlPath)];
$results[] = ['name' => 'SQL contains CK_erp_jobcard_media_mime', 'pass' => str_contains($sqlContent, 'CK_erp_jobcard_media_mime')];
$results[] = ['name' => 'SQL contains application/pdf', 'pass' => str_contains($sqlContent, 'application/pdf')];
$results[] = ['name' => 'SQL contains image/jpeg', 'pass' => str_contains($sqlContent, 'image/jpeg')];
$results[] = ['name' => 'SQL contains image/png', 'pass' => str_contains($sqlContent, 'image/png')];
$results[] = ['name' => 'SQL contains image/webp', 'pass' => str_contains($sqlContent, 'image/webp')];
$results[] = [
    'name' => 'SQL contains DROP CONSTRAINT CK_erp_jobcard_media_mime',
    'pass' => str_contains($sqlContent, 'DROP CONSTRAINT CK_erp_jobcard_media_mime'),
];
$results[] = [
    'name' => 'SQL contains ADD CONSTRAINT CK_erp_jobcard_media_mime',
    'pass' => preg_match('/ADD\s+CONSTRAINT\s+CK_erp_jobcard_media_mime/i', $sqlContent) === 1,
];
$results[] = [
    'name' => 'SQL contains safe OBJECT_ID check for dbo.erp_jobcard_media',
    'pass' => str_contains($sqlContent, "OBJECT_ID(N'dbo.erp_jobcard_media', N'U') IS NULL")
        && str_contains($sqlContent, 'WAVE 2C-FIX stopped safely'),
];
$results[] = [
    'name' => 'SQL contains mime_type column check',
    'pass' => str_contains($sqlContent, "name = N'mime_type'"),
];
$results[] = [
    'name' => 'SQL contains no DROP TABLE',
    'pass' => !preg_match('/\bDROP\s+TABLE\b/i', $sqlWithoutComments),
];
$results[] = [
    'name' => 'SQL contains no ALTER DATABASE',
    'pass' => !preg_match('/\bALTER\s+DATABASE\b/i', strtoupper($sqlWithoutComments)),
];
$results[] = [
    'name' => 'SQL does not alter source constraint',
    'pass' => !preg_match('/CK_erp_jobcard_media_source/i', $sqlContent),
];
$results[] = [
    'name' => 'SQL does not alter capture_method constraint',
    'pass' => !preg_match('/CK_erp_jobcard_media_capture_method/i', $sqlContent),
];
$results[] = [
    'name' => 'SQL does not enable external URL',
    'pass' => !preg_match('/\b(public_url|external_url|https?:\/\/)\b/i', $sqlContent),
];
$results[] = [
    'name' => 'SQL contains readiness status marker',
    'pass' => str_contains($sqlContent, 'WAVE_2C_FIX_DIAGNOSTIC_PDF_MIME_READY'),
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
    fwrite(STDERR, 'WAVE 2C-FIX DIAGNOSTIC PDF MIME TEST FAILED' . PHP_EOL);
    exit(1);
}

echo 'WAVE 2C-FIX DIAGNOSTIC PDF MIME TEST PASSED' . PHP_EOL;
exit(0);
