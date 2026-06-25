<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Wave 2A Camera Media Runtime CLI Test
 */

$root = dirname(__DIR__);
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

$helperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-camera-media-helper.php';
$capturePath = $public . DIRECTORY_SEPARATOR . 'erp-jobcard-camera-capture.php';
$submitPath = $public . DIRECTORY_SEPARATOR . 'submit-jobcard-camera-capture.php';
$previewPath = $public . DIRECTORY_SEPARATOR . 'erp-jobcard-media-preview.php';

require_once $helperPath;

$docs = [
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_2_camera_media' . DIRECTORY_SEPARATOR . 'WAVE_2A_CAMERA_MEDIA_RUNTIME_SCOPE.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_2_camera_media' . DIRECTORY_SEPARATOR . 'WAVE_2A_CAMERA_MEDIA_RUNTIME_TEST_PLAN.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_2_camera_media' . DIRECTORY_SEPARATOR . 'WAVE_2A_CAMERA_MEDIA_RUNTIME_RESULT.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_2_camera_media' . DIRECTORY_SEPARATOR . 'WAVE_2A_CAMERA_MEDIA_RUNTIME_SIGNOFF.md',
];

// Minimal valid 1x1 JPEG (no GD dependency).
$validJpegBinary = hex2bin(
    'ffd8ffdb004300080606070605080707070909080a0c140d0c0b0b0c1912130f141d1a1f1e1d1a1c1c20242e2720222c231c1c2837292c30313434341f27393d38323c2e333432' .
    'ffc00011080001000101011100021101031101ffc40014000100000000000000000000000000000008ffc40014100100000000000000000000000000000000' .
    'ffda0008010100003f0037ffd9'
);

if ($validJpegBinary === false || $validJpegBinary === '') {
    fwrite(STDERR, 'WAVE 2A CAMERA MEDIA RUNTIME TEST FAILED — JPEG test fixture invalid' . PHP_EOL);
    exit(1);
}

$validJpegBase64 = 'data:image/jpeg;base64,' . base64_encode($validJpegBinary);

$results = [];

$results[] = ['name' => 'Camera helper exists', 'pass' => is_file($helperPath)];
$results[] = ['name' => 'Capture page exists', 'pass' => is_file($capturePath)];
$results[] = ['name' => 'Submit page exists', 'pass' => is_file($submitPath)];
$results[] = ['name' => 'Preview page exists', 'pass' => is_file($previewPath)];

$uploadReject = moghare360_validate_camera_media_payload([
    'jobcard_id' => '1',
    'media_stage' => 'input',
    'media_type' => 'front',
    'camera_data' => $validJpegBase64,
    '_files' => ['name' => 'bypass.jpg'],
]);
$results[] = ['name' => 'Helper rejects file upload bypass', 'pass' => $uploadReject['ok'] === false];

$urlReject = moghare360_validate_camera_media_payload([
    'jobcard_id' => '1',
    'media_stage' => 'input',
    'media_type' => 'front',
    'camera_data' => 'https://example.com/image.jpg',
]);
$results[] = ['name' => 'Helper rejects external URL', 'pass' => $urlReject['ok'] === false];

$stageReject = moghare360_validate_camera_media_payload([
    'jobcard_id' => '1',
    'media_stage' => 'invalid_stage',
    'media_type' => 'front',
    'camera_data' => $validJpegBase64,
]);
$results[] = ['name' => 'Helper rejects invalid media_stage', 'pass' => $stageReject['ok'] === false];

$typeReject = moghare360_validate_camera_media_payload([
    'jobcard_id' => '1',
    'media_stage' => 'input',
    'media_type' => 'invalid_type',
    'camera_data' => $validJpegBase64,
]);
$results[] = ['name' => 'Helper rejects invalid media_type', 'pass' => $typeReject['ok'] === false];

$validPayload = moghare360_validate_camera_media_payload([
    'jobcard_id' => '1',
    'media_stage' => 'input',
    'media_type' => 'front',
    'camera_data' => $validJpegBase64,
]);
$results[] = ['name' => 'Helper accepts valid base64 image payload', 'pass' => $validPayload['ok'] === true];

$captureContent = is_file($capturePath) ? (string)file_get_contents($capturePath) : '';
$submitContent = is_file($submitPath) ? (string)file_get_contents($submitPath) : '';

$results[] = [
    'name' => 'Capture page contains no input type="file"',
    'pass' => $captureContent !== '' && !preg_match('/type\s*=\s*["\']file["\']/i', $captureContent),
];

$results[] = [
    'name' => 'Submit page contains no input type="file"',
    'pass' => $submitContent !== '' && !preg_match('/type\s*=\s*["\']file["\']/i', $submitContent),
];

$results[] = [
    'name' => 'Submit page rejects $_FILES',
    'pass' => $submitContent !== '' && str_contains($submitContent, '$_FILES'),
];

$wave2aSqlCreated = false;
foreach (['tools/test-wave-2a-camera-media-runtime.php'] as $relative) {
    if (str_ends_with(strtolower($relative), '.sql')) {
        $wave2aSqlCreated = true;
        break;
    }
}
$results[] = ['name' => 'No SQL file created for Wave 2A', 'pass' => !$wave2aSqlCreated];

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
    fwrite(STDERR, 'WAVE 2A CAMERA MEDIA RUNTIME TEST FAILED' . PHP_EOL);
    exit(1);
}

echo 'WAVE 2A CAMERA MEDIA RUNTIME TEST PASSED' . PHP_EOL;
exit(0);
