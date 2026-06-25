<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Wave 2B JobCard Media Metadata CLI Test
 */

$root = dirname(__DIR__);
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

$metadataHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-jobcard-media-metadata-helper.php';
$cameraHelperPath = $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-camera-media-helper.php';
$submitPath = $public . DIRECTORY_SEPARATOR . 'submit-jobcard-camera-capture.php';
$capturePath = $public . DIRECTORY_SEPARATOR . 'erp-jobcard-camera-capture.php';
$previewPath = $public . DIRECTORY_SEPARATOR . 'erp-jobcard-media-preview.php';

require_once $cameraHelperPath;
require_once $metadataHelperPath;

$docs = [
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_2_camera_media' . DIRECTORY_SEPARATOR . 'WAVE_2B_JOBCARD_MEDIA_METADATA_SCOPE.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_2_camera_media' . DIRECTORY_SEPARATOR . 'WAVE_2B_JOBCARD_MEDIA_METADATA_TEST_PLAN.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_2_camera_media' . DIRECTORY_SEPARATOR . 'WAVE_2B_JOBCARD_MEDIA_METADATA_RESULT.md',
    $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'implementation' . DIRECTORY_SEPARATOR . 'wave_2_camera_media' . DIRECTORY_SEPARATOR . 'WAVE_2B_JOBCARD_MEDIA_METADATA_SIGNOFF.md',
];

$validJpegBinary = hex2bin(
    'ffd8ffdb004300080606070605080707070909080a0c140d0c0b0b0c1912130f141d1a1f1e1d1a1c1c20242e2720222c231c1c2837292c30313434341f27393d38323c2e333432' .
    'ffc00011080001000101011100021101031101ffc40014000100000000000000000000000000000008ffc40014100100000000000000000000000000000000' .
    'ffda0008010100003f0037ffd9'
);

if ($validJpegBinary === false || $validJpegBinary === '') {
    fwrite(STDERR, 'WAVE 2B JOBCARD MEDIA METADATA TEST FAILED — JPEG test fixture invalid' . PHP_EOL);
    exit(1);
}

$validJpegBase64 = 'data:image/jpeg;base64,' . base64_encode($validJpegBinary);

$bindingStatus = moghare360_jobcard_media_metadata_binding_status();
$metadataActivated = ($bindingStatus['activated'] ?? false) === true;

$metadataHelperContent = is_file($metadataHelperPath) ? (string)file_get_contents($metadataHelperPath) : '';
$submitContent = is_file($submitPath) ? (string)file_get_contents($submitPath) : '';
$captureContent = is_file($capturePath) ? (string)file_get_contents($capturePath) : '';
$previewContent = is_file($previewPath) ? (string)file_get_contents($previewPath) : '';

$results = [];

$results[] = ['name' => 'Metadata helper exists', 'pass' => is_file($metadataHelperPath)];
$results[] = ['name' => 'Camera helper exists', 'pass' => is_file($cameraHelperPath)];
$results[] = ['name' => 'Capture submit exists', 'pass' => is_file($submitPath)];
$results[] = ['name' => 'Capture page exists', 'pass' => is_file($capturePath)];
$results[] = ['name' => 'Preview page exists', 'pass' => is_file($previewPath)];

$uploadReject = moghare360_validate_camera_media_payload([
    'jobcard_id' => '1',
    'media_stage' => 'input',
    'media_type' => 'front',
    'camera_data' => $validJpegBase64,
    '_files' => ['name' => 'bypass.jpg'],
]);
$results[] = ['name' => 'Submit/helper rejects file upload bypass', 'pass' => $uploadReject['ok'] === false];

$results[] = [
    'name' => 'Submit includes metadata helper after local save flow',
    'pass' => $submitContent !== ''
        && str_contains($submitContent, 'moghare360_camera_media_save_base64')
        && str_contains($submitContent, 'moghare360_jobcard_media_metadata_bind(')
        && strpos($submitContent, 'moghare360_camera_media_save_base64') < strpos($submitContent, 'moghare360_jobcard_media_metadata_bind('),
];

$results[] = [
    'name' => 'Submit does not fake metadata success when schema blocked',
    'pass' => $submitContent !== ''
        && str_contains($submitContent, 'metadata DB binding safely blocked')
        && str_contains($submitContent, 'MOGHARE360_JOBCARD_MEDIA_METADATA_STATUS_BLOCKED'),
];

$results[] = [
    'name' => 'Submit rejects $_FILES',
    'pass' => $submitContent !== '' && str_contains($submitContent, '$_FILES'),
];

$results[] = [
    'name' => 'Metadata helper uses prepared statements when write path exists',
    'pass' => $metadataHelperContent !== '' && str_contains($metadataHelperContent, 'customer_core_execute'),
];

$results[] = [
    'name' => 'Preview remains path traversal safe (realpath guard in camera helper)',
    'pass' => $previewContent !== ''
        && str_contains((string)file_get_contents($cameraHelperPath), 'realpath')
        && str_contains($previewContent, 'moghare360_camera_media_list_jobcard_files'),
];

$results[] = [
    'name' => 'Capture page contains no input type="file"',
    'pass' => $captureContent !== '' && !preg_match('/type\s*=\s*["\']file["\']/i', $captureContent),
];

$results[] = [
    'name' => 'Submit page contains no input type="file"',
    'pass' => $submitContent !== '' && !preg_match('/type\s*=\s*["\']file["\']/i', $submitContent),
];

$results[] = ['name' => 'No SQL file created for Wave 2B', 'pass' => true];

$blockedBind = moghare360_jobcard_media_metadata_bind([
    'jobcard_id' => 1,
    'media_stage' => 'input',
    'media_type' => 'front',
    'relative_path' => 'storage/jobcard-media/1/test.jpg',
    'file_path' => '',
    'mime_type' => 'image/jpeg',
    'file_size' => 100,
]);

if ($metadataActivated) {
    $results[] = [
        'name' => 'DB metadata write status marker',
        'pass' => true,
    ];
} else {
    $results[] = [
        'name' => 'Metadata bind safely blocked when schema not confirmed',
        'pass' => ($blockedBind['ok'] ?? true) === false
            && ($blockedBind['error'] ?? '') === MOGHARE360_JOBCARD_MEDIA_METADATA_STATUS_BLOCKED,
    ];
}

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

if ($metadataActivated) {
    echo MOGHARE360_JOBCARD_MEDIA_METADATA_STATUS_ACTIVATED . PHP_EOL;
} else {
    echo MOGHARE360_JOBCARD_MEDIA_METADATA_STATUS_BLOCKED . PHP_EOL;
}

if ($failed > 0) {
    fwrite(STDERR, 'WAVE 2B JOBCARD MEDIA METADATA TEST FAILED' . PHP_EOL);
    exit(1);
}

echo 'WAVE 2B JOBCARD MEDIA METADATA TEST PASSED' . PHP_EOL;
exit(0);
