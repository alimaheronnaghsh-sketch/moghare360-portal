<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Wave 2C Diagnostic File Binding CLI Test
 */

$root = dirname(__DIR__);
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

$helperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-diagnostic-file-helper.php';
$diagPagePath = $public . DIRECTORY_SEPARATOR . 'erp-jobcard-diagnostic-file.php';
$submitPath = $public . DIRECTORY_SEPARATOR . 'submit-jobcard-diagnostic-file.php';
$previewPath = $public . DIRECTORY_SEPARATOR . 'erp-jobcard-diagnostic-preview.php';
$cameraCapturePath = $public . DIRECTORY_SEPARATOR . 'erp-jobcard-camera-capture.php';
$cameraSubmitPath = $public . DIRECTORY_SEPARATOR . 'submit-jobcard-camera-capture.php';

require_once $helperPath;

$docs = [
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_2_camera_media' . DIRECTORY_SEPARATOR . 'WAVE_2C_DIAGNOSTIC_FILE_BINDING_SCOPE.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_2_camera_media' . DIRECTORY_SEPARATOR . 'WAVE_2C_DIAGNOSTIC_FILE_BINDING_TEST_PLAN.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_2_camera_media' . DIRECTORY_SEPARATOR . 'WAVE_2C_DIAGNOSTIC_FILE_BINDING_RESULT.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_2_camera_media' . DIRECTORY_SEPARATOR . 'WAVE_2C_DIAGNOSTIC_FILE_BINDING_SIGNOFF.md',
];

$helperContent = is_file($helperPath) ? (string)file_get_contents($helperPath) : '';
$submitContent = is_file($submitPath) ? (string)file_get_contents($submitPath) : '';
$diagPageContent = is_file($diagPagePath) ? (string)file_get_contents($diagPagePath) : '';
$cameraCaptureContent = is_file($cameraCapturePath) ? (string)file_get_contents($cameraCapturePath) : '';
$cameraSubmitContent = is_file($cameraSubmitPath) ? (string)file_get_contents($cameraSubmitPath) : '';

$validJpegBinary = hex2bin(
    'ffd8ffdb004300080606070605080707070909080a0c140d0c0b0b0c1912130f141d1a1f1e1d1a1c1c20242e2720222c231c1c2837292c30313434341f27393d38323c2e333432' .
    'ffc00011080001000101011100021101031101ffc40014000100000000000000000000000000000008ffc40014100100000000000000000000000000000000' .
    'ffda0008010100003f0037ffd9'
);

$tempDir = sys_get_temp_dir();
$validImagePath = $tempDir . DIRECTORY_SEPARATOR . 'wave2c_valid.jpg';
file_put_contents($validImagePath, $validJpegBinary !== false ? $validJpegBinary : '');

$results = [];

$results[] = ['name' => 'Diagnostic helper exists', 'pass' => is_file($helperPath)];
$results[] = ['name' => 'Diagnostic page exists', 'pass' => is_file($diagPagePath)];
$results[] = ['name' => 'Diagnostic submit exists', 'pass' => is_file($submitPath)];
$results[] = ['name' => 'Diagnostic preview exists', 'pass' => is_file($previewPath)];

$allowedExtensions = array_keys(moghare360_diagnostic_allowed_mime_map());
$results[] = [
    'name' => 'Helper allows only pdf/jpg/jpeg/png/webp',
    'pass' => $allowedExtensions === ['pdf', 'jpg', 'jpeg', 'png', 'webp'],
];

$basePayload = [
    'jobcard_id' => '1',
    'diagnostic_stage' => 'diagnostic_initial',
    'diagnostic_type' => 'scanner_report',
];

$rejectCases = [
  ['label' => 'exe', 'name' => 'bad.exe', 'bytes' => "MZ"],
  ['label' => 'zip', 'name' => 'bad.zip', 'bytes' => "PK\x03\x04"],
  ['label' => 'rar', 'name' => 'bad.rar', 'bytes' => "Rar!"],
  ['label' => 'docm', 'name' => 'bad.docm', 'bytes' => 'docm'],
];

