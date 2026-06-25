<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Wave 1C Critical Form Pages CLI Test
 */

$root = dirname(__DIR__);
$public = $root . DIRECTORY_SEPARATOR . 'public_html';

$requiredFiles = [
    $public . DIRECTORY_SEPARATOR . 'erp-customer-create-v2.php',
    $public . DIRECTORY_SEPARATOR . 'submit-customer-v2.php',
    $public . DIRECTORY_SEPARATOR . 'erp-vehicle-create-v2.php',
    $public . DIRECTORY_SEPARATOR . 'submit-vehicle-v2.php',
    $public . DIRECTORY_SEPARATOR . 'erp-jobcard-create-v2.php',
    $public . DIRECTORY_SEPARATOR . 'submit-jobcard-v2.php',
    $public . DIRECTORY_SEPARATOR . 'erp-critical-forms-v2-live-preview.php',
    $public . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-form-validation-bridge.php',
];

$submitFiles = [
    $public . DIRECTORY_SEPARATOR . 'submit-customer-v2.php',
    $public . DIRECTORY_SEPARATOR . 'submit-vehicle-v2.php',
    $public . DIRECTORY_SEPARATOR . 'submit-jobcard-v2.php',
];

$bridgeMarker = 'moghare360-form-validation-bridge.php';
$dbDisabledMarker = 'DB write intentionally not activated in WAVE 1C';

$results = [];

foreach ($requiredFiles as $path) {
    $name = basename($path);
    $results[] = [
        'name' => "File exists: {$name}",
        'pass' => is_file($path),
    ];
}

foreach ($submitFiles as $path) {
    $name = basename($path);
    $content = is_file($path) ? (string)file_get_contents($path) : '';

    $results[] = [
        'name' => "Submit includes bridge: {$name}",
        'pass' => $content !== '' && str_contains($content, $bridgeMarker),
    ];

    $results[] = [
        'name' => "Submit DB-write disabled message: {$name}",
        'pass' => $content !== '' && str_contains($content, $dbDisabledMarker),
    ];
}

$wave1cSqlCreated = false;
$wave1cPaths = [
    $public . DIRECTORY_SEPARATOR . 'erp-customer-create-v2.php',
    $public . DIRECTORY_SEPARATOR . 'submit-customer-v2.php',
    $public . DIRECTORY_SEPARATOR . 'erp-vehicle-create-v2.php',
    $public . DIRECTORY_SEPARATOR . 'submit-vehicle-v2.php',
    $public . DIRECTORY_SEPARATOR . 'erp-jobcard-create-v2.php',
    $public . DIRECTORY_SEPARATOR . 'submit-jobcard-v2.php',
    $public . DIRECTORY_SEPARATOR . 'erp-critical-forms-v2-live-preview.php',
    $root . DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR . 'test-wave-1c-critical-form-pages.php',
];

foreach ($wave1cPaths as $path) {
    if (str_ends_with(strtolower($path), '.sql')) {
        $wave1cSqlCreated = true;
        break;
    }
}

$results[] = [
    'name' => 'No SQL file created by Wave 1C deliverables',
    'pass' => !$wave1cSqlCreated,
];

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

$total = count($results);
echo PHP_EOL;
echo 'Passed: ' . $passed . ' / ' . $total . PHP_EOL;

if ($failed > 0) {
    fwrite(STDERR, 'WAVE 1C CRITICAL FORM PAGES TEST FAILED' . PHP_EOL);
    exit(1);
}

echo 'WAVE 1C CRITICAL FORM PAGES TEST PASSED' . PHP_EOL;
exit(0);