$rejectPass = true;
foreach ($rejectCases as $case) {
    $path = $tempDir . DIRECTORY_SEPARATOR . 'wave2c_' . $case['label'];
    file_put_contents($path, $case['bytes']);
    $validation = moghare360_validate_diagnostic_file_payload($basePayload, [
        'name' => $case['name'],
        'type' => 'application/octet-stream',
        'tmp_name' => $path,
        'error' => UPLOAD_ERR_OK,
        'size' => strlen($case['bytes']),
    ]);
    if ($validation['ok'] === true) {
        $rejectPass = false;
        break;
    }
}
$results[] = ['name' => 'Helper rejects exe/zip/rar/docm', 'pass' => $rejectPass];

$urlReject = moghare360_validate_diagnostic_file_payload(
    array_merge($basePayload, ['external_url' => 'https://example.com/report.pdf']),
    ['name' => '', 'tmp_name' => '', 'error' => UPLOAD_ERR_NO_FILE, 'size' => 0]
);
$results[] = ['name' => 'Helper rejects external URL', 'pass' => $urlReject['ok'] === false];

$traversalReject = moghare360_validate_diagnostic_file_payload($basePayload, [
    'name' => '../evil.pdf',
    'type' => 'application/pdf',
    'tmp_name' => $validImagePath,
    'error' => UPLOAD_ERR_OK,
    'size' => 100,
]);
$results[] = ['name' => 'Helper rejects path traversal filename', 'pass' => $traversalReject['ok'] === false];

$results[] = [
    'name' => 'Diagnostic submit uses metadata registration',
    'pass' => $submitContent !== ''
        && str_contains($submitContent, 'moghare360_diagnostic_save_file')
        && str_contains($submitContent, 'moghare360_diagnostic_register_metadata')
        && strpos($submitContent, 'moghare360_diagnostic_save_file') < strpos($submitContent, 'moghare360_diagnostic_register_metadata'),
];

$results[] = [
    'name' => 'Diagnostic helper writes history',
    'pass' => $helperContent !== ''
        && str_contains($helperContent, 'DIAGNOSTIC_FILE_REGISTERED')
        && str_contains($helperContent, 'erp_jobcard_media_history'),
];

$results[] = [
    'name' => 'Camera capture page remains without file input',
    'pass' => $cameraCaptureContent !== '' && !preg_match('/type\s*=\s*["\']file["\']/i', $cameraCaptureContent),
];

$results[] = [
    'name' => 'Camera submit remains unchanged (no diagnostic helper)',
    'pass' => $cameraSubmitContent !== ''
        && !str_contains($cameraSubmitContent, 'moghare360-diagnostic-file-helper.php')
        && str_contains($cameraSubmitContent, 'moghare360-camera-media-helper.php'),
];

$wave2cSqlCreated = false;
foreach (glob($root . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . '*wave_2c*') ?: [] as $sqlFile) {
    if (is_file($sqlFile)) {
        $wave2cSqlCreated = true;
        break;
    }
}
$results[] = ['name' => 'No SQL files created for Wave 2C', 'pass' => !$wave2cSqlCreated];

$results[] = [
    'name' => 'Diagnostic page has controlled file input only',
    'pass' => $diagPageContent !== ''
        && str_contains($diagPageContent, 'Only controlled diagnostic PDF/image files are accepted')
        && preg_match('/name\s*=\s*["\']diagnostic_file["\']/i', $diagPageContent) === 1,
];

$results[] = [
    'name' => 'Helper registers metadata in erp_jobcard_media',
    'pass' => $helperContent !== '' && str_contains($helperContent, 'INSERT INTO dbo.erp_jobcard_media'),
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
    fwrite(STDERR, 'WAVE 2C DIAGNOSTIC FILE BINDING TEST FAILED' . PHP_EOL);
    exit(1);
}

echo 'WAVE 2C DIAGNOSTIC FILE BINDING TEST PASSED' . PHP_EOL;
exit(0);
